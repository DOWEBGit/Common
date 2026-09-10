<?php
declare(strict_types=1);

namespace Common\WebForms;

use Common\WebForms\Controls\Literal;

/**
 * Da nodi del programma a controlli veri.
 *
 * Un solo punto di costruzione: cosi' il Repeater, che deve ricostruire i controlli di ogni
 * riga, usa esattamente la stessa strada della pagina e non nasce una seconda regola.
 */
class ControlBuilder
{
    /**
     * Dove si cercano gli UserControl chiamati per nome: <dw:PageNavigator> e' il markup
     * UserControls/PageNavigator.php. La cartella E' la registrazione.
     */
    public const CARTELLA = 'UserControls';

    /**
     * @param array $nodi        nodi prodotti da PageParser::Parse()
     * @param array $tokens segnaposto {{Campo}} => valore, usati dentro un ItemTemplate
     * @return Control[]
     */
    public static function Build(array $nodi, ?Page $pagina, array $tokens = []): array
    {
        $controls = [];

        foreach ($nodi as $nodo)
        {
            if ($nodo['t'] === 'html')
            {
                $literal = new Literal();
                $literal->Text = self::Substitute($nodo['v'], $tokens, true);
                $literal->Mode = Literal::PASSTHROUGH;
                $literal->Page = $pagina;

                $controls[] = $literal;

                continue;
            }

            //un ItemTemplate fuori da un Repeater non ha un significato: si ignora invece di
            //renderizzarlo come se fosse contenuto
            if ($nodo['t'] !== 'ctl')
                continue;

            if ($nodo['tipo'] === 'UserControl')
            {
                $controls[] = self::BuildUserControl($nodo, $pagina, $tokens);

                continue;
            }

            $classe = __NAMESPACE__ . '\\Controls\\' . $nodo['tipo'];

            if (!class_exists($classe))
            {
                //non e' un controllo del motore: puo' essere un UserControl chiamato per
                //nome, <dw:PageNavigator>, che e' il modo in cui si scrivono in WK
                $src = self::SrcDiTag($nodo['tipo']);

                if ($src !== '')
                {
                    $nodo['attr']['src'] = $src;

                    $controls[] = self::BuildUserControl($nodo, $pagina, $tokens);

                    continue;
                }

                //markup con un controllo che non esiste: e' un errore dello sviluppatore e
                //deve vedersi, non sparire
                throw new \RuntimeException(
                    'Controllo <dw:' . $nodo['tipo'] . '> non riconosciuto: non e\' un controllo del '
                    . 'motore e non c\'e\' un UserControl ' . self::CARTELLA . '/' . $nodo['tipo'] . '.php.'
                );
            }

            /** @var Control $control */
            $control = new $classe();

            $attr = [];

            //valore GREZZO: finisce in una proprieta' del controllo, che lo escapa lui al
            //render. Escaparlo anche qui darebbe "L&#039;Oreal" dentro un Confirm o un
            //CommandArgument, cioe' l'entita' visibile all'utente invece dell'apostrofo.
            foreach ($nodo['attr'] as $chiave => $valore)
                $attr[$chiave] = self::Substitute($valore, $tokens, false);

            $control->Id     = $attr['id'] ?? '';
            $control->Page = $pagina;

            $control->ApplyAttributes($attr);

            //il Repeater si tiene il template invece di costruirlo: le righe nascono solo
            //quando ci sono i dati, e vanno ricostruite ad ogni postback dallo stato
            if ($control instanceof Controls\Repeater)
            {
                $control->ItemTemplate = self::ItemTemplateNodes($nodo['figli']);

                $controls[] = $control;

                continue;
            }

            foreach (self::Build($nodo['figli'], $pagina, $tokens) as $child)
                $control->Add($child);

            self::RaccogliVoci($control);

            $controls[] = $control;
        }

        return $controls;
    }

    /**
     * Costruisce la master e ci innesta dentro il contenuto della pagina.
     *
     * L'albero che ne esce ha la master alla radice e la pagina dentro i suoi segnaposto: e'
     * il rovescio di un UserControl, che invece sta dentro la pagina.
     *
     * DUE COSE CHE NON SI FANNO, e sono la ragione per cui questo metodo esiste invece di
     * riusare BuildUserControl:
     *
     * - NON si qualificano gli id. Di master ce n'e' una sola per pagina, quindi non c'e'
     *   niente da disambiguare, e txtFiltro deve restare txtFiltro: e' la regola su cui si
     *   regge il morph, ed e' quella che in WebForms la master rompeva.
     * - NON si costruisce il markup della pagina come figlio: i suoi nodi arrivano gia'
     *   divisi per segnaposto e si costruiscono dentro il segnaposto che li ospita.
     *
     * @param array $nodiPagina i nodi del markup della PAGINA, fatto di soli <dw:Content>
     */
    public static function BuildMaster(string $src, array $nodiPagina, Page $pagina): MasterPage
    {
        $markupFile = self::MarkupDi($src, 'master');

        $codeClass = '\\' . str_replace('/', '\\', $src);

        Designer::Update($markupFile, $codeClass);
        Designer::RequireCode($markupFile);

        if (!is_subclass_of($codeClass, MasterPage::class))
            throw new \RuntimeException($codeClass . ' deve estendere ' . MasterPage::class . '.');

        /** @var MasterPage $master */
        $master = new $codeClass();

        $master->Page = $pagina;

        foreach (self::Build(PageParser::Parse($markupFile), $pagina) as $child)
            $master->Add($child);

        $master->BindDesignerFields();

        $contenuti = self::Contenuti($nodiPagina);

        $riempiti = [];

        self::RiempiSegnaposti($master, $contenuti, $pagina, $riempiti);

        //un <dw:Content> che punta a un segnaposto che non esiste sparirebbe dalla pagina
        //senza che niente lo segnali: e' il tipo di errore che si scopre guardando una
        //pagina vuota e chiedendosi perche'
        $orfani = array_diff(array_keys($contenuti), $riempiti);

        if ($orfani !== [])
            throw new \RuntimeException(
                '<dw:Content placeholder="' . implode('", "', $orfani) . '">: la master ' . $src
                . ' non ha nessun <dw:ContentPlaceHolder> con quell\'id.'
            );

        return $master;
    }

    /**
     * I <dw:Content> della pagina, per id di segnaposto.
     *
     * Fuori dai Content ci puo' stare solo spazio bianco: in una pagina con master il resto
     * non avrebbe un posto dove finire, e lasciarlo sparire in silenzio sarebbe peggio che
     * dirlo.
     *
     * Pubblica perche' e' una funzione pura sui nodi e le prove la chiamano da sola: farla
     * passare da BuildMaster vorrebbe dire tirarsi dietro una master vera e una Page.
     */
    public static function Contenuti(array $nodi): array
    {
        $contenuti = [];

        foreach ($nodi as $nodo)
        {
            if ($nodo['t'] === 'html')
            {
                if (trim($nodo['v']) !== '')
                    throw new \RuntimeException(
                        'Una pagina con master puo\' contenere solo <dw:Content>, ma qui c\'e\' anche: '
                        . trim(preg_replace('/\s+/', ' ', substr(trim($nodo['v']), 0, 60)))
                    );

                continue;
            }

            if ($nodo['t'] !== 'ctl' || $nodo['tipo'] !== 'Content')
                continue;

            $segnaposto = $nodo['attr']['placeholder'] ?? '';

            if ($segnaposto === '')
                throw new \RuntimeException('<dw:Content> vuole l\'attributo placeholder.');

            $contenuti[$segnaposto] = $nodo['figli'];
        }

        return $contenuti;
    }

    /** @param string[] $riempiti id dei segnaposto che hanno ricevuto il contenuto della pagina */
    private static function RiempiSegnaposti(Control $control, array $contenuti, Page $pagina, array &$riempiti): void
    {
        if ($control instanceof Controls\ContentPlaceHolder && isset($contenuti[$control->Id]))
        {
            //quello che c'era dentro il segnaposto era il contenuto predefinito: la pagina
            //l'ha sostituito
            $control->Controls = [];

            foreach (self::Build($contenuti[$control->Id], $pagina) as $child)
                $control->Add($child);

            $riempiti[] = $control->Id;

            return;
        }

        foreach ($control->Controls as $child)
            self::RiempiSegnaposti($child, $contenuti, $pagina, $riempiti);
    }

    /**
     * Da "Layouts/Sito" al file del markup.
     *
     * Il percorso viene dal markup, non dalla richiesta, ma finisce in un include e in un
     * nome di classe: si accettano solo segmenti veri, mai ".." o barre rovesciate.
     */
    /**
     * Un UserControl si puo' chiamare per nome: <dw:PageNavigator id="pgSotto" PageSize="10" />
     * invece di <dw:UserControl src="UserControls/PageNavigator" ... />.
     *
     * E' la forma delle pagine WK - <Layout:PageNavigator runat="server" /> - senza dover
     * registrare niente in cima al file: la registrazione e' la CARTELLA. Un tag che non e' un
     * controllo del motore si cerca in UserControls/, e se il markup c'e' quello e'.
     *
     * I controlli del motore vincono sempre: un UserControl chiamato "Panel" resta
     * raggiungibile solo con src=, e va bene cosi' - il markup non deve poter cambiare
     * significato a un tag che tutti danno per scontato.
     *
     * @return string il src da usare, o stringa vuota se quel nome non e' un UserControl
     */
    public static function SrcDiTag(string $tipo): string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $tipo) !== 1)
            return '';

        //si guarda il FILE e non class_exists: quello chiamerebbe l'autoloader per ogni tag
        //che non e' del motore, e un autoloader chiamato per una classe che non esiste e' un
        //giro a vuoto per ogni <dw:Menu> di ogni pagina
        if (is_file(self::Radice() . '/Common/WebForms/Controls/' . $tipo . '.php'))
            return '';

        $src = self::CARTELLA . '/' . $tipo;

        return is_file(self::Radice() . '/' . $src . '.php') ? $src : '';
    }

    /**
     * La radice dei sorgenti php: questo file sta in Common/WebForms, due sopra c'e' lei.
     *
     * NON si passa da DOCUMENT_ROOT. Quello lo mette il server web, quindi da riga di comando
     * non c'e': il generatore del designer girava senza, non trovava piu' gli UserControl e
     * dichiarava tipi che non esistono - e la pagina moriva al primo caricamento con un
     * "Cannot assign UserControls\Menu to property of type Controls\Menu". Il percorso di
     * questo file invece c'e' sempre.
     */
    private static function Radice(): string
    {
        return str_replace('\\', '/', dirname(__DIR__, 2));
    }

    private static function MarkupDi(string $src, string $cosa): string
    {
        if (preg_match('#^[A-Za-z_][A-Za-z0-9_]*(/[A-Za-z_][A-Za-z0-9_]*)*$#', $src) !== 1)
            throw new \RuntimeException('Il ' . $cosa . ' vuole un src valido, ricevuto "' . $src . '".');

        $markupFile = self::Radice() . '/' . $src . '.php';

        if (!is_file($markupFile))
            throw new \RuntimeException('Il ' . $cosa . ' "' . $src . '": manca ' . $markupFile . '.');

        return $markupFile;
    }

    /**
     * I <dw:ListItem> dichiarati dentro un elenco diventano le sue Items e spariscono dai
     * figli.
     *
     * Cosi' da qui in poi le voci scritte nel markup e quelle assegnate dal codebehind sono
     * la stessa cosa: vivono in Items, attraversano lo stato allo stesso modo, e il resto
     * del controllo non deve sapere da dove venivano.
     */
    private static function RaccogliVoci(Control $control): void
    {
        if (!($control instanceof Controls\DropDownList) && !($control instanceof Controls\ListBox))
            return;

        $voci  = [];
        $altri = [];

        foreach ($control->Controls as $child)
        {
            if (!($child instanceof Controls\ListItem))
            {
                $altri[] = $child;
                continue;
            }

            $voci[$child->Value] = $child->Etichetta();

            if (!$child->Selected)
                continue;

            if ($control instanceof Controls\ListBox && $control->SelectionMode === Controls\ListBox::MULTIPLA)
                $control->SelectedValues[] = $child->Value;
            else
                $control->SelectedValue = $child->Value;
        }

        if ($voci === [])
            return;

        $control->Items    = $voci;
        $control->Controls = $altri;
    }

    /**
     * <dw:UserControl src="UserControls/PageNavigator" id="pgSopra" ... />
     *
     * Stessa convenzione delle pagine: markup in <src>.php, codebehind in <src>.code.php, classe
     * con le barre trasformate in namespace. Un file, un nome: non c'e' un
     * registro da tenere allineato.
     */
    private static function BuildUserControl(array $nodo, ?Page $pagina, array $tokens): UserControl
    {
        $src = $nodo['attr']['src'] ?? '';

        $markupFile = self::MarkupDi($src, '<dw:UserControl>');

        $codeClass = '\\' . str_replace('/', '\\', $src);

        //come per le pagine: il designer va scritto PRIMA che la classe lo usi come trait
        Designer::Update($markupFile, $codeClass);
        Designer::RequireCode($markupFile);

        if (!is_subclass_of($codeClass, UserControl::class))
            throw new \RuntimeException($codeClass . ' deve estendere ' . UserControl::class . '.');

        /** @var UserControl $control */
        $control = new $codeClass();

        $attr = [];

        foreach ($nodo['attr'] as $chiave => $valore)
            $attr[$chiave] = self::Substitute($valore, $tokens, false);

        $control->Id   = $attr['id'] ?? '';
        $control->Page = $pagina;

        if ($control->Id === '')
            throw new \RuntimeException('<dw:UserControl src="' . $src . '"> vuole un id: e\' la chiave con cui i suoi figli restano distinti.');

        //il controllo e' contenitore di denominazione di se stesso: cosi' al suo interno
        //FindControl('phLinks') trova il figlio anche se sull'HTML si chiama phLinks__pgSopra
        $control->NamingKey = $control->Id;

        $control->ApplyAttributes($attr);
        $control->BindHostHandlers($attr);

        //i figli nascono dal markup DEL CONTROLLO, non da quello della pagina
        foreach (self::Build(PageParser::Parse($markupFile), $pagina) as $child)
            $control->Add($child);

        //prima l'aggancio del designer, che usa gli id del markup...
        $control->BindDesignerFields();

        //...poi la qualifica: due paginatori nella stessa pagina hanno lo stesso markup e
        //quindi gli stessi id nel sorgente
        foreach ($control->Controls as $child)
            Control::QualifyIds($child, $control->Id);

        return $control;
    }

    /** I nodi dentro <ItemTemplate>; se manca, tutto il contenuto del Repeater. */
    private static function ItemTemplateNodes(array $figli): array
    {
        foreach ($figli as $nodo)
            if ($nodo['t'] === 'tpl')
                return $nodo['figli'];

        return $figli;
    }

    /**
     * I segnaposto {{Campo}} di un ItemTemplate.
     *
     * Non e' un motore di espressioni, ed e' voluto: qui ci va la presentazione di un campo,
     * non logica.
     *
     * $escape distingue i due contesti. Nel markup letterale il valore finisce dritto
     * nell'HTML e va escapato qui; in un attributo finisce in una proprieta' del controllo,
     * che lo escapa al render, e farlo due volte mostrerebbe le entita' all'utente.
     */
    private static function Substitute(string $testo, array $tokens, bool $escape): string
    {
        if ($tokens === [] || !str_contains($testo, '{{'))
            return $testo;

        return preg_replace_callback(
            '/\{\{\s*([A-Za-z_][A-Za-z0-9_]*)\s*}}/',
            static function (array $m) use ($tokens, $escape): string
            {
                $valore = (string)($tokens[$m[1]] ?? '');

                return $escape ? Control::HtmlEncode($valore) : $valore;
            },
            $testo
        );
    }
}
