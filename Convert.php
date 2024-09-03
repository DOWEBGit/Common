<?php
declare(strict_types=1);

namespace Common;

class Convert
{
    //da una stringa e una istanza di model sostituisce le occorrenze nella stringa con i valori di tutte le proprietà dell'istanza insensitive.
    // es.: la foresta [Colore] era fredda,  [Colore] -> se model ha quella proprietà, viene inserito il valore
    public static function ReplaceModel(\Common\Base\BaseModel $item, string $contenuto)
    {
        $reflection = new \ReflectionClass($item);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property)
        {
            $nome = "";
            $tipo = "";

            if ($property->name == "Id")
            {
                $nome = "Id";
                $tipo = "Numeri";
            }
            else if ($property->name == "ParentId")
            {
                $nome = "ParentId";
                $tipo = "Numeri";
            }
            else if ($property->name == "Visibile")
            {
                $nome = "Visibile";
                $tipo = "Numeri";
            }
            else if ($property->name == "Aggiornamento")
            {
                $nome = "Aggiornamento";
                $tipo = "Data";
            }
            else if ($property->name == "Inserimento")
            {
                $nome = "Inserimento";
                $tipo = "Data";
            }
            else
            {
                $attributes = $property->getAttributes(\Common\Attribute\PropertyAttribute::class);

                if (!$attributes)
                    continue;

                $args = $attributes[0]->getArguments();

                if (count($args) < 2)
                    continue;

                $nome = $args['0'];
                $tipo = $args['1'];
            }

            $propertyValue = $property->getValue($item);

            switch ($tipo)
            {
                case 'Data':
                    if ($propertyValue instanceof \DateTime)
                        $propertyValue = $propertyValue->format('d/m/Y');
                    break;
                case 'DataOra':
                    if ($propertyValue instanceof \DateTime)
                        $propertyValue = $propertyValue->format('d/m/Y H:i');
                    break;
                case 'Numeri':
                    if (is_bool($propertyValue))
                        $propertyValue = $propertyValue ? 'Si' : 'No';
                    else
                        $propertyValue = (string)$propertyValue;
                    break;
                case 'Testo':
                    $propertyValue = strval($propertyValue);
                    break;
                default:
                    break;
            }
            $pattern = '/\[' . preg_quote($nome, '/') . '\]/i';
            $contenuto = preg_replace($pattern, $propertyValue, $contenuto);
        }

        return $contenuto;
    }

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

    private static function BeautifyFilename(string $filename) : string
    {
        // reduce consecutive characters
        $filename = preg_replace(array(
             // "file   name.zip" becomes "file-name.zip"
             '/ +/',
             // "file___name.zip" becomes "file-name.zip"
             '/_+/',
             // "file---name.zip" becomes "file-name.zip"
             '/-+/'
         ), '-', $filename);
        $filename = preg_replace(array(
             // "file--.--.-.--name.zip" becomes "file.name.zip"
             '/-*\.-*/',
             // "file...name..zip" becomes "file.name.zip"
             '/\.{2,}/'
         ), '.', $filename);
        // lowercase for windows/unix interoperability http://support.microsoft.com/kb/100625
        $filename = mb_strtolower($filename, mb_detect_encoding($filename));
        // ".file-name.-" becomes "file-name"
        $filename = trim($filename, '.-');
        return $filename;
    }

    /**
     * @param string $filename <p>Il filename da fixare</p>
     * @param bool $beautify
     * @return string
     */
    public static function FilterFilename(string $filename, bool $beautify = true) {
        // sanitize filename
        $filename = preg_replace(
            '~
        [<>:"/\\|?*]|            # file system reserved https://en.wikipedia.org/wiki/Filename#Reserved_characters_and_words
        [\x00-\x1F]|             # control characters http://msdn.microsoft.com/en-us/library/windows/desktop/aa365247%28v=vs.85%29.aspx
        [\x7F\xA0\xAD]|          # non-printing characters DEL, NO-BREAK SPACE, SOFT HYPHEN
        [#\[\]@!$&\'()+,;=]|     # URI reserved https://tools.ietf.org/html/rfc3986#section-2.2
        [{}^\~`]                 # URL unsafe characters https://www.ietf.org/rfc/rfc1738.txt
        ~x',
            '-', $filename);
        // avoids ".", ".." or ".hiddenFiles"
        $filename = ltrim($filename, '.-');
        // optional beautification
        if ($beautify) $filename = self::BeautifyFilename($filename);
        // maximize filename length to 255 bytes http://serverfault.com/a/9548/44086
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $filename = mb_strcut(pathinfo($filename, PATHINFO_FILENAME), 0, 255 - ($ext ? strlen($ext) + 1 : 0), mb_detect_encoding($filename)) . ($ext ? '.' . $ext : '');
        return $filename;
    }
}