<?php
declare(strict_types=1);

namespace Common\WebForms\Examples;

use Common\WebForms\Control;
use Common\WebForms\Controls\Label;
use Common\WebForms\Controls\LinkButton;
use Common\WebForms\Controls\Panel;
use Common\WebForms\Controls\TextBox;
use Common\WebForms\Page;

/**
 * Controlli che non stanno nel markup, e restano lo stesso.
 *
 * In OnLoad si costruisce solo al primo caricamento; negli handler si aggiunge e si toglie.
 * Nessuno ricrea niente ad ogni richiesta: quello che si vede dopo un postback l'ha rimesso
 * lo stato del contenitore, che si e' salvato i figli non venuti dal markup.
 */
class DynamicControlsExample extends Page
{
    use DynamicControlsDesigner;

    public int $Postbacks = 0;

    /** Quante etichette ha attaccato il bottone: da' loro un id stabile. */
    public int $Born = 0;

    /** L'ultimo numero dato a una riga. Non torna mai indietro, nemmeno dopo un'eliminazione. */
    public int $LastRow = 0;

    /** Quello che il codice ha letto dalla casella dinamica, per rimostrarlo. */
    public string $Echo = '';

    protected function OnLoad(): void
    {
        if ($this->IsPostBack)
            return;

        //RIQUADRO 1: nascono qui, una volta. Al postback dopo non si ripassa da questo punto
        $box = new TextBox();

        $box->Id          = '__TextBox_Dynamic';
        $box->Placeholder = 'scrivi qui, poi fai un postback';

        $this->__PlaceHolder_Once->Add($box);

        $echo = new Label();

        $echo->Id       = '__Label_Echo';
        $echo->CssClass = 'ex-note';

        $this->__PlaceHolder_Once->Add($echo);
    }

    /** Conta e basta: non tocca niente. Tutto quello che si vede ancora dopo arriva dallo stato. */
    protected function PostbackClick(): void
    {
        $this->Postbacks++;
    }

    /** RIQUADRO 2: un'etichetta attaccata al volo, dentro l'handler. Nessun OnInit la ricrea. */
    protected function AddClick(): void
    {
        $this->Born++;

        $label = new Label();

        $label->Id   = '__Label_Volatile' . $this->Born;
        $label->Text = 'Numero ' . $this->Born . ', nata dentro un handler alle ' . date('H:i:s') . '.';

        $label->Style->Add('display', 'block')->Add('color', '#0f766e');

        $this->__PlaceHolder_Volatile->Add($label);
    }

    // ---------------------------------------------------------------- riquadro 3: le righe

    protected function AddRowClick(): void
    {
        $this->LastRow++;

        $this->__Panel_Rows->Add($this->Row($this->LastRow));
    }

    /**
     * Toglie la riga da cui e' arrivato il click. L'id viene dal CommandArgument, che il SERVER
     * ha scritto nel markup: non si fa fede a niente che arrivi dalla richiesta.
     */
    protected function DeleteClick(Control $sender, string $number): void
    {
        foreach ($this->__Panel_Rows->Controls as $position => $row)
        {
            if ($row->Id !== '__Panel_Row' . $number)
                continue;

            array_splice($this->__Panel_Rows->Controls, $position, 1);

            return;
        }
    }

    /** Una riga: <tr><td>Label</td><td>LinkButton</td></tr>, tutti con id stabili derivati dal numero. */
    private function Row(int $number): Panel
    {
        $row = new Panel();

        $row->Id  = '__Panel_Row' . $number;
        $row->Tag = 'tr';

        $text = new Panel();

        $text->Id  = '__Panel_Text' . $number;
        $text->Tag = 'td';

        $label = new Label();

        $label->Id   = '__Label_Row' . $number;
        $label->Text = 'Riga numero ' . $number . ', nata al click delle ' . date('H:i:s') . ' e mai piu\' ricostruita.';

        $text->Add($label);

        $action = new Panel();

        $action->Id       = '__Panel_Action' . $number;
        $action->Tag      = 'td';
        $action->CssClass = 'ex-action';

        $delete = new LinkButton();

        $delete->Id              = '__LinkButton_Delete' . $number;
        $delete->Text            = 'elimina';
        $delete->OnClick         = 'DeleteClick';
        $delete->CommandArgument = (string)$number;

        $delete->Attributes->Add('title', 'Elimina la riga ' . $number);

        $action->Add($delete);

        $row->Add($text);
        $row->Add($action);

        return $row;
    }

    /** Di un controllo costruito dal codice funziona TUTTO: il valore digitato passa da LoadPostData. */
    protected function OnPreRender(): void
    {
        /** @var TextBox $box */
        $box = $this->FindControl('__TextBox_Dynamic');

        if ($box->Text !== '')
            $this->Echo = $box->Text;

        /** @var Label $echo */
        $echo = $this->FindControl('__Label_Echo');

        $echo->Text = $this->Echo === ''
            ? 'La casella qui sopra e\' vuota: scrivici qualcosa e fai un postback.'
            : 'Il motore ha riletto dalla casella dinamica: "' . $this->Echo . '"';

        $this->__Literal_Postbacks->Text = (string)$this->Postbacks;
        $this->__Literal_Labels->Text    = (string)count($this->__PlaceHolder_Volatile->Controls);
        $this->__Literal_Rows->Text      = (string)count($this->__Panel_Rows->Controls);
    }
}
