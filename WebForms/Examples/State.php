<?php require __DIR__ . '/../Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Common\WebForms\Examples\StateExample::class,
    'Common/WebForms/Examples/Site'); ?>

<dw:Content placeholder="content">

    <p>
        Tre livelli, e sono tre cose diverse. <b>Le variabili della pagina</b> e i controlli restano
        da soli a ogni postback, come i campi di una form: nessun attributo. <b><code>#[Portable]</code></b>
        attraversa le pagine, in un pacchetto firmato che il browser si porta dietro navigando.
        <b>Il modo WinForms</b> (<code>KeepState</code>, acceso per tutti) fa si' che anche il primo
        livello sopravviva alla navigazione: si va altrove, si torna, e la pagina e' com'era.
    </p>

    <div class="ex-demo">
        <h3>1. Le variabili restano</h3>

        <p>
            <span class="ex-big"><dw:Literal id="__Literal_Counter" /></span>
            <dw:Button id="__Button_Count" Text="Conta" OnClick="CountClick" />
            &nbsp;
            <dw:TextBox id="__TextBox_Note" Placeholder="scrivi qualcosa" />
            <dw:Button id="__Button_Read" Text="Rileggi" OnClick="ReadClick" />
            <span class="ex-note"><dw:Literal id="__Literal_Note" /></span>
        </p>

        <p class="ex-note">
            <code>public int $Counter</code> e' una proprieta' della classe, senza attributi: al click
            dopo vale ancora. Sta nel ViewState, un campo nascosto firmato — niente sessione sul
            server. Il poco che deve rinascere a ogni richiesta si marca <code>#[Transient]</code>.
        </p>
    </div>

    <div class="ex-demo">
        <h3>2. <code>#[Portable]</code>: quello che attraversa le pagine</h3>

        <p>
            <dw:TextBox id="__TextBox_Name" Placeholder="il tuo nome" />
            <dw:Button id="__Button_Carry" Text="Porta di la'" OnClick="CarryClick" />
            <span class="ex-note">adesso vale: <b><dw:Literal id="__Literal_Carried" /></b> ·
            <a href="Events.php">vai alla pagina degli eventi</a>, che lo legge</span>
        </p>

        <p class="ex-note">
            Il nome della proprieta' e' la chiave: la pagina degli eventi dichiara
            <code>#[Portable] public string $CarriedName</code> uguale, e lo trova. Niente in
            querystring — guarda l'indirizzo — e niente in sessione. Un F5 lo azzera: e' memoria della
            scheda, com'e' un circuito Blazor.
        </p>
    </div>

    <div class="ex-demo">
        <h3>3. Il modo WinForms</h3>

        <p>
            <dw:CheckBox id="__CheckBox_Keep" Text="Tieni questa pagina com'era quando la lascio" Checked="true" AutoPostBack="true" />
        </p>

        <p class="ex-note">
            Acceso — com'e' per tutte le pagine — conta fino a tre, vai su un'altra pagina del menu
            e torna: il contatore e la casella sono dov'erano, e l'ora nel piede e' cambiata perche'
            la pagina e' stata chiesta davvero, con lo stato rimandato dal browser. Spento, torni e
            ricominci da zero: e' il web. Funziona anche col tasto indietro. Il cassetto tiene fino
            a 50 MB di pagine; in console, dopo ogni postback, una riga dice quanto pesa.
        </p>
    </div>

    <dw:UserControl id="__SourceView" src="Common/WebForms/Examples/SourceView" />

</dw:Content>
