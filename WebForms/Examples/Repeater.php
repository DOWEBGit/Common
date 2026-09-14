<?php require __DIR__ . '/../Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Common\WebForms\Examples\RepeaterExample::class,
    'Common/WebForms/Examples/Site'); ?>

<dw:Content placeholder="content">

    <p>
        Le righe da un array: <code>DataSource</code>, <code>DataBind()</code>, e per ogni riga
        l'<code>ItemTemplate</code> con i segnaposto <code>{{Campo}}</code> e i controlli dentro.
        <code>DataKeyField</code> e' la chiave del morph — le righe si riconoscono dal dato, mai
        dalla posizione. <code>Tag</code> e <code>ItemTag</code> perche' un <code>div</code> dentro una
        <code>table</code> il browser lo butta fuori.
    </p>

    <div class="ex-demo">
        <p>
            <dw:Button id="__Button_Bind" Text="Rileggi (DataBind)" OnClick="BindClick" />
            <dw:Button id="__Button_Clear" Text="Svuota (ClearItems)" OnClick="ClearClick" />
            <dw:Button id="__Button_Nothing" Text="Postback che non tocca l'elenco" OnClick="NothingClick" />
            <span class="ex-note">DataBind fatti: <b><dw:Literal id="__Literal_Binds" /></b> · postback: <b><dw:Literal id="__Literal_Postbacks" /></b></span>
        </p>

        <table>
            <thead>
            <tr><th style="width:50px">N.</th><th style="width:140px">Nome</th><th>Dall'handler OnItemDataBound</th><th>Dal codice, dopo il DataBind</th><th style="width:160px"></th></tr>
            </thead>
            <dw:Repeater id="__Repeater_Items" Tag="tbody" ItemTag="tr" DataKeyField="Id" OnItemDataBound="ItemDataBound">
                <ItemTemplate>
                    <td>{{Id}}</td>
                    <td>{{Name}}</td>
                    <td><dw:Literal id="__Literal_Bound" /></td>
                    <td><dw:Literal id="__Literal_Note" /></td>
                    <td><dw:LinkButton id="__LinkButton_Touch" Text="tocca questa riga" OnClick="TouchClick" /></td>
                </ItemTemplate>
            </dw:Repeater>
            <tfoot>
            <tr><td colspan="5" class="ex-empty">Nessuna riga: premi «Rileggi».</td></tr>
            </tfoot>
        </table>

        <p class="ex-note">
            La terza colonna la scrive <code>OnItemDataBound</code>, riga per riga durante il
            <code>DataBind()</code>; la quarta, il bordo colorato e il <code>title</code> del link li mette il
            codice <b>dopo</b>, scorrendo <code>Items()</code>. Nessuna delle due cose sta nel template o nei
            dati: sopravvivono al postback che non ridatabinda perche' il Repeater salva solo quello che
            il codice ha cambiato rispetto alla riga appena nata. «Tocca» scrive nella riga da cui e'
            partito, ritrovando i fratelli con l'id nudo del template.
        </p>
    </div>

    <dw:UserControl id="__PropertyTable" src="Common/WebForms/Examples/PropertyTable" Type="Repeater,RepeaterItem" />
    <dw:UserControl id="__SourceView" src="Common/WebForms/Examples/SourceView" />

</dw:Content>
