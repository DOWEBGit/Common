<?php
declare(strict_types=1);

namespace Common\WebForms\Controls;

use Common\WebForms\Control;

class Label extends Control
{
    public string $Text = '';

    /** Il testo esce sempre escapato. Per l'HTML vero si alza questo flag, consapevolmente. */
    public bool $Html = false;

    protected function ViewStateProperties(): array
    {
        return array_merge(parent::ViewStateProperties(), ['Text', 'Html']);
    }

    public function Render(): string
    {
        return '<span' . $this->RenderAttributes() . '>'
            . ($this->Html ? $this->Text : self::HtmlEncode($this->Text))
            . '</span>';
    }
}
