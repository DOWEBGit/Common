<?php
declare(strict_types=1);

namespace Common\WebForms;

/**
 * Lo stato della pagina fra un postback e l'altro: il ViewState, e nient'altro.
 *
 * Lo stato viaggia in un campo nascosto, compresso e FIRMATO. Non tocca la sessione: il
 * server non tiene niente fra una richiesta e l'altra, quindi lo stato non scade finche' la
 * pagina resta aperta, sopravvive a un riavvio del server e non chiede che le richieste
 * dello stesso utente si mettano in fila per un lock di sessione. Si paga in banda, in
 * andata e ritorno, ad ogni postback.
 *
 * La firma non e' un dettaglio: senza, il client si riscrive lo stato come vuole e le
 * proprieta' dei controlli diventano quello che decide lui. La verifica avviene prima di
 * toccare il contenuto.
 *
 * C'era anche un modo "in sessione", con un anello di slot: tolto. Faceva scadere le pagine
 * aperte da un po', costringeva ad aprire una sessione per ogni pagina, e serializzava le
 * richieste dello stesso browser sul lock del file di sessione - proprio quando i postback
 * si accavallano.
 */
class ViewState
{
    /** Marcatore di versione: se un giorno cambia il formato, i vecchi pacchetti si scartano. */
    private const VERSIONE = 'v1';

    public static function NewKey(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Stato -> stringa da mettere nel campo nascosto.
     *
     * serialize, poi gzdeflate, poi base64, poi firma. La compressione sta prima del base64
     * perche' dopo non comprimerebbe piu' niente, e prima della firma perche' cosi' si firma
     * esattamente cio' che viaggia: se un byte cambia per strada, la firma non torna.
     */
    public static function Pack(array $dati): string
    {
        $grezzo = serialize($dati);

        $compresso = gzdeflate($grezzo, 9);

        //gzdeflate torna false sui dati che non riesce a comprimere: meglio spedirli
        //cosi' come sono che perdere lo stato
        $corpo = $compresso === false
            ? 'r' . base64_encode($grezzo)
            : 'z' . base64_encode($compresso);

        $pacchetto = self::VERSIONE . '.' . $corpo;

        return $pacchetto . '.' . self::Firma($pacchetto);
    }

    /**
     * Stringa del campo nascosto -> stato, o null se non e' nostra.
     *
     * Null significa "ricomincia da capo": pacchetto manomesso, chiave cambiata, formato
     * vecchio. Chi chiama lo tratta come uno stato scaduto.
     */
    public static function Unpack(string $pacchetto): ?array
    {
        $pos = strrpos($pacchetto, '.');

        if ($pos === false)
            return null;

        $corpo = substr($pacchetto, 0, $pos);
        $firma = substr($pacchetto, $pos + 1);

        //hash_equals e non ==: il confronto a tempo costante toglie di mezzo la possibilita'
        //di indovinare la firma un carattere alla volta misurando i tempi di risposta
        if (!hash_equals(self::Firma($corpo), $firma))
            return null;

        if (!str_starts_with($corpo, self::VERSIONE . '.'))
            return null;

        $dati = substr($corpo, strlen(self::VERSIONE) + 1);

        $modo = $dati[0] ?? '';

        $binario = base64_decode(substr($dati, 1), true);

        if ($binario === false)
            return null;

        if ($modo === 'z')
        {
            $binario = @gzinflate($binario);

            if ($binario === false)
                return null;
        }
        elseif ($modo !== 'r')
        {
            return null;
        }

        //allowed_classes false: qui dentro ci vanno solo scalari e array, e un pacchetto
        //firmato non dovrebbe mai contenere oggetti. Se ci finiscono, non si istanziano.
        $stato = @unserialize($binario, ['allowed_classes' => false]);

        return is_array($stato) ? $stato : null;
    }

    private static function Firma(string $testo): string
    {
        return hash_hmac('sha256', $testo, self::Segreto());
    }

    /**
     * Il segreto con cui si firma.
     *
     * Sta in un .php e non in un .txt di proposito: sotto Public/Php un .txt verrebbe
     * servito come file statico e la chiave sarebbe pubblica. Un .php chiesto direttamente
     * esegue e non stampa niente.
     *
     * Nasce alla prima richiesta che serve. Cambiarlo invalida gli stati in circolazione,
     * che e' il comportamento giusto: le pagine aperte si ricaricano pulite.
     */
    private static function Segreto(): string
    {
        static $segreto = null;

        if ($segreto !== null)
            return $segreto;

        $file = __DIR__ . DIRECTORY_SEPARATOR . '_segreto.php';

        if (is_file($file))
        {
            $letto = include $file;

            if (is_string($letto) && $letto !== '')
                return $segreto = $letto;
        }

        $nuovo = bin2hex(random_bytes(32));

        //scrittura atomica: due richieste che lo creano insieme non devono poter leggere
        //un file mezzo scritto, che darebbe firme diverse a caso
        $tmp = $file . '.' . getmypid() . '.tmp';

        if (@file_put_contents($tmp, "<?php return '" . $nuovo . "';\n") !== false)
            @rename($tmp, $file);

        return $segreto = $nuovo;
    }
}
