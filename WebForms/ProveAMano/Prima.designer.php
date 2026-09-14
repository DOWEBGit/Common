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
 * @see \Common\WebForms\ProveAMano\Prima::DataCambiata()
 * @see \Common\WebForms\ProveAMano\Prima::CambiaModoClick()
 * @see \Common\WebForms\ProveAMano\Prima::SalutaClick()
 * @see \Common\WebForms\ProveAMano\Prima::UtenteClick()
 */
trait PrimaDesigner
{
    public \Common\WebForms\ProveAMano\Cornice $Master;
    public \Common\WebForms\Controls\Literal $__Literal_Contatore;
    public \Common\WebForms\Controls\Button $__Button_Conta;
    public \Common\WebForms\Controls\TextBox $__TextBox_Nota;
    public \Common\WebForms\Controls\Button $__Button_Rileggi;
    public \Common\WebForms\Controls\Literal $__Literal_Nota;
    public \Common\WebForms\Controls\TextBox $__TextBox_Nome;
    public \Common\WebForms\Controls\Button $__Button_Porta;
    public \Common\WebForms\Controls\Literal $__Literal_Portato;
    public \Common\WebForms\Controls\DatePicker $__DatePicker_Giorno;
    public \Common\WebForms\Controls\DatePicker $__DatePicker_Quando;
    public \Common\WebForms\Controls\Button $__Button_CambiaModo;
    public \Common\WebForms\Controls\Literal $__Literal_Date;
    public \Common\WebForms\Controls\Button $__Button_Saluta;
    public \Common\WebForms\Controls\Button $__Button_Utente;
    public \Common\WebForms\Controls\Literal $__Literal_Saluti;
    public \Common\WebForms\Controls\CheckBox $__CheckBox_Tieni;
}
