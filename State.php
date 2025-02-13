<?php
declare(strict_types=1);

namespace Common;

/**
 * TempState: il passaggio di parametri tra singole chiamate, Javascript PHP
 * WindowState: le variabili permanenti salvate nella finestra
 * SessionState: le variabili permanenti salvate nella finestra
 */

class State
{
    //TempState non mantiene tra chiamate, solo una volta, uso due array,
    //uno per leggere e l'altro per scrivere in modo da perdere il contenuto vecchio e mantenere quello nuovo
    public static function TempWrite(string $name, string $value) : void
    {
        //scrivo sia sul Read che sul Write in modo che se leggo quello che scrivo nello stesso codice il risultato è uniforme
        //ad ogni modo nell'output mi porto dietro solo il _TempStateWrite

        $name = strtolower($name);

        $windowState = [];

        if (isset($GLOBALS['_TempStateWrite']))
        {
            $json = $GLOBALS['_TempStateWrite'];

            $windowState = json_decode(base64_decode($json), true);

            if ($windowState === null)
                $windowState = [];
        }

        $windowState[$name] = strval($value);

        $newJson = base64_encode(json_encode($windowState));

        $GLOBALS['_TempStateWrite'] = $newJson;



        $windowState = [];

        if (isset($GLOBALS['_TempStateRead']))
        {
            $json = $GLOBALS['_TempStateRead'];

            $windowState = json_decode(base64_decode($json), true);

            if ($windowState === null)
                $windowState = [];
        }

        $windowState[$name] = strval($value);

        $newJson = base64_encode(json_encode($windowState));

        $GLOBALS['_TempStateRead'] = $newJson;
    }

    public static function TempRead(string $name) : string
    {
        $name = strtolower($name);

        if (isset($GLOBALS['_TempStateRead']))
        {
            $json = $GLOBALS['_TempStateRead'];

            $windowState = [];

            if ($json)
            {
                $windowState = json_decode(base64_decode($json), true);

                if ($windowState === null)
                    return "";
            }

            if (isset($windowState[$name]))
                return $windowState[$name];
        }

        return "";
    }


    public static function WindowWrite(string $name, string $value) : void
    {
        $name = strtolower($name);

        $windowState = [];

        if (isset($GLOBALS['_WindowState']))
        {
            $json = $GLOBALS['_WindowState'];

            $windowState = json_decode(base64_decode($json), true);

            if ($windowState === null)
                $windowState = [];
        }

        if (isset($windowState[$name]) && $windowState[$name] === $value)
            return;

        $windowState[$name] = strval($value);

        $newJson = base64_encode(json_encode($windowState));

        $GLOBALS['_WindowState'] = $newJson;
    }

    public static function WindowRead(string $name, string $default = "") : string
    {
        $name = strtolower($name);

        if (isset($GLOBALS['_WindowState']))
        {
            $json = $GLOBALS['_WindowState'];

            $windowState = [];

            if ($json)
            {
                $windowState = json_decode(base64_decode($json), true);

                if ($windowState === null)
                    return "";
            }

            if (isset($windowState[$name]) && $windowState[$name] != "")
                return $windowState[$name];
        }

        return $default;
    }

    public static function SessionId() : string
    {
        if (session_status() == PHP_SESSION_NONE)
            session_start();

        return session_id();
    }

    public static function CookieRead(string $name) : string
    {
        if ($name == "") {
            return "";
        }

        // Controlla se il cookie esiste e restituisce il suo valore
        if (isset($_COOKIE[$name])) {
            return $_COOKIE[$name];
        }

        return ""; // Se il cookie non esiste, restituisci una stringa vuota
    }

    public static function CookieWrite(string $name, string $value) : void
    {
        if (headers_sent() || $name == "" || $value == "") {
            return;
        }

        $expiryTime = time() + (86400 * 30); // 86400 secondi = 30 giorni

        // Imposta il cookie
        setcookie($name, $value, $expiryTime, '/'); // '/' indica che il cookie è valido per tutto il dominio
    }

    public static function CookieDelete(string $name) : void
    {
        if ($name == "" || headers_sent())
            return;

        $expiryTime = time() - 3600;

        // Imposta il cookie
        setcookie($name, '', $expiryTime, '/'); // '/' indica che il cookie è valido per tutto il dominio
    }

    public static function SessionWrite(string $name, string $value) : void
    {
        if (session_status() == PHP_SESSION_NONE)
            session_start();

        $_SESSION[strtolower($name)] = $value;
    }

    public static function SessionRead(string $name) : string
    {
        if (session_status() == PHP_SESSION_NONE)
            session_start();

        $name = strtolower($name);

        if (!isset($_SESSION[$name]))
            return "";

        return $_SESSION[$name];
    }


    public static function WriteToHtml() : void
    {
        if (isset($GLOBALS['_WindowState']))
            echo '<input type="hidden" id="WindowState" value="' . $GLOBALS['_WindowState'] . '">';
        else
            echo '<input type="hidden" id="WindowState" value="">';


        if (isset($GLOBALS['_TempStateWrite']))
            echo '<input type="hidden" id="TempState" value="' . $GLOBALS['_TempStateWrite'] . '">';
        else
            echo '<input type="hidden" id="TempState" value="">';
    }

    //sessionstate lo scrivo tramite javascript direttamente con una api SetSessionState
    //chiamato da baseview e da baseaction

    public static function BodyToState() : void
    {
        if (!isset($_POST))
            return;

        $json = $_POST["INPUTSTREAM"];

        $stateArray = json_decode($json, true);

        if (!is_array($stateArray))
            return;

        if (isset($stateArray[0]))
            $GLOBALS['_TempStateRead'] = $stateArray[0];

        if (isset($stateArray[1]))
            $GLOBALS['_WindowState'] = $stateArray[1];
    }

    public static function StateToBody() : string
    {
        if (!isset($GLOBALS['_TempStateWrite']))
            $resultArray[] = "";
        else
            $resultArray[] = $GLOBALS['_TempStateWrite'];

        if (!isset($GLOBALS['_WindowState']))
            $resultArray[] = "";
        else
            $resultArray[] = $GLOBALS['_WindowState'];

        return json_encode($resultArray);
    }
}