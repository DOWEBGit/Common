<?php
declare(strict_types=1);

namespace Common\WebForms;

/**
 * Gli attributi HTML aggiunti dal codice: l'Attributes di WebForms.
 *
 *     $elimina->Attributes->Add('title', 'Elimina la riga ' . $nome);
 *     $riga->Attributes->Add('data-stato', $ordine->Stato);
 *
 * E' la valvola di sfogo per quello che il motore non prevede: un title che cambia per
 * riga, un data- che serve a un pezzo di JavaScript del sito, un aria- per un caso
 * particolare. Il valore esce escapato, sempre; il nome dev'essere un nome di attributo.
 */
final class AttributeCollection extends NamedCollection
{
    /**
     * Nomi che hanno gia' un padrone: renderli due volte darebbe un HTML con l'attributo
     * ripetuto, e il browser terrebbe il primo - cioe' non quello appena scritto.
     */
    private const array RESERVED = ['id', 'class', 'hidden', 'name', 'style'];
    protected function CheckName(string $name): void
    {
        if (preg_match('/^[A-Za-z][A-Za-z0-9_.:-]*$/', $name) !== 1)
            throw new \RuntimeException('"' . $name . '" non e\' un nome di attributo.');

        if (in_array(strtolower($name), self::RESERVED, true))
            throw new \RuntimeException(
                'L\'attributo "' . $name . '" lo scrive il controllo: usa Id, CssClass, Style o Visible.'
            );

        //data-dw-* e' il canale fra il server e il runtime: sovrascriverlo non aggiunge un
        //attributo, cambia il modo in cui il client interpreta il controllo
        if (str_starts_with(strtolower($name), 'data-dw-'))
            throw new \RuntimeException('"' . $name . '" appartiene al motore.');
    }
}
