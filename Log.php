<?php
declare(strict_types=1);

namespace Common;

class Log
{
    public static function ErrorSaveResponse(\Common\Response\SaveResponse $saveResponse) : \Common\Response\SaveResponse
    {
        $e = new \Exception;
        $trace = $e->getTraceAsString();

        $str = $saveResponse->Avviso(PHP_EOL) . ", " . $trace;

        $obj = PHPDOWEB();
        $obj->LogError($str);

        return $saveResponse;
    }

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
