<?php
declare(strict_types=1);

namespace Common\Attribute;

use Attribute;

//usato sulle proprietà dei model
#[Attribute]
class PropertyAttribute
{
    function __construct(string $nomeColonna, string $tipoDato, bool $univoco)
    {

    }
}
