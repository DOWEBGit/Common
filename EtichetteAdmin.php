<?php
declare(strict_types=1);

namespace Common;

/**
 * Le etichette admin, cioe' quello che nelle pagine e' [EA:NOME]: CookieBar, AccessibilityBar,
 * CookiePolicy, Accessibility. Sono comuni a tutti i siti.
 *
 * Nelle pagine quel tag lo risolve il server. Nell'output del php no: il php non passa
 * dall'espansione dei template, quindi un [EA:COOKIEBAR] scritto qui resterebbe lettera morta.
 * Si chiede al server gia' risolto, con lo stesso lavoro che fa per le pagine.
 *
 * Un'etichetta admin non e' testo, e' un pezzo di template: CookieBar contiene [E:PRIVACYURL],
 * [ISO] e [NONCE]. Il server risolve tutto tranne [NONCE], che vale per QUESTA richiesta e da
 * li' non si puo' conoscere: lo mettiamo noi da $_SERVER['NONCE'].
 *
 * Uso:
 *     echo \Common\EtichetteAdmin::GetValoreIso('COOKIEBAR');
 */
class EtichetteAdmin
{
    public static function GetValoreIso(string $nome): string
    {
        $iso = \Common\Lingue::GetLinguaFromUrl()->Iso;

        return self::GetValore($nome, $iso);
    }

    /**
     * La lingua e' un argomento e non si indovina: la chiamata viaggia su un canale separato
     * dalla richiesta http, e senza iso il server ripiega su "Senza lingua" (zz), non sulla
     * lingua predefinita del sito. E la lingua non sceglie solo il testo: entra negli indirizzi
     * che l'etichetta produce - con zz il CookieBanner esce con privacyUrl "/zz/privacy".
     */
    public static function GetValore(string $nome, string $iso): string
    {
        $success = false;

        $html = \Common\Cache::GetEtichetteAdmin($nome, $iso, $success);

        if (!$success)
        {
            /** @noinspection PhpUndefinedFunctionInspection */
            $obj = PHPDOWEB();

            //EtichetteAdmin non c'e' in tutte le build dell'estensione. Un'etichetta che manca
            //e' un di meno, non un fatal sulla prima pagina: si avvisa e si stampa niente
            if (!method_exists($obj, 'EtichetteAdmin'))
            {
                \Common\Log::Warn('EtichetteAdmin non disponibile in questa versione della piattaforma: [EA:' . $nome . '] non risolta.');

                return '';
            }

            $result = $obj->EtichetteAdmin($nome, $iso);

            if (isset($result->Errore) && \Common\Convert::ToBool($result->Errore))
            {
                \Common\Log::Error('EtichetteAdmin(' . $nome . ', ' . $iso . '): ' . ($result->Avviso ?? ''));

                return '';
            }

            //un'etichetta che non esiste torna vuota, come nelle pagine: si mette in cache lo
            //stesso, cosi' non si rifa' il giro sul pipe a ogni richiesta per niente
            $html = (string)($result->Valore ?? '');

            \Common\Cache::SetEtichetteAdmin($nome, $iso, $html);
        }

        //Il nonce si sostituisce DOPO la cache, mai prima. E' di questa singola richiesta: se
        //finisse in cache, tutti i visitatori riceverebbero lo stesso nonce per dieci minuti,
        //e la Content-Security-Policy tornerebbe un ornamento.
        return str_replace('[NONCE]', $_SERVER['NONCE'] ?? '', $html);
    }
}
