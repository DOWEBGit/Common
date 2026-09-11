<?php
declare(strict_types=1);

namespace Common\WebForms\ProveAMano;

use Common\WebForms\Control;
use Common\WebForms\Controls\Label;
use Common\WebForms\Controls\Literal;
use Common\WebForms\Controls\TextBox;
use Common\WebForms\Page;

/**
 * Il banco di prova a mano dello stato: cosa sopravvive a un postback e cosa no.
 *
 * Sta dentro Common e non in un sito perche' e' una prova DEL MOTORE: niente master page,
 * niente Model, niente database, nessun foglio di stile del sito. Si apre e funziona ovunque
 * ci sia il motore, anche in un sito appena creato che non ha ancora niente dentro.
 *
 * Le prove automatiche in UnitTest/ dicono che lo stato torna indietro; questa pagina fa
 * vedere la stessa cosa con le mani, cliccando - che e' l'unico modo di provare anche il
 * pezzo che gira nel browser: il morph che rimpiazza i nodi senza ricaricare la pagina.
 *
 * Le tre risposte che da':
 *
 *   1. quello che il codice mette nelle righe di un Repeater resta, comunque ci sia arrivato;
 *   2. un controllo costruito in OnInit resta, com'e' sempre stato in WebForms, e non
 *      diventa un doppione adesso che anche lo stato lo saprebbe rifare;
 *   3. un controllo costruito in OnLoad o dentro un handler resta lo stesso: il contenitore
 *      si salva i figli che non vengono dal markup e li rimette prima che si legga il form.
 */
class Stato extends Page
{
    use StatoDesigner;

    public int $Click = 0;

    /** Quante etichette ha attaccato il bottone: serve a dar loro un id stabile. */
    public int $Nate = 0;

    /** Quello che il codice ha scritto nella casella dinamica, per rimettercelo. */
    public string $Eco = '';

    /** I dati stanno qui: la prova e' sul motore, non sulle letture. */
    private const array RIGHE = [
        ['Id' => 1, 'Nome' => 'Alfa'],
        ['Id' => 2, 'Nome' => 'Beta'],
        ['Id' => 3, 'Nome' => 'Gamma'],
        ['Id' => 4, 'Nome' => 'Delta'],
    ];

    private const array COLORI = ['#b91c1c', '#15803d', '#1d4ed8', '#a16207'];
    /**
     * RIQUADRO 2. La maniera classica: i controlli dinamici ricreati AD OGNI RICHIESTA.
     *
     * E' come si e' sempre fatto in WebForms, e qui continua a funzionare: OnInit gira prima
     * di LoadViewState, quindi quando lo stato arriva il controllo c'e' gia' e ci si posa
     * sopra. Non serve piu' - il riquadro 3 fa lo stesso senza ricreare niente - ma chi ha
     * pagine scritte cosi' non deve toccarle, e non si ritrova doppioni: il motore, se trova
     * gia' un figlio con quell'id, gli rimette lo stato invece di aggiungerne un secondo.
     *
     * L'id dev'essere STABILE. Un id che dipende dall'ordine di creazione, o dall'ora, o da
     * un contatore che riparte, e' un controllo diverso ogni volta: lo stato del precedente
     * resta orfano e viene buttato via.
     */
    protected function OnInit(): void
    {
        /*
        $this->Title = 'Prove a mano: lo stato dei controlli';

        $casella = new TextBox();

        $casella->Id          = 'txtDinamico';
        $casella->Placeholder = 'scrivi qui, poi fai un postback';

        $this->phSempre->Add($casella);

        $eco = new Label();

        $eco->Id       = 'lblEco';
        $eco->CssClass = 'pm-tenue';

        $this->phSempre->Add($eco);
        */
    }

    protected function OnLoad(): void
    {
        if ($this->IsPostBack)
            return;

        $this->Title = 'Prove a mano: lo stato dei controlli';

        $casella = new TextBox();

        $casella->Id          = 'txtDinamico';
        $casella->Placeholder = 'scrivi qui, poi fai un postback';

        $this->phSempre->Add($casella);

        $eco = new Label();

        $eco->Id       = 'lblEco';
        $eco->CssClass = 'pm-tenue';

        $this->phSempre->Add($eco);

        $this->litClick->Text = '0';

        //RIQUADRO 3: creata QUI, in OnLoad. Al postback dopo non ripassa di qui - c'e' il
        //return su IsPostBack - eppure c'e' ancora: la rimette lo stato.
        $this->Nate = 0;

        $this->Attacca('lblDaOnLoad', 'Sono nata in OnLoad, al primo caricamento.');

        $this->rpt->DataSource = self::RIGHE;
        $this->rpt->DataBind();

        //RIQUADRO 1: si veste QUI, a DataBind gia' fatto e senza OnItemDataBound. Niente di
        //quello che segue e' ricavabile dal template o dai dati della riga.
        foreach ($this->rpt->Items() as $riga)
        {
            $colore = self::COLORI[$riga->ItemIndex % count(self::COLORI)];

            $riga->Style->Add('border-left', '4px solid ' . $colore);

            $riga->CssClass = 'js-prova-riga';

            $tocca = $riga->FindControl('lnkTocca');

            $tocca->Style->Add('color', $colore);
            $tocca->Attributes->Add('title', 'Riga numero ' . ($riga->ItemIndex + 1));

            /** @var Literal $nota */
            $nota = $riga->FindControl('litNota');

            $nota->Text = 'vestita dal codice, riga ' . ($riga->ItemIndex + 1);
        }
    }

    /**
     * Il bottone in cima: conta e basta.
     *
     * Non tocca il Repeater, non ridatabinda, non ricostruisce niente. Tutto quello che si
     * vede ancora dopo questo click arriva dallo stato.
     */
    protected function PostbackClick(): void
    {
        $this->Click++;

        $this->litClick->Text = (string)$this->Click;
    }

    /**
     * Il link dentro la riga: scrive nella riga da cui e' partito.
     *
     * Prova due cose insieme: che un controllo creato a runtime esiste ancora quando arriva
     * l'evento, e che dalla riga si raggiungono i propri fratelli con l'id NUDO del markup,
     * senza sapere niente del suffisso.
     */
    protected function ToccaClick(Control $sender): void
    {
        $riga = $sender->NamingContainer();

        /** @var Literal $nota */
        $nota = $riga->FindControl('litNota');

        $nota->Text = 'toccata al postback numero ' . ($this->Click + 1);

        $riga->Style->Add('background', '#fef9c3');
    }

    /**
     * RIQUADRO 3. Un'etichetta attaccata al volo, dentro l'handler.
     *
     * Non c'e' nessun OnInit che la ricrei, eppure al postback dopo c'e' ancora - e ci sono
     * anche tutte quelle aggiunte prima, nel loro ordine. Le rimette lo stato del PlaceHolder,
     * che si e' salvato i figli che non venivano dal markup.
     */
    protected function VoloClick(): void
    {
        $this->Nate++;

        $this->Attacca('lblVolo' . $this->Nate, 'Numero ' . $this->Nate . ', nata dentro un handler.');
    }

    /**
     * Un'etichetta attaccata al segnaposto, e nient'altro.
     *
     * Non c'e' nessuna riga che la ricostruisca: torna perche' il PlaceHolder si salva i figli
     * che non vengono dal markup - posizione, classe e stato - e li rimette in LoadViewState,
     * prima che si legga il form. L'id dev'essere STABILE, ed e' per questo che il contatore
     * e' una variabile di pagina, che dura: un id che riparte da capo sarebbe un controllo nuovo ogni volta, e lo
     * stato del precedente resterebbe orfano.
     */
    private function Attacca(string $id, string $testo): void
    {
        $al = new Label();

        $al->Id   = $id;
        $al->Text = $testo;

        $al->Style->Add('color', '#0f766e');
        $al->Style->Add('display', 'block');

        $this->phVolatile->Add($al);
    }

    /**
     * Il testo della casella dinamica si rilegge e si rimostra.
     *
     * E' la dimostrazione che di un controllo costruito dal codice funziona TUTTO, non solo
     * il render: il valore digitato passa da LoadPostData come per un controllo del markup.
     */
    protected function OnPreRender(): void
    {
        $this->litVolo->Text = (string)count($this->phVolatile->Controls);

        /** @var TextBox $casella */
        $casella = $this->FindControl('txtDinamico');

        if ($casella->Text !== '')
            $this->Eco = $casella->Text;

        /** @var Label $eco */
        $eco = $this->FindControl('lblEco');

        $eco->Text = $this->Eco === ''
            ? 'La casella qui sopra e\' vuota: scrivici qualcosa e fai un postback.'
            : 'Il motore ha riletto dalla casella dinamica: "' . $this->Eco . '"';
    }
}
