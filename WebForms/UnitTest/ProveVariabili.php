<?php
declare(strict_types=1);

namespace Common\WebForms\UnitTest;

use Common\WebForms\Controls\Label;
use Common\WebForms\Page;
use Common\WebForms\Portable;
use Common\WebForms\Transient;

/**
 * Le variabili della pagina attraversano il postback TUTTE, come i campi di una form.
 *
 * Non si marca piu' quello che deve restare: si marca #[Transient] il poco che deve
 * rinascere. Questa prova tiene fermi i confini della regola - cosa resta fuori da solo, cosa
 * si esclude a mano, e cosa non puo' passare e lo dice subito.
 */
class ProveVariabili
{
    public static function Esegui(Prova $p): void
    {
        $p->Sezione('le variabili della pagina');

        $pagina = self::Pagina();

        $pagina->Pubblica   = 42;
        $pagina->Volatile   = 99;
        $pagina->Portatile  = 'viaggia a parte';
        $pagina->Controllo  = new Label();
        $pagina->Senzatipo  = ['a' => 1];

        $pagina->ScriviPrivate('prot', ['x', 'y']);

        $stato = self::Salva($pagina);

        $campi = $stato['p'];

        $p->Uguale('una proprieta\' pubblica senza attributi resta', 42, $campi['Pubblica'] ?? null);
        $p->Uguale('una protetta anche', 'prot', $campi['Protetta'] ?? null);
        $p->Uguale('e una privata anche', ['x', 'y'], $campi['Privata'] ?? null);
        $p->Uguale('senza tipo, con dentro un array: resta', ['a' => 1], $campi['Senzatipo'] ?? null);

        $p->Uguale('#[Transient] la tiene fuori', false, array_key_exists('Volatile', $campi));
        $p->Uguale('#[Portable] ha il suo canale e non finisce qui', false, array_key_exists('Portatile', $campi));
        $p->Uguale('una proprieta\' tipizzata con una classe resta fuori da sola', false, array_key_exists('Controllo', $campi));

        $p->Uguale('le proprieta\' del motore restano fuori (Title viaggia per conto suo)',
            false, array_key_exists('Title', $campi));

        $p->Uguale('una tipizzata mai assegnata si salta invece di esplodere',
            false, array_key_exists('MaiAssegnata', $campi));

        // --- e tornano indietro

        $seconda = self::Pagina();

        self::Carica($seconda, $stato);

        $p->Uguale('al giro dopo la pubblica e\' quella di prima', 42, $seconda->Pubblica);
        $p->Uguale('la privata anche', ['x', 'y'], $seconda->LeggiPrivata());
        $p->Uguale('la transiente e\' rinata al suo valore iniziale', 0, $seconda->Volatile);

        // --- un oggetto non passa, e lo dice subito

        $p->Solleva('un oggetto in una proprieta\' senza tipo si ferma al salvataggio, con il nome della proprieta\'',
            '"Senzatipo"',
            static function (): void
            {
                $rotta = self::Pagina();

                $rotta->Senzatipo = new \stdClass();

                self::Salva($rotta);
            });
    }

    private static function Pagina(): Page
    {
        return new class extends Page
        {
            public int $Pubblica = 0;

            protected string $Protetta = '';

            private array $Privata = [];

            public int $MaiAssegnata;

            #[Transient]
            public int $Volatile = 0;

            #[Portable]
            public string $Portatile = '';

            public ?Label $Controllo = null;

            public $Senzatipo = null;

            public function ScriviPrivate(string $protetta, array $privata): void
            {
                $this->Protetta = $protetta;
                $this->Privata  = $privata;
            }

            public function LeggiPrivata(): array
            {
                return $this->Privata;
            }
        };
    }

    /** SaveViewState e' privato della pagina: lo si chiama come farebbe il motore. */
    private static function Salva(Page $pagina): array
    {
        return (new \ReflectionMethod($pagina, 'SaveViewState'))->invoke($pagina);
    }

    private static function Carica(Page $pagina, array $stato): void
    {
        (new \ReflectionMethod($pagina, 'LoadViewState'))->invoke($pagina, $stato);
    }
}
