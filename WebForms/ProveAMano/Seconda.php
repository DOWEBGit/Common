<?php require __DIR__ . '/../Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Common\WebForms\ProveAMano\Seconda::class,
    'Common/WebForms/ProveAMano/Cornice'); ?>

<dw:Content placeholder="corpo">

    <div class="pm-card">
        <h2>Quello che e' arrivato dalla prima pagina</h2>

        <p class="pm-grande"><dw:Literal id="litArrivato" /></p>

        <p class="pm-tenue">
            Questa pagina non ha ricevuto niente in querystring — guarda l'indirizzo — e non
            c'e' nessuna sessione sul server. Il nome sta in una proprieta'
            <code>#[Portable]</code> che si chiama come quella di la': e' il nome della
            proprieta' a fare da chiave.
        </p>

        <p>
            <dw:Button id="btnMaiuscolo" Text="Scrivilo maiuscolo, e riportalo indietro" OnClick="MaiuscoloClick" />
            <dw:Button id="btnSvuota" Text="Svuotalo" OnClick="SvuotaClick" />
        </p>

        <p class="pm-tenue">
            Poi <a href="Prima.php">torna alla prima pagina</a>: quello che hai cambiato qui
            e' gia' di la'. Il viaggio vale nei due sensi.
        </p>
    </div>

    <div class="pm-card">
        <h2>Anche questa pagina e' iscritta a «Saluti»</h2>

        <p class="pm-tenue">
            Premi «Saluta tutti» sulla prima pagina in un'altra scheda: qui compare l'avviso e il
            contatore sale, senza che questa pagina abbia fatto niente. Il numero lo aggiorna il
            codebehind di questa pagina, sul server, dentro l'handler iscritto in <code>OnInit</code>.
        </p>

        <p class="pm-grande"><dw:Literal id="litSaluti" /></p>

        <h2>E a «Utente»: l'oggetto arriva qui, sul server, e si mostra</h2>

        <dw:Panel id="pnlUtente" Visible="false">
            <p>
                <dw:Literal id="litFoto" Mode="PassThrough" />
                <b><dw:Literal id="litNomeUtente" /></b>
                <span class="pm-tenue">&lt;<dw:Literal id="litEmail" />&gt;</span>
            </p>
        </dw:Panel>
    </div>

    <div class="pm-card">
        <h2>E il suo contatore, che invece e' suo</h2>

        <p>
            <span class="pm-grande"><dw:Literal id="litContatore" /></span>
            <dw:Button id="btnConta" Text="Conta" OnClick="ContaClick" />
        </p>

        <p class="pm-tenue">
            Ogni pagina ha il suo ViewState: questo contatore e quello della prima pagina non
            si vedono fra loro, e nessuno dei due sopravvive alla navigazione — a meno di
            accendere il modo WinForms, che li' e' una casella e vale solo per quella pagina.
        </p>
    </div>

</dw:Content>
