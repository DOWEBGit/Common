<?php
declare(strict_types=1);

namespace Common;

class Master
{
    //chiave di sessione in cui si segna la giornata gia' registrata da AggiornaAccessoGiornaliero
    private const SESSIONE_ACCESSO = 'MasterAccessoRegistrato';

    public static function GetMasterLoggato(): \stdClass | null
    {
        $obj = PHPDOWEB();

        $master = $obj->MasterSessione($_COOKIE['AdminSession'] ?? '');

        if ($master->Errore)
            return null;

        return $master;
    }

    /**
     * Registra l'accesso odierno del master collegato.
     *
     * L'area amministrativa generata dal CMS scrive l'ultimo accesso quando si passa dal login.
     * Un'area riservata di progetto invece riusa il cookie AdminSession gia' valido e quindi entra
     * senza lasciare traccia: chi non rifa' il login risulta non essere piu' entrato da giorni.
     * Va chiamata dall'area riservata, una volta per richiesta, dopo aver verificato la sessione.
     *
     * La piattaforma tiene una riga al giorno per master, quindi richiamarla e' innocuo, ma costa
     * un giro sul pipe: il flag di sessione la limita alla prima pagina visitata. Il flag conserva
     * la data e non un booleano, cosi' una sessione lasciata aperta a cavallo della mezzanotte
     * registra comunque l'accesso del giorno nuovo.
     */
    public static function AggiornaAccessoGiornaliero(): void
    {
        $oggi = date('Y-m-d');

        if (($_SESSION[self::SESSIONE_ACCESSO] ?? '') === $oggi)
            return;

        //il guid della sessione e' il valore del cookie, lo stesso che GetMasterLoggato() passa a
        //MasterSessione: non esiste nessun $_SESSION['doweb_guid'] in questi progetti
        $guid = $_COOKIE['AdminSession'] ?? '';

        if ($guid === '')
            return;

        $obj = PHPDOWEB();

        //MasterAccessoGiornaliero non c'e' in tutte le build dell'estensione (la 0.1.0 in locale non
        //ce l'ha). Registrare l'accesso e' un di piu': se manca il metodo l'area riservata deve
        //restare in piedi, non andare in fatal sulla prima pagina. Il flag viene impostato lo stesso
        //per non rifare il controllo, e l'avviso a log resta uno per sessione invece che uno per pagina.
        if (!method_exists($obj, 'MasterAccessoGiornaliero'))
        {
            $_SESSION[self::SESSIONE_ACCESSO] = $oggi;

            \Common\Log::Warn('MasterAccessoGiornaliero non disponibile in questa versione della piattaforma: accesso non registrato.');

            return;
        }

        $esito = $obj->MasterAccessoGiornaliero(
            $guid,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? '');

        $avviso = strval($esito->Avviso ?? '');

        //Aggiunto = riga scritta adesso; senza Aggiunto e senza Avviso = c'era gia' quella di oggi.
        //In tutti e due i casi per oggi non serve piu' chiamare.
        if (($esito->Aggiunto ?? false) || $avviso === '')
        {
            $_SESSION[self::SESSIONE_ACCESSO] = $oggi;
            return;
        }

        //il flag resta non impostato apposta: se e' un problema passeggero la pagina dopo riprova.
        //Se invece e' stabile lo si vede nel log, che e' meglio di un accesso perso in silenzio.
        \Common\Log::Error('MasterAccessoGiornaliero: ' . $avviso);
    }
}
