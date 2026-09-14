<?php require __DIR__ . '/../Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Common\WebForms\Examples\LabelExample::class,
    'Common/WebForms/Examples/Site'); ?>

<dw:Content placeholder="content">

    <p>
        Testo in uno <code>&lt;span&gt;</code>: come il Literal, ma con un elemento attorno su cui
        appoggiare <code>CssClass</code>, <code>Style</code> e <code>Attributes</code>. E' il
        controllo giusto quando il testo va vestito dal codice — un colore che dipende dal valore,
        un <code>title</code> che spiega.
    </p>

    <div class="ex-demo">
        <p>
            Temperatura: <dw:Label id="__Label_Temperature" Text="—" />
            <dw:Button id="__Button_Colder" Text="− 5°" OnClick="ColderClick" />
            <dw:Button id="__Button_Warmer" Text="+ 5°" OnClick="WarmerClick" />
        </p>

        <p class="ex-note">
            Il colore lo mette il codebehind con <code>Style->Add('color', ...)</code> secondo il
            valore, e il <code>title</code> con <code>Attributes->Add()</code>: passa il mouse sopra.
            Tutto questo vive nel ViewState come il testo, quindi resta finche' un handler non lo
            cambia.
        </p>
    </div>

    <dw:UserControl id="__PropertyTable" src="Common/WebForms/Examples/PropertyTable" Type="Label" />
    <dw:UserControl id="__SourceView" src="Common/WebForms/Examples/SourceView" />

</dw:Content>
