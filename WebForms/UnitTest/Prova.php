<?php
declare(strict_types=1);

namespace Common\WebForms\UnitTest;

/**
 * Il minimo che serve per scrivere una prova: un confronto e un conto.
 *
 * Non c'e' PHPUnit: il sito non ha composer e queste prove devono poter girare anche dentro
 * Kestrel, dove non si installa niente. Quindi quello che serve per scriverle sta qui, ed e'
 * poco: un confronto, un "contiene", un "deve sollevare".
 *
 * Il nome di una prova dice cosa DEVE succedere, non cosa fa il codice: quando diventa
 * rossa, la riga che si legge deve gia' spiegare cosa si e' rotto.
 */
class Prova
{
    private int $fatte = 0;

    /** @var string[] */
    private array $rosse = [];

    private string $uscita = '';

    public function Sezione(string $titolo): void
    {
        $this->uscita .= "\n--- " . $titolo . " ---\n";
    }

    /** Confronto stretto: tipo compreso, cosi' "1" non passa per 1. */
    public function Uguale(string $nome, mixed $atteso, mixed $ottenuto): void
    {
        $this->fatte++;

        if ($atteso === $ottenuto)
        {
            $this->uscita .= "ok      " . $nome . "\n";
            return;
        }

        $this->rosse[] = $nome;

        $this->uscita .= "ROSSA   " . $nome . "\n"
            . "        atteso:   " . var_export($atteso, true) . "\n"
            . "        ottenuto: " . var_export($ottenuto, true) . "\n";
    }

    /** Per l'HTML, dove l'ordine degli attributi non e' una promessa. */
    public function Contiene(string $nome, string $pezzo, string $html): void
    {
        $this->fatte++;

        if (str_contains($html, $pezzo))
        {
            $this->uscita .= "ok      " . $nome . "\n";
            return;
        }

        $this->rosse[] = $nome;

        $this->uscita .= "ROSSA   " . $nome . "\n"
            . "        cercavo:   " . $pezzo . "\n"
            . "        nell'html: " . $html . "\n";
    }

    public function Manca(string $nome, string $pezzo, string $html): void
    {
        $this->Uguale($nome, false, str_contains($html, $pezzo));
    }

    /** La chiamata deve sollevare, e il messaggio deve dire perche'. */
    public function Solleva(string $nome, string $pezzoDelMessaggio, callable $azione): void
    {
        $this->fatte++;

        try
        {
            $azione();
        }
        catch (\Throwable $errore)
        {
            if (str_contains($errore->getMessage(), $pezzoDelMessaggio))
            {
                $this->uscita .= "ok      " . $nome . "\n";
                return;
            }

            $this->rosse[] = $nome;

            $this->uscita .= "ROSSA   " . $nome . "\n"
                . "        cercavo nel messaggio: " . $pezzoDelMessaggio . "\n"
                . "        messaggio:             " . $errore->getMessage() . "\n";

            return;
        }

        $this->rosse[] = $nome;

        $this->uscita .= "ROSSA   " . $nome . " (non ha sollevato niente)\n";
    }

    public function Uscita(): string
    {
        return $this->uscita;
    }

    public function Fatte(): int
    {
        return $this->fatte;
    }

    /** @return string[] */
    public function Rosse(): array
    {
        return $this->rosse;
    }
}
