<?php
declare(strict_types=1);

namespace Common\WebForms;

/**
 * Il ciclo di vita della pagina.
 *
 *   Init          albero costruito dal markup - sempre uguale, e' il vincolo che regge tutto
 *   LoadViewState   proprieta' dei controlli e variabili della pagina
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
     * La pagina si tiene com'era quando la si lascia, come una form di WinForms.
     *
     * ACCESA PER TUTTI: il browser conserva lo stato di questa pagina e glielo rimanda quando
     * ci si torna - filtri, righe aperte, controlli attaccati dal codice, tutto dov'era. E'
     * il modello di WinForms, dove una form resta in memoria mentre se ne guarda un'altra.
     *
     * Spenta, ogni volta che si arriva sulla pagina la si trova nuova, com'e' il web:
     *
     *     protected function OnInit(): void
     *     {
     *         $this->KeepState = false;
     *     }
     *
     * Quando spegnerla: su un elenco di dati che cambiano sotto le mani di altri, dove
     * tornare e trovare le righe di prima vorrebbe dire mostrare cose che qualcuno ha gia'
     * cambiato o cancellato. L'alternativa e' lasciarla accesa e rileggere in OnLoad quando
     * serve: IsPostBack e' vero al ritorno, e la pagina sa di essere stata ripristinata.
     *
     * Lo stato conservato vive nella memoria della SCHEDA del browser: si perde chiudendola o
     * ricaricando con F5, non si vede da un'altra scheda, e resta firmato - il server lo
     * verifica come qualunque altro stato. Se non torna buono, la pagina si apre pulita.
     */
    public bool $KeepState = true;

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
    public array $DwAlerts = [];

    /** Il markup della master, se la pagina ne dichiara una. */
    private string $MasterSrc = '';

    /** @var Control[] */
    protected array $Controls = [];

    /**
     * I controlli che stavano alla radice appena costruito l'albero: quelli del markup.
     * Serve a distinguere quelli che il codice attacca dopo con Add(), che vanno nello stato.
     *
     * @var Control[]
     */
    private array $markupRoot = [];

    /** @var array<string,callable[]> */
    private array $subscriptions = [];

    private bool $eventsClosed = false;

    private int $depth = 0;

    private const int MAX_DEPTH = 8;
    /** Tetto del pacchetto #[Portable]: viaggia su ogni richiesta, non e' un magazzino. */
    private const int PORTABLE_MAX = 4096;
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
        PageMap::Ensure($markupFile);

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

        //Il ritorno su una pagina gia' visitata, con lo stato che il browser si era tenuto.
        //E' una POST come il postback - lo stato e' lungo e non sta in un'intestazione - ma
        //non porta nessun evento e la risposta e' un documento intero, non un frammento.
        $ripristino = ($_SERVER['HTTP_X_DW_RIPRISTINA'] ?? '') === '1';

        if ($ripristino)
            $postback = true;

        //il canale di postback e' una POST con un header personalizzato: senza il token la
        //richiesta e' partita da un altro sito con la sessione della vittima allegata
        if ($postback && !Csrf::Verify())
        {
            http_response_code(403);
            Response::Reload();
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

        //da qui in poi quello che compare alla radice l'ha messo il codice
        $this->markupRoot = $this->Controls;

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
                //su un ripristino non si urla e non si ricarica: lo stato tenuto dal browser
                //puo' essere di una versione fa, o firmato con un segreto rigenerato. Si apre
                //la pagina pulita, che e' esattamente quello che l'utente si aspetta.
                if ($ripristino)
                {
                    //il PushId lo rifa' il ramo qui sotto, come per un primo caricamento
                    $postback = false;
                    $this->IsPostBack = false;
                }
                else
                {
                    $this->OnViewStateExpired();
                    return;
                }
            }
        }

        if ($postback)
        {
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

        //un ripristino non porta eventi: rimette in piedi la pagina e basta
        if ($postback && !$ripristino)
            $this->ProcessPostBackEvent();

        $this->eventsClosed = true;

        $this->OnPreRender();

        $this->ForEachUserControl(static fn(UserControl $uc) => $uc->OnPreRender());

        $stato = ViewState::Pack($this->SaveViewState());

        $html = $this->RenderPage();

        //Lo stato viaggia ACCANTO all'HTML, non dentro.
        //
        //Al postback il client rimpiazza il contenuto di dw-root con un morph, cioe' con una
        //riconciliazione nodo per nodo: finche' il campo nascosto stava li' dentro, il
        //pacchetto piu' importante della pagina dipendeva dal fatto che il morph lo
        //riconoscesse e ne aggiornasse il valore. Fuori non dipende piu' da niente - il
        //client ce lo scrive dentro e basta - e si trova sempre nello stesso posto, come una
        //volta si trovava la sessione.
        //il frammento e' la risposta del postback; il ripristino invece e' una navigazione, e
        //il client si aspetta un documento da cui prendere dw-root
        if ($postback && !$ripristino)
            Response::Fragment($html, $stato);
        else
            Response::Document($this, $html, $stato);
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

        try
        {
            $riflesso->invoke($this, $sender, $argomento);
        }
        catch (\ReflectionException $e)
        {
            //il metodo esiste, e' di questa classe ed e' appena stato letto: se non si
            //lascia invocare e' un errore di programmazione, non un caso da gestire
            throw new \RuntimeException('Handler "' . $metodo . '" non invocabile.', 0, $e);
        }
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
     *     $this->RedirectToPage(\Pages::Northwind_Categorie);
     *
     * L'elenco e' l'enum generato dai markup (@see PageMap): ci sono dentro solo le pagine
     * vere, l'IDE le completa, e rinominarne una porta dietro tutti i rimandi. Con la
     * stringa invece un refuso si scopre quando ci clicca un utente.
     */
    public function RedirectToPage(SitePage $pagina): never
    {
        $this->Redirect($pagina->Path());
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
        if (!$control->CanRaiseEvents())
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
    /**
     * Attacca un controllo alla radice della pagina, fuori da qualunque contenitore.
     *
     * E' la pagina "vuota" costruita tutta dal codice: markup senza controlli, e il
     * codebehind che monta pannelli, elenchi e bottoni. Quello che si attacca qui torna al
     * postback dopo come da un PlaceHolder qualunque: la radice e' un contenitore come gli
     * altri, solo che non ha un tag attorno.
     */
    public function Add(Control $control): Control
    {
        $control->Parent = null;
        $control->Page   = $this;

        $this->Controls[] = $control;

        return $control;
    }

    /**
     * I controlli attaccati alla radice dal codice, per posizione.
     *
     * @return array<int,Control>
     */
    public function DynamicChildren(): array
    {
        return array_filter(
            $this->Controls,
            fn(Control $control): bool => !in_array($control, $this->markupRoot, true)
        );
    }

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

        //quelli attaccati dal codice alla radice viaggiano a parte, con classe e posizione,
        //come i figli dinamici di qualunque contenitore; gli altri si salvano per id
        $dinamici = $this->DynamicChildren();

        foreach ($this->Controls as $posizione => $control)
            if (!isset($dinamici[$posizione]))
                $this->SaveControlState($control, $controls);

        $radice = Control::SaveDynamicChildren($dinamici);

        $fields = [];

        foreach ($this->PersistedProperties() as $property)
        {
            //una proprieta' tipizzata e mai assegnata non ha un valore: si salta, e al
            //ritorno restera' com'e' nata
            if (!$property->isInitialized($this))
                continue;

            $valore = $property->getValue($this);

            //un oggetto non attraversa lo stato - il pacchetto si riapre con
            //allowed_classes:false, di proposito - e scoprirlo al click dopo, con una classe
            //incompleta fra le mani, e' molto peggio che scoprirlo qui
            if (is_object($valore))
                throw new \RuntimeException(
                    'La proprieta\' "' . $property->getName() . '" di ' . static::class
                    . ' contiene un oggetto (' . $valore::class . ') e non puo\' attraversare il postback: '
                    . 'se non deve durare marcala #[Transient], se deve tienine l\'id.'
                );

            $fields[$property->getName()] = $valore;
        }

        //Titolo e lingua sono roba della TESTA del documento, non di un controllo, e nessun
        //variabile di pagina li copre. Su un postback non conterebbero - la risposta e' un frammento -
        //ma su un ripristino si rende un documento intero, e senza di loro la linguetta del
        //browser resterebbe senza nome.
        $stato = [
            'push'  => $this->PushId,
            'c'     => $controls,
            'p'     => $fields,
            'testa' => ['t' => $this->Title, 'l' => $this->Lang],
        ];

        if ($radice !== [])
            $stato['r'] = $radice;

        return $stato;
    }

    private function SaveControlState(Control $control, array &$controls): void
    {
        //un controllo spento non si salva, ma sotto di lui si scende lo stesso: un figlio
        //puo' essersi riacceso con ViewStateMode="Enabled", come in WebForms
        if ($control->Id !== '' && $control->IsViewStateEnabled())
            $controls[$control->Id] = $control->SaveViewState();

        //le righe di un Repeater sono ricostruite dal suo stato: salvarle una per una
        //significherebbe scrivere due volte la stessa informazione
        if ($control instanceof Controls\Repeater)
            return;

        //i figli attaccati dal codice stanno gia' dentro lo stato del padre, con la loro
        //posizione e la loro classe: salvarli di nuovo qui li scriverebbe due volte
        $dinamici = $control->DynamicChildren();

        foreach ($control->Controls as $posizione => $child)
            if (!isset($dinamici[$posizione]))
                $this->SaveControlState($child, $controls);
    }

    private function LoadViewState(array $state): void
    {
        $this->PushId = (string)($state['push'] ?? '');

        $this->Title = (string)($state['testa']['t'] ?? $this->Title);
        $this->Lang  = (string)($state['testa']['l'] ?? $this->Lang);

        foreach ($this->PersistedProperties() as $property)
            if (array_key_exists($property->getName(), $state['p'] ?? []))
                $property->setValue($this, $state['p'][$property->getName()]);

        foreach ($this->Controls as $control)
            $this->LoadControlState($control, $state['c'] ?? []);

        //e per ultimi quelli che il codice aveva attaccato alla radice: arrivano con il loro
        //stato gia' dentro, non c'e' niente da rileggere per id
        Control::LoadDynamicChildren($this->Controls, $state['r'] ?? [], null, $this, '');
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

        foreach (new \ReflectionClass($this)->getProperties() as $p)
            if ($p->getAttributes(Portable::class) !== [])
                $property[] = $p;

        return $memoria[$classe] = $property;
    }

    /**
     * Le variabili della pagina che attraversano il postback: TUTTE, come i campi di una form.
     *
     * In WinForms una form resta in memoria e i suoi campi con lei; in PHP l'oggetto pagina
     * muore a fine richiesta, e per anni la risposta e' stata "marca quello che vuoi tenere".
     * Ma guardando le pagine vere non ce n'e' una che voglia il contrario: un campo di pagina
     * e' memoria per definizione, e l'attributo era solo un modo per dimenticarsene.
     *
     * Quindi la regola si rovescia: si tiene tutto, e si marca #[Transient] il poco che deve
     * rinascere ad ogni richiesta. Restano fuori da soli:
     *
     *   - le proprieta' del motore, cioe' quelle dichiarate da Page stessa: Title e Lang
     *     viaggiano per conto loro, IsPostBack si decide ad ogni richiesta, Alert e Head si
     *     ricostruiscono;
     *   - le proprieta' #[Portable], che hanno un canale loro e attraversano le pagine;
     *   - le proprieta' tipizzate con una classe: i controlli del designer, la master, un
     *     Model. Un oggetto non attraversa la serializzazione, e non deve nemmeno provarci.
     *
     *
     * @return \ReflectionProperty[]
     */
    private function PersistedProperties(): array
    {
        static $memoria = [];

        $classe = static::class;

        if (isset($memoria[$classe]))
            return $memoria[$classe];

        $property = [];

        foreach (new \ReflectionClass($this)->getProperties() as $p)
        {
            if ($p->isStatic() || $p->getDeclaringClass()->getName() === self::class)
                continue;

            if ($p->getAttributes(Portable::class) !== [] || $p->getAttributes(Transient::class) !== [])
                continue;

            if (!self::TipoDiStato($p->getType()))
                continue;

            $property[] = $p;
        }

        return $memoria[$classe] = $property;
    }

    /**
     * Un tipo che puo' stare nello stato: scalari, array, mixed, o nessun tipo. Una classe
     * no - e in un'unione basta una classe per dire di no.
     */
    private static function TipoDiStato(?\ReflectionType $tipo): bool
    {
        if ($tipo === null)
            return true;

        if ($tipo instanceof \ReflectionNamedType)
            return $tipo->isBuiltin();

        if ($tipo instanceof \ReflectionUnionType)
        {
            foreach ($tipo->getTypes() as $parte)
                if (!self::TipoDiStato($parte))
                    return false;

            return true;
        }

        //intersezioni e quant'altro: sono classi per forza
        return false;
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

    private function RenderPage(): string
    {
        $html = '';

        foreach ($this->Controls as $control)
            $html .= $control->Render();

        //Il pacchetto portatile viaggia come attributo della radice, non in un campo
        //nascosto: il morph sincronizza gli attributi del nodo, quindi il client se lo
        //ritrova aggiornato dopo un postback e dopo una navigazione senza doverlo cercare
        //in due posti diversi. L'attributo c'e' solo se la pagina ha campi #[Portable]:
        //cosi' chi non li usa non paga niente, nemmeno un giro in piu' al primo caricamento.
        $portatile = $this->PortableProperties() === []
            ? ''
            : ' data-dw-portable="' . Control::HtmlEncode($this->PackPortable()) . '"';

        //la pagina che si tiene: il client se ne conserva lo stato quando la si lascia, e
        //glielo rimanda quando ci si torna
        $tieni = $this->KeepState ? ' data-dw-tieni="1"' : '';

        return '<div id="dw-root"'
            . ' data-dw-push="' . Control::HtmlEncode($this->PushId) . '"'
            . $tieni
            . $portatile . '>'
            . $html
            . '</div>';
    }
}
