<?php
declare(strict_types=1);

namespace Common;

class Master
{
    public static function GetMasterLoggato(): \stdClass | null
    {
        $obj = PHPDOWEB();

        $master = $obj->MasterGetItem(\Common\State::CookieRead("AdminGuid"));

        if ($master->Errore == "1")
            return null;

        return $master;
    }
}