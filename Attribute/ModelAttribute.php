<?php
declare(strict_types=1);

namespace Common\Attribute;

use Attribute;

//usato per common/pagineinterne.php

#[Attribute]
class ModelAttribute
{
    public string $model = "";

    public function __construct(string $model)
    {
        $this->model = $model;
    }
}
