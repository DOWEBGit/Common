<?php

namespace Common;

class Lingue
{
    public string $Iso = "";
    public string $Nome = "";
    public bool $Attiva = true;
    public bool $Default = true;

    public static function GetLinguaFromUrl(): Lingue
    {
        $lingua = new Lingue();

        $iso = $_GET['iso'];

        $obj = PHPDOWEB();

        $lingueDb = $obj->LingueGetListAttive()->Lingue;

        foreach ($lingueDb as $linguaDb)
        {
            if ($linguaDb->CodiceIso === $iso)
            {
                $lingua->Iso = $linguaDb->CodiceIso;
                $lingua->Nome = $linguaDb->Nome;
                $lingua->Default = filter_var($linguaDb->Default, FILTER_VALIDATE_BOOLEAN);
                $lingua->Attiva = filter_var($linguaDb->Attiva, FILTER_VALIDATE_BOOLEAN);

                return $lingua;
            }
        }

        return $lingua;
    }
}