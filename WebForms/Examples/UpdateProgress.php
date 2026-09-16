<?php require __DIR__ . '/../Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Common\WebForms\Examples\UpdateProgressExample::class,
    'Common/WebForms/Examples/Site'); ?>

<dw:Content placeholder="content">

    <p>
        «Attendere...» mentre il postback e' in viaggio. Si mette <b>una volta nella master</b> —
        qui in <code>Site.php</code> — e vale per tutte le pagine. Non ha handler e niente da
        accendere: il runtime mette <code>js-dw-attesa</code> sul <code>&lt;body&gt;</code> per tutta
        la durata della richiesta, e il controllo e' un pezzo di CSS che reagisce a quella classe.
        <code>DisplayAfter</code> e' il ritardo di un'animazione CSS, non un timer: se la risposta
        arriva prima non c'e' niente da annullare.
    </p>

    <div class="ex-demo">
        <p>
            <dw:Button id="__Button_Fast" Text="Postback veloce" OnClick="FastClick" />
            <dw:Button id="__Button_Slow" Text="Postback lento (1,2 s)" OnClick="SlowClick" />
            <dw:Button id="__Button_VerySlow" Text="Molto lento (3 s)" OnClick="VerySlowClick" />
            <span class="ex-note"><dw:Literal id="__Literal_Last" /></span>
        </p>

        <p class="ex-note">
            Il veloce non mostra niente: dura meno di <code>DisplayAfter</code>, e un lampo bianco a
            ogni click si vedrebbe peggio di non averlo. Gli altri due mostrano l'overlay dopo 200 ms
            e lo tolgono appena la risposta arriva — anche se la richiesta fallisce.
        </p>
    </div>

    <dw:UserControl id="__PropertyTable" src="Common/WebForms/Examples/PropertyTable" Type="UpdateProgress" />
    <dw:UserControl id="__SourceView" src="Common/WebForms/Examples/SourceView" />

</dw:Content>
