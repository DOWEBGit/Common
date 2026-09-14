<?php require __DIR__ . '/../Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Common\WebForms\Examples\TextBoxExample::class,
    'Common/WebForms/Examples/Site'); ?>

<dw:Content placeholder="content">

    <p>
        La casella di testo: una riga, piu' righe (<code>TextMode="MultiLine"</code>), password.
        Il valore torna al server in <code>Text</code> a ogni postback, come per qualunque
        controllo; con <code>AutoPostBack</code> parte da solo quando cambia, e con
        <code>AutoPostBackDelay</code> mentre si scrive, trattenuto per quella pausa: digitando
        "color" parte <b>una</b> richiesta, non cinque.
    </p>

    <div class="ex-demo">
        <h3>Ricerca mentre si scrive</h3>

        <p>
            <dw:TextBox id="__TextBox_Filter" Placeholder="filtra i paesi..." AutoPostBack="true" AutoPostBackDelay="350" OnTextChanged="FilterChanged" />
            <span class="ex-note">postback fatti: <b><dw:Literal id="__Literal_Postbacks" /></b></span>
        </p>

        <p><dw:Literal id="__Literal_Countries" /></p>
    </div>

    <div class="ex-demo">
        <h3>Le tre forme</h3>

        <p><dw:TextBox id="__TextBox_Single" Placeholder="una riga" /></p>
        <p><dw:TextBox id="__TextBox_Multi" TextMode="MultiLine" Rows="3" Placeholder="piu' righe: e' una textarea" /></p>
        <p><dw:TextBox id="__TextBox_Secret" TextMode="Password" Placeholder="password: non torna mai nel markup" /></p>
        <p><dw:TextBox id="__TextBox_Email" Type="email" Placeholder="Type=&quot;email&quot;: la tastiera giusta sul telefono" /></p>

        <p>
            <dw:Button id="__Button_Read" Text="Leggi sul server" OnClick="ReadClick" />
            <dw:Button id="__Button_Toggle" Text="Abilita / disabilita" OnClick="ToggleClick" />
        </p>

        <p class="ex-note"><dw:Literal id="__Literal_Read" /></p>
    </div>

    <dw:UserControl id="__PropertyTable" src="Common/WebForms/Examples/PropertyTable" Type="TextBox" />
    <dw:UserControl id="__SourceView" src="Common/WebForms/Examples/SourceView" />

</dw:Content>
