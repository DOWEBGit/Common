<?php http_response_code(404); exit; /* frammento di master: non e' una pagina */ ?>

<!--
    La cornice dei banchi di prova: menu, titolo, contenuto, piede.

    Sta in Common come le pagine che la usano, e non dipende da niente del sito: nessun foglio
    di stile esterno, nessun Model, nessun UserControl. Il menu e' fatto di <a> normali - il
    runtime li intercetta da solo e li trasforma in navigazione senza ricarico, senza che
    debbano saperlo.
-->

<style>
    body{margin:0;padding:0;font:14px/1.6 system-ui,Segoe UI,sans-serif;color:#1f2937;background:#f8fafc}
    .pm-testata{display:flex;align-items:center;gap:24px;padding:12px 24px;background:#0f172a;color:#e2e8f0}
    .pm-marchio{font-weight:600;letter-spacing:.02em}
    .pm-menu{display:flex;gap:16px}
    .pm-menu a{color:#94a3b8;text-decoration:none;padding:4px 0;border-bottom:2px solid transparent}
    .pm-menu a:hover{color:#e2e8f0}
    .pm-menu a.pm-qui{color:#fff;border-bottom-color:#38bdf8}
    .pm-corpo{padding:24px;max-width:820px}
    h1{font-size:20px;margin:0 0 4px}
    h2{font-size:15px;margin:0 0 10px}
    .pm-card{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin:16px 0}
    .pm-tenue{color:#64748b}
    .pm-si{color:#15803d;font-weight:600}
    .pm-no{color:#b91c1c;font-weight:600}
    .pm-grande{font-size:22px;font-weight:600}
    .pm-pie{padding:12px 24px;border-top:1px solid #e2e8f0;font-size:12px}
    table{border-collapse:collapse;width:100%}
    th,td{text-align:left;padding:6px 8px;border-bottom:1px solid #e2e8f0}
    button,input[type=text]{font:inherit;padding:5px 10px}
    a{color:#0369a1}
</style>

<header class="pm-testata">
    <div class="pm-marchio">Prove a mano</div>

    <nav class="pm-menu">
        <dw:Literal id="litMenu" Mode="PassThrough" />
    </nav>
</header>

<div class="pm-corpo">
    <h1><dw:Literal id="litTitolo" /></h1>
    <p class="pm-tenue"><dw:Literal id="litSottotitolo" /></p>

    <dw:ContentPlaceHolder id="corpo">
        <p class="pm-tenue">Questa pagina non ha dichiarato nessun contenuto.</p>
    </dw:ContentPlaceHolder>
</div>

<footer class="pm-pie pm-tenue">
    Questa pagina e' stata resa dal server alle <b><dw:Literal id="litOra" /></b> —
    se cambia navigando, la navigazione ha davvero chiesto la pagina al server.
</footer>

<dw:UpdateProgress id="prgAttesa" DisplayAfter="200">
    <div class="dw-attesa-scatola">Attendere ...</div>
</dw:UpdateProgress>

<dw:Alert id="avvisi" Duration="4000" />
