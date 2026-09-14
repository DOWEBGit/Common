<?php
declare(strict_types=1);

namespace Common\WebForms\Examples;

use Common\WebForms\Page;

class AlertExample extends Page
{
    use AlertDesigner;

    protected function SuccessClick(): void
    {
        $this->Alert->Success('Salvato alle ' . date('H:i:s') . '.');
    }

    protected function FailClick(): void
    {
        $this->Alert->Fail('Non salvato: il nome e\' obbligatorio.');
    }

    /** Il secondo argomento a true: modale, si deve cliccare OK. */
    protected function ModalClick(): void
    {
        $this->Alert->Fail('Il file supera gli 8 MB e non e\' stato caricato. Questo va letto per forza.', true);
    }

    protected function ManyClick(): void
    {
        for ($i = 1; $i <= 7; $i++)
            $this->Alert->Success('Avviso numero ' . $i . ' di sette.');
    }

    /** L'avviso viaggia con la navigazione: si legge sulla pagina di arrivo. */
    protected function TravelClick(): void
    {
        $this->Alert->Success('Salvato sulla pagina Alert, letto qui: la coda ha attraversato la navigazione.');

        $this->Redirect('State.php');
    }
}
