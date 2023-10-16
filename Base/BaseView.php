<?php
declare(strict_types=1);

namespace Common\Base;

//ogni view deve estendere questa classe
class BaseView extends BodyToState
{
    public function GetViewId() : string
    {
        $className = get_class($this);

        global $viewCounter;

        $viewCounter++;

        return "id=\"View" . $viewCounter . "\" view=\"" . $className . "\"";
    }
}