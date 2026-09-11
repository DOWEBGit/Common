<?php require __DIR__ . '/../Bootstrap.php';
\Common\WebForms\Page::Run(__FILE__, \Common\WebForms\ProveAMano\Tabella::class); ?>

<style>
    body{margin:0;padding:24px;font:14px/1.6 system-ui,Segoe UI,sans-serif;color:#1f2937;background:#f8fafc}
    h1{font-size:20px;margin:0 0 4px}
    .pm-card{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin:16px 0;max-width:760px}
    .pm-tenue{color:#64748b}
    table{border-collapse:collapse;width:100%;margin-top:12px}
    th,td{text-align:left;padding:6px 8px;border-bottom:1px solid #e2e8f0}
    th{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#64748b}
    td.pm-azione{width:80px;text-align:right}
    a{color:#b91c1c;cursor:pointer}
    button{font:inherit;padding:5px 10px}
    tbody:empty + tfoot .pm-vuoto{display:table-cell}
    .pm-vuoto{display:none;color:#94a3b8;font-style:italic}
</style>

<h1>Prove a mano: righe aggiunte a mano</h1>

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
        <a href="/public/php/Common/WebForms/ProveAMano/Stato.php">vai all'altro banco</a>
        &nbsp;·&nbsp;
        <a href="/public/php/Common/WebForms/ProveAMano/Tabella.php">torna qui</a>
        &nbsp;·&nbsp;
        <span>e poi il tasto indietro del browser</span>
    </p>

    <table>
        <thead>
        <tr><th>Riga</th><th class="pm-azione"></th></tr>
        </thead>

        <dw:Panel id="corpo" Tag="tbody" />

        <tfoot>
        <tr><td colspan="2" class="pm-vuoto">Nessuna riga: premi «aggiungi una riga».</td></tr>
        </tfoot>
    </table>
</div>
