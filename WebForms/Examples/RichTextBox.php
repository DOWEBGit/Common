<?php require __DIR__ . '/../Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Common\WebForms\Examples\RichTextBoxExample::class,
    'Common/WebForms/Examples/Site'); ?>

<dw:Content placeholder="content">

    <p>
        Un editor alla maniera di Word: grassetto, colori, interlinea, titoli, elenchi, link,
        tabelle. <b>Ogni funzione ha la sua proprieta'</b> <code>Enable…</code>: spenta, sparisce il
        bottone <b>e</b> il server smette di farla passare — una tabella incollata in un campo senza
        tabelle non entra. Quello che arriva dal browser e' HTML, e l'HTML di un utente e' la porta
        dell'XSS: il server lo ricostruisce da zero tenendo solo quello che sta nell'elenco.
    </p>

    <style>.ex-nota{padding:10px 0;border-bottom:1px solid var(--ex-bordo)} .ex-db{font:12px/1.5 ui-monospace,Consolas,monospace;white-space:pre-wrap;background:#f8fafc;border:1px solid var(--ex-bordo);border-radius:6px;padding:8px 10px}</style>

    <h2>Tutte le funzioni</h2>

    <div class="ex-demo">
        <dw:RichTextBox id="__RichTextBox_Full" MinHeight="220" MaxHeight="520" ShowCounter="true" EnableSourceView="true"
                        Placeholder="Scrivi, incolla da Word, prova Ctrl+B, Ctrl+K, Tab in un elenco..." />

        <p>
            <dw:Button id="__Button_Show" Text="Mostra l'HTML salvato" OnClick="ShowClick" />
            <dw:Button id="__Button_Sample" Text="Testo di prova, dal server" OnClick="SampleClick" />
            <dw:Button id="__Button_Dirty" Text="Prova a salvare HTML cattivo" OnClick="DirtyClick" />
        </p>

        <dw:Literal id="__Literal_Html" />

        <p class="ex-note">
            Sul telefono la barra scorre di lato, i bottoni sono da dito e i menu salgono dal basso;
            «schermo intero» allarga l'editor a tutto il telefono. Annulla e Ripeti sono dell'editor,
            non del browser: tornano indietro anche su interlinea, rientri e tabelle.
        </p>
    </div>

    <h2>Le funzioni, una per una</h2>

    <div class="ex-demo">
        <p class="ex-note">Scegli quali accendere: e' <code>EnableOnly(...)</code> dal codice. Prova poi a incollare una tabella
            o un testo colorato: entra solo quello che e' acceso.</p>

        <p>
            <dw:ListBox id="__ListBox_Features" SelectionMode="Multiple" Rows="8" />
            <dw:Button id="__Button_Apply" Text="Applica" OnClick="ApplyClick" />
            <dw:Button id="__Button_All" Text="Tutte" OnClick="AllClick" />
        </p>

        <dw:RichTextBox id="__RichTextBox_Partial" MinHeight="120" EnableTable="false" EnableFontName="false" EnableFontSize="false"
                        EnableLineHeight="false" EnableBackColor="false" MaxLength="300" Placeholder="Al massimo 300 caratteri" />
    </div>

    <h2>Un editor per riga: Salva, Aggiungi, Salva tutto</h2>

    <div class="ex-demo">
        <dw:Repeater id="__Repeater_Notes" Tag="div" ItemTag="div" DataKeyField="Id" OnItemDataBound="NoteBound">
            <ItemTemplate>
                <div class="ex-nota">
                    <dw:HiddenField id="__Hidden_Id" Value="{{Id}}" />
                    <p><b>Nota {{Id}}</b> <dw:Literal id="__Literal_Saved" /></p>
                    <dw:RichTextBox id="__RichTextBox_Note" MinHeight="90" EnableTable="false" EnableSourceView="false"
                                    EnableFullScreen="false" EnableFontName="false" Placeholder="Scrivi la nota..." />
                    <p>
                        <dw:Button id="__Button_Save" Text="Salva questa" OnClick="SaveNoteClick" />
                        <dw:Button id="__Button_Remove" Text="Togli" OnClick="RemoveNoteClick" Confirm="Togliere la nota?" />
                    </p>
                </div>
            </ItemTemplate>
        </dw:Repeater>

        <p>
            <dw:Button id="__Button_Add" Text="Aggiungi una nota" OnClick="AddNoteClick" />
            <dw:Button id="__Button_SaveAll" Text="Salva tutto" OnClick="SaveAllClick" />
        </p>

        <p class="ex-note">Nel "database" (una variabile della pagina):</p>
        <dw:Literal id="__Literal_Database" />

        <p class="ex-note">
            Ogni riga ha il suo editor, il suo campo e il suo Salva: il Salva di una riga salva quella e non
            tocca le altre, anche se ci hai scritto. «Aggiungi» rilega le righe e rimette in ognuna il testo
            che stavi scrivendo; «Salva tutto» passa da tutte. Lo script dell'editor si carica una volta sola,
            anche con dieci editor e anche quando la riga compare con un postback.
        </p>
    </div>

    <h2>Anteprima mentre si scrive</h2>

    <div class="ex-demo">
        <dw:RichTextBox id="__RichTextBox_Live" MinHeight="90" AutoPostBack="true" AutoPostBackDelay="600" OnTextChanged="LiveChanged"
                        Placeholder="AutoPostBackDelay=600: fermati e il server risponde" />
        <p class="ex-note">Dal server, come testo: <dw:Literal id="__Literal_Live" /></p>
    </div>

    <dw:UserControl id="__PropertyTable" src="Common/WebForms/Examples/PropertyTable" Type="RichTextBox" />
    <dw:UserControl id="__SourceView" src="Common/WebForms/Examples/SourceView" />

</dw:Content>
