<?php
declare(strict_types=1);

namespace Common\WebForms\Controls;

use Common\WebForms\Control;
use Common\WebForms\ControlBuilder;

/**
 * Ripete un template per ogni riga di DataSource.
 *
 * Tre scelte che contano:
 *
 * 1. L'ID DI RIGA E' LA CHIAVE DEL DATO, non la posizione. Con id posizionali, inserire una
 *    riga in testa sposta tutti gli id di uno e il morph lato client riscrive l'intera
 *    griglia: sparisce il focus, muoiono i listener, si vede il lampo. Con la chiave del
 *    dato inserisce un nodo e non tocca gli altri. E' la "key" di React, per lo stesso
 *    identico motivo. Per questo DataKeyField non e' facoltativo.
 *
 * 2. Le righe sono controlli creati a runtime, e i controlli creati a runtime al postback
 *    dopo non esistono piu'. Percio' le righe rendute finiscono nello stato e vengono
 *    ricostruite in LoadViewState, PRIMA che si legga il form e si scatenino gli eventi: e'
 *    l'unico modo perche' il bottone dentro la riga trovi ancora il suo controllo.
 *
 * 3. UNA RIGA SI RIEMPIE IN DUE MODI, e valgono tutti e due:
 *
 *    - con i segnaposto del template - <td>{{Nome}}</td> - quando il valore e' gia' nel
 *      DataSource e va solo scritto;
 *    - dal codice, quando la cella si calcola: un conteggio, un colore, un link, qualcosa
 *      che dipende da altre letture. Di solito in OnItemDataBound, che e' la forma delle
 *      pagine WK - template con <dw:Literal> vuoti, e a riempirli e' il codice - ma va bene
 *      anche dopo il DataBind(), pescando la riga con Items().
 *
 *    QUELLO CHE IL CODICE HA MESSO SOPRAVVIVE, comunque sia arrivato li'. Il Repeater
 *    fotografa ogni riga appena costruita e a fine richiesta salva solo cio' che non
 *    combacia piu': chi usa i segnaposto e basta non paga niente, chi tocca i controlli di
 *    riga paga esattamente quello che ha toccato. Vedi SaveViewState.
 */
class Repeater extends Control
{
    /** Nodi dell'ItemTemplate, dal markup. */
    public array $ItemTemplate = [];

    /** @var array<int,array> righe da rendere, come array associativi */
    public array $DataSource = [];

    /** Il campo della riga che la identifica: e' la chiave del morph, mai la posizione. */
    public string $DataKeyField = 'Id';

    /**
     * Tag del contenitore e di ogni riga: tbody/tr dentro una tabella, ul/li in un elenco.
     * Un div dentro <table> il browser lo sposta fuori dalla tabella, quindi non basta un
     * contenitore neutro.
     */
    public string $Tag = 'div';

    /** L'elemento di ogni riga: tr dentro un tbody, li dentro un ul. */
    public string $ItemTag = 'div';

    /**
     * Handler di pagina chiamato per ogni riga durante il DataBind, come l'OnItemDataBound
     * di WebForms:
     *
     *     protected function OnRowDataBound(Repeater $sender, RepeaterItem $riga): void
     *
     * Scatta SOLO al DataBind, mai alla ricostruzione da stato: e' il momento in cui i dati
     * ci sono, ed e' anche l'unico in cui abbia senso andare a rileggere qualcosa.
     */
    public string $OnItemDataBound = '';

    /** @var array<int,array{k:string,d:array}> le righe cosi' come sono state rese */
    private array $items = [];

    /**
     * Lo stato dei controlli di riga COM'ERANO APPENA COSTRUITI, per id.
     *
     * E' il metro di paragone di SaveViewState: quello che a fine richiesta non combacia piu'
     * l'ha messo il codice, e va portato al postback dopo. Si rifa' ad ogni CreateItems, sia
     * al DataBind sia ricostruendo dallo stato, e in tutti e due i casi viene dagli stessi
     * dati - quindi le due fotografie sono la stessa.
     *
     * @var array<string,array>
     */
    private array $nascita = [];

    protected function ViewStateProperties(): array
    {
        return array_merge(parent::ViewStateProperties(), ['DataKeyField', 'Tag', 'ItemTag', 'OnItemDataBound']);
    }

    /**
     * Fotografa DataSource nelle righe e ricostruisce i controlli di riga.
     * Va chiamata dopo aver assegnato DataSource, come in WebForms.
     */
    public function DataBind(): void
    {
        $righe = [];

        foreach ($this->DataSource as $riga)
        {
            $dati = self::ToArray($riga);

            if (!array_key_exists($this->DataKeyField, $dati))
                throw new \RuntimeException(
                    'Repeater "' . $this->Id . '": la riga non ha il campo chiave "' . $this->DataKeyField . '".'
                );

            $righe[] = ['k' => (string)$dati[$this->DataKeyField], 'd' => $dati];
        }

        $this->items = $righe;

        $this->CreateItems(true);
    }

    /**
     * Le righe rese, come RepeaterItem. E' l'Items del Repeater di WebForms, e serve alla
     * riga che tutte le pagine WK scrivono dopo il DataBind:
     *
     *     $this->rpt->Visible = $this->rpt->Items() !== [];
     *
     * @return RepeaterItem[]
     */
    public function Items(): array
    {
        return $this->Controls;
    }

    /**
     * Via tutte le righe, come Items.Clear() in WebForms.
     *
     * Serve quando l'elenco va rifatto da capo per un motivo che non e' un evento della
     * pagina: e' cambiata la querystring - un altro mese, un altro filtro nell'indirizzo - e
     * le righe tenute nello stato descrivono il mese di prima. Si svuota e si ridatabinda in
     * OnLoad; senza svuotare, DataBind() rimpiazzerebbe comunque le righe, ma questo dice
     * l'intenzione e lascia l'elenco vuoto anche se poi non c'e' niente da rilegare.
     */
    public function ClearItems(): void
    {
        $this->items    = [];
        $this->Controls = [];
        $this->nascita  = [];

        $this->RebuildsChildren();
    }

    /**
     * Oltre alle righe si salva quello che il CODICE ha cambiato nei controlli di riga.
     *
     * Non tutto lo stato di riga, e non niente: la differenza. Un template a segnaposto
     * ricostruisce le celle dai dati della riga, che sono gia' in 'Items', e salvarle di
     * nuovo sarebbe spreco puro; ma un testo scritto in un handler, un colore messo secondo
     * lo stato, un bottone spento perche' l'ordine e' partito non sono ricavabili da niente,
     * e senza di loro un postback che non ridatabinda - si apre una scheda, si annulla, si
     * preme un bottone fuori dall'elenco - renderebbe la griglia diversa da com'era.
     *
     * Il confronto e' con la fotografia scattata alla nascita delle righe, quindi la regola
     * non ha eccezioni da ricordare: vale per OnItemDataBound, per le modifiche fatte dopo il
     * DataBind(), e per quelle che una pagina fara' fra un anno in un modo che oggi non
     * esiste.
     */
    public function SaveViewState(): array
    {
        $state = parent::SaveViewState();

        $state['Items'] = $this->items;

        //Un Repeater costruito dal codice non ha un markup da cui rileggere il template: al
        //giro dopo rinascerebbe con un ItemTemplate vuoto e le righe uscirebbero senza niente
        //dentro. Quindi il template viaggia con lui - ma SOLO se e' dinamico: per quelli del
        //markup sarebbe la stessa informazione scritta due volte ad ogni postback.
        if ($this->Dinamico())
            $state['Tpl'] = $this->ItemTemplate;

        $cambiati = [];

        foreach ($this->Controls as $riga)
            self::RaccogliRamo($riga, $cambiati);

        //via tutto quello che una ricostruzione rifarebbe uguale
        foreach ($cambiati as $id => $stato)
            if (($this->nascita[$id] ?? null) === $stato)
                unset($cambiati[$id]);

        if ($cambiati !== [])
            $state['Rows'] = $cambiati;

        return $state;
    }

    public function LoadViewState(array $state): void
    {
        parent::LoadViewState($state);

        if (isset($state['Tpl']) && is_array($state['Tpl']))
            $this->ItemTemplate = $state['Tpl'];

        $this->items = $state['Items'] ?? [];

        $this->CreateItems(false);

        $this->ApplicaStatoRighe($state['Rows'] ?? []);
    }

    /**
     * Un controllo di riga per chiave: id "<idTemplate>__<chiave>", cosi' l'handler sa da
     * quale riga arriva l'evento senza doversi fidare di niente che venga dal client.
     *
     * @param bool $bind true al DataBind - e allora si scatena OnItemDataBound - false
     *                   quando le righe si stanno solo ricostruendo dallo stato
     */
    private function CreateItems(bool $bind): void
    {
        $this->Controls = [];

        $this->nascita = [];

        $indice = 0;

        foreach ($this->items as $riga)
        {
            $contenitore = new RepeaterItem();

            $contenitore->Id     = $this->Id . '__' . $riga['k'];
            $contenitore->Tag    = $this->ItemTag;
            $contenitore->Page = $this->Page;
            $contenitore->NamingKey = $riga['k'];
            $contenitore->ItemIndex = $indice;
            $contenitore->ItemType = $indice % 2 === 0 ? RepeaterItem::ITEM : RepeaterItem::ALTERNATING_ITEM;

            $tokens = $riga['d'];

            foreach (ControlBuilder::Build($this->ItemTemplate, $this->Page, $tokens) as $child)
            {
                self::QualifyIds($child, $riga['k']);

                $contenitore->Add($child);
            }

            //i figli della riga vengono dall'ItemTemplate, che si rilegge ad ogni richiesta:
            //non sono roba messa dal codice e non vanno salvati come figli dinamici
            $contenitore->RebuildsChildren();

            $this->Add($contenitore);

            //la fotografia si scatta PRIMA dell'handler: quello che scrive lui e' gia' una
            //modifica del codice, e come tale deve finire nello stato
            self::RaccogliRamo($contenitore, $this->nascita);

            if ($bind && $this->OnItemDataBound !== '')
            {
                if ($this->Page === null)
                    throw new \RuntimeException('Repeater "' . $this->Id . '": OnItemDataBound senza pagina.');

                //i dati valgono solo qui dentro: fuori dal DataBind la riga non li ha piu',
                //e chi li vuole se li rilegge dall'id, come fanno le pagine WK
                $contenitore->DataItem = $tokens;

                $this->Page->InvokeHandler($this->OnItemDataBound, $this, $contenitore);

                $contenitore->DataItem = [];
            }

            $indice++;
        }

        //e le righe se le rifa' il Repeater da Items: stessa ragione, un livello piu' su
        $this->RebuildsChildren();
    }

    /** Questo Repeater l'ha attaccato il codice? Lo sa chi lo contiene, o la pagina. */
    private function Dinamico(): bool
    {
        $contenitore = $this->Parent ?? $this->Page;

        return $contenitore !== null && in_array($this, $contenitore->DynamicChildren(), true);
    }

    private static function RaccogliRamo(Control $control, array &$stato): void
    {
        if ($control->Id !== '' && $control->IsViewStateEnabled())
            $stato[$control->Id] = $control->SaveViewState();

        //un Repeater annidato si e' gia' salvato tutto da solo nella riga qui sopra - le sue
        //righe comprese - e ricostruira' i propri figli nel suo LoadViewState: scendere qui
        //vorrebbe dire salvare una seconda volta dei controlli che rinasceranno comunque.
        //E' la stessa regola che segue Page quando gira l'albero.
        if ($control instanceof self)
            return;

        foreach ($control->Controls as $child)
            self::RaccogliRamo($child, $stato);
    }

    private function ApplicaStatoRighe(array $stato): void
    {
        if ($stato === [])
            return;

        foreach ($this->Controls as $riga)
            self::ApplicaRamo($riga, $stato);
    }

    private static function ApplicaRamo(Control $control, array $stato): void
    {
        if ($control->Id !== '' && isset($stato[$control->Id]))
            $control->LoadViewState($stato[$control->Id]);

        foreach ($control->Controls as $child)
            self::ApplicaRamo($child, $stato);
    }

    private static function ToArray(mixed $riga): array
    {
        if (is_array($riga))
            return $riga;

        if (is_object($riga))
            return get_object_vars($riga);

        throw new \RuntimeException('DataSource deve contenere array od oggetti.');
    }

    public function Render(): string
    {
        $tag = preg_match('/^[a-z][a-z0-9]*$/', $this->Tag) === 1 ? $this->Tag : 'div';

        return '<' . $tag . $this->RenderAttributes() . '>' . $this->RenderChildren() . '</' . $tag . '>';
    }
}
