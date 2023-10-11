<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    http_response_code(400);
    exit();
}

$controller = "";
$action = "";
$view = "";

if (isset($_GET['controller']) && isset($_GET['action']))
{
    $controller = $_GET['controller'];
    $action = $_GET['action'];
}
else if (isset($_GET['view']))
{
    $view = $_GET['view'];
}

if ($action === null && $view === null)
{
    // Metodo non trovato
    \Common\Log::Error("\Common\View\Client.php, indicare Action o View: " . print_r($_GET, true));
    http_response_code(400);
    exit();
}

include_once $_SERVER['DOCUMENT_ROOT'] . '/public/php/start.php';

if (!empty($view))
{
    $className = $view;

// Verifica se il metodo corrispondente all'azione esiste nella classe corrente
    if (method_exists($className, "Client"))
    {
        // Creazione dell'oggetto utilizzando ReflectionClass
        $reflectionClass = new ReflectionClass($className);
        $obj = $reflectionClass->newInstance();

        // Utilizzo dell'oggetto
        $obj->Client();
    }
    else
    {
        // Metodo non trovato
        \Common\Log::Error("\Common\View\Client.php, non trovo la view \"" . $view . "\": " . print_r($_GET, true));
        http_response_code(400);
    }

    return;
}

$className = "\\Action\\" . $controller;

// Verifica se il metodo corrispondente all'azione esiste nella classe corrente
if (method_exists($className, $action))
{
    // Creazione dell'oggetto utilizzando ReflectionClass
    $reflectionClass = new ReflectionClass($className);
    $obj = $reflectionClass->newInstance();

    $v = call_user_func(array($obj, $action));

    //invia lo stato a javascript, tempState e windowState
    \Common\State::StateToBody();
}
else
{
    // Metodo non trovato
    \Common\Log::Error("\Common\View\Client.php, non trovo la action \"" . $controller . "\":\"" . $action . "\": " . print_r($_GET, true));
    http_response_code(400);
}
