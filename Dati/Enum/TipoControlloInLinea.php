<?php
declare(strict_types=1);

namespace Common\Dati\Enum;

enum TipoControlloInLinea : string
{
    case SolaLettura = "SolaLettura";
    case Editor = "Editor";
}