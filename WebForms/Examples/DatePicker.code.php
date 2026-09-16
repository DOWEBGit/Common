<?php
declare(strict_types=1);

namespace Common\WebForms\Examples;

use Common\WebForms\Control;
use Common\WebForms\Controls\DatePicker;
use Common\WebForms\DateTimeMode;
use Common\WebForms\Page;

class DatePickerExample extends Page
{
    use DatePickerDesigner;

    protected function OnLoad(): void
    {
        if ($this->IsPostBack)
            return;

        //Min e' una data vera: il browser non fa scegliere prima, e il server comunque rilegge
        $this->__DatePicker_Day->Min = new \DateTimeImmutable('first day of january this year');
    }

    /** Uno dei due ha cambiato data: arriva il controllo, e il valore e' gia' una data. */
    protected function DateChanged(Control $sender): void
    {
        /** @var DatePicker $sender */
        $this->Alert->Success(($sender->Id === '__DatePicker_Day' ? 'Giorno' : 'Quando') . ' cambiato: '
            . ($sender->Value === null ? 'vuoto' : $sender->Value->format('d/m/Y H:i')));
    }

    /** Passa da solo giorno a giorno e ora, e viceversa, tenendo il valore. */
    protected function SwitchModeClick(): void
    {
        $this->__DatePicker_Day->Mode = $this->__DatePicker_Day->Mode === DateTimeMode::Date
            ? DateTimeMode::DateTime
            : DateTimeMode::Date;
    }

    protected function TodayClick(): void
    {
        $this->__DatePicker_Day->Value  = new \DateTimeImmutable('today');
        $this->__DatePicker_When->Value = new \DateTimeImmutable('now');
    }

    protected function OnPreRender(): void
    {
        $scrivi = static fn(DatePicker $dt): string => $dt->Value === null
            ? '(vuoto)'
            : $dt->Value->format($dt->Mode === DateTimeMode::Date ? 'l j F Y' : 'l j F Y, H:i');

        $this->__Literal_Dates->Text = 'primo [' . $this->__DatePicker_Day->Mode->name . ']: ' . $scrivi($this->__DatePicker_Day)
            . ' — secondo [' . $this->__DatePicker_When->Mode->name . ']: ' . $scrivi($this->__DatePicker_When);
    }
}
