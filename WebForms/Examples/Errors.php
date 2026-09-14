<?php require __DIR__ . '/../Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Common\WebForms\Examples\ErrorsExample::class,
    'Common/WebForms/Examples/Site'); ?>

<dw:Content placeholder="content">

    <p>
        Quello che scappa da un handler — un'eccezione, un tipo sbagliato, la memoria finita —
        <code>Page::Run</code> lo scrive in <b><code>\Common\Log::Error</code></b>, il log del sito che si
        legge dal pannello, con pagina, metodo, URL, file:riga e pila. Poi lo rilancia com'era, e la
        risposta e' un <b>500</b>: il runtime la mostra nel riquadro rosso in fondo alla pagina invece
        di ricaricare in silenzio. Niente si perde: la pagina resta com'era, e si puo' continuare.
    </p>

    <div class="ex-demo">
        <p>
            <dw:Button id="__Button_Throw" Text="Lancia un'eccezione nell'handler" OnClick="ThrowClick" />
            <dw:Button id="__Button_Fine" Text="Un postback che va bene" OnClick="FineClick" />
            <span class="ex-note">postback riusciti: <b><dw:Literal id="__Literal_Ok" /></b></span>
        </p>

        <p class="ex-note">
            Dopo l'errore guarda il riquadro rosso in fondo, e il log del sito
            (<code>public/log/&lt;data&gt;.html</code>): la riga comincia con
            <code>WebForms Errors.php [POST …]: RuntimeException: …</code>. Poi premi il secondo
            bottone: il contatore continua da dov'era, perche' lo stato del postback fallito non e'
            stato toccato.
        </p>
    </div>

    <dw:UserControl id="__SourceView" src="Common/WebForms/Examples/SourceView" />

</dw:Content>
