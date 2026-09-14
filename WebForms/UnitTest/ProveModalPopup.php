<?php
declare(strict_types=1);

namespace Common\WebForms\UnitTest;

use Common\WebForms\ControlBuilder;
use Common\WebForms\Controls\ModalPopup;
use Common\WebForms\PageParser;

/**
 * <dw:ModalPopup>: quello che il server scrive perche' il runtime sappia chi lo apre, chi lo
 * chiude e dove metterlo. Il resto - l'apertura, il trascinamento, il click che non fa
 * postback - e' JavaScript e si guarda in Examples/ModalPopup.php.
 */
class ProveModalPopup
{
    public static function Esegui(Prova $p): void
    {
        $p->Sezione('ModalPopup: i marcatori per il runtime');

        $nodi = PageParser::ParseTesto(
            '<dw:ModalPopup id="__ModalPopup_Scheda" CssClass="scheda" TargetControlID="__Button_Apri"'
            . ' OkControlID="__Button_Ok" CancelControlID="__Button_Annulla" DragHandleControlID="__Panel_Testata"'
            . ' DropShadow="true" BackgroundCssClass="fondo-scuro" OnOkScript="salva(\'x\')">'
            . '<dw:Panel id="__Panel_Testata">Titolo</dw:Panel><dw:TextBox id="__TextBox_Nome" />'
            . '</dw:ModalPopup>');

        /** @var ModalPopup $popup */
        $popup = ControlBuilder::Build($nodi, null)[0];
        $html  = $popup->Render();

        $p->Contiene('la classe del motore viene prima di quella della pagina', 'class="dw-popup scheda"', $html);
        $p->Contiene('e' . '\' un dialogo per chi legge con lo schermo', 'role="dialog" aria-modal="true"', $html);
        $p->Contiene('chi lo apre', 'data-dw-modal-target="__Button_Apri"', $html);
        $p->Contiene('chi lo chiude bene', 'data-dw-modal-ok="__Button_Ok"', $html);
        $p->Contiene('chi lo annulla', 'data-dw-modal-cancel="__Button_Annulla"', $html);
        $p->Contiene('da dove si trascina', 'data-dw-modal-handle="__Panel_Testata"', $html);
        $p->Contiene('lo script dell\'OK esce escapato, dentro un attributo', 'data-dw-modal-ok-script="salva(&#039;x&#039;)"', $html);
        $p->Manca('senza OnCancelScript non c\'e\' l\'attributo', 'data-dw-modal-cancel-script', $html);
        $p->Contiene('lo sfondo porta la classe della pagina', 'class="dw-popup-fondo fondo-scuro"', $html);
        $p->Contiene('l\'ombra e\' una classe sulla scatola', 'class="dw-popup-scatola dw-popup-ombra"', $html);
        $p->Contiene('i figli stanno nella scatola', '<div id="__Panel_Testata">Titolo</div><input', $html);
        $p->Manca('senza Show() ne\' Hide() il server non da\' ordini: decide il browser', 'data-dw-modal-open', $html);
        $p->Manca('centrato: nessuno stile in linea', 'style=', $html);

        // --- gli ordini del server, per questa risposta e basta

        $popup->Show();

        $p->Contiene('Show() e\' l\'ordine di aprire', 'data-dw-modal-open="1"', $popup->Render());

        $popup->Hide();

        $p->Contiene('Hide() quello di chiudere', 'data-dw-modal-open="0"', $popup->Render());

        $p->Uguale('e non stanno nello stato: al postback dopo il browser decide da se\'',
            false, array_key_exists('ordine', $popup->SaveViewState()));

        // --- la posizione

        $popup->X = 40;

        $p->Contiene('solo X: fisso in orizzontale, centrato in verticale',
            'style="left:40px;transform:translate(0,-50%)"', $popup->Render());

        $popup->X = -1;
        $popup->Y = 0;

        $p->Contiene('solo Y, anche zero: fisso in verticale, centrato in orizzontale',
            'style="top:0px;transform:translate(-50%,0)"', $popup->Render());

        $popup->X = 10;

        $p->Contiene('X e Y: niente centraggio', 'style="left:10px;top:0px;transform:translate(0,0)"', $popup->Render());

        // --- nascosto e' nascosto, e i riferimenti seguono il contenitore di denominazione

        $popup->Visible = false;

        $p->Contiene('Visible=false lo rende con hidden, come tutti: il nodo resta per il morph', ' hidden', $popup->Render());

        $popup->Visible = true;
        $popup->NamingKey = 'pg';

        $p->Contiene('dentro un UserControl i riferimenti prendono il suffisso, come gli id',
            'data-dw-modal-target="__Button_Apri__pg"', $popup->Render());

        $popup->TargetControlID = '__Button_Apri__pg';

        $p->Contiene('e un riferimento gia\' qualificato non lo prende due volte',
            'data-dw-modal-target="__Button_Apri__pg"', $popup->Render());

        // --- il runtime lo trova

        $js = (string)file_get_contents(__DIR__ . '/../runtime.js');

        $p->Contiene('il runtime cerca i popup per il loro marcatore', "querySelectorAll('[data-dw-modal]')", $js);
        $p->Contiene('e ferma il click di target, OK e Annulla prima del postback: fase di cattura', "}, true);", $js);

        $css = (string)file_get_contents(__DIR__ . '/../runtime.css');

        $p->Contiene('chiuso finche\' il runtime non lo apre', '.dw-popup{display:none', $css);
        $p->Contiene('sotto gli avvisi, che si devono leggere anche sopra un popup', 'z-index:9990', $css);
    }
}
