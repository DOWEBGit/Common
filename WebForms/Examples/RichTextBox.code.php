<?php
declare(strict_types=1);

namespace Common\WebForms\Examples;

use Common\WebForms\Control;
use Common\WebForms\Controls\Literal;
use Common\WebForms\Controls\Repeater;
use Common\WebForms\Controls\RepeaterItem;
use Common\WebForms\Controls\RichTextBox;
use Common\WebForms\Page;

class RichTextBoxExample extends Page
{
    use RichTextBoxDesigner;

    /**
     * Il "database" delle note: id => HTML salvato. E' una variabile della pagina, quindi resta
     * fra un click e l'altro come ogni variabile del motore.
     *
     * @var array<int,string>
     */
    public array $Notes = [];

    protected function OnLoad(): void
    {
        if ($this->IsPostBack)
            return;

        $this->__ListBox_Features->Items = array_combine(RichTextBox::FEATURES, RichTextBox::FEATURES);
        $this->__ListBox_Features->SelectedValues = array_values(array_filter(RichTextBox::FEATURES, $this->__RichTextBox_Partial->IsEnabled(...)));

        $this->__RichTextBox_Live->EnableOnly('Bold', 'Italic', 'Underline', 'Link');

        $this->SampleClick();

        $this->Notes = [
            1 => '<p>Prima nota, <strong>gia\' salvata</strong>.</p>',
            2 => '<ul><li>una voce</li><li>un\'altra</li></ul>',
        ];

        $this->BindNotes();
    }

    // ------------------------------------------------------------ l'editor completo

    protected function ShowClick(): void
    {
        //Text e' gia' ripulito: e' esattamente quello che andrebbe nel database
        $this->__Literal_Html->Mode = Literal::PASSTHROUGH;
        $this->__Literal_Html->Text = '<pre class="ex-db">' . Control::HtmlEncode($this->__RichTextBox_Full->Text === '' ? '(vuoto)' : $this->__RichTextBox_Full->Text) . '</pre>'
            . '<p class="ex-note">Come testo: ' . Control::HtmlEncode($this->__RichTextBox_Full->PlainText()) . '</p>';
    }

    protected function SampleClick(): void
    {
        $this->__RichTextBox_Full->Text =
            '<h2>Un titolo</h2>'
            . '<p style="line-height:1.5">Del testo con <strong>grassetto</strong>, <em>corsivo</em>, <u>sottolineato</u>, '
            . '<span style="color:#cc0000">rosso</span>, <span style="background-color:#fff2cc">evidenziato</span> e un '
            . '<a href="https://doweb.it" target="_blank">link</a>.</p>'
            . '<ol><li>primo</li><li>secondo<ul><li>dentro</li></ul></li></ol>'
            . '<blockquote><p>Una citazione.</p></blockquote>'
            . '<table><tbody><tr><th>Prodotto</th><th>Prezzo</th></tr><tr><td>Mela</td><td>1,20</td></tr></tbody></table>';
    }

    /** Quello che scriverebbe un attaccante dalla console: si vede cosa ne resta. */
    protected function DirtyClick(): void
    {
        $sporco = '<p onclick="alert(1)">Ciao <img src=x onerror="alert(2)"><a href="java&#9;script:alert(3)">clic</a>'
            . '<span style="background:url(javascript:alert(4));color:red">rosso</span><script>alert(5)</script></p>';

        $this->__RichTextBox_Full->LoadPostData([$this->__RichTextBox_Full->Id => $sporco]);

        $this->__Literal_Html->Mode = Literal::PASSTHROUGH;
        $this->__Literal_Html->Text = '<p class="ex-note">Arrivato:</p><pre class="ex-db">' . Control::HtmlEncode($sporco) . '</pre>'
            . '<p class="ex-note">Rimasto:</p><pre class="ex-db">' . Control::HtmlEncode($this->__RichTextBox_Full->Text) . '</pre>';
    }

    // ------------------------------------------------------------ le funzioni una per una

    protected function ApplyClick(): void
    {
        $this->__RichTextBox_Partial->EnableOnly(...$this->__ListBox_Features->SelectedValues);

        $this->Alert->Success('Accese: ' . ($this->__ListBox_Features->SelectedValues === [] ? 'nessuna' : implode(', ', $this->__ListBox_Features->SelectedValues)));
    }

    protected function AllClick(): void
    {
        $this->__RichTextBox_Partial->SetAll(true);
        $this->__ListBox_Features->SelectedValues = RichTextBox::FEATURES;
    }

    // ------------------------------------------------------------ le note: un editor per riga

    private function BindNotes(): void
    {
        $this->__Repeater_Notes->DataSource = array_map(static fn(int $id): array => ['Id' => $id], array_keys($this->Notes));
        $this->__Repeater_Notes->DataBind();
    }

    protected function NoteBound(Repeater $sender, RepeaterItem $riga): void
    {
        $riga->FindControl('__RichTextBox_Note')->Text = $this->Notes[(int)$riga->DataItem['Id']] ?? '';
    }

    /** Il Salva di una riga: dal bottone alla sua riga, e dalla riga al suo editor. */
    protected function SaveNoteClick(Control $sender): void
    {
        $id = $this->SaveRow($sender->NamingContainer());

        $this->Alert->Success('Nota ' . $id . ' salvata.');
    }

    protected function RemoveNoteClick(Control $sender): void
    {
        $scritti = $this->Written();

        unset($this->Notes[(int)$sender->NamingContainer()->FindControl('__Hidden_Id')->Value]);

        $this->Rebind($scritti);
    }

    /**
     * Una nota nuova, vuota. Rilegare rifarebbe ogni riga dal database e perderebbe quello che
     * l'utente ha scritto e non salvato: lo si mette da parte prima, e lo si rimette dopo.
     */
    protected function AddNoteClick(): void
    {
        $scritti = $this->Written();

        $this->Notes[($this->Notes === [] ? 0 : max(array_keys($this->Notes))) + 1] = '';

        $this->Rebind($scritti);
    }

    protected function SaveAllClick(): void
    {
        $salvate = 0;

        foreach ($this->__Repeater_Notes->Items() as $riga)
        {
            $this->SaveRow($riga);
            $salvate++;
        }

        $this->Alert->Success($salvate . ($salvate === 1 ? ' nota salvata.' : ' note salvate.'));
    }

    private function SaveRow(Control $riga): int
    {
        $id = (int)$riga->FindControl('__Hidden_Id')->Value;

        //Text e' gia' ripulito dal controllo, con le regole delle sue funzioni
        $this->Notes[$id] = $riga->FindControl('__RichTextBox_Note')->Text;

        return $id;
    }

    /** @return array<int,string> quello che c'e' negli editor adesso, per id */
    private function Written(): array
    {
        $scritti = [];

        foreach ($this->__Repeater_Notes->Items() as $riga)
            $scritti[(int)$riga->FindControl('__Hidden_Id')->Value] = $riga->FindControl('__RichTextBox_Note')->Text;

        return $scritti;
    }

    /** @param array<int,string> $scritti */
    private function Rebind(array $scritti): void
    {
        $this->BindNotes();

        foreach ($this->__Repeater_Notes->Items() as $riga)
        {
            $id = (int)$riga->FindControl('__Hidden_Id')->Value;

            if (array_key_exists($id, $scritti))
                $riga->FindControl('__RichTextBox_Note')->Text = $scritti[$id];
        }
    }

    // ------------------------------------------------------------ l'anteprima

    protected function LiveChanged(RichTextBox $sender): void
    {
        $this->__Literal_Live->Text = $sender->PlainText() === '' ? '(vuoto)' : $sender->PlainText();
    }

    protected function OnPreRender(): void
    {
        //accanto a ogni nota: salvata o no, confrontando l'editor col "database"
        foreach ($this->__Repeater_Notes->Items() as $riga)
        {
            $id = (int)$riga->FindControl('__Hidden_Id')->Value;

            $riga->FindControl('__Literal_Saved')->Mode = Literal::PASSTHROUGH;
            $riga->FindControl('__Literal_Saved')->Text = ($this->Notes[$id] ?? null) === $riga->FindControl('__RichTextBox_Note')->Text
                ? '<span class="ex-ok">salvata</span>'
                : '<span class="ex-ko">da salvare</span>';
        }

        $db = '';

        foreach ($this->Notes as $id => $html)
            $db .= $id . ' => ' . ($html === '' ? '(vuota)' : $html) . "\n";

        $this->__Literal_Database->Mode = Literal::PASSTHROUGH;
        $this->__Literal_Database->Text = '<pre class="ex-db">' . Control::HtmlEncode($db === '' ? '(nessuna nota)' : $db) . '</pre>';
    }
}
