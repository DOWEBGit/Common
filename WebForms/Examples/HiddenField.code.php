<?php
declare(strict_types=1);

namespace Common\WebForms\Examples;

use Common\WebForms\Page;

class HiddenFieldExample extends Page
{
    use HiddenFieldDesigner;

    protected function ReadClick(): void
    {
        $valore = $this->__Hidden_Id->Value;

        //e' un dato che arriva dal browser: si controlla come qualunque altro
        $this->__Literal_Read->Text = ctype_digit($valore)
            ? 'Il server ha letto l\'id ' . $valore . ' alle ' . date('H:i:s') . '.'
            : 'Il server ha letto "' . $valore . '", che non e\' un id: lo scarta.';
    }

    protected function NextClick(): void
    {
        $this->__Hidden_Id->Value = (string)((int)$this->__Hidden_Id->Value + 1);

        $this->ReadClick();
    }
}
