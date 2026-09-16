<?php require __DIR__ . '/../Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Common\WebForms\Examples\CheckBoxExample::class,
    'Common/WebForms/Examples/Site'); ?>

<dw:Content placeholder="content">

    <p>
        Una casella da spuntare, con l'etichetta accanto in <code>Text</code>. <code>Checked</code>
        torna al server a ogni postback; con <code>AutoPostBack</code> il click sulla casella e' un
        postback e <code>OnCheckedChanged</code> gira subito. Rende anche un campo nascosto
        <code>&lt;id&gt;__presente</code>: una casella non spuntata non compare fra i campi
        inviati, e l'assenza va distinta dal "controllo non era in pagina".
    </p>

    <div class="ex-demo">
        <p>
            <dw:CheckBox id="__CheckBox_Details" Text="Mostra i dettagli (AutoPostBack)" AutoPostBack="true" OnCheckedChanged="DetailsChanged" />
        </p>

        <dw:Panel id="__Panel_Details" Visible="false">
            <p class="ex-note">Questi dettagli li ha mostrati il server: la casella ha fatto postback e l'handler ha acceso il Panel.</p>
        </dw:Panel>

        <p>
            <dw:CheckBox id="__CheckBox_Terms" Text="Accetto le condizioni" />
            <dw:CheckBox id="__CheckBox_News" Text="Voglio le novita'" Checked="true" />
            <dw:Button id="__Button_Save" Text="Salva" OnClick="SaveClick" />
        </p>

        <p class="ex-note"><dw:Literal id="__Literal_Saved" /></p>
    </div>

    <dw:UserControl id="__PropertyTable" src="Common/WebForms/Examples/PropertyTable" Type="CheckBox" />
    <dw:UserControl id="__SourceView" src="Common/WebForms/Examples/SourceView" />

</dw:Content>
