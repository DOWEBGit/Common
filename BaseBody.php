<?php

namespace Common;

class BaseBody
{
    function __construct()
    {   
        //ripristina windowstate e tempstate
        \Common\State::BodyToState();
    }

    public function GetViewId() : string
    {
        $className = get_class($this);

        global $viewCounter;

        $viewCounter++;

        return "id=\"View" . $viewCounter . "\" view=\"" . $className . "\"";
    }
}