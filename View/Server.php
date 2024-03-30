<?php
declare(strict_types=1);

namespace Common\View;

class Server
{
    public static function View(string $viewName = ""): void
    {
        if ($viewName == "")
        {
            $url = $_GET["url"];

            // Verifica se $url inizia con "/xx/" dove xx sono due caratteri qualsiasi
            if (preg_match('/^\/.{2}\//', $url))
            {
                // Rimuovi i primi due caratteri
                $url = substr($url, 3);
            }

            $viewName = $url;

            $viewName = str_replace("/", "\\", $viewName);

            if (str_starts_with($viewName, "\\"))
                $viewName = substr($viewName, 1);
        }

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
