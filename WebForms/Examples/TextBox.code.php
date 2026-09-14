<?php
declare(strict_types=1);

namespace Common\WebForms\Examples;

use Common\WebForms\Control;
use Common\WebForms\Page;

class TextBoxExample extends Page
{
    use TextBoxDesigner;

    public int $Postbacks = 0;

    private const array COUNTRIES = [
        'Italia', 'Francia', 'Germania', 'Spagna', 'Portogallo', 'Austria', 'Svizzera', 'Belgio',
        'Paesi Bassi', 'Danimarca', 'Svezia', 'Norvegia', 'Finlandia', 'Irlanda', 'Regno Unito',
        'Polonia', 'Cechia', 'Slovacchia', 'Ungheria', 'Slovenia', 'Croazia', 'Grecia',
    ];

    protected function OnLoad(): void
    {
        if (!$this->IsPostBack)
            $this->Filter();
    }

    /** L'handler dell'AutoPostBack: lo stesso che chiamerebbe un bottone "cerca". */
    protected function FilterChanged(): void
    {
        $this->Postbacks++;

        $this->Filter();
    }

    private function Filter(): void
    {
        $filtro = mb_strtolower(trim($this->__TextBox_Filter->Text));

        $trovati = array_values(array_filter(self::COUNTRIES,
            static fn(string $paese): bool => $filtro === '' || str_contains(mb_strtolower($paese), $filtro)));

        $this->__Literal_Countries->Text = $trovati === []
            ? 'Nessun paese contiene "' . $filtro . '".'
            : implode(' · ', $trovati);

        $this->__Literal_Postbacks->Text = (string)$this->Postbacks;
    }

    protected function ReadClick(): void
    {
        //la password si legge e si usa, ma non si rimostra: il controllo non la rimette mai nel markup
        $this->__Literal_Read->Text = 'una riga: "' . $this->__TextBox_Single->Text . '" — '
            . 'piu\' righe: ' . substr_count($this->__TextBox_Multi->Text, "\n") + 1 . ' riga/e — '
            . 'password: ' . mb_strlen($this->__TextBox_Secret->Text) . ' caratteri, non li rimostro — '
            . 'email: "' . $this->__TextBox_Email->Text . '"';
    }

    protected function ToggleClick(): void
    {
        foreach ([$this->__TextBox_Single, $this->__TextBox_Multi, $this->__TextBox_Secret, $this->__TextBox_Email] as $casella)
            $casella->Enabled = !$casella->Enabled;

        $this->Alert->Success($this->__TextBox_Single->Enabled ? 'Caselle abilitate.' : 'Caselle disabilitate: il disabled non e\' una difesa, il server ricontrolla.');
    }
}
