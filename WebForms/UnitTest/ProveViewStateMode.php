<?php
declare(strict_types=1);

namespace Common\WebForms\UnitTest;

use Common\WebForms\Control;
use Common\WebForms\ControlBuilder;
use Common\WebForms\Controls\Label;
use Common\WebForms\Controls\Repeater;
use Common\WebForms\Page;
use Common\WebForms\PageParser;

/**
 * ViewStateMode: acceso per tutti, spento dove lo si dice, ereditato dai figli.
 *
 * E' l'interruttore di WebForms, con le stesse regole: Inherit segue il padre, la pagina e'
 * Enabled, un Disabled spegne tutto il ramo, un figlio puo' riaccendersi. Sotto uno spento i
 * controlli del markup restano - coi valori del markup - e quelli attaccati dal codice no.
 */
class ProveViewStateMode
{
    public static function Esegui(Prova $p): void
    {
        $p->Sezione('ViewStateMode');

        // --- l'eredita'

        $pagina = self::Pagina(
            '<dw:Panel id="spento" ViewStateMode="Disabled">'
            . '<dw:Label id="dentro" />'
            . '<dw:Panel id="riacceso" ViewStateMode="Enabled"><dw:Label id="salvo" /></dw:Panel>'
            . '</dw:Panel>'
            . '<dw:Label id="fuori" />'
        );

        $p->Uguale('senza dire niente lo stato e\' acceso', true, $pagina->FindControl('fuori')->IsViewStateEnabled());
        $p->Uguale('Disabled spegne il controllo', false, $pagina->FindControl('spento')->IsViewStateEnabled());
        $p->Uguale('e il figlio lo eredita', false, $pagina->FindControl('dentro')->IsViewStateEnabled());
        $p->Uguale('un figlio Enabled si riaccende dentro un ramo spento', true, $pagina->FindControl('salvo')->IsViewStateEnabled());

        $p->Uguale('il valore si scrive in forma canonica, comunque lo si sia scritto',
            Control::DISABLED, $pagina->FindControl('spento')->ViewStateMode);

        // --- cosa sopravvive al giro

        $pagina->FindControl('dentro')->Text = 'scritto dal codice, sotto uno spento';
        $pagina->FindControl('salvo')->Text  = 'scritto dal codice, dentro il riacceso';
        $pagina->FindControl('fuori')->Text  = 'scritto dal codice, fuori';

        $stato = self::Salva($pagina);

        $p->Uguale('lo spento non e\' nello stato', false, isset($stato['c']['spento']));
        $p->Uguale('e nemmeno il figlio che eredita', false, isset($stato['c']['dentro']));
        $p->Uguale('il riacceso si', true, isset($stato['c']['salvo']));
        $p->Uguale('e quello fuori dal ramo, ovviamente', true, isset($stato['c']['fuori']));

        $dopo = self::Pagina(
            '<dw:Panel id="spento" ViewStateMode="Disabled">'
            . '<dw:Label id="dentro" Text="dal markup" />'
            . '<dw:Panel id="riacceso" ViewStateMode="Enabled"><dw:Label id="salvo" /></dw:Panel>'
            . '</dw:Panel>'
            . '<dw:Label id="fuori" />'
        );

        self::Carica($dopo, $stato);

        $p->Uguale('sotto uno spento il controllo del markup c\'e' . "'" . ' ancora, coi valori del markup',
            'dal markup', $dopo->FindControl('dentro')->Text);
        $p->Uguale('quello riacceso ha ancora quello che il codice gli aveva scritto',
            'scritto dal codice, dentro il riacceso', $dopo->FindControl('salvo')->Text);
        $p->Uguale('e quello fuori pure',
            'scritto dal codice, fuori', $dopo->FindControl('fuori')->Text);

        // --- i figli attaccati dal codice sotto uno spento non tornano

        $pagina = self::Pagina('<dw:Panel id="spento" ViewStateMode="Disabled" /><dw:Panel id="acceso" />');

        foreach (['spento', 'acceso'] as $dove)
        {
            $eti = new Label();
            $eti->Id   = 'dinamica_' . $dove;
            $eti->Text = 'attaccata dal codice';

            $pagina->FindControl($dove)->Add($eti);
        }

        $dopo = self::Pagina('<dw:Panel id="spento" ViewStateMode="Disabled" /><dw:Panel id="acceso" />');

        self::Carica($dopo, self::Salva($pagina));

        $p->Uguale('il figlio dinamico sotto lo spento non torna: lo si ricrea in OnInit',
            0, count($dopo->FindControl('spento')->Controls));
        $p->Uguale('quello sotto l\'acceso torna come sempre',
            1, count($dopo->FindControl('acceso')->Controls));

        // --- un Repeater spento non porta le righe

        $pagina = self::Pagina(
            '<dw:Repeater id="rpt" ViewStateMode="Disabled" DataKeyField="Id">'
            . '<ItemTemplate>{{Nome}}</ItemTemplate></dw:Repeater>'
        );

        /** @var Repeater $rpt */
        $rpt = $pagina->FindControl('rpt');

        $rpt->DataSource = [['Id' => 1, 'Nome' => 'Alfa'], ['Id' => 2, 'Nome' => 'Beta']];
        $rpt->DataBind();

        $stato = self::Salva($pagina);

        $p->Uguale('un Repeater spento non e\' nello stato: niente Items, niente righe',
            false, isset($stato['c']['rpt']));

        $p->Uguale('e infatti il pacchetto e\' vuoto di controlli', [], $stato['c']);

        // --- un valore che non esiste si ferma subito

        $p->Solleva('un ViewStateMode sconosciuto si ferma al markup, con i tre valori buoni',
            'vale Inherit, Enabled o Disabled',
            static fn() => self::Pagina('<dw:Panel id="x" ViewStateMode="Forse" />'));
    }

    /** Una pagina con l'albero costruito da questo markup, senza master e senza file. */
    private static function Pagina(string $markup): Page
    {
        $pagina = new class extends Page {
        };

        $albero = ControlBuilder::Build(PageParser::ParseTesto($markup), $pagina);

        //quello che fa ProcessRequest dopo aver costruito l'albero: questi sono i controlli
        //del markup, e la radice se lo segna per distinguerli da quelli attaccati dal codice
        (new \ReflectionProperty(Page::class, 'Controls'))->setValue($pagina, $albero);
        (new \ReflectionProperty(Page::class, 'markupRoot'))->setValue($pagina, $albero);

        return $pagina;
    }

    private static function Salva(Page $pagina): array
    {
        return (new \ReflectionMethod($pagina, 'SaveViewState'))->invoke($pagina);
    }

    private static function Carica(Page $pagina, array $stato): void
    {
        (new \ReflectionMethod($pagina, 'LoadViewState'))->invoke($pagina, $stato);
    }
}
