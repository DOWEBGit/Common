<?php
declare(strict_types=1);

namespace Common\Base;

/**
 * Ogni View deve implementare questa interfaccia
 */
interface IView
{
    public function Client() : void;

    public function Server() : void;
}
