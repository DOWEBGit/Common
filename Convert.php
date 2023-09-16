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
        return html_entity_decode($value, ENT_QUOTES);
    }
}