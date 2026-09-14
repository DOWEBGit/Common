<?php
declare(strict_types=1);

namespace Common\WebForms\ProveAMano;

/**
 * GENERATO AUTOMATICAMENTE da Stato.php - non modificare a mano.
 *
 * Handler richiamati dal markup:
 * @see \Common\WebForms\ProveAMano\Stato::PostbackClick()
 * @see \Common\WebForms\ProveAMano\Stato::ToccaClick()
 * @see \Common\WebForms\ProveAMano\Stato::VoloClick()
 */
trait StatoDesigner
{
    public \Common\WebForms\ProveAMano\Cornice $Master;
    public \Common\WebForms\Controls\Literal $litClick;
    public \Common\WebForms\Controls\Button $btnPostback;
    public \Common\WebForms\Controls\Repeater $rpt;
    public \Common\WebForms\Controls\PlaceHolder $phSempre;
    public \Common\WebForms\Controls\Button $btnVolo;
    public \Common\WebForms\Controls\Literal $litVolo;
    public \Common\WebForms\Controls\PlaceHolder $phVolatile;
}
