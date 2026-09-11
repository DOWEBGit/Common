<?php
declare(strict_types=1);

namespace Common\WebForms;

/**
 * Lo stile in linea: la CssStyleCollection di WebForms.
 *
 *     $riga->Style->Add('background-color', $colore);
 *     $barra->Style->Add('width', $percentuale . '%');
 *     $riga->Style->Remove('background-color');
 *
 * Serve quando il valore E' il dato: una barra lunga quanto una percentuale, una riga
 * colorata secondo lo stato. Tutto il resto - com'e' fatto un bottone, che aspetto ha una
 * scheda - e' vestito, e il vestito sta nel foglio di stile: una regola scritta li' si cambia
 * per tutti insieme, uno stile in linea si cambia riga per riga e vince su tutto.
 *
 * Esce in un attributo solo, nell'ordine in cui lo si e' scritto, escapato tutto insieme.
 */
final class CssStyleCollection extends NamedCollection
{
    /** Un nome di proprieta' CSS: lettere, cifre e trattini, con le due iniziali delle custom. */
    protected function CheckName(string $name): void
    {
        if (preg_match('/^-{0,2}[A-Za-z][A-Za-z0-9-]*$/', $name) !== 1)
            throw new \RuntimeException('"' . $name . '" non e\' una proprieta\' CSS.');
    }

    /** Il valore dell'attributo style, senza escape: lo fa chi lo scrive nel tag. */
    public function ToCss(): string
    {
        $pezzi = [];

        foreach ($this as $proprieta => $valore)
            $pezzi[] = $proprieta . ':' . $valore;

        return implode(';', $pezzi);
    }
}
