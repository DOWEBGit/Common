<?php
declare(strict_types=1);

namespace Common\WebForms;

/**
 * Le tre forme di risposta.
 *
 * Il primo caricamento e' un documento HTML completo, reso dal percorso normale: l'URL
 * incollato funziona, i motori di ricerca vedono la pagina intera, e senza JavaScript si
 * legge comunque. Il postback torna solo il frammento della radice, che il client fonde
 * con il DOM esistente.
 */
class Response
{
    public static function Document(Page $pagina, string $html): never
    {
        header('Content-Type: text/html; charset=utf-8');

        echo "<!doctype html>\n<html lang=\"" . Control::HtmlEncode($pagina->Lang) . "\">";
        echo '<head><meta charset="utf-8">';
        echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<title>' . Control::HtmlEncode($pagina->Title) . '</title>';

        //PRIMA gli stili del motore, POI quello che aggiunge la pagina: i primi sono il
        //minimo funzionale, e a parita' di specificita' in CSS vince chi viene dopo. Cosi'
        //una master o una pagina sovrascrivono qualunque regola del motore senza dover
        //alzare la specificita' e senza toccare Common.
        echo Runtime::Styles();

        //description, canonical, og:, un foglio di stile di pagina
        foreach ($pagina->Head as $riga)
            echo $riga;
        echo '</head><body>';
        echo $html;
        echo Runtime::Scripts();
        echo '</body></html>';

        exit;
    }

    public static function Fragment(string $html): never
    {
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(['html' => $html], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }

    /**
     * L'indirizzo porta dentro questo sito?
     *
     * Un redirect che accetta qualunque indirizzo e' un trampolino: basta che un giorno una
     * pagina ci passi qualcosa che viene dall'utente, e il nostro dominio manda la gente
     * dove vuole chi ha scritto il link, con la nostra faccia davanti. Si accettano solo
     * percorsi, e gli indirizzi assoluti solo verso l'host di questa richiesta.
     *
     * Le tre forme che sembrano percorsi e non lo sono:
     *   //altrosito.it/x   per il browser e' assoluto, eredita solo lo schema
     *   \\altrosito.it\x   c'e' chi la normalizza come sopra
     *   javascript:...     non e' una navigazione, e' codice
     */
    public static function Interno(string $url): bool
    {
        //un a capo spezzerebbe l'intestazione Location e ne farebbe aggiungere altre.
        //header() lo rifiuta gia' per conto suo, ma sul ramo del postback l'indirizzo non
        //passa da header(): finisce in un JSON e poi in una navigazione del runtime
        if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url) === 1)
            return false;

        if (str_starts_with($url, '//') || str_starts_with($url, '\\\\'))
            return false;

        //uno schema qualunque non e' un percorso: si ammettono http e https, e solo verso
        //l'host di questa richiesta
        if (preg_match('#^[A-Za-z][A-Za-z0-9+.\-]*:#', $url) === 1)
        {
            $pezzi = parse_url($url);

            if ($pezzi === false || !isset($pezzi['scheme'], $pezzi['host']))
                return false;

            if (!in_array(strtolower($pezzi['scheme']), ['http', 'https'], true))
                return false;

            $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));

            //l'host della richiesta puo' portarsi dietro la porta, quello dell'indirizzo la
            //tiene in un campo suo: si confrontano i nomi
            return $host !== '' && strtolower($pezzi['host']) === explode(':', $host)[0];
        }

        return true;
    }

    /**
     * Manda il browser altrove.
     *
     * Durante un postback non si puo' rispondere con un 302: la richiesta e' una fetch, e il
     * browser seguirebbe il redirect restituendo alla fetch l'HTML della pagina di
     * destinazione, che finirebbe fuso nel DOM di quella di partenza. Serve un'istruzione
     * esplicita che il runtime traduce in una navigazione vera.
     */
    public static function Redirect(string $url, bool $postback, string $portatile = ''): never
    {
        if (!self::Interno($url))
            throw new \RuntimeException('Redirect fuori dal sito: "' . $url . '".');

        if (!$postback)
        {
            header('Location: ' . $url, true, 302);
            exit;
        }

        header('Content-Type: application/json; charset=utf-8');

        $risposta = ['vai' => $url];

        //lo stato che attraversa le pagine parte insieme all'ordine di andare altrove: il
        //render che l'avrebbe portato non ci sara', e il client deve aggiornarlo PRIMA di
        //chiedere la pagina nuova
        if ($portatile !== '')
            $risposta['portatile'] = $portatile;

        echo json_encode($risposta, JSON_UNESCAPED_SLASHES);

        exit;
    }

    /**
     * Lo stato non c'e' piu' o la richiesta non e' autorizzata: si ricarica pulito.
     * Meglio un ricarico che una pagina mezza aggiornata in cui l'utente non capisce piu'
     * cosa sta guardando.
     */
    public static function Reload(): never
    {
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(['ricarica' => true]);

        exit;
    }
}
