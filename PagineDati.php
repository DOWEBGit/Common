<?php

namespace Common;

class PagineDati
{
    public static function ControlliValori(string $nomePagina, string $identificativo, string $iso = "") : \Common\Controlli
    {
        $phpobj = PHPDOWEB();

        $controllo = $phpobj->PagineDatiControlliValori($nomePagina, $identificativo, $iso);

        $paginaControllo = new \Common\Controlli();

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
}