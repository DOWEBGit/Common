<?php
declare(strict_types=1);

namespace Common\WebForms\Examples;

use Common\WebForms\Page;

class CheckBoxExample extends Page
{
    use CheckBoxDesigner;

    protected function DetailsChanged(): void
    {
        $this->__Panel_Details->Visible = $this->__CheckBox_Details->Checked;
    }

    protected function SaveClick(): void
    {
        if (!$this->__CheckBox_Terms->Checked)
        {
            $this->Alert->Fail('Le condizioni vanno accettate.');

            return;
        }

        $this->__Literal_Saved->Text = 'Salvato alle ' . date('H:i:s') . ': condizioni accettate, novita\' '
            . ($this->__CheckBox_News->Checked ? 'si\'' : 'no') . '.';
    }
}
