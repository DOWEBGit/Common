<?php
declare(strict_types=1);

namespace Common;

class Master
{
    public static function GetMasterLoggato(): \stdClass | null
    {
        $obj = PHPDOWEB();

        $master = $obj->MasterSessione($_COOKIE['AdminSession'] ?? '');

        if ($master->Errore)
            return null;

        return $master;
    }
}