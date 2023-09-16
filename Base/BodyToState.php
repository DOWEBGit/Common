<?php

namespace Common\Base;

//ogni view deve estendere questa classe
class BodyToState
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