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
    public static function ControlliValori(\Code\Enum\PagineEnum $pagina, \Code\Enum\PagineControlliEnum $identificativo, string $iso = ""): \Common\Controlli
    {
        $phpobj = PHPDOWEB();

        //recupero con reflection il valore dell'attributo che contiene l'identificativo

        $reflection = new \ReflectionEnum($identificativo);

        $case =  $reflection->getCase($identificativo->name);

        $attribute =  $case->getAttributes()[0];

        $identificativo = $attribute->getArguments()[0];

        $controllo = $phpobj->PagineControlliValori($pagina->value, $identificativo, $iso);

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
}