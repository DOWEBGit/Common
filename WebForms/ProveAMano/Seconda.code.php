<?php
declare(strict_types=1);

namespace Common\WebForms\ProveAMano;

use Common\WebForms\Control;
use Common\WebForms\Page;
use Common\WebForms\Portable;

/**
 * La seconda delle due pagine collegate: riceve, cambia, e rimanda indietro.
 *
 * Non c'e' nessuna riga che "legga il parametro": la proprieta' si chiama NomePortato come
 * quella di la', ed e' #[Portable], e tanto basta. Il pacchetto arriva firmato e il motore lo
 * posa nelle proprieta' prima di OnLoad.
 *
 * Vale nei due sensi: quello che questa pagina scrive in NomePortato la prima pagina se lo
 * ritrova, senza che nessuna delle due sappia dell'altra.
 */
class Seconda extends Page
{
    use SecondaDesigner;

    public int $Conteggio = 0;

    #[Portable]
    public string $NomePortato = '';

    public int $SalutiRicevuti = 0;

    /** L'evento arriva sul server di questa pagina: si aggiorna lo stato e si avvisa. */
    protected function OnInit(): void
    {
        $this->Subscribe('Saluti', function (): void
        {
            $this->SalutiRicevuti++;

            $this->Alert->Success('Saluto numero ' . $this->SalutiRicevuti . ' dalla prima pagina.');
        });

        //l'evento con dati: $dati e' l'oggetto mandato di la', appiattito in array. Il
        //pacchetto e' firmato, quindi e' quello che il server ha scritto; ma il contenuto lo
        //si tratta come qualunque cosa arrivi dal browser - si escapa, si controlla
        $this->Subscribe('Utente', function (array $dati): void
        {
            $nome  = (string)($dati['Nome'] ?? '');
            $email = (string)($dati['Email'] ?? '');
            $foto  = (string)($dati['Immagine'] ?? '');

            $this->litNomeUtente->Text = trim($nome . ' ' . ($dati['Cognome'] ?? ''));
            $this->litEmail->Text      = $email;

            //solo un data URI di immagine passa nel src: e' l'unica cosa che ci si aspetta
            $this->litFoto->Text = str_starts_with($foto, 'data:image/')
                ? '<img src="' . Control::HtmlEncode($foto) . '" width="48" height="48" alt="" style="vertical-align:middle;margin-right:8px">'
                : '';

            $this->pnlUtente->Visible = true;

            $this->Alert->Success('E\' arrivato ' . $this->litNomeUtente->Text . '.');
        });
    }

    protected function OnLoad(): void
    {
        if ($this->IsPostBack)
            return;

        $this->Master->SetTitle(
            'Seconda pagina',
            'Legge quello che viaggia, e lo rimanda indietro cambiato.'
        );
    }

    protected function ContaClick(): void
    {
        $this->Conteggio++;
    }

    protected function MaiuscoloClick(): void
    {
        if ($this->NomePortato === '')
        {
            $this->Alert->Fail('Non e\' arrivato niente da mettere in maiuscolo.');

            return;
        }

        $this->NomePortato = mb_strtoupper($this->NomePortato, 'UTF-8');

        $this->Alert->Success('Adesso e\' "' . $this->NomePortato . '", anche di la\'.');
    }

    protected function SvuotaClick(): void
    {
        $this->NomePortato = '';

        $this->Alert->Success('Svuotato: la prima pagina lo trovera\' vuoto.');
    }

    protected function OnPreRender(): void
    {
        $this->litArrivato->Text = $this->NomePortato === ''
            ? 'Non e\' arrivato niente: torna alla prima pagina e scrivi un nome.'
            : 'E\' arrivato: ' . $this->NomePortato;

        $this->litContatore->Text = (string)$this->Conteggio;

        $this->litSaluti->Text = $this->SalutiRicevuti . ($this->SalutiRicevuti === 1 ? ' saluto ricevuto' : ' saluti ricevuti');
    }
}
