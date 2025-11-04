<?php
declare(strict_types=1);

namespace Common;

class Lingue
{
    public string $Iso = "";
    public string $Nome = "";
    public bool $Attiva = true;
    public bool $Default = true;

    public static function GetLinguaFromUrl(): Lingue
    {
        $iso = $_GET['iso'];

        $key = "LinguaSelezionata|" . $iso;

        $success = false;

        $item = \Common\Cache::GetLingue($key, $success);

        if ($success)
            return $item;

        $lingua = new Lingue();

        /** @noinspection PhpUndefinedFunctionInspection */
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

                \Common\Cache::SetLingue($key, $lingua);

                return $lingua;
            }
        }

        $lingua->Iso = "";
        $lingua->Nome = "";
        $lingua->Attiva = true;
        $lingua->Default = true;

        \Common\Cache::SetLingue($key, $lingua);

        return $lingua;
    }

    /** @return Lingue[] */
    public static function GetLingueAttive(): array
    {
        $key = "LingueElenco";

        $success = false;

        $item = \Common\Cache::GetLingue($key, $success);

        if ($success)
            return $item;

        /** @noinspection PhpUndefinedFunctionInspection */
        $obj = PHPDOWEB();

        $lingueDb = $obj->LingueGetListAttive()->Lingue;

        $arr = [];

        foreach ($lingueDb as $linguaDb)
        {
            $lingua = new Lingue();

            $lingua->Iso = $linguaDb->CodiceIso;
            $lingua->Nome = $linguaDb->Nome;
            $lingua->Default = filter_var($linguaDb->Default, FILTER_VALIDATE_BOOLEAN);
            $lingua->Attiva = filter_var($linguaDb->Attiva, FILTER_VALIDATE_BOOLEAN);

            $arr[] = $lingua;
        }

        \Common\Cache::SetLingue($key, $arr);

        return $arr;
    }
}