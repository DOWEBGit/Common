<?php
declare(strict_types=1);

namespace Common;

enum VarsEnum
{
    case cssmin;
    case jsmin;
    case multilingua;
    case costorinnovodominio;
    case costorinnovoemail;
    case costorinnovoserver;
    case costoticketinsospeso;
    case datascadenza;
    case incostruzione;
    case minutiassistenza;
    //->http/s://sito.ext
    case webpath;
    case diskpath;
    case rootpath;
    case protocollo;
    case redirect301;
    case jmail;
    case emailsupporto;
    case spaziooccupatodatabase;
    case spaziooccupatodisco;
    case spaziooccupato;
    case datiinmenu;
}

class SiteVars
{
    public static function Value(VarsEnum $varsEnum): string
    {
        $name = $varsEnum->name;

        // Verifica se la cache è già stata inizializzata
        if (isset($GLOBALS['SiteVars' . $name]))
        {
            return $GLOBALS['SiteVars' . $name];
        }

        $phpobj = PHPDOWEB();

        $GLOBALS['SiteVars' . $name] = $phpobj->InfoSito($varsEnum->name)->Valore;

        return $GLOBALS['SiteVars' . $name];
    }
}