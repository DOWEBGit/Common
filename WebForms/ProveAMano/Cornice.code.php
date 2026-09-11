<?php
declare(strict_types=1);

namespace Common\WebForms\ProveAMano;

use Common\WebForms\Control;
use Common\WebForms\MasterPage;

/**
 * La cornice dei banchi di prova: due pagine collegate, un menu, e niente del sito.
 *
 * Serve a due cose insieme. La prima e' pratica: le pagine di prova diventano solo il loro
 * contenuto, e il menu si scrive una volta. La seconda e' che una master E' una prova - il
 * caso in cui i controlli non stanno nel markup della pagina ma in quello della cornice, e
 * il loro stato deve attraversare i postback lo stesso. Quando quel meccanismo si e' rotto,
 * il sintomo era il titolo che spariva al primo click: proprio questo litTitolo.
 *
 * Il menu e' fatto di <a> normali: il runtime intercetta i link interni e li trasforma in
 * navigazione senza ricarico da se', quindi non c'e' niente da dichiarare.
 */
class Cornice extends MasterPage
{
    /** Le voci del menu: indirizzo => etichetta. */
    private const array VOCI = [
        'Prima.php'   => 'Prima pagina',
        'Seconda.php' => 'Seconda pagina',
        'Tabella.php' => 'Righe a mano',
        'Stato.php'   => 'Stato dei controlli',
    ];

    use CorniceDesigner;

    public function OnPreRender(): void
    {
        $this->litMenu->Text = $this->Menu();

        //l'ora del SERVER, non del browser: se cambia navigando vuol dire che la pagina e'
        //stata chiesta davvero, e non ricomposta da qualcosa che il client aveva in mano
        $this->litOra->Text = date('H:i:s');
    }

    /**
     * Il menu, con la voce corrente marcata.
     *
     * Esce come HTML gia' pronto in un Literal in PassThrough: sono quattro <a> senza niente
     * di dinamico dentro, e quattro LinkButton per fare la stessa cosa sarebbero quattro
     * postback al posto di quattro navigazioni.
     */
    private function Menu(): string
    {
        $qui = basename((string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH));

        $html = '';

        foreach (self::VOCI as $file => $etichetta)
        {
            $classe = $file === $qui ? ' class="pm-qui"' : '';

            $html .= '<a href="' . Control::HtmlEncode($file) . '"' . $classe . '>'
                . Control::HtmlEncode($etichetta) . '</a>';
        }

        return $html;
    }

    /** Titolo e sottotitolo: l'unica cosa che la cornice non sa da se'. */
    public function SetTitle(string $titolo, string $sottotitolo = ''): void
    {
        $this->litTitolo->Text = $titolo;

        $this->litSottotitolo->Text = $sottotitolo;
        $this->litSottotitolo->Visible = $sottotitolo !== '';

        if ($this->Page->Title === '')
            $this->Page->Title = $titolo . ' — Prove a mano';
    }
}
