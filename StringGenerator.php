<?php
declare(strict_types=1);

namespace Common;

class StringGenerator
{

    /**
     * Ritorna un GUID generato da com_create_guid
     * @return string
     */
    public static function GUID(): string
    {
        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }

    /**
     * Ritorna una stringa casuale che corrisponde ad una password complessa.
     *
     * @param int $length La lunghezza della password da generare.
     * @param bool $specialChars Se includere caratteri speciali.
     * @return string La password complessa generata.
     */
    public static function GetComplexPassword(int $length = 8, bool $specialChars = true): string
    {
        $lowerCase = str_split('abcdefghilmnopqrstuvzxywjk');
        $upperCase = str_split('ABCDEFGHILMNOPQRSTUVZXYWJK');
        $digits = str_split('1234567890');
        $specials = str_split('\\|!"£$%&/()=?^\'@[]*;:<>,');

        $password = [];
        $special = false;
        $upper = false;
        $lower = false;
        $digit = false;

        for ($i = 0; $i < $length; $i++)
        {
            if ($specialChars)
            {
                $choice = random_int(0, 3);
            }
            else
            {
                $special = true;
                $choice = random_int(0, 2);
            }

            // Ensure all character types are included
            if ($i > 3)
            {
                if (!$lower) $choice = 0;
                if (!$upper) $choice = 1;
                if (!$digit) $choice = 2;
                if (!$special) $choice = 3;
            }

            switch ($choice)
            {
                case 0:
                    $password[] = $lowerCase[random_int(0, count($lowerCase) - 1)];
                    $lower = true;
                    break;
                case 1:
                    $password[] = $upperCase[random_int(0, count($upperCase) - 1)];
                    $upper = true;
                    break;
                case 2:
                    $password[] = $digits[random_int(0, count($digits) - 1)];
                    $digit = true;
                    break;
                case 3:
                    $password[] = $specials[random_int(0, count($specials) - 1)];
                    $special = true;
                    break;
            }
        }

        return implode('', $password);
    }

    /**
     * Ritorna un IP casuale.
     *
     * @return string L'IP casuale generato.
     */
    public static function GetRandomIp(): string
    {
        return mt_rand(1, 254) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(1, 254);
    }

    /**
     * Ritorna una stringa casuale.
     *
     * @param int $length La lunghezza della stringa da generare. Default è 8.
     * @return string La stringa casuale generata.
     */
    public static function GetRandomString(int $length = 8): string
    {
        $lowerCase = 'abcdefghijklmnopqrstuvwxyz';
        $upperCase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $characters = $lowerCase . $upperCase;
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++)
        {
            $randomIndex = mt_rand(0, $charactersLength - 1);
            $randomString .= $characters[$randomIndex];
        }

        return $randomString;
    }
}