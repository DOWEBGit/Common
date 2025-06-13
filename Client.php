<?php
declare(strict_types=1);

namespace Common;

use Common\Response\SaveResponse;

class Client
{
    public static bool $enabled = false;

    public static function Enable(): void
    {
        \Common\Client::$enabled = true;
    }

    //risponde push javascript dentro \Common\Include\Head
    public static function Push(string $name, array $value): SaveResponse
    {
        if (!\Common\Client::$enabled)
        {
            $resp =  new SaveResponse();
            $resp->Success = false;
            $resp->InternalAvviso = "Client push non abilitato";
            return $resp;
        }

        $parametri = json_encode($value);

        $obj = PHPDOWEB()->ClientPush($name, $parametri);

        $resp = new \Common\Response\SaveResponse();
        $resp->Success = $obj->Errore == "false";
        $resp->InternalAvviso = $obj->Avviso;

        return $resp;
    }

    public static function ReloadViewAll(int $preloader = 200): SaveResponse
    {
        return self::Push("ReloadViewAll", [$preloader]);
    }

    public static function ReloadView(string $viewName = "", int $preloader = 200): SaveResponse
    {
        if (stripos($viewName, 'View\\') === 0)
            $viewName = substr($viewName, 5); // Rimuove i primi 5 caratteri

        return self::Push("ReloadView", [$viewName, $preloader]);
    }

    public static function Count(): int
    {
        return PHPDOWEB()->ClientCount();
    }
}