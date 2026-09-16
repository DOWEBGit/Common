<?php
declare(strict_types=1);

namespace Common\WebForms\Examples;

use Common\WebForms\Page;

class ModalPopupExample extends Page
{
    use ModalPopupDesigner;

    /** Dal server: e' Show() che lo apre, con la risposta di questo postback. */
    protected function OpenFromServerClick(): void
    {
        $this->__TextBox_Card->Text = 'aperto dal server alle ' . date('H:i:s');

        $this->__ModalPopup_Card->Show();
    }

    /**
     * Un bottone qualunque dentro il popup: postback normale, e il popup resta aperto finche'
     * il server non dice Hide(). Se il testo e' vuoto non lo dice, e l'avviso compare SOPRA il
     * popup ancora aperto: e' il giro di una scheda che non passa la validazione.
     */
    protected function SaveClick(): void
    {
        if (trim($this->__TextBox_Card->Text) === '')
        {
            $this->Alert->Fail('Scrivi qualcosa prima di salvare: il popup resta aperto.');

            return;
        }

        $this->__Literal_Saved->Text = $this->__TextBox_Card->Text;

        $this->__ModalPopup_Card->Hide();

        $this->Alert->Success('Salvato: il server ha chiuso il popup con Hide().');
    }
}
