<?php
declare(strict_types=1);

namespace Common;

use Common\Response\SaveResponse;

class Client
{
    public static $enabled = false;

    public static function Enable(): void
    {
        \Common\Client::$enabled = true;
    }

    public static function Push(string $name, string $value) : SaveResponse
    {
        $obj = PHPDOWEB()->ClientPush($name, $value);

        $resp = new \Common\Response\SaveResponse();
        $resp->Success = $obj->Errore == "false";
        $resp->InternalAvviso = $obj->Avviso;

        return $resp;
    }

    public static function Count() : int
    {
        return PHPDOWEB()->ClientCount();
    }
}