<?php
declare(strict_types=1);

namespace Common\WebForms\Controls;

use Common\WebForms\Control;

/**
 * Un pezzo di pagina che compare SOPRA il resto, e finche' e' aperto il resto non si tocca:
 * il ModalPopupExtender dell'AjaxControlToolkit, senza l'extender - qui il popup E' il
 * contenitore, come un Panel che sa aprirsi.
 *
 *     <dw:Button id="__Button_Apri" Text="Modifica" />
 *
 *     <dw:ModalPopup id="__ModalPopup_Scheda" TargetControlID="__Button_Apri"
 *                    OkControlID="__Button_Ok" CancelControlID="__Button_Annulla"
 *                    DragHandleControlID="__Panel_Testata" DropShadow="true">
 *         <dw:Panel id="__Panel_Testata" CssClass="testata">Scheda</dw:Panel>
 *         <dw:TextBox id="__TextBox_Nome" />
 *         <dw:Button id="__Button_Salva" Text="Salva" OnClick="SalvaClick" />
 *         <button type="button" id="__Button_Ok">Ok</button>
 *         <button type="button" id="__Button_Annulla">Annulla</button>
 *     </dw:ModalPopup>
 *
 * CHI LO APRE E CHI LO CHIUDE, e dove. Il click sul TargetControlID lo apre nel browser,
 * senza postback; OK e Annulla lo chiudono nel browser, senza postback - come nel toolkit,
 * dove l'extender annulla il click di quei tre controlli. Un controllo qualunque DENTRO il
 * popup fa il suo postback normale e il popup resta aperto: e' il server a decidere se
 * chiuderlo, con Hide(), tipicamente quando il salvataggio e' andato bene. Show() lo apre
 * dal server, da un handler qualunque.
 *
 * Lo stato aperto/chiuso VIVE NEL BROWSER, in una classe js- che il morph rispetta, e non
 * nel ViewState: Show() e Hide() sono ordini per questa risposta, non uno stato che si
 * porta dietro. Se fosse nello stato, un Annulla fatto nel browser lascerebbe il server
 * convinto che il popup e' aperto, e al postback dopo lo riaprirebbe.
 *
 * Cliccare sullo sfondo non fa niente, di proposito: e' un lavoro da finire o da annullare,
 * non un avviso da far sparire. Esc equivale ad Annulla.
 */
class ModalPopup extends Control
{
    /** L'elemento che, cliccato, apre il popup nel browser. Vuoto: si apre solo da Show(). */
    public string $TargetControlID = '';

    /** L'elemento che chiude il popup "per bene": niente postback, gira OnOkScript. */
    public string $OkControlID = '';

    /** L'elemento che chiude il popup annullando: niente postback, gira OnCancelScript. */
    public string $CancelControlID = '';

    /** L'elemento DENTRO il popup - la testata - che si afferra per trascinarlo. */
    public string $DragHandleControlID = '';

    /** JavaScript che gira quando si chiude con OK. */
    public string $OnOkScript = '';

    /** JavaScript che gira quando si chiude con Annulla, o con Esc. */
    public string $OnCancelScript = '';

    /** Classe in piu' sullo sfondo che copre la pagina. */
    public string $BackgroundCssClass = '';

    public bool $DropShadow = false;

    /** Posizione fissa dell'angolo in alto a sinistra, in pixel. -1 = centrato su quell'asse. */
    public int $X = -1;

    public int $Y = -1;

    /** L'ordine per questa risposta: true apri, false chiudi, null lascia com'e'. */
    private ?bool $ordine = null;

    protected function ViewStateProperties(): array
    {
        return array_merge(parent::ViewStateProperties(), [
            'TargetControlID', 'OkControlID', 'CancelControlID', 'DragHandleControlID',
            'OnOkScript', 'OnCancelScript', 'BackgroundCssClass', 'DropShadow', 'X', 'Y',
        ]);
    }

    /** Apri, da un handler: il browser lo mostra con la risposta di questo postback. */
    public function Show(): void
    {
        $this->ordine = true;
    }

    /** Chiudi, da un handler: tipicamente quando il salvataggio e' andato bene. */
    public function Hide(): void
    {
        $this->ordine = false;
    }

    public function Render(): string
    {
        $html = '<div' . $this->RenderAttributes('dw-popup')
            . ' role="dialog" aria-modal="true" data-dw-modal="1"'
            . $this->Riferimento('target', $this->TargetControlID)
            . $this->Riferimento('ok', $this->OkControlID)
            . $this->Riferimento('cancel', $this->CancelControlID)
            . $this->Riferimento('handle', $this->DragHandleControlID)
            . ($this->OnOkScript === '' ? '' : ' data-dw-modal-ok-script="' . self::HtmlEncode($this->OnOkScript) . '"')
            . ($this->OnCancelScript === '' ? '' : ' data-dw-modal-cancel-script="' . self::HtmlEncode($this->OnCancelScript) . '"')
            . ($this->ordine === null ? '' : ' data-dw-modal-open="' . ($this->ordine ? '1' : '0') . '"')
            . '>';

        $html .= '<div class="dw-popup-fondo' . ($this->BackgroundCssClass === '' ? '' : ' ' . self::HtmlEncode($this->BackgroundCssClass)) . '"></div>';

        $html .= '<div class="dw-popup-scatola' . ($this->DropShadow ? ' dw-popup-ombra' : '') . '"' . $this->Posizione() . '>'
            . $this->RenderChildren()
            . '</div>';

        return $html . '</div>';
    }

    /**
     * Il riferimento a un altro controllo, con il suffisso del contenitore di denominazione:
     * dentro un UserControl gli id escono qualificati, e chi scrive il markup del controllo
     * non deve saperlo - e' la stessa regola di FindControl.
     */
    private function Riferimento(string $ruolo, string $id): string
    {
        if ($id === '')
            return '';

        if ($this->NamingKey !== '' && !str_ends_with($id, '__' . $this->NamingKey))
            $id .= '__' . $this->NamingKey;

        return ' data-dw-modal-' . $ruolo . '="' . self::HtmlEncode($id) . '"';
    }

    /**
     * Centrato per difetto; con X o Y si fissa quell'asse e l'altro resta centrato. La
     * traslazione e' quella che fa il centraggio, quindi si toglie solo sull'asse fissato.
     */
    private function Posizione(): string
    {
        if ($this->X < 0 && $this->Y < 0)
            return '';

        $css = '';

        if ($this->X >= 0)
            $css .= 'left:' . $this->X . 'px;';

        if ($this->Y >= 0)
            $css .= 'top:' . $this->Y . 'px;';

        $css .= 'transform:translate(' . ($this->X >= 0 ? '0' : '-50%') . ',' . ($this->Y >= 0 ? '0' : '-50%') . ')';

        return ' style="' . $css . '"';
    }
}
