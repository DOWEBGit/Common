<?php require __DIR__ . '/../Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Common\WebForms\Examples\IndexExample::class,
    'Common/WebForms/Examples/Site'); ?>

<dw:Content placeholder="content">

    <p>
        Un motore a postback per PHP sul modello di ASP.NET WebForms: albero di controlli sul
        server, eventi nel codebehind, e il browser che fonde le differenze invece di
        ricaricare. Queste pagine sono la prova con le mani di quello che il <code>LEGGIMI</code>
        racconta e le prove in <code>UnitTest/</code> dimostrano: una pagina per controllo, con la
        cosa da toccare, la tabella delle proprieta' letta dalla classe e il sorgente che l'ha
        resa.
    </p>

    <p class="ex-note">
        Stanno in <code>Common/WebForms/Examples</code>, dentro il motore: niente database, niente
        Model, nessun foglio di stile del sito. Si aprono uguali su un sito appena creato. La
        cornice attorno e' <code>Site.php</code>, una master page che vive fuori dal sito.
    </p>

    <h2>Come si legge una pagina</h2>

    <p>
        In alto <b>la prova</b>: si clicca, si scrive, si guarda cosa resta. Poi <b>le proprieta'</b>,
        con il tipo, il valore predefinito e la descrizione — che e' il commento scritto nel
        sorgente del controllo, non una tabella tenuta a mano. In fondo <b>il sorgente</b> della
        pagina stessa, markup e codebehind, letto dal disco: quello che si legge e' esattamente
        quello che ha reso la pagina.
    </p>

    <p>
        Gli id seguono la convenzione di WK — <code>__Literal_Nome</code>, <code>__Button_Save</code> —
        e i nomi dopo il prefisso sono in inglese, come le variabili e i nomi dei file; i testi e
        i commenti sono in italiano.
    </p>

    <dw:Literal id="__Literal_Cards" Mode="PassThrough" />

</dw:Content>
