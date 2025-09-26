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
     * @throws \DateMalformedStringException
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

    /**
     * <p>Ritorna la data in \DateTime dell'ultimo giorno del mese e anno indicati, se mese e anno non sono validi ritorna false</p>
     * @param int $mese
     * @param int $anno
     * @return \DateTime|bool
     * @throws \DateMalformedStringException
     */
    public static function GetUltimaDataMese(int $mese, int $anno): \DateTime | bool
    {
        $mese = (strlen((string)$mese)== "1") ? "0".$mese : $mese;

        $dataInizio = "01/".$mese."/".$anno;

        $data = \DateTime::createFromFormat('d/m/Y', $dataInizio);
        $data->modify('last day of this month');

        return $data;
    }

    public static function DifferenzaInMinuti(\DateTime $data1, \DateTime $data2): int
    {
        $diff = $data1->diff($data2);
        return intval(($diff->days * 24 * 60) +
            ($diff->h * 60) + $diff->i);
    }
}