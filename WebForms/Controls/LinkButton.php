<?php
declare(strict_types=1);

namespace Common\WebForms\Controls;

use Common\WebForms\Control;

/**
 * Un link che scatena un evento invece di navigare.
 *
 * Resta un <a> vero - raggiungibile da tastiera, con il cursore giusto - ma il click viene
 * fermato dal runtime e trasformato in postback. L'intercettazione della navigazione senza
 * ricarico lo lascia passare perche' controlla se qualcuno ha gia' consumato il click.
 */
class LinkButton extends Control
{
    /** Il testo del link. */
    public string $Text = '';

    /** Spento degrada a <span>, non a un link morto. Il server ricontrolla comunque. */
    public bool $Enabled = true;

    /** Il nome del metodo del codebehind che gira al click: (Control $sender, string $argument). */
    public string $OnClick = '';

    /** Cosa ha cliccato l'utente: qui ci finisce il nome della colonna, l'id della riga, ... */
    public string $CommandArgument = '';

    /**
     * Alternativa a OnClick: invece di chiamare un metodo della pagina per nome, manda un
     * comando verso l'alto e lo raccoglie il contenitore. E' cosi' che i bottoni dentro un
     * UserControl restano ignari di chi li ospita, e il controllo si riusa altrove.
     */
    public string $CommandName = '';

    /**
     * Domanda di conferma prima del postback. Vuota = nessuna conferma.
     *
     * E' cortesia verso l'utente, non una difesa: si toglie dalla console, quindi
     * l'handler deve comunque comportarsi come se il click fosse arrivato senza.
     */
    public string $Confirm = '';

    protected function ViewStateProperties(): array
    {
        return array_merge(
            parent::ViewStateProperties(),
            ['Text', 'Enabled', 'OnClick', 'CommandName', 'CommandArgument', 'Confirm']
        );
    }

    public function RaisePostBackEvent(string $evento, string $argomento): void
    {
        if ($evento !== 'click' || !$this->Enabled)
            return;

        //l'argomento buono e' quello che il SERVER ha reso nel markup, non quello che
        //arriva dalla richiesta: e' l'unico dei due di cui ci si possa fidare
        $argument = $this->CommandArgument !== '' ? $this->CommandArgument : $argomento;

        if ($this->OnClick !== '')
        {
            $this->Page->InvokeHandler($this->OnClick, $this, $argument);
            return;
        }

        if ($this->CommandName !== '')
            $this->RaiseBubbleEvent($this, $this->CommandName, $argument);
    }

    public function Render(): string
    {
        if (!$this->Enabled)
            return '<span' . $this->RenderAttributes() . '>' . self::HtmlEncode($this->Text) . '</span>';

        $html = '<a href="#"' . $this->RenderAttributes() . $this->PostBackAttribute('click')
            . ' data-dw-arg="' . self::HtmlEncode($this->CommandArgument) . '"';

        if ($this->Confirm !== '')
            $html .= ' data-dw-confirm="' . self::HtmlEncode($this->Confirm) . '"';

        return $html . '>' . self::HtmlEncode($this->Text) . '</a>';
    }
}
