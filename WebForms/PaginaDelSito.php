<?php
declare(strict_types=1);

namespace Common\WebForms;

/**
 * Quello che il motore chiede a un elenco di pagine per poterci mandare qualcuno.
 *
 * Lo implementa l'enum "Pagine" del sito, che e' generato: il motore non puo' conoscere i
 * nomi delle pagine di un sito, e il sito non deve conoscere il motore piu' di cosi'.
 *
 * @see PageMap per chi lo genera
 */
interface PaginaDelSito
{
    /** Il percorso con cui la pagina si chiede al server: "/public/php/Northwind/Ordini.php". */
    public function Percorso(): string;
}
