<?php

namespace Common;

use Code\Enum\PagineDatiEnum;

class PagineDati
{
    public static function ControlliValori(\Code\Enum\PagineDatiEnum $pagineDatiEnum, \Code\Enum\PagineDatiControlliEnum $controlliEnum, string $iso = ""): \Common\Controlli
    {
        $phpobj = PHPDOWEB();

         $reflection = new \ReflectionEnum($controlliEnum);

         $attributes =  $reflection->getAttributes();

         $value = $attributes[0]->getArguments()[0];

        $controllo = $phpobj->PagineDatiControlliValori($pagineDatiEnum->value, $controlliEnum->name, $iso);

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

    public static function GetUrlElenco(\Code\Enum\PagineDatiEnum $pagineDatiEnum, int $idElemento = 0, string $iso = ""): string
    {
        $phpobj = PHPDOWEB();

        $result = $phpobj->PagineDatiGetUrlElenco($pagineDatiEnum->value, $idElemento, $iso);

        if (\Common\Convert::ToBool($result->Errore))
        {
            \Common\Log::Error("\Common\PagineDati->GetUrlElenco(" . $pagineDatiEnum->name . ", " . $idElemento . ", " . $iso . "), " . $result->Avviso);
            return "";
        }

        return $result->Url;
    }

    public static function GetUrlElemento(\Code\Enum\PagineDatiEnum $pagineDatiEnum, int $idElemento = 0, string $iso = ""): string
    {
        $phpobj = PHPDOWEB();

        $result = $phpobj->PagineDatiGetUrlElemento($pagineDatiEnum->name, $idElemento, $iso);

        if (\Common\Convert::ToBool($result->Errore))
        {
            \Common\Log::Error("\Common\PagineDati->PagineDatiGetUrlElemento(" . $pagineDatiEnum->name . ", " . $idElemento . ", " . $iso . "), " . $result->Avviso);
            return "";
        }

        return $result->Url;
    }
}