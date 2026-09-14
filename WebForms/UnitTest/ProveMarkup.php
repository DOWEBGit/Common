<?php
declare(strict_types=1);

namespace Common\WebForms\UnitTest;

use Common\WebForms\ControlBuilder;
use Common\WebForms\Designer;
use Common\WebForms\PageParser;

/**
 * Il compilatore del markup e il costruttore dei controlli.
 *
 * L'albero che esce da qui dev'essere DETERMINISTICO: dallo stesso markup, sempre gli stessi
 * controlli con gli stessi id nello stesso ordine. E' il vincolo su cui si regge tutto lo
 * stato, quindi e' quello che va tenuto sotto prova.
 */
class ProveMarkup
{
    public static function Esegui(Prova $p): void
    {
        self::Nodi($p);
        self::Segnaposti($p);
        self::Master($p);
        self::TagDiUserControl($p);
        self::IdAllaWk($p);
    }

    /**
     * La convenzione degli id di WK - __Literal_Nome, __Hidden_Id - deve passare dal markup al
     * designer al codebehind senza che il doppio underscore iniziale si confonda con il
     * suffisso __ dei contenitori di denominazione o con i campi __dw_ del runtime.
     */
    private static function IdAllaWk(Prova $p): void
    {
        $p->Sezione('gli id alla WK: __Tipo_Nome');

        $nodi = PageParser::ParseTesto('<dw:Literal id="__Literal_Nome" Text="x" /><dw:HiddenField id="__Hidden_Id" Value="7" />');
        $controlli = ControlBuilder::Build($nodi, null);

        $p->Uguale('l\'id resta com\'e\' scritto', '__Literal_Nome', $controlli[0]->Id);
        $p->Contiene('e nell\'HTML esce verbatim', 'id="__Hidden_Id"', $controlli[1]->Render());

        $cartella = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dw-prove-id-' . getmypid();

        mkdir($cartella);

        $markup = $cartella . DIRECTORY_SEPARATOR . 'Wk.php';

        file_put_contents($markup, '<?php ?><dw:Literal id="__Literal_Nome" /><dw:TextBox id="__TextBox_Iban" />');

        Designer::Update($markup, 'Common\WebForms\UnitTest\Wk\Wk');

        $designer = file_get_contents(Designer::DesignerPath($markup));

        $p->Contiene('il designer dichiara la proprieta\' con quel nome', 'Literal $__Literal_Nome;', $designer);
        $p->Contiene('e quella della casella', 'TextBox $__TextBox_Iban;', $designer);

        foreach (glob($cartella . DIRECTORY_SEPARATOR . '*') ?: [] as $file)
            unlink($file);

        rmdir($cartella);
    }

    /**
     * Un UserControl chiamato per nome: <dw:PageNavigator> invece di
     * <dw:UserControl src="UserControls/PageNavigator">.
     *
     * La regola e' tutta qui: se non e' un controllo del motore, e in UserControls/ c'e' un
     * markup che si chiama cosi', quello e'. Niente da registrare in cima al file.
     */
    private static function TagDiUserControl(Prova $p): void
    {
        $p->Sezione('UserControl chiamato per nome');

        //il markup si crea nella cartella VERA degli UserControl e non in una finta sotto la
        //temporanea: il motore parte dal percorso di se stesso, non da DOCUMENT_ROOT. Fingere
        //una radice qui vorrebbe dire provare una strada che in produzione non si percorre -
        //ed e' gia' successo: la prova era verde e il generatore da riga di comando, dove
        //DOCUMENT_ROOT non c'e', dichiarava tipi che non esistono.
        $cartella = dirname(__DIR__, 3) . '/' . ControlBuilder::FOLDER;

        $file = $cartella . '/Paginatore.php';

        @mkdir($cartella, 0777, true);

        file_put_contents($file, '<?php //markup di prova, cancellato dalle prove stesse');

        try
        {
            $p->Uguale('un markup in UserControls/ diventa un tag',
                'UserControls/Paginatore', ControlBuilder::TagSrc('Paginatore'));

            //i controlli del motore vincono: un UserControl chiamato "Panel" non deve poter
            //cambiare significato a un tag che tutti danno per scontato
            $p->Uguale('un controllo del motore non si fa scavalcare',
                '', ControlBuilder::TagSrc('Panel'));

            $p->Uguale('un nome che non e\' niente resta niente',
                '', ControlBuilder::TagSrc('NonEsisteProprio'));

            $p->Uguale('e un nome storto non diventa un percorso',
                '', ControlBuilder::TagSrc('../fuori'));
        }
        finally
        {
            @unlink($file);
        }
    }

    private static function Nodi(Prova $p): void
    {
        $p->Sezione('il compilatore del markup');

        $nodi = PageParser::ParseTesto(
            '<div><dw:Repeater id="r"><ItemTemplate><dw:Label id="l" /></ItemTemplate></dw:Repeater></div>');

        $p->Uguale('il markup letterale attorno resta letterale', 'html', $nodi[0]['t']);
        $p->Uguale('il Repeater e\' un controllo', 'Repeater', $nodi[1]['tipo']);
        $p->Uguale('l\'ItemTemplate e\' un nodo a se\'', 'tpl', $nodi[1]['figli'][0]['t']);
        $p->Uguale('i controlli del template stanno dentro il template',
            'Label', $nodi[1]['figli'][0]['figli'][0]['tipo']);

        $nodi = PageParser::ParseTesto('<dw:TextBox id="a" /><dw:TextBox id="b" />');

        $p->Uguale('due tag autochiusi sono due fratelli, non un annidamento', 2, count($nodi));

        $nodi = PageParser::ParseTesto('<dw:Panel id="p"><dw:TextBox id="a" /></dw:Panel>');

        $p->Uguale('un tag chiuso contiene i suoi figli', 1, count($nodi[0]['figli']));
    }

    private static function Segnaposti(Prova $p): void
    {
        $p->Sezione('i segnaposto {{Campo}}');

        //il difetto vero: in un ATTRIBUTO il valore veniva escapato due volte e all'utente
        //arrivava "L&#039;Oreal" sotto gli occhi
        $nodi = PageParser::ParseTesto('<dw:TextBox id="t" Text="{{Nome}}" />');
        $controlli = ControlBuilder::Build($nodi, null, ['Nome' => 'L\'Oreal']);

        $p->Contiene('un segnaposto in un attributo non viene escapato due volte',
            'value="L&#039;Oreal"', $controlli[0]->Render());

        //nel markup letterale invece va escapato, una volta: li' finisce dritto nell'HTML
        $nodi = PageParser::ParseTesto('<td>{{Nome}}</td>');
        $controlli = ControlBuilder::Build($nodi, null, ['Nome' => 'L\'Oreal']);

        $p->Contiene('un segnaposto nel markup letterale esce escapato',
            'L&#039;Oreal', $controlli[0]->Render());

        $nodi = PageParser::ParseTesto('<td>{{Manca}}</td>');
        $controlli = ControlBuilder::Build($nodi, null, ['Nome' => 'x']);

        //il <td> e il </td> sono markup letterale e restano: quello che deve sparire e' il
        //segnaposto, non il tag attorno
        $p->Uguale('un segnaposto senza valore diventa vuoto, non resta scritto',
            '<td></td>', $controlli[0]->Render());
    }

    private static function Master(Prova $p): void
    {
        $p->Sezione('le master page');

        //un <dw:Content> senza placeholder non saprebbe dove finire
        $p->Solleva('un Content senza placeholder si ferma subito', 'vuole l\'attributo placeholder',
            static function (): void
            {
                ControlBuilder::Contents(PageParser::ParseTesto('<dw:Content>ciao</dw:Content>'));
            });

        //fuori dai Content, in una pagina con master, il markup non avrebbe un posto dove
        //andare: sparirebbe in silenzio, ed e' peggio che dirlo
        $p->Solleva('il markup fuori dai Content si ferma subito', 'puo\' contenere solo',
            static function (): void
            {
                ControlBuilder::Contents(PageParser::ParseTesto('<p>fuori</p><dw:Content placeholder="c">x</dw:Content>'));
            });

        $contenuti = ControlBuilder::Contents(
            PageParser::ParseTesto("\n  <dw:Content placeholder=\"corpo\"><dw:Label id=\"l\" /></dw:Content>\n"));

        $p->Uguale('lo spazio bianco fuori dai Content non da\' fastidio', ['corpo'], array_keys($contenuti));
        $p->Uguale('il contenuto del segnaposto e\' quello dentro il Content',
            'Label', $contenuti['corpo'][0]['tipo']);
    }
}
