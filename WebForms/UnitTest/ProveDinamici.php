<?php
declare(strict_types=1);

namespace Common\WebForms\UnitTest;

use Common\WebForms\Control;
use Common\WebForms\ControlBuilder;
use Common\WebForms\Controls\Label;
use Common\WebForms\Controls\LinkButton;
use Common\WebForms\Controls\Panel;
use Common\WebForms\Controls\PlaceHolder;
use Common\WebForms\Controls\TextBox;
use Common\WebForms\PageParser;

/**
 * I controlli che il codice attacca a runtime, e che lo stato rimette al loro posto.
 *
 * E' il "lo tengo in sessione" di WebForms, senza sessione: il contenitore salva i figli che
 * non vengono dal markup - posizione, classe e stato - e li ricostruisce quando lo stato
 * torna indietro, PRIMA che si legga il form. Quindi funziona tutto, non solo il render: un
 * TextBox creato dal codice riceve quello che l'utente ci ha scritto come uno qualunque.
 *
 * I due confini che questa prova tiene fermi:
 *
 *   - quello che viene dal MARKUP non si salva: si rilegge da solo ad ogni richiesta, e
 *     scriverlo nello stato sarebbe la stessa informazione due volte;
 *   - un controllo che non e' del motore NON si sa ricostruire da un nome di classe, e la
 *     cosa si dice subito con un'eccezione invece di farlo sparire al primo click.
 */
class ProveDinamici
{
    public static function Esegui(Prova $p): void
    {
        $p->Sezione('controlli attaccati dal codice');

        // --- il giro completo: si attacca, si salva, si ricostruisce

        $ph = self::Segnaposto();

        $eti = new Label();

        $eti->Id       = 'lblDinamica';
        $eti->Text     = 'creata a runtime';
        $eti->CssClass = 'nota';

        $eti->Style->Add('color', 'crimson');

        $ph->Add($eti);

        $rifatto = self::Segnaposto();

        $rifatto->LoadViewState($ph->SaveViewState());

        $p->Uguale('il figlio attaccato dal codice torna al postback dopo', 1, count($rifatto->Controls));

        $p->Uguale('con lo stesso id', 'lblDinamica', $rifatto->Controls[0]->Id);

        $p->Contiene('e con tutto il suo stato addosso', 'creata a runtime', $rifatto->Render());
        $p->Contiene('classe compresa', 'class="nota"', $rifatto->Render());
        $p->Contiene('e stile in linea compreso', 'color:crimson', $rifatto->Render());

        $p->Uguale('e si ritrova con FindControl', $rifatto->Controls[0], $rifatto->FindControl('lblDinamica'));

        // --- e sopravvive al giro dopo, e a quello dopo ancora

        $terzo = self::Segnaposto();

        $terzo->LoadViewState($rifatto->SaveViewState());

        $p->Contiene('e al postback successivo, e a quello dopo', 'creata a runtime', $terzo->Render());

        // --- un controllo di input dinamico riceve il POST

        $ph = self::Segnaposto();

        $casella = new TextBox();

        $casella->Id = 'txtDinamico';

        $ph->Add($casella);

        $dopo = self::Segnaposto();

        $dopo->LoadViewState($ph->SaveViewState());

        //e' l'ordine vero del motore: LoadViewState ricostruisce l'albero, LoadPostData
        //arriva dopo e trova i controlli gia' al loro posto
        $dopo->FindControl('txtDinamico')->LoadPostData(['txtDinamico' => 'battuto a mano']);

        $p->Uguale('un input creato dal codice riceve quello che l\'utente ha scritto',
            'battuto a mano', $dopo->FindControl('txtDinamico')->Text);

        // --- chi lo ricrea in OnInit non se ne ritrova due

        $ph = self::Segnaposto();

        $primo = new Label();
        $primo->Id   = 'lblDoppia';
        $primo->Text = 'dal primo giro';

        $ph->Add($primo);

        $stato = $ph->SaveViewState();

        //la pagina lo ricrea in OnInit, come si e' sempre fatto: OnInit gira PRIMA che lo
        //stato arrivi, quindi quando arriva il controllo c'e' gia'
        $conOnInit = self::Segnaposto();

        $gia = new Label();
        $gia->Id = 'lblDoppia';

        $conOnInit->Add($gia);

        $conOnInit->LoadViewState($stato);

        $p->Uguale('ricreandolo in OnInit non se ne ottengono due', 1, count($conOnInit->Controls));

        $p->Uguale('ed e\' quello ricreato, con sopra lo stato salvato',
            'dal primo giro', $conOnInit->Controls[0]->Text);

        // --- la posizione fra i figli del markup si mantiene

        $misto = self::Misto();

        $mezzo = new Label();
        $mezzo->Id   = 'lblMezzo';
        $mezzo->Text = 'IN MEZZO';

        //fra i due <b> del markup, non in coda
        array_splice($misto->Controls, 1, 0, [$mezzo]);

        $rifatto = self::Misto();

        $rifatto->LoadViewState($misto->SaveViewState());

        $p->Uguale('il figlio dinamico torna nella posizione in cui stava',
            $misto->Render(), $rifatto->Render());

        // --- il markup NON finisce nello stato

        $soloMarkup = self::Misto();

        $p->Uguale('quello che viene dal markup non si salva: si rilegge',
            false, array_key_exists('Dyn', $soloMarkup->SaveViewState()));

        // --- un albero dinamico annidato: tr dentro tbody, td dentro tr

        //E' il caso della tabella costruita a mano, e mette alla prova la RICORSIONE: se la
        //ricostruzione fosse solo di primo livello si otterrebbero <tr> vuoti.
        $corpo = self::Corpo();

        foreach ([1, 2, 3] as $numero)
            $corpo->Add(self::RigaDiTabella($numero));

        $rifatto = self::Corpo();

        $rifatto->LoadViewState($corpo->SaveViewState());

        $p->Uguale('le righe annidate tornano tutte, con dentro le loro celle',
            $corpo->Render(), $rifatto->Render());

        $p->Contiene('e il bottone dentro la cella e\' ancora un bottone che scatena eventi',
            'data-dw-click="1"', $rifatto->Render());

        $p->Uguale('e si raggiunge per id, tre livelli sotto',
            'elimina', $rifatto->FindControl('lnkElimina2')->Text);

        // --- una riga tolta resta tolta, e le altre non si spostano

        array_splice($rifatto->Controls, 1, 1);

        $dopoElimina = self::Corpo();

        $dopoElimina->LoadViewState($rifatto->SaveViewState());

        $p->Uguale('la riga eliminata non torna indietro', 2, count($dopoElimina->Controls));

        $p->Uguale('e le altre restano quelle che erano, senza rinumerarsi',
            ['tr1', 'tr3'], array_map(static fn($r) => $r->Id, $dopoElimina->Controls));

        //il giro dopo, e quello dopo ancora: e' li' che si vedrebbe uno stato che si sfalda
        $terzo = self::Corpo();

        $terzo->LoadViewState($dopoElimina->SaveViewState());

        $p->Uguale('e restano cosi' . "'" . ' anche ai postback successivi',
            $dopoElimina->Render(), $terzo->Render());

        // --- la cornice viene dal markup quanto la pagina

        //Regressione vera, e cattiva: quando la master non veniva marcata, i suoi controlli
        //passavano per figli attaccati dal codice e il motore smetteva di salvarne lo stato
        //per conto suo. Il sintomo era il titolo scritto dalla pagina che spariva al primo
        //click - su OGNI pagina con una master.
        $master = self::Cornice();

        $p->Uguale('la cornice non ha figli dinamici: viene tutta dal markup',
            [], $master->DynamicChildren());

        $p->Uguale('quindi il suo stato non porta figli da ricostruire',
            false, array_key_exists('Dyn', $master->SaveViewState()));

        $titolo = $master->FindControl('litTitolo');

        $titolo->Text = 'scritto dalla pagina';

        $p->Uguale('e un suo controllo si salva come tutti gli altri',
            'scritto dalla pagina', $titolo->SaveViewState()['Text']);

        // --- quello che non si sa ricostruire lo dice subito

        $p->Solleva('un controllo che non e\' del motore lo dice quando lo si attacca, non al click dopo',
            'ricrealo in OnInit',
            static function (): void
            {
                $ph = self::Segnaposto();

                $ph->Add(new class extends Control {
                    public function Render(): string
                    {
                        return '';
                    }
                });

                $ph->SaveViewState();
            });
    }

    /**
     * La cornice dei banchi di prova, costruita come la costruirebbe una pagina.
     *
     * E' una master vera e sta in Common, quindi la prova resta dentro il motore: non tira
     * dentro niente del sito.
     */
    private static function Cornice(): \Common\WebForms\MasterPage
    {
        $pagina = new class extends \Common\WebForms\Page {
        };

        return ControlBuilder::BuildMaster('Common/WebForms/ProveAMano/Cornice', [], $pagina);
    }

    /** Un PlaceHolder vuoto, come lo costruirebbe il markup. */
    private static function Segnaposto(): PlaceHolder
    {
        /** @var PlaceHolder $ph */
        $ph = ControlBuilder::Build(PageParser::ParseTesto('<dw:PlaceHolder id="ph" />'), null)[0];

        return $ph;
    }

    /** Il <tbody> vuoto che il markup dichiara, e che il codice riempie a mano. */
    private static function Corpo(): Panel
    {
        /** @var Panel $corpo */
        $corpo = ControlBuilder::Build(
            PageParser::ParseTesto('<dw:Panel id="corpo" Tag="tbody" />'),
            null
        )[0];

        return $corpo;
    }

    /** <tr><td><Label></td><td><LinkButton></td></tr>, tutto costruito dal codice. */
    private static function RigaDiTabella(int $numero): Panel
    {
        $riga = new Panel();

        $riga->Id  = 'tr' . $numero;
        $riga->Tag = 'tr';

        $cella = new Panel();

        $cella->Id  = 'tdTesto' . $numero;
        $cella->Tag = 'td';

        $testo = new Label();

        $testo->Id   = 'litRiga' . $numero;
        $testo->Text = 'Riga ' . $numero;

        $cella->Add($testo);

        $azione = new Panel();

        $azione->Id  = 'tdAzione' . $numero;
        $azione->Tag = 'td';

        $elimina = new LinkButton();

        $elimina->Id              = 'lnkElimina' . $numero;
        $elimina->Text            = 'elimina';
        $elimina->OnClick         = 'EliminaClick';
        $elimina->CommandArgument = (string)$numero;

        $azione->Add($elimina);

        $riga->Add($cella);
        $riga->Add($azione);

        return $riga;
    }

    /** Un Panel con due figli dichiarati nel markup, per provare le posizioni. */
    private static function Misto(): Panel
    {
        /** @var Panel $pnl */
        $pnl = ControlBuilder::Build(
            PageParser::ParseTesto('<dw:Panel id="pnl"><b>uno</b><b>due</b></dw:Panel>'),
            null
        )[0];

        return $pnl;
    }
}
