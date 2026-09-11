<?php
declare(strict_types=1);

namespace Common\WebForms\Controls;

/**
 * Una riga di Repeater.
 *
 * E' il RepeaterItem di WebForms, e serve a chi riempie le righe in codice invece che con i
 * segnaposto del template: l'handler di OnItemDataBound riceve questo oggetto e da qui
 * raggiunge i propri controlli con FindControl, esattamente come l'e.Item delle pagine WK.
 *
 * ItemType esiste per la stessa ragione: le pagine WK cominciano tutte con
 *
 *   if ($riga->ItemType !== RepeaterItem::ITEM && $riga->ItemType !== RepeaterItem::ALTERNATING_ITEM)
 *       return;
 *
 * e quella riga deve continuare a leggersi allo stesso modo. Qui le righe sono solo di dati -
 * non ci sono intestazioni ne' separatori - quindi la guardia non scarta mai niente: e' il
 * prezzo di poter copiare una pagina da WK senza riscriverla.
 */
class RepeaterItem extends Panel
{
    public const ITEM = 'Item';
    public const ALTERNATING_ITEM = 'AlternatingItem';

    /** Posizione nella pagina corrente, base zero. */
    public int $ItemIndex = 0;

    public string $ItemType = self::ITEM;

    /**
     * I dati della riga, come array associativo.
     *
     * Vale SOLO dentro OnItemDataBound: fra un postback e l'altro il Repeater ricostruisce
     * le righe dal proprio stato e questo campo torna vuoto. Chi ha bisogno del dato dopo se
     * lo rilegge, come fa il codice WK con il GetItem sull'id del campo nascosto.
     */
    public array $DataItem = [];

    protected function ViewStateProperties(): array
    {
        return array_merge(parent::ViewStateProperties(), ['ItemIndex', 'ItemType']);
    }
}
