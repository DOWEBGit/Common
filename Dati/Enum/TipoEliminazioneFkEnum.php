<?php
declare(strict_types=1);

namespace Common\Dati\Enum;

enum TipoEliminazioneFkEnum : string
{
    case Blocco = "Blocco";
    case Cascata = "Cascata";
    case Orfano = "Orfano";
    case False = "False";
}