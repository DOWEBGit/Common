<?php
declare(strict_types=1);

namespace Common\WebForms\Controls;

use Common\WebForms\Control;

/**
 * Un editor di testo formattato, alla maniera di Word: grassetto, colori, interlinea,
 * titoli, elenchi, link, tabelle.
 *
 *     <dw:RichTextBox id="__RichTextBox_Descrizione" MinHeight="220" Placeholder="Scrivi qui..."
 *                     EnableTable="false" EnableFontName="false" ShowCounter="true" MaxLength="2000" />
 *
 *     $this->__RichTextBox_Descrizione->Text = $articolo->Descrizione;   // HTML
 *     $html  = $this->__RichTextBox_Descrizione->Text;                    // HTML gia' ripulito
 *     $testo = $this->__RichTextBox_Descrizione->PlainText();             // solo il testo
 *
 * OGNI FUNZIONE SI ACCENDE E SI SPEGNE DA SOLA, con la sua proprieta' Enable...: spenta, il
 * bottone non c'e' E il server non la lascia passare. Il secondo pezzo e' quello che conta:
 * senza, una tabella incollata da Word o scritta dalla console entrerebbe lo stesso in un
 * campo che le tabelle non le vuole. Quello che non si puo' fare dalla barra, non si puo' fare.
 *
 * IL TESTO CHE ARRIVA DAL BROWSER E' HTML, e l'HTML di un utente e' la porta dell'XSS. Qui non
 * si "tolgono le cose pericolose": si ricostruisce l'HTML da zero tenendo SOLO quello che sta
 * nell'elenco - pochi tag, un solo attributo di stile con poche proprieta' e valori
 * controllati uno per uno, i link solo http, https, mailto e tel. Tutto il resto cade, e quello
 * che non si conosce cade per definizione: un tag nuovo inventato dai browser fra due anni non
 * passa finche' qualcuno non lo aggiunge qui. Il parser e' quello HTML5 di PHP 8.4, lo stesso
 * algoritmo dei browser: quello che il browser vedrebbe e' quello che qui si pulisce.
 *
 * Il pezzo che gira nel browser - la barra, i menu, la cronologia, l'incolla ripulito - sta in
 * RichTextBox/RichTextBox.js, e lo carica il motore solo sulle pagine che hanno un editor.
 */
class RichTextBox extends Control
{
    /** Il contenuto, in HTML. Arrivando dal browser e' gia' ripulito secondo le funzioni accese. */
    public string $Text = '';

    /** Il suggerimento nell'editor vuoto. */
    public string $Placeholder = '';

    /** L'altezza minima dell'area di scrittura, in pixel. */
    public int $MinHeight = 180;

    /** L'altezza massima in pixel, poi l'area scorre. 0 = cresce con il testo. */
    public int $MaxHeight = 0;

    /**
     * Quanti caratteri di TESTO al massimo - la formattazione non conta. 0 = senza limite.
     * Il browser non lascia scrivere oltre; il server lo ricontrolla con LengthExceeded().
     */
    public int $MaxLength = 0;

    /** Mostra sotto l'editor il conto di parole e caratteri. */
    public bool $ShowCounter = false;

    /** Spento, l'editor si legge e non si scrive; il server ignora quello che arriva. */
    public bool $Enabled = true;

    /** Se true il cambio di testo fa partire un postback: all'uscita, o dopo AutoPostBackDelay. */
    public bool $AutoPostBack = false;

    /** Millisecondi di quiete prima del postback mentre si scrive. 0 = all'uscita dall'editor. */
    public int $AutoPostBackDelay = 0;

    /** Il nome del metodo del codebehind che gira quando il testo cambia, con AutoPostBack. */
    public string $OnTextChanged = '';

    /**
     * I caratteri che si possono scegliere, separati da punto e virgola. Sono anche gli UNICI
     * che il server lascia passare: un Calibri incollato da Word cade e il testo prende quello
     * della pagina.
     */
    public string $FontNames = 'Arial;Georgia;Times New Roman;Verdana;Tahoma;Trebuchet MS;Courier New';

    /** Le dimensioni del menu, in pixel, separate da virgola. */
    public string $FontSizes = '10,12,14,16,18,20,24,28,32,40';

    /** La tavolozza dei due menu colore, separati da virgola. Vuota = quella predefinita. */
    public string $Colors = '';

    /** Annulla e Ripeti, anche con Ctrl+Z e Ctrl+Y. */
    public bool $EnableUndoRedo = true;
    /** Paragrafo, Titolo 1-4. */
    public bool $EnableHeadings = true;
    /** Il tipo di carattere, fra quelli di FontNames. */
    public bool $EnableFontName = true;
    /** La dimensione del carattere, fra quelle di FontSizes. */
    public bool $EnableFontSize = true;
    /** Grassetto, Ctrl+B. */
    public bool $EnableBold = true;
    /** Corsivo, Ctrl+I. */
    public bool $EnableItalic = true;
    /** Sottolineato, Ctrl+U. */
    public bool $EnableUnderline = true;
    /** Barrato. */
    public bool $EnableStrikethrough = true;
    /** Apice. */
    public bool $EnableSuperscript = true;
    /** Pedice. */
    public bool $EnableSubscript = true;
    /** Il colore del testo. */
    public bool $EnableForeColor = true;
    /** L'evidenziatore: il colore dietro il testo. */
    public bool $EnableBackColor = true;
    /** Allineamento a sinistra, centro, destra, giustificato (Ctrl+L, E, R, J). */
    public bool $EnableAlign = true;
    /** L'interlinea del paragrafo: 1, 1,15, 1,5, 2... */
    public bool $EnableLineHeight = true;
    /** L'elenco puntato, Ctrl+Maiusc+8. */
    public bool $EnableBulletedList = true;
    /** L'elenco numerato, Ctrl+Maiusc+7. */
    public bool $EnableNumberedList = true;
    /** Aumenta e riduci rientro; negli elenchi anche Tab e Maiusc+Tab. */
    public bool $EnableIndent = true;
    /** La citazione. */
    public bool $EnableQuote = true;
    /** La linea orizzontale. */
    public bool $EnableHorizontalRule = true;
    /** Inserisci e togli collegamento, Ctrl+K. Solo http, https, mailto e tel. */
    public bool $EnableLink = true;
    /** Le tabelle, con righe e colonne da aggiungere e togliere. */
    public bool $EnableTable = true;
    /** Il pulsante che toglie la formattazione dalla selezione. */
    public bool $EnableClearFormatting = true;
    /** Il pulsante che mostra e fa modificare l'HTML. Ripulito comunque, come il resto. */
    public bool $EnableSourceView = false;
    /** Il pulsante che allarga l'editor a tutto lo schermo: sul telefono e' quello che serve. */
    public bool $EnableFullScreen = true;

    /**
     * Le funzioni, per nome: e' l'elenco che leggono EnableOnly() e SetAll(), e l'ordine in cui
     * i bottoni stanno nella barra. Un nome e' la proprieta' senza "Enable".
     */
    public const array FEATURES = [
        'UndoRedo', 'Headings', 'FontName', 'FontSize',
        'Bold', 'Italic', 'Underline', 'Strikethrough', 'Superscript', 'Subscript',
        'ForeColor', 'BackColor',
        'Align', 'LineHeight', 'BulletedList', 'NumberedList', 'Indent', 'Quote', 'HorizontalRule',
        'Link', 'Table',
        'ClearFormatting', 'SourceView', 'FullScreen',
    ];

    /** Il codice di chi c'e' nel browser: lo carica runtime.js alla prima pagina che ne ha bisogno. */
    private const string JS = 'Common/WebForms/RichTextBox/RichTextBox.js';
    /** Lo stile, icone comprese: sono maschere SVG dentro il CSS, quindi un file solo e in cache. */
    private const string CSS = 'Common/WebForms/RichTextBox/RichTextBox.css';

    /** La tavolozza se Colors e' vuota, otto per riga come la griglia: i grigi, i pieni, e tre sfumature. */
    private const string COLORI = '#000000,#434343,#666666,#999999,#b7b7b7,#d9d9d9,#efefef,#ffffff,'
        . '#ff0000,#ff9900,#ffff00,#00ff00,#00ffff,#0000ff,#9900ff,#ff00ff,'
        . '#f4cccc,#fce5cd,#fff2cc,#d9ead3,#d0e0e3,#cfe2f3,#d9d2e9,#ead1dc,'
        . '#e06666,#f6b26b,#ffd966,#93c47d,#76a5af,#6fa8dc,#8e7cc3,#c27ba0,'
        . '#990000,#b45f06,#bf9000,#38761d,#134f5c,#0b5394,#351c75,#741b47';

    protected function ViewStateProperties(): array
    {
        return array_merge(
            parent::ViewStateProperties(),
            ['Text', 'Placeholder', 'MinHeight', 'MaxHeight', 'MaxLength', 'ShowCounter', 'Enabled',
             'AutoPostBack', 'AutoPostBackDelay', 'OnTextChanged', 'FontNames', 'FontSizes', 'Colors'],
            array_map(static fn(string $f): string => 'Enable' . $f, self::FEATURES)
        );
    }

    /**
     * Accende solo le funzioni nominate e spegne tutte le altre.
     *
     *     $this->__RichTextBox_Nota->EnableOnly('Bold', 'Italic', 'Link');
     *
     * Un nome che non esiste si ferma qui: un refuso non deve diventare una barra vuota.
     */
    public function EnableOnly(string ...$funzioni): void
    {
        $this->SetAll(false);

        foreach ($funzioni as $funzione)
            $this->{'Enable' . self::Funzione($funzione)} = true;
    }

    /** Accende o spegne tutte le funzioni insieme. */
    public function SetAll(bool $accese): void
    {
        foreach (self::FEATURES as $funzione)
            $this->{'Enable' . $funzione} = $accese;
    }

    /** Se la funzione e' accesa, per nome: IsEnabled('Table'). */
    public function IsEnabled(string $funzione): bool
    {
        return $this->{'Enable' . self::Funzione($funzione)};
    }

    /** Il nome canonico di una funzione, senza badare alle maiuscole. */
    private static function Funzione(string $nome): string
    {
        foreach (self::FEATURES as $funzione)
            if (strcasecmp($funzione, $nome) === 0)
                return $funzione;

        throw new \RuntimeException('RichTextBox: la funzione "' . $nome . '" non esiste. Sono: ' . implode(', ', self::FEATURES) . '.');
    }

    /** Il testo senza formattazione: quello che si legge, a capo compresi. */
    public function PlainText(): string
    {
        return self::TestoDi($this->Text);
    }

    /** Il testo supera MaxLength? Il browser lo impedisce, ma quello che arriva va ricontrollato. */
    public function LengthExceeded(): bool
    {
        return $this->MaxLength > 0 && mb_strlen(self::TestoDi($this->Text)) > $this->MaxLength;
    }

    public function LoadPostData(array $post): void
    {
        //spento non scrive: il campo nel browser e' disabled e non viaggia, ma quello che
        //arriva lo stesso - dalla console, da uno script - non deve entrare
        if (!$this->Enabled || !array_key_exists($this->Id, $post))
            return;

        $this->Text = self::Sanitize((string)$post[$this->Id], $this->Rules());
    }

    public function RaisePostBackEvent(string $evento, string $argomento): void
    {
        if (($evento === 'change' || $evento === 'input') && $this->Enabled && $this->OnTextChanged !== '')
            $this->Page->InvokeHandler($this->OnTextChanged, $this, $argomento);
    }

    public function Render(): string
    {
        //in pagina esce SEMPRE ripulito, anche quello che ha scritto il codice: con tutte le
        //funzioni pero', perche' spegnere un bottone non deve far sparire quello che il
        //programma ha messo apposta - la regola delle funzioni vale per quello che scrive l'utente
        $html = self::Sanitize($this->Text, self::Rules(true));

        $area = $this->Id . '__area';

        $out = '<div' . $this->RenderAttributes('dw-rte')
            . ' data-dw-rte="' . self::HtmlEncode(json_encode($this->Configurazione(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '"'
            . ' data-dw-rte-js="' . self::HtmlEncode(StaticResource::Url(self::JS, '<dw:RichTextBox>')) . '"';

        if ($this->AutoPostBack && $this->Enabled)
            $out .= $this->AutoPostBackDelay > 0
                ? $this->PostBackAttribute('input') . ' data-dw-delay="' . $this->AutoPostBackDelay . '"'
                : $this->PostBackAttribute('change');

        //lo stile sta DENTRO il controllo e non nella testa: cosi' arriva anche con un editor
        //comparso a un postback, e prima che la barra si veda - le icone sono nel CSS, e senza
        //i bottoni sarebbero vuoti. Due editor fanno due <link> uguali: la cache ne scarica uno
        $out .= '><link rel="stylesheet" href="' . self::HtmlEncode(StaticResource::Url(self::CSS, '<dw:RichTextBox>')) . '">';

        $barra = $this->Barra();

        if ($barra !== '')
            $out .= '<div class="dw-rte-barra" role="toolbar" aria-label="Formattazione" aria-controls="' . self::HtmlEncode($area) . '">' . $barra . '</div>';

        //dw-preserve: quello che c'e' dentro e' del browser mentre si scrive, e il morph non lo
        //deve toccare. Quando il server cambia il testo lo rimette il runtime dell'editor, che
        //sa se l'utente sta scrivendo o no
        $stile = 'min-height:' . max(40, $this->MinHeight) . 'px' . ($this->MaxHeight > 0 ? ';max-height:' . max(40, $this->MaxHeight) . 'px' : '');

        $out .= '<div id="' . self::HtmlEncode($area) . '" class="dw-rte-area" dw-preserve role="textbox" aria-multiline="true"'
            . ' contenteditable="' . ($this->Enabled ? 'true' : 'false') . '"'
            . ($this->Placeholder !== '' ? ' data-placeholder="' . self::HtmlEncode($this->Placeholder) . '" aria-placeholder="' . self::HtmlEncode($this->Placeholder) . '"' : '')
            . ' style="' . $stile . '">' . $html . '</div>';

        if ($this->ShowCounter || $this->MaxLength > 0)
            $out .= '<div class="dw-rte-piede" aria-live="polite"><span class="dw-rte-conto">'
                . self::HtmlEncode(self::Conto(self::TestoDi($html), $this->MaxLength)) . '</span></div>';

        //il valore viaggia qui: un campo nascosto e' raccolto dal postback come gli altri, e il
        //morph gli rimette il valore del server senza badare al fuoco
        $out .= '<input type="hidden" name="' . self::HtmlEncode($this->Id) . '" value="' . self::HtmlEncode($html) . '"'
            . ($this->Enabled ? '' : ' disabled') . '>';

        return $out . '</div>';
    }

    /** Quello che il browser deve sapere e che non e' la barra: lo legge RichTextBox.js. */
    private function Configurazione(): array
    {
        return [
            'placeholder' => $this->Placeholder,
            'minHeight'   => max(40, $this->MinHeight),
            'maxHeight'   => $this->MaxHeight > 0 ? max(40, $this->MaxHeight) : 0,
            'maxLength'   => max(0, $this->MaxLength),
            'counter'     => $this->ShowCounter || $this->MaxLength > 0,
            'enabled'     => $this->Enabled,
            'features'    => array_values(array_filter(self::FEATURES, fn(string $f): bool => $this->{'Enable' . $f})),
            'fontNames'   => self::Elenco($this->FontNames, ';'),
            'fontSizes'   => array_values(array_filter(array_map('intval', self::Elenco($this->FontSizes, ',')), static fn(int $n): bool => $n >= 6 && $n <= 96)),
            'colors'      => array_values(array_filter(self::Elenco($this->Colors !== '' ? $this->Colors : self::COLORI, ','), self::ColoreValido(...))),
            'rules'       => $this->Rules(),
        ];
    }

    /** @return string[] */
    private static function Elenco(string $testo, string $separatore): array
    {
        return array_values(array_filter(array_map('trim', explode($separatore, $testo)), static fn(string $v): bool => $v !== ''));
    }

    // ------------------------------------------------------------------ la barra

    /** I bottoni, a gruppi: fra un gruppo e l'altro un separatore. Vuota se non c'e' niente di acceso. */
    private function Barra(): string
    {
        $gruppi = [
            [['UndoRedo', 'undo', 'undo', 'Annulla (Ctrl+Z)'], ['UndoRedo', 'redo', 'redo', 'Ripeti (Ctrl+Y)']],
            [['Headings', 'menu:heading', 'heading', 'Stile del paragrafo'],
             ['FontName', 'menu:fontname', 'fontname', 'Tipo di carattere'],
             ['FontSize', 'menu:fontsize', 'fontsize', 'Dimensione del carattere']],
            [['Bold', 'bold', 'bold', 'Grassetto (Ctrl+B)'],
             ['Italic', 'italic', 'italic', 'Corsivo (Ctrl+I)'],
             ['Underline', 'underline', 'underline', 'Sottolineato (Ctrl+U)'],
             ['Strikethrough', 'strikethrough', 'strikethrough', 'Barrato'],
             ['Superscript', 'superscript', 'superscript', 'Apice'],
             ['Subscript', 'subscript', 'subscript', 'Pedice']],
            [['ForeColor', 'menu:forecolor', 'forecolor', 'Colore del testo'],
             ['BackColor', 'menu:backcolor', 'backcolor', 'Evidenziatore']],
            [['Align', 'alignleft', 'alignleft', 'Allinea a sinistra (Ctrl+L)'],
             ['Align', 'aligncenter', 'aligncenter', 'Centra (Ctrl+E)'],
             ['Align', 'alignright', 'alignright', 'Allinea a destra (Ctrl+R)'],
             ['Align', 'alignjustify', 'alignjustify', 'Giustifica (Ctrl+J)'],
             ['LineHeight', 'menu:lineheight', 'lineheight', 'Interlinea']],
            [['BulletedList', 'bulletedlist', 'bulletedlist', 'Elenco puntato (Ctrl+Maiusc+8)'],
             ['NumberedList', 'numberedlist', 'numberedlist', 'Elenco numerato (Ctrl+Maiusc+7)'],
             ['Indent', 'outdent', 'outdent', 'Riduci rientro (Maiusc+Tab)'],
             ['Indent', 'indent', 'indent', 'Aumenta rientro (Tab)'],
             ['Quote', 'quote', 'quote', 'Citazione'],
             ['HorizontalRule', 'horizontalrule', 'horizontalrule', 'Linea orizzontale']],
            [['Link', 'menu:link', 'link', 'Collegamento (Ctrl+K)'],
             ['Table', 'menu:table', 'table', 'Tabella']],
            [['ClearFormatting', 'clearformatting', 'clearformatting', 'Cancella formattazione'],
             ['SourceView', 'source', 'source', 'HTML'],
             ['FullScreen', 'fullscreen', 'fullscreen', 'Schermo intero (Esc per uscire)']],
        ];

        $html = [];

        foreach ($gruppi as $gruppo)
        {
            $bottoni = '';

            foreach ($gruppo as [$funzione, $comando, $icona, $titolo])
            {
                if (!$this->{'Enable' . $funzione})
                    continue;

                $menu = str_starts_with($comando, 'menu:');

                $bottoni .= '<button type="button" class="dw-rte-b"'
                    . ($menu ? ' data-rte-menu="' . substr($comando, 5) . '" aria-haspopup="true" aria-expanded="false"' : ' data-rte="' . $comando . '"')
                    . ' title="' . self::HtmlEncode($titolo) . '" aria-label="' . self::HtmlEncode($titolo) . '"'
                    . ($this->Enabled ? '' : ' disabled') . '>'
                    . self::Icona($icona)
                    //lo schermo intero ha due facce: le mostra il CSS, secondo la classe che il
                    //browser mette sull'editor - una classe js- sopravvive al morph
                    . ($comando === 'fullscreen' ? self::Icona('exitfullscreen') : '')
                    . ($menu ? '<span class="dw-rte-freccia" aria-hidden="true"></span>' : '')
                    . '</button>';
            }

            if ($bottoni !== '')
                $html[] = '<span class="dw-rte-gruppo">' . $bottoni . '</span>';
        }

        return implode('', $html);
    }

    /** L'icona e' una maschera nel CSS: qui c'e' solo il posto dove disegnarla. */
    private static function Icona(string $nome): string
    {
        return '<span class="dw-rte-i dw-rte-i-' . $nome . '" aria-hidden="true"></span>';
    }

    /** "12 parole, 80 caratteri", o "80 / 2000 caratteri" con il limite. */
    private static function Conto(string $testo, int $massimo): string
    {
        $caratteri = mb_strlen($testo);

        $parole = preg_match_all('/[\p{L}\p{N}]+(?:[\'’.,-][\p{L}\p{N}]+)*/u', $testo);

        return $parole . ($parole === 1 ? ' parola' : ' parole') . ', '
            . ($massimo > 0 ? $caratteri . ' / ' . $massimo : $caratteri) . ($caratteri === 1 && $massimo === 0 ? ' carattere' : ' caratteri');
    }

    // ------------------------------------------------------------------ le regole

    /**
     * Cosa passa, secondo le funzioni accese. E' la stessa regola che il browser usa per
     * l'incolla - gliela si manda in configurazione - cosi' quello che si incolla e' gia' quello
     * che il server terra', invece di cambiare aspetto al primo postback.
     *
     * @param bool $tutte ignora le funzioni: e' la regola di sicurezza, quella del render
     * @return array{tags:array<string,string[]>,rename:array<string,string>,styles:string[],fonts:string[]}
     */
    public function Rules(bool $tutte = false): array
    {
        $acceso = fn(string $f): bool => $tutte || $this->{'Enable' . $f};

        //quello che c'e' sempre: il paragrafo e l'a capo
        $tags   = ['p' => [], 'br' => [], 'span' => []];
        $rename = ['div' => 'p'];
        $styles = [];

        $inline = [
            'Bold'          => ['strong', ['b' => 'strong'], ['font-weight']],
            'Italic'        => ['em', ['i' => 'em', 'cite' => 'em', 'dfn' => 'em'], ['font-style']],
            'Underline'     => ['u', ['ins' => 'u'], ['text-decoration']],
            'Strikethrough' => ['s', ['strike' => 's', 'del' => 's'], ['text-decoration']],
            'Superscript'   => ['sup', [], ['vertical-align']],
            'Subscript'     => ['sub', [], ['vertical-align']],
        ];

        foreach ($inline as $funzione => [$tag, $alias, $stili])
        {
            if (!$acceso($funzione))
                continue;

            $tags[$tag] = [];
            $rename += $alias;
            $styles = array_merge($styles, $stili);
        }

        if ($acceso('ForeColor'))
            $styles[] = 'color';

        if ($acceso('BackColor'))
            $styles[] = 'background-color';

        if ($acceso('FontSize'))
            $styles[] = 'font-size';

        if ($acceso('FontName'))
            $styles[] = 'font-family';

        if ($acceso('Align'))
            $styles[] = 'text-align';

        if ($acceso('LineHeight'))
            $styles[] = 'line-height';

        if ($acceso('Indent'))
            $styles[] = 'margin-left';

        if ($acceso('Headings'))
        {
            $tags += ['h1' => [], 'h2' => [], 'h3' => [], 'h4' => []];
            $rename += ['h5' => 'h4', 'h6' => 'h4'];
        }
        else
            $rename += ['h1' => 'p', 'h2' => 'p', 'h3' => 'p', 'h4' => 'p', 'h5' => 'p', 'h6' => 'p'];

        if ($acceso('BulletedList'))
            $tags['ul'] = [];

        if ($acceso('NumberedList'))
            $tags['ol'] = [];

        //un elenco spento lascia le sue voci come paragrafi: il testo resta, i pallini no
        if ($acceso('BulletedList') || $acceso('NumberedList'))
            $tags['li'] = [];
        else
            $rename['li'] = 'p';

        if ($acceso('Quote'))
            $tags['blockquote'] = [];

        if ($acceso('HorizontalRule'))
            $tags['hr'] = [];

        if ($acceso('Link'))
            $tags['a'] = ['href', 'target'];

        //la tabella spenta lascia le celle come paragrafi, e il resto della struttura si scioglie
        if ($acceso('Table'))
        {
            $tags += ['table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => [], 'td' => []];
            $rename['tfoot'] = 'tbody';
        }
        else
            $rename += ['td' => 'p', 'th' => 'p'];

        //i caratteri che si possono scegliere sono gli unici che passano: quelli della
        //configurazione anche quando si ripulisce con tutte le funzioni, perche' il render non
        //ha un altro elenco a cui chiedere
        $fonts = in_array('font-family', $styles, true) ? self::Elenco($this->FontNames, ';') : [];

        return ['tags' => $tags, 'rename' => $rename, 'styles' => array_values(array_unique($styles)), 'fonts' => $fonts];
    }

    /**
     * Ripulisce un HTML secondo le regole: quello che non e' nell'elenco non esce.
     *
     * E' pubblica e statica perche' serve anche fuori da una pagina - un testo arrivato da
     * un'API, da un import - e deve passare dalla stessa porta:
     *
     *     $pulito = RichTextBox::Sanitize($html, (new RichTextBox())->Rules());
     *
     * @param array{tags:array<string,string[]>,rename:array<string,string>,styles:string[],fonts:string[]} $regole
     */
    public static function Sanitize(string $html, array $regole): string
    {
        if (trim($html) === '')
            return '';

        $documento = \Dom\HTMLDocument::createFromString('<!DOCTYPE html><html><body>' . $html . '</body></html>', LIBXML_NOERROR);

        $out = self::Figli($documento->body, $regole);

        //un editor svuotato lascia quasi sempre un paragrafo con dentro un a capo: e' vuoto, e
        //deve essere vuoto per chi lo salva e per chi controlla se l'utente ha scritto qualcosa
        return self::TestoDi($out) === '' && !preg_match('/<(hr|table)\b/', $out) ? '' : $out;
    }

    /** I tag che se ne vanno CON il contenuto: il loro testo non e' testo per chi legge. */
    private const array VIA = [
        'script', 'style', 'template', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet',
        'noscript', 'head', 'title', 'meta', 'link', 'base', 'svg', 'math', 'canvas', 'audio',
        'video', 'source', 'track', 'picture', 'img', 'map', 'area', 'input', 'button', 'select',
        'option', 'optgroup', 'textarea', 'datalist', 'output', 'progress', 'meter', 'dialog',
        'colgroup', 'col', 'caption', 'xml', 'param', 'slot', 'portal',
    ];

    /** Gli elementi che non hanno chiusura. */
    private const array VUOTI = ['br', 'hr'];

    private static function Figli(\Dom\Node $padre, array $regole): string
    {
        $out = '';

        foreach ($padre->childNodes as $nodo)
            $out .= self::Nodo($nodo, $regole);

        return $out;
    }

    private static function Nodo(\Dom\Node $nodo, array $regole): string
    {
        if ($nodo instanceof \Dom\Text)
            //nel testo le virgolette e gli apostrofi non chiudono niente: restano come sono, e nel
            //database un "l'albero" resta leggibile invece di diventare l&apos;albero
            return htmlspecialchars($nodo->data, ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');

        //commenti, istruzioni, doctype: niente
        if (!$nodo instanceof \Dom\Element)
            return '';

        $nome = strtolower($nodo->localName);

        if (in_array($nome, self::VIA, true))
            return '';

        //<font> e' quello che scrivono i comandi del browser e che arriva da Word: si traduce in
        //uno span con lo stile, poi le regole dello stile decidono cosa resta
        $stile = (string)$nodo->getAttribute('style');

        if ($nome === 'font')
        {
            $nome = 'span';

            if ($nodo->hasAttribute('color'))
                $stile .= ';color:' . $nodo->getAttribute('color');

            if ($nodo->hasAttribute('face'))
                $stile .= ';font-family:' . $nodo->getAttribute('face');
        }

        $nome = $regole['rename'][$nome] ?? $nome;

        $contenuto = self::Figli($nodo, $regole);

        //un tag che non si conosce si scioglie: resta il suo testo, se ne va lui
        if (!array_key_exists($nome, $regole['tags']))
            return $contenuto;

        $attributi = '';

        $stile = self::Stile($stile, $regole);

        if ($stile !== '')
            //ENT_COMPAT: l'attributo e' fra virgolette doppie, e l'apice di 'Times New Roman'
            //non ha niente da chiudere - lasciarlo com'e' fa leggere lo stile a chi lo guarda
            $attributi .= ' style="' . htmlspecialchars($stile, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8') . '"';

        if ($nome === 'a')
        {
            $href = self::Indirizzo((string)$nodo->getAttribute('href'));

            //un link senza un indirizzo buono non e' un link: resta il testo
            if ($href === '')
                return $contenuto;

            $attributi .= ' href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"';

            //la scheda nuova si porta sempre dietro noopener: senza, la pagina aperta puo'
            //riscrivere l'indirizzo di questa con window.opener
            if (strtolower(trim((string)$nodo->getAttribute('target'))) === '_blank')
                $attributi .= ' target="_blank" rel="noopener noreferrer"';
        }

        //uno span senza stile non dice niente: si scioglie invece di restare vuoto
        if ($nome === 'span' && $attributi === '')
            return $contenuto;

        if (in_array($nome, self::VUOTI, true))
            return '<' . $nome . $attributi . '>';

        return '<' . $nome . $attributi . '>' . $contenuto . '</' . $nome . '>';
    }

    /** Ricompone lo stile tenendo solo le proprieta' permesse, con valori controllati. */
    private static function Stile(string $stile, array $regole): string
    {
        if ($stile === '' || $regole['styles'] === [])
            return '';

        $tenute = [];

        foreach (explode(';', $stile) as $dichiarazione)
        {
            $due = explode(':', $dichiarazione, 2);

            if (count($due) !== 2)
                continue;

            $proprieta = strtolower(trim($due[0]));
            $valore = trim(preg_replace('/\s*!important\s*$/i', '', $due[1]));

            //text-decoration-line e' lo stesso di text-decoration, per quello che serve qui
            if ($proprieta === 'text-decoration-line')
                $proprieta = 'text-decoration';

            if (!in_array($proprieta, $regole['styles'], true))
                continue;

            $valore = self::Valore($proprieta, $valore, $regole);

            if ($valore !== '')
                $tenute[$proprieta] = $proprieta . ':' . $valore;
        }

        return implode(';', $tenute);
    }

    /**
     * Il valore di una proprieta', se e' uno di quelli buoni, nella sua forma canonica; stringa
     * vuota altrimenti. Ogni proprieta' ha la sua forma: niente url(), niente expression(),
     * niente commenti, niente caratteri che possano chiudere qualcosa.
     */
    private static function Valore(string $proprieta, string $valore, array $regole): string
    {
        $v = strtolower($valore);

        if (preg_match('/[<>\\\\{}]|\/\*|url\s*\(|expression|javascript|@/i', $valore))
            return '';

        return match ($proprieta) {
            'color', 'background-color' => self::ColoreValido($v) && !in_array($v, ['transparent', 'inherit', 'initial', 'unset', 'currentcolor', 'windowtext'], true) ? $v : '',

            'font-size' => preg_match('/^(\d{1,3}(\.\d+)?)(px|pt|em|rem|%)$/', $v, $m) && self::Misura((float)$m[1], $m[3])
                || in_array($v, ['xx-small', 'x-small', 'small', 'medium', 'large', 'x-large', 'xx-large'], true) ? $v : '',

            'font-family' => self::Carattere($valore, $regole['fonts']),

            'font-weight' => in_array($v, ['bold', 'bolder', '600', '700', '800', '900'], true) ? 'bold' : '',

            'font-style' => in_array($v, ['italic', 'oblique'], true) ? 'italic' : '',

            'text-decoration' => self::Decorazione($v, $regole['tags']),

            'vertical-align' => ($v === 'super' && isset($regole['tags']['sup'])) || ($v === 'sub' && isset($regole['tags']['sub'])) ? $v : '',

            'text-align' => match ($v) {
                'left', 'start' => 'left',
                'center', '-webkit-center' => 'center',
                'right', 'end' => 'right',
                'justify' => 'justify',
                default => '',
            },

            'line-height' => $v === 'normal' || (preg_match('/^\d(\.\d{1,2})?$/', $v) && (float)$v >= 0.8 && (float)$v <= 4) ? $v : '',

            'margin-left' => preg_match('/^(\d{1,3})px$/', $v, $m) && (int)$m[1] > 0 && (int)$m[1] <= 400 ? $v : '',

            default => '',
        };
    }

    /** Una dimensione di carattere sensata: niente testo da 0 pixel o da 2000. */
    private static function Misura(float $n, string $unita): bool
    {
        return match ($unita) {
            'px' => $n >= 6 && $n <= 96,
            'pt' => $n >= 5 && $n <= 72,
            'em', 'rem' => $n >= 0.4 && $n <= 6,
            '%' => $n >= 40 && $n <= 600,
            default => false,
        };
    }

    /** Il primo carattere dell'elenco, se e' fra quelli permessi; nel loro nome scritto bene. */
    private static function Carattere(string $valore, array $permessi): string
    {
        $primo = trim(explode(',', $valore)[0], " \t\n\r\"'");

        foreach ($permessi as $carattere)
            if (strcasecmp($carattere, $primo) === 0)
                return preg_match('/^[\p{L}\p{N} _-]+$/u', $carattere) ? (str_contains($carattere, ' ') ? "'" . $carattere . "'" : $carattere) : '';

        return '';
    }

    /** Sottolineato e barrato passano solo se lo sono le loro funzioni. */
    private static function Decorazione(string $v, array $tags): string
    {
        $tenute = [];

        if (str_contains($v, 'underline') && isset($tags['u']))
            $tenute[] = 'underline';

        if (str_contains($v, 'line-through') && isset($tags['s']))
            $tenute[] = 'line-through';

        return implode(' ', $tenute);
    }

    /** #rgb, #rrggbb, rgb(), rgba(), hsl(), o un nome di colore. */
    private static function ColoreValido(string $colore): bool
    {
        $c = strtolower(trim($colore));

        return (bool)preg_match('/^(#[0-9a-f]{3,4}|#[0-9a-f]{6}|#[0-9a-f]{8}|rgba?\(\s*[\d.]+%?\s*[, ]\s*[\d.]+%?\s*[, ]\s*[\d.]+%?\s*([,\/]\s*[\d.]+%?\s*)?\)|hsla?\(\s*[\d.]+(deg)?\s*[, ]\s*[\d.]+%\s*[, ]\s*[\d.]+%\s*([,\/]\s*[\d.]+%?\s*)?\)|[a-z]{3,20})$/', $c);
    }

    /**
     * L'indirizzo di un link, se e' uno di quelli buoni; stringa vuota altrimenti.
     *
     * I caratteri di controllo si tolgono PRIMA di guardare lo schema: il browser li ignora, e
     * "java&#9;script:" per lui e' javascript:. Gli spazi diventano %20 per la stessa ragione.
     */
    private static function Indirizzo(string $href): string
    {
        $href = trim(preg_replace('/[\x00-\x1F\x7F]+/', '', $href));

        if ($href === '')
            return '';

        $href = str_replace(' ', '%20', $href);

        if (preg_match('/^([a-z][a-z0-9+.-]*):/i', $href, $m))
            return in_array(strtolower($m[1]), ['http', 'https', 'mailto', 'tel'], true) ? $href : '';

        //senza schema: un percorso del sito, un'ancora, una querystring. "//altro.sito" e'
        //un indirizzo esterno scritto di nascosto, e si scrive per intero
        return str_starts_with($href, '//') ? 'https:' . $href : $href;
    }

    /** Il testo di un pezzo di HTML, con gli a capo dove li vedrebbe chi legge. */
    private static function TestoDi(string $html): string
    {
        if (trim($html) === '')
            return '';

        $html = preg_replace('/<(br|\/p|\/li|\/h[1-6]|\/blockquote|\/tr|hr)\b[^>]*>/i', "$0\n", $html);

        $documento = \Dom\HTMLDocument::createFromString('<!DOCTYPE html><html><body>' . $html . '</body></html>', LIBXML_NOERROR);

        $testo = str_replace("\u{00A0}", ' ', (string)$documento->body->textContent);

        return trim(preg_replace("/[ \t]*\n[ \t\n]*/", "\n", $testo));
    }
}
