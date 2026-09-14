<?php http_response_code(404); exit; /* frammento di master: non e' una pagina */ ?>

<!--
    La cornice degli esempi: il menu a sinistra con tutti i controlli, il titolo, il contenuto.

    Sta in Common come le pagine che la usano, e non dipende da niente del sito: nessun foglio
    di stile esterno, nessun Model, nessun UserControl del sito. Il menu e' fatto di <a>
    normali - il runtime li intercetta e li trasforma in navigazione senza ricarico.
-->

<style>
    :root{--ex-nav:#0f172a;--ex-nav-testo:#cbd5e1;--ex-acc:#38bdf8;--ex-bordo:#e2e8f0;--ex-tenue:#64748b}
    body{margin:0;font:14px/1.6 system-ui,Segoe UI,sans-serif;color:#1f2937;background:#f8fafc}
    .ex-shell{display:flex;min-height:100vh}
    .ex-nav{width:250px;flex:0 0 250px;background:var(--ex-nav);color:var(--ex-nav-testo);padding:18px 0 32px;position:sticky;top:0;height:100vh;overflow:auto;box-sizing:border-box}
    .ex-brand{padding:0 20px 14px;border-bottom:1px solid #1e293b;margin-bottom:10px}
    .ex-brand a{color:#fff;text-decoration:none;font-weight:700;font-size:17px;letter-spacing:.02em}
    .ex-brand small{display:block;font-weight:400;font-size:12px;color:var(--ex-tenue)}
    .ex-group{padding:12px 20px 4px;font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:#64748b}
    .ex-nav a.ex-voce{display:block;padding:5px 20px;color:var(--ex-nav-testo);text-decoration:none;border-left:3px solid transparent}
    .ex-nav a.ex-voce:hover{color:#fff;background:#1e293b}
    .ex-nav a.ex-voce.ex-qui{color:#fff;border-left-color:var(--ex-acc);background:#1e293b}
    .ex-greetings{margin:16px 20px 0;font-size:12px;color:#94a3b8}
    .ex-greetings b{display:inline-block;min-width:20px;padding:1px 7px;border-radius:10px;background:var(--ex-acc);color:#0f172a;text-align:center}
    .ex-main{flex:1;min-width:0;padding:28px 40px 48px;max-width:960px;box-sizing:border-box}
    h1{font-size:24px;margin:0 0 6px}
    h2{font-size:16px;margin:28px 0 10px;padding-top:18px;border-top:1px solid var(--ex-bordo)}
    h3{font-size:14px;margin:18px 0 6px}
    .ex-lead{color:var(--ex-tenue);font-size:15px;margin:0 0 18px}
    .ex-demo{background:#fff;border:1px solid var(--ex-bordo);border-radius:8px;padding:18px 20px;margin:14px 0}
    .ex-demo p{margin:8px 0}
    .ex-note{color:var(--ex-tenue)}
    .ex-ok{color:#15803d;font-weight:600}
    .ex-ko{color:#b91c1c;font-weight:600}
    .ex-big{font-size:22px;font-weight:600}
    .ex-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px;margin:14px 0}
    .ex-card{display:block;background:#fff;border:1px solid var(--ex-bordo);border-radius:8px;padding:12px 14px;text-decoration:none;color:inherit}
    .ex-card:hover{border-color:var(--ex-acc)}
    .ex-card b{display:block;margin-bottom:2px}
    .ex-card span{color:var(--ex-tenue);font-size:13px}
    table{border-collapse:collapse;width:100%}
    th,td{text-align:left;padding:6px 8px;border-bottom:1px solid var(--ex-bordo);vertical-align:top}
    th{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:var(--ex-tenue)}
    td.ex-action{width:80px;text-align:right}
    tbody:empty + tfoot .ex-empty{display:table-cell}
    .ex-empty{display:none;color:#94a3b8;font-style:italic}
    code{background:#eef2f7;padding:1px 5px;border-radius:4px;font-size:13px}
    pre{margin:0;padding:14px 16px;background:#0f172a;color:#e2e8f0;border-radius:8px;overflow:auto;font:12.5px/1.5 ui-monospace,Consolas,monospace}
    pre code{background:none;padding:0;color:inherit;font-size:inherit}
    details{margin:10px 0}
    summary{cursor:pointer;font-weight:600;padding:6px 0}
    .ex-props td:first-child{white-space:nowrap;font-family:ui-monospace,Consolas,monospace;font-size:13px}
    .ex-props td:nth-child(2),.ex-props td:nth-child(3){white-space:nowrap;font-family:ui-monospace,Consolas,monospace;font-size:12px;color:var(--ex-tenue)}
    .ex-popup-header{margin:-20px -24px 12px;padding:10px 24px;background:var(--ex-nav);color:#e2e8f0;border-radius:10px 10px 0 0;cursor:move;user-select:none;font-weight:600}
    .ex-popup-footer{display:flex;justify-content:flex-end;gap:8px;margin:16px 0 0}
    .ex-footer{margin-top:40px;padding-top:12px;border-top:1px solid var(--ex-bordo);font-size:12px;color:var(--ex-tenue)}
    button,input[type=text],input[type=email],input[type=password],textarea,select{font:inherit;padding:5px 10px}
    a{color:#0369a1}
</style>

<div class="ex-shell">
    <aside class="ex-nav">
        <div class="ex-brand">
            <a href="Index.php">WebForms<small>gli esempi, un controllo per pagina</small></a>
        </div>

        <dw:Literal id="__Literal_Menu" Mode="PassThrough" />

        <dw:Panel id="__Panel_Greetings" CssClass="ex-greetings" Visible="false">
            saluti arrivati alla cornice: <b><dw:Literal id="__Literal_Greetings" Text="0" /></b>
        </dw:Panel>
    </aside>

    <main class="ex-main">
        <h1><dw:Literal id="__Literal_Title" /></h1>
        <p class="ex-lead"><dw:Literal id="__Literal_Lead" /></p>

        <dw:ContentPlaceHolder id="content">
            <p class="ex-note">Questa pagina non ha dichiarato nessun contenuto.</p>
        </dw:ContentPlaceHolder>

        <div class="ex-footer">
            Pagina resa dal server alle <b><dw:Literal id="__Literal_RenderedAt" /></b> — se cambia
            navigando, la navigazione ha davvero chiesto la pagina al server.
        </div>
    </main>
</div>

<dw:UpdateProgress id="__UpdateProgress" DisplayAfter="200">
    <div class="dw-attesa-scatola">Attendere ...</div>
</dw:UpdateProgress>

<dw:Alert id="__Alert" Duration="4000" />
