<?php
declare(strict_types=1);

namespace Common\WebForms\Controls;

/**
 * Un foglio di stile, con in coda la marca temporale del file.
 *
 *     <dw:Stylesheet src="Layouts/Sito.css" />
 *
 * rende
 *
 *     <link rel="stylesheet" href="/public/php/Layouts/Sito.css?v=1789041657">
 *
 * Il perche' della marca temporale, e come si scrive il percorso, stanno in StaticResource.
 */
class Stylesheet extends StaticResource
{
    /** Per un foglio che vale solo alla stampa: Media="print". Vuoto = tutti i media. */
    public string $Media = '';

    protected function ViewStateProperties(): array
    {
        return array_merge(parent::ViewStateProperties(), ['Media']);
    }

    public function Render(): string
    {
        if (!$this->Visible)
            return '';

        $html = '<link rel="stylesheet" href="' . self::HtmlEncode($this->VersionedUrl()) . '"';

        if ($this->Media !== '')
            $html .= ' media="' . self::HtmlEncode($this->Media) . '"';

        return $html . '>';
    }
}
