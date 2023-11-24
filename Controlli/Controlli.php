<?php
declare(strict_types=1);

namespace Common\Controlli;

/**
 * I controlli delle pagine, pagine dati, aree,
 */
class Controlli
{
    public string $Valore;
    public string $PercorsoWeb;
    public \DateTime $DataUpload;
    public int $DimensioneCompressa;
    public int $DimensioneReale;
    public int $ImmagineAltezza;
    public int $ImmagineLarghezza;

    public function __construct()
    {
        $this->Valore = "";
        $this->PercorsoWeb = "";
        $this->DataUpload = new \DateTime();
        $this->DimensioneCompressa = 0;
        $this->DimensioneReale = 0;
        $this->ImmagineAltezza = 0;
        $this->ImmagineLarghezza = 0;
    }
}
