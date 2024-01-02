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
        $value = \Common\Cache::GetSiteVars($varsEnum);

        if ($value != null)
            return $value;

        $phpobj = PHPDOWEB();

        $value = $phpobj->InfoSito($varsEnum->name)->Valore;

        \Common\Cache::SetSiteVars($varsEnum, $value);

        return $value;
    }
}