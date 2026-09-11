<?php
declare(strict_types=1);

namespace Common\WebForms\UnitTest;

use Common\WebForms\Controls\CheckBox;
use Common\WebForms\Controls\DropDownList;
use Common\WebForms\Controls\FileUpload;
use Common\WebForms\Controls\ListBox;
use Common\WebForms\Controls\Literal;
use Common\WebForms\Controls\TextBox;

/**
 * I controlli: cosa rendono, e cosa diventano con quello che torna dal browser.
 *
 * Sono oggetti PHP e basta: si valorizzano, si chiede il Render, si guarda l'HTML; oppure
 * si passa loro l'array del POST e si guarda lo stato. Niente HTTP, niente database.
 *
 * Ogni prova qui sotto corrisponde a un difetto vero, gia' capitato in produzione: se si
 * toglie la correzione, la prova diventa rossa.
 */
class ProveControlli
{
    public static function Esegui(Prova $p): void
    {
        self::Escape($p);
        self::Attributi($p);
        self::Attesa($p);
        self::Avvisi($p);
        self::Foglio($p);
        self::PostData($p);
        self::Upload($p);
    }

    /**
     * Gli avvisi: la coda, i due modi di mostrarli, e la regola che conta - chi li disegna li
     * consuma, altrimenti ricomparirebbero ad ogni click.
     */
    private static function Avvisi(Prova $p): void
    {
        $p->Sezione('Alert');

        $pagina = new class extends \Common\WebForms\Page {
        };

        $pagina->Alert = new \Common\WebForms\Alert($pagina);

        $p->Uguale('all\'inizio non c\'e\' niente da dire', true, $pagina->Alert->IsEmpty());

        $pagina->Alert->Success('Salvato.');
        $pagina->Alert->Fail('Non salvato: manca il nome.', true);

        $p->Uguale('i messaggi si accodano', 2, count($pagina->Alert->Messages()));

        $pagina->Alert->Success('   ');

        $p->Uguale('un messaggio vuoto non e\' un messaggio', 2, count($pagina->Alert->Messages()));

        //il tetto: sei ne lasciano cinque, e restano gli ultimi - il primo l'utente ha gia'
        //avuto il tempo di leggerlo
        for ($i = 1; $i <= 6; $i++)
            $pagina->Alert->Success('numero ' . $i);

        $messaggi = $pagina->Alert->Messages();

        $p->Uguale('non se ne tengono piu' . "'" . ' di cinque', 5, count($messaggi));
        $p->Uguale('e sono gli ultimi', 'numero 6', $messaggi[4]['testo']);

        $controllo = new \Common\WebForms\Controls\Alert();
        $controllo->Id = 'avvisi';
        $controllo->Page = $pagina;

        $html = $controllo->Render();

        $p->Contiene('il contenitore porta la durata', 'data-dw-duration="5000"', $html);
        $p->Contiene('un riquadro per messaggio', 'class="dw-avviso dw-avviso-successo"', $html);
        $p->Contiene('con la x per chiuderlo subito', 'dw-avviso-chiudi', $html);

        //la chiave serve al client per distinguere un messaggio nuovo da uno gia' in pagina:
        //il morph riusa il nodo che sta in quella posizione
        $p->Contiene('ogni riquadro ha un id suo', 'id="dw-avviso-' . $messaggi[0]['chiave'] . '"', $html);

        $p->Uguale('chi li disegna li consuma', true, $pagina->Alert->IsEmpty());

        $p->Manca('e alla seconda passata non c\'e\' piu\' niente', 'dw-avviso ', $controllo->Render());

        // --- il modale

        $pagina->Alert->Fail('Il file supera gli 8 MB.', true);

        $html = $controllo->Render();

        $p->Contiene('un messaggio modale oscura la pagina', 'class="dw-modale"', $html);
        $p->Contiene('e pretende un OK', 'dw-modale-ok', $html);
        $p->Manca('non e\' un riquadro volante', 'class="dw-avviso ', $html);

        // --- l'escape, che qui conta il doppio: nel testo ci finiscono i nomi dei record

        $pagina->Alert->Success('L\'Oreal & C. <script>');

        $p->Contiene('il testo esce escapato',
            'L&#039;Oreal &amp; C. &lt;script&gt;', $controllo->Render());
    }

    /**
     * Il foglio di stile con la marca temporale.
     *
     * Quello che conta e' che la marca ci sia e sia quella del FILE: e' l'unica cosa che
     * distingue questo controllo da un <link> scritto a mano, ed e' quella che fa arrivare
     * una modifica al CSS a chi ha gia' visitato il sito.
     */
    private static function Foglio(Prova $p): void
    {
        $p->Sezione('Stylesheet e Script');

        //un foglio vero, creato qui: la prova non deve dipendere dai file di un sito
        $radice = dirname(__DIR__, 3);

        $file = $radice . '/Common/WebForms/UnitTest/_prova.css';

        file_put_contents($file, '.prova{color:red}');

        $primaRadice = $_SERVER['DOCUMENT_ROOT'] ?? null;

        //il controllo ricava l'indirizzo togliendo la radice dei documenti da quella dei
        //sorgenti: qui i sorgenti SONO la radice, quindi l'indirizzo parte da /Common
        $_SERVER['DOCUMENT_ROOT'] = $radice;

        try
        {
            $css = new \Common\WebForms\Controls\Stylesheet();
            $css->Src = 'Common/WebForms/UnitTest/_prova.css';

            $html = $css->Render();

            $p->Contiene('rende un link al foglio', 'href="/Common/WebForms/UnitTest/_prova.css?v=', $html);
            $p->Contiene('con la marca temporale del file', '?v=' . filemtime($file) . '"', $html);
            $p->Manca('e senza media, se non e\' stato chiesto', 'media=', $html);

            //la barra iniziale e' quella che viene naturale scrivere: si accetta e si ignora
            $css->Src = '/Common/WebForms/UnitTest/_prova.css';

            $p->Uguale('la barra iniziale non cambia niente', $html, $css->Render());

            $css->Media = 'print';

            $p->Contiene('il media si aggiunge quando serve', 'media="print"', $css->Render());

            // --- lo script, stessa meccanica

            $js = new \Common\WebForms\Controls\Script();
            $js->Src = 'Common/WebForms/UnitTest/_prova.css';

            $html = $js->Render();

            $p->Contiene('lo script porta la stessa marca temporale', '?v=' . filemtime($file) . '"', $html);
            $p->Contiene('e per difetto non blocca l\'analisi del documento', ' defer>', $html);
            $p->Contiene('il tag si chiude sempre: senza, il browser si mangia il resto', '></script>', $html);

            $js->Async = true;

            $p->Manca('async e defer non si scrivono insieme', ' defer', $js->Render());
            $p->Contiene('async quando lo si chiede', ' async', $js->Render());

            $js->Async = false;
            $js->Module = true;

            $p->Contiene('un modulo si dichiara', 'type="module"', $js->Render());
            $p->Manca('e non ripete il defer, che ha gia\' per definizione', ' defer', $js->Render());

            $css->Media = '';
            $css->Src = 'Layouts/NonEsiste.css';

            $p->Solleva('un foglio che non c\'e\' ferma la pagina e dice quale', 'NonEsiste.css',
                static fn() => $css->Render());

            $css->Src = '../fuori.css';

            $p->Solleva('un percorso che esce dalla radice non si serve', 'src non valido',
                static fn() => $css->Render());
        }
        finally
        {
            @unlink($file);

            if ($primaRadice === null)
                unset($_SERVER['DOCUMENT_ROOT']);
            else
                $_SERVER['DOCUMENT_ROOT'] = $primaRadice;
        }
    }

    /**
     * L'UpdateProgress: quello che si vede mentre il postback e' in viaggio.
     *
     * Non ha eventi e non ha stato da provare: quello che conta e' che porti nell'HTML le
     * due cose da cui dipende, la classe su cui aggancia il CSS e il ritardo.
     */
    private static function Attesa(Prova $p): void
    {
        $p->Sezione('UpdateProgress');

        $prg = new \Common\WebForms\Controls\UpdateProgress();
        $prg->Id = 'prgAttesa';

        $html = $prg->Render();

        $p->Contiene('la classe su cui aggancia il CSS c\'e\' sempre', 'class="dw-attesa"', $html);
        $p->Contiene('il ritardo predefinito e\' 200ms', '--dw-attesa-dopo:200ms', $html);
        $p->Contiene('senza contenuto ne rende uno suo', 'Attendere...', $html);

        $prg->DisplayAfter = 500;
        $prg->CssClass = 'nw-attesa';

        $html = $prg->Render();

        $p->Contiene('il ritardo si cambia dal markup', '--dw-attesa-dopo:500ms', $html);
        $p->Contiene('la classe della pagina si aggiunge, non sostituisce',
            'class="dw-attesa nw-attesa"', $html);

        $dentro = new Literal();
        $dentro->Mode = Literal::PASSTHROUGH;
        $dentro->Text = '<b class="nw-mio">contenuto mio</b>';

        $prg->Add($dentro);

        $html = $prg->Render();

        $p->Contiene('con un contenuto proprio rende quello', '<b class="nw-mio">contenuto mio</b>', $html);
        $p->Manca('e non anche il predefinito', 'Attendere...', $html);
    }

    /**
     * Gli attributi aggiunti dal codice: l'Attributes di WebForms.
     *
     * Due cose da tenere ferme: il valore esce escapato (ci finiscono nomi di record, che
     * hanno apostrofi e virgolette), e il nome deve essere un nome di attributo - altrimenti
     * non si aggiunge un attributo, si inietta markup.
     */
    private static function Attributi(Prova $p): void
    {
        $p->Sezione('attributi aggiunti dal codice');

        $btn = new \Common\WebForms\Controls\Button();
        $btn->Id = 'btnElimina';

        $btn->Attributes->Add('title', 'Elimina "L\'Oreal" & C.')
            ->Add('data-riga', '7');

        $html = $btn->Render();

        $p->Contiene('il valore esce escapato', 'title="Elimina &quot;L&#039;Oreal&quot; &amp; C."', $html);
        $p->Contiene('un data- passa com\'e\'', 'data-riga="7"', $html);

        $btn->Attributes->Remove('data-riga');

        $p->Manca('e si puo\' togliere', 'data-riga', $btn->Render());

        //lo stato: un attributo messo in un handler deve esserci ancora al click dopo
        $altro = new \Common\WebForms\Controls\Button();
        $altro->Id = 'btnElimina';
        $altro->LoadViewState($btn->SaveViewState());

        $p->Contiene('gli attributi attraversano il postback', 'title="Elimina', $altro->Render());

        $p->Solleva('un nome che non e\' un nome di attributo si ferma subito', 'non e\' un nome di attributo',
            static fn() => $btn->Attributes->Add('on click="x"', 'y'));

        $p->Solleva('gli attributi che scrive il controllo sono riservati', 'lo scrive il controllo',
            static fn() => $btn->Attributes->Add('class', 'x'));

        $p->Solleva('il canale del motore non si tocca', 'appartiene al motore',
            static fn() => $btn->Attributes->Add('data-dw-click', '1'));

        //anche scrivendo come in un array si passa dalla collection, quindi il nome si
        //controlla subito: non esiste una strada per cui un nome storto arrivi all'HTML
        $p->Solleva('un nome storto scritto come in un array si ferma subito', 'non e\' un nome di attributo',
            static fn() => $btn->Attributes['nome con spazio'] = 'x');

        $p->Uguale('e la collection si legge come un array', 'Elimina "L' . "'" . 'Oreal" & C.', $btn->Attributes['title']);

        // --- lo stile in linea, che in WebForms e' la CssStyleCollection

        $barra = new \Common\WebForms\Controls\Panel();
        $barra->Id = 'pnlBarra';

        $barra->Style->Add('width', '72%')
              ->Add('background-color', '#1a7f37');

        $html = $barra->Render();

        $p->Contiene('lo stile esce in un attributo solo',
            'style="width:72%;background-color:#1a7f37"', $html);

        $barra->Style->Remove('background-color');

        $p->Contiene('e si toglie una dichiarazione alla volta', 'style="width:72%"', $barra->Render());

        //lo stato: uno stile messo in un handler deve esserci ancora al click dopo, e dentro
        //un Repeater viaggia per riga come tutto il resto
        $altra = new \Common\WebForms\Controls\Panel();
        $altra->Id = 'pnlBarra';
        $altra->LoadViewState($barra->SaveViewState());

        $p->Contiene('lo stile attraversa il postback', 'style="width:72%"', $altra->Render());

        $barra->Style->Clear();

        $p->Manca('svuotarlo toglie l\'attributo', 'style=', $barra->Render());

        //una proprieta' personalizzata e' un nome CSS valido: --dw-qualcosa
        $barra->Style->Add('--dw-mio', '3px');

        $p->Contiene('le proprieta\' personalizzate valgono', 'style="--dw-mio:3px"', $barra->Render());

        $p->Solleva('un nome che non e\' una proprieta\' CSS si ferma subito', 'non e\' una proprieta',
            static fn() => $barra->Style->Add('width:0;color', 'red'));

        //il valore puo' contenere qualunque cosa, e l'escape dell'attributo la neutralizza
        $barra->Style->Clear();
        $barra->Style->Add('background-image', 'url("a\'b.png")');

        $p->Contiene('il valore esce escapato',
            'url(&quot;a&#039;b.png&quot;)', $barra->Render());
    }

    private static function Escape(Prova $p): void
    {
        $p->Sezione('testo e apostrofi');

        $txt = new TextBox();
        $txt->Id = 'txtNome';
        $txt->Text = 'L\'Oreal & C. <b>';

        $p->Contiene('il valore di una TextBox esce escapato una volta sola',
            'value="L&#039;Oreal &amp; C. &lt;b&gt;"', $txt->Render());

        $lit = new Literal();
        $lit->Text = '<b>grassetto</b>';

        $p->Uguale('Literal in Encode non lascia passare i tag',
            '&lt;b&gt;grassetto&lt;/b&gt;', $lit->Render());

        $lit->Mode = Literal::PASSTHROUGH;

        $p->Uguale('Literal in PassThrough lascia passare l\'HTML del server',
            '<b>grassetto</b>', $lit->Render());
    }

    private static function PostData(Prova $p): void
    {
        $p->Sezione('quello che torna dal browser');

        //una casella non spuntata NON compare fra i campi inviati: senza il campo di
        //presenza non si distingue "l'utente l'ha tolta" da "il controllo non era in pagina"
        $chk = new CheckBox();
        $chk->Id = 'chkVisibile';
        $chk->Checked = true;

        $chk->LoadPostData(['chkVisibile__presente' => '1']);

        $p->Uguale('una casella spuntata e poi tolta risulta non spuntata', false, $chk->Checked);

        $chk->Checked = true;
        $chk->LoadPostData([]);

        $p->Uguale('senza il campo di presenza la casella non si tocca', true, $chk->Checked);

        $chk->LoadPostData(['chkVisibile__presente' => '1', 'chkVisibile' => 'on']);

        $p->Uguale('una casella spuntata torna spuntata', true, $chk->Checked);

        //il valore scelto si accetta solo se e' una delle voci RESE, altrimenti la tendina
        //diventa un campo di testo libero in mano al client
        $ddl = new DropDownList();
        $ddl->Id = 'ddlColore';
        $ddl->Items = ['rosso' => 'Rosso', 'verde' => 'Verde'];
        $ddl->SelectedValue = 'rosso';

        $ddl->LoadPostData(['ddlColore' => 'verde']);

        $p->Uguale('la tendina accetta una voce che esiste', 'verde', $ddl->SelectedValue);

        $ddl->LoadPostData(['ddlColore' => 'DROP TABLE']);

        $p->Uguale('la tendina rifiuta un valore che non ha mai reso', 'verde', $ddl->SelectedValue);

        //su un <select multiple> il campo si chiama id[]: senza parentesi PHP terrebbe solo
        //l'ultimo valore, e la selezione multipla non sarebbe mai piu' di uno
        $lst = new ListBox();
        $lst->Id = 'lstColori';
        $lst->Items = ['rosso' => 'Rosso', 'verde' => 'Verde', 'blu' => 'Blu'];
        $lst->SelectionMode = ListBox::MULTIPLE;

        $p->Contiene('un elenco multiplo rende il campo con le parentesi',
            'name="lstColori[]"', $lst->Render());

        $lst->LoadPostData(['lstColori' => ['rosso', 'blu', 'giallo']]);

        $p->Uguale('un elenco multiplo tiene tutte le voci scelte, e scarta quelle che non esistono',
            ['rosso', 'blu'], $lst->SelectedValues);
    }

    private static function Upload(Prova $p): void
    {
        $p->Sezione('FileUpload');

        $fu = new FileUpload();
        $fu->Id = 'fuImmagine';

        $html = $fu->Render();

        $p->Manca('senza AllowDrop il FileUpload non aggiunge nessun elemento attorno', '<div', $html);
        $p->Contiene('senza AllowDrop id e marcatori stanno sull\'input', 'data-dw-upload="1"', $html);
        $p->Contiene('il token viaggia in un campo a parte', 'name="fuImmagine__token"', $html);

        $fu->AllowDrop = true;

        $p->Contiene('con AllowDrop compare la zona di trascinamento', 'data-dw-drop="1"', $fu->Render());
    }
}
