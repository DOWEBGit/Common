<?php

namespace Common;

class Utils
{

    /**
     * Ritorna un GUID generato da com_create_guid
     * @return string
     */
    function GUID()
    {
        if (function_exists('com_create_guid') === true)
        {
            return trim(com_create_guid(), '{}');
        }

        //se la funzione com_create_guid non esiste, l'output generato da sta roba è identico al risultato di com_create_guid
        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }

}