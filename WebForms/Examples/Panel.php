<?php require __DIR__ . '/../Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Common\WebForms\Examples\PanelExample::class,
    'Common/WebForms/Examples/Site'); ?>

<dw:Content placeholder="content">

    <p>
        Due contenitori. <code>Panel</code> rende un elemento — <code>div</code>, o quello che dice
        <code>Tag</code>: <code>tbody</code>, <code>tr</code>, <code>span</code> — e serve a
        raggruppare e a nascondere un pezzo di pagina in un colpo solo. <code>PlaceHolder</code> non
        rende niente di suo: e' il punto dove il codebehind attacca i controlli che crea a runtime.
    </p>

    <div class="ex-demo">
        <h3>Nascondere in un colpo</h3>

        <p>
            <dw:Button id="__Button_Toggle" Text="Mostra / nascondi il riquadro" OnClick="ToggleClick" />
        </p>

        <dw:Panel id="__Panel_Box" CssClass="ex-demo" Tag="section">
            <p>Questo riquadro e' un <code>Panel</code> con <code>Tag="section"</code>. Dentro c'e' una casella:
                <dw:TextBox id="__TextBox_Inside" Placeholder="scrivi, poi nascondi e rimostra" />
            </p>
            <p class="ex-note">
                <code>Visible=false</code> non lo toglie dall'HTML: lo rende con <code>hidden</code>, cosi' il
                morph lo ritrova quando torna visibile e la casella ha ancora il suo testo. Un controllo
                nascosto pero' <b>non scatena eventi</b>: il server li ferma.
            </p>
        </dw:Panel>
    </div>

    <div class="ex-demo">
        <h3>Attaccare dal codice</h3>

        <p>
            <dw:Button id="__Button_Add" Text="Aggiungi un'etichetta" OnClick="AddClick" />
            <dw:Button id="__Button_Clear" Text="Svuota" OnClick="ClearClick" />
            <span class="ex-note">etichette: <b><dw:Literal id="__Literal_Count" /></b></span>
        </p>

        <dw:PlaceHolder id="__PlaceHolder_Labels" />

        <p class="ex-note">
            Le etichette non stanno nel markup: le crea l'handler e le attacca con <code>Add()</code>.
            Al postback dopo ci sono ancora — con posizione, classe e testo — perche' il contenitore
            si salva i figli che non vengono dal markup. Il caso completo, con righe di tabella e
            bottoni dentro, e' in <a href="DynamicControls.php">Controlli creati dal codice</a>.
        </p>
    </div>

    <dw:UserControl id="__PropertyTable" src="Common/WebForms/Examples/PropertyTable" Type="Panel,PlaceHolder" />
    <dw:UserControl id="__SourceView" src="Common/WebForms/Examples/SourceView" />

</dw:Content>
