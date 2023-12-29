<?php
declare(strict_types=1);

namespace Common;

class PagineDati
{
    public static function ValoreIso(\Code\Enum\PagineDatiControlliEnum $identificativoEnum): string
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

        $success = false;

        $key = $identificativoEnum->name . "|" . $iso;

        $item = \Common\Cache::GetDatiPagine($key, $success);

        if ($success)
            return $item;

        $phpobj = PHPDOWEB();

        $controllo = $phpobj->PagineDatiControlliValori($pagina, $identificativo, $iso);

        if ($decode)
            return html_entity_decode($controllo->Valore);

        $valore = $controllo->Valore;

        if ($tipoInput == "TextArea")
        {
            $valore = str_replace("\r\n", "<br>", $valore);
            $valore = str_replace("\n", "<br>", $valore);

            $valore = \Common\Convert::ConvertUrlsToLinks($valore);
        }

        \Common\Cache::SetDatiPagine($key, $valore);

        return $valore;
    }

    public static function ControlliValori(\Code\Enum\PagineDatiControlliEnum $identificativoEnum, string $iso = ""): Controlli\Controlli
    {
        $success = false;

        $key = "V|" . $identificativoEnum->name . "|" . $iso;

        $item = \Common\Cache::GetDatiPagine($key, $success);

        if ($success)
            return $item;

        $phpobj = PHPDOWEB();

        $reflection = new \ReflectionEnum($identificativoEnum);

        $case =  $reflection->getCase($identificativoEnum->name);

        $attribute =  $case->getAttributes()[0];

        $pagina = $attribute->getArguments()[0];
        $identificativo = $attribute->getArguments()[1];

        $controllo = $phpobj->PagineDatiControlliValori($pagina, $identificativo, $iso);

        $paginaControllo = new Controlli\Controlli();

        if ($controllo->Valore == "")
        {
            \Common\Cache::SetDatiPagine($key, $paginaControllo);

            return $paginaControllo;
        }

        $paginaControllo->Valore = $controllo->Valore;
        $paginaControllo->PercorsoWeb = $controllo->PercorsoWeb;
        $paginaControllo->DataUpload = \DateTime::createFromFormat("Y-m-d\TH:i:s", $controllo->DataUpload);
        $paginaControllo->DimensioneCompressa = $controllo->DimensioneCompressa;
        $paginaControllo->DimensioneReale = $controllo->DimensioneReale;
        $paginaControllo->ImmagineAltezza = $controllo->ImmagineAltezza;
        $paginaControllo->ImmagineLarghezza = $controllo->ImmagineLarghezza;

        \Common\Cache::SetDatiPagine($key, $paginaControllo);

        return $paginaControllo;
    }

    public static function GetUrlElenco(\Code\Enum\PagineDatiEnum $pagineDatiEnum, int $idElemento = 0, string $iso = "", bool $includiDominio = false): string
    {
        $success = false;

        $key = "I|" .$pagineDatiEnum->name . "|" . $idElemento . "|" . $iso . "|" . $includiDominio;

        $item = \Common\Cache::GetDatiPagine($key, $success);

        if ($success)
            return $item;

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

        \Common\Cache::SetDatiPagine($key, $url);

        return $url;
    }

    public static function GetUrlElemento(\Code\Enum\PagineDatiEnum $pagineDatiEnum, int $idElemento = 0, string $iso = "", bool $includiDominio = false): string
    {
        $success = false;

        $key = "E|" . $pagineDatiEnum->name . "|" . $idElemento . "|" . $iso . "|" . $includiDominio;

        $item = \Common\Cache::GetDatiPagine($key, $success);

        if ($success)
            return $item;

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

        \Common\Cache::SetDatiPagine($key, $url);

        return $url;
    }
}