<?php require __DIR__ . '/../Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Common\WebForms\Examples\ButtonExample::class,
    'Common/WebForms/Examples/Site'); ?>

<dw:Content placeholder="content">

    <p>
        Il click che fa postback: <code>Button</code> rende un <code>&lt;button&gt;</code>,
        <code>LinkButton</code> un <code>&lt;a&gt;</code> — e se disabilitato degrada a
        <code>&lt;span&gt;</code>, non a link morto. L'handler lo nomina il markup in
        <code>OnClick</code>, e riceve chi ha cliccato e il <code>CommandArgument</code>:
        tre bottoni, un handler solo.
    </p>

    <div class="ex-demo">
        <p>
            <dw:Button id="__Button_Red" Text="Rosso" OnClick="ColorClick" CommandArgument="#b91c1c" />
            <dw:Button id="__Button_Green" Text="Verde" OnClick="ColorClick" CommandArgument="#15803d" />
            <dw:LinkButton id="__LinkButton_Blue" Text="blu, ed e' un LinkButton" OnClick="ColorClick" CommandArgument="#1d4ed8" />
            &nbsp; scelto: <dw:Label id="__Label_Chosen" Text="niente" />
        </p>

        <p>
            <dw:Button id="__Button_Reset" Text="Azzera tutto" OnClick="ResetClick" Confirm="Azzerare davvero? E' una domanda del browser, non una difesa." />
            <dw:Button id="__Button_Slow" Text="Un postback lento (1,5 s)" OnClick="SlowClick" />
            <dw:LinkButton id="__LinkButton_Disabled" Text="disabilitato: sono uno span" Enabled="false" />
        </p>

        <p class="ex-note">
            Il bottone lento: mentre la risposta viaggia <b>lui</b> resta spento — <code>disabled</code>,
            classe <code>js-dw-occupato</code> — e il secondo click non parte. Il resto della pagina
            risponde. Dopo 200 ms compare l'«Attendere» della cornice. Click fatti:
            <b><dw:Literal id="__Literal_Clicks" /></b>.
        </p>
    </div>

    <dw:UserControl id="__PropertyTable" src="Common/WebForms/Examples/PropertyTable" Type="Button,LinkButton" />
    <dw:UserControl id="__SourceView" src="Common/WebForms/Examples/SourceView" />

</dw:Content>
