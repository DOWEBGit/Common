<?php
declare(strict_types=1);

namespace Common\WebForms;

/**
 * Il ciclo di vita della pagina.
 *
 *   Init          albero costruito dal markup - sempre uguale, e' il vincolo che regge tutto
 *   LoadViewState   proprieta' dei controlli e campi #[Persist] della pagina
 *   LoadPostData i valori del form entrano nei controlli
 *   Load          OnLoad(), con $this->IsPostBack
 *   Evento        l'handler indicato dal markup: qui si manipola liberamente la pagina
 *   PreRender     OnPreRender()
 *   Render         HTML dell'intera pagina
 *   SaveViewState    lo stato torna in sessione
 *
 * Il render e' sempre totale e il client fa il morph: niente regioni da dichiarare, niente
 * UpdatePanel. Un handler puo' toccare qualunque controllo, ovunque nella pagina, e si
 * aggiorna. Il render parziale per controlli sporchi e' un'ottimizzazione che si potra'
 * aggiungere senza toccare una riga di markup o di codebehind.
 */
abstract class Page
{
    public bool $IsPostBack = false;

    /**
     * Identificativo di instradamento per le notifiche push: dice CHI e' questa pagina, non
     * cosa contiene. Si puo' diffondere senza esporre niente.
     */
    public string $PushId = '';

    public string $Title = '';

    /**
     * Lingua del documento: finisce in <html lang="...">.
     *
     * E' una proprieta' e non una costante perche' un sito multilingua cambia lingua per
     * richiesta, e un lang sbagliato lo sentono i lettori di schermo e i traduttori.
     */
    public string $Lang = 'it';

    /**
     * Righe da mettere nel <head>: description, canonical, og:, un css di pagina.
     *
     * Ci si scrive HTML gia' pronto, quindi ci va solo roba decisa dal server. Il posto per
     * riempirlo e' OnPreRender, quando la pagina sa gia' cosa sta mostrando.
     *
     * @var string[]
     */
    public array $Head = [];

    /**
     * Gli avvisi da mostrare: $this->Alert->Success('Salvato.'), Fail(...), anche modali.
     *
     * A disegnarli e' il <dw:Alert> della master page. @see \Common\WebForms\Alert
     */
    public Alert $Alert;

    /**
     * La coda degli avvisi, in viaggio.
     *
     * E' #[Portable] perche' un avviso deve poter sopravvivere al cambio di pagina: un
     * handler dice "salvato" e subito dopo manda altrove, e il messaggio si vede la' - qui
     * una navigazione e' una fetch e un morph, non un ricarico, quindi il pacchetto che il
     * browser tiene in memoria arriva a destinazione.
     *
     * Il nome e' la chiave, ed e' la stessa per tutte le pagine: e' proprio quello che
     * serve. Non si tocca a mano: la si riempie con $this->Alert e la svuota chi la disegna.
     *
     * @var array<int,array{tipo:string,testo:string,modale:bool,chiave:string}>
     */
    #[Portable]
    public array $DwAvvisi = [];

    /** Il markup della master, se la pagina ne dichiara una. */
    private string $MasterSrc = '';

    /** @var Control[] */
    protected array $Controls = [];

    /** @var array<string,callable[]> */
    private array $subscriptions = [];

    private bool $eventsClosed = false;

    private int $depth = 0;

    private const MAX_DEPTH = 8;

    /** Tetto del pacchetto #[Portable]: viaggia su ogni richiesta, non e' un magazzino. */
    private const PORTABLE_MAX = 4096;

    // ---------------------------------------------------------------- ganci

    protected function OnInit(): void
    {
    }

    protected function OnLoad(): void
    {
    }

    protected function OnPreRender(): void
    {
    }

    /**
     * Lo slot di stato non c'e' piu' (sessione scaduta, o troppe pagine aperte).
     * Per difetto si ricarica pulito, che e' meglio di un errore incomprensibile.
     */
    protected function OnViewStateExpired(): void
    {
        Response::Reload();
    }

    // ---------------------------------------------------------------- avvio

    /**
     * @param string $masterSrc markup della master, es. "Layouts/Sito". E' l'equivalente
     *                          della direttiva MasterPageFile di WebForms, e sta nel markup
     *                          per lo stesso motivo: e' una scelta di impaginazione, si vede
     *                          aprendo la pagina invece di andarla a cercare nel codebehind.
     */
    public static function Run(string $markupFile, string $codeClass, string $masterSrc = ''): void
    {
        //Il designer si scrive PRIMA che la classe di logica venga caricata: e' un trait che
        //quella classe usa, e senza il file l'autoload fallirebbe al primo avvio di una
        //pagina nuova.
        Designer::Update($markupFile, $codeClass, $masterSrc);

        //codebehind e designer non hanno nomi di classe come nomi di file, quindi
        //l'autoloader non li vede: si includono per percorso, designer per primo
        Designer::RequireCode($markupFile);

        //e l'elenco delle pagine si aggiorna se questa non c'e' dentro: una pagina nuova
        //entra da sola la prima volta che la si apre
        PageMap::Assicura($markupFile);

        if (!is_subclass_of($codeClass, self::class))
            throw new \RuntimeException($codeClass . ' deve estendere ' . self::class . '.');

        /** @var Page $pagina */
        $pagina = new $codeClass();

        $pagina->MasterSrc = $masterSrc;

        $pagina->ProcessRequest($markupFile);
    }

    private function ProcessRequest(string $markupFile): void
    {
        //prima di tutto: un handler puo' parlare gia' in OnInit, e il pacchetto portatile che
        //arriva puo' portare avvisi lasciati dalla pagina precedente
        $this->Alert = new Alert($this);

        $postback = ($_SERVER['HTTP_X_DW_POSTBACK'] ?? '') === '1';

        //il canale di postback e' una POST con un header personalizzato: senza il token la
        //richiesta e' partita da un altro sito con la sessione della vittima allegata
        if ($postback && !Csrf::Verifica())
        {
            http_response_code(403);
            Response::Reload();
            return;
        }

        $this->IsPostBack = $postback;

        //il token si crea ADESSO, prima di qualunque uscita: setcookie scrive
        //un'intestazione, e quando comincia il render le intestazioni sono gia' partite.
        //Chiamarlo solo dal runtime - che gira in fondo alla pagina - lasciava il cookie non
        //scritto, e senza cookie la verifica lascia passare tutto: la protezione c'era in
        //teoria e non in pratica.
        Csrf::Token();

        // --- Init: l'albero nasce dal markup, identico ad ogni richiesta
        $nodi = PageParser::Parse($markupFile);

        if ($this->MasterSrc === '')
        {
            $this->Controls = ControlBuilder::Build($nodi, $this);
        }
        else
        {
            //con la master la radice e' lei, e la pagina sta dentro i suoi segnaposto
            $master = ControlBuilder::BuildMaster($this->MasterSrc, $nodi, $this);

            $this->Controls = [$master];

            //la proprieta' la dichiara il designer, tipizzata con la classe della master:
            //cosi' $this->Master->litTitolo si completa. Senza designer non esiste, e
            //assegnarla creerebbe una proprieta' dinamica
            if (property_exists($this, 'Master'))
                $this->Master = $master;
        }

        $this->BindDesignerFields();

        //Init dal basso: come in WebForms, i controlli composti si preparano prima della
        //pagina, cosi' l'OnInit della pagina li trova gia' pronti da configurare.
        $this->ForEachUserControl(static fn(UserControl $uc) => $uc->OnInit());

        $this->OnInit();

        //lo stato che attraversa le pagine arriva prima di tutto il resto: OnLoad e gli
        //handler devono trovarlo gia' al suo posto. Dopo OnInit e non prima, altrimenti i
        //valori iniziali scritti li' cancellerebbero quelli che arrivano dalla pagina
        //precedente
        $this->LoadPortable();

        if ($postback)
        {
            $state = ViewState::Unpack((string)($_POST['__dw_state'] ?? ''));

            if ($state === null)
            {
                $this->OnViewStateExpired();
                return;
            }

            $this->LoadViewState($state);

            //dopo il carico dello stato l'albero puo' essere cresciuto (le righe dei
            //Repeater): i riferimenti del designer vanno riagganciati
            $this->BindDesignerFields();

            $this->LoadPostData($_POST);
        }
        else
        {
            $this->PushId = ViewState::NewKey();
        }

        //Chi salva firma i propri eventi con il PushId della pagina, cosi' la scheda che ha
        //fatto l'operazione scarta la propria notifica: si e' gia' aggiornata col postback.
        EntityEvents::$Origin = $this->PushId;

        //Load e PreRender dall'alto: la pagina decide, i controlli composti si adeguano.
        //E' l'ordine di WebForms, ed e' quello che serve: un paginatore deve sapere quanti
        //elementi ci sono, e lo sa solo dopo che la pagina ha letto i dati.
        $this->OnLoad();

        $this->ForEachUserControl(static fn(UserControl $uc) => $uc->OnLoad());

        if ($postback)
            $this->ProcessPostBackEvent();

        $this->eventsClosed = true;

        $this->OnPreRender();

        $this->ForEachUserControl(static fn(UserControl $uc) => $uc->OnPreRender());

        $stato = $this->SaveViewState();

        //il campo nascosto si compone PRIMA del render: deve finire dentro l'HTML reso
        $html = $this->RenderPage(ViewState::Pack($stato));

        if ($postback)
            Response::Fragment($html);
        else
            Response::Document($this, $html);
    }

    // ---------------------------------------------------------------- eventi

    /**
     * Esegue l'handler nominato dal MARKUP.
     *
     * Il client manda l'id del controllo, mai il nome del metodo: quello viaggia solo lato
     * server. Non esiste quindi una richiesta capace di far eseguire un metodo che il markup
     * non abbia autorizzato.
     *
     * Il secondo parametro dell'handler di solito e' l'argomento del comando, una stringa;
     * per l'OnItemDataBound del Repeater e' invece la riga, cioe' un controllo. Sono le due
     * forme che esistono, e restano due firme dello stesso metodo perche' la regola su chi
     * puo' essere chiamato deve stare in un posto solo.
     */
    public function InvokeHandler(string $metodo, Control $sender, string|Control $argomento): void
    {
        if (!method_exists($this, $metodo))
            throw new \RuntimeException(
                'Handler "' . $metodo . '" non trovato in ' . static::class . '.'
            );

        $riflesso = new \ReflectionMethod($this, $metodo);

        //i metodi del framework non sono handler: senza questo controllo un markup con
        //onClick="ProcessRequest" farebbe rientrare la pagina dentro se stessa
        if ($riflesso->getDeclaringClass()->getName() === self::class)
            throw new \RuntimeException('"' . $metodo . '" non e\' un handler di pagina.');

        $riflesso->invoke($this, $sender, $argomento);
    }

    /**
     * Manda il browser altrove e chiude qui la richiesta.
     *
     * Si chiama allo stesso modo dal primo caricamento e da dentro un handler: nel primo
     * caso e' un 302, nel secondo un'istruzione che il runtime esegue. Chi scrive la pagina
     * non deve sapere in quale dei due si trova.
     *
     * Non torna mai. Lo stato non viene salvato e la pagina non viene resa, il che e'
     * giusto: si sta andando via.
     */
    public function Redirect(string $url): never
    {
        //il pacchetto aggiornato parte insieme all'ordine di andare altrove: un handler che
        //cambia un campo #[Portable] e poi rimanda a un'altra pagina deve far arrivare li'
        //il valore nuovo, e il render che l'avrebbe portato non ci sara'
        Response::Redirect($url, $this->IsPostBack, $this->PortableProperties() === [] ? '' : $this->PackPortable());
    }

    /**
     * Manda al login del pannello con il ritorno a questa pagina.
     *
     * Stessa rotta che usa il resto del sito (area-riservata/includes/auth.php): il login
     * e' uno solo, e una pagina che se ne inventa un'altra e' una pagina che un giorno
     * rimandera' in un posto che non esiste piu'.
     */
    /**
     * Manda a un'altra PAGINA del sito, nominandola invece di scriverne l'indirizzo.
     *
     *     $this->RedirectToPage(\Pagine::Northwind_Categorie);
     *
     * L'elenco e' l'enum generato dai markup (@see PageMap): ci sono dentro solo le pagine
     * vere, l'IDE le completa, e rinominarne una porta dietro tutti i rimandi. Con la
     * stringa invece un refuso si scopre quando ci clicca un utente.
     */
    public function RedirectToPage(PaginaDelSito $pagina): never
    {
        $this->Redirect($pagina->Percorso());
    }

    public function RedirectToLogin(): never
    {
        $ritorno = $_SERVER['REQUEST_URI'] ?? '/';

        $this->Redirect('/admin/public/signin?returnUrl=' . urlencode($ritorno));
    }

    /** Iscrizione a un evento. Va dichiarata in OnInit, mai dentro un handler. */
    public function Subscribe(string $topic, callable $handler): void
    {
        if ($this->eventsClosed)
            throw new \RuntimeException('Le iscrizioni si dichiarano in OnInit.');

        $this->subscriptions[$topic][] = $handler;
    }

    /**
     * Solleva un evento verso i componenti iscritti.
     *
     * L'ordine e' quello di iscrizione, sempre: se dipendesse dall'ordine di iterazione di
     * una mappa si otterrebbero difetti che si presentano una volta su venti.
     */
    public function Raise(string $topic, array $dati = []): void
    {
        if ($this->eventsClosed)
            throw new \RuntimeException(
                'Evento "' . $topic . '" sollevato dopo la fase eventi: la pagina e\' gia\' in render.'
            );

        if (!isset($this->subscriptions[$topic]))
            return;

        if ($this->depth >= self::MAX_DEPTH)
            throw new \RuntimeException('Catena di eventi troppo profonda su "' . $topic . '": probabile anello.');

        $this->depth++;

        try
        {
            foreach ($this->subscriptions[$topic] as $handler)
                $handler($dati);
        }
        finally
        {
            $this->depth--;
        }
    }

    private function ProcessPostBackEvent(): void
    {
        $target = (string)($_POST['__dw_target'] ?? '');

        if ($target === '')
            return;

        //notifica arrivata da fuori (un salvataggio altrove): entra nello stesso bus degli
        //eventi locali, cambia solo la porta d'ingresso
        if ($target === '__push')
        {
            foreach (json_decode((string)($_POST['__dw_arg'] ?? '[]'), true) ?: [] as $topic)
                if (is_string($topic))
                    $this->Raise($topic);

            return;
        }

        $control = $this->FindInTree($target);

        //il controllo puo' non esistere piu': la pagina e' cambiata sotto le mani. Si ignora
        //invece di rompere - il render che segue rimette il client in pari
        if ($control === null)
            return;

        //un controllo nascosto e' reso lo stesso (hidden), quindi il suo id e' li' da
        //cliccare anche quando la scheda che lo contiene e' chiusa: l'evento si ferma qui
        if (!$control->Attivabile())
            return;

        $control->RaisePostBackEvent((string)($_POST['__dw_event'] ?? 'click'), (string)($_POST['__dw_arg'] ?? ''));
    }

    // ---------------------------------------------------------------- controlli

    /**
     * Il controllo con quell'id.
     *
     * A differenza del FindControl di WebForms qui un id sbagliato solleva invece di
     * tornare null: un refuso deve vedersi subito, non trasformarsi in un errore su una
     * variabile nulla venti righe piu' avanti.
     */
    public function FindControl(string $id): Control
    {
        $control = $this->FindInTree($id);

        if ($control === null)
            throw new \RuntimeException('Controllo "' . $id . '" non trovato.');

        return $control;
    }

    /** @param callable(UserControl):void $fn */
    private function ForEachUserControl(callable $fn): void
    {
        foreach ($this->Controls as $control)
            self::WalkUserControls($control, $fn);
    }

    private static function WalkUserControls(Control $control, callable $fn): void
    {
        //prima i figli: un UserControl annidato dentro un altro si prepara per primo
        foreach ($control->Controls as $child)
            self::WalkUserControls($child, $fn);

        if ($control instanceof UserControl)
            $fn($control);
    }

    private function FindInTree(string $id): ?Control
    {
        foreach ($this->Controls as $control)
        {
            $found = $control->FindControl($id);

            if ($found !== null)
                return $found;
        }

        return null;
    }

    /** Popola le proprieta' dichiarate nel designer con i controlli corrispondenti. */
    private function BindDesignerFields(): void
    {
        foreach ($this->Controls as $control)
            $this->AgganciaRamo($control);
    }

    private function AgganciaRamo(Control $control): void
    {
        if ($control->Id !== '' && property_exists($this, $control->Id))
            $this->{$control->Id} = $control;

        foreach ($control->Controls as $child)
            $this->AgganciaRamo($child);
    }

    // ---------------------------------------------------------------- stato

    private function SaveViewState(): array
    {
        $controls = [];

        foreach ($this->Controls as $control)
            $this->SaveControlState($control, $controls);

        $fields = [];

        foreach ($this->PersistedProperties() as $property)
            $fields[$property->getName()] = $property->getValue($this);

        return ['push' => $this->PushId, 'c' => $controls, 'p' => $fields];
    }

    private function SaveControlState(Control $control, array &$controls): void
    {
        if ($control->Id !== '')
            $controls[$control->Id] = $control->SaveViewState();

        //le righe di un Repeater sono ricostruite dal suo stato: salvarle una per una
        //significherebbe scrivere due volte la stessa informazione
        if ($control instanceof Controls\Repeater)
            return;

        foreach ($control->Controls as $child)
            $this->SaveControlState($child, $controls);
    }

    private function LoadViewState(array $state): void
    {
        $this->PushId = (string)($state['push'] ?? '');

        foreach ($this->PersistedProperties() as $property)
            if (array_key_exists($property->getName(), $state['p'] ?? []))
                $property->setValue($this, $state['p'][$property->getName()]);

        foreach ($this->Controls as $control)
            $this->LoadControlState($control, $state['c'] ?? []);
    }

    private function LoadControlState(Control $control, array $controls): void
    {
        if ($control->Id !== '' && isset($controls[$control->Id]))
            $control->LoadViewState($controls[$control->Id]);

        //il Repeater ricrea i figli dentro LoadViewState: si iterano DOPO, non prima
        foreach ($control->Controls as $child)
            $this->LoadControlState($child, $controls);
    }

    /**
     * Lo stato che attraversa le pagine, dal pacchetto firmato che manda il browser.
     *
     * Arriva nel corpo del postback o nell'intestazione della navigazione: sono le due
     * strade con cui questo motore cambia pagina, e non ce ne sono altre. Se il pacchetto
     * manca - primo caricamento, F5, indirizzo scritto a mano - i campi restano ai loro
     * valori iniziali, e ci pensa il runtime a rimandarlo indietro.
     */
    private function LoadPortable(): void
    {
        $proprieta = $this->PortableProperties();

        if ($proprieta === [])
            return;

        $pacchetto = (string)($_POST['__dw_portable'] ?? $_SERVER['HTTP_X_DW_PORTABLE'] ?? '');

        if ($pacchetto === '')
            return;

        //firma non valida o pacchetto manomesso: si tira dritto con i valori iniziali. Non
        //e' un errore da mostrare - il peggio che succede e' una pagina che riparte pulita
        $valori = ViewState::Unpack($pacchetto);

        if ($valori === null)
            return;

        foreach ($proprieta as $property)
            if (array_key_exists($property->getName(), $valori))
                $property->setValue($this, $valori[$property->getName()]);
    }

    /** Il pacchetto firmato da rimandare al browser. */
    private function PackPortable(): string
    {
        $valori = [];

        foreach ($this->PortableProperties() as $property)
            $valori[$property->getName()] = $property->getValue($this);

        $pacchetto = ViewState::Pack($valori);

        //oltre questa misura non e' piu' uno stato di navigazione, e viaggia su OGNI
        //richiesta: meglio fermarsi qui che far tagliare l'intestazione a un proxy e
        //passare il pomeriggio a chiedersi perche' un valore sparisce ogni tanto
        if (strlen($pacchetto) > self::PORTABLE_MAX)
            throw new \RuntimeException(
                'Lo stato #[Portable] di ' . static::class . ' e\' di ' . strlen($pacchetto)
                . ' byte: il massimo e\' ' . self::PORTABLE_MAX . '. Tienici le chiavi, non i dati.'
            );

        return $pacchetto;
    }

    /** @return \ReflectionProperty[] */
    private function PortableProperties(): array
    {
        static $memoria = [];

        $classe = static::class;

        if (isset($memoria[$classe]))
            return $memoria[$classe];

        $property = [];

        foreach ((new \ReflectionClass($this))->getProperties() as $p)
            if ($p->getAttributes(Portable::class) !== [])
                $property[] = $p;

        return $memoria[$classe] = $property;
    }

    /** @return \ReflectionProperty[] */
    private function PersistedProperties(): array
    {
        static $memoria = [];

        $classe = static::class;

        if (isset($memoria[$classe]))
            return $memoria[$classe];

        $property = [];

        foreach ((new \ReflectionClass($this))->getProperties() as $p)
            if ($p->getAttributes(Persist::class) !== [])
                $property[] = $p;

        return $memoria[$classe] = $property;
    }

    // ---------------------------------------------------------------- postdata e render

    private function LoadPostData(array $post): void
    {
        foreach ($this->Controls as $control)
            $this->LoadControlPostData($control, $post);
    }

    private function LoadControlPostData(Control $control, array $post): void
    {
        $control->LoadPostData($post);

        foreach ($control->Controls as $child)
            $this->LoadControlPostData($child, $post);
    }

    private function RenderPage(string $statoNascosto): string
    {
        $html = '';

        foreach ($this->Controls as $control)
            $html .= $control->Render();

        //in fondo, non in cima: il ViewState puo' essere lungo, e metterlo prima del
        //contenuto ritarda quello che l'utente deve vedere. E' lo stesso motivo per cui
        //WebForms ha finito per offrire RenderAllHiddenFieldsAtTopOfForm come opzione.
        if ($statoNascosto !== '')
            $html .= '<input type="hidden" name="__dw_state" id="__dw_state" value="'
                . Control::HtmlEncode($statoNascosto) . '">';

        //Il pacchetto portatile viaggia come attributo della radice, non in un campo
        //nascosto: il morph sincronizza gli attributi del nodo, quindi il client se lo
        //ritrova aggiornato dopo un postback e dopo una navigazione senza doverlo cercare
        //in due posti diversi. L'attributo c'e' solo se la pagina ha campi #[Portable]:
        //cosi' chi non li usa non paga niente, nemmeno un giro in piu' al primo caricamento.
        $portatile = $this->PortableProperties() === []
            ? ''
            : ' data-dw-portable="' . Control::HtmlEncode($this->PackPortable()) . '"';

        return '<div id="dw-root"'
            . ' data-dw-push="' . Control::HtmlEncode($this->PushId) . '"'
            . $portatile . '>'
            . $html
            . '</div>';
    }
}
