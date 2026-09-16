<?php require __DIR__ . '/../Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Common\WebForms\Examples\ModalPopupExample::class,
    'Common/WebForms/Examples/Site'); ?>

<dw:Content placeholder="content">

    <p>
        E' il ModalPopupExtender dell'AjaxControlToolkit, senza extender: il popup <b>e'</b> il
        contenitore. «Apri» lo mostra nel browser, senza postback; finche' e' aperto il resto della
        pagina non si tocca, e la testata si trascina. «Ok» e «Annulla» lo chiudono nel browser,
        senza postback, e fanno girare <code>OnOkScript</code> e <code>OnCancelScript</code>; Esc
        vale Annulla. «Salva» invece e' un bottone qualunque dentro il popup: fa il suo postback,
        e' il server a chiudere con <code>Hide()</code> se il testo va bene — e a lasciarlo aperto,
        con l'avviso sopra, se e' vuoto. «Apri dal server» lo apre con <code>Show()</code>.
    </p>

    <div class="ex-demo">
        <p>
            <dw:Button id="__Button_Open" Text="Apri" />
            <dw:Button id="__Button_OpenFromServer" Text="Apri dal server" OnClick="OpenFromServerClick" />
            <span class="ex-note">ultimo testo salvato: <b><dw:Literal id="__Literal_Saved" /></b>
            &nbsp;·&nbsp; chiuso dal browser con: <b><span id="ex-popup-result" data-dw-client="1">—</span></b></span>
        </p>

        <dw:ModalPopup id="__ModalPopup_Card" TargetControlID="__Button_Open"
                       OkControlID="__Button_Ok" CancelControlID="__Button_Cancel"
                       DragHandleControlID="__Panel_Header" DropShadow="true"
                       OnOkScript="document.getElementById('ex-popup-result').textContent = 'Ok'"
                       OnCancelScript="document.getElementById('ex-popup-result').textContent = 'Annulla'">
            <dw:Panel id="__Panel_Header" CssClass="ex-popup-header">Una scheda — trascinami dalla testata</dw:Panel>

            <p class="ex-note">Quello che scrivi qui viaggia col postback di «Salva» come da qualunque altro campo.</p>

            <p>
                <dw:TextBox id="__TextBox_Card" Placeholder="un testo da salvare" />
                <dw:Button id="__Button_Save" Text="Salva" OnClick="SaveClick" />
            </p>

            <p class="ex-popup-footer">
                <button type="button" id="__Button_Ok">Ok</button>
                <button type="button" id="__Button_Cancel">Annulla</button>
            </p>
        </dw:ModalPopup>

        <p class="ex-note">
            Lo stato aperto/chiuso vive nel browser, in una classe <code>js-</code> che il morph
            rispetta: <code>Show()</code> e <code>Hide()</code> sono ordini per quella risposta, non uno
            stato che si porta dietro. Cliccare sullo sfondo non fa niente, di proposito.
        </p>
    </div>

    <dw:UserControl id="__PropertyTable" src="Common/WebForms/Examples/PropertyTable" Type="ModalPopup" />
    <dw:UserControl id="__SourceView" src="Common/WebForms/Examples/SourceView" />

</dw:Content>
