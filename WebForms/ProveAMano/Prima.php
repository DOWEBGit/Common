<?php require __DIR__ . '/../Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Common\WebForms\ProveAMano\Prima::class,
    'Common/WebForms/ProveAMano/Cornice'); ?>

<dw:Content placeholder="corpo">

    <div class="pm-card">
        <h2>Lo stato di QUESTA pagina</h2>

        <p class="pm-tenue">
            Il contatore e la casella vivono nel ViewState di questa pagina: restano a ogni
            click. Vai sulla seconda pagina e torna: li ritrovi dov'erano, perche' il modo
            WinForms (piu' sotto) e' acceso — spegnilo, e li ritrovi da capo com'e' il web.
        </p>

        <p>
            <span class="pm-grande"><dw:Literal id="__Literal_Contatore" /></span>
            <dw:Button id="__Button_Conta" Text="Conta" OnClick="ContaClick" />
        </p>

        <p>
            <dw:TextBox id="__TextBox_Nota" Placeholder="scrivi qualcosa" />
            <dw:Button id="__Button_Rileggi" Text="Rileggi" OnClick="RileggiClick" />
            <span class="pm-tenue"><dw:Literal id="__Literal_Nota" /></span>
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
            <dw:TextBox id="__TextBox_Nome" Placeholder="il tuo nome" />
            <dw:Button id="__Button_Porta" Text="Porta di la'" OnClick="PortaClick" />
        </p>

        <p class="pm-tenue">
            adesso vale: <b><dw:Literal id="__Literal_Portato" /></b> &nbsp;·&nbsp;
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
            <dw:DatePicker id="__DatePicker_Giorno" Mode="Date" AutoPostBack="true" OnDateChanged="DataCambiata" />
            <dw:DatePicker id="__DatePicker_Quando" Mode="DateTime" AutoPostBack="true" OnDateChanged="DataCambiata" />
            <dw:Button id="__Button_CambiaModo" Text="Cambia il modo del primo" OnClick="CambiaModoClick" />
        </p>

        <p class="pm-tenue"><dw:Literal id="__Literal_Date" /></p>
    </div>

    <div class="pm-card">
        <h2>Un evento a tutti i browser, backend a backend: <code>Notify()</code> e <code>Subscribe()</code></h2>

        <p class="pm-tenue">
            Apri questa pagina e la <a href="Seconda.php">seconda</a> in due schede. Il bottone fa
            un postback e l'handler chiama <code>EntityEvents::Notify('Saluti')</code>: e' un nome,
            niente dati. Ogni pagina aperta che in <code>OnInit</code> ha fatto
            <code>$this->Subscribe('Saluti', ...)</code> riceve l'evento <b>sul server</b>, dentro
            un postback suo, e fa quello che vuole. L'avviso e il contatore in testata li fa la
            <b>cornice</b>, iscritta una volta per tutte le pagine che la ereditano — anche
            Tabella e Stato, che di «Saluti» non sanno niente; queste due pagine tengono in piu'
            un conto loro. Zero JavaScript scritto dalla pagina: il runtime porta solo il nome, il
            codebehind risponde.
        </p>

        <p>
            <dw:Button id="__Button_Saluta" Text="Saluta tutti" OnClick="SalutaClick" />
            <dw:Button id="__Button_Utente" Text="Manda un utente (nome, cognome, email, immagine)" OnClick="UtenteClick" />
            <span class="pm-tenue">saluti ricevuti da questa pagina: <b><dw:Literal id="__Literal_Saluti" /></b></span>
        </p>

        <p class="pm-tenue">
            Il secondo bottone passa un <b>oggetto</b> — <code>new Utente(...)</code> — come terzo
            argomento di <code>Notify('Utente', dati: $utente)</code>. Arriva all'handler iscritto
            della seconda pagina come array con le stesse chiavi, e lei lo mostra con la foto.
            Il pacchetto viaggia firmato: un browser non puo' cambiarlo. Ma lo vede chiunque abbia
            una pagina aperta: ci va solo cio' che tutti possono vedere.
        </p>
    </div>

    <div class="pm-card">
        <h2>Il modo WinForms</h2>

        <p>
            <dw:CheckBox id="__CheckBox_Tieni" Text="Tieni questa pagina com'era quando la lascio" Checked="true" AutoPostBack="true" />
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

    <div class="pm-card">
        <h2>Una finestra sopra la pagina: <code>&lt;dw:ModalPopup&gt;</code></h2>

        <p class="pm-tenue">
            E' il ModalPopupExtender dell'AjaxControlToolkit, senza extender: il popup E' il
            contenitore. «Apri» lo mostra nel browser, senza postback; finche' e' aperto il
            resto della pagina non si tocca, e la testata si trascina. «Ok» e «Annulla» lo
            chiudono nel browser, senza postback, e fanno girare <code>OnOkScript</code> e
            <code>OnCancelScript</code>; Esc vale Annulla. «Salva» invece e' un bottone
            qualunque dentro il popup: fa il suo postback, e' il server a chiudere con
            <code>Hide()</code> se il testo va bene — e a lasciarlo aperto, con l'avviso, se
            e' vuoto. «Apri dal server» lo apre con <code>Show()</code> da un handler.
        </p>

        <p>
            <dw:Button id="__Button_Apri" Text="Apri" />
            <dw:Button id="__Button_ApriDalServer" Text="Apri dal server" OnClick="ApriDalServerClick" />
            <span class="pm-tenue">ultimo testo salvato: <b><dw:Literal id="__Literal_Salvato" /></b>
            &nbsp;·&nbsp; chiuso dal browser con: <b><span id="pm-esito-popup" data-dw-client="1">—</span></b></span>
        </p>

        <dw:ModalPopup id="__ModalPopup_Scheda" TargetControlID="__Button_Apri"
                       OkControlID="__Button_Ok" CancelControlID="__Button_Annulla"
                       DragHandleControlID="__Panel_Testata" DropShadow="true"
                       OnOkScript="document.getElementById('pm-esito-popup').textContent = 'Ok'"
                       OnCancelScript="document.getElementById('pm-esito-popup').textContent = 'Annulla'">
            <dw:Panel id="__Panel_Testata" CssClass="pm-popup-testata">Una scheda — trascinami dalla testata</dw:Panel>

            <p class="pm-tenue">Quello che scrivi qui viaggia col postback di «Salva» come da qualunque altro campo.</p>

            <p>
                <dw:TextBox id="__TextBox_Scheda" Placeholder="un testo da salvare" />
                <dw:Button id="__Button_Salva" Text="Salva" OnClick="SalvaSchedaClick" />
            </p>

            <p class="pm-popup-piede">
                <button type="button" id="__Button_Ok">Ok</button>
                <button type="button" id="__Button_Annulla">Annulla</button>
            </p>
        </dw:ModalPopup>
    </div>

</dw:Content>
