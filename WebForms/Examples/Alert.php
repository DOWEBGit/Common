<?php require __DIR__ . '/../Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Common\WebForms\Examples\AlertExample::class,
    'Common/WebForms/Examples/Site'); ?>

<dw:Content placeholder="content">

    <p>
        Gli avvisi: «salvato», «non salvato e il perche'». Il controllo <code>&lt;dw:Alert&gt;</code>
        si mette <b>una volta nella master</b> — qui in <code>Site.php</code>, con
        <code>Duration="4000"</code> — e da qualunque handler di qualunque pagina si chiama
        <code>$this->Alert->Success()</code> o <code>Fail()</code>. Due forme: il riquadro in alto a
        destra se ne va da solo e non ferma niente; il modale oscura la pagina e vuole un OK, ed
        e' per quando il messaggio va letto <b>per forza</b>.
    </p>

    <div class="ex-demo">
        <p>
            <dw:Button id="__Button_Success" Text="Success" OnClick="SuccessClick" />
            <dw:Button id="__Button_Fail" Text="Fail" OnClick="FailClick" />
            <dw:Button id="__Button_Modal" Text="Fail modale" OnClick="ModalClick" />
            <dw:Button id="__Button_Many" Text="Sette insieme (ne restano cinque)" OnClick="ManyClick" />
        </p>

        <p>
            <dw:Button id="__Button_Travel" Text="Salva e vai sulla pagina dello stato" OnClick="TravelClick" />
            <span class="ex-note">l'avviso compare <b>sulla pagina di arrivo</b>: la coda e' <code>#[Portable]</code>.</span>
        </p>

        <p class="ex-note">
            Col mouse sopra un riquadro resta; la x lo chiude; il modale si chiude con OK o Esc, non
            cliccando lo sfondo. Al massimo cinque insieme, i piu' vecchi se ne vanno per fare posto.
            Chi li disegna li consuma: al postback dopo non ricompaiono.
        </p>
    </div>

    <dw:UserControl id="__PropertyTable" src="Common/WebForms/Examples/PropertyTable" Type="Alert" />
    <dw:UserControl id="__SourceView" src="Common/WebForms/Examples/SourceView" />

</dw:Content>
