<?php
declare(strict_types=1);

namespace Common\WebForms\Examples;

use Common\WebForms\Page;

class DropDownListExample extends Page
{
    use DropDownListDesigner;

    private const array CITIES = [
        'Veneto'    => ['VR' => 'Verona', 'VE' => 'Venezia', 'PD' => 'Padova', 'VI' => 'Vicenza'],
        'Lombardia' => ['MI' => 'Milano', 'BS' => 'Brescia', 'BG' => 'Bergamo'],
        'Piemonte'  => ['TO' => 'Torino', 'NO' => 'Novara'],
    ];

    protected function OnLoad(): void
    {
        if ($this->IsPostBack)
            return;

        //le voci dal codice: valore => testo. Le regioni sono le chiavi dell'elenco
        $regioni = array_keys(self::CITIES);

        $this->__DropDownList_Region->Items         = array_combine($regioni, $regioni);
        $this->__DropDownList_Region->SelectedValue = 'Veneto';

        $this->RegionChanged();
        $this->ColorChanged();
    }

    protected function ColorChanged(): void
    {
        $colore = $this->__DropDownList_Color->SelectedValue;

        $this->__Label_Sample->Style->Add('color', $colore === '' ? '#94a3b8' : $colore);
        $this->__Label_Sample->Text = $colore === '' ? '■ nessun colore' : '■ ' . $this->__DropDownList_Color->Items[$colore];
    }

    /** La seconda tendina dipende dalla prima: si riempie a ogni cambio, e la scelta si azzera. */
    protected function RegionChanged(): void
    {
        $this->__DropDownList_City->Items         = self::CITIES[$this->__DropDownList_Region->SelectedValue] ?? [];
        $this->__DropDownList_City->SelectedValue = (string)array_key_first($this->__DropDownList_City->Items);
    }

    protected function ReadClick(): void
    {
        $this->__Literal_Read->Text = 'Regione: ' . $this->__DropDownList_Region->SelectedValue
            . ' — citta\': ' . $this->__DropDownList_City->SelectedValue
            . ' (' . ($this->__DropDownList_City->Items[$this->__DropDownList_City->SelectedValue] ?? '?') . ')';
    }
}
