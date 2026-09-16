<?php
declare(strict_types=1);

namespace Common\WebForms\Controls;

use Common\WebForms\Control;

/** Contenitore: raggruppa e permette di nascondere un pezzo di pagina in un colpo solo. */
class Panel extends Control
{
    /** L'elemento reso: div, o quello che serve - tbody, tr, td, span, section. Un div dentro una table il browser lo butta fuori. */
    public string $Tag = 'div';

    protected function ViewStateProperties(): array
    {
        return array_merge(parent::ViewStateProperties(), ['Tag']);
    }

    public function Render(): string
    {
        //il tag finisce nell'HTML: si accetta solo un nome di elemento, mai quello che
        //capita di trovare nello stato
        $tag = preg_match('/^[a-z][a-z0-9]*$/', $this->Tag) === 1 ? $this->Tag : 'div';

        return '<' . $tag . $this->RenderAttributes() . '>' . $this->RenderChildren() . '</' . $tag . '>';
    }
}
