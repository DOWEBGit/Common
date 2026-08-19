<?php
declare(strict_types=1);

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

//Il dispatcher si fidava del solo cookie di sessione, che il browser allega anche a una
//richiesta partita da un altro sito: bastava un modulo altrove per far eseguire un'azione a
//nome di chi era autenticato. Il token viaggia nell'header X-Csrf-Token, emesso dallo stesso
//file che contiene le fetch (Common/Include/Head.php).
if (!\Common\Csrf::Verifica())
{
    \Common\Log::Error("\Common\View\Client.php, token CSRF assente o non valido: " . print_r($_GET, true));

    http_response_code(403);

    //si risponde con la forma che il JavaScript si aspetta, altrimenti il lock di Action()
    //non viene rilasciato e la pagina resta bloccata invece di mostrare l'errore
    \Common\State::BodyToState();

    if (!empty($view))
        echo json_encode(['', \Common\State::StateToBody()]);
    else
        echo \Common\State::StateToBody();

    exit();
}

//Il nome della classe arriva dal client e finisce nell'autoloader, che lo traduce in un
//percorso e fa include: un nome con ".." permetteva di includere qualunque file .php del
//server e di istanziare classi mai pensate come endpoint. Qui si accettano solo nomi di
//classe veri, e per le view solo quelle sotto il namespace View.
if (!empty($view))
{
    if (!preg_match('/^\\\\?View(\\\\[A-Za-z][A-Za-z0-9]*)+$/', $view))
    {
        \Common\Log::Error("\Common\View\Client.php, nome di view non valido: " . print_r($_GET, true));
        http_response_code(400);
        exit();
    }
}
else if (!preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $controller) || !preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $action))
{
    \Common\Log::Error("\Common\View\Client.php, nome di controller o action non valido: " . print_r($_GET, true));
    http_response_code(400);
    exit();
}

if (!empty($view))
{
    $className = $view;

    // Verifica se il metodo corrispondente all'azione esiste nella classe corrente
    if (method_exists($className, "Client"))
    {
        // Creazione dell'oggetto utilizzando ReflectionClass
        $reflectionClass = new ReflectionClass($className);
        $obj = $reflectionClass->newInstance();

        //salvo l'output del metodo client
        ob_start();

        $obj->Client();

        //prendo l'html
        $response = ob_get_contents();

        ob_end_clean();

        $result = [];
        $result[] = $response;
        $result[] = \Common\State::StateToBody(); //prendo lo state

        echo json_encode($result);
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
    echo \Common\State::StateToBody();
}
else
{
    // Metodo non trovato
    \Common\Log::Error("\Common\View\Client.php, non trovo la action \"" . $controller . "\":\"" . $action . "\": " . print_r($_GET, true));
    http_response_code(400);
}
