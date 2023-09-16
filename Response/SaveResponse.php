<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace Common\Response;

/**
 * Description of SaveResponse
 *
 * @author Administrator
 */
class SaveResponse
{
    function __construct()
    {
        $this->Success = false;
        $this->InternalAvvisi = [];
        $this->InternalAvviso = '';
    }

    public function Avviso(string $delimiter = '<br>'): string
    {
        if ($this->InternalAvviso != '')
            return $this->InternalAvviso;

        if (count($this->InternalAvvisi) > 0)
        {
            $result = '';

            foreach ($this->InternalAvvisi as $key => $value)
                $result .= $key . ': ' . $value . $delimiter;
            
            if (strlen($result) > 0)
                $result = substr ($result, 0, strlen($result) - strlen($delimiter));
            
            return $result;
        }

        return '';
    }

    public function AvvisoDecode(string $delimiter = '<br>'): string
    {
        if ($this->InternalAvviso != '')
            return html_entity_decode($this->InternalAvviso);

        if (count($this->InternalAvvisi) > 0)
        {
            $result = '';

            foreach ($this->InternalAvvisi as $key => $value)
                $result .= $key . ': ' . $value . $delimiter;

            if (strlen($result) > 0)
                $result = substr ($result, 0, strlen($result) - strlen($delimiter));

            return html_entity_decode($result);
        }

        return '';
    }

    public bool $Success;
    public array $InternalAvvisi;
    public string $InternalAvviso;
}
