<?php
declare(strict_types=1);

namespace Common\WebForms\Examples;

use Common\WebForms\Page;

class LabelExample extends Page
{
    use LabelDesigner;

    /** Il valore sta in una variabile di pagina: resta da solo, come i campi di una form. */
    public int $Degrees = 20;

    protected function OnLoad(): void
    {
        if (!$this->IsPostBack)
            $this->Show();
    }

    protected function ColderClick(): void
    {
        $this->Degrees -= 5;
        $this->Show();
    }

    protected function WarmerClick(): void
    {
        $this->Degrees += 5;
        $this->Show();
    }

    private function Show(): void
    {
        $this->__Label_Temperature->Text = $this->Degrees . ' °C';

        $this->__Label_Temperature->Style->Add('color', match (true) {
            $this->Degrees <= 0  => '#1d4ed8',
            $this->Degrees >= 30 => '#b91c1c',
            default              => '#15803d',
        })->Add('font-weight', '600');

        $this->__Label_Temperature->Attributes->Add('title', 'Aggiornata alle ' . date('H:i:s'));
    }
}
