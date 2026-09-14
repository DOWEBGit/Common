<?php
declare(strict_types=1);

namespace Common\WebForms\ProveAMano;

use Common\WebForms\Control;
use Common\WebForms\Controls\DatePicker;
use Common\WebForms\DateTimeMode;
use Common\WebForms\EntityEvents;
use Common\WebForms\Page;
use Common\WebForms\Portable;

/**
 * La prima delle due pagine collegate: cosa resta e cosa no, passando da una all'altra.
 *
 * Tre cose, e sono tre livelli diversi:
 *
 *   - le variabili e i controlli: vivono nel ViewState di QUESTA pagina. Sopravvivono ai click,
 *     non alla navigazione - si va altrove e si torna, e la pagina e' nuova;
 *   - #[Portable]: viaggia FRA le pagine, in un pacchetto firmato che sta nella memoria del
 *     browser. La chiave e' il nome della proprieta', quindi la seconda pagina lo legge
 *     dichiarando una proprieta' che si chiama uguale;
 *   - KeepState: il modo WinForms. Il browser si tiene lo stato di questa pagina e glielo
 *     rimanda quando ci si torna, e allora anche il primo livello sopravvive alla navigazione.
 */
class Prima extends Page
{
    use PrimaDesigner;

    public int $Conteggio = 0;

    /**
     * Il nome che viaggia fra le pagine.
     *
     * La chiave e' il NOME della proprieta': una proprieta' #[Portable] che si chiama
     * "NomePortato" anche sulla seconda pagina e' la stessa cosa. Non c'e' niente da passare
     * in querystring, e infatti l'indirizzo delle due pagine e' sempre lo stesso.
     */
    #[Portable]
    public string $NomePortato = '';

    /** Quanti saluti sono arrivati qui da altre pagine: aggiornato sul server, dall'evento. */
    public int $SalutiRicevuti = 0;

    /**
     * Le iscrizioni si dichiarano in OnInit, ad ogni richiesta: e' il posto in cui la pagina
     * dice a cosa risponde. Quando un altro browser chiama Notify('Saluti'), il runtime di
     * QUESTA pagina fa un postback vuoto con quel nome, e il motore chiama l'handler qui
     * dentro - sul server, con lo stato di questa pagina in mano. Da qui si fa quello che si
     * farebbe in un click qualunque: un avviso, una rilettura, un DataBind.
     */
    protected function OnInit(): void
    {
        $this->Subscribe('Saluti', function (): void
        {
            $this->SalutiRicevuti++;

            $this->Alert->Success('Qualcuno ha salutato alle ' . date('H:i:s') . '.');
        });
    }

    protected function OnLoad(): void
    {
        if ($this->IsPostBack)
            return;

        $this->Master->SetTitle(
            'Prima pagina',
            'Un contatore che vale qui, e un nome che va di la\'.'
        );
    }

    protected function ContaClick(): void
    {
        $this->Conteggio++;
    }

    protected function RileggiClick(): void
    {
        $this->litNota->Text = $this->txtNota->Text === ''
            ? 'la casella e\' vuota'
            : 'il server ha letto: "' . $this->txtNota->Text . '"';
    }

    protected function PortaClick(): void
    {
        $this->NomePortato = $this->txtNome->Text;

        $this->Alert->Success($this->NomePortato === ''
            ? 'Portato via il nome: adesso non c\'e\' niente.'
            : '"' . $this->NomePortato . '" viaggia con te: vai sulla seconda pagina.');
    }

    /** Uno dei due selettori ha cambiato data: si rilegge come data e si riscrive. */
    protected function DataCambiata(Control $sender): void
    {
        $this->Alert->Success(($sender->Id === 'dtGiorno' ? 'Giorno' : 'Quando') . ' cambiato.');
    }

    /**
     * Un messaggio con dati a tutti i browser del dominio, adesso. Arriva anche a questa
     * pagina: il ricevitore in Prima.js non distingue chi ha premuto il bottone.
     */
    /**
     * Annuncia il topic a tutto il dominio. Chi e' iscritto risponde sul suo server; questa
     * pagina no - il proprio evento si scarta, si e' gia' aggiornata con questo postback - e
     * quindi il suo avviso se lo fa da sola.
     */
    protected function SalutaClick(): void
    {
        EntityEvents::Notify('Saluti');

        $this->Alert->Success('Saluto mandato: chi e\' iscritto lo riceve sul suo server.');
    }

    /** Il primo selettore passa da solo giorno a giorno e ora, e viceversa, tenendo il valore. */
    protected function CambiaModoClick(): void
    {
        $this->dtGiorno->Mode = $this->dtGiorno->Mode === DateTimeMode::Date
            ? DateTimeMode::DateTime
            : DateTimeMode::Date;
    }

    protected function OnPreRender(): void
    {
        $scrivi = static fn(DatePicker $dt): string => $dt->Value === null
            ? '(vuoto)'
            : $dt->Value->format($dt->Mode === DateTimeMode::Date ? 'l j F Y' : 'l j F Y, H:i');

        $this->litSaluti->Text = (string)$this->SalutiRicevuti;

        $this->litDate->Text = 'dtGiorno [' . $this->dtGiorno->Mode->name . ']: ' . $scrivi($this->dtGiorno)
            . ' — dtQuando [' . $this->dtQuando->Mode->name . ']: ' . $scrivi($this->dtQuando);

        //la casella si decide da se' se questa pagina si tiene o no
        $this->KeepState = $this->chkTieni->Checked;

        $this->litContatore->Text = (string)$this->Conteggio;

        $this->litPortato->Text = $this->NomePortato === '' ? '(niente)' : $this->NomePortato;

        $this->txtNome->Text = $this->NomePortato;
    }
}
