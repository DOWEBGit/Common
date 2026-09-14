<?php
declare(strict_types=1);

namespace Common\WebForms\Examples;

use Common\WebForms\Control;
use Common\WebForms\Page;

class ButtonExample extends Page
{
    use ButtonDesigner;

    public int $Clicks = 0;

    /** Un handler per tre controlli: chi ha cliccato e con che argomento arrivano insieme. */
    protected function ColorClick(Control $sender, string $color): void
    {
        $this->Clicks++;

        //il CommandArgument l'ha scritto il SERVER nel markup: si puo' usare com'e'.
        //Un valore che arrivasse dall'utente andrebbe controllato come qualunque altro.
        $this->__Label_Chosen->Text = $sender->Id . ' → ' . $color;
        $this->__Label_Chosen->Style->Add('color', $color)->Add('font-weight', '600');
    }

    protected function ResetClick(): void
    {
        $this->Clicks = 0;

        $this->__Label_Chosen->Text = 'niente';
        $this->__Label_Chosen->Style->Clear();
    }

    /** Lento di proposito: per vedere il bottone spento e l'UpdateProgress che compare. */
    protected function SlowClick(): void
    {
        $this->Clicks++;

        usleep(1_500_000);

        $this->Alert->Success('Arrivato dopo un secondo e mezzo: intanto il bottone era spento.');
    }

    protected function OnPreRender(): void
    {
        $this->__Literal_Clicks->Text = (string)$this->Clicks;
    }
}
