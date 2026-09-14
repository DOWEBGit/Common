<?php
declare(strict_types=1);

namespace Common\WebForms;

/**
 * Base di ogni controllo.
 *
 * Un controllo e' un oggetto vivo per tutta la durata del postback: l'handler puo'
 * cambiarne le proprieta' come farebbe in WinForms, e il render successivo le riflette.
 * Fra un postback e l'altro sopravvive quello che dichiara ViewStateProperties().
 *
 * L'id finisce nell'HTML COSI' COM'E'. Niente prefissi di contenitore, niente ctl00$: e'
 * l'errore che rendeva inservibile getElementById in WebForms, ed e' anche cio' che
 * permette al morph lato client di riconoscere i nodi invece di ricrearli.
 */
abstract class Control
{
    public string $Id = '';

    public bool $Visible = true;

    public string $CssClass = '';

    public ?Control $Parent = null;

    /** @var Control[] */
    public array $Controls = [];

    public ?Page $Page = null;

    /**
     * Chiave della riga di Repeater che contiene questo controllo, vuota fuori da una riga.
     * E' quello che permette a un controllo di ritrovare i suoi fratelli senza indovinare
     * il suffisso degli id.
     */
    public string $NamingKey = '';

    /**
     * Gli attributi HTML aggiunti dal codice, come l'Attributes di WebForms.
     *
     *     $elimina->Attributes->Add('title', 'Elimina la riga ' . $nome);
     *
     * @see AttributeCollection
     */
    public AttributeCollection $Attributes;

    /**
     * Lo stile in linea, come lo Style di WebForms.
     *
     *     $riga->Style->Add('background-color', $colore);
     *
     * @see CssStyleCollection
     */
    public CssStyleCollection $Style;

    public function __construct()
    {
        $this->Attributes = new AttributeCollection();
        $this->Style      = new CssStyleCollection();
    }

    /**
     * Se lo stato di questo controllo si salva, come il ViewStateMode di WebForms.
     *
     *     <dw:Repeater id="rpt" ViewStateMode="Disabled" ...>
     *
     * Tre valori. INHERIT (predefinito) fa quello che fa il padre, e la pagina e' ENABLED:
     * quindi senza scrivere niente si salva tutto. DISABLED spegne questo controllo e, per
     * eredita', tutto quello che sta sotto; un figlio puo' riaccendersi con ENABLED.
     *
     * Cosa succede sotto un DISABLED: i controlli DEL MARKUP ci sono ancora - si ricostruiscono
     * dal markup, con i valori del markup - ma quello che il codice ci aveva scritto e'
     * perso; i controlli ATTACCATI DAL CODICE non tornano proprio, e chi li vuole se li ricrea
     * in OnInit, alla maniera vecchia. Un Repeater spento non porta le righe: la pagina lo
     * ridatabinda in OnLoad, ed e' la scelta giusta per un elenco che rilegge dal database ad
     * ogni click - quelle righe nello stato erano solo peso.
     *
     * Viene dal markup e non dallo stato, per forza: e' lui a decidere se lo stato esiste.
     */
    public string $ViewStateMode = self::INHERIT;

    public const string INHERIT = 'Inherit';
    public const string ENABLED = 'Enabled';
    public const string DISABLED = 'Disabled';
    /**
     * Proprieta' che attraversano il postback. Chi aggiunge una proprieta' che l'utente
     * puo' cambiare e non la elenca qui si ritrova il valore azzerato al click dopo.
     *
     * @return string[]
     */
    protected function ViewStateProperties(): array
    {
        return ['Visible', 'CssClass', 'Attributes', 'Style'];
    }

    /**
     * Valori iniziali dal markup. Si applicano solo alla costruzione dell'albero: da li' in
     * poi comanda lo stato.
     */
    public function ApplyAttributes(array $attr): void
    {
        foreach ($attr as $chiave => $valore)
        {
            if (strcasecmp((string)$chiave, 'ViewStateMode') !== 0)
                continue;

            //si confronta senza badare alle maiuscole, come WebForms, ma si scrive in forma
            //canonica: e' quella che IsViewStateEnabled() confronta
            $modo = array_search(strtolower((string)$valore),
                array_map('strtolower', [self::INHERIT, self::ENABLED, self::DISABLED]), true);

            if ($modo === false)
                throw new \RuntimeException(
                    'ViewStateMode="' . $valore . '" su "' . $this->Id . '": vale Inherit, Enabled o Disabled.'
                );

            $this->ViewStateMode = [self::INHERIT, self::ENABLED, self::DISABLED][$modo];
        }

        foreach ($this->ViewStateProperties() as $nome)
        {
            foreach ($attr as $chiave => $valore)
            {
                if (strcasecmp($chiave, $nome) !== 0)
                    continue;

                //una proprieta' enum si sceglie per NOME del caso, senza badare alle maiuscole:
                //Mode="datetime" e Mode="DateTime" sono la stessa cosa, e un nome che non
                //esiste si ferma qui con l'elenco di quelli buoni
                if ($this->$nome instanceof \UnitEnum)
                {
                    $this->$nome = self::CasoEnum($this->$nome::class, (string)$valore, $nome);

                    continue;
                }

                $this->$nome = match (gettype($this->$nome)) {
                    'boolean' => $valore === 'true' || $valore === '1',
                    'integer' => (int)$valore,
                    default   => $valore,
                };
            }
        }
    }

    /**
     * Il caso di un enum dal suo nome, come lo si scrive nel markup.
     *
     * @param class-string<\UnitEnum> $enum
     */
    private function CasoEnum(string $enum, string $nome, string $proprieta): \UnitEnum
    {
        foreach ($enum::cases() as $caso)
            if (strcasecmp($caso->name, $nome) === 0)
                return $caso;

        throw new \RuntimeException(
            $proprieta . '="' . $nome . '" su "' . $this->Id . '": vale '
            . implode(', ', array_map(static fn(\UnitEnum $c): string => $c->name, $enum::cases())) . '.'
        );
    }

    public function Add(Control $child): Control
    {
        $child->Parent  = $this;
        $child->Page = $this->Page;

        $this->Controls[] = $child;

        return $child;
    }

    /**
     * Ricerca per id in tutto il sottoalbero.
     *
     * Dentro un contenitore di denominazione si accetta anche l'id "nudo" come sta scritto
     * nel markup: e' il contenitore a sapere quale riga rappresenta, e chi scrive la pagina
     * non deve conoscerne il suffisso. Stesso comportamento del FindControl di WebForms
     * dentro un ItemTemplate.
     */
    public function FindControl(string $id): ?Control
    {
        if ($this->Id === $id)
            return $this;

        foreach ($this->Controls as $child)
        {
            $found = $child->FindControl($id);

            if ($found !== null)
                return $found;
        }

        if ($this->NamingKey !== '' && !str_ends_with($id, '__' . $this->NamingKey))
            return $this->FindControl($id . '__' . $this->NamingKey);

        return null;
    }

    /**
     * Il contenitore di denominazione: la riga di Repeater in cui vive questo controllo.
     *
     * Serve a scrivere $sender->NamingContainer()->FindControl('hidId'), cioe' esattamente
     * il linkButton.Parent.FindControl("__Hidden_Id") delle pagine WebForms: dal controllo
     * che ha scatenato l'evento si raggiunge il campo che porta l'id del record.
     *
     * Fuori da una riga torna null: e' un errore di programmazione, e chi chiama deve
     * accorgersene invece di lavorare su un valore vuoto.
     */
    public function NamingContainer(): ?Control
    {
        if ($this->NamingKey === '')
            return null;

        $container = $this;

        //si risale finche' si resta nella stessa riga: il contenitore di riga ha la chiave,
        //il Repeater che lo contiene no, quindi la salita si ferma da sola
        while ($container->Parent !== null && $container->Parent->NamingKey === $this->NamingKey)
            $container = $container->Parent;

        return $container;
    }

    /**
     * Questo controllo puo' scatenare un evento?
     *
     * No, se lui o un suo antenato e' invisibile. Serve perche' qui un controllo invisibile
     * viene RESO LO STESSO, con l'attributo hidden: e' quello che permette al morph di
     * ritrovarlo quando torna visibile, invece di ricostruire il ramo. Il rovescio e' che il
     * nodo resta nella pagina, e dalla console si puo' cliccare il bottone di una scheda
     * chiusa. In WebForms il problema non esisteva perche' Visible=false non rendeva niente.
     *
     * Chi scrive una pagina da' per scontato che un pannello chiuso non risponda, e deve
     * poterlo dare per scontato: la condizione si verifica QUI, dove conta, non nel markup.
     *
     * Enabled non si guarda: non sta su Control ma sui controlli che ce l'hanno, e ognuno lo
     * ricontrolla gia' nel proprio RaisePostBackEvent.
     */
    public function CanRaiseEvents(): bool
    {
        for ($nodo = $this; $nodo !== null; $nodo = $nodo->Parent)
            if (!$nodo->Visible)
                return false;

        return true;
    }

    /**
     * I figli che c'erano appena costruito l'albero: quelli del markup.
     *
     * Non e' un dettaglio contabile, e' il confine fra due mondi. Quello che sta qui dentro
     * si rifa' da solo ad ogni richiesta, perche' il markup non cambia; quello che compare
     * dopo l'ha messo il codice, e se non lo salva il motore non lo rimette piu' nessuno.
     *
     * @var Control[]
     */
    private array $markupChildren = [];

    /**
     * Dichiara che i figli di ADESSO non devono finire nello stato: li rimette chi li ha
     * messi.
     *
     * Lo chiama ControlBuilder su tutto l'albero appena costruito - il markup si rilegge ad
     * ogni richiesta - e lo chiama chi si ricostruisce i propri figli da se': il Repeater
     * dalle sue righe, il paginatore dai suoi numeri di pagina. Senza, quei figli
     * verrebbero salvati due volte: una da chi li sa rifare e una dal motore.
     *
     * Non e' ricorsiva: riguarda solo i figli diretti di questo controllo.
     */
    public function RebuildsChildren(): void
    {
        $this->markupChildren = $this->Controls;
    }

    /**
     * I figli messi dal codice, per posizione.
     *
     * @return array<int,Control>
     */
    public function DynamicChildren(): array
    {
        return array_filter(
            $this->Controls,
            fn(Control $figlio): bool => !in_array($figlio, $this->markupChildren, true)
        );
    }

    /**
     * Lo stato di questo controllo si salva?
     *
     * Si risale finche' qualcuno lo dice: il primo Enabled o Disabled che si incontra decide,
     * e in cima - nessuno ha detto niente - e' acceso.
     */
    public function IsViewStateEnabled(): bool
    {
        for ($nodo = $this; $nodo !== null; $nodo = $nodo->Parent)
            if ($nodo->ViewStateMode !== self::INHERIT)
                return $nodo->ViewStateMode === self::ENABLED;

        return true;
    }

    /**
     * Lo stato di questo controllo, e quello dei figli che il codice gli ha attaccato.
     *
     * I figli dinamici viaggiano DENTRO lo stato di chi li contiene, con la loro posizione e
     * il nome della loro classe. E' quello che permette di scriverli dove viene comodo - in
     * OnLoad, dentro un handler - e ritrovarseli al postback dopo senza ricostruirli a mano:
     * il vecchio "lo metto in sessione in Page_Init", ma senza sessione e senza Page_Init.
     */
    public function SaveViewState(): array
    {
        $state = [];

        //le collection viaggiano come array: il pacchetto si riapre senza classi
        foreach ($this->ViewStateProperties() as $nome)
            $state[$nome] = match (true) {
                $this->$nome instanceof NamedCollection => $this->$nome->ToArray(),
                //un enum non attraversa il pacchetto - si riapre senza classi - ma il suo
                //valore si'
                $this->$nome instanceof \BackedEnum   => $this->$nome->value,
                default                                => $this->$nome,
            };

        $dinamici = self::SaveDynamicChildren($this->DynamicChildren());

        if ($dinamici !== [])
            $state['Dyn'] = $dinamici;

        return $state;
    }

    public function LoadViewState(array $state): void
    {
        foreach ($this->ViewStateProperties() as $nome)
        {
            if (!array_key_exists($nome, $state))
                continue;

            if ($this->$nome instanceof NamedCollection)
                $this->$nome->Replace(is_array($state[$nome]) ? $state[$nome] : []);
            elseif ($this->$nome instanceof \BackedEnum)
                //un valore che l'enum non conosce piu' - un caso tolto - lascia quello di
                //adesso invece di far morire la pagina
                $this->$nome = $this->$nome::tryFrom($state[$nome]) ?? $this->$nome;
            else
                $this->$nome = $state[$nome];
        }

        self::LoadDynamicChildren($this->Controls, $state['Dyn'] ?? [], $this, $this->Page, $this->NamingKey);
    }

    /**
     * I figli dinamici come vanno nello stato: posizione, classe, id e stato di ognuno.
     *
     * E' statica e pubblica perche' la usa anche la pagina per i controlli attaccati alla
     * sua radice: la regola e' una sola, e sta in un posto solo.
     *
     * @param array<int,Control> $figli per posizione, come li da' DynamicChildren()
     */
    public static function SaveDynamicChildren(array $figli): array
    {
        $dinamici = [];

        foreach ($figli as $posizione => $figlio)
        {
            //un figlio dinamico spento non si porta al giro dopo: chi lo vuole lo ricrea in
            //OnInit, alla maniera vecchia. E' quello che vuol dire spegnerlo.
            if (!$figlio->IsViewStateEnabled())
                continue;

            $dinamici[] = [
                'i' => $posizione,
                'c' => self::RebuildableName($figlio),
                'd' => $figlio->Id,
                's' => $figlio->SaveViewState(),
            ];
        }

        return $dinamici;
    }

    /**
     * Rimette al loro posto i figli che il codice aveva attaccato.
     *
     * Il nome della classe arriva da un pacchetto firmato con HMAC, quindi non e' roba che
     * un client possa scegliere; il controllo qui sotto e' contro i nostri sbagli - una
     * classe rinominata, un controllo tolto dal motore - non contro un attacco. In quel caso
     * si salta il figlio invece di far morire la pagina: manchera' un pezzo, e si vede.
     *
     * @param Control[] $controls l'elenco in cui rimetterli: quello di un controllo, o la
     *                            radice della pagina
     */
    public static function LoadDynamicChildren(array &$controls, array $voci, ?Control $parent, ?Page $page, string $namingKey): void
    {
        foreach ($voci as $voce)
        {
            if (!is_array($voce))
                continue;

            $classe = self::VIVAIO . ($voce['c'] ?? '');

            if (!class_exists($classe) || !is_subclass_of($classe, self::class))
                continue;

            $id    = (string)($voce['d'] ?? '');
            $stato = is_array($voce['s'] ?? null) ? $voce['s'] : [];

            //Se c'e' gia' - perche' il codice lo ricrea in OnInit ad ogni richiesta, che e'
            //il modo classico - non se ne fa un secondo con lo stesso id: gli si rimette
            //sopra il suo stato e basta. Cosi' le due strade convivono invece di raddoppiare
            //i controlli, e chi aveva scritto la pagina alla maniera vecchia non deve
            //cambiare niente.
            $esistente = null;

            if ($id !== '')
                foreach ($controls as $c)
                    if ($c->Id === $id)
                        $esistente = $c;

            if ($esistente !== null)
            {
                $esistente->LoadViewState($stato);

                continue;
            }

            $figlio = new $classe();

            $figlio->Id        = $id;
            $figlio->Parent    = $parent;
            $figlio->Page      = $page;
            $figlio->NamingKey = $namingKey;

            $figlio->LoadViewState($stato);

            array_splice($controls, (int)($voce['i'] ?? count($controls)), 0, [$figlio]);
        }
    }

    /**
     * Solo i controlli del motore si sanno ricostruire da un nome.
     *
     * Un UserControl no: e' markup piu' un designer piu' una classe, e un new() nudo darebbe
     * un guscio vuoto. Chi ne attacca uno a runtime se lo ricrea in OnInit, come si e' sempre
     * fatto. Meglio dirlo qui, quando lo si scrive, che lasciarlo sparire al primo click.
     */
    private static function RebuildableName(Control $figlio): string
    {
        $classe = $figlio::class;

        if (!str_starts_with($classe, self::VIVAIO))
            throw new \RuntimeException(
                'Il controllo "' . $figlio->Id . '" (' . $classe . ') e\' stato attaccato dal codice, '
                . 'ma solo i controlli del motore si sanno ricostruire dallo stato: '
                . 'ricrealo in OnInit ad ogni richiesta.'
            );

        return substr($classe, strlen(self::VIVAIO));
    }

    /** Il vivaio da cui si ripescano i controlli dinamici. */
    private const string VIVAIO = __NAMESPACE__ . '\\Controls\\';
    /** Applica i valori del form. Solo i controlli di input la implementano. */
    public function LoadPostData(array $post): void
    {
    }

    /**
     * Scatena l'evento richiesto dal client.
     *
     * Il client manda l'ID DEL CONTROLLO, mai il nome del metodo: quello sta nel markup,
     * lato server. Cosi' non esiste una richiesta che possa far eseguire un metodo che il
     * markup non ha autorizzato.
     */
    public function RaisePostBackEvent(string $evento, string $argomento): void
    {
    }

    /**
     * Manda un comando verso l'alto finche' un contenitore lo gestisce.
     *
     * E' il RaiseBubbleEvent di WebForms, ed e' cio' che permette a un UserControl di
     * reagire ai propri figli senza che quei figli sappiano chi li contiene: il LinkButton
     * "pagina 3" dice solo "Page/3", non chiama nessuno per nome. Senza, ogni controllo
     * composito dovrebbe far dichiarare gli handler nel markup della pagina ospite, e non
     * sarebbe piu' riutilizzabile.
     */
    protected function RaiseBubbleEvent(Control $source, string $commandName, string $commandArgument): void
    {
        $parent = $this->Parent;

        while ($parent !== null)
        {
            if ($parent->OnBubbleEvent($source, $commandName, $commandArgument))
                return;

            $parent = $parent->Parent;
        }
    }

    /** @return bool true se il comando e' stato gestito e non deve salire oltre. */
    protected function OnBubbleEvent(Control $source, string $commandName, string $commandArgument): bool
    {
        return false;
    }

    /**
     * Rende univoci gli id di un sottoalbero, marcandolo come contenuto di un contenitore
     * di denominazione. Serve al Repeater (una riga) e agli UserControl (due istanze dello
     * stesso controllo nella stessa pagina non devono avere gli stessi id).
     */
    public static function QualifyIds(Control $control, string $key): void
    {
        if ($control->Id !== '')
            $control->Id .= '__' . $key;

        //la chiave resta anche sui controlli senza id: serve a NamingContainer() per sapere
        //dove fermare la risalita
        $control->NamingKey = $key;

        foreach ($control->Controls as $child)
            self::QualifyIds($child, $key);
    }

    abstract public function Render(): string;

    protected function RenderChildren(): string
    {
        $html = '';

        foreach ($this->Controls as $child)
            $html .= $child->Render();

        return $html;
    }

    /**
     * id, class e hidden: hidden invece di non renderizzare, cosi' il nodo resta al suo
     * posto con il suo id e il morph lo ritrova quando torna visibile.
     */
    protected function RenderAttributes(): string
    {
        $html = ' id="' . self::HtmlEncode($this->Id) . '"';

        if ($this->CssClass !== '')
            $html .= ' class="' . self::HtmlEncode($this->CssClass) . '"';

        if (!$this->Visible)
            $html .= ' hidden';

        //lo stile in linea, in un attributo solo: un solo escape su tutto, cosi' le
        //virgolette diventano entita' e non possono chiudere l'attributo
        if (count($this->Style) > 0)
            $html .= ' style="' . self::HtmlEncode($this->Style->ToCss()) . '"';

        //quelli aggiunti dal codice, per ultimi. I nomi li ha gia' controllati la collection
        //quando ci sono entrati, anche arrivando dallo stato
        foreach ($this->Attributes as $nome => $valore)
            $html .= ' ' . $nome . '="' . self::HtmlEncode($valore) . '"';

        return $html;
    }

    /**
     * Marcatore dell'evento sull'elemento che lo genera.
     *
     * L'id viaggia insieme al marcatore invece di leggerlo dall'elemento: in un CheckBox il
     * marcatore sta sull'<input> mentre l'id sta sul <label> che lo avvolge, e leggendo
     * element.id il postback partirebbe con un bersaglio vuoto. Cosi' la regola vale per
     * qualunque forma abbia il controllo.
     */
    protected function PostBackAttribute(string $evento): string
    {
        return ' data-dw-' . $evento . '="1" data-dw-id="' . self::HtmlEncode($this->Id) . '"';
    }

    public static function HtmlEncode(?string $testo): string
    {
        return htmlspecialchars((string)$testo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
