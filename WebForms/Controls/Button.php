<?php
declare(strict_types=1);

namespace Common\WebForms\Controls;

use Common\WebForms\Control;

class Button extends Control
{
    public string $Text = '';

    public bool $Enabled = true;

    public string $OnClick = '';

    /** Argomento fisso passato all'handler: serve quando lo stesso metodo copre piu' bottoni. */
    public string $CommandArgument = '';

    /** Domanda di conferma prima del postback. Cortesia verso l'utente, non una difesa. */
    public string $Confirm = '';

    protected function ViewStateProperties(): array
    {
        return array_merge(parent::ViewStateProperties(), ['Text', 'Enabled', 'OnClick', 'CommandArgument', 'Confirm']);
    }

    public function RaisePostBackEvent(string $evento, string $argomento): void
    {
        if ($evento !== 'click' || $this->OnClick === '')
            return;

        //il disabled dell'HTML si toglie dalla console: la condizione va verificata dove
        //conta davvero, cioe' qui
        if (!$this->Enabled)
            return;

        $this->Page->InvokeHandler($this->OnClick, $this, $argomento !== '' ? $argomento : $this->CommandArgument);
    }

    public function Render(): string
    {
        $html = '<button type="button"' . $this->RenderAttributes() . $this->PostBackAttribute('click');

        if ($this->CommandArgument !== '')
            $html .= ' data-dw-arg="' . self::HtmlEncode($this->CommandArgument) . '"';

        if ($this->Confirm !== '')
            $html .= ' data-dw-confirm="' . self::HtmlEncode($this->Confirm) . '"';

        if (!$this->Enabled)
            $html .= ' disabled';

        return $html . '>' . self::HtmlEncode($this->Text) . '</button>';
    }
}
