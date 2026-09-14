<?php
declare(strict_types=1);

/**
 * Lancia le prove del motore, da riga di comando o da URL.
 *
 *     "C:\Program Files\PHP\php.exe" Esegui.php
 *     http://localhost:8081/public/php/Common/WebForms/UnitTest/Esegui.php
 *
 * Da riga di comando NON si passa da Start.php: quello vuole un sito attorno e il pipe verso
 * Kestrel, che qui non servono. Si registra il minimo per trovare le classi del motore, e le
 * prove girano su PHP e basta - che e' esattamente quello che devono provare.
 *
 * L'esito e' un numero: uscita 1 e risposta 500 se qualcosa e' rosso, cosi' si puo' chiamare
 * da uno script senza leggerlo a occhio.
 *
 * COSA SI PROVA QUI: quello che e' PHP puro e non ha bisogno di niente attorno - i controlli,
 * il compilatore del markup, il pacchetto dello stato. Una prova che ha bisogno di un sito
 * acceso non e' una prova unitaria, e' un collaudo.
 *
 * COSA NON SI PROVA QUI: il morph, la coda dei postback e la navigazione, che sono
 * JavaScript e vanno guardati nel browser; e tutto quello che passa dal pipe, che e' il
 * layer dati.
 */

if (PHP_SAPI === 'cli')
{
    //la radice dei sorgenti: .../public/php. Il nome della classe e' gia' il percorso, come
    //nell'autoloader del sito - qui se ne rifa' solo la parte che serve
    $radice = dirname(__DIR__, 3);

    spl_autoload_register(static function (string $classe) use ($radice): void
    {
        $file = $radice . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $classe) . '.php';

        if (is_file($file))
            require_once $file;
    });
}
else
{
    require __DIR__ . '/../Bootstrap.php';
}

use Common\WebForms\UnitTest\Prova;
use Common\WebForms\UnitTest\ProveControlli;
use Common\WebForms\UnitTest\ProveDatePicker;
use Common\WebForms\UnitTest\ProveDinamici;
use Common\WebForms\UnitTest\ProveEventiConDati;
use Common\WebForms\UnitTest\ProveMarkup;
use Common\WebForms\UnitTest\ProveMemoria;
use Common\WebForms\UnitTest\ProveOgniControllo;
use Common\WebForms\UnitTest\ProvePaginaVuota;
use Common\WebForms\UnitTest\ProveQuerystring;
use Common\WebForms\UnitTest\ProveRepeater;
use Common\WebForms\UnitTest\ProveSicurezza;
use Common\WebForms\UnitTest\ProveStato;
use Common\WebForms\UnitTest\ProveVariabili;
use Common\WebForms\UnitTest\ProveViewStateMode;

if (PHP_SAPI !== 'cli')
    header('Content-Type: text/plain; charset=utf-8');

$prova = new Prova();

ProveControlli::Esegui($prova);
ProveDatePicker::Esegui($prova);
ProveOgniControllo::Esegui($prova);
ProveDinamici::Esegui($prova);
ProveVariabili::Esegui($prova);
ProveViewStateMode::Esegui($prova);
ProvePaginaVuota::Esegui($prova);
ProveQuerystring::Esegui($prova);
ProveMemoria::Esegui($prova);
ProveEventiConDati::Esegui($prova);
ProveMarkup::Esegui($prova);
ProveRepeater::Esegui($prova);
ProveSicurezza::Esegui($prova);
ProveStato::Esegui($prova);

echo $prova->Uscita();

$rosse = $prova->Rosse();

echo "\n";

if ($rosse === [])
{
    echo 'tutte verdi: ' . $prova->Fatte() . ' prove' . "\n";
    return;
}

echo count($rosse) . ' rosse su ' . $prova->Fatte() . ":\n  " . implode("\n  ", $rosse) . "\n";

//da riga di comando l'esito e' il codice di uscita, non la risposta HTTP: e' quello che
//guarda uno script, ed e' anche l'unico che qui significhi qualcosa
if (PHP_SAPI === 'cli')
    exit(1);

http_response_code(500);
