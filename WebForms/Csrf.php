<?php
declare(strict_types=1);

namespace Common\WebForms;

/**
 * La protezione CSRF del motore, senza sessione: doppio invio.
 *
 * COME FUNZIONA. Il server mette nel browser un cookie con un valore casuale e scrive lo
 * stesso valore nella pagina; il runtime lo rimanda nell'intestazione X-Csrf-Token, e il
 * server confronta l'intestazione col cookie. Non tiene niente da nessuna parte: il
 * confronto e' fra due cose che arrivano entrambe dalla richiesta.
 *
 * PERCHE' REGGE. Un sito qualsiasi puo' far partire una richiesta verso di noi col cookie
 * della vittima allegato - e' il cuore del CSRF - ma NON puo' leggere il valore del cookie
 * (e' di un altro dominio) ne' leggere la nostra pagina (la stessa regola), quindi non sa
 * cosa scrivere nell'intestazione. E un'intestazione personalizzata da un form cross-site
 * non e' nemmeno inviabile senza un preflight CORS, che noi non concediamo.
 *
 * PERCHE' NON \Common\Csrf. Quella tiene il token in sessione, ed e' usata dal dispatcher
 * AJAX del sito: qui non si tocca. Il motore invece non deve aprire nessuna sessione - lo
 * stato di pagina sta nel campo nascosto firmato - e una pagina non deve smettere di
 * funzionare perche' la sessione e' scaduta mentre si compilava una scheda.
 */
class Csrf
{
    private const COOKIE = 'dw_csrf';

    /** Nome dell'intestazione X-Csrf-Token come lo espone PHP. */
    private const HEADER = 'HTTP_X_CSRF_TOKEN';

    /** Il valore da mettere nella pagina, uguale a quello del cookie. */
    public static function Token(): string
    {
        $token = (string)($_COOKIE[self::COOKIE] ?? '');

        //solo esadecimale: il valore torna dal browser e finisce in un confronto e in una
        //pagina, e non c'e' nessun motivo per cui debba contenere altro
        if (preg_match('/^[a-f0-9]{64}$/', $token) === 1)
            return $token;

        $token = bin2hex(random_bytes(32));

        //cookie di sessione del BROWSER: niente scadenza, muore quando si chiude. HttpOnly
        //perche' il valore lo scrive il server nella pagina e il JavaScript non deve leggerlo
        //dal cookie; SameSite=Lax perche' un form cross-site non lo porti con se'.
        if (!headers_sent())
            setcookie(self::COOKIE, $token, [
                'expires'  => 0,
                'path'     => '/',
                //"off" e' un valore che si trova davvero in $_SERVER['HTTPS']: prenderlo per
                //buono marcherebbe il cookie come Secure su una richiesta http, e il browser
                //lo butterebbe via senza dire niente
                'secure'   => !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

        //vale anche per questa richiesta: la pagina che stiamo per rendere deve gia' portare
        //il token, altrimenti il primo postback partirebbe senza
        $_COOKIE[self::COOKIE] = $token;

        return $token;
    }

    /** L'intestazione della richiesta corrente combacia col cookie? */
    public static function Verify(): bool
    {
        $cookie = (string)($_COOKIE[self::COOKIE] ?? '');

        //IL COOKIE MANCANTE NON E' UN LASCIAPASSARE. Qui prima si tornava true, col
        //ragionamento "nessun cookie, nessuna sessione da cavalcare": sbagliato proprio nel
        //caso che c'e' da fermare. Il cookie e' SameSite=Lax, quindi in una POST cross-site
        //NON viene mandato, e la verifica lasciava passare sempre.
        if ($cookie === '')
            return false;

        $fornito = (string)($_SERVER[self::HEADER] ?? '');

        if ($fornito === '')
            return false;

        return hash_equals($cookie, $fornito);
    }
}
