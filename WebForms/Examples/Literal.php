<?php require __DIR__ . '/../Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Common\WebForms\Examples\LiteralExample::class,
    'Common/WebForms/Examples/Site'); ?>

<dw:Content placeholder="content">

    <p>
        Testo e basta, senza un elemento attorno: serve dove uno <code>&lt;span&gt;</code> darebbe
        fastidio — dentro una cella gia' stilizzata, in mezzo a una frase. E' il controllo piu'
        usato in WK (983 volte) e il modo normale di scrivere un valore in pagina dal codebehind.
        Con <code>Mode="Encode"</code>, il predefinito, il testo esce escapato; con
        <code>PassThrough</code> esce com'e', ed e' per l'HTML che costruisce il <b>server</b>.
    </p>

    <div class="ex-demo">
        <p>
            <dw:TextBox id="__TextBox_Input" Placeholder="scrivi qualcosa, anche con <b>tag</b>" Text="Ciao <b>mondo</b> & C." />
            <dw:Button id="__Button_Show" Text="Mostra" OnClick="ShowClick" />
        </p>

        <p>Encode (predefinito): <b><dw:Literal id="__Literal_Encoded" /></b></p>
        <p>PassThrough, con HTML costruito dal server: <dw:Literal id="__Literal_Raw" Mode="PassThrough" /></p>

        <p class="ex-note">
            Quello che scrivi passa dal primo Literal e si vede com'e' scritto, tag compresi: e'
            escapato. Nel secondo il server ci mette un suo markup — il testo in un riquadro
            colorato — e il TUO testo lo escapa a mano prima di metterlo dentro: in
            <code>PassThrough</code> non ci va mai quello che arriva dal browser cosi' com'e'.
        </p>
    </div>

    <dw:UserControl id="__PropertyTable" src="Common/WebForms/Examples/PropertyTable" Type="Literal" />
    <dw:UserControl id="__SourceView" src="Common/WebForms/Examples/SourceView" />

</dw:Content>
