<?php
declare(strict_types=1);

namespace Common;

class ColorManagement
{
    /**
     * <p>Ritorna il colore di contrasto secondo il <b>Luminosity Contrast algorithm</b>, per esempio sapere quale colore va meglio su un dato sfondo</p>
     * @param string $hexColor, <b>deve essere un colore esadecimale a 6 cifre compreso di #</b>
     * @return string <p>Ritorna il colore di contrasto, oppure string vuota se il colore in input non segue le regole descritte per il parametro $hexColor</p>
     */
    public static function getContrastColor(string $hexColor): string
    {
        if (!@preg_match("/^#([A-Fa-f0-9]{6})$/i", $hexColor))
            return "";

        // hexColor RGB
        $R1 = hexdec(substr($hexColor, 1, 2));
        $G1 = hexdec(substr($hexColor, 3, 2));
        $B1 = hexdec(substr($hexColor, 5, 2));

        // Black RGB
        $blackColor = "#000000";
        $R2BlackColor = hexdec(substr($blackColor, 1, 2));
        $G2BlackColor = hexdec(substr($blackColor, 3, 2));
        $B2BlackColor = hexdec(substr($blackColor, 5, 2));

        // Calc contrast ratio
        $L1 = 0.2126 * pow($R1 / 255, 2.2) +
            0.7152 * pow($G1 / 255, 2.2) +
            0.0722 * pow($B1 / 255, 2.2);

        $L2 = 0.2126 * pow($R2BlackColor / 255, 2.2) +
            0.7152 * pow($G2BlackColor / 255, 2.2) +
            0.0722 * pow($B2BlackColor / 255, 2.2);

        $contrastRatio = 0;
        if ($L1 > $L2) {
            $contrastRatio = (int)(($L1 + 0.05) / ($L2 + 0.05));
        } else {
            $contrastRatio = (int)(($L2 + 0.05) / ($L1 + 0.05));
        }

        // If contrast is more than 5, return black color
        if ($contrastRatio > 5) {
            return '#000000';
        } else {
            // if not, return white color.
            return '#FFFFFF';
        }
    }
}