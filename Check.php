<?php
declare(strict_types=1);

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

    public static function IsEmail($email): bool
    {
        // Utilizza la funzione filter_var per verificare se la stringa è un indirizzo email valido
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function IsCellulare(string $phone): bool
    {
        //l'ho usato per Danzi per inviare gli SMS delle visite e mi pare fungere bene, chiaro che servirebbe un check magari
        //dei prefissi 'veri'

        //tolgo il +39 o +039 se c'è

        if (str_starts_with($phone, "+39"))
            $phone = substr($phone, 3);
        else if (str_starts_with($phone, "+039"))
            $phone = substr($phone, 4);

        //non voglio spazi
        $phone = str_replace(" ", "", $phone);

        $filtered_phone_number = filter_var($phone, FILTER_SANITIZE_NUMBER_INT);
        $phone_to_check = str_replace("-", "", $filtered_phone_number);

        $arrayPrefissi = array();

        #region Fastweb
        $arrayPrefissi[] = "370";
        $arrayPrefissi[] = "371";
        $arrayPrefissi[] = "372";
        $arrayPrefissi[] = "373";
        $arrayPrefissi[] = "374";
        $arrayPrefissi[] = "375";
        #endregion

        #region Iliad
        $arrayPrefissi[] = "350";
        $arrayPrefissi[] = "351";
        $arrayPrefissi[] = "352";
        $arrayPrefissi[] = "353";
        #endregion

        #region Rete Ferroviaria Italiana
        $arrayPrefissi[] = "313";
        #endregion

        #region TIM
        $arrayPrefissi[] = "330";
        $arrayPrefissi[] = "331";
        $arrayPrefissi[] = "332";
        $arrayPrefissi[] = "333";
        $arrayPrefissi[] = "334";
        $arrayPrefissi[] = "335";
        $arrayPrefissi[] = "336";
        $arrayPrefissi[] = "337";
        $arrayPrefissi[] = "338";
        $arrayPrefissi[] = "339";
        $arrayPrefissi[] = "360";
        $arrayPrefissi[] = "366";
        $arrayPrefissi[] = "368";
        #endregion

        #region Vodafone Italia
        $arrayPrefissi[] = "340";
        $arrayPrefissi[] = "341";
        $arrayPrefissi[] = "342";
        $arrayPrefissi[] = "344";
        $arrayPrefissi[] = "345";
        $arrayPrefissi[] = "346";
        $arrayPrefissi[] = "347";
        $arrayPrefissi[] = "348";
        $arrayPrefissi[] = "349";
        #endregion

        #region Wind Tre
        $arrayPrefissi[] = "320";
        $arrayPrefissi[] = "321";
        $arrayPrefissi[] = "322";
        $arrayPrefissi[] = "323";
        $arrayPrefissi[] = "324";
        $arrayPrefissi[] = "327";
        $arrayPrefissi[] = "328";
        $arrayPrefissi[] = "329";
        $arrayPrefissi[] = "380";
        $arrayPrefissi[] = "388";
        $arrayPrefissi[] = "389";
        $arrayPrefissi[] = "390";
        $arrayPrefissi[] = "391";
        $arrayPrefissi[] = "392";
        $arrayPrefissi[] = "393";
        $arrayPrefissi[] = "397";
        #endregion

        //piglio le prime tre cifre del numero
        $prefisso = substr($phone, 0, 3);

        if (!in_array($prefisso, $arrayPrefissi))
            return false;

        if (strlen($phone_to_check) < 9 || strlen($phone_to_check) > 14)
            return false;
        else
            return true;
    }
}
