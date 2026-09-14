<?php
declare(strict_types=1);

namespace Common\WebForms\Controls;

use Common\WebForms\Control;

/**
 * Elenco a piu' righe, con selezione singola o multipla.
 *
 * Nella selezione multipla il campo si chiama "id[]": senza le parentesi PHP terrebbe solo
 * l'ultimo valore inviato, e la selezione multipla non funzionerebbe mai piu' di una voce.
 */
class ListBox extends Control
{
    public const string SINGLE = 'Single';
    public const string MULTIPLE = 'Multiple';
    /** @var array<int|string,string> valore => testo. Una chiave numerica PHP la fa int: si confronta come testo */
    public array $Items = [];

    /** La prima voce scelta; con la selezione multipla si usa SelectedValues. */
    public string $SelectedValue = '';

    /** @var string[] usato quando SelectionMode e' Multiple */
    public array $SelectedValues = [];

    /** Single (predefinito) o Multiple: con Multiple il campo si chiama id[] e SelectedValues porta tutte le scelte. */
    public string $SelectionMode = self::SINGLE;

    /** Le righe visibili dell'elenco. */
    public int $Rows = 6;

    /** Spento rende disabled; il server ricontrolla comunque. */
    public bool $Enabled = true;

    /** Cambiare la scelta e' un postback: OnSelectedIndexChanged gira subito. */
    public bool $AutoPostBack = false;

    /** Il nome del metodo del codebehind che gira quando cambia la scelta, con AutoPostBack. */
    public string $OnSelectedIndexChanged = '';

    /** Alternativa a OnSelectedIndexChanged: un comando che sale verso il contenitore, per gli UserControl. */
    public string $CommandName = '';

    protected function ViewStateProperties(): array
    {
        return array_merge(
            parent::ViewStateProperties(),
            ['Items', 'SelectedValue', 'SelectedValues', 'SelectionMode', 'Rows',
             'Enabled', 'AutoPostBack', 'OnSelectedIndexChanged', 'CommandName']
        );
    }

    private function Multipla(): bool
    {
        return $this->SelectionMode === self::MULTIPLE;
    }

    public function LoadPostData(array $post): void
    {
        if (!array_key_exists($this->Id, $post))
        {
            //un elenco multiplo senza niente di selezionato non compare fra i campi inviati:
            //l'assenza vale "nessuna selezione", non "lascia com'era"
            if ($this->Multipla() && array_key_exists($this->Id . '__presente', $post))
                $this->SelectedValues = [];

            return;
        }

        $valore = $post[$this->Id];

        if (!$this->Multipla())
        {
            //si accetta solo una voce che il server ha davvero reso, altrimenti la selezione
            //diventa un campo di testo libero
            if (array_key_exists((string)$valore, $this->Items))
                $this->SelectedValue = (string)$valore;

            return;
        }

        $scelti = [];

        foreach ((array)$valore as $uno)
            if (array_key_exists((string)$uno, $this->Items))
                $scelti[] = (string)$uno;

        $this->SelectedValues = $scelti;
    }

    public function RaisePostBackEvent(string $evento, string $argomento): void
    {
        if ($evento !== 'change')
            return;

        if ($this->OnSelectedIndexChanged !== '')
        {
            $this->Page->InvokeHandler($this->OnSelectedIndexChanged, $this, $argomento);
            return;
        }

        if ($this->CommandName !== '')
            $this->RaiseBubbleEvent($this, $this->CommandName, $this->SelectedValue);
    }

    public function Render(): string
    {
        $multipla = $this->Multipla();

        $html = '';

        if ($multipla)
            $html .= '<input type="hidden" name="' . self::HtmlEncode($this->Id) . '__presente" value="1">';

        $html .= '<select' . $this->RenderAttributes()
            . ' name="' . self::HtmlEncode($this->Id) . ($multipla ? '[]' : '') . '"'
            . ' size="' . max(2, $this->Rows) . '"';

        if ($multipla)
            $html .= ' multiple';

        if (!$this->Enabled)
            $html .= ' disabled';

        if ($this->AutoPostBack)
            $html .= $this->PostBackAttribute('change');

        $html .= '>';

        foreach ($this->Items as $valore => $testo)
        {
            $valore = (string)$valore;

            $scelto = $multipla
                ? in_array($valore, $this->SelectedValues, true)
                : $valore === $this->SelectedValue;

            $html .= '<option value="' . self::HtmlEncode($valore) . '"'
                . ($scelto ? ' selected' : '') . '>'
                . self::HtmlEncode($testo) . '</option>';
        }

        return $html . '</select>';
    }
}
