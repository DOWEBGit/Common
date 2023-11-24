<?php
declare(strict_types=1);

namespace Common\Collection;

class NameValue
{
    public $Name;
    public $Value;

    public function __construct($name, $value)
    {
        $this->Name = $name;
        $this->Value = $value;
    }
}