<?php require __DIR__ . '/../Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Common\WebForms\Examples\HiddenFieldExample::class,
    'Common/WebForms/Examples/Site'); ?>

<dw:Content placeholder="content">

    <p>
        Un valore che va nel form e torna, senza vedersi: dentro una riga di Repeater porta
        l'identificativo del record, come il <code>__Hidden_Id</code> delle pagine WK. Torna dal
        <b>browser</b>, quindi <b>non e' un'autorizzazione</b>: serve a ritrovare il record, il
        controllo di accesso resta nel Controller. Qui sotto lo si vede: cambiarlo dalla console
        e' facile, e il server lo sa.
    </p>

    <div class="ex-demo">
        <p>
            <dw:HiddenField id="__Hidden_Id" Value="42" />
            <dw:Button id="__Button_Read" Text="Leggi il valore nascosto" OnClick="ReadClick" />
            <dw:Button id="__Button_Next" Text="Il server lo cambia (+1)" OnClick="NextClick" />
        </p>

        <p class="ex-note"><dw:Literal id="__Literal_Read" /></p>

        <p class="ex-note">
            Prova dalla console: <code>document.getElementById('__Hidden_Id').value = '999'</code>,
            poi «leggi». Il server riceve 999. E' per questo che l'id serve a <i>trovare</i>, e i
            permessi si controllano sul record trovato — mai fidandosi del numero.
        </p>
    </div>

    <dw:UserControl id="__PropertyTable" src="Common/WebForms/Examples/PropertyTable" Type="HiddenField" />
    <dw:UserControl id="__SourceView" src="Common/WebForms/Examples/SourceView" />

</dw:Content>
