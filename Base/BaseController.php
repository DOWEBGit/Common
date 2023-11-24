<?php
declare(strict_types=1);

namespace Common\Base;

use Common\Response\SaveResponse;
use Controller\AvvisiKey;

/**
 * Ogni controller deve implementare questa interfaccia
 */
abstract class BaseController
{
    abstract public static function OnSave(\Common\Base\BaseModel $baseModel = null): string;

    abstract public static function OnDelete(\Common\Base\BaseModel $baseModel = null): string;
}