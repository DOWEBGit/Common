<?php
declare(strict_types=1);

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace Common\Response;

use Common\Response;

/**
 * Description of SaveResponse
 *
 * @author Administrator
 */
class SaveResponseModel extends SaveResponse
{   
    function __construct(?Response\SaveResponse $response = null)
    {
        parent::__construct();
        
        $this->Model = null;        
        
        if ($response == null)
            return;            
        
        $this->InternalAvvisi = $response->InternalAvvisi;
        $this->InternalAvviso = $response->InternalAvviso;
        $this->Success = $response->Success;
    }
    
    public ?\Common\Base\BaseModel $Model;
}
