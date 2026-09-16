<?php require __DIR__ . '/../Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Common\WebForms\Examples\FileUploadExample::class,
    'Common/WebForms/Examples/Site'); ?>

<dw:Content placeholder="content">

    <p>
        Il file <b>non passa dal postback</b>: va per conto suo su <code>FileUploadHandler.php</code>,
        che lo mette da parte in una cartella temporanea fuori dal sito e restituisce un token
        firmato; nel form entra solo il token. Il codebehind vede <code>HasFile()</code>,
        <code>FileName()</code>, <code>Size()</code>, <code>Bytes()</code>, e butta il temporaneo con
        <code>Clear()</code>. Il tipo si legge dal contenuto, non dal nome ne' dal browser.
    </p>

    <div class="ex-demo">
        <h3>Solo l'input, come in WebForms</h3>

        <p>
            <dw:FileUpload id="__FileUpload_Simple" Accept="image/*,.pdf,.txt" OnFileUploaded="Uploaded" />
        </p>

        <h3>Con l'area di trascinamento</h3>

        <dw:FileUpload id="__FileUpload_Drop" AllowDrop="true" Accept="image/*,.pdf,.txt" OnFileUploaded="Uploaded"
                       Text="Trascina qui un'immagine o un pdf, o clicca per sceglierlo" />

        <p>
            <dw:Button id="__Button_Save" Text="Salva (consuma i file)" OnClick="SaveClick" />
            <dw:Button id="__Button_Discard" Text="Butta i temporanei" OnClick="DiscardClick" />
        </p>

        <p class="ex-note"><dw:Literal id="__Literal_Status" Mode="PassThrough" /></p>

        <p class="ex-note">
            Senza <code>Constraints</code> il canale accetta quello che sa riconoscere — jpg, png, gif,
            pdf, zip, docx, xlsx, doc, xls, txt, csv — fino a 8 MB. Un <code>.txt</code> rinominato
            <code>.png</code> viene rifiutato con il perche' in <code>Error</code>; un'immagine si guarda
            in anteprima, un documento si scarica. In un sito vero <code>Constraints="Model\Prodotti::Immagine"</code>
            prende le regole del campo.
        </p>
    </div>

    <dw:UserControl id="__PropertyTable" src="Common/WebForms/Examples/PropertyTable" Type="FileUpload" />
    <dw:UserControl id="__SourceView" src="Common/WebForms/Examples/SourceView" />

</dw:Content>
