<?php require __DIR__ . '/../Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Common\WebForms\Examples\EventsExample::class,
    'Common/WebForms/Examples/Site'); ?>

<dw:Content placeholder="content">

    <p>
        <b>Apri questa pagina in due schede.</b> Un click in una fa un postback e l'handler chiama
        <code>EntityEvents::Notify('Saluti')</code>: un nome, niente dati. Ogni pagina aperta che in
        <code>OnInit</code> ha fatto <code>$this->Subscribe('Saluti', ...)</code> riceve l'evento
        <b>sul server</b>, dentro un postback suo, e fa quello che vuole — un avviso, una rilettura,
        un <code>DataBind</code>. Zero JavaScript scritto dalla pagina. La scheda che ha premuto
        scarta la propria notifica: si e' gia' aggiornata con quel postback.
    </p>

    <div class="ex-demo">
        <h3>Un nome a tutti: <code>Notify()</code> e <code>Subscribe()</code></h3>

        <p>
            <dw:Button id="__Button_Greet" Text="Saluta tutti" OnClick="GreetClick" />
            <span class="ex-note">saluti ricevuti da questa pagina: <b><dw:Literal id="__Literal_Greetings" /></b></span>
        </p>

        <p class="ex-note">
            L'avviso e il contatore in fondo al menu li fa la <b>cornice</b>, iscritta una volta con
            <code>$this->Page->Subscribe()</code> per tutte le pagine che la ereditano — anche quelle
            che di «Saluti» non sanno niente. Questa pagina tiene in piu' un conto suo: allo stesso
            evento si iscrivono in due, e girano tutti e due.
        </p>
    </div>

    <div class="ex-demo">
        <h3>Un oggetto in viaggio: <code>Notify('User', dati: $user)</code></h3>

        <p>
            <dw:Button id="__Button_User" Text="Manda un utente (nome, cognome, email, immagine)" OnClick="UserClick" />
        </p>

        <dw:Panel id="__Panel_User" Visible="false">
            <p>
                <dw:Literal id="__Literal_Photo" Mode="PassThrough" />
                <b><dw:Literal id="__Literal_UserName" /></b>
                <span class="ex-note">&lt;<dw:Literal id="__Literal_Email" />&gt;</span>
                — arrivato alle <dw:Literal id="__Literal_ArrivedAt" />
            </p>
        </dw:Panel>

        <p class="ex-note">
            L'oggetto <code>User</code> passa da <code>json_encode</code> e arriva all'handler dell'<b>altra</b>
            scheda come array con le stesse chiavi, firmato: un browser non puo' cambiarlo. Ma lo vede
            chiunque abbia una pagina aperta: ci va solo cio' che tutti possono vedere. Gli eventi dei
            salvataggi, quelli automatici, portano solo il nome.
        </p>
    </div>

    <div class="ex-demo">
        <h3>Dati al browser senza postback: <code>Broadcast()</code> e <code>DW.on()</code></h3>

        <p>
            <dw:TextBox id="__TextBox_Message" Placeholder="un messaggio" Text="ciao" />
            <dw:Button id="__Button_Broadcast" Text="Broadcast" OnClick="BroadcastClick" />
        </p>

        <div id="ex-received" data-dw-client="1" class="ex-note">niente ricevuto ancora — arriva anche a questa scheda</div>

        <script>
            // roba della pagina: DW.on vive quanto la pagina, e si toglie da solo cambiandola
            DW.on('Message', dati => {
                const riga = document.createElement('div');
                riga.dataset.dwClient = '1';
                riga.textContent = dati.text + ' — alle ' + dati.at;
                document.getElementById('ex-received').prepend(riga);
            });
        </script>

        <p class="ex-note">
            Qui il browser reagisce <b>senza postback</b>, e il prezzo e' un pezzo di JavaScript: e' per
            un contatore che pulsa, un «qualcuno sta scrivendo». Le righe aggiunte dallo script portano
            <code>data-dw-client</code>, cosi' il morph del postback dopo non le toglie.
        </p>
    </div>

    <p class="ex-note">
        Il nome portato dalla <a href="State.php">pagina dello stato</a> con <code>#[Portable]</code>:
        <b><dw:Literal id="__Literal_Carried" /></b>
        <dw:Button id="__Button_Upper" Text="Maiuscolo, e riportalo indietro" OnClick="UpperClick" />
    </p>

    <dw:UserControl id="__SourceView" src="Common/WebForms/Examples/SourceView" />

</dw:Content>
