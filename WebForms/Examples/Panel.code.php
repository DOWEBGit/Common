<?php
declare(strict_types=1);

namespace Common\WebForms\Examples;

use Common\WebForms\Controls\Label;
use Common\WebForms\Page;

class PanelExample extends Page
{
    use PanelDesigner;

    /** Quante etichette sono nate: da' loro un id stabile, che non torna mai indietro. */
    public int $Born = 0;

    protected function ToggleClick(): void
    {
        $this->__Panel_Box->Visible = !$this->__Panel_Box->Visible;
    }

    protected function AddClick(): void
    {
        $this->Born++;

        $label = new Label();

        //l'id dev'essere STABILE: e' la chiave con cui lo stato lo ritrova al postback dopo
        $label->Id   = '__Label_Added' . $this->Born;
        $label->Text = 'Etichetta numero ' . $this->Born . ', nata alle ' . date('H:i:s') . ' dentro un handler.';

        $label->Style->Add('display', 'block')->Add('color', '#0f766e');

        $this->__PlaceHolder_Labels->Add($label);
    }

    protected function ClearClick(): void
    {
        //via tutte: lo stato del PlaceHolder torna a niente, non resta nulla appeso
        $this->__PlaceHolder_Labels->Controls = [];
    }

    protected function OnPreRender(): void
    {
        $this->__Literal_Count->Text = (string)count($this->__PlaceHolder_Labels->Controls);
    }
}
