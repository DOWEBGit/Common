<?php
declare(strict_types=1);

namespace Common\WebForms\Controls;

use Common\WebForms\Control;

/**
 * Una voce di DropDownList o ListBox, dichiarata nel markup:
 *
 *   <dw:DropDownList id="ddlStato">
 *       <dw:ListItem Value="1" Text="Attivo" Selected="true" />
 *       <dw:ListItem Value="0" Text="Sospeso" />
 *   </dw:DropDownList>
 *
 * Non si rende da solo: il costruttore dell'albero lo raccoglie dentro Items del controllo
 * che lo contiene e lo toglie dai figli. Da li' in poi le voci vivono nello stato come
 * quelle assegnate dal codebehind, e i due modi non si distinguono piu'.
 *
 * Se Text manca vale Value, che e' il caso piu' frequente ("30", "50", "100").
 */
class ListItem extends Control
{
    public string $Value = '';

    public string $Text = '';

    public bool $Selected = false;

    protected function ViewStateProperties(): array
    {
        return array_merge(parent::ViewStateProperties(), ['Value', 'Text', 'Selected']);
    }

    public function Label(): string
    {
        return $this->Text !== '' ? $this->Text : $this->Value;
    }

    public function Render(): string
    {
        return '';
    }
}
