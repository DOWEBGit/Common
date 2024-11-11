<?php
declare(strict_types=1);

namespace Common;

class MapUtilities
{
    /**
     * Ritorna in metri la distanza in linea d'aria tra 2 punti
     * @param float $latitudineUno
     * @param float $longitudineUno
     * @param float $latitudineDue
     * @param float $longitudineDue
     * @return float
     */
    public static function GetDistanzaTraCoordinate(float $latitudineUno, float $longitudineUno, float $latitudineDue, float $longitudineDue): float
    {
        $raggioTerrestre = 6371; //in chilometri
        $dLat = deg2rad($latitudineDue - $latitudineUno);
        $dLon  = deg2rad($longitudineDue - $longitudineUno);

        $latitudineUno = deg2rad($latitudineUno);
        $latitudineDue = deg2rad($latitudineDue);

        //qua uso https://en.wikipedia.org/wiki/Haversine_formula così becchiamo la distanza

        $a = sin($dLat/2) * sin($dLat/2) +sin($dLon/2) * sin($dLon/2) * cos($latitudineUno) * cos($latitudineDue);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        $distanza = ($raggioTerrestre * $c) * 1000;

        return $distanza;
    }
}