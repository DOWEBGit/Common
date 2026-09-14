<?php
declare(strict_types=1);

namespace Common\WebForms\Examples;

use Common\WebForms\Page;

class ListBoxExample extends Page
{
    use ListBoxDesigner;

    protected function ReadClick(): void
    {
        $scelti = $this->__ListBox_Days->SelectedValues;

        $this->__Literal_Read->Text = $scelti === []
            ? 'Nessun giorno scelto.'
            : 'Scelti ' . count($scelti) . ': ' . implode(', ', array_map(fn(string $v): string => $this->__ListBox_Days->Items[$v], $scelti));
    }

    protected function WeekdaysClick(): void
    {
        $this->__ListBox_Days->SelectedValues = ['1', '2', '3', '4', '5'];

        $this->ReadClick();
    }
}
