<?php
declare(strict_types=1);

namespace Common\WebForms\Examples;

use Common\WebForms\Control;
use Common\WebForms\Controls\Literal;
use Common\WebForms\Controls\Repeater;
use Common\WebForms\Controls\RepeaterItem;
use Common\WebForms\Page;

class RepeaterExample extends Page
{
    use RepeaterDesigner;

    public int $Binds = 0;

    public int $Postbacks = 0;

    /** I dati stanno qui: l'esempio e' sul controllo, non sulle letture. */
    private const array ROWS = [
        ['Id' => 1, 'Name' => 'Alfa'],
        ['Id' => 2, 'Name' => 'Beta'],
        ['Id' => 3, 'Name' => 'Gamma'],
        ['Id' => 4, 'Name' => 'Delta'],
    ];

    private const array COLORS = ['#b91c1c', '#15803d', '#1d4ed8', '#a16207'];

    protected function OnLoad(): void
    {
        if (!$this->IsPostBack)
            $this->BindClick();
    }

    /**
     * L'handler per riga: scatta al DataBind() e solo li'. Ricostruendo le righe dallo stato
     * non scatta, altrimenti ogni postback rifarebbe tutte le letture.
     */
    protected function ItemDataBound(Repeater $sender, RepeaterItem $row): void
    {
        /** @var Literal $bound */
        $bound = $row->FindControl('__Literal_Bound');

        //DataItem vale SOLO qui dentro: fuori dal DataBind la riga non tiene piu' i dati
        $bound->Text = 'riga ' . ($row->ItemIndex + 1) . ($row->ItemType === RepeaterItem::ALTERNATING_ITEM ? ', alternata' : '')
            . ', nome di ' . mb_strlen((string)$row->DataItem['Name']) . ' lettere';
    }

    protected function BindClick(): void
    {
        $this->Binds++;

        $this->__Repeater_Items->DataSource = self::ROWS;
        $this->__Repeater_Items->DataBind();

        //vestite DOPO il DataBind, senza OnItemDataBound: il Repeater salva solo la differenza
        foreach ($this->__Repeater_Items->Items() as $row)
        {
            $colore = self::COLORS[$row->ItemIndex % count(self::COLORS)];

            $row->Style->Add('border-left', '4px solid ' . $colore);

            $touch = $row->FindControl('__LinkButton_Touch');

            $touch->Style->Add('color', $colore);
            $touch->Attributes->Add('title', 'Riga numero ' . ($row->ItemIndex + 1));

            /** @var Literal $note */
            $note = $row->FindControl('__Literal_Note');

            $note->Text = 'vestita dal codice alle ' . date('H:i:s');
        }
    }

    /** Via tutte le righe, e dallo stato: e' l'Items.Clear() di WebForms. */
    protected function ClearClick(): void
    {
        $this->__Repeater_Items->ClearItems();
    }

    /** Non tocca il Repeater: tutto quello che si vede ancora dopo arriva dallo stato. */
    protected function NothingClick(): void
    {
        $this->Postbacks++;
    }

    /** Dalla riga da cui e' partito il click ai suoi fratelli, con l'id nudo del template. */
    protected function TouchClick(Control $sender): void
    {
        $row = $sender->NamingContainer();

        /** @var Literal $note */
        $note = $row->FindControl('__Literal_Note');

        $note->Text = 'toccata alle ' . date('H:i:s');

        $row->Style->Add('background', '#fef9c3');
    }

    protected function OnPreRender(): void
    {
        $this->__Literal_Binds->Text     = (string)$this->Binds;
        $this->__Literal_Postbacks->Text = (string)$this->Postbacks;
    }
}
