<?php
declare(strict_types=1);

namespace Common\WebForms\Controls;

use Common\WebForms\Control;

/**
 * "Attendere..." mentre il postback e' in viaggio: l'UpdateProgress di WebForms.
 *
 * Si mette UNA VOLTA nella master page e vale per tutte le pagine, come in WK:
 *
 *     <dw:UpdateProgress id="prgAttesa" DisplayAfter="200">
 *         <div class="nw-attesa">Attendere ... <img src="/layout/images/spinner.gif" alt=""></div>
 *     </dw:UpdateProgress>
 *
 * Il contenuto va DENTRO il tag, senza il <ProgressTemplate> di WebForms: qui l'unico
 * template che il compilatore riconosce e' l'ItemTemplate del Repeater, e un tag inventato
 * finirebbe nell'HTML come elemento sconosciuto. Senza contenuto ne rende uno suo, che e'
 * gia' quello che serve nove volte su dieci.
 *
 * COME FA A SAPERE CHE C'E' UN POSTBACK IN CORSO. Non lo sa lui: il runtime mette
 * "js-dw-attesa" sul <body> per tutta la durata della richiesta, e questo controllo e' un
 * pezzo di CSS che reagisce a quella classe. Niente JavaScript da scrivere, niente handler,
 * e soprattutto niente da spegnere: quando la richiesta finisce la classe sparisce, comunque
 * sia andata - anche se la fetch e' fallita.
 *
 * DISPLAYAFTER, e perche' non e' un dettaglio. Un postback che dura 40 ms con l'overlay
 * mostrato subito e' un lampo bianco ad ogni click: si vede peggio di non averlo. L'attesa
 * la fa il ritardo di un'animazione CSS, non un setTimeout, quindi non c'e' niente da
 * annullare quando la risposta arriva prima.
 */
class UpdateProgress extends Control
{
    /** Millisecondi prima di mostrarsi: sotto questa soglia il postback passa inosservato. */
    public int $DisplayAfter = 200;

    /** Il testo del contenuto predefinito. Ignorato se dentro il tag c'e' del markup. */
    public string $Text = 'Attendere...';

    protected function ViewStateProperties(): array
    {
        return array_merge(parent::ViewStateProperties(), ['DisplayAfter', 'Text']);
    }

    public function Render(): string
    {
        if (!$this->Visible)
            return '';

        $classe = 'dw-attesa' . ($this->CssClass !== '' ? ' ' . $this->CssClass : '');

        //la classe la si compone qui invece di passare da RenderAttributes: quella di questo
        //controllo non e' una decorazione, e' cio' che lo fa comparire
        $html = '<div id="' . self::HtmlEncode($this->Id) . '" class="' . self::HtmlEncode($classe) . '"'
            . ' aria-live="polite" aria-busy="true"'
            . ' style="--dw-attesa-dopo:' . max(0, $this->DisplayAfter) . 'ms">';

        $dentro = $this->RenderChildren();

        return $html
            . ($dentro !== '' ? $dentro : '<div class="dw-attesa-scatola">' . self::HtmlEncode($this->Text) . '</div>')
            . '</div>';
    }
}
