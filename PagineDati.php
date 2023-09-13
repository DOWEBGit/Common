<?php

namespace Common;

use Code\Enum\PagineDatiEnum;

class PagineDati
{
    public static function ValoreIso(\Code\Enum\PagineDatiControlliEnum $identificativoEnum): string
    {
        $iso = \Common\Lingue::GetLinguaFromUrl();

        $phpobj = PHPDOWEB();

        //recupero con reflection il valore dell'attributo che contiene l'identificativo

        $reflection = new \ReflectionEnum($identificativoEnum);

        $case = $reflection->getCase($identificativoEnum->name);

        $attribute = $case->getAttributes()[0];

        $args = $attribute->getArguments();

        $pagina = $args[0];
        $identificativo = $args[1];
        $decode = $args[2];

        $controllo = $phpobj->PagineDatiControlliValori($pagina, $identificativo, $iso->Iso);

        if ($controllo->Valore == "")
            return $identificativo;

        if ($decode)
            return html_entity_decode($controllo->Valore);

        return $controllo->Valore;
    }

    public static function ControlliValori(\Code\Enum\PagineDatiControlliEnum $identificativoEnum, string $iso = ""): \Common\Controlli
    {
        $phpobj = PHPDOWEB();

        $reflection = new \ReflectionEnum($identificativoEnum);

        $case =  $reflection->getCase($identificativoEnum->name);

        $attribute =  $case->getAttributes()[0];

        $pagina = $attribute->getArguments()[0];
        $identificativo = $attribute->getArguments()[1];

        $controllo = $phpobj->PagineDatiControlliValori($pagina, $identificativo, $iso);

        $paginaControllo = new \Common\Controlli();

        if ($controllo->Valore == "")
        {
            return $paginaControllo;
        }

        $paginaControllo->Valore = $controllo->Valore;
        $paginaControllo->PercorsoWeb = $controllo->PercorsoWeb;
        $paginaControllo->DataUpload = \DateTime::createFromFormat("Y-m-d\TH:i:s", $controllo->DataUpload);
        $paginaControllo->DimensioneCompressa = $controllo->DimensioneCompressa;
        $paginaControllo->DimensioneReale = $controllo->DimensioneReale;
        $paginaControllo->ImmagineAltezza = $controllo->ImmagineAltezza;
        $paginaControllo->ImmagineLarghezza = $controllo->ImmagineLarghezza;

        return $paginaControllo;
    }

    public static function GetUrlElenco(\Code\Enum\PagineDatiEnum $pagineDatiEnum, int $idElemento = 0, string $iso = "", bool $includiDominio = false): string
    {
        $phpobj = PHPDOWEB();

        $result = $phpobj->PagineDatiGetUrlElenco($pagineDatiEnum->value, $idElemento, $iso);

        if (\Common\Convert::ToBool($result->Errore))
        {
            \Common\Log::Error("\Common\PagineDati->GetUrlElenco(" . $pagineDatiEnum->name . ", " . $idElemento . ", " . $iso . "), " . $result->Avviso);
            return "";
        }

        $url = $result->Url;

        if ($includiDominio)
            $url = \Common\SiteVars::Value(VarsEnum::webpath) . $url;

        return $url;
    }

    public static function GetUrlElemento(\Code\Enum\PagineDatiEnum $pagineDatiEnum, int $idElemento = 0, string $iso = "", bool $includiDominio = false): string
    {
        $phpobj = PHPDOWEB();

        $result = $phpobj->PagineDatiGetUrlElemento($pagineDatiEnum->name, $idElemento, $iso);

        if (\Common\Convert::ToBool($result->Errore))
        {
            \Common\Log::Error("\Common\PagineDati->PagineDatiGetUrlElemento(" . $pagineDatiEnum->name . ", " . $idElemento . ", " . $iso . "), " . $result->Avviso);
            return "";
        }

        $url = $result->Url;

        if ($includiDominio)
            $url = \Common\SiteVars::Value(VarsEnum::webpath) . $url;

        return $url;
    }
}