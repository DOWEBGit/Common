<?php
declare(strict_types=1);

namespace Common\Controlli;

class ControlloImmagine extends ControlloFile
{
    function __construct()    
    {
        parent::__construct();
        
        $this->ImmagineAltezza = 0;
        $this->ImmagineLarghezza = 0;
    }        
    
    public int $ImmagineAltezza;
    
    public int $ImmagineLarghezza;
}
