<?php
declare(strict_types=1);

namespace Common\View;

class Server
{
    public static function View(string $viewName) : void
    {
        $className = "\\View\\" . $viewName;

        // Verifica se il metodo corrispondente all'azione esiste nella classe corrente
        if (method_exists($className, "Server"))
        {
            $reflectionClass = new \ReflectionClass($className);
            $obj = $reflectionClass->newInstance();
            $obj->Server();
        }
        else
        {
            // Metodo non trovato         
            http_response_code(400);
        }
    }
}
