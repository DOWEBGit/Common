<?php
declare(strict_types=1);

namespace Common\WebForms\Controls;

use Common\WebForms\Control;

class CheckBox extends Control
{
    /** Spuntata o no. Torna dal browser a ogni postback. */
    public bool $Checked = false;

    /** L'etichetta accanto alla casella: e' una <label>, quindi si clicca anche sul testo. */
    public string $Text = '';

    /** Spento rende disabled; il server ricontrolla comunque. */
    public bool $Enabled = true;

    /** Il click sulla casella e' un postback: OnCheckedChanged gira subito. */
    public bool $AutoPostBack = false;

    /** Il nome del metodo del codebehind che gira quando cambia, con AutoPostBack. */
    public string $OnCheckedChanged = '';

    protected function ViewStateProperties(): array
    {
        return array_merge(
            parent::ViewStateProperties(),
            ['Checked', 'Text', 'Enabled', 'AutoPostBack', 'OnCheckedChanged']
        );
    }

    /**
     * Una casella non spuntata non compare fra i campi inviati: l'assenza vale false, ma va
     * distinta dal "campo assente perche' il controllo non era in pagina". Il marcatore
     * nascosto reso insieme alla casella dice che il controllo c'era davvero.
     */
    public function LoadPostData(array $post): void
    {
        if (!array_key_exists($this->Id . '__presente', $post))
            return;

        $this->Checked = array_key_exists($this->Id, $post);
    }

    public function RaisePostBackEvent(string $evento, string $argomento): void
    {
        if ($evento === 'change' && $this->OnCheckedChanged !== '')
            $this->Page->InvokeHandler($this->OnCheckedChanged, $this, $argomento);
    }

    public function Render(): string
    {
        $html = '<label' . $this->RenderAttributes() . '>'
            . '<input type="hidden" name="' . self::HtmlEncode($this->Id) . '__presente" value="1">'
            . '<input type="checkbox" name="' . self::HtmlEncode($this->Id) . '" value="1"';

        if ($this->Checked)
            $html .= ' checked';

        if (!$this->Enabled)
            $html .= ' disabled';

        if ($this->AutoPostBack)
            $html .= $this->PostBackAttribute('change');

        return $html . '> ' . self::HtmlEncode($this->Text) . '</label>';
    }
}
