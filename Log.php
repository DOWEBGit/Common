<?php

namespace Common;

class Log
{
    public static function ErrorStack(string $messaggio) : void
    {
        $e = new \Exception;
        $trace = $e->getTraceAsString();

        $obj = PHPDOWEB();
        $obj->LogError($messaggio . ", " . $trace);
    }

    public static function Error(string $messaggio) : void
    {
        $obj = PHPDOWEB();
        $obj->LogError($messaggio);
    }
    
    public static function Warn(string $messaggio) : void
    {
        $obj = PHPDOWEB();
        $obj->LogWarn($messaggio);
    }       
}
