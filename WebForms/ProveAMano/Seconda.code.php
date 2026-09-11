<?php
declare(strict_types=1);

namespace Common\WebForms\ProveAMano;

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
    }
}
