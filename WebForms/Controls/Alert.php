<?php
declare(strict_types=1);

namespace Common\WebForms\Controls;

use Common\WebForms\Alert as Coda;
use Common\WebForms\Control;

/**
 * Dove finiscono gli avvisi: <dw:Alert /> nella master page, una volta sola.
 *
 *     <dw:Alert id="avvisi" Durata="5000" />
 *
 * e nelle pagine, da qualunque handler:
 *
 *     $this->Alert->Success('Categoria salvata.');
 *     $this->Alert->Fail('Non salvata: ' . $esito->Avviso(' '), true);
 *
 * DUE FORME, e la differenza non e' l'importanza. Il riquadro in alto a destra se ne va da
 * solo e non ferma niente: e' per dire com'e' andata. Il modale oscura la pagina e vuole un
 * OK: e' per quando il messaggio va letto PER FORZA, perche' quello che l'utente stava
 * facendo non e' successo. Usarlo per un "salvato" e' come mettere un semaforo in mezzo a un
 * corridoio.
 *
 * QUI NON C'E' LOGICA: il controllo disegna la coda che trova e la svuota. Il resto - il
 * timer, il fermarsi sotto il mouse, la x, la dissolvenza - e' nel runtime, perche' e' roba
 * che succede DOPO che il server ha finito di parlare.
 *
 * Si stila da fuori: le classi sono `dw-avviso`, `dw-avviso-successo`, `dw-avviso-fallito`,
 * `dw-modale`. Il motore ne porta il minimo perche' si vedano bene appena montati, e il
 * foglio di stile del sito, che esce dopo, le sovrascrive senza toccare Common.
 */
class Alert extends Control
{
    /** Millisecondi prima che un riquadro se ne vada da solo. 0 = resta finche' non lo si chiude. */
    public int $Durata = 5000;

    protected function ViewStateProperties(): array
    {
        return array_merge(parent::ViewStateProperties(), ['Durata']);
    }

    public function Render(): string
    {
        if (!$this->Visible || $this->Page === null)
            return '';

        $coda = $this->Page->Alert;

        $volanti = '';
        $modali  = '';

        foreach ($coda->Messaggi() as $messaggio)
        {
            if ($messaggio['modale'])
                $modali .= $this->Riga($messaggio);
            else
                $volanti .= $this->Riquadro($messaggio);
        }

        //appena disegnati, la coda si svuota: il pacchetto portatile parte DOPO il render, e
        //cosi' non se li porta dietro alla pagina successiva facendoli vedere due volte
        $coda->Svuota();

        //il contenitore c'e' sempre, anche vuoto: e' il nodo che il morph aggiorna, e senza
        //di lui un avviso che arriva da un postback non avrebbe un posto dove comparire
        $html = '<div id="' . self::HtmlEncode($this->Id) . '" class="dw-avvisi'
            . ($this->CssClass !== '' ? ' ' . self::HtmlEncode($this->CssClass) : '') . '"'
            . ' data-dw-durata="' . max(0, $this->Durata) . '" aria-live="polite">'
            . $volanti
            . '</div>';

        if ($modali === '')
            return $html;

        return $html
            . '<div class="dw-modale" role="alertdialog" aria-modal="true">'
            . '<div class="dw-modale-scatola">'
            . $modali
            . '<div class="dw-modale-piede"><button type="button" class="dw-modale-ok">OK</button></div>'
            . '</div></div>';
    }

    /** Un riquadro volante. */
    private function Riquadro(array $messaggio): string
    {
        return '<div class="dw-avviso ' . self::Classe($messaggio['tipo']) . '"'
            . ' id="dw-avviso-' . self::HtmlEncode($messaggio['chiave']) . '" role="status">'
            . '<span class="dw-avviso-icona" aria-hidden="true">' . self::Icona($messaggio['tipo']) . '</span>'
            . '<div class="dw-avviso-testo">' . self::HtmlEncode($messaggio['testo']) . '</div>'
            . '<button type="button" class="dw-avviso-chiudi" aria-label="Chiudi">&times;</button>'
            . '</div>';
    }

    /** Una riga dentro la finestra al centro. */
    private function Riga(array $messaggio): string
    {
        return '<div class="dw-modale-riga ' . self::Classe($messaggio['tipo']) . '">'
            . '<span class="dw-avviso-icona" aria-hidden="true">' . self::Icona($messaggio['tipo']) . '</span>'
            . '<div class="dw-avviso-testo">' . self::HtmlEncode($messaggio['testo']) . '</div>'
            . '</div>';
    }

    private static function Classe(string $tipo): string
    {
        return $tipo === Coda::SUCCESSO ? 'dw-avviso-successo' : 'dw-avviso-fallito';
    }

    /**
     * L'icona e' un carattere e non un'immagine: non ha un file da servire, non ha un
     * indirizzo da sbagliare, e si colora con il colore del testo.
     */
    private static function Icona(string $tipo): string
    {
        return $tipo === Coda::SUCCESSO ? '&#10003;' : '&#9888;';
    }
}
