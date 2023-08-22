<?php

namespace Common;

class ControlloFile
{
    function __construct()
    {
        $this->Nome = '';
        $this->Bytes = '';        
        $this->DimensioneReale = 0;
        $this->DimensioneCompressa = 0;
    }    
    
    public string $Nome;
    
    public string $Bytes;
    
    public int $DimensioneReale;
    
    public int $DimensioneCompressa;
}
