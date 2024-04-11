<?php
declare(strict_types=1);

namespace Common\Attribute;

use Attribute;

//usato per code/enum/PagineInterneEnum.php

#[Attribute]
class PagineInterneAttribute
{
    public int $paginaInternaId = 0;

    public function __construct(int $paginaInternaId)
    {
        $this->paginaInternaId = $paginaInternaId;
    }
}
