<?php
declare(strict_types=1);

namespace Common;

class DateTimeUtilities
{
    /**<p>Ritorna la data di inizio e la data fine del numero settimana relativo all'anno passati come parametri</p>
     * <p>Entrambe le date sono classi DateTime e possono essere lette usando $result->DataInizio e $result->DataFine</p>
     * @param int $numeroSettimana
     * @param int $anno
     * @return \stdClass
     */
    public static function GetStartAndEndDate(int $numeroSettimana, int $anno): \stdClass
    {
        $data = new \DateTime('midnight');
        //clono se no ->modify mi modifica anche la data di inizio
        $result = new \stdClass();
        $result->DataInizio = clone $data->setISODate($anno, $numeroSettimana);
        $result->DataFine = $data->modify('+6 days');
        return $result;
    }
}