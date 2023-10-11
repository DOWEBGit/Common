<?php

namespace Common\Controlli;

class ControlloFile
{
    function __construct()
    {
        $this->Nome = '';
        $this->Bytes = '';        
        $this->DimensioneReale = 0;
        $this->DimensioneCompressa = 0;
        $this->Base64Encoded = false;
    }

    public bool $Base64Encoded;
    
    public string $Nome;
    
    public string $Bytes;
    
    public int $DimensioneReale;
    
    public int $DimensioneCompressa;
}
