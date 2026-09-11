<?php
declare(strict_types=1);

namespace Common\WebForms\ProveAMano;

/**
 * GENERATO AUTOMATICAMENTE da Prima.php - non modificare a mano.
 *
 * Handler richiamati dal markup:
 * @see \Common\WebForms\ProveAMano\Prima::ContaClick()
 * @see \Common\WebForms\ProveAMano\Prima::RileggiClick()
 * @see \Common\WebForms\ProveAMano\Prima::PortaClick()
 */
trait PrimaDesigner
{
    public \Common\WebForms\ProveAMano\Cornice $Master;
    public \Common\WebForms\Controls\Literal $litContatore;
    public \Common\WebForms\Controls\Button $btnConta;
    public \Common\WebForms\Controls\TextBox $txtNota;
    public \Common\WebForms\Controls\Button $btnRileggi;
    public \Common\WebForms\Controls\Literal $litNota;
    public \Common\WebForms\Controls\TextBox $txtNome;
    public \Common\WebForms\Controls\Button $btnPorta;
    public \Common\WebForms\Controls\Literal $litPortato;
    public \Common\WebForms\Controls\CheckBox $chkTieni;
}
