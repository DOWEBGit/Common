<?php
declare(strict_types=1);

namespace Common;

class Convert
{
    public static function ToBool(mixed $mixed): bool
    {
        return (bool)filter_var($mixed, FILTER_VALIDATE_BOOLEAN);
    }

    public static function ForInputValue(string $value): string
    {
        return str_replace("\"", "&quot;", html_entity_decode($value, ENT_QUOTES));
    }

    public static function ForInputValueBr(string $value): string
    {
        return self::BRToNewLine(self::ForInputValue($value));
    }

    public static function BRToNewLine(string $inputString): string
    {
        $inputString = str_replace("<br />", PHP_EOL, $inputString);
        $inputString = str_replace("<br/>", PHP_EOL, $inputString);
        return str_replace("<br>", PHP_EOL, $inputString);
    }

    public static function UrlCompatible(string $input): string
    {
        $input = html_entity_decode($input); //può arrivare encodato da database es.: asddjn&ampklsdmklds

        if (strlen($input) <= 0)
        {
            return '';
        }


        $input = strtolower($input); // Converte il carattere in minuscolo

        $charBuilder = '';

        $bar = false;

        for ($i = 0; $i < strlen($input); $i++)
        {
            $c = $input[$i];

            if (ctype_alnum($c))
            {
                $charBuilder .= $c;
                $bar = false;
                continue;
            }

            switch ($c)
            {
                case 'È':
                case 'è':
                case 'É':
                case 'é':
                case 'Ê':
                case 'ê':
                case 'Ë':
                case 'ë':
                    $charBuilder .= 'e';
                    $bar = false;
                    break;
                case 'À':
                case 'à':
                case 'Á':
                case 'á':
                case 'Â':
                case 'â':
                case 'Ã':
                case 'ã':
                case 'Ä':
                case 'ä':
                case 'Å':
                case 'å':
                case 'Æ':
                case 'æ':
                    $charBuilder .= 'a';
                    $bar = false;
                    break;
                case 'Ì':
                case 'ì':
                case 'Í':
                case 'í':
                case 'Î':
                case 'î':
                case 'Ï':
                case 'ï':
                    $charBuilder .= 'i';
                    $bar = false;
                    break;
                case 'ñ':
                case 'Ñ':
                    $charBuilder .= 'n';
                    $bar = false;
                    break;
                case 'Ò':
                case 'ò':
                case 'Ó':
                case 'ó':
                case 'Ô':
                case 'ô':
                case 'Õ':
                case 'õ':
                case 'Ö':
                case 'ö':
                case 'Ø':
                case 'ø':
                    $charBuilder .= 'o';
                    $bar = false;
                    break;
                case 'Ù':
                case 'ù':
                case 'Ú':
                case 'ú':
                case 'Û':
                case 'û':
                case 'Ü':
                case 'ü':
                    $charBuilder .= 'u';
                    $bar = false;
                    break;
                case 'ÿ':
                    $charBuilder .= 'y';
                    $bar = false;
                    break;
                case 'ç':
                case 'Ç':
                    $charBuilder .= 'c';
                    $bar = false;
                    break;
                case 'ß':
                    $charBuilder .= 'b';
                    $bar = false;
                    break;
                case "'":
                    break;
                case '&':
                    $charBuilder .= 'e'; // Sostituisci "&" con "e"
                    $bar = false;
                    break;
                default:

                    if ($bar)
                    {
                        break;
                    }

                    //if (strlen($input) < $i - 1 && $input[$i] != ' ' && $input[$i] != '-')
                    {
                        $charBuilder .= '-';
                    }

                    $bar = true;
            }

        }

        $charBuilder = ltrim($charBuilder, '-');
        $charBuilder = rtrim($charBuilder, '-');

        return $charBuilder;
    }

    /*
     * da un testo, trova i link http:// e li converte in link <a href=..
     * */
    public static function ConvertUrlsToLinks(string $inputString): string
    {
        $length = strlen($inputString);

        if ($length <= 0)
        {
            return $inputString;
        }

        $index = 0;

        while ($index < $length)
        {
            $indexHttp = strpos($inputString, "http://", $index);
            $indexHttps = strpos($inputString, "https://", $index);

            $index = -1;

            if ($indexHttps !== false)
            {
                $index = $indexHttps;
                $urlLength = 8;
            }

            if ($indexHttp !== false)
            {
                $index = $indexHttp;
                $urlLength = 7;
            }

            if ($index == -1)
            {
                return $inputString;
            }

            $rilevatoPunto = 0;

            while ($urlLength + $index < $length)
            {
                $ch = $inputString[$index + $urlLength];

                if ($ch == '.')
                {
                    $rilevatoPunto++;
                    $urlLength++;
                    continue;
                }

                if (ctype_alpha($ch) || is_numeric($ch) || $ch == '-')
                {
                    $urlLength++;
                    continue;
                }

                break;
            }

            if ($rilevatoPunto > 1)
            {
                $linkText = substr($inputString, $index, $urlLength);
                $tagLink = '<a target="_blank" href="' . $linkText . '">';
                $inputString = substr_replace($inputString, $tagLink, $index, 0);
                $index += strlen($tagLink) + $urlLength;

                $inputString = substr_replace($inputString, '">', $index, 0);
                $index += 2;

                $inputString = substr_replace($inputString, "</a>", $index, 0);
                $index += 4;
            }
            else
            {
                $index += $urlLength;
            }
        }

        return $inputString;
    }

    public static function GetDecodedQueryString(string $url) : array
    {
        $index = strpos($url, '?');

        if ($index !== false)
        {
            $url = substr($url, $index + 1);
        }

        // Replace characters replaced during encoding
        $codedQueryString = str_replace(['-', '_'], ['+', '/'], $url);

        $decbuff = base64_decode($codedQueryString);

        $codedQueryString = mb_convert_encoding($decbuff, "UTF-8");

        if (empty($codedQueryString))
        {
            return [];
        }

        $arrMsgs = explode('&', $codedQueryString);
        $dictionary = array();

        foreach ($arrMsgs as $arrMsg)
        {
            $arrIndMsg = explode('=', $arrMsg);

            $key = $arrIndMsg[0];
            $values = isset($arrIndMsg[1]) ? $arrIndMsg[1] : '';

            $dictionary[$key] = $values;
        }

        return $dictionary;
    }

    public static function GetEncodedLink($link) : string
    {
        $index = strpos($link, '?');

        if ($index === false)
        {
            return $link;
        }

        $querystring = substr($link, $index + 1);

        $link = substr($link, 0, $index + 1);

        $querystring = base64_encode($querystring);

        // Replace characters not compatible with URL
        $querystring = str_replace(['+', '/'], ['-', '_'], $querystring);

        return $link . $querystring;
    }
}