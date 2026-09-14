<?php
declare(strict_types=1);

namespace Common\WebForms\UnitTest;

use Common\WebForms\Page;

/**
 * Un errore nella pagina deve finire nel log, non solo sullo schermo.
 *
 * Page::Run e' l'unico punto da cui passa ogni richiesta: quello che scappa da li' e' un
 * errore del codice della pagina - un handler che lancia, un tipo sbagliato nel designer -
 * e va scritto dove lo si legge, con il nome della pagina e la riga.
 *
 * Il log del sito e' PHPDOWEB()->LogError, che senza il pipe verso il server lancia: la
 * prova gli mette davanti un secchio suo e legge cosa ci finisce dentro.
 */
class ProveErrori
{
    public static function Esegui(Prova $p): void
    {
        $p->Sezione('errori: nel log, con il nome della pagina, e poi a PHP com\'erano');

        $cartella = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dw-prove-errori-' . getmypid();

        mkdir($cartella);

        $markup = $cartella . DIRECTORY_SEPARATOR . 'Rotta.php';

        file_put_contents($markup, '<?php ?><dw:Label id="lbl" />');
        file_put_contents($cartella . DIRECTORY_SEPARATOR . 'Rotta.code.php', <<<'PHP'
            <?php
            declare(strict_types=1);

            namespace Common\WebForms\UnitTest\Rotte;

            class Rotta extends \Common\WebForms\Page
            {
                use RottaDesigner;

                protected function OnLoad(): void
                {
                    throw new \RuntimeException('il bottone non c\'e\' piu\'');
                }
            }
            PHP);

        $scritto = '';

        $secchio = new \ReflectionProperty(Page::class, 'errorSink');
        $secchio->setValue(null, static function (string $riga) use (&$scritto): void { $scritto .= $riga . "\n"; });

        try
        {
            $p->Solleva('l\'eccezione arriva fuori da Run com\'era: la pagina risponde 500, non un frammento a meta\'',
                'il bottone non c\'e\' piu\'',
                static fn() => Page::Run($markup, 'Common\WebForms\UnitTest\Rotte\Rotta'));
        }
        finally
        {
            $secchio->setValue(null, null);

            //la chiusura di QUESTO processo non deve credere di aver gia' scritto
            (new \ReflectionProperty(Page::class, 'errorLogged'))->setValue(null, false);
        }

        $p->Contiene('nel log del sito c\'e\' il nome della pagina', 'WebForms Rotta.php', $scritto);
        $p->Contiene('e il tipo dell\'errore con il messaggio', 'RuntimeException: il bottone non c\'e\' piu\'', $scritto);
        $p->Contiene('e il file con la riga', 'Rotta.code.php:12', $scritto);
        $p->Contiene('e la pila delle chiamate, per sapere da dove si e\' arrivati', 'Page->ProcessRequest', $scritto);
        $p->Uguale('una volta sola: la chiusura e\' per gli errori che un catch non prende', 1, substr_count($scritto, 'WebForms Rotta.php'));

        foreach (glob($cartella . DIRECTORY_SEPARATOR . '*') ?: [] as $file)
            unlink($file);

        rmdir($cartella);
    }
}
