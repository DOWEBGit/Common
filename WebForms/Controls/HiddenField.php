<?php
declare(strict_types=1);

namespace Common\WebForms\Controls;

use Common\WebForms\Control;

/**
 * Campo nascosto. Dentro una riga di Repeater serve a portarsi l'identificativo del record,
 * come il __Hidden_Id delle pagine WK.
 *
 * Il valore torna dal client ad ogni postback, quindi e' un dato di cui NON fidarsi per
 * decidere cosa cancellare: va usato per ritrovare la riga, non come autorizzazione. Chi
 * elimina ricarica comunque l'entita' e applica i propri controlli.
 */
class HiddenField extends Control
{
    public string $Value = '';

    protected function ViewStateProperties(): array
    {
        return array_merge(parent::ViewStateProperties(), ['Value']);
    }

    public function LoadPostData(array $post): void
    {
        if (array_key_exists($this->Id, $post))
            $this->Value = (string)$post[$this->Id];
    }

    public function Render(): string
    {
        return '<input type="hidden"' . $this->RenderAttributes()
            . ' name="' . self::HtmlEncode($this->Id) . '"'
            . ' value="' . self::HtmlEncode($this->Value) . '">';
    }
}
