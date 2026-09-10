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
 *    - con OnItemDataBound, quando la cella si calcola: un conteggio, un colore, un link,
 *      qualcosa che dipende da altre letture. E' la forma delle pagine WK, dove il template
 *      contiene <dw:Literal> vuoti e a riempirli e' il codice.
 *
 *    Il secondo modo costa uno stato in piu' (vedi SaveViewState), il primo no: chi puo'
 *    usare i segnaposto li usi.
 */
class Repeater extends Control
{
    /** Nodi dell'ItemTemplate, dal markup. */
    public array $ItemTemplate = [];

    /** @var array<int,array> righe da rendere, come array associativi */
    public array $DataSource = [];

    public string $DataKeyField = 'Id';

    /**
     * Tag del contenitore e di ogni riga: tbody/tr dentro una tabella, ul/li in un elenco.
     * Un div dentro <table> il browser lo sposta fuori dalla tabella, quindi non basta un
     * contenitore neutro.
     */
    public string $Tag = 'div';

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
     * Oltre alle righe si salva lo stato dei controlli DENTRO le righe, ma solo quando c'e'
     * un OnItemDataBound.
     *
     * Il motivo: con i segnaposto il contenuto di una cella si ricava dai dati della riga,
     * che sono gia' nello stato, e salvarlo due volte sarebbe spreco. Riempendo le celle in
     * codice invece il contenuto non e' ricavabile da niente, e un postback che non
     * ridatabinda - si apre una scheda, si annulla - renderebbe la griglia vuota.
     */
    public function SaveViewState(): array
    {
        $state = parent::SaveViewState();

        $state['Items'] = $this->items;

        if ($this->OnItemDataBound !== '')
            $state['Rows'] = $this->StatoRighe();

        return $state;
    }

    public function LoadViewState(array $state): void
    {
        parent::LoadViewState($state);

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

        $indice = 0;

        foreach ($this->items as $riga)
        {
            $contenitore = new RepeaterItem();

            $contenitore->Id     = $this->Id . '__' . $riga['k'];
            $contenitore->Tag    = $this->ItemTag;
            $contenitore->Page = $this->Page;
            $contenitore->NamingKey = $riga['k'];
            $contenitore->ItemIndex = $indice;
            $contenitore->ItemType = $indice % 2 === 0 ? RepeaterItem::ITEM : RepeaterItem::ALTERNATO;

            $tokens = $riga['d'];

            foreach (ControlBuilder::Build($this->ItemTemplate, $this->Page, $tokens) as $child)
            {
                self::QualifyIds($child, $riga['k']);

                $contenitore->Add($child);
            }

            $this->Add($contenitore);

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
    }

    /** @return array<string,array> lo stato dei controlli di tutte le righe, per id */
    private function StatoRighe(): array
    {
        $stato = [];

        foreach ($this->Controls as $riga)
            self::RaccogliRamo($riga, $stato);

        return $stato;
    }

    private static function RaccogliRamo(Control $control, array &$stato): void
    {
        if ($control->Id !== '')
            $stato[$control->Id] = $control->SaveViewState();

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
