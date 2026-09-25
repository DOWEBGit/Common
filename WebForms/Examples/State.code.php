<?php
declare(strict_types=1);

namespace Common\WebForms\Examples;

use Common\WebForms\Page;
use Common\WebForms\Portable;

class StateExample extends Page
{
    use StateDesigner;

    /** Nessun attributo: una variabile di pagina resta da sola, come il campo di una form. */
    public int $Counter = 0;

    /**
     * Il nome che viaggia fra le pagine.
     *
     * La chiave e' il NOME della proprieta': una #[Portable] che si chiama "CarriedName" anche
     * sulla pagina degli eventi e' la stessa cosa. Non c'e' niente da passare in querystring.
     */
    #[Portable]
    public string $CarriedName = '';

    protected function CountClick(): void
    {
        $this->Counter++;
    }

    protected function ReadClick(): void
    {
        $this->__Literal_Note->Text = $this->__TextBox_Note->Text === ''
            ? 'la casella e\' vuota'
            : 'il server ha letto: "' . $this->__TextBox_Note->Text . '"';
    }

    protected function CarryClick(): void
    {
        $this->CarriedName = $this->__TextBox_Name->Text;

        $this->Alert->Success($this->CarriedName === ''
            ? 'Portato via il nome: adesso non c\'e\' niente.'
            : '"' . $this->CarriedName . '" viaggia con te: vai sulla pagina degli eventi.');
    }

    protected function OnPreRender(): void
    {
        $this->__Literal_Counter->Text = (string)$this->Counter;
        $this->__Literal_Carried->Text = $this->CarriedName === '' ? '(niente)' : $this->CarriedName;
        $this->__TextBox_Name->Text    = $this->CarriedName;
    }
}
