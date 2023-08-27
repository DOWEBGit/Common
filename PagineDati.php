<?php

namespace Common;

class PagineDati
{
    public static function ControlliValori(string $nomePagina, string $identificativo, string $iso = "") : ?\Common\Controlli
    {
        $controllo = PHPDOWEB()->PagineDatiControlliValori($nomePagina, $identificativo, $iso);

        if ($controllo->Errore === 1) //potrei loggare se è null...
            return null;

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