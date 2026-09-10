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
     * Attributi HTML aggiunti dal codice, come l'Attributes di WebForms.
     *
     *     $elimina->AttributoAggiungi('title', 'Elimina la riga ' . $nome);
     *     $riga->AttributoAggiungi('data-stato', $ordine->Stato);
     *
     * E' la valvola di sfogo per quello che il motore non prevede: un title che cambia per
     * riga, un data- che serve a un pezzo di JavaScript del sito, un aria- per un caso
     * particolare. Si scrive dal codebehind - nel markup gli attributi si mettono e basta.
     *
     * @var array<string,string>
     */
    public array $Attributes = [];

    /**
     * Nomi che hanno gia' un padrone: renderli due volte darebbe un HTML con l'attributo
     * ripetuto, e il browser terrebbe il primo - cioe' non quello appena scritto.
     */
    private const RISERVATI = ['id', 'class', 'hidden', 'name'];

    /**
     * Proprieta' che attraversano il postback. Chi aggiunge una proprieta' che l'utente
     * puo' cambiare e non la elenca qui si ritrova il valore azzerato al click dopo.
     *
     * @return string[]
     */
    protected function ViewStateProperties(): array
    {
        return ['Visible', 'CssClass', 'Attributes'];
    }

    /**
     * Aggiunge o cambia un attributo HTML. Torna il controllo, cosi' se ne possono
     * concatenare piu' di uno.
     *
     * Il valore esce escapato, sempre: e' l'unico modo perche' un titolo che contiene un
     * apostrofo o un nome che contiene virgolette non spezzino il tag. Il NOME invece deve
     * essere un nome di attributo e basta, e qui si controlla subito - non al render, dove
     * l'errore salterebbe fuori lontano da chi l'ha scritto.
     */
    public function AttributoAggiungi(string $nome, string $valore): static
    {
        self::ControllaNomeAttributo($nome);

        $this->Attributes[$nome] = $valore;

        return $this;
    }

    /** Toglie un attributo aggiunto prima. */
    public function AttributoTogli(string $nome): static
    {
        unset($this->Attributes[$nome]);

        return $this;
    }

    private static function ControllaNomeAttributo(string $nome): void
    {
        if (preg_match('/^[A-Za-z][A-Za-z0-9_.:-]*$/', $nome) !== 1)
            throw new \RuntimeException('"' . $nome . '" non e\' un nome di attributo.');

        if (in_array(strtolower($nome), self::RISERVATI, true))
            throw new \RuntimeException(
                'L\'attributo "' . $nome . '" lo scrive il controllo: usa Id, CssClass o Visible.'
            );

        //data-dw-* e' il canale fra il server e il runtime: sovrascriverlo non aggiunge un
        //attributo, cambia il modo in cui il client interpreta il controllo
        if (str_starts_with(strtolower($nome), 'data-dw-'))
            throw new \RuntimeException('"' . $nome . '" appartiene al motore.');
    }

    /**
     * Valori iniziali dal markup. Si applicano solo alla costruzione dell'albero: da li' in
     * poi comanda lo stato.
     */
    public function ApplyAttributes(array $attr): void
    {
        foreach ($this->ViewStateProperties() as $nome)
        {
            foreach ($attr as $chiave => $valore)
            {
                if (strcasecmp($chiave, $nome) !== 0)
                    continue;

                $this->$nome = match (gettype($this->$nome)) {
                    'boolean' => $valore === 'true' || $valore === '1',
                    'integer' => (int)$valore,
                    default   => $valore,
                };
            }
        }
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
    public function Attivabile(): bool
    {
        for ($nodo = $this; $nodo !== null; $nodo = $nodo->Parent)
            if (!$nodo->Visible)
                return false;

        return true;
    }

    public function SaveViewState(): array
    {
        $state = [];

        foreach ($this->ViewStateProperties() as $nome)
            $state[$nome] = $this->$nome;

        return $state;
    }

    public function LoadViewState(array $state): void
    {
        foreach ($this->ViewStateProperties() as $nome)
            if (array_key_exists($nome, $state))
                $this->$nome = $state[$nome];
    }

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

        //quelli aggiunti dal codice, per ultimi. Il nome si ricontrolla anche qui: nello
        //stato ci si arriva anche scrivendo dritto nell'array, e un nome con uno spazio
        //dentro non sarebbe un attributo in piu' - sarebbe markup iniettato
        foreach ($this->Attributes as $nome => $valore)
        {
            self::ControllaNomeAttributo((string)$nome);

            $html .= ' ' . $nome . '="' . self::HtmlEncode((string)$valore) . '"';
        }

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
