<?php require __DIR__ . '/../Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Common\WebForms\Examples\ResourcesExample::class,
    'Common/WebForms/Examples/Site'); ?>

<dw:Content placeholder="content">

    <dw:Stylesheet src="Common/WebForms/Examples/Resources.css" />
    <dw:Script src="Common/WebForms/Examples/Resources.js" />

    <p>
        Un foglio di stile e uno script <b>del sito</b>, agganciati con una riga di markup. Il
        <code>src</code> e' relativo alla radice dei sorgenti php, e il tag esce con
        <code>?v=&lt;marca temporale del file&gt;</code>: sotto <code>Public/Php</code> gli statici hanno
        una cache di sessanta giorni, e senza quella marca una modifica la vedrebbe solo chi non e'
        mai passato di li'. Un file che non c'e' <b>ferma la pagina</b> dicendo quale manca.
    </p>

    <div class="ex-demo">
        <p>
            <span class="ex-resources-badge">Questo riquadro e' vestito da <code>Resources.css</code></span>
        </p>

        <p>
            <span class="ex-note">Lo script dice: </span>
            <b id="ex-resources-script" data-dw-client="1">non ancora partito</b>
            <dw:Button id="__Button_Postback" Text="Fai un postback" OnClick="PostbackClick" />
            <span class="ex-note">postback: <b><dw:Literal id="__Literal_Count" /></b></span>
        </p>

        <p class="ex-note">
            Uno <code>&lt;dw:Script&gt;</code> con <code>src</code> gira <b>una volta sola</b>, al caricamento
            vero: qui le pagine non si ricaricano, e il runtime riesegue solo gli script inline dopo
            una navigazione. Quindi ci va codice che si aggancia al documento — qui
            <code>document.addEventListener('dw:pagina', ...)</code> — non codice che cerca i suoi
            elementi all'avvio e se li tiene. Il testo in grassetto ha <code>data-dw-client</code>: e'
            del client, e il morph non lo tocca nemmeno dopo il postback.
        </p>
    </div>

    <dw:UserControl id="__PropertyTable" src="Common/WebForms/Examples/PropertyTable" Type="Stylesheet,Script" />
    <dw:UserControl id="__SourceView" src="Common/WebForms/Examples/SourceView" />

</dw:Content>
