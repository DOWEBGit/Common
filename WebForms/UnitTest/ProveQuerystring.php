<?php
declare(strict_types=1);

namespace Common\WebForms\UnitTest;

use Common\WebForms\ControlBuilder;
use Common\WebForms\Controls\Repeater;
use Common\WebForms\Page;
use Common\WebForms\PageParser;
use Common\WebForms\ViewState;

/**
 * La querystring cambia, lo stato resta, e a ricaricare l'elenco ci pensa la pagina.
 *
 * Con il modo WinForms acceso, tornare su una pagina con un parametro diverso
 * nell'indirizzo - un altro mese - NON e' una pagina nuova: lo stato rientra, IsPostBack e'
 * vero, e la $_GET e' quella nuova. Il motore si ferma li'. Cosa ricaricare lo decide la
 * pagina in OnLoad: confronta il parametro con quello che si ricorda, e se e' cambiato
 * svuota l'elenco e lo rilega. Se non e' cambiato, non tocca niente e le righe arrivano
 * dallo stato.
 *
 * L'elenco qui sono i giorni di un mese, numero e nome del giorno, e il mese arriva da
 * ?mese=AAAA-MM.
 */
class ProveQuerystring
{
    public static function Esegui(Prova $p): void
    {
        $p->Sezione('querystring: la $_GET cambia, la pagina decide');

        // --- primo caricamento, settembre

        $pagina = self::Apri(['mese' => '2026-09'], null);

        $p->Uguale('primo caricamento: settembre ha 30 giorni', 30, count(self::Righe($pagina)));
        $p->Uguale('il primo giorno e\' scritto come numero e nome', '1 martedì', self::Righe($pagina)[0]);
        $p->Uguale('e l\'ultimo anche', '30 mercoledì', self::Righe($pagina)[29]);
        $p->Uguale('la pagina si ricorda il mese', '2026-09', $pagina->Mese);
        $p->Uguale('e ha legato una volta', 1, $pagina->Legature);

        // --- si torna con la STESSA querystring: e' un ripristino, niente si ricarica

        $stessa = self::Apri(['mese' => '2026-09'], $pagina);

        $p->Uguale('stessa querystring: le righe arrivano dallo stato', 30, count(self::Righe($stessa)));
        $p->Uguale('e la pagina non ha rilegato niente', 0, $stessa->Legature);

        // --- si torna con un ALTRO mese: stato rientrato, ma la pagina vede la differenza

        $ottobre = self::Apri(['mese' => '2026-10'], $stessa);

        $p->Uguale('altro mese: la pagina lo vede in $_GET e rilega', 1, $ottobre->Legature);
        $p->Uguale('ottobre ha 31 giorni', 31, count(self::Righe($ottobre)));
        $p->Uguale('il primo di ottobre e\' un giovedi\'', '1 giovedì', self::Righe($ottobre)[0]);
        $p->Manca('e di settembre non e\' rimasto niente', '30 mercoledì', implode('|', self::Righe($ottobre)));
        $p->Uguale('la pagina ora si ricorda ottobre', '2026-10', $ottobre->Mese);

        //quello che NON era l'elenco e' rimasto: un contatore di pagina, per dire
        $p->Uguale('il resto dello stato e\' passato indenne', 3, $ottobre->Visite);

        // --- e senza querystring si tiene il mese che si aveva

        $senza = self::Apri([], $ottobre);

        $p->Uguale('senza parametro si resta sul mese di prima', '2026-10', $senza->Mese);
        $p->Uguale('e non si rilega', 0, $senza->Legature);
        $p->Uguale('le 31 righe sono ancora li\'', 31, count(self::Righe($senza)));

        // --- Svuota() da solo lascia l'elenco vuoto

        /** @var Repeater $rpt */
        $rpt = $senza->FindControl('rptGiorni');

        $rpt->ClearItems();

        $p->Uguale('ClearItems() toglie tutte le righe', 0, count($rpt->Items()));
        $p->Uguale('e al giro dopo restano zero', 0, count(self::Righe(self::Apri([], $senza))));
    }

    /**
     * Apre la pagina con quella querystring. Se c'e' una pagina precedente, e' un ritorno:
     * il suo stato rientra, come fa il modo WinForms, e IsPostBack e' vero.
     */
    private static function Apri(array $get, ?PaginaMese $precedente): PaginaMese
    {
        $_GET = $get;

        $pagina = new PaginaMese();

        $albero = ControlBuilder::Build(PageParser::ParseTesto(
            '<dw:Repeater id="rptGiorni" Tag="ul" ItemTag="li" DataKeyField="Giorno">'
            . '<ItemTemplate>{{Giorno}} {{Nome}}</ItemTemplate></dw:Repeater>'
        ), $pagina);

        (new \ReflectionProperty(Page::class, 'Controls'))->setValue($pagina, $albero);
        (new \ReflectionProperty(Page::class, 'markupRoot'))->setValue($pagina, $albero);

        if ($precedente !== null)
        {
            $pacco = ViewState::Pack((new \ReflectionMethod($precedente, 'SaveViewState'))->invoke($precedente));

            (new \ReflectionMethod($pagina, 'LoadViewState'))->invoke($pagina, ViewState::Unpack($pacco));

            $pagina->IsPostBack = true;
        }

        //l'ordine del motore: prima lo stato, poi OnLoad
        (new \ReflectionMethod($pagina, 'OnLoad'))->invoke($pagina);

        return $pagina;
    }

    /** @return string[] le righe rese, "numero nome" */
    private static function Righe(PaginaMese $pagina): array
    {
        preg_match_all('/<li[^>]*>(.*?)<\/li>/', $pagina->FindControl('rptGiorni')->Render(), $m);

        return $m[1];
    }
}

/** La pagina del calendario: un mese dalla querystring, un giorno per riga. */
final class PaginaMese extends Page
{
    /** Il mese mostrato: e' con questo che OnLoad capisce se la querystring e' cambiata. */
    public string $Mese = '';

    /** Quante volte e' passata di qui: e' stato qualunque, e deve restare. */
    public int $Visite = 0;

    /** Quante volte ha rilegato IN QUESTA richiesta: #[Transient], serve solo alla prova. */
    #[\Common\WebForms\Transient]
    public int $Legature = 0;

    private const GIORNI = ['domenica', 'lunedì', 'martedì', 'mercoledì', 'giovedì', 'venerdì', 'sabato'];

    protected function OnLoad(): void
    {
        $this->Visite++;

        //la querystring puo' essere cambiata anche se IsPostBack e' vero: e' un ritorno sulla
        //pagina con un parametro diverso. Il motore ha rimesso lo stato di prima; se il mese
        //non e' piu' quello, l'elenco di prima non vale e va rifatto
        $richiesto = (string)($_GET['mese'] ?? $this->Mese);

        if ($richiesto === '')
            $richiesto = date('Y-m');

        if ($richiesto === $this->Mese)
            return;

        $this->Mese = $richiesto;

        $this->Rilega();
    }

    private function Rilega(): void
    {
        /** @var Repeater $rpt */
        $rpt = $this->FindControl('rptGiorni');

        $rpt->ClearItems();

        $primo = new \DateTimeImmutable($this->Mese . '-01');

        $righe = [];

        for ($g = 1; $g <= (int)$primo->format('t'); $g++)
            $righe[] = [
                'Giorno' => $g,
                'Nome'   => self::GIORNI[(int)$primo->setDate((int)$primo->format('Y'), (int)$primo->format('m'), $g)->format('w')],
            ];

        $rpt->DataSource = $righe;
        $rpt->DataBind();

        $this->Legature++;
    }
}
