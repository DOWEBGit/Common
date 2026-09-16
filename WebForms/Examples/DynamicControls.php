<?php require __DIR__ . '/../Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Common\WebForms\Examples\DynamicControlsExample::class,
    'Common/WebForms/Examples/Site'); ?>

<dw:Content placeholder="content">

    <p>
        Un controllo creato dal codice si scrive dove viene comodo, <b>e resta</b>: in
        <code>OnInit</code>, in <code>OnLoad</code>, dentro un handler. Il contenitore si salva i figli
        che non vengono dal markup — posizione, classe e stato — e li rimette al loro posto prima
        che si legga il form. Quindi funziona tutto e non solo il render: una casella attaccata dal
        codice riceve quello che ci hai scritto come una qualunque. E' il vecchio «me lo tengo in
        sessione», senza sessione. Postback fatti finora: <b><dw:Literal id="__Literal_Postbacks" /></b>
        <dw:Button id="__Button_Postback" Text="Fai un postback e non toccare nient'altro" OnClick="PostbackClick" />
    </p>

    <div class="ex-demo">
        <h3>1. Creati in <code>OnLoad</code>, al primo caricamento <span class="ex-ok">restano</span></h3>

        <p class="ex-note">
            Questa casella e questa etichetta non esistono nel markup: le costruisce il codebehind
            in <code>OnLoad</code>, solo se <code>!IsPostBack</code>. Al postback dopo non ripassa di
            li' — eppure ci sono, e la casella porta quello che ci hai scritto.
        </p>

        <dw:PlaceHolder id="__PlaceHolder_Once" />
    </div>

    <div class="ex-demo">
        <h3>2. Creati dentro un handler <span class="ex-ok">restano anche loro</span></h3>

        <p>
            <dw:Button id="__Button_Add" Text="Aggiungi al volo" OnClick="AddClick" />
            <span class="ex-note">etichette vive adesso: <b><dw:Literal id="__Literal_Labels" /></b></span>
        </p>

        <dw:PlaceHolder id="__PlaceHolder_Volatile" />
    </div>

    <div class="ex-demo">
        <h3>3. Righe di tabella a mano: il caso piu' severo</h3>

        <p class="ex-note">
            <b>In <code>OnLoad</code> non succede niente.</b> Ogni riga nasce da un click: un
            <code>Panel</code> con <code>Tag="tr"</code>, due celle <code>Tag="td"</code>, dentro un
            <code>Label</code> e un <code>LinkButton</code> «elimina» che funziona ancora al postback
            dopo. Un albero dinamico <b>annidato</b>, un controllo che <b>scatena eventi</b> creato
            dal codice, la <b>rimozione</b> che resta rimossa senza spostare le altre, e il <b>tag</b>
            giusto — un <code>div</code> dentro una <code>table</code> il browser lo butta fuori. L'orario
            in ogni riga dice se qualcuno l'ha ricostruita: dopo cinque postback e' ancora quello del
            click.
        </p>

        <p>
            <dw:Button id="__Button_AddRow" Text="Aggiungi una riga" OnClick="AddRowClick" />
            <span class="ex-note">righe: <b><dw:Literal id="__Literal_Rows" /></b></span>
        </p>

        <table>
            <thead>
            <tr><th>Riga</th><th class="ex-action"></th></tr>
            </thead>

            <dw:Panel id="__Panel_Rows" Tag="tbody" />

            <tfoot>
            <tr><td colspan="2" class="ex-empty">Nessuna riga: premi «aggiungi una riga».</td></tr>
            </tfoot>
        </table>
    </div>

    <p class="ex-note">
        La maniera classica di WebForms — ricreare il controllo in <code>OnInit</code> ad ogni
        richiesta con lo stesso id — continua a funzionare, senza doppioni: se al momento di rimettere
        lo stato esiste gia' un figlio con quell'id, il motore gli posa sopra lo stato invece di
        aggiungerne un secondo. L'id dev'essere <b>stabile</b>: un contatore che riparte da capo e' un
        controllo nuovo ogni volta, e lo stato del precedente resta orfano.
    </p>

    <dw:UserControl id="__SourceView" src="Common/WebForms/Examples/SourceView" />

</dw:Content>
