<?php
declare(strict_types=1);

namespace Common\WebForms\UnitTest;

use Common\WebForms\ControlBuilder;
use Common\WebForms\Controls\Literal;
use Common\WebForms\Controls\Repeater;
use Common\WebForms\Controls\RepeaterItem;
use Common\WebForms\Page;
use Common\WebForms\PageParser;

/**
 * Il Repeater riempito in codice: OnItemDataBound.
 *
 * E' la forma delle pagine WK - template con celle vuote, e a riempirle e' il codice - e ha
 * due punti delicati che qui si tengono fermi:
 *
 *   1. l'evento scatta al DataBind e SOLO al DataBind. Se scattasse anche ricostruendo le
 *      righe dallo stato, ogni postback rifarebbe le letture della pagina intera;
 *   2. quello che il codice ha scritto nelle celle deve sopravvivere a un postback che non
 *      ridatabinda. Senza, aprire una scheda svuoterebbe la griglia sotto.
 */
class ProveRepeater
{
    public static function Esegui(Prova $p): void
    {
        $p->Sezione('Repeater: righe riempite in codice');

        $pagina = self::Pagina();

        $rpt = self::Costruisci($pagina, 'OnItemDataBound="Riempi"');

        $rpt->DataSource = [['Id' => 7], ['Id' => 9], ['Id' => 11]];
        $rpt->DataBind();

        $p->Uguale('l\'evento scatta una volta per riga', 3, $pagina->Chiamate);

        $p->Uguale('le righe rese sono i RepeaterItem', 3, count($rpt->Items()));

        $p->Uguale('la prima riga e\' Item', RepeaterItem::ITEM, $rpt->Items()[0]->ItemType);
        $p->Uguale('la seconda riga e\' AlternatingItem', RepeaterItem::ALTERNATING_ITEM, $rpt->Items()[1]->ItemType);
        $p->Uguale('l\'indice di riga segue la posizione nella pagina', 2, $rpt->Items()[2]->ItemIndex);

        //la riga trova i propri controlli con l'id NUDO del markup: chi scrive la pagina non
        //deve sapere che sotto c'e' un suffisso
        $p->Contiene('quello che l\'handler ha scritto finisce nell\'HTML', 'riga 7', $rpt->Render());
        $p->Contiene('ogni riga ha il suo valore', 'riga 11', $rpt->Render());

        $p->Uguale('il campo chiave della riga arriva dal segnaposto del template',
            '9', $rpt->Items()[1]->FindControl('hidId')->Value);

        // --- lo stato

        $stato = $rpt->SaveViewState();

        $secondo = self::Costruisci(self::Pagina(), 'OnItemDataBound="Riempi"');

        $secondo->LoadViewState($stato);

        $p->Uguale('ricostruendo dallo stato l\'evento NON scatta', 0, $secondo->Page->Chiamate);

        $p->Contiene('quello che l\'handler aveva scritto sopravvive al postback',
            'riga 7', $secondo->Render());

        //il DataItem vale solo dentro l'handler: fuori la riga non porta piu' i dati, e chi
        //li vuole se li rilegge dall'id come fanno le pagine WK
        $p->Uguale('fuori dal DataBind la riga non tiene i dati', [], $rpt->Items()[0]->DataItem);

        // --- lo stato porta la differenza, non tutto

        //nessuno ha toccato niente: le celle si ricavano dai segnaposto, che si rifanno dai
        //dati della riga, che sono gia' nello stato. Salvarle di nuovo sarebbe spreco puro.
        $intatto = self::Costruisci(self::Pagina(), '');

        $intatto->DataSource = [['Id' => 7], ['Id' => 9]];
        $intatto->DataBind();

        $p->Uguale('righe non toccate: lo stato non porta i controlli di riga',
            false, array_key_exists('Rows', $intatto->SaveViewState()));

        $p->Uguale('quello che ha scritto l\'handler invece lo porta',
            true, array_key_exists('Rows', $rpt->SaveViewState()));

        //e porta SOLO i controlli cambiati: l'handler tocca litNome, non hidId ne' lnkApri
        $p->Uguale('e porta solo i controlli che il codice ha cambiato',
            ['litNome__7', 'litNome__9', 'litNome__11'],
            array_keys($rpt->SaveViewState()['Rows']));

        // --- lo stile messo dal codice sui controlli DENTRO le righe

        //Un LinkButton di riga colorato secondo il dato - un ordine in ritardo, una scorta
        //sotto soglia - e' il caso normale del Repeater riempito in codice. Lo stile e' stato
        //dei controlli come tutto il resto, quindi vive dove vive lo stato di riga: nel
        //ramo 'Rows', che c'e' solo se c'e' un OnItemDataBound.
        $vestito = self::Costruisci(self::Pagina(), 'OnItemDataBound="Riempi"');

        $vestito->DataSource = [['Id' => 7], ['Id' => 9]];
        $vestito->DataBind();

        $apri = $vestito->Items()[0]->FindControl('lnkApri');

        $apri->Style->Add('color', 'crimson');
        $apri->Attributes->Add('title', 'in ritardo');
        $apri->CssClass = 'rosso';

        $dopo = self::Costruisci(self::Pagina(), 'OnItemDataBound="Riempi"');

        $dopo->LoadViewState($vestito->SaveViewState());

        $p->Contiene('lo stile in linea di un controllo di riga sopravvive al postback',
            'style="color:crimson"', $dopo->Render());

        $p->Contiene('e cosi' . "'" . ' l\'attributo aggiunto dal codice',
            'title="in ritardo"', $dopo->Render());

        $p->Contiene('e la classe', 'class="rosso"', $dopo->Render());

        //E vale anche SENZA handler, vestendo le righe dopo il DataBind(): il Repeater non
        //guarda da dove arriva la modifica, guarda se la riga e' diversa da com'e' nata.
        $nudo = self::Costruisci(self::Pagina(), '');

        $nudo->DataSource = [['Id' => 7]];
        $nudo->DataBind();

        $nudo->Items()[0]->FindControl('lnkApri')->Style->Add('color', 'crimson');

        $rifatto = self::Costruisci(self::Pagina(), '');

        $rifatto->LoadViewState($nudo->SaveViewState());

        $p->Contiene('lo stile messo dopo il DataBind, senza handler, sopravvive lo stesso',
            'color:crimson', $rifatto->Render());

        //due giri di postback di fila: la differenza salvata non deve sciogliersi per strada
        $terzo = self::Costruisci(self::Pagina(), '');

        $terzo->LoadViewState($rifatto->SaveViewState());

        $p->Contiene('e sopravvive anche al postback dopo, e a quello dopo ancora',
            'color:crimson', $terzo->Render());

        // --- un Repeater dentro l'altro

        //Il caso della griglia con le sottorighe: un ordine e le sue voci. Quello annidato si
        //salva tutto da solo, quindi quello di fuori non deve scendere nelle sue righe -
        //altrimenti le stesse righe finirebbero nello stato due volte, e a ogni livello in
        //piu' si moltiplicherebbero.
        $fuori = self::Annidati();

        $stato = $fuori->SaveViewState();

        $p->Uguale('lo stato di fuori si ferma sul Repeater annidato, non scende nelle sue righe',
            ['dentro__1', 'dentro__2'], array_keys($stato['Rows']));

        $rifatto = self::Annidati(false);

        $rifatto->LoadViewState($stato);

        $p->Uguale('e nonostante questo le sottorighe tornano tutte', $fuori->Render(), $rifatto->Render());

        // --- la chiave e' obbligatoria

        $p->Solleva('una riga senza campo chiave si ferma subito, con il nome del campo',
            'la riga non ha il campo chiave "Id"',
            static function () use ($pagina): void
            {
                $rotto = self::Costruisci($pagina, '');

                $rotto->DataSource = [['Nome' => 'senza id']];
                $rotto->DataBind();
            });
    }

    /** Un Repeater vero, costruito dal markup come lo costruirebbe una pagina. */
    private static function Costruisci(Page $pagina, string $attributi): Repeater
    {
        $markup = '<dw:Repeater id="rpt" Tag="tbody" ItemTag="tr" DataKeyField="Id" ' . $attributi . '>'
            . '<ItemTemplate>'
            . '<td><dw:Literal id="litNome" /></td>'
            . '<td><dw:HiddenField id="hidId" Value="{{Id}}" /></td>'
            . '<td><dw:LinkButton id="lnkApri" Text="apri" /></td>'
            . '</ItemTemplate>'
            . '</dw:Repeater>';

        $controlli = ControlBuilder::Build(PageParser::ParseTesto($markup), $pagina);

        /** @var Repeater $rpt */
        $rpt = $controlli[0];

        return $rpt;
    }

    /**
     * Un Repeater dentro l'ItemTemplate di un altro, riempito su due livelli.
     *
     * @param bool $riempi false per averlo vuoto, da ricostruire poi dallo stato
     */
    private static function Annidati(bool $riempi = true): Repeater
    {
        $markup = '<dw:Repeater id="fuori" Tag="div" ItemTag="div" DataKeyField="Id">'
            . '<ItemTemplate>'
            . '<b>{{Nome}}</b>'
            . '<dw:Repeater id="dentro" Tag="ul" ItemTag="li" DataKeyField="Id">'
            . '<ItemTemplate>{{Voce}}</ItemTemplate>'
            . '</dw:Repeater>'
            . '</ItemTemplate>'
            . '</dw:Repeater>';

        /** @var Repeater $fuori */
        $fuori = ControlBuilder::Build(PageParser::ParseTesto($markup), self::Pagina())[0];

        if (!$riempi)
            return $fuori;

        $fuori->DataSource = [['Id' => 1, 'Nome' => 'Alfa'], ['Id' => 2, 'Nome' => 'Beta']];
        $fuori->DataBind();

        foreach ($fuori->Items() as $riga)
        {
            /** @var Repeater $dentro */
            $dentro = $riga->FindControl('dentro');

            $dentro->DataSource = [['Id' => 10, 'Voce' => 'x'], ['Id' => 11, 'Voce' => 'y']];
            $dentro->DataBind();
        }

        return $fuori;
    }

    /** Una pagina finta: conta le chiamate e riempie la cella come farebbe una pagina vera. */
    private static function Pagina(): Page
    {
        return new class extends Page
        {
            public int $Chiamate = 0;

            protected function Riempi(Repeater $sender, RepeaterItem $riga): void
            {
                $this->Chiamate++;

                if ($riga->ItemType !== RepeaterItem::ITEM && $riga->ItemType !== RepeaterItem::ALTERNATING_ITEM)
                    return;

                /** @var Literal $nome */
                $nome = $riga->FindControl('litNome');

                $nome->Text = 'riga ' . $riga->FindControl('hidId')->Value;
            }
        };
    }
}
