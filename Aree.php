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

        $controllo = $phpobj->AreeControlliValori($pagina, $identificativo, $iso->Iso);

        if ($controllo->Valore == "")
            return $identificativo;

        if ($decode)
            return html_entity_decode($controllo->Valore);

        return $controllo->Valore;
    }

    public static function ControlliValori(\Code\Enum\AreeControlliEnum $identificativoEnum, string $iso = ""): Controlli\Controlli
    {
        $phpobj = PHPDOWEB();

        //recupero con reflection il valore dell'attributo che contiene l'identificativo

        $reflection = new \ReflectionEnum($identificativoEnum);

        $case = $reflection->getCase($identificativoEnum->name);

        $attribute = $case->getAttributes()[0];

        $pagina = $attribute->getArguments()[0];
        $identificativo = $attribute->getArguments()[1];

        $controllo = $phpobj->AreeControlliValori($pagina, $identificativo, $iso);

        $areeControllo = new Controlli\Controlli();

        if ($controllo->Valore == "")
            return $areeControllo;

        $areeControllo->Valore = $controllo->Valore;
        $areeControllo->PercorsoWeb = $controllo->PercorsoWeb;
        $areeControllo->DataUpload = \DateTime::createFromFormat("Y-m-d\TH:i:s", $controllo->DataUpload);
        $areeControllo->DimensioneCompressa = $controllo->DimensioneCompressa;
        $areeControllo->DimensioneReale = $controllo->DimensioneReale;
        $areeControllo->ImmagineAltezza = $controllo->ImmagineAltezza;
        $areeControllo->ImmagineLarghezza = $controllo->ImmagineLarghezza;

        return $areeControllo;
    }
}