<?php require __DIR__ . '/../Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Common\WebForms\ProveAMano\Tabella::class,
    'Common/WebForms/ProveAMano/Cornice'); ?>

<dw:Content placeholder="corpo">

<p class="pm-tenue" style="max-width:760px">
    <b>In <code>OnLoad</code> non succede niente.</b> Ogni riga della tabella nasce da un click:
    il codice costruisce un <code>&lt;tr&gt;</code> con dentro due celle — un
    <code>Literal</code> e un <code>LinkButton</code> «elimina» — e lo attacca al
    <code>&lt;tbody&gt;</code>. Nessuno le ricostruisce ad ogni richiesta: se dopo un postback
    sono ancora li', ce le ha rimesse lo stato.
</p>

<div class="pm-card">
    <p>
        <dw:Button id="btnAggiungi" Text="Aggiungi una riga" OnClick="AggiungiClick" />
        <span class="pm-tenue">righe: <b><dw:Literal id="litQuante" /></b> &nbsp;·&nbsp;
        postback: <b><dw:Literal id="litClick" /></b></span>
        <dw:Button id="btnNiente" Text="Postback che non fa niente" OnClick="NienteClick" />
    </p>

    <p class="pm-tenue" style="border-top:1px solid #e2e8f0;padding-top:12px">
        <dw:CheckBox id="chkTieni" Text="Tieni questa pagina come una form di WinForms" Checked="true" AutoPostBack="true" />
        <br>
        Acceso — com'e' per tutte le pagine — se vai altrove e torni le righe sono ancora li':
        il browser si conserva lo stato di QUESTA pagina e glielo rimanda. Spento, com'e' il
        web, torni e trovi la tabella vuota: lo stato vale per quella visita. Prova con i due
        link qui sotto, prima acceso e poi spento.
        <br>
        <a href="Stato.php">vai all'altro banco</a>
        &nbsp;·&nbsp;
        <a href="Tabella.php">torna qui</a>
        &nbsp;·&nbsp;
        <span>e poi il tasto indietro del browser</span>
    </p>

    <table>
        <thead>
        <tr><th>Riga</th><th class="pm-azione"></th></tr>
        </thead>

        <dw:Panel id="tbRighe" Tag="tbody" />

        <tfoot>
        <tr><td colspan="2" class="pm-vuoto">Nessuna riga: premi «aggiungi una riga».</td></tr>
        </tfoot>
    </table>
</div>

</dw:Content>
