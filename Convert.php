<?php

namespace Common;

class Convert
{
    public static function ToBool(mixed $mixed) : bool
    {
        return (bool) filter_var($mixed, FILTER_VALIDATE_BOOLEAN);
    }

    public static function ForInputValue(string $value)
    {
        return htmlentities($value, ENT_QUOTES);
    }
}