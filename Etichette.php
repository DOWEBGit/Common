<?php
declare(strict_types=1);

namespace Common;

class Etichette
{
    public static function GetValoreIso(\Code\Enum\EtichetteEnum $etichetta, bool $encode = false): string
    {
        $iso = \Common\Lingue::GetLinguaFromUrl()->Iso;

        return self::GetValore($etichetta, $iso, $encode);
    }

    public static function GetValore(\Code\Enum\EtichetteEnum $etichetta, string $iso, bool $encode = false): string
    {
        $success = false;

        $item = \Common\Cache::GetEtichette($etichetta, $iso, $encode, $success);

        if ($success)
            return $item;

        /** @noinspection PhpUndefinedFunctionInspection */
        $result = PHPDOWEB()->SitoEtichetteGetItem($etichetta->name, $encode, $iso);

        if (\Common\Convert::ToBool($result->Errore))
        {
            //probabilmente l'errore è che l'etichetta non c'è, la aggiungo

            /** @noinspection PhpUndefinedFunctionInspection */
            $result = PHPDOWEB()->SitoEtichetteSave($etichetta->name, $encode, $iso !== "", $iso, $etichetta->name);

            if (\Common\Convert::ToBool($result->Errore))
                \Common\Log::Error("Etichette->SitoEtichetteSave(" . $etichetta->name . ", " . $encode . ")");

            /** @noinspection PhpUndefinedFunctionInspection */
            $result = PHPDOWEB()->SitoEtichetteGetItem($etichetta->name, $encode, $iso);
        }

        $valore = $result->Valore;

        \Common\Cache::SetEtichette($etichetta, $iso, $encode, $valore);

        return $valore;
    }
}