<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace Common;

/**
 * Description of Check
 *
 * @author Administrator
 */
class Check
{

    //Verifica se un codice fiscale è valido
    public static function VerificaCodiceFiscale($codiceFiscale): bool
    {
        $pattern = '/^[A-Z]{6}[0-9LMNPQRSTUV]{2}[ABCDEHLMPRST][0-9LMNPQRSTUV]{2}[A-Z][0-9LMNPQRSTUV]{3}[A-Z]$/';

        // Verifica che il codice fiscale abbia il formato corretto
        if (!preg_match($pattern, $codiceFiscale))
        {
            return false;
        }

        // Verifica la validità del codice fiscale
        $s = 0;
        for ($i = 1; $i <= 13; $i += 2)
        {
            $c = $codiceFiscale[$i];
            if ($c >= '0' && $c <= '9')
            {
                $s += ord($c) - ord('0');
            }
            else
            {
                $s += ord($c) - ord('A');
            }
        }
        for ($i = 0; $i <= 14; $i += 2)
        {
            $c = $codiceFiscale[$i];
            if ($c >= '0' && $c <= '9')
            {
                $c = chr(ord('A') + $c);
            }
            switch ($c)
            {
                case 'A': $s += 1;
                    break;
                case 'B': $s += 0;
                    break;
                case 'C': $s += 5;
                    break;
                case 'D': $s += 7;
                    break;
                case 'E': $s += 9;
                    break;
                case 'F': $s += 13;
                    break;
                case 'G': $s += 15;
                    break;
                case 'H': $s += 17;
                    break;
                case 'I': $s += 19;
                    break;
                case 'J': $s += 21;
                    break;
                case 'K': $s += 2;
                    break;
                case 'L': $s += 4;
                    break;
                case 'M': $s += 18;
                    break;
                case 'N': $s += 20;
                    break;
                case 'O': $s += 11;
                    break;
                case 'P': $s += 3;
                    break;
                case 'Q': $s += 6;
                    break;
                case 'R': $s += 8;
                    break;
                case 'S': $s += 12;
                    break;
                case 'T': $s += 14;
                    break;
                case 'U': $s += 16;
                    break;
                case 'V': $s += 10;
                    break;
                case 'W': $s += 22;
                    break;
                case 'X': $s += 25;
                    break;
                case 'Y': $s += 24;
                    break;
                case 'Z': $s += 23;
                    break;
            }
        }
        if ($s % 26 != ord($codiceFiscale[15]) - ord('A'))
        {
            return false;
        }

        return true;
    }

    public static function IsEmail($email)
    {
        // Utilizza la funzione filter_var per verificare se la stringa è un indirizzo email valido
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
