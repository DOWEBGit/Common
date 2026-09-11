<?php
declare(strict_types=1);

namespace Common;

/**
 * Protezione CSRF per le chiamate al dispatcher (Common/View/Client.php).
 *
 * Il dispatcher si fida del solo cookie di sessione: un modulo su un sito qualsiasi che fa
 * POST verso Client.php parte con la sessione della vittima già allegata dal browser, e
 * l'azione viene eseguita a suo nome. Finché tutte le azioni erano aperte a chiunque il
 * problema era coperto da un buco più grande; con il controllo d'accesso in piedi, questo
 * diventa il modo principale per far compiere operazioni a un amministratore autenticato.
 *
 * Il token vive in sessione, viene emesso da Common/Include/Head.php insieme al JavaScript
 * che fa le fetch — così i due pezzi non possono separarsi — e viaggia nell'header
 * `X-Csrf-Token`. Un header personalizzato non è nemmeno inviabile da un form cross-site
 * senza preflight CORS, quindi regge anche prima del confronto.
 */
class Csrf
{
    private const string CHIAVE = 'csrf';
    //nome dell'header X-Csrf-Token come lo espone PHP in $_SERVER
    private const string HEADER = 'HTTP_X_CSRF_TOKEN';
    /**
     * Token della sessione corrente, generato al primo utilizzo.
     */
    public static function Token(): string
    {
        $token = State::SessionRead(self::CHIAVE);

        if ($token === '')
        {
            $token = bin2hex(random_bytes(32));

            State::SessionWrite(self::CHIAVE, $token);
        }

        return $token;
    }

    /**
     * La richiesta corrente porta il token della sessione?
     *
     * Se in sessione non c'è ancora un token significa che questo browser non ha mai
     * caricato una pagina del sito: non esiste una sessione da cavalcare, quindi non c'è
     * nulla da proteggere e si lascia passare. Serve a non rompere la prima richiesta.
     */
    public static function Verifica(): bool
    {
        $atteso = State::SessionRead(self::CHIAVE);

        if ($atteso === '')
            return true;

        $fornito = strval($_SERVER[self::HEADER] ?? '');

        if ($fornito === '')
            return false;

        return hash_equals($atteso, $fornito);
    }
}
