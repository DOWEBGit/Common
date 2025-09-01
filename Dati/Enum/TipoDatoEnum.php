<?php
declare(strict_types=1);

namespace Common\Dati\Enum;

enum TipoDatoEnum : string
{
    case DataOra = "DataOra";
    case Data = "Data";
    case Numeri = "Numeri";
    case Testo = "Testo";
    case File = "File";
    case Immagini = "Immagini";
    case Dato = "Dato";
}