<?php
declare(strict_types=1);

namespace Common\WebForms\Controls;

use Common\WebForms\Control;

/**
 * Testo senza un elemento attorno.
 *
 * E' il controllo piu' usato di tutti nelle pagine WK - quasi mille occorrenze - e si
 * capisce: serve ogni volta che bisogna scrivere un valore dove un <span> darebbe fastidio,
 * dentro una cella gia' stilizzata o in mezzo a una frase.
 *
 * Doppio mestiere: il compilatore lo usa anche per i pezzi di HTML letterale del markup,
 * che passano in PassThrough e non hanno id, quindi non pesano sullo stato.
 */
class Literal extends Control
{
    /** Il testo esce escapato. */
    public const string ENCODE = 'Encode';
    /** Il testo esce cosi' com'e': solo su HTML costruito dal server. */
    public const string PASSTHROUGH = 'PassThrough';
    /** Il testo. Esce escapato, salvo Mode="PassThrough". */
    public string $Text = '';

    /** Encode (predefinito) escapa; PassThrough lascia l'HTML com'e' - solo per markup costruito dal server. */
    public string $Mode = self::ENCODE;

    protected function ViewStateProperties(): array
    {
        return array_merge(parent::ViewStateProperties(), ['Text', 'Mode']);
    }

    public function Render(): string
    {
        if (!$this->Visible)
            return '';

        return $this->Mode === self::PASSTHROUGH ? $this->Text : self::HtmlEncode($this->Text);
    }
}
