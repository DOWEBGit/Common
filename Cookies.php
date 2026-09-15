<?php
declare(strict_types=1);

namespace Common;

/**
 * I consensi del CookieBanner (static.doweb.site/CookieBanner), cioe' quello che nelle pagine
 * e' [COOKIES:NOME:ATTIVO]...[/COOKIES:NOME:ATTIVO] e [COOKIES:NOME:NONATTIVO]...[/...].
 *
 * Nelle pagine quel tag lo risolve il server. Nell'output del php no, come per le etichette
 * admin: un blocco scritto in un file incluso resterebbe lettera morta. Qui si fa lo stesso
 * lavoro sull'HTML gia' reso.
 *
 * Come funziona il consenso: il banner (js.js) installa un cookie con il NOME dell'interruttore
 * e valore 1 quando l'utente accetta, e lo cancella quando rifiuta. I nomi sono quelli delle
 * chiamate Add*() in iso.*.js - "googlemaps", "analytics_storage", "vimeo"... - e nei tag si
 * scrivono come si vuole per le maiuscole: [COOKIES:GOOGLEMAPS:ATTIVO] cerca "googlemaps".
 *
 * L'elenco dei nomi sta QUI (GRUPPI, NECESSARI, ALTRI) ed e' l'unico posto da aggiornare quando in iso.*.js si
 * aggiunge un cookie: poi basta aggiornare il Common nei siti. Un tag con un nome che non c'e'
 * nell'elenco e' quasi sempre un refuso: si avvisa nel log e si tratta come non attivo, che e'
 * la scelta sicura per un consenso.
 *
 * Uso:
 *     $html = \Common\Cookies::Resolve($html);          // i blocchi nell'output
 *     if (\Common\Cookies::Attivo('googlemaps')) ...    // dal codice
 *     CookieBanner([ ...\Common\Cookies::GRUPPI... ])   // i gruppi che il banner accetta
 */
class Cookies
{
    /**
     * I nomi dei cookie di consenso, per gruppo: la chiave e' il parametro che si passa a
     * CookieBanner(["GoogleMaps", ...]) e a CookiePolicy(...), i valori sono gli interruttori
     * che quel gruppo aggiunge al banner (iso.*.js, CookieBannerCompleted). Allineato ai file
     * del 15/09/2026.
     */
    public const array GRUPPI = [
        'Ecommerce'       => ['cart'],
        'GoogleAnalytics' => ['analytics_storage', 'ad_storage', 'ad_user_data', 'ad_personalization'],
        'Sessione'        => ['s'],
        'Lingua'          => ['l'],
        'Vimeo'           => ['vimeo'],
        'Stripe'          => ['stripe'],
        'YouTube'         => ['youtube'],
        'GoogleMaps'      => ['googlemaps'],
        'Meta'            => ['_fbp', '_fbc', 'fr', 'datr', 'sb', 'wd'],
    ];

    /** I necessari: il banner li mostra bloccati su acceso, quindi contano sempre come attivi. */
    public const array NECESSARI = ['CookieBanner', 'AccessibilityBar'];

    /**
     * L'interruttore unico che il banner mostra quando CookieBanner() viene chiamato senza
     * gruppi, e che i siti vecchi usano come [COOKIES:TERZEPARTI:...].
     */
    public const array ALTRI = ['TerzeParti'];

    /** Tutti i nomi ammessi nei tag, in minuscolo. */
    public static function Nomi(): array
    {
        $nomi = array_merge(self::NECESSARI, self::ALTRI);

        foreach (self::GRUPPI as $gruppo)
            $nomi = array_merge($nomi, $gruppo);

        return array_map('strtolower', $nomi);
    }

    /**
     * Il consenso c'e'? Vero se il browser ha mandato il cookie con quel nome (senza badare
     * alle maiuscole) e non e' vuoto ne' "0". I necessari sono sempre attivi: il banner li
     * mostra bloccati su acceso, ma il cookie vero e proprio potrebbe non esserci ancora.
     */
    public static function Attivo(string $nome): bool
    {
        $nome = strtolower($nome);

        if (in_array($nome, array_map('strtolower', self::NECESSARI), true))
            return true;

        foreach ($_COOKIE as $chiave => $valore)
            if (strtolower((string)$chiave) === $nome)
                return (string)$valore !== '' && (string)$valore !== '0';

        return false;
    }

    /**
     * Risolve nell'HTML i blocchi [COOKIES:NOME:ATTIVO]...[/COOKIES:NOME:ATTIVO] e
     * [COOKIES:NOME:NONATTIVO]...[/COOKIES:NOME:NONATTIVO]: il contenuto resta se la condizione
     * vale, altrimenti sparisce insieme ai tag. Un nome sconosciuto va nel log e conta come
     * non attivo.
     */
    public static function Resolve(string $html): string
    {
        if (!str_contains($html, '[COOKIES:'))
            return $html;

        $nomi = self::Nomi();

        $risolto = preg_replace_callback(
            '/\[COOKIES:([A-Za-z0-9_\-]+):(ATTIVO|NONATTIVO)\](.*?)\[\/COOKIES:\1:\2\]/s',
            static function (array $m) use ($nomi): string
            {
                if (!in_array(strtolower($m[1]), $nomi, true))
                {
                    \Common\Log::Warn('Cookies: [COOKIES:' . $m[1] . '] non e\' un cookie del banner (vedi Common\Cookies::GRUPPI): trattato come non attivo.');

                    $attivo = false;
                }
                else
                    $attivo = self::Attivo($m[1]);

                return ($m[2] === 'ATTIVO') === $attivo ? $m[3] : '';
            },
            $html
        );

        //un blocco aperto e mai chiuso non si tocca: resta visibile, che e' il modo di accorgersene
        return $risolto ?? $html;
    }
}
