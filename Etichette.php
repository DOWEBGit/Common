<?php

namespace Common;

class Etichette
{
    public static function GetValoreIso(\Code\Enum\EtichetteEnum $etichetta, bool $encode = false): string
    {
        $iso = \Common\Lingue::GetLinguaFromUrl()->Iso;

        $result = PHPDOWEB()->SitoEtichetteGetItem($etichetta->name, $encode, $iso);

        if (\Common\Convert::ToBool($result->Errore))
        {
            //probabilmente l'errore è che l'etichetta non c'è, la aggiungo

            $result = PHPDOWEB()->SitoEtichetteSave($etichetta->name, $encode, $iso !== "", $iso, $etichetta->name);

            if (\Common\Convert::ToBool($result->Errore))
                \Common\Log::Error("Etichette->SitoEtichetteSave(" . $etichetta->name . ", " . $encode . ")");

            $result = PHPDOWEB()->SitoEtichetteGetItem($etichetta->name, $encode, $iso);
        }

        return $result->Valore;
    }

    public static function GetValore(\Code\Enum\EtichetteEnum $etichetta, string $iso, bool $encode = false): string
    {
        $result = PHPDOWEB()->SitoEtichetteGetItem($etichetta->name, $encode, $iso);

        if (\Common\Convert::ToBool($result->Errore))
        {
            //probabilmente l'errore è che l'etichetta non c'è, la aggiungo

            $result = PHPDOWEB()->SitoEtichetteSave($etichetta->name, $encode, $iso !== "", $iso, $etichetta->name);

            if (\Common\Convert::ToBool($result->Errore))
                \Common\Log::Error("Etichette->SitoEtichetteSave(" . $etichetta->name . ", " . $encode . ")");

            $result = PHPDOWEB()->SitoEtichetteGetItem($etichetta->name, $encode, $iso);
        }

        return $result->Valore;
    }
}