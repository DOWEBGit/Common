<?php
declare(strict_types=1);

namespace Common\WebForms\ProveAMano;

use Common\WebForms\Control;
use Common\WebForms\Controls\Label;
use Common\WebForms\Controls\LinkButton;
use Common\WebForms\Controls\Panel;
use Common\WebForms\Page;

/**
 * Righe di tabella costruite a mano, e nient'altro.
 *
 * In OnLoad NON si fa niente: nessun DataBind, nessun elenco, nessuna riga ricreata. Ogni
 * <tr> nasce da un click e da quel momento in poi vive nello stato: al postback dopo torna
 * indietro con dentro il suo testo e il suo bottone, e il bottone funziona ancora.
 *
 * E' la prova piu' severa che si possa fare al ViewState di questo motore, perche' mette
 * insieme tutte le cose che di solito si rompono una alla volta:
 *
 *   - un albero dinamico ANNIDATO - tr dentro tbody, td dentro tr, controlli dentro td -
 *     quindi la ricostruzione dev'essere ricorsiva e non solo di primo livello;
 *   - un controllo che scatena EVENTI, creato dal codice: quando il click arriva, il
 *     LinkButton dev'esserci gia', o l'evento cade nel vuoto;
 *   - la RIMOZIONE: una riga tolta deve restare tolta, e le altre non devono spostarsi ne'
 *     rinumerarsi;
 *   - il TAG giusto: un <div> dentro una <table> il browser lo butta fuori dalla tabella, e
 *     il morph poi non ritrova piu' niente al suo posto. Per questo sono Panel con Tag.
 *
 * Il numero di riga non e' la posizione: e' un contatore che non torna mai indietro. Se fosse
 * la posizione, eliminando la seconda di tre righe la terza diventerebbe la seconda, e lo
 * stato della riga eliminata si poserebbe su quella sbagliata.
 */
class Tabella extends Page
{
    use TabellaDesigner;

    /** L'ultimo numero dato a una riga. Non torna mai indietro, nemmeno dopo un'eliminazione. */
    public int $Ultima = 0;

    public int $Click = 0;

    protected function OnInit(): void
    {
        $this->Master->SetTitle('Righe aggiunte a mano', 'In OnLoad non succede niente: ogni riga nasce da un click e vive nello stato.');
    }

    /**
     * Qui non si costruisce niente, ed e' il punto.
     *
     * Le righe non vengono da un DataBind e non vengono ricreate: quelle che si vedono dopo
     * un postback le ha rimesse il ViewState, non questo metodo.
     */
    protected function OnLoad(): void
    {
    }

    protected function AggiungiClick(): void
    {
        $this->Ultima++;

        $this->__Panel_Righe->Add($this->Riga($this->Ultima));
    }

    /** Un postback qualunque, che non tocca la tabella: serve solo a vedere se regge. */
    protected function NienteClick(): void
    {
        $this->Click++;
    }

    /**
     * Toglie la riga da cui e' arrivato il click.
     *
     * L'id della riga viene dal CommandArgument, che il SERVER ha scritto nel markup: non si
     * fa fede a niente che arrivi dalla richiesta. Si potrebbe anche risalire da
     * $sender->Parent->Parent, ma il numero in chiaro rende evidente su cosa si sta lavorando.
     */
    protected function EliminaClick(Control $sender, string $numero): void
    {
        foreach ($this->__Panel_Righe->Controls as $posizione => $riga)
        {
            if ($riga->Id !== '__Panel_Riga' . $numero)
                continue;

            array_splice($this->__Panel_Righe->Controls, $posizione, 1);

            return;
        }
    }

    /**
     * Una riga: <tr><td>Literal</td><td class="pm-azione">LinkButton</td></tr>.
     *
     * Ogni pezzo ha il suo id, e gli id sono stabili perche' derivano dal numero della riga:
     * e' con quelli che lo stato li ritrova al postback dopo.
     */
    private function Riga(int $numero): Panel
    {
        $riga = new Panel();

        $riga->Id  = '__Panel_Riga' . $numero;
        $riga->Tag = 'tr';

        $riga->Add($this->CellaTesto($numero));
        $riga->Add($this->CellaElimina($numero));

        return $riga;
    }

    private function CellaTesto(int $numero): Panel
    {
        $cella = new Panel();

        $cella->Id  = '__Panel_Testo' . $numero;
        $cella->Tag = 'td';

        $testo = new Label();

        $testo->Id   = '__Literal_Riga' . $numero;
        $testo->Text = 'Riga numero ' . $numero . ', nata al click delle '
            . date('H:i:s') . ' e mai piu\' ricostruita.';

        $cella->Add($testo);

        return $cella;
    }

    private function CellaElimina(int $numero): Panel
    {
        $cella = new Panel();

        $cella->Id       = '__Panel_Azione' . $numero;
        $cella->Tag      = 'td';
        $cella->CssClass = 'pm-azione';

        $elimina = new LinkButton();

        $elimina->Id              = '__LinkButton_Elimina' . $numero;
        $elimina->Text            = 'elimina';
        $elimina->OnClick         = 'EliminaClick';
        $elimina->CommandArgument = (string)$numero;

        $elimina->Attributes->Add('title', 'Elimina la riga ' . $numero);

        $cella->Add($elimina);

        return $cella;
    }

    /**
     * L'interruttore del "modo WinForms".
     *
     * Si decide qui e non in OnInit perche' la casella e' un controllo della pagina: il suo
     * valore arriva col postback, e va letto dopo. Il render viene dopo ancora, quindi
     * l'attributo sulla radice esce gia' giusto.
     */
    protected function OnPreRender(): void
    {
        $this->KeepState = $this->__CheckBox_Tieni->Checked;

        $this->__Literal_Quante->Text = (string)count($this->__Panel_Righe->Controls);
        $this->__Literal_Click->Text  = (string)$this->Click;
    }
}
