<?php require __DIR__ . '/../Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Common\WebForms\ProveAMano\Prima::class,
    'Common/WebForms/ProveAMano/Cornice'); ?>

<dw:Content placeholder="corpo">

    <div class="pm-card">
        <h2>Lo stato di QUESTA pagina</h2>

        <p class="pm-tenue">
            Il contatore e la casella vivono nel ViewState di questa pagina. Restano a ogni
            click; vai sulla seconda pagina e torna, e li ritrovi da capo — <b>lo stato di una
            pagina vale per quella pagina e per quella visita</b>. E' il web, non WinForms.
        </p>

        <p>
            <span class="pm-grande"><dw:Literal id="litContatore" /></span>
            <dw:Button id="btnConta" Text="Conta" OnClick="ContaClick" />
        </p>

        <p>
            <dw:TextBox id="txtNota" Placeholder="scrivi qualcosa" />
            <dw:Button id="btnRileggi" Text="Rileggi" OnClick="RileggiClick" />
            <span class="pm-tenue"><dw:Literal id="litNota" /></span>
        </p>
    </div>

    <div class="pm-card">
        <h2>Quello che invece attraversa le pagine</h2>

        <p class="pm-tenue">
            Questo campo e' <code>#[Portable]</code>: non sta nel ViewState della pagina, sta
            in un pacchetto firmato che il browser si porta dietro navigando. Scrivi un nome,
            premi «porta di la'», e vai sulla seconda pagina: lo trovi gia' scritto.
        </p>

        <p>
            <dw:TextBox id="txtNome" Placeholder="il tuo nome" />
            <dw:Button id="btnPorta" Text="Porta di la'" OnClick="PortaClick" />
        </p>

        <p class="pm-tenue">
            adesso vale: <b><dw:Literal id="litPortato" /></b> &nbsp;·&nbsp;
            <a href="Seconda.php">vai alla seconda pagina</a>
        </p>
    </div>

    <div class="pm-card">
        <h2>Il DatePicker: il calendario del browser, date vere sul server</h2>

        <p class="pm-tenue">
            Due selettori nativi — solo giorno, e giorno con ora — con <code>AutoPostBack</code>:
            scegli e il server rilegge una <code>DateTimeImmutable</code>, non una stringa, e la
            riscrive qui sotto nel suo formato. Il terzo cambia il modo del primo al volo:
            il valore resta e si adegua al tipo dell'input.
        </p>

        <p>
            <dw:DatePicker id="dtGiorno" Mode="Date" AutoPostBack="true" OnDateChanged="DataCambiata" />
            <dw:DatePicker id="dtQuando" Mode="DateTime" AutoPostBack="true" OnDateChanged="DataCambiata" />
            <dw:Button id="btnCambiaModo" Text="Cambia il modo del primo" OnClick="CambiaModoClick" />
        </p>

        <p class="pm-tenue"><dw:Literal id="litDate" /></p>
    </div>

    <div class="pm-card">
        <h2>Un evento a tutti i browser, backend a backend: <code>Notify()</code> e <code>Subscribe()</code></h2>

        <p class="pm-tenue">
            Apri questa pagina e la <a href="Seconda.php">seconda</a> in due schede. Il bottone fa
            un postback e l'handler chiama <code>EntityEvents::Notify('Saluti')</code>: e' un nome,
            niente dati. Ogni pagina aperta che in <code>OnInit</code> ha fatto
            <code>$this->Subscribe('Saluti', ...)</code> riceve l'evento <b>sul server</b>, dentro
            un postback suo, e fa quello che vuole — qui un avviso, di la' anche un contatore. Zero
            JavaScript scritto dalla pagina: il runtime porta solo il nome, il codebehind risponde.
        </p>

        <p>
            <dw:Button id="btnSaluta" Text="Saluta tutti" OnClick="SalutaClick" />
            <span class="pm-tenue">saluti ricevuti da questa pagina: <b><dw:Literal id="litSaluti" /></b></span>
        </p>
    </div>

    <div class="pm-card">
        <h2>Il modo WinForms</h2>

        <p>
            <dw:CheckBox id="chkTieni" Text="Tieni questa pagina com'era quando la lascio" Checked="true" AutoPostBack="true" />
        </p>

        <p class="pm-tenue">
            E' acceso per tutte le pagine: il browser conserva lo stato di questa e glielo
            rimanda quando ci torni, contatore e casella sono ancora dov'erano. Spento,
            ricominci da zero - com'e' il web.
            Prova a contare fino a tre, andare sulla seconda pagina e tornare, in tutti e due
            i modi. Ricaricare con F5 butta via il tenuto: e' memoria di questa scheda, non
            roba salvata da qualche parte.
        </p>
    </div>

</dw:Content>
