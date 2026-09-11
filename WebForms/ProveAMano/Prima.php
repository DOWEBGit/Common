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
