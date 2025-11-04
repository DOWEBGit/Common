<?php
declare(strict_types=1);

namespace Common;

class AreeResult
{
    public string $Nome;
    public string $TagReplace;
    public bool $Multilingua;    
    public string $Descrizione;
    public string $OnSave;    
}

class Aree
{
    public static function ValoreIso(\Code\Enum\AreeControlliEnum $identificativoEnum): string
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

        $valore = \Common\Cache::GetAree($key, $success);

        if ($success)
            return $valore;

        /** @noinspection PhpUndefinedFunctionInspection */
        $phpobj = PHPDOWEB();

        $controllo = $phpobj->AreeControlliValori($pagina, $identificativo, $iso);

        if ($decode)
            return html_entity_decode($controllo->Valore);

        $valore = $controllo->Valore;

        if ($tipoInput == "TextArea")
        {
            $valore = str_replace("\r\n", "<br>", $valore);
            $valore = str_replace("\n", "<br>", $valore);

            $valore = \Common\Convert::ConvertUrlsToLinks($valore);
        }

        \Common\Cache::SetAree($key, $valore);

        return $valore;
    }

    public static function ControlliValori(\Code\Enum\AreeControlliEnum $identificativoEnum, string $iso = ""): Controlli\Controlli
    {
        //recupero con reflection il valore dell'attributo che contiene l'identificativo

        $reflection = new \ReflectionEnum($identificativoEnum);

        $case = $reflection->getCase($identificativoEnum->name);

        $attribute = $case->getAttributes()[0];

        $pagina = $attribute->getArguments()[0];
        $identificativo = $attribute->getArguments()[1];

        $success = false;

        $key = $pagina . "|" . $identificativo . "|" . $iso;

        $valore = \Common\Cache::GetAree($key, $success);

        if ($success)
            return $valore;

        /** @noinspection PhpUndefinedFunctionInspection */
        $phpobj = PHPDOWEB();

        $controllo = $phpobj->AreeControlliValori($pagina, $identificativo, $iso);

        $areeControllo = new Controlli\Controlli();

        if ($controllo->Valore == "")
        {
            \Common\Cache::SetAree($key, $areeControllo);

            return $areeControllo;
        }

        $areeControllo->Valore = $controllo->Valore;
        $areeControllo->PercorsoWeb = $controllo->PercorsoWeb;
        $areeControllo->DataUpload = \DateTime::createFromFormat("Y-m-d\TH:i:s", $controllo->DataUpload);
        $areeControllo->DimensioneCompressa = $controllo->DimensioneCompressa;
        $areeControllo->DimensioneReale = $controllo->DimensioneReale;
        $areeControllo->ImmagineAltezza = $controllo->ImmagineAltezza;
        $areeControllo->ImmagineLarghezza = $controllo->ImmagineLarghezza;

        \Common\Cache::SetAree($key, $areeControllo);

        return $areeControllo;
    }

    public static function ControlliValoriBytes(\Code\Enum\AreeControlliEnum $identificativoEnum, string $iso = ""): Controlli\ControlloImmagine
    {
        //recupero con reflection il valore dell'attributo che contiene l'identificativo

        $reflection = new \ReflectionEnum($identificativoEnum);

        $case = $reflection->getCase($identificativoEnum->name);

        $attribute = $case->getAttributes()[0];

        $pagina = $attribute->getArguments()[0];
        $identificativo = $attribute->getArguments()[1];

        /** @noinspection PhpUndefinedFunctionInspection */
        $phpobj = PHPDOWEB();

        $controllo = $phpobj->AreeFileInfo($pagina, $identificativo, $iso);

        $paginaControllo = new Controlli\ControlloImmagine();

        if ($controllo->Nome == "")
            return $paginaControllo;

        $paginaControllo->Nome = $controllo->Nome;
        $paginaControllo->Bytes = $controllo->Bytes;
        $paginaControllo->DimensioneReale = intval($controllo->DimensioneReale);
        $paginaControllo->DimensioneCompressa = intval($controllo->DimensioneCompressa);
        $paginaControllo->Base64Encoded = true;
        $paginaControllo->ImmagineAltezza = intval($controllo->ImmagineAltezza);
        $paginaControllo->ImmagineLarghezza = intval($controllo->ImmagineLarghezza);

        return $paginaControllo;
    }

}