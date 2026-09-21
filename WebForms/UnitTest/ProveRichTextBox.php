<?php
declare(strict_types=1);

namespace Common\WebForms\UnitTest;

use Common\WebForms\Control;
use Common\WebForms\ControlBuilder;
use Common\WebForms\Controls\RichTextBox;
use Common\WebForms\Page;
use Common\WebForms\PageParser;
use Common\WebForms\ViewState;

/**
 * Il RichTextBox: l'HTML di un utente che entra nel database.
 *
 * Quello che si tiene fermo, in ordine di quanto costa sbagliarlo:
 *
 * 1. LA PULIZIA. Tutto quello che non e' nell'elenco cade: script, eventi, javascript: nei
 *    link anche mascherato, stili con url() o expression(). Qui dentro ci sono i trucchi veri,
 *    non quelli di scuola: il tab dentro "javascript", l'SVG con l'onload, l'entita' che
 *    diventa una virgoletta.
 * 2. LE FUNZIONI. Ognuna si spegne da sola: sparisce il bottone E il server smette di far
 *    passare quello che fa. Una tabella incollata in un campo senza tabelle non entra.
 * 3. IL RESTO come per ogni controllo: markup, stato, evento, il campo spento che non scrive.
 */
class ProveRichTextBox
{
    public static function Esegui(Prova $p): void
    {
        //da riga di comando non c'e' un server: il controllo compone gli indirizzi dei suoi
        //file e ha bisogno di sapere dove sta la radice dei documenti
        //sta cinque sopra: .../<sito>, che contiene public/php
        $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 5);

        self::Pulizia($p);
        self::Funzioni($p);
        self::Controllo($p);
        self::Multipli($p);
        self::RepeaterConSalva($p);
    }

    /**
     * Il caso vero: un elenco di note, un editor per riga, un Salva per riga, un bottone che
     * aggiunge una riga e un Salva generale. Ogni passo e' un giro intero - lo stato si
     * impacchetta, la pagina rinasce, lo stato rientra - come fra due click nel browser.
     *
     * Quello che deve succedere: il Salva di una riga salva QUELLA riga e non tocca le altre,
     * anche quelle modificate e non salvate; la riga aggiunta ha un editor che funziona come
     * gli altri, Salva compreso; il Salva generale prende tutti gli editor, ognuno col suo
     * testo; e quello che finisce nel "database" e' sempre ripulito.
     */
    private static function RepeaterConSalva(Prova $p): void
    {
        $p->Sezione('RichTextBox: un editor per riga di Repeater, Salva, Aggiungi, Salva tutto');

        // --- primo caricamento: due note dal "database"

        $pagina = self::PaginaNote();
        $pagina->Salvate = [1 => '<p>prima nota</p>', 2 => '<p>seconda nota</p>'];
        $pagina->Lega();

        $html = self::RenderNote($pagina);

        $p->Contiene('la riga 1 ha il suo editor col suo testo', 'id="__RichTextBox_Testo__1"', $html);
        $p->Contiene('...e il testo della nota 1', 'value="&lt;p&gt;prima nota&lt;/p&gt;"', $html);
        $p->Contiene('la riga 2 ha il suo', 'id="__RichTextBox_Testo__2"', $html);
        $p->Contiene('e il suo Salva', 'id="__Button_Salva__2"', $html);

        // --- si scrive in tutte e due, si salva solo la 2

        $pagina = self::GiroNote($pagina);

        $pagina->FindControl('__RichTextBox_Testo__1')->LoadPostData(['__RichTextBox_Testo__1' => '<p>prima, cambiata ma NON salvata</p>']);
        $pagina->FindControl('__RichTextBox_Testo__2')->LoadPostData(['__RichTextBox_Testo__2' => '<p>seconda <b>salvata</b><script>x()</script></p>']);
        $pagina->FindControl('__Button_Salva__2')->RaisePostBackEvent('click', '');

        $p->Uguale('il Salva della riga 2 salva la 2, ripulita', '<p>seconda <strong>salvata</strong></p>', $pagina->Salvate[2]);
        $p->Uguale('e non tocca la 1', '<p>prima nota</p>', $pagina->Salvate[1]);

        $pagina = self::GiroNote($pagina);

        $p->Uguale('al click dopo la 1 ha ancora il testo scritto e non salvato',
            '<p>prima, cambiata ma NON salvata</p>', $pagina->FindControl('__RichTextBox_Testo__1')->Text);

        // --- si aggiunge una riga: il suo editor e il suo Salva funzionano

        $pagina->FindControl('__Button_Aggiungi')->RaisePostBackEvent('click', '');

        $p->Uguale('Aggiungi fa una riga in piu\'', 3, count($pagina->FindControl('__Repeater_Note')->Items()));

        $pagina = self::GiroNote($pagina);

        $html = self::RenderNote($pagina);

        $p->Contiene('la riga nuova ha il suo editor', 'id="__RichTextBox_Testo__3"', $html);
        $p->Contiene('e il suo campo', 'name="__RichTextBox_Testo__3"', $html);
        $p->Contiene('e il suo Salva', 'id="__Button_Salva__3"', $html);

        preg_match_all('/\sid="([^"]+)"/', $html, $m);
        $p->Uguale('con la riga nuova ancora nessun id ripetuto', $m[1], array_values(array_unique($m[1])));

        $pagina->FindControl('__RichTextBox_Testo__3')->LoadPostData(['__RichTextBox_Testo__3' => '<h2>Nuova</h2><p>terza</p>']);
        $pagina->FindControl('__Button_Salva__3')->RaisePostBackEvent('click', '');

        $p->Uguale('il Salva della riga nuova salva la nuova', '<h2>Nuova</h2><p>terza</p>', $pagina->Salvate[3] ?? null);
        $p->Uguale('senza toccare le altre', ['<p>prima nota</p>', '<p>seconda <strong>salvata</strong></p>'], [$pagina->Salvate[1], $pagina->Salvate[2]]);

        $pagina = self::GiroNote($pagina);

        $p->Uguale('aggiungere una riga non ha rimesso il testo salvato sopra quello scritto nella 1',
            '<p>prima, cambiata ma NON salvata</p>', $pagina->FindControl('__RichTextBox_Testo__1')->Text);

        // --- Salva tutto: ogni editor col suo testo, in un colpo

        $post = [
            '__RichTextBox_Testo__1' => '<p>uno, finale</p>',
            '__RichTextBox_Testo__2' => '<p>due, finale</p><table><tbody><tr><td>no</td></tr></tbody></table>',
            '__RichTextBox_Testo__3' => '<p>tre, <a href="javascript:x()">finale</a></p>',
        ];

        foreach ($pagina->FindControl('__Repeater_Note')->Items() as $riga)
            $riga->FindControl('__RichTextBox_Testo')->LoadPostData($post);

        $pagina->FindControl('__Button_SalvaTutto')->RaisePostBackEvent('click', '');

        $p->Uguale('Salva tutto salva ogni riga col suo testo, ripulito con le sue regole',
            [1 => '<p>uno, finale</p>', 2 => '<p>due, finale</p><p>no</p>', 3 => '<p>tre, finale</p>'], $pagina->Salvate);

        $p->Uguale('una riga salvata per editor', 3, $pagina->Contate);

        $pagina = self::GiroNote($pagina);

        $p->Uguale('e al click dopo ogni editor mostra quello che e\' stato salvato',
            ['<p>uno, finale</p>', '<p>due, finale</p><p>no</p>', '<p>tre, finale</p>'],
            array_map(static fn($r): string => $r->FindControl('__RichTextBox_Testo')->Text, $pagina->FindControl('__Repeater_Note')->Items()));
    }

    private const string MARKUP_NOTE =
        '<dw:Repeater id="__Repeater_Note" Tag="div" ItemTag="div" DataKeyField="Id" OnItemDataBound="RigaLegata">'
        . '<ItemTemplate>'
        . '<dw:HiddenField id="__Hidden_Id" Value="{{Id}}" />'
        . '<dw:RichTextBox id="__RichTextBox_Testo" EnableTable="false" />'
        . '<dw:Button id="__Button_Salva" Text="Salva" OnClick="SalvaRiga" />'
        . '</ItemTemplate>'
        . '</dw:Repeater>'
        . '<dw:Button id="__Button_Aggiungi" Text="Aggiungi una nota" OnClick="Aggiungi" />'
        . '<dw:Button id="__Button_SalvaTutto" Text="Salva tutto" OnClick="SalvaTutto" />';

    private static function PaginaNote(): PaginaNote
    {
        $pagina = new PaginaNote();

        $albero = ControlBuilder::Build(PageParser::ParseTesto(self::MARKUP_NOTE), $pagina);

        //quello che fa ProcessRequest dopo aver costruito l'albero
        (new \ReflectionProperty(Page::class, 'Controls'))->setValue($pagina, $albero);
        (new \ReflectionProperty(Page::class, 'markupRoot'))->setValue($pagina, $albero);

        return $pagina;
    }

    /** Un click: lo stato si impacchetta, la pagina rinasce dal markup, lo stato rientra. */
    private static function GiroNote(PaginaNote $pagina): PaginaNote
    {
        $pacco = ViewState::Pack((new \ReflectionMethod($pagina, 'SaveViewState'))->invoke($pagina));

        $nuova = self::PaginaNote();

        (new \ReflectionMethod($nuova, 'LoadViewState'))->invoke($nuova, ViewState::Unpack($pacco));

        return $nuova;
    }

    private static function RenderNote(Page $pagina): string
    {
        return (new \ReflectionMethod($pagina, 'RenderPage'))->invoke($pagina);
    }

    /**
     * Piu' editor nella stessa pagina: ognuno il suo valore, le sue funzioni, i suoi id. E'
     * il caso normale - titolo e descrizione, italiano e inglese - e il posto dove un nome in
     * comune fa scrivere un editor nell'altro.
     */
    private static function Multipli(Prova $p): void
    {
        $p->Sezione('RichTextBox: piu\' editor nella stessa pagina');

        $controlli = ControlBuilder::Build(PageParser::ParseTesto(
            '<dw:RichTextBox id="__RichTextBox_It" EnableTable="false" />'
            . '<dw:RichTextBox id="__RichTextBox_En" EnableBold="false" MaxLength="50" />'
            . '<dw:RichTextBox id="__RichTextBox_Note" Enabled="false" />'), null);

        [$it, $en, $note] = $controlli;

        $html = $it->Render() . $en->Render() . $note->Render();

        preg_match_all('/\sid="([^"]+)"/', $html, $m);
        $p->Uguale('nessun id ripetuto fra tre editor', $m[1], array_values(array_unique($m[1])));

        preg_match_all('/<input type="hidden" name="([^"]+)"/', $html, $m);
        $p->Uguale('ogni editor ha il suo campo, col suo nome', ['__RichTextBox_It', '__RichTextBox_En', '__RichTextBox_Note'], $m[1]);

        $p->Uguale('ogni barra comanda la sua area', 3, preg_match_all('/aria-controls="(__RichTextBox_(It|En|Note)__area)"/', $html));

        $p->Manca('le funzioni sono di ciascuno: il primo non ha le tabelle', 'data-rte-menu="table"', $it->Render());
        $p->Contiene('...il secondo si', 'data-rte-menu="table"', $en->Render());
        $p->Manca('il secondo non ha il grassetto', 'data-rte="bold"', $en->Render());
        $p->Contiene('...il primo si', 'data-rte="bold"', $it->Render());
        $p->Uguale('il limite e\' solo del secondo', [0, 50], [self::Configurazione($it)['maxLength'], self::Configurazione($en)['maxLength']]);

        //lo stesso POST per tutti, come lo manda il browser: ognuno prende il suo, con le sue regole
        $post = [
            '__RichTextBox_It'   => '<p><strong>ciao</strong></p><table><tbody><tr><td>t</td></tr></tbody></table>',
            '__RichTextBox_En'   => '<p><strong>hello</strong></p><table><tbody><tr><td>t</td></tr></tbody></table>',
            '__RichTextBox_Note' => '<p>scritto sul campo spento</p>',
        ];

        foreach ($controlli as $c)
            $c->LoadPostData($post);

        $p->Uguale('il primo prende il suo: grassetto si, tabella no', '<p><strong>ciao</strong></p><p>t</p>', $it->Text);
        $p->Uguale('il secondo il suo: tabella si, grassetto no', '<p>hello</p><table><tbody><tr><td>t</td></tr></tbody></table>', $en->Text);
        $p->Uguale('il terzo e\' spento e resta vuoto', '', $note->Text);

        // --- in un Repeater: un editor per riga

        $pagina = new class extends Page {};

        /** @var \Common\WebForms\Controls\Repeater $rpt */
        $rpt = ControlBuilder::Build(PageParser::ParseTesto(
            '<dw:Repeater id="rpt" Tag="div" ItemTag="div" DataKeyField="Id">'
            . '<ItemTemplate><dw:RichTextBox id="__RichTextBox_Riga" EnableTable="false" /></ItemTemplate>'
            . '</dw:Repeater>'), $pagina)[0];

        $rpt->DataSource = [['Id' => 7], ['Id' => 9], ['Id' => 11]];
        $rpt->DataBind();

        $html = $rpt->Render();

        preg_match_all('/\sid="([^"]+)"/', $html, $m);
        $p->Uguale('nel Repeater nessun id ripetuto, aree comprese', $m[1], array_values(array_unique($m[1])));

        preg_match_all('/<input type="hidden" name="([^"]+)"/', $html, $m);
        $p->Uguale('un campo per riga, col nome della riga', 3, count(array_unique($m[1])));

        $righe = $rpt->Items();

        $editor = array_map(static fn($riga) => $riga->FindControl('__RichTextBox_Riga'), $righe);

        $post = [];
        foreach ($editor as $i => $e)
            $post[$e->Id] = '<p>riga ' . $i . '</p>';

        foreach ($editor as $e)
            $e->LoadPostData($post);

        $p->Uguale('ogni riga prende il suo testo', ['<p>riga 0</p>', '<p>riga 1</p>', '<p>riga 2</p>'],
            array_map(static fn(RichTextBox $e): string => $e->Text, $editor));

        $stato = ViewState::Unpack(ViewState::Pack($rpt->SaveViewState()));

        /** @var \Common\WebForms\Controls\Repeater $secondo */
        $secondo = ControlBuilder::Build(PageParser::ParseTesto(
            '<dw:Repeater id="rpt" Tag="div" ItemTag="div" DataKeyField="Id">'
            . '<ItemTemplate><dw:RichTextBox id="__RichTextBox_Riga" EnableTable="false" /></ItemTemplate>'
            . '</dw:Repeater>'), new class extends Page {})[0];

        $secondo->LoadViewState($stato);

        $p->Uguale('al postback dopo ogni riga ritrova il suo testo', ['<p>riga 0</p>', '<p>riga 1</p>', '<p>riga 2</p>'],
            array_map(static fn($riga): string => $riga->FindControl('__RichTextBox_Riga')->Text, $secondo->Items()));
    }

    /** Con tutte le funzioni accese: e' la regola di sicurezza, quella del render. */
    private static function Tutto(string $html): string
    {
        return RichTextBox::Sanitize($html, (new RichTextBox())->Rules(true));
    }

    private static function Pulizia(Prova $p): void
    {
        $p->Sezione('RichTextBox: la pulizia');

        // --- quello che resta

        $p->Uguale('la formattazione di Word che serve resta',
            '<p><strong>grassetto</strong> <em>corsivo</em> <u>sotto</u> <s>barrato</s> x<sup>2</sup> H<sub>2</sub>O</p>',
            self::Tutto('<p><strong>grassetto</strong> <em>corsivo</em> <u>sotto</u> <s>barrato</s> x<sup>2</sup> H<sub>2</sub>O</p>'));

        $p->Uguale('titoli, elenchi, citazione, linea: restano',
            '<h2>Titolo</h2><ul><li>uno</li></ul><ol><li>due</li></ol><blockquote><p>citato</p></blockquote><hr>',
            self::Tutto('<h2>Titolo</h2><ul><li>uno</li></ul><ol><li>due</li></ol><blockquote><p>citato</p></blockquote><hr>'));

        $p->Uguale('una tabella resta com\'e\', senza attributi',
            '<table><tbody><tr><td>a</td><td>b</td></tr></tbody></table>',
            self::Tutto('<table border="1" cellpadding="3" class="x"><tbody><tr><td width="50">a</td><td>b</td></tr></tbody></table>'));

        $p->Uguale('il testo si riescapa: un < scritto resta un < scritto, le virgolette restano virgolette',
            '<p>1 &lt; 2 &amp;&amp; 3 &gt; 2 "x" l\'albero</p>',
            self::Tutto('<p>1 &lt; 2 &amp;&amp; 3 &gt; 2 "x" l\'albero</p>'));

        $p->Uguale('i vecchi nomi diventano quelli di oggi: b, i, strike, del',
            '<p><strong>a</strong><em>b</em><s>c</s><s>d</s></p>',
            self::Tutto('<p><b>a</b><i>b</i><strike>c</strike><del>d</del></p>'));

        $p->Uguale('il <div> dei browser diventa un paragrafo', '<p>riga</p>', self::Tutto('<div>riga</div>'));

        $p->Uguale('h5 e h6 diventano h4: i titoli sono quattro', '<h4>a</h4><h4>b</h4>', self::Tutto('<h5>a</h5><h6>b</h6>'));

        $p->Uguale('il <font> dei comandi del browser diventa uno span con lo stile',
            '<p><span style="color:#ff0000;font-family:Georgia">rosso</span></p>',
            self::Tutto('<p><font color="#ff0000" face="Georgia">rosso</font></p>'));

        // --- lo stile

        $p->Uguale('gli stili buoni restano, nella loro forma',
            '<p style="text-align:center;line-height:1.5;margin-left:40px"><span style="color:rgb(255, 0, 0);background-color:#ffff00;font-size:18px;font-weight:bold;font-style:italic">x</span></p>',
            self::Tutto('<p style="text-align: center; line-height: 1.5; margin-left: 40px"><span style="color: rgb(255, 0, 0); background-color: #ffff00; font-size: 18px; font-weight: 700; font-style: italic">x</span></p>'));

        $p->Uguale('le proprieta\' fuori elenco cadono: position, width, z-index',
            '<p><span style="color:#000000">x</span></p>',
            self::Tutto('<p><span style="position:fixed;top:0;width:100%;z-index:9999;color:#000000">x</span></p>'));

        $p->Uguale('url() in un colore cade', '<p>x</p>', self::Tutto('<p><span style="background-color:url(javascript:alert(1))">x</span></p>'));

        $p->Uguale('expression() cade', '<p>x</p>', self::Tutto('<p><span style="color:expression(alert(1))">x</span></p>'));

        $p->Uguale('un commento dentro lo stile cade', '<p>x</p>', self::Tutto('<p><span style="color:re/**/d">x</span></p>'));

        $p->Uguale('un colore trasparente non e\' un colore', '<p>x</p>', self::Tutto('<p><span style="background-color:transparent">x</span></p>'));

        $p->Uguale('text-align start ed end diventano sinistra e destra',
            '<p style="text-align:left">a</p><p style="text-align:right">b</p>',
            self::Tutto('<p style="text-align:start">a</p><p style="text-align:end">b</p>'));

        $p->Uguale('un\'interlinea assurda cade', '<p>a</p>', self::Tutto('<p style="line-height:40">a</p>'));

        $p->Uguale('un carattere gigante cade', '<p>a</p>', self::Tutto('<p><span style="font-size:900px">a</span></p>'));

        $p->Uguale('un rientro oltre i 400 pixel cade', '<p>a</p>', self::Tutto('<p style="margin-left:4000px">a</p>'));

        $p->Uguale('un carattere che non e\' fra quelli scelti cade: il Calibri di Word',
            '<p>a</p>', self::Tutto('<p><span style="font-family:Calibri, sans-serif">a</span></p>'));

        $p->Uguale('un carattere fra quelli scelti resta, scritto bene',
            '<p><span style="font-family:\'Times New Roman\'">a</span></p>',
            self::Tutto('<p><span style="font-family:&quot;times new roman&quot;, serif">a</span></p>'));

        $p->Uguale('text-decoration-line si legge come text-decoration',
            '<p><span style="text-decoration:underline line-through">a</span></p>',
            self::Tutto('<p><span style="text-decoration-line:underline line-through">a</span></p>'));

        $p->Uguale('!important si toglie, il valore resta',
            '<p><span style="color:#123456">a</span></p>', self::Tutto('<p><span style="color:#123456 !important">a</span></p>'));

        $p->Uguale('uno span che non dice niente si scioglie', '<p>abc</p>', self::Tutto('<p>a<span class="x" id="y">b</span>c</p>'));

        // --- quello che se ne va

        $p->Uguale('lo script se ne va con il suo contenuto', '<p>primadopo</p>', self::Tutto('<p>prima<script>alert(1)</script>dopo</p>'));

        $p->Uguale('lo style se ne va con il suo contenuto', '<p>x</p>', self::Tutto('<style>p{color:red}</style><p>x</p>'));

        $p->Uguale('gli eventi cadono con tutti gli altri attributi', '<p>x</p>', self::Tutto('<p onclick="alert(1)" onmouseover="alert(2)">x</p>'));

        $p->Uguale('l\'svg con l\'onload se ne va tutto', '<p>x</p>', self::Tutto('<p>x<svg onload="alert(1)"><circle r="3"/></svg></p>'));

        $p->Uguale('le immagini se ne vanno: con onerror o senza', '<p>ab</p>', self::Tutto('<p>a<img src=x onerror="alert(1)">b</p>'));

        $p->Uguale('iframe, object, embed se ne vanno', '<p>x</p>', self::Tutto('<p>x<iframe src="//male"></iframe><object data="a"></object><embed src="b"></p>'));

        $p->Uguale('i campi di una form se ne vanno', '<p>x</p>', self::Tutto('<p>x<input value="a"><button>b</button><select><option>c</option></select><textarea>d</textarea></p>'));

        $p->Uguale('i commenti se ne vanno, anche quelli condizionali di Word', '<p>x</p>',
            self::Tutto('<!--[if gte mso 9]><xml><o:OfficeDocumentSettings/></xml><![endif]--><p>x<!-- nota --></p>'));

        $p->Uguale('un tag che non si conosce si scioglie e il testo resta', '<p>ciao mondo</p>',
            self::Tutto('<p><o:p>ciao</o:p> <marquee>mondo</marquee></p>'));

        $p->Uguale('un paragrafo di Word con classi e stili mso resta un paragrafo', '<p>Testo</p>',
            self::Tutto('<p class="MsoNormal" style="mso-margin-top-alt:auto;mso-add-space:auto">Testo<o:p></o:p></p>'));

        // --- i link

        $p->Uguale('un link http resta', '<p><a href="https://doweb.it/x?a=1&amp;b=2">doweb</a></p>',
            self::Tutto('<p><a href="https://doweb.it/x?a=1&amp;b=2" onclick="x()">doweb</a></p>'));

        $p->Uguale('mailto e tel restano', '<p><a href="mailto:a@b.it">m</a><a href="tel:+39045">t</a></p>',
            self::Tutto('<p><a href="mailto:a@b.it">m</a><a href="tel:+39045">t</a></p>'));

        $p->Uguale('un percorso del sito e un\'ancora restano', '<p><a href="/contatti">c</a><a href="#su">s</a></p>',
            self::Tutto('<p><a href="/contatti">c</a><a href="#su">s</a></p>'));

        $p->Uguale('javascript: nel link: resta il testo, se ne va il link', '<p>clic</p>', self::Tutto('<p><a href="javascript:alert(1)">clic</a></p>'));

        $p->Uguale('JaVaScRiPt: con le maiuscole: idem', '<p>clic</p>', self::Tutto('<p><a href="JaVaScRiPt:alert(1)">clic</a></p>'));

        $p->Uguale('java&#9;script: con il tab in mezzo, che il browser ignora: idem', '<p>clic</p>',
            self::Tutto('<p><a href="java&#9;script:alert(1)">clic</a></p>'));

        $p->Uguale('con gli spazi davanti: idem', '<p>clic</p>', self::Tutto('<p><a href="  &#10; javascript:alert(1)">clic</a></p>'));

        $p->Uguale('data: e vbscript: idem', '<p>ab</p>',
            self::Tutto('<p><a href="data:text/html;base64,PHNjcmlwdD4=">a</a><a href="vbscript:msgbox">b</a></p>'));

        $p->Uguale('//altro.sito si scrive per intero', '<p><a href="https://altro.sito/x">a</a></p>',
            self::Tutto('<p><a href="//altro.sito/x">a</a></p>'));

        $p->Uguale('la scheda nuova porta sempre noopener', '<p><a href="https://x.it" target="_blank" rel="noopener noreferrer">x</a></p>',
            self::Tutto('<p><a href="https://x.it" target="_blank">x</a></p>'));

        $p->Uguale('un target che non e\' _blank cade', '<p><a href="https://x.it">x</a></p>',
            self::Tutto('<p><a href="https://x.it" target="finestra">x</a></p>'));

        // --- le virgolette e i vuoti

        $p->Uguale('una virgoletta nel valore di un attributo non lo chiude',
            '<p><a href="https://x.it/?q=%22%3E">x</a></p>', self::Tutto('<p><a href="https://x.it/?q=%22%3E">x</a></p>'));

        $p->Manca('un attributo spezzato con le entita\' non esce vivo', 'onmouseover="',
            self::Tutto('<p><a href="https://x.it/&quot; onmouseover=&quot;alert(1)">x</a></p>'));

        $p->Uguale('un editor svuotato e\' vuoto davvero, non <p><br></p>', '', self::Tutto('<p><br></p>'));

        $p->Uguale('anche con gli spazi duri', '', self::Tutto('<p>&nbsp;</p><p> </p>'));

        $p->Uguale('una linea sola non e\' vuota', '<hr>', self::Tutto('<hr>'));

        $p->Uguale('spazzatura che non e\' HTML: resta testo, escapato', '&lt;&lt;&gt;&gt; x', self::Tutto('<<>> x'));
    }

    private static function Funzioni(Prova $p): void
    {
        $p->Sezione('RichTextBox: le funzioni, una per una');

        // --- la barra

        $r = self::Dal('<dw:RichTextBox id="r" />');

        foreach (['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'alignleft', 'aligncenter',
                  'alignright', 'alignjustify', 'bulletedlist', 'numberedlist', 'indent', 'outdent', 'quote', 'horizontalrule',
                  'clearformatting', 'fullscreen', 'undo', 'redo'] as $comando)
            $p->Contiene('di serie c\'e\' il bottone ' . $comando, 'data-rte="' . $comando . '"', $r->Render());

        foreach (['heading', 'fontname', 'fontsize', 'forecolor', 'backcolor', 'lineheight', 'link', 'table'] as $menu)
            $p->Contiene('di serie c\'e\' il menu ' . $menu, 'data-rte-menu="' . $menu . '"', $r->Render());

        $p->Manca('di serie l\'HTML no: e\' per chi sa cosa fa', 'data-rte="source"', $r->Render());

        $r = self::Dal('<dw:RichTextBox id="r" EnableBold="false" EnableTable="false" EnableSourceView="true" />');

        $p->Manca('EnableBold="false" toglie il grassetto dalla barra', 'data-rte="bold"', $r->Render());
        $p->Manca('EnableTable="false" toglie il menu tabella', 'data-rte-menu="table"', $r->Render());
        $p->Contiene('EnableSourceView="true" mette il bottone HTML', 'data-rte="source"', $r->Render());
        $p->Contiene('e il corsivo resta dov\'era', 'data-rte="italic"', $r->Render());

        $r = new RichTextBox();
        $r->Id = 'r';
        $r->EnableOnly('Bold', 'link');

        $p->Uguale('EnableOnly accende solo quelle nominate, senza badare alle maiuscole',
            ['Bold', 'Link'], array_values(array_filter(RichTextBox::FEATURES, $r->IsEnabled(...))));

        $p->Uguale('e le altre sono spente', false, $r->EnableItalic);

        $p->Solleva('una funzione che non esiste si ferma, con l\'elenco di quelle buone', 'Sono: UndoRedo',
            static fn() => $r->EnableOnly('Grassetto'));

        $r->SetAll(false);

        $p->Manca('tutto spento: niente barra', 'dw-rte-barra', $r->Render());
        $p->Contiene('ma l\'editor c\'e\'', 'class="dw-rte-area"', $r->Render());

        $r->SetAll(true);

        $p->Contiene('SetAll(true) le riaccende tutte, anche l\'HTML', 'data-rte="source"', $r->Render());

        // --- quello che arriva dal browser rispetta le funzioni

        $scritto = '<p><strong>g</strong> <em>c</em> <span style="color:#ff0000;background-color:#ffff00">col</span></p>'
            . '<h2>T</h2><table><tbody><tr><td>cella</td></tr></tbody></table><p><a href="https://x.it">link</a></p>'
            . '<ul><li>voce</li></ul><blockquote><p>cit</p></blockquote><hr>';

        $spenta = static function (string $funzione) use ($scritto): string
        {
            $r = new RichTextBox();
            $r->Id = 'r';
            $r->{'Enable' . $funzione} = false;
            $r->LoadPostData(['r' => $scritto]);

            return $r->Text;
        };

        $p->Manca('Bold spento: <strong> non entra', '<strong>', $spenta('Bold'));
        $p->Contiene('...ma il testo si', '<p>g ', $spenta('Bold'));
        $p->Manca('Italic spento: <em> non entra', '<em>', $spenta('Italic'));
        $p->Manca('ForeColor spento: il colore del testo non entra', 'color:#ff0000', $spenta('ForeColor'));
        $p->Contiene('...e l\'evidenziatore si', 'background-color:#ffff00', $spenta('ForeColor'));
        $p->Manca('BackColor spento: l\'evidenziatore non entra', 'background-color', $spenta('BackColor'));
        $p->Manca('Headings spento: niente titoli', '<h2>', $spenta('Headings'));
        $p->Contiene('...il titolo diventa un paragrafo', '<p>T</p>', $spenta('Headings'));
        $p->Manca('Table spento: niente tabella', '<table', $spenta('Table'));
        $p->Contiene('...la cella diventa un paragrafo', '<p>cella</p>', $spenta('Table'));
        $p->Manca('Link spento: niente link', '<a ', $spenta('Link'));
        $p->Contiene('...resta il testo', 'link', $spenta('Link'));
        $p->Manca('Quote spento: niente citazione', '<blockquote', $spenta('Quote'));
        $p->Manca('HorizontalRule spento: niente linea', '<hr', $spenta('HorizontalRule'));
        $p->Manca('BulletedList spento: niente elenco puntato', '<ul', $spenta('BulletedList'));
        $p->Contiene('...ma la voce resta, nell\'altro elenco che c\'e\'', '<li>voce</li>', $spenta('BulletedList'));

        $r = new RichTextBox();
        $r->Id = 'r';
        $r->EnableBulletedList = false;
        $r->EnableNumberedList = false;
        $r->LoadPostData(['r' => '<ul><li>voce</li></ul>']);

        $p->Uguale('tutti e due gli elenchi spenti: le voci diventano paragrafi', '<p>voce</p>', $r->Text);

        $r = new RichTextBox();
        $r->Id = 'r';
        $r->EnableUnderline = false;
        $r->LoadPostData(['r' => '<p><span style="text-decoration:underline line-through">a</span><u>b</u></p>']);

        $p->Uguale('Underline spento: resta il barrato, il sottolineato cade anche come stile',
            '<p><span style="text-decoration:line-through">a</span>b</p>', $r->Text);

        $r = new RichTextBox();
        $r->Id = 'r';
        $r->EnableAlign = false;
        $r->EnableLineHeight = false;
        $r->EnableIndent = false;
        $r->LoadPostData(['r' => '<p style="text-align:center;line-height:2;margin-left:40px">a</p>']);

        $p->Uguale('allineamento, interlinea e rientro spenti: il paragrafo resta nudo', '<p>a</p>', $r->Text);

        $r = new RichTextBox();
        $r->Id = 'r';
        $r->FontNames = 'Roboto;Open Sans';
        $r->LoadPostData(['r' => '<p><span style="font-family:Open Sans">a</span><span style="font-family:Georgia">b</span></p>']);

        $p->Uguale('FontNames e\' anche l\'elenco di quelli che passano',
            '<p><span style="font-family:\'Open Sans\'">a</span>b</p>', $r->Text);

        // --- la configurazione per il browser

        $r = new RichTextBox();
        $r->Id = 'r';
        $r->EnableTable = false;
        $cfg = self::Configurazione($r);

        $p->Uguale('il browser riceve le regole: senza tabella, table non c\'e\'', false, isset($cfg['rules']['tags']['table']));
        $p->Uguale('e le funzioni accese', false, in_array('Table', $cfg['features'], true));
        $p->Uguale('le dimensioni sono numeri', [10, 12, 14, 16, 18, 20, 24, 28, 32, 40], $cfg['fontSizes']);
        $p->Uguale('la tavolozza predefinita ha 40 colori', 40, count($cfg['colors']));

        $r->Colors = '#ff0000, blu scuro, url(x), #00ff00';
        $p->Uguale('una tavolozza propria tiene solo i colori veri', ['#ff0000', '#00ff00'], self::Configurazione($r)['colors']);
    }

    private static function Controllo(Prova $p): void
    {
        $p->Sezione('RichTextBox: il controllo');

        $r = self::Dal('<dw:RichTextBox id="__RichTextBox_Nota" Placeholder="Scrivi..." MinHeight="220" MaxHeight="500" />');

        $html = $r->Render();

        $p->Contiene('il contenitore porta la classe e la configurazione', 'class="dw-rte" data-dw-rte="', $html);
        $p->Contiene('l\'area e\' un contenteditable che il morph non tocca', 'class="dw-rte-area" dw-preserve', $html);
        $p->Contiene('con il suo id', 'id="__RichTextBox_Nota__area"', $html);
        $p->Contiene('il segnaposto', 'data-placeholder="Scrivi..."', $html);
        $p->Contiene('le altezze', 'style="min-height:220px;max-height:500px"', $html);
        $p->Contiene('il valore viaggia in un campo nascosto con il nome del controllo', '<input type="hidden" name="__RichTextBox_Nota" value="">', $html);
        $p->Contiene('lo stile e\' un link dentro il controllo, con la marca temporale', 'RichTextBox/RichTextBox.css?v=', $html);
        $p->Contiene('lo script lo carica il motore dall\'indirizzo che il controllo gli da\'', 'data-dw-rte-js="/public/php/Common/WebForms/RichTextBox/RichTextBox.js?v=', $html);
        $p->Contiene('le icone sono span del CSS', '<span class="dw-rte-i dw-rte-i-bold" aria-hidden="true"></span>', $html);
        $p->Contiene('i bottoni hanno il nome per chi non vede', 'aria-label="Grassetto (Ctrl+B)"', $html);
        $p->Manca('senza conto niente piede', 'dw-rte-piede', $html);

        // --- il testo del codice esce ripulito, ma con tutte le funzioni

        $r->EnableTable = false;
        $r->Text = '<table><tbody><tr><td>dal codice</td></tr></tbody></table><script>x()</script>';
        $html = $r->Render();

        $p->Contiene('una tabella messa dal CODICE resta anche con le tabelle spente', '<table><tbody><tr><td>dal codice</td></tr></tbody></table>', $html);
        $p->Manca('ma lo script no', '<script>x()', $html);
        $p->Contiene('e il campo nascosto porta lo stesso valore, escapato', 'value="&lt;table&gt;&lt;tbody&gt;', $html);

        // --- il POST

        $r = new RichTextBox();
        $r->Id = 'r';
        $r->LoadPostData(['r' => '<p onclick="x">ciao <b>mondo</b></p>']);

        $p->Uguale('quello che arriva si ripulisce subito: Text e\' gia\' pulito', '<p>ciao <strong>mondo</strong></p>', $r->Text);
        $p->Uguale('PlainText e\' il testo', 'ciao mondo', $r->PlainText());

        $r->LoadPostData(['altro' => 'x']);
        $p->Uguale('se il campo non e\' nel POST non si tocca niente', '<p>ciao <strong>mondo</strong></p>', $r->Text);

        $r->Enabled = false;
        $r->LoadPostData(['r' => '<p>riscritto dalla console</p>']);
        $p->Uguale('spento non scrive, anche se il valore arriva lo stesso', '<p>ciao <strong>mondo</strong></p>', $r->Text);
        $p->Contiene('spento: il campo e\' disabled e non viaggia', '" disabled></div>', $r->Render());
        $p->Contiene('spento: l\'area non si scrive', 'contenteditable="false"', $r->Render());
        $p->Contiene('spento: i bottoni sono spenti', 'aria-label="Grassetto (Ctrl+B)" disabled', $r->Render());

        // --- il testo

        $r = new RichTextBox();
        $r->Text = '<h2>Titolo</h2><p>uno&nbsp;due<br>tre</p><ul><li>a</li><li>b</li></ul>';

        $p->Uguale('PlainText mette gli a capo dove li vede chi legge', "Titolo\nuno due\ntre\na\nb", $r->PlainText());

        $r->MaxLength = 10;
        $p->Uguale('oltre MaxLength: LengthExceeded', true, $r->LengthExceeded());

        $r->MaxLength = 100;
        $p->Uguale('dentro MaxLength: no', false, $r->LengthExceeded());

        $r->MaxLength = 0;
        $p->Uguale('senza limite: mai', false, $r->LengthExceeded());

        $r = new RichTextBox();
        $r->Id = 'r';
        $r->Text = '<p>Ciao mondo, l\'albero è verde.</p>';
        $r->MaxLength = 2000;

        $p->Contiene('con MaxLength c\'e\' il conto, con il limite', '5 parole, 29 / 2000 caratteri', $r->Render());

        $r->MaxLength = 0;
        $r->ShowCounter = true;
        $p->Contiene('ShowCounter senza limite: parole e caratteri', '5 parole, 29 caratteri', $r->Render());

        // --- l'evento

        $pagina = new class extends Page {
            public ?RichTextBox $Chiamato = null;

            protected function TestoCambiato(Control $sender): void
            {
                $this->Chiamato = $sender;
            }
        };

        $r = self::Dal('<dw:RichTextBox id="r" AutoPostBack="true" OnTextChanged="TestoCambiato" />', $pagina);

        $p->Contiene('AutoPostBack senza ritardo: il postback all\'uscita', 'data-dw-change="1" data-dw-id="r"', $r->Render());

        $r->RaisePostBackEvent('change', '');
        $p->Uguale('al cambio scatta l\'handler, con il controllo come sender', $r, $pagina->Chiamato);

        $pagina->Chiamato = null;
        $r->Enabled = false;
        $r->RaisePostBackEvent('change', '');
        $p->Uguale('spento l\'evento non scatta', null, $pagina->Chiamato);
        $p->Manca('e non c\'e\' il marcatore', 'data-dw-change', $r->Render());

        $r = self::Dal('<dw:RichTextBox id="r" AutoPostBack="true" AutoPostBackDelay="500" OnTextChanged="TestoCambiato" />', $pagina);

        $p->Contiene('con il ritardo: mentre si scrive, trattenuto', 'data-dw-input="1" data-dw-id="r" data-dw-delay="500"', $r->Render());

        $r->RaisePostBackEvent('input', '');
        $p->Uguale('e l\'handler scatta anche per input', $r, $pagina->Chiamato);

        $p->Manca('senza AutoPostBack niente marcatore', 'data-dw-',
            str_replace(['data-dw-rte', 'data-dw-client'], '', self::Dal('<dw:RichTextBox id="r" />')->Render()));

        // --- lo stato

        $r = new RichTextBox();
        $r->Id = 'r';
        $r->Text = '<p>salvato</p>';
        $r->EnableTable = false;
        $r->EnableSourceView = true;
        $r->FontNames = 'Roboto';

        $tornato = new RichTextBox();
        $tornato->LoadViewState(ViewState::Unpack(ViewState::Pack($r->SaveViewState())));

        $p->Uguale('il testo attraversa il pacchetto vero', '<p>salvato</p>', $tornato->Text);
        $p->Uguale('le funzioni spente restano spente', false, $tornato->EnableTable);
        $p->Uguale('quelle accese restano accese', true, $tornato->EnableSourceView);
        $p->Uguale('e i caratteri scelti', 'Roboto', $tornato->FontNames);
    }

    private static function Configurazione(RichTextBox $r): array
    {
        preg_match('/data-dw-rte="([^"]*)"/', $r->Render(), $m);

        return json_decode(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'), true);
    }

    private static function Dal(string $markup, ?Page $pagina = null): RichTextBox
    {
        /** @var RichTextBox $r */
        $r = ControlBuilder::Build(PageParser::ParseTesto($markup), $pagina)[0];

        return $r;
    }
}

/**
 * La pagina delle note della prova: $Salvate fa da database, e le variabili della pagina
 * restano fra un click e l'altro come in ogni pagina del motore.
 */
final class PaginaNote extends Page
{
    /** @var array<int,string> id => HTML salvato */
    public array $Salvate = [];

    /** Quante righe ha salvato l'ultimo Salva tutto. */
    public int $Contate = 0;

    /** Le righe dal "database". */
    public function Lega(): void
    {
        $rpt = $this->FindControl('__Repeater_Note');

        $rpt->DataSource = array_map(static fn(int $id): array => ['Id' => $id], array_keys($this->Salvate));
        $rpt->DataBind();
    }

    protected function RigaLegata(\Common\WebForms\Controls\Repeater $sender, \Common\WebForms\Controls\RepeaterItem $riga): void
    {
        $riga->FindControl('__RichTextBox_Testo')->Text = $this->Salvate[(int)$riga->DataItem['Id']] ?? '';
    }

    /** Il Salva di una riga: dal bottone alla riga, dalla riga all'id e all'editor. */
    protected function SalvaRiga(Control $sender): void
    {
        $this->Salva($sender->NamingContainer());
    }

    /**
     * Aggiunge una nota vuota e rilega. Le righe che c'erano tengono il testo che l'utente ci
     * ha scritto: rilegare le rifarebbe dal database, quindi prima lo si mette da parte.
     */
    protected function Aggiungi(): void
    {
        $scritti = [];

        foreach ($this->FindControl('__Repeater_Note')->Items() as $riga)
            $scritti[(int)$riga->FindControl('__Hidden_Id')->Value] = $riga->FindControl('__RichTextBox_Testo')->Text;

        $this->Salvate[($this->Salvate === [] ? 0 : max(array_keys($this->Salvate))) + 1] = '';

        $this->Lega();

        foreach ($this->FindControl('__Repeater_Note')->Items() as $riga)
        {
            $id = (int)$riga->FindControl('__Hidden_Id')->Value;

            if (array_key_exists($id, $scritti))
                $riga->FindControl('__RichTextBox_Testo')->Text = $scritti[$id];
        }
    }

    protected function SalvaTutto(): void
    {
        $this->Contate = 0;

        foreach ($this->FindControl('__Repeater_Note')->Items() as $riga)
        {
            $this->Salva($riga);
            $this->Contate++;
        }
    }

    private function Salva(Control $riga): void
    {
        $this->Salvate[(int)$riga->FindControl('__Hidden_Id')->Value] = $riga->FindControl('__RichTextBox_Testo')->Text;
    }
}
