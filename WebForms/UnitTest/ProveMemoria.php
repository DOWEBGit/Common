<?php
declare(strict_types=1);

namespace Common\WebForms\UnitTest;

use Common\WebForms\ControlBuilder;
use Common\WebForms\Controls\Label;
use Common\WebForms\Controls\Panel;
use Common\WebForms\Controls\Repeater;
use Common\WebForms\Page;
use Common\WebForms\PageParser;
use Common\WebForms\ViewState;

/**
 * Lo stato cala quando calano i dati: niente resta appeso.
 *
 * Il dubbio e' legittimo: una pagina si porta dietro il suo stato a ogni postback, andata e
 * ritorno. Se svuotare un elenco o rimpiazzare un pannello lasciasse in giro pezzi di stato
 * vecchio, il pacchetto crescerebbe a ogni click e la banda con lui. Qui si misura: si riempie, si svuota, si rimpiazza, e il pacchetto deve
 * tornare piccolo com'era. Non "piu' piccolo": com'era.
 *
 * Sul server non c'e' niente da misurare per costruzione: l'oggetto pagina nasce e muore in
 * una richiesta, e non esiste nessuna sessione. Quello che sopravvive e' SOLO il pacchetto.
 */
class ProveMemoria
{
    public static function Esegui(Prova $p): void
    {
        $p->Sezione('memoria: lo stato cala con i dati');

        // --- la misura di partenza: pagina vuota

        $vuota = self::Pagina();

        $base = strlen(self::Pacco($vuota));

        // --- 500 righe in un Repeater del markup

        $piena = self::Pagina();

        self::Rilega($piena, 500);

        $pieno = strlen(self::Pacco($piena));

        $p->Uguale('500 righe pesano parecchio piu' . "'" . ' di zero', true, $pieno > $base + 2000);

        // --- ClearItems() e il pacchetto torna com'era

        $svuotata = self::Giro($piena);

        /** @var Repeater $rpt */
        $rpt = $svuotata->FindControl('rpt');

        $p->Uguale('le 500 righe sono tornate dal pacchetto', 500, count($rpt->Items()));

        $rpt->ClearItems();

        $dopoClear = strlen(self::Pacco($svuotata));

        $p->Uguale('dopo ClearItems() il pacchetto e\' tornato al peso della pagina vuota (a meno di pochi byte)',
            true, abs($dopoClear - $base) < 40, );

        $p->Uguale('e al giro dopo le righe sono zero', 0, count(self::Giro($svuotata)->FindControl('rpt')->Items()));

        // --- 500 controlli attaccati dal codice, poi rimpiazzati con uno

        $dinamica = self::Pagina();

        $pannello = new Panel();
        $pannello->Id = 'pnl';

        for ($i = 1; $i <= 500; $i++)
        {
            $eti = new Label();
            $eti->Id   = 'lbl' . $i;
            $eti->Text = 'Etichetta numero ' . $i . ' attaccata dal codice';

            $pannello->Add($eti);
        }

        $dinamica->FindControl('ph')->Add($pannello);

        $pesoDinamico = strlen(self::Pacco($dinamica));

        $p->Uguale('500 etichette attaccate dal codice pesano', true, $pesoDinamico > $base + 2000);

        //al giro dopo si REISTANZIA il pannello: uno nuovo, con dentro una sola etichetta,
        //al posto di quello con 500. E' quello che fa una pagina che ricostruisce un pezzo
        $rifatta = self::Giro($dinamica);

        $p->Uguale('le 500 etichette sono tornate', 500, count($rifatta->FindControl('pnl')->Controls));

        $ph = $rifatta->FindControl('ph');

        $nuovo = new Panel();
        $nuovo->Id = 'pnl';

        $una = new Label();
        $una->Id   = 'lblUnica';
        $una->Text = 'una sola';

        $nuovo->Add($una);

        $ph->Controls = [$nuovo];

        $nuovo->Parent = $ph;
        $nuovo->Page   = $rifatta;

        $dopoRimpiazzo = strlen(self::Pacco($rifatta));

        $p->Uguale('rimpiazzato il pannello, il pacchetto e\' tornato piccolo',
            true, $dopoRimpiazzo < $base + 400);

        $ultima = self::Giro($rifatta);

        $p->Uguale('e al giro dopo c\'e\' il pannello nuovo con una etichetta, non il vecchio',
            ['lblUnica'], array_map(static fn($c) => $c->Id, $ultima->FindControl('pnl')->Controls));

        // --- dieci giri a vuoto: il peso non deve muoversi di un byte

        $ferma = $ultima;
        $pesi  = [];

        for ($g = 0; $g < 10; $g++)
        {
            $ferma  = self::Giro($ferma);
            $pesi[] = strlen(self::Pacco($ferma));
        }

        $p->Uguale('dieci postback senza cambiare niente: il pacchetto non cresce di un byte',
            1, count(array_unique($pesi)));

        $p->Sezione('memoria: i numeri (informativi)');

        $p->Uguale(sprintf('pagina vuota %d B, 500 righe %d B, dopo ClearItems %d B, 500 dinamiche %d B, dopo rimpiazzo %d B',
            $base, $pieno, $dopoClear, $pesoDinamico, $dopoRimpiazzo), true, true);
    }

    private static function Pagina(): PaginaMemoria
    {
        $pagina = new PaginaMemoria();

        $albero = ControlBuilder::Build(PageParser::ParseTesto(
            '<dw:Repeater id="rpt" Tag="tbody" ItemTag="tr" DataKeyField="Id">'
            . '<ItemTemplate><td>{{Nome}}</td><td><dw:Label id="lbl" /></td></ItemTemplate>'
            . '</dw:Repeater>'
            . '<dw:PlaceHolder id="ph" />'
        ), $pagina);

        (new \ReflectionProperty(Page::class, 'Controls'))->setValue($pagina, $albero);
        (new \ReflectionProperty(Page::class, 'markupRoot'))->setValue($pagina, $albero);

        return $pagina;
    }

    private static function Rilega(PaginaMemoria $pagina, int $quante): void
    {
        $righe = [];

        for ($i = 1; $i <= $quante; $i++)
            $righe[] = ['Id' => $i, 'Nome' => 'Riga numero ' . $i . ' con un nome abbastanza lungo'];

        /** @var Repeater $rpt */
        $rpt = $pagina->FindControl('rpt');

        $rpt->DataSource = $righe;
        $rpt->DataBind();

        //e ogni riga si veste dal codice, cosi' c'e' anche il delta delle righe nel pacchetto
        foreach ($rpt->Items() as $riga)
            $riga->FindControl('lbl')->Text = 'vestita ' . $riga->ItemIndex;
    }

    private static function Pacco(Page $pagina): string
    {
        return ViewState::Pack((new \ReflectionMethod($pagina, 'SaveViewState'))->invoke($pagina));
    }

    private static function Giro(PaginaMemoria $pagina): PaginaMemoria
    {
        $pacco = self::Pacco($pagina);

        $nuova = self::Pagina();

        (new \ReflectionMethod($nuova, 'LoadViewState'))->invoke($nuova, ViewState::Unpack($pacco));

        return $nuova;
    }
}

final class PaginaMemoria extends Page
{
}
