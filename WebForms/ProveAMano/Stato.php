<?php require __DIR__ . '/../Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Common\WebForms\ProveAMano\Stato::class,
    'Common/WebForms/ProveAMano/Cornice'); ?>

<dw:Content placeholder="corpo">

<p class="pm-tenue" style="max-width:900px">
    Nessun database, nessun Model: solo il motore e la cornice qui attorno. Ogni riquadro fa
    una domanda sola, e la risposta si legge premendo il bottone e guardando cosa resta.
    Postback fatti finora: <b><dw:Literal id="__Literal_Click" /></b>
    <dw:Button id="__Button_Postback" Text="Fai un postback e non toccare nient'altro" OnClick="PostbackClick" />
</p>

<div class="pm-card">
    <h2>1. Righe di Repeater vestite dal codice</h2>

    <p class="pm-tenue">
        Il bordo colorato, la classe, il <code>title</code> del link e la nota non stanno nel
        markup e non stanno nei dati: le mette il codice <b>dopo il DataBind</b>, senza
        <code>OnItemDataBound</code>. Il bottone qui sopra non ridatabinda niente.
    </p>

    <table>
        <thead>
        <tr><th style="width:60px">N.</th><th style="width:120px">Nome</th><th>Messo dal codice</th><th style="width:180px"></th></tr>
        </thead>
        <dw:Repeater id="__Repeater_Elenco" Tag="tbody" ItemTag="tr" DataKeyField="Id">
            <ItemTemplate>
                <td>{{Id}}</td>
                <td>{{Nome}}</td>
                <td><dw:Literal id="__Literal_Nota" /></td>
                <td><dw:LinkButton id="__LinkButton_Tocca" Text="tocca questa riga" OnClick="ToccaClick" /></td>
            </ItemTemplate>
        </dw:Repeater>
    </table>
</div>

<div class="pm-card">
    <h2>2. Controlli creati dal codice in <code>OnInit</code> <span class="pm-si">restano</span></h2>

    <p class="pm-tenue">
        Questa casella e questa etichetta non esistono nel markup: le costruisce il codebehind
        in <code>OnInit</code>, ad ogni richiesta, con lo stesso id. E' la maniera classica di
        WebForms, e continua a funzionare: il motore le ritrova, ci rimette sopra il loro stato
        e ci passa quello che ci hai scritto dentro. Nessun doppione, anche se adesso lo stato
        saprebbe ricostruirle da solo.
    </p>

    <dw:PlaceHolder id="__PlaceHolder_Sempre" />
</div>

<div class="pm-card">
    <h2>3. Controlli creati in <code>OnLoad</code> o dentro un handler <span class="pm-si">restano anche loro</span></h2>

    <p class="pm-tenue">
        La riga qui sotto nasce in <code>OnLoad</code>, al primo caricamento e basta. Le altre
        le aggiunge l'handler del bottone, una per click. Nessuno le ricostruisce: se le rimette
        lo stato, che si porta dietro <b>dove</b> stavano, <b>di che classe</b> erano e
        <b>com'erano messe</b>. Premi «aggiungi al volo» due o tre volte, poi il bottone in
        cima: restano tutte, e in ordine.
    </p>

    <p>
        <dw:Button id="__Button_Volo" Text="Aggiungi al volo" OnClick="VoloClick" />
        <span class="pm-tenue">etichette vive adesso: <b><dw:Literal id="__Literal_Volo" /></b></span>
    </p>

    <dw:PlaceHolder id="__PlaceHolder_Volatile" />
</div>

</dw:Content>
