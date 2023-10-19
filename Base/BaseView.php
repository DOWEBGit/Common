<?php
declare(strict_types=1);

namespace Common\Base;

//ogni view deve estendere questa classe
class BaseView extends BodyToState
{
    public function GetViewId(bool $async = false) : string
    {
        $className = get_class($this);

        global $viewCounter;

        $viewCounter++;

        if (!$async)
            return "id=\"View" . $viewCounter . "\" view=\"" . $className . "\"";

        global $asyncArray;

        $asyncArray[] = $viewCounter;

        return "id=\"View" . $viewCounter . "\" view=\"" . $className . "\"";
    }
}