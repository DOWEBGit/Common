<?php

namespace Common;

class PagineResult
{
    public string $Url;
    public string $FullUrl;
    public bool $Multilingua;
    public string $TagReplace;
    public int $Parent;
    public bool $Attiva;
    public bool $Home;
    public string $Nome;
    public bool $Sitemap;
}

class Pagine
{
    public static function ValoreIso(\Code\Enum\PagineControlliEnum $identificativoEnum): string
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

        $controllo = $phpobj->PagineControlliValori($pagina, $identificativo, $iso->Iso);

        if ($controllo->Valore == "")
            return $identificativo;

        if ($decode)
            return html_entity_decode($controllo->Valore);

        return $controllo->Valore;
    }

    public static function ControlliValori(\Code\Enum\PagineControlliEnum $identificativoEnum, string $iso = ""): Controlli\Controlli
    {
        $phpobj = PHPDOWEB();

        //recupero con reflection il valore dell'attributo che contiene l'identificativo

        $reflection = new \ReflectionEnum($identificativoEnum);

        $case = $reflection->getCase($identificativoEnum->name);

        $attribute = $case->getAttributes()[0];

        $pagina = $attribute->getArguments()[0];
        $identificativo = $attribute->getArguments()[1];

        $controllo = $phpobj->PagineControlliValori($pagina, $identificativo, $iso);

        $paginaControllo = new Controlli\Controlli();

        if ($controllo->Valore == "")
            return $paginaControllo;

        $paginaControllo->Valore = $controllo->Valore;
        $paginaControllo->PercorsoWeb = $controllo->PercorsoWeb;
        $paginaControllo->DataUpload = \DateTime::createFromFormat("Y-m-d\TH:i:s", $controllo->DataUpload);
        $paginaControllo->DimensioneCompressa = $controllo->DimensioneCompressa;
        $paginaControllo->DimensioneReale = $controllo->DimensioneReale;
        $paginaControllo->ImmagineAltezza = $controllo->ImmagineAltezza;
        $paginaControllo->ImmagineLarghezza = $controllo->ImmagineLarghezza;

        return $paginaControllo;
    }

    public static function GetPagina(\Code\Enum\PagineEnum $pagina): ?PagineResult
    {
        $phpobj = PHPDOWEB();

        $lingua = \Common\Lingue::GetLinguaFromUrl();

        $result = $phpobj->Pagine($pagina->value, $lingua->Iso);

        $errore = \Common\Convert::ToBool($result->Errore);

        if ($errore)
            return null;

        $pagina = new PagineResult();

        $pagina->Nome = $result->Nome;
        $pagina->TagReplace = $result->TagReplace;
        $pagina->FullUrl = $result->FullUrl;
        $pagina->Url = $result->Url;
        $pagina->Home = \Common\Convert::ToBool($result->Home);
        $pagina->Attiva = \Common\Convert::ToBool($result->Attiva);
        $pagina->Sitemap = \Common\Convert::ToBool($result->Sitemap);
        $pagina->Parent = intval($result->Parent);
        $pagina->Multilingua = \Common\Convert::ToBool($result->Multilingua);

        return $pagina;
    }

    public static function GetUrlIso(\Code\Enum\PagineEnum $pagineEnum, bool $includiDominio = false): string
    {
        $phpobj = PHPDOWEB();

        $lingua = \Common\Lingue::GetLinguaFromUrl();

        $iso = $lingua->Iso;

        $result = $phpobj->Pagine($pagineEnum->value, $iso);

        if (\Common\Convert::ToBool($result->Errore))
        {
            \Common\Log::Error("\Common\Pagine->GetUrlIso(" . $pagineEnum->name . "), " . $result->Avviso);
            return "";
        }

        $url = $result->FullUrl;

        if ($includiDominio)
            $url = \Common\SiteVars::Value(VarsEnum::webpath) . $url;

        return $url;
    }

    public static function GetUrl(\Code\Enum\PagineEnum $pagineEnum, string $iso, bool $includiDominio = false): string
    {
        $phpobj = PHPDOWEB();

        $result = $phpobj->Pagine($pagineEnum->value, $iso);

        if (\Common\Convert::ToBool($result->Errore))
        {
            \Common\Log::Error("\Common\Pagine->GetUrl(" . $pagineEnum->name . ", " . $iso . "), " . $result->Avviso);
            return "";
        }

        $url = $result->FullUrl;

        if ($includiDominio)
            $url = \Common\SiteVars::Value(VarsEnum::webpath) . $url;

        return $url;
    }
}