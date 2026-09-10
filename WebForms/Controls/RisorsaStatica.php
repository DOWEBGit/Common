<?php
declare(strict_types=1);

namespace Common\WebForms\Controls;

use Common\WebForms\Control;

/**
 * Quello che serve a <dw:Stylesheet> e <dw:Script>: dal nome di un file al suo indirizzo,
 * con in coda la marca temporale.
 *
 * PERCHE' LA MARCA TEMPORALE. Sotto Public/Php i file statici escono con una cache di
 * SESSANTA giorni: senza qualcosa che cambi nell'indirizzo, una modifica al CSS o al
 * JavaScript la vede solo chi non e' mai passato di qui. Scrivendo il tag a mano ci si
 * ricorda di alzare il "?v=" le prime due volte e poi mai piu', e si finisce a premere
 * Ctrl+F5 senza capire perche' il sito non cambia. Qui l'indirizzo cambia da solo quando
 * cambia il file.
 *
 * IL PERCORSO E' RELATIVO ALLA RADICE DEI SORGENTI PHP, la stessa da cui parte l'autoloader:
 * "Layouts/Sito.css". La barra iniziale si accetta e si ignora - "/Layouts/Sito.css" e'
 * quello che viene naturale scrivere, e non c'e' nessun motivo per farne un errore - ma NON
 * e' un percorso assoluto dalla radice del sito: quello che conta e' dove stanno i sorgenti,
 * cosi' la stessa riga funziona su un sito servito da una cartella diversa.
 *
 * Un file che non c'e' ferma la pagina invece di lasciarla senza vestito o senza script: e'
 * un refuso nel markup, e un sito che si comporta male e' piu' difficile da capire di un
 * errore che dice quale file manca.
 */
abstract class RisorsaStatica extends Control
{
    /** Percorso del file dalla radice dei sorgenti php: "Layouts/Sito.css". */
    public string $Src = '';

    protected function ViewStateProperties(): array
    {
        return array_merge(parent::ViewStateProperties(), ['Src']);
    }

    /** L'indirizzo da mettere nel tag, con la marca temporale. Solleva se il file non c'e'. */
    protected function IndirizzoConVersione(): string
    {
        $tag = '<dw:' . (new \ReflectionClass($this))->getShortName() . '>';

        //il percorso viene dal markup, non dalla richiesta, ma un ".." ci arriverebbe lo
        //stesso da una svista e servirebbe un file che sta fuori dal sito
        if ($this->Src === '' || str_contains($this->Src, '..'))
            throw new \RuntimeException($tag . ': src non valido: "' . $this->Src . '".');

        $relativo = ltrim(str_replace('\\', '/', $this->Src), '/');

        $file = self::Radice() . '/' . $relativo;

        if (!is_file($file))
            throw new \RuntimeException($tag . ': non trovo "' . $relativo . '".');

        return self::Prefisso() . '/' . $relativo . '?v=' . filemtime($file);
    }

    /** La radice dei sorgenti: questo file sta in Common/WebForms/Controls, tre sopra c'e' lei. */
    private static function Radice(): string
    {
        return str_replace('\\', '/', dirname(__DIR__, 3));
    }

    /**
     * La stessa radice, vista dal browser: "/public/php".
     *
     * Si ricava togliendo la radice dei documenti dal percorso su disco, invece di scriverla
     * fissa: un sito servito da una cartella diversa continua a funzionare senza che nessuno
     * debba accorgersene.
     */
    private static function Prefisso(): string
    {
        $documenti = rtrim(str_replace('\\', '/', (string)($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');

        $radice = self::Radice();

        if ($documenti === '' || !str_starts_with(strtolower($radice), strtolower($documenti)))
            throw new \RuntimeException('I sorgenti non stanno sotto la radice dei documenti.');

        return substr($radice, strlen($documenti));
    }
}
