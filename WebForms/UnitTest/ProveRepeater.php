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
        $p->Uguale('la seconda riga e\' AlternatingItem', RepeaterItem::ALTERNATO, $rpt->Items()[1]->ItemType);
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

        // --- senza handler lo stato resta magro

        $senza = self::Costruisci(self::Pagina(), '');

        $senza->DataSource = [['Id' => 7], ['Id' => 9]];
        $senza->DataBind();

        $p->Uguale('senza OnItemDataBound lo stato non porta i controlli di riga',
            false, array_key_exists('Rows', $senza->SaveViewState()));

        $p->Uguale('con OnItemDataBound li porta',
            true, array_key_exists('Rows', $rpt->SaveViewState()));

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
            . '</ItemTemplate>'
            . '</dw:Repeater>';

        $controlli = ControlBuilder::Build(PageParser::ParseTesto($markup), $pagina);

        /** @var Repeater $rpt */
        $rpt = $controlli[0];

        return $rpt;
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

                if ($riga->ItemType !== RepeaterItem::ITEM && $riga->ItemType !== RepeaterItem::ALTERNATO)
                    return;

                /** @var Literal $nome */
                $nome = $riga->FindControl('litNome');

                $nome->Text = 'riga ' . $riga->FindControl('hidId')->Value;
            }
        };
    }
}
