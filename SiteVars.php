<?php

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
        $phpobj = PHPDOWEB();

        return $phpobj->InfoSito($varsEnum->name)->Valore;
    }
}