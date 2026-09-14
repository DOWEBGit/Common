<?php
declare(strict_types=1);

namespace Common\WebForms\Examples;

use Common\WebForms\Control;
use Common\WebForms\Page;

/** La panoramica: le schede di tutte le pagine, dallo stesso elenco che fa il menu. */
class IndexExample extends Page
{
    use IndexDesigner;

    protected function OnPreRender(): void
    {
        $html = '';

        foreach (Site::PAGES as $gruppo => $voci)
        {
            if ($gruppo === 'Inizio')
                continue;

            $html .= '<h2>' . Control::HtmlEncode($gruppo) . '</h2><div class="ex-cards">';

            foreach ($voci as $file => [$titolo, $riga])
                $html .= '<a class="ex-card" href="' . $file . '.php"><b>' . Control::HtmlEncode($titolo) . '</b>'
                    . '<span>' . Control::HtmlEncode($riga) . '</span></a>';

            $html .= '</div>';
        }

        $this->__Literal_Cards->Text = $html;
    }
}
