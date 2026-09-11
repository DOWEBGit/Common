<?php
declare(strict_types=1);

namespace Common\WebForms;

/**
 * Genera il file designer: le dichiarazioni tipizzate dei controlli dichiarati nel markup.
 *
 * Non serve a far funzionare la pagina - i controlli vengono agganciati per id comunque -
 * ma a far sapere all'IDE che $this->txtFiltro e' un TextBox, cosi' completa i membri e
 * segnala i refusi prima di aprire il browser.
 *
 * Si rigenera da solo quando il markup e' piu' recente. In produzione il file c'e' gia' e
 * questo controllo costa una filemtime.
 */
class Designer
{
    /**
     * I tre file di una pagina o di un UserControl, sul modello di WebForms:
     *
     *   Brand.php            markup, ed e' l'URL     (l'analogo di Brand.aspx)
     *   Brand.code.php       codebehind              (Brand.aspx.cs)
     *   Brand.designer.php   generato                (Brand.aspx.designer.cs)
     *
     * Codebehind e designer NON passano dall'autoloader - il loro nome non e' un nome di
     * classe - e vengono inclusi per percorso da chi apre la pagina. Il vantaggio e' che la
     * classe puo' chiamarsi \WebForms\Brand invece di \WebForms\BrandCode.
     */
    public static function CodePath(string $markupFile): string
    {
        return dirname($markupFile) . DIRECTORY_SEPARATOR . basename($markupFile, '.php') . '.code.php';
    }

    public static function DesignerPath(string $markupFile): string
    {
        return dirname($markupFile) . DIRECTORY_SEPARATOR . basename($markupFile, '.php') . '.designer.php';
    }

    /**
     * Include designer e codebehind, nell'ordine che conta: il designer e' un trait che la
     * classe usa, quindi deve essere gia' caricato quando la classe si compila.
     */
    public static function RequireCode(string $markupFile): void
    {
        $designer = self::DesignerPath($markupFile);

        if (is_file($designer))
            require_once $designer;

        $code = self::CodePath($markupFile);

        if (!is_file($code))
            throw new \RuntimeException('Manca il codebehind ' . basename($code) . ' accanto a ' . basename($markupFile) . '.');

        require_once $code;
    }

    public static function Update(string $markupFile, string $codeClass, string $masterSrc = ''): void
    {
        $spazio = self::NamespaceOf($codeClass);
        $nome   = basename($markupFile, '.php');

        $file = self::DesignerPath($markupFile);

        //si rigenera quando il markup e' piu' recente, MA ANCHE quando lo e' il generatore.
        //
        //Senza la seconda condizione un designer scritto ieri resta li' per sempre, e se nel
        //frattempo e' cambiata una regola su come si ricava il TIPO di un controllo, quel file
        //dichiara un tipo che non esiste piu': la pagina muore con un "Cannot assign
        //UserControls\PageNavigator to property of type Controls\PageNavigator", e chi legge
        //il markup non trova niente di sbagliato. Successo davvero.
        $quando = max(
            filemtime($markupFile),
            filemtime(__FILE__),
            filemtime(__DIR__ . DIRECTORY_SEPARATOR . 'ControlBuilder.php')
        );

        //Un designer piu' recente del markup e del generatore di solito e' buono. Non sempre:
        //lo scrive anche il plugin di PhpStorm, e se il plugin installato e' vecchio scrive
        //tipi sbagliati - Controls\PageNavigator per un UserControl - DOPO che il motore
        //aveva scritto quelli giusti. La data lo direbbe fresco e la pagina morirebbe al
        //primo caricamento. Quindi la data taglia il grosso, e il CONTENUTO decide: se un
        //tipo dichiarato non esiste, si rigenera. E' il motore la fonte della verita'.
        if (is_file($file) && filemtime($file) >= $quando && self::TipiEsistono($file))
            return;

        $declarations = [];
        $handlers     = [];

        self::Collect(PageParser::Parse($markupFile), $declarations, $handlers);

        $corpo = '';

        //la master si dichiara per prima, e tipizzata con la SUA classe: e' li' che stanno
        //le proprieta' che l'IDE deve suggerire quando si scrive $this->Master->litTitolo
        if ($masterSrc !== '')
            $corpo .= '    public \\' . str_replace('/', '\\', $masterSrc) . ' $Master;' . "\n";

        foreach ($declarations as $id => $tipo)
            $corpo .= '    public ' . $tipo . ' $' . $id . ';' . "\n";

        //Gli @see rendono cliccabili dall'IDE gli handler nominati nel markup e - cosa che
        //conta di piu' - fanno segnalare in rosso quelli che NON esistono: un refuso in
        //OnClick="DeletRow" si vede qui invece che al primo click dell'utente.
        $vedi = '';

        foreach (array_keys($handlers) as $metodo)
            //barra iniziale obbligatoria: il designer sta nello stesso namespace della
            //classe, e "WebForms\Brand" li' dentro significherebbe "WebForms\WebForms\Brand"
            $vedi .= ' * @see \\' . ltrim($codeClass, '\\') . '::' . $metodo . '()' . "\n";

        $intestazione = "/**\n * GENERATO AUTOMATICAMENTE da " . basename($markupFile) . " - non modificare a mano.\n";

        if ($vedi !== '')
            $intestazione .= " *\n * Handler richiamati dal markup:\n" . $vedi;

        $intestazione .= " */\n";

        $testo = "<?php\ndeclare(strict_types=1);\n\n"
            . "namespace " . $spazio . ";\n\n"
            . $intestazione
            . "trait " . $nome . "Designer\n{\n" . $corpo . "}\n";

        //si riscrive solo se e' davvero cambiato, altrimenti ogni richiesta tocca la data
        //del file e in cartelle sincronizzate diventa traffico inutile
        if (is_file($file) && file_get_contents($file) === $testo)
        {
            @touch($file);
            return;
        }

        @file_put_contents($file, $testo);
    }

    /**
     * Ogni classe del motore dichiarata nel designer esiste davvero?
     *
     * Con un is_file e non con class_exists: quello chiamerebbe l'autoloader per ogni riga
     * di ogni designer ad ogni richiesta.
     */
    private static function TipiEsistono(string $file): bool
    {
        $testo = (string)@file_get_contents($file);

        if (preg_match_all('/public \\\\Common\\\\WebForms\\\\Controls\\\\([A-Za-z_][A-Za-z0-9_]*) \$/', $testo, $m) === 0)
            return true;

        foreach ($m[1] as $classe)
            if (!is_file(__DIR__ . '/Controls/' . $classe . '.php'))
                return false;

        return true;
    }

    private static function Collect(array $nodi, array &$declarations, array &$handlers, bool $dentroTemplate = false): void
    {
        foreach ($nodi as $nodo)
        {
            if ($nodo['t'] === 'html')
                continue;

            //i controlli dentro un ItemTemplate esistono una volta per riga e con id
            //suffissato: non sono raggiungibili per nome e non vanno dichiarati. I loro
            //handler pero' vanno raccolti lo stesso: sono metodi della pagina come gli altri.
            if ($nodo['t'] === 'tpl')
            {
                self::Collect($nodo['figli'], $declarations, $handlers, true);
                continue;
            }

            if (!$dentroTemplate && ($nodo['id'] ?? '') !== '')
                $declarations[$nodo['id']] = self::TypeOf($nodo);

            foreach ($nodo['attr'] as $chiave => $valore)
                if (str_starts_with($chiave, 'On') && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $valore) === 1)
                    $handlers[$valore] = true;

            self::Collect($nodo['figli'], $declarations, $handlers, $dentroTemplate);
        }
    }

    /**
     * Un UserControl non e' tipizzato con la classe base ma con il SUO codebehind: e' li'
     * che stanno le proprieta' che l'IDE deve suggerire (TotalItems, CurrentPage, ...).
     */
    private static function TypeOf(array $nodo): string
    {
        $src = $nodo['tipo'] === 'UserControl'
            ? ($nodo['attr']['src'] ?? '')
            //un UserControl chiamato per nome: <dw:PageNavigator> e' UserControls/PageNavigator,
            //e nel designer va dichiarato con la SUA classe, non con una del motore che non
            //esiste
            : ControlBuilder::TagSrc($nodo['tipo']);

        if ($src === '')
            return '\\Common\\WebForms\\Controls\\' . $nodo['tipo'];

        return '\\' . str_replace('/', '\\', $src);
    }

    private static function NamespaceOf(string $classe): string
    {
        //la classe puo' arrivare con la barra iniziale: "namespace \Foo;" non compila
        $classe = ltrim($classe, '\\');

        $pos = strrpos($classe, '\\');

        return $pos === false ? '' : substr($classe, 0, $pos);
    }
}
