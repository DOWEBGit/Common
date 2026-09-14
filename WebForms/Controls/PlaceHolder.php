<?php
declare(strict_types=1);

namespace Common\WebForms\Controls;

use Common\WebForms\Control;

/**
 * Non rende niente di suo: e' il punto del markup dove il codebehind attacca i controlli
 * che crea a runtime. Come l'<asp:PlaceHolder> di WebForms.
 */
class PlaceHolder extends Control
{
    public function Render(): string
    {
        return $this->Visible ? $this->RenderChildren() : '';
    }
}
