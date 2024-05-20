<?php
declare(strict_types=1);

namespace Common;

class Pagine
{
    public static function ValoreIso(\Code\Enum\PagineControlliEnum $identificativoEnum): string
    {
        $iso = \Common\Lingue::GetLinguaFromUrl()->Iso;

        //recupero con reflection il valore dell'attributo che contiene l'identificativo

        $reflection = new \ReflectionEnum($identificativoEnum);

        $case = $reflection->getCase($identificativoEnum->name);

        $attribute = $case->getAttributes()[0];

        $args = $attribute->getArguments();

        $pagina = $args[0];
        $identificativo = $args[1];
        $tipoInput = $args[2];
        $decode = $args[3];

        $key = $pagina . "|" . $identificativo . "|" . $tipoInput . "|" . $decode . "|" . $iso;

        $success = false;

        $valore = \Common\Cache::GetPagine($key, $success);

        if ($success)
            return $valore;

        $phpobj = PHPDOWEB();

        $controllo = $phpobj->PagineControlliValori($pagina, $identificativo, $iso);

        if ($decode)
            return html_entity_decode($controllo->Valore);

        $valore = $controllo->Valore;

        if ($tipoInput == "TextArea")
        {
            $valore = str_replace("\r\n", "<br>", $valore);
            $valore = str_replace("\n", "<br>", $valore);

            $valore = \Common\Convert::ConvertUrlsToLinks($valore);
        }

        \Common\Cache::SetPagine($key, $valore);

        return $valore;
    }

    public static function ControlliValori(\Code\Enum\PagineControlliEnum $identificativoEnum, string $iso = ""): Controlli\Controlli
    {
        //recupero con reflection il valore dell'attributo che contiene l'identificativo

        $reflection = new \ReflectionEnum($identificativoEnum);

        $case = $reflection->getCase($identificativoEnum->name);

        $attribute = $case->getAttributes()[0];

        $pagina = $attribute->getArguments()[0];
        $identificativo = $attribute->getArguments()[1];

        $success = false;

        $key = $pagina . "|" . $identificativo . "|" . $iso;

        $valore = \Common\Cache::GetPagine($key, $success);

        if ($success)
            return $valore;


        $phpobj = PHPDOWEB();

        $controllo = $phpobj->PagineControlliValori($pagina, $identificativo, $iso);

        $paginaControllo = new Controlli\Controlli();

        if ($controllo->Valore == "")
        {
            \Common\Cache::SetPagine($key, $paginaControllo);

            return $paginaControllo;
        }

        $paginaControllo->Valore = $controllo->Valore;
        $paginaControllo->PercorsoWeb = $controllo->PercorsoWeb;
        $paginaControllo->DataUpload = \DateTime::createFromFormat("Y-m-d\TH:i:s", $controllo->DataUpload);
        $paginaControllo->DimensioneCompressa = $controllo->DimensioneCompressa;
        $paginaControllo->DimensioneReale = $controllo->DimensioneReale;
        $paginaControllo->ImmagineAltezza = $controllo->ImmagineAltezza;
        $paginaControllo->ImmagineLarghezza = $controllo->ImmagineLarghezza;

        \Common\Cache::SetPagine($key, $paginaControllo);

        return $paginaControllo;
    }

    public static function GetPagina(\Code\Enum\PagineEnum $pagina): ?PagineResult
    {
        $lingua = \Common\Lingue::GetLinguaFromUrl();

        $key = $pagina->name . "|" . $lingua->Iso;

        $success = false;

        $item = \Common\Cache::GetPagine($key, $success);

        if ($success)
            return $item;

        $phpobj = PHPDOWEB();

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

        \Common\Cache::SetPagine($key, $pagina);

        return $pagina;
    }

    public static function GetUrlIso(\Code\Enum\PagineEnum $pagineEnum, bool $includiDominio = false): string
    {
        $lingua = \Common\Lingue::GetLinguaFromUrl();

        $iso = $lingua->Iso;

        return self::GetUrl($pagineEnum, $iso, $includiDominio);
    }

    public static function GetUrl(\Code\Enum\PagineEnum $pagineEnum, string $iso, bool $includiDominio = false): string
    {
        $success = false;

        $key = $pagineEnum->name . "|" . $iso . "|" . $includiDominio;

        $item = \Common\Cache::GetPagine($key, $success);

        if ($success)
            return $item;


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

        \Common\Cache::SetPagine($key, $url);

        return $url;
    }
}