<?php

namespace Common;

class Log
{
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
