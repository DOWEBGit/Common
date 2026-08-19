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

        //quando $viewName arriva da $_GET["url"] è scelto dal client, e finisce nell'autoloader
        //che ne fa un percorso e lo include: senza controllo un ".." permetteva di includere
        //qualunque file .php del server
        if (!preg_match('/^[A-Za-z][A-Za-z0-9]*(\\\\[A-Za-z][A-Za-z0-9]*)*$/', $viewName))
        {
            echo 'View non valida';
            return;
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
            echo 'View '.$viewName.' non trovata';
        }
    }
}
