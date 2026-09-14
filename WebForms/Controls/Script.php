<?php
declare(strict_types=1);

namespace Common\WebForms\Controls;

/**
 * Un file JavaScript del sito, con in coda la marca temporale.
 *
 *     <dw:Script src="Layouts/Sito.js" />
 *
 * rende
 *
 *     <script src="/public/php/Layouts/Sito.js?v=1789041657" defer></script>
 *
 * Il perche' della marca temporale, e come si scrive il percorso, stanno in StaticResource.
 *
 * DEFER E' ACCESO DI SUA INIZIATIVA. Uno script che blocca l'analisi del documento ritarda
 * tutto quello che viene dopo, e in una master page "dopo" e' la pagina intera. Con defer il
 * file si scarica in parallelo e viene eseguito quando il documento e' pronto, nell'ordine in
 * cui e' scritto: e' quello che serve nove volte su dieci. Si spegne con Defer="false" quando
 * lo script deve girare prima che il browser disegni - un anti-flicker del tema scuro, per
 * dire - o quando qualcosa piu' in basso nella pagina si aspetta di trovarlo gia' eseguito.
 *
 * ATTENZIONE AL CICLO DI VITA. Questo script viene eseguito UNA VOLTA, al caricamento vero
 * della pagina: qui le pagine non si ricaricano - un click e' una fetch e poi un morph - e il
 * runtime riesegue solo gli script INLINE dopo una navigazione, non quelli con src, che sono
 * gia' in memoria. Quindi ci va codice che si aggancia al documento (delega, DW.onLeave,
 * l'evento dw:pagina), non codice che cerca i suoi elementi all'avvio e se li tiene: quelli
 * al primo morph diventano nodi che non stanno piu' in pagina.
 */
class Script extends StaticResource
{
    /** Non blocca l'analisi del documento; esecuzione a documento pronto, in ordine. */
    public bool $Defer = true;

    /** Esecuzione appena arriva, senza ordine garantito. Solo per roba che non dipende da altro. */
    public bool $Async = false;

    /** Modulo ES: import/export, e defer implicito. */
    public bool $Module = false;

    protected function ViewStateProperties(): array
    {
        return array_merge(parent::ViewStateProperties(), ['Defer', 'Async', 'Module']);
    }

    public function Render(): string
    {
        if (!$this->Visible)
            return '';

        $html = '<script src="' . self::HtmlEncode($this->VersionedUrl()) . '"';

        if ($this->Module)
            $html .= ' type="module"';

        //su un modulo il defer c'e' gia' per definizione, e scriverlo lo stesso non e' un
        //errore ma dice una cosa che non si applica
        if ($this->Defer && !$this->Async && !$this->Module)
            $html .= ' defer';

        if ($this->Async)
            $html .= ' async';

        //il tag di chiusura ci vuole: <script src="..." /> il browser non lo chiude, e si
        //mangia tutto quello che viene dopo fino al primo </script>
        return $html . '></script>';
    }
}
