<?php require __DIR__ . '/../Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Common\WebForms\Examples\DropDownListExample::class,
    'Common/WebForms/Examples/Site'); ?>

<dw:Content placeholder="content">

    <p>
        La tendina. Le voci si dichiarano nel markup con <code>&lt;dw:ListItem&gt;</code> o si
        assegnano dal codice in <code>Items</code> (valore ⇒ testo): al montaggio le voci del markup
        finiscono in <code>Items</code> e da li' in poi non c'e' differenza. Il valore che torna dal
        browser si accetta <b>solo se e' una delle voci rese</b>: altrimenti la tendina diventerebbe
        un campo di testo libero.
    </p>

    <div class="ex-demo">
        <p>
            Colore, dal markup:
            <dw:DropDownList id="__DropDownList_Color" AutoPostBack="true" OnSelectedIndexChanged="ColorChanged">
                <dw:ListItem Value="" Text="— scegli —" />
                <dw:ListItem Value="#b91c1c" Text="Rosso" />
                <dw:ListItem Value="#15803d" Text="Verde" Selected="true" />
                <dw:ListItem Value="#1d4ed8" Text="Blu" />
            </dw:DropDownList>
            <dw:Label id="__Label_Sample" Text="■ anteprima" />
        </p>

        <p>
            Citta', dal codice, secondo la regione:
            <dw:DropDownList id="__DropDownList_Region" AutoPostBack="true" OnSelectedIndexChanged="RegionChanged" />
            <dw:DropDownList id="__DropDownList_City" />
            <dw:Button id="__Button_Read" Text="Leggi" OnClick="ReadClick" />
        </p>

        <p class="ex-note"><dw:Literal id="__Literal_Read" /></p>
    </div>

    <dw:UserControl id="__PropertyTable" src="Common/WebForms/Examples/PropertyTable" Type="DropDownList,ListItem" />
    <dw:UserControl id="__SourceView" src="Common/WebForms/Examples/SourceView" />

</dw:Content>
