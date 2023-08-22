<?php

namespace Common;

class BaseBody
{
    function __construct()
    {   
        //ripristina windowstate e tempstate
        \Common\State::BodyToState();
    }
}
