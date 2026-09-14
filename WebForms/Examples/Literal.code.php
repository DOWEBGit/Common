<?php
declare(strict_types=1);

namespace Common\WebForms\Examples;

use Common\WebForms\Control;
use Common\WebForms\Page;

class LiteralExample extends Page
{
    use LiteralDesigner;

    protected function OnLoad(): void
    {
        if (!$this->IsPostBack)
            $this->ShowClick();
    }

    protected function ShowClick(): void
    {
        $testo = $this->__TextBox_Input->Text;

        //Encode: il controllo escapa lui
        $this->__Literal_Encoded->Text = $testo;

        //PassThrough: l'HTML e' del server, e il testo dell'utente si escapa PRIMA di metterlo dentro
        $this->__Literal_Raw->Text = '<span style="background:#fef3c7;padding:2px 8px;border-radius:4px">'
            . Control::HtmlEncode($testo) . '</span> <small class="ex-note">(' . mb_strlen($testo) . ' caratteri)</small>';
    }
}
