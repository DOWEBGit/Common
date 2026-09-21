<?php
declare(strict_types=1);

namespace Common;

class TextUtilities
{
    /**
     * Tag che sopravvivono al salvataggio di una richtextbox.
     * span e mark ci sono per colore ed evidenziazione: sono gli unici modi in cui un editor
     * esprime "questa parola è colorata", e senza di loro l'evidenziazione spariva al salvataggio.
     */
    private const TAG_CONSENTITI = ['strong', 'b', 'em', 'i', 'u', 'p', 'br', 'ul', 'ol', 'li', 'span', 'mark'];

    /**
     * Proprietà css che possono restare in un attributo style.
     *
     * Volutamente cortissima: il senso del pulitore è che il testo scritto da un professionista
     * non possa alterare la grafica del sito. Colore, sfondo e sottolineatura non fanno danni;
     * font, dimensioni, margini e allineamenti sì, e restano fuori.
     */
    private const STILI_CONSENTITI = ['color', 'background-color', 'background', 'text-decoration', 'text-decoration-line'];

    /**
     * Funzione che pulisce tutti gli stili in linea quando si salva, utile per le richtextbox per mantenere gli stili di un sito
     * consistenti.
     * @param string $text
     * @return string
     */
    public static function CleanText(string $text): string
    {
        if (empty($text))
            return '';

        // Converti tag blocco non consentiti in <p> per preservare gli a capo.
        // Molti editor (Quill, TinyMCE, ecc.) usano <div>, <h1>-<h6>, ecc.
        // Senza questa conversione il testo risulterebbe privo di separatori.
        $text = preg_replace('/<(div|h[1-6]|blockquote|pre|section|article|header|footer)(\s[^>]*)?>/', '<p>', $text);
        $text = preg_replace('/<\/(div|h[1-6]|blockquote|pre|section|article|header|footer)>/', '</p>', $text);

        // <font color="..."> degli editor vecchi e degli incolla da Word: diventa uno span
        // colorato, che è la stessa cosa scritta in un modo che sappiamo trattare.
        // Anche i <font> senza colore diventano span, così le chiusure restano appaiate.
        $text = preg_replace_callback('/<font\b([^>]*)>/i', function (array $m): string {
            if (preg_match('/\bcolor\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $m[1], $c))
            {
                $colore = trim($c[1] !== '' ? $c[1] : ($c[2] !== '' ? $c[2] : $c[3]));

                if ($colore !== '' && !preg_match('/[<>()]/', $colore))
                    return '<span style="color: ' . $colore . '">';
            }

            return '<span>';
        }, $text);

        $text = preg_replace('/<\/font>/i', '</span>', $text);

        // Rimuove gli attributi da tutti i tag, tranne lo style di span e mark, di cui si tiene
        // solo quel poco elencato in STILI_CONSENTITI. Prima si buttavano via tutti gli attributi
        // di tutti i tag: è lì che il colore dell'evidenziazione moriva.
        $text = preg_replace_callback('/<([a-zA-Z][a-zA-Z0-9]*)\b([^>]*)>/i', function (array $m): string {
            $tag = strtolower($m[1]);

            if ($tag !== 'span' && $tag !== 'mark')
                return '<' . $tag . '>';

            $stile = self::StileConsentito($m[2]);

            return $stile === '' ? '<' . $tag . '>' : '<' . $tag . ' style="' . $stile . '">';
        }, $text);

        // Strip tutti i tag non consentiti mantenendo il contenuto interno
        $text = preg_replace_callback(
            '/<\/?([a-zA-Z][a-zA-Z0-9]*)\b[^>]*>/i',
            function (array $matches): string {
                $tag = strtolower($matches[1]);

                if (in_array($tag, self::TAG_CONSENTITI, true))
                    return $matches[0];

                return '';
            },
            $text
        );

        // Rimuove il <br> fantasma finale dentro i <p> (aggiunto dal browser per mostrare il cursore)
        // Es: <p>testo<br></p> → <p>testo</p>  |  <p>testo<br><br></p> → <p>testo<br></p>
        // Usa callback per rimuovere solo l'ULTIMO <br> prima di </p>
        $text = preg_replace_callback('/<p>(.*?)<\/p>/is', function(array $m): string {
            $inner = preg_replace('/(<br\s*\/?>)\s*$/i', '', $m[1]);
            return '<p>' . $inner . '</p>';
        }, $text);

        // Converti paragrafi con solo &nbsp; (riga vuota di TinyMCE) in <p><br></p>
        // così le righe intenzionalmente lasciate vuote dall'admin vengono preservate.
        // DEVE stare PRIMA della rimozione dei paragrafi vuoti.
        $text = preg_replace('/<p>\s*(&nbsp;\s*)+<\/p>/i', '<p><br></p>', $text);

        // Rimuove paragrafi vuoti (senza alcun contenuto, nemmeno &nbsp;)
        $text = preg_replace('/<p>\s*<\/p>/i', '', $text);

        // Converte <p><br></p> in semplice <br> per evitare doppio spazio visivo
        // (il <p> con solo <br> dentro genera margini + altezza br = spazio eccessivo)
        $text = preg_replace('/<p>\s*<br\s*\/?>\s*<\/p>/i', '<br>', $text);


        // Normalizza spazi multipli. Fuori dagli attributi style: dentro "background-color: #ff0"
        // gli spazi ci stanno bene, ma altrove due spazi di fila non servono a niente.
        $text = preg_replace('/[ \t]+/', ' ', $text);

        return trim($text);
    }

    /**
     * Estrae da una lista di attributi le sole dichiarazioni di stile ammesse, già pronte da
     * rimettere in un attributo style. Stringa vuota se non ne sopravvive nessuna.
     */
    private static function StileConsentito(string $attributi): string
    {
        if (!preg_match('/\bstyle\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i', $attributi, $m))
            return '';

        $dichiarazioni = $m[1] !== '' ? $m[1] : ($m[2] ?? '');
        $tenute        = [];

        foreach (explode(';', $dichiarazioni) as $dichiarazione)
        {
            $pezzi = explode(':', $dichiarazione, 2);

            if (count($pezzi) !== 2)
                continue;

            $proprieta = strtolower(trim($pezzi[0]));
            $valore    = trim($pezzi[1]);

            if (!in_array($proprieta, self::STILI_CONSENTITI, true) || $valore === '' || !self::ValoreSicuro($valore))
                continue;

            $tenute[] = $proprieta . ': ' . $valore;
        }

        return implode('; ', $tenute);
    }

    /**
     * True se il valore di una dichiarazione è innocuo: un colore o una parola chiave.
     *
     * Si elencano le forme ammesse invece di vietare quelle cattive. Vietare "url" e le parentesi
     * sembrava più semplice, ma buttava via anche `rgb(255, 0, 0)`, che è esattamente quello che
     * scrive il selettore colore del browser: il colore sarebbe sparito lo stesso.
     */
    private static function ValoreSicuro(string $valore): bool
    {
        // #fff / #ffff00, una parola chiave (red, underline, transparent), oppure una funzione
        // colore con dentro solo numeri, virgole, percentuali e spazi
        $token = '(?:#[0-9a-f]{3,8}|[a-z][a-z0-9-]*|(?:rgb|rgba|hsl|hsla)\([0-9.,%\s\/]+\))';

        return (bool)preg_match('/^' . $token . '(?:\s+' . $token . ')*$/i', trim($valore));
    }
}
