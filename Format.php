<?php
declare(strict_types=1);

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace Common;

/**
 * Description of Format
 *
 * @author Administrator
 */
class Format
{
    //arriva il prezzo in centesimi
    public static function FormatPrezzo(int|float $prezzo): string
    {
        return number_format($prezzo / 100, 2, ',', '');
    }

    public static function FormatPrezzoPunto(int|float $prezzo): string
    {
        return number_format($prezzo / 100, 2, '.', '');
    }

    public static function ValueNA(string $value, string $option = "N/A"): string
    {
        return empty($value) ? $option : $value;
    }

    public static function DataNA(\DateTime $value, string $format, string $option = "N/A"): string
    {
        if ($value->format("d") == "01" &&
            $value->format("m") == "01" &&
            $value->format("Y") == "1970"
        )
            return $option;

        return $value->format($format);
    }

    /**
     * Converte un oggetto DateTime in un numero intero lungo per il database.
     * È un metodo super ottimizzato che in genere è 3 o 4 volte più veloce di .ToString("yyyyMMddHHmmss").
     *
     * @param \DateTime $dateTime L'oggetto DateTime da convertire.
     * @return int Il numero intero lungo che rappresenta la data e l'ora in formato compatto.
     */
    public static function DateTimeToInt(\DateTime $dateTime): int
    {
        return ((int)$dateTime->format('Y') * 10000000000) +
            ((int)$dateTime->format('n') * 100000000) +
            ((int)$dateTime->format('j') * 1000000) +
            ((int)$dateTime->format('G') * 10000) +
            ((int)$dateTime->format('i') * 100) +
            (int)$dateTime->format('s');
    }

    public static function LimitWords($inputString, $n): string
    {
        // Rimuove i caratteri di punteggiatura e li sostituisce con spazi
        $inputString = preg_replace('/[[:punct:]]/', ' ', $inputString);

        // Suddivide la stringa in parole
        $words = preg_split('/\s+/', $inputString, -1, PREG_SPLIT_NO_EMPTY);

        // Prende le prime n parole
        $limitedWords = array_slice($words, 0, $n);

        // Ricostruisce la stringa limitata
        $limitedString = implode(' ', $limitedWords);

        return $limitedString;
    }

    public static function TrimCase(string $inputString): string
    {
        // Rimuovi spazi all'inizio e alla fine della stringa
        $trimmedString = trim($inputString);

        // Verifica se tutti i caratteri sono minuscoli
        if (ctype_lower($trimmedString))
        {
            // Metti il primo carattere in maiuscolo
            $formattedString = ucfirst($trimmedString);
        }
        // Verifica se tutti i caratteri sono maiuscoli
        elseif (ctype_upper($trimmedString))
        {
            // Metti tutti i caratteri in minuscolo e il primo in maiuscolo
            $formattedString = ucfirst(strtolower($trimmedString));
        }
        // Altrimenti, mantieni la formattazione originale
        else
        {
            $formattedString = $trimmedString;
        }

        return $formattedString;
    }
}
