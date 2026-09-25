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

        self::PortableDiPassaggio($p);
    }

    /**
     * Il pacchetto #[Portable] attraversa anche le pagine che non conoscono una sua chiave.
     *
     * Il difetto vero: una pagina con $CarriedName, poi una pagina qualunque, poi di nuovo la
     * prima - e il nome non c'era piu'. La pagina in mezzo reimpacchettava solo le sue
     * proprieta' e buttava le altre.
     */
    private static function PortableDiPassaggio(Prova $p): void
    {
        $p->Sezione('#[Portable]: le chiavi delle altre pagine passano');

        $scheda = new class extends Page {
            #[Portable]
            public string $Filtro = '';
        };

        $scheda->Filtro = 'rossi';

        $pacco = self::Viaggio(null, $scheda);

        $pacco = self::Viaggio($pacco, new class extends Page {
        });

        $ritorno = new class extends Page {
            #[Portable]
            public string $Filtro = '';
        };

        $pacco = self::Viaggio($pacco, $ritorno, function (Page $pagina): void { $pagina->Filtro = 'bianchi'; });

        $p->Uguale('passando da una pagina che non la dichiara, la chiave arriva intera', 'rossi', self::$letto);

        $ultima = new class extends Page {
            #[Portable]
            public string $Filtro = '';
        };

        self::Viaggio($pacco, $ultima);

        $p->Uguale('chi la dichiara la riscrive, e riparte cambiata', 'bianchi', $ultima->Filtro);
    }

    /** Il valore letto all'arrivo, prima che il codice della pagina lo cambiasse. */
    private static string $letto = '';

    /** Il pacchetto arriva (LoadPortable), la pagina lavora, lo rimanda (PackPortable): come il motore. */
    private static function Viaggio(?string $arrivato, Page $pagina, ?callable $lavoro = null): string
    {
        $_SERVER['HTTP_X_DW_PORTABLE'] = $arrivato ?? '';

        (new \ReflectionMethod($pagina, 'LoadPortable'))->invoke($pagina);

        unset($_SERVER['HTTP_X_DW_PORTABLE']);

        if (property_exists($pagina, 'Filtro'))
            self::$letto = $pagina->Filtro;

        if ($lavoro !== null)
            $lavoro($pagina);

        return (new \ReflectionMethod($pagina, 'PackPortable'))->invoke($pagina);
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
