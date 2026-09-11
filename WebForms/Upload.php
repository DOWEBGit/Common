<?php
declare(strict_types=1);

namespace Common\WebForms;

/**
 * Il canale di upload, separato dal postback.
 *
 * Un file non passa dal postback: quello e' una POST con i campi del form e risponde JSON.
 * Qui il file arriva da solo, viene messo da parte, e al postback successivo il controllo
 * riceve solo un TOKEN. Cosi' lo stato della pagina non si porta dietro megabyte e il
 * postback resta leggero come prima.
 *
 * Il file finisce nella cartella temporanea di sistema, FUORI dalla radice del sito: sotto
 * Public/Php sarebbe raggiungibile da URL, e un upload diventerebbe pubblicazione.
 */
class Upload
{
    /** Quanto vale un token, e quanto resta in giro un temporaneo: un'ora per compilare. */
    private const DURATA = 3600;

    /** Oltre questo si rifiuta. Resta comunque sotto il limite di PHP, che vince sempre. */
    private const BYTE_MASSIMI = 8 * 1024 * 1024;

    /**
     * I formati che si accettano, e basta.
     *
     * Niente WEBP: la piattaforma non lo legge, e quando non capisce un'immagine risponde
     * "Dimensioni minime 10 pixel" - che sembra un problema di misure. Un'immagine che non
     * va bene si RIFIUTA qui, dicendolo; non si converte di nascosto. Chi carica deve sapere
     * cosa e' finito nel sito, e un file che entra diverso da come e' stato scelto e' una
     * bugia detta a fin di bene.
     */
    private const TIPI = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
    ];

    /**
     * I documenti, riconosciuti dai byte iniziali.
     *
     * Estensione => [mime, firma]. La firma e' quella vera del formato: un .pdf che non
     * comincia con %PDF- non e' un pdf, comunque si chiami. Dove la firma e' vuota - testo
     * e csv - non c'e' niente da riconoscere: sono file inerti e si accettano per estensione.
     *
     * Non si usa mime_content_type: qui l'estensione fileinfo non e' caricata. E non ci si
     * fida del tipo dichiarato dal browser, che lo scrive lui.
     */
    private const DOCUMENTI = [
        'pdf'  => ['application/pdf', "%PDF-"],
        'zip'  => ['application/zip', "PK\x03\x04"],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', "PK\x03\x04"],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', "PK\x03\x04"],
        'doc'  => ['application/msword', "\xD0\xCF\x11\xE0"],
        'xls'  => ['application/vnd.ms-excel', "\xD0\xCF\x11\xE0"],
        'txt'  => ['text/plain', ''],
        'csv'  => ['text/csv', ''],
    ];

    /** Le due famiglie, per capire se un file si guarda o si scarica. */
    public const IMAGES = 'immagini';
    public const DOCUMENTS_ONLY = 'documenti';

    /** Riceve il file e risponde con il token. Chiamato da Common/WebForms/FileUploadHandler.php. */
    public static function Receive(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !Csrf::Verify())
        {
            http_response_code(403);
            echo json_encode(['errore' => 'Richiesta non autorizzata.']);
            return;
        }

        $file = $_FILES['file'] ?? null;

        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK)
        {
            echo json_encode(['errore' => self::Motivo($file['error'] ?? UPLOAD_ERR_NO_FILE)]);
            return;
        }

        if ($file['size'] > self::BYTE_MASSIMI)
        {
            echo json_encode(['errore' => 'File troppo grande: massimo '
                . round(self::BYTE_MASSIMI / 1024 / 1024) . ' MB.']);
            return;
        }

        $nome = self::NomePulito((string)$file['name']);

        //Il tipo si legge dal CONTENUTO, non da quello che dichiara il browser: l'intestazione
        //la scrive il client e vale quanto una promessa.
        [$tipo, $estensione] = self::Riconosci($file['tmp_name'], $nome);

        if ($tipo === '')
        {
            //il messaggio dice cosa si accetta: "formato non ammesso" e basta lascia chi
            //carica a indovinare cosa ha sbagliato
            echo json_encode(['errore' => 'Formato non ammesso: servono JPG, PNG, GIF, '
                . 'oppure PDF, Word, Excel, ZIP, TXT o CSV.']);
            return;
        }

        $destinazione = sys_get_temp_dir() . DIRECTORY_SEPARATOR
            . 'dwup_' . bin2hex(random_bytes(16)) . '.' . $estensione;

        if (!@move_uploaded_file($file['tmp_name'], $destinazione))
        {
            echo json_encode(['errore' => 'Non riesco a mettere da parte il file.']);
            return;
        }

        $voce = [
            'nome'       => $nome,
            'percorso'   => $destinazione,
            'dimensione' => (int)filesize($destinazione),
            'tipo'       => $tipo,
        ];

        //i vincoli del campo a cui il file e' destinato: il riferimento lo manda il client,
        //e vale come cortesia - dice subito la cosa giusta nella zona di trascinamento. La
        //guardia vera e' altrove: il controllo ricontrolla al postback col riferimento del
        //markup, e Kestrel al salvataggio.
        $rifiuto = self::CheckConstraints($voce, (string)($_POST['vincoli'] ?? ''));

        if ($rifiuto !== '')
        {
            @unlink($destinazione);

            echo json_encode(['errore' => $rifiuto], JSON_UNESCAPED_UNICODE);
            return;
        }

        //un file caricato e mai salvato resterebbe nel temporaneo per sempre: si fa pulizia
        //qui, che e' l'unico momento in cui si sa di sicuro che qualcuno sta caricando
        self::Pota();

        $token = ViewState::Pack([
            'nome'       => $nome,
            'percorso'   => $destinazione,
            'dimensione' => (int)filesize($destinazione),
            'tipo'       => $tipo,
            'fino'       => time() + self::DURATA,
            'chi'        => Csrf::Token(),
        ]);

        echo json_encode([
            'token'      => $token,
            'nome'       => $nome,
            'dimensione' => (int)filesize($destinazione),
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Che cos'e' questo file: [mime, estensione], o ['', ''] se non e' niente di ammesso.
     *
     * Prima le immagini, con getimagesize: e' piu' severa di un confronto sui primi byte,
     * perche' il file deve essere davvero un'immagine leggibile e non solo cominciare bene.
     * Poi i documenti, sulla firma; e per gli inerti, sull'estensione.
     */
    private static function Riconosci(string $percorso, string $nome): array
    {
        $dimensioni = @getimagesize($percorso);

        $tipo = is_array($dimensioni) ? ($dimensioni['mime'] ?? '') : '';

        if (isset(self::TIPI[$tipo]))
            return [$tipo, self::TIPI[$tipo]];

        $estensione = strtolower(pathinfo($nome, PATHINFO_EXTENSION));

        if (!isset(self::DOCUMENTI[$estensione]))
            return ['', ''];

        [$mime, $firma] = self::DOCUMENTI[$estensione];

        if ($firma === '')
            return [$mime, $estensione];

        $inizio = @file_get_contents($percorso, false, null, 0, strlen($firma));

        return $inizio === $firma ? [$mime, $estensione] : ['', ''];
    }

    /**
     * Il file rispetta i vincoli del campo? Torna il perche' del rifiuto, o stringa vuota.
     *
     * Le regole arrivano dall'attributo che il generatore copia dal pannello: estensioni
     * ammesse e peso massimo. Qui non ce ne sono di proprie - senza un riferimento valido
     * non si rifiuta niente, perche' inventarsi una regola sarebbe peggio che non averla.
     *
     * @param array $voce quello che Info() sa del file
     */
    public static function CheckConstraints(array $voce, string $riferimento): string
    {
        $vincoli = \Common\Attribute\VincoliAttribute::Di($riferimento);

        if ($vincoli === null)
            return '';

        $ammesse = $vincoli->EstensioniAmmesse();

        $estensione = strtolower(pathinfo((string)$voce['nome'], PATHINFO_EXTENSION));

        if ($ammesse !== [] && !in_array($estensione, $ammesse, true))
        {
            //l'elenco si mostra com'e' scritto nel pannello: e' quello che l'utente vedrebbe
            //anche salvando, e due frasi diverse per la stessa regola confondono e basta
            return ($vincoli->AvvisoNonValido !== '' ? $vincoli->AvvisoNonValido . ' ' : '')
                . 'Qui ci vuole: ' . implode(', ', $ammesse) . '.';
        }

        if ($vincoli->KBytesMax > 0 && (int)$voce['dimensione'] > $vincoli->KBytesMax * 1024)
        {
            return 'File troppo grande: il massimo e\' '
                . number_format($vincoli->KBytesMax / 1024, 1, ',', '.') . ' MB.';
        }

        return self::Misure($voce, $vincoli);
    }

    /**
     * Le misure di un'immagine, contro quelle che il pannello ammette.
     *
     * Perche' qui e non solo al salvataggio: senza questo controllo un'immagine da 2166x1601
     * viene accettata, caricata, mostrata in anteprima, e poi rifiutata dalla Save con
     * "Immagine: Valore non valido" - una frase che non dice cosa c'e' che non va, e arriva
     * dopo che l'utente ha compilato tutto il resto della scheda. Il momento giusto per dire
     * di no e' quando il file arriva, e la frase giusta contiene i numeri.
     *
     * L'ultima parola resta a Kestrel, che ricontrolla al salvataggio: questo taglia il
     * viaggio, non la guardia.
     */
    private static function Misure(array $voce, \Common\Attribute\VincoliAttribute $vincoli): string
    {
        if ($vincoli->LarghezzaMin <= 0 && $vincoli->LarghezzaMax <= 0
            && $vincoli->AltezzaMin <= 0 && $vincoli->AltezzaMax <= 0)
            return '';

        $misure = @getimagesize($voce['percorso']);

        //un documento non ha misure, e non e' un errore: le regole sulle dimensioni valgono
        //per i campi immagine, e li' getimagesize risponde sempre
        if ($misure === false)
            return '';

        [$larghezza, $altezza] = $misure;

        $quanto = $larghezza . 'x' . $altezza . ' pixel';

        if ($vincoli->LarghezzaMax > 0 && $larghezza > $vincoli->LarghezzaMax)
            return 'Immagine troppo larga: e\' ' . $quanto . ', il massimo e\' '
                . $vincoli->LarghezzaMax . ' pixel di larghezza.';

        if ($vincoli->AltezzaMax > 0 && $altezza > $vincoli->AltezzaMax)
            return 'Immagine troppo alta: e\' ' . $quanto . ', il massimo e\' '
                . $vincoli->AltezzaMax . ' pixel di altezza.';

        if ($vincoli->LarghezzaMin > 0 && $larghezza < $vincoli->LarghezzaMin)
            return 'Immagine troppo stretta: e\' ' . $quanto . ', il minimo e\' '
                . $vincoli->LarghezzaMin . ' pixel di larghezza.';

        if ($vincoli->AltezzaMin > 0 && $altezza < $vincoli->AltezzaMin)
            return 'Immagine troppo bassa: e\' ' . $quanto . ', il minimo e\' '
                . $vincoli->AltezzaMin . ' pixel di altezza.';

        return '';
    }

    /** A quale famiglia appartiene un tipo: e' quello che serve per capire come servirlo. */
    public static function Family(string $tipo): string
    {
        return isset(self::TIPI[$tipo]) ? self::IMAGES : self::DOCUMENTS_ONLY;
    }

    /**
     * Serve il file messo da parte, per l'anteprima.
     *
     * Passa dalla sessione: il token vale solo per chi l'ha caricato, quindi un file non
     * ancora salvato non e' raggiungibile da nessun altro.
     */
    public static function Show(string $token): void
    {
        $voce = self::Info($token);

        if ($voce === null)
        {
            http_response_code(404);
            return;
        }

        header('Content-Type: ' . $voce['tipo']);
        header('Content-Length: ' . filesize($voce['percorso']));
        header('Cache-Control: private, max-age=60');

        //un documento si scarica col suo nome, un'immagine si guarda: inline per le une,
        //attachment per gli altri. Il nome e' gia' ripulito da NomePulito, quindi non ci
        //puo' finire un a capo che spezzerebbe l'intestazione
        if (self::Family($voce['tipo']) === self::DOCUMENTS_ONLY)
            header('Content-Disposition: attachment; filename="' . $voce['nome'] . '"');

        readfile($voce['percorso']);
    }

    /**
     * Quello che si sa di un file messo da parte, o null.
     *
     * Il token NON e' una chiave in una tabella: e' il dato stesso, firmato. Dentro ci sono
     * nome, percorso, tipo, scadenza e il browser a cui appartiene; se la firma non torna,
     * se e' scaduto, se e' di un altro browser o se il file non c'e' piu', non vale.
     */
    public static function Info(string $token): ?array
    {
        if ($token === '')
            return null;

        $voce = ViewState::Unpack($token);

        if ($voce === null)
            return null;

        //il file resta raggiungibile solo a chi l'ha caricato: il legame e' il cookie del
        //motore, che un altro browser non ha
        if (($voce['chi'] ?? '') !== Csrf::Token())
            return null;

        if (time() > (int)($voce['fino'] ?? 0))
            return null;

        return is_string($voce['percorso'] ?? null) && is_file($voce['percorso']) ? $voce : null;
    }

    /** Il contenuto del file, o null. */
    public static function Content(string $token): ?string
    {
        $voce = self::Info($token);

        if ($voce === null)
            return null;

        $dati = @file_get_contents($voce['percorso']);

        return $dati === false ? null : $dati;
    }

    /** Il file e' stato consumato: si butta, cosi' non resta in giro piu' del necessario. */
    public static function Consume(string $token): void
    {
        $voce = self::Info($token);

        if ($voce !== null)
            @unlink($voce['percorso']);
    }

    /**
     * Butta i temporanei vecchi.
     *
     * Si guarda la CARTELLA e non un elenco tenuto da qualche parte: un elenco in sessione
     * vedeva solo i file di quella sessione, quindi quelli di una sessione morta restavano
     * li' per sempre - misurati, dieci alla volta. Qui invece si passa da tutti i dwup_
     * scaduti, di chiunque fossero.
     */
    private static function Pota(): void
    {
        $vecchi = glob(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dwup_*');

        if ($vecchi === false)
            return;

        foreach ($vecchi as $file)
            if (is_file($file) && time() - (int)@filemtime($file) > self::DURATA)
                @unlink($file);
    }

    /** Il nome arriva dal client e finisce in un database: si tiene solo il nome del file. */
    private static function NomePulito(string $nome): string
    {
        $nome = basename(str_replace('\\', '/', $nome));

        $nome = preg_replace('/[^A-Za-z0-9._ -]/', '', $nome) ?? '';

        return $nome === '' ? 'immagine' : mb_substr($nome, 0, 120);
    }

    private static function Motivo(int $errore): string
    {
        return match ($errore) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File troppo grande per la configurazione di PHP.',
            UPLOAD_ERR_PARTIAL                        => 'Caricamento interrotto.',
            UPLOAD_ERR_NO_FILE                        => 'Nessun file ricevuto.',
            default                                   => 'Caricamento non riuscito.',
        };
    }
}
