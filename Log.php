<?php

namespace Common;

class Log
{
    public static function ErrorStack(string $messaggio) : string
    {
        $e = new \Exception;
        $trace = $e->getTraceAsString();

        $str = $messaggio . ", " . $trace;

        $obj = PHPDOWEB();
        $obj->LogError($str);
        return $str;
    }

    public static function Error(string $messaggio) : string
    {
        $obj = PHPDOWEB();
        $obj->LogError($messaggio);
        return  $messaggio;
    }
    
    public static function Warn(string $messaggio) : string
    {
        $obj = PHPDOWEB();
        $obj->LogWarn($messaggio);
        return $messaggio;
    }       
}
