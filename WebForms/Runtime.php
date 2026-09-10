<?php
declare(strict_types=1);

namespace Common\WebForms;

/**
 * Il pezzo che gira nel browser: intercettazione degli eventi, postback, morph del DOM,
 * navigazione senza ricarico, notifiche push.
 *
 * Tutto passa da delega sul document: nessun listener viene attaccato ai singoli controlli,
 * quindi non c'e' niente da riagganciare dopo un aggiornamento e non si accumula nulla
 * navigando. Il ciclo init/leave resta esposto per il codice di pagina, che invece deve
 * pulire quello che apre.
 */
class Runtime
{
    /**
     * Il client SignalR per il browser, servito da noi.
     *
     * Aggiornarlo vuol dire mettere la versione nuova accanto a questa - su
     * Z:\Rete\_Programmi\static.doweb.site\LiveServer\signalr\<versione>\ - e cambiare
     * questa riga. La cartella vecchia resta finche' c'e' un sito che la chiede.
     */
    private const SIGNALR = 'https://static.doweb.site/LiveServer/signalr/8.0.29/signalr.min.js';

    /**
     * Se il nostro static non risponde si ripiega sul CDN pubblico.
     *
     * Non e' fiducia in jsdelivr: e' che senza libreria le notifiche spariscono in silenzio, e
     * una griglia che non si aggiorna piu' non ha nessun sintomo da cui risalire. Il ripiego
     * si toglie il giorno che il nostro static e' l'unica strada che serve.
     */
    private const SIGNALR_SCORTA = 'https://cdn.jsdelivr.net/npm/@microsoft/signalr@8/dist/browser/signalr.min.js';

    public static function Styles(): string
    {
        return '<style>' . self::CSS . '</style>';
    }

    public static function Scripts(): string
    {
        return '<script>window.DW_CSRF=' . json_encode(Csrf::Token()) . ';'
            . 'window.DW_SIGNALR_SCORTA=' . json_encode(self::SIGNALR_SCORTA) . ';</script>'
            . '<script>' . self::JS . '</script>'
            //defer e non async: la libreria deve esistere quando parte il collegamento, ma
            //nessuna pagina deve aspettarla per essere usabile - se non arriva, restano i
            //postback e si perdono solo le notifiche
            //
            //Sta sul NOSTRO static e non su un CDN pubblico: un sito che per funzionare
            //dipende da un dominio di qualcun altro smette di funzionare quando quel dominio
            //ha una brutta giornata, e intanto racconta a lui chi visita le nostre pagine.
            //L'indirizzo porta il numero di versione, quindi non cambia mai contenuto e la
            //cache lunga degli statici non e' un problema: la versione nuova e' un indirizzo
            //nuovo.
            . '<script defer src="' . self::SIGNALR . '"'
            . ' onload="DW.collegaNotifiche()" onerror="DW.signalrDiScorta()"></script>';
    }

    /**
     * IL MINIMO SENZA CUI QUALCOSA NON FUNZIONA, e nient'altro.
     *
     * Non e' il vestito del sito: quello sta nel markup della master page, dove si cambia
     * come si cambia un foglio di stile qualunque. Qui restano solo le regole che tengono in
     * piedi un comportamento - l'attesa che compare dopo un ritardo, il controllo spento
     * mentre il postback viaggia, il campo file invisibile steso sopra la zona di
     * trascinamento, il banner degli errori. Toglierne una non rende una pagina brutta: la
     * rompe.
     *
     * I colori passano da var(--dw-...) con un ripiego scritto accanto: se il sito dichiara
     * la sua tavolozza vince quella, se non la dichiara si vede lo stesso qualcosa.
     */
    private const CSS = <<<'CSS'
[hidden]{display:none!important}
/* il controllo che ha cliccato resta spento finche' non torna la risposta: senza
   pointer-events il secondo click su un LinkButton partirebbe lo stesso */
.js-dw-occupato{opacity:.55;cursor:progress}
a.js-dw-occupato{pointer-events:none;text-decoration:none}
/* l'attesa: nascosta a riposo, e il ritardo lo fa l'animazione, non un timer - se la
   risposta arriva prima non c'e' niente da annullare, e un postback di 40ms non lascia
   un lampo bianco sullo schermo */
.dw-attesa{position:fixed;inset:0;z-index:9998;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.55);visibility:hidden;opacity:0}
.dw-attesa-scatola{background:var(--dw-fondo,#fff);border:1px solid var(--dw-bordo,#d8dbe0);border-radius:8px;padding:14px 20px;box-shadow:0 6px 24px rgba(0,0,0,.12)}
body.js-dw-attesa .dw-attesa{animation:dw-attesa-entra .12s linear var(--dw-attesa-dopo,200ms) forwards}
/* la durata NON e' zero di proposito: con 0s l'animazione non ha una fase attiva e il
   riempimento forwards da solo non basta a farla comparire - provato */
@keyframes dw-attesa-entra{from{visibility:visible;opacity:0}to{visibility:visible;opacity:1}}
/* la zona di trascinamento: il campo file e' steso sopra e invisibile, ed e' lui a
   ricevere il click. Il testo non deve rubarglielo, da qui pointer-events:none */
.dw-drop{position:relative;display:block;border:2px dashed var(--dw-bordo,#d8dbe0);border-radius:8px;padding:16px;text-align:center;background:#fbfcfd;transition:border-color .15s,background .15s}
.dw-drop input[type=file]{position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer}
.dw-drop-testo{color:var(--dw-tenue,#6b7280);font-size:13px;pointer-events:none}
.dw-drop.js-dw-sopra{border-color:var(--dw-acc,#1f6feb);background:#eef5ff}
.dw-drop.js-dw-caricando{opacity:.6}
/* GLI AVVISI. In alto a destra, uno sotto l'altro, il piu' recente in basso: si legge
   dove si e' guardato l'ultima volta, e i vecchi salgono invece di spostarsi sotto il
   dito. pointer-events sul contenitore e non sui riquadri, cosi' la colonna vuota non
   ruba i click alla pagina sotto. */
.dw-avvisi{position:fixed;top:20px;right:20px;z-index:9997;display:flex;flex-direction:column;gap:10px;pointer-events:none;max-width:min(400px,calc(100vw - 40px))}
.dw-avviso{pointer-events:auto;display:flex;align-items:flex-start;gap:10px;min-width:280px;padding:12px 14px;border-radius:8px;border:1px solid var(--dw-bordo,#d8dbe0);border-left:4px solid var(--dw-tenue,#6b7280);background:var(--dw-fondo,#fff);box-shadow:0 6px 20px rgba(0,0,0,.14);animation:dw-avviso-entra .28s cubic-bezier(.4,0,.2,1)}
.dw-avviso-successo{border-left-color:#1a7f37}
.dw-avviso-fallito{border-left-color:#b42318}
.dw-avviso-icona{flex:0 0 auto;font-size:16px;line-height:20px}
.dw-avviso-successo .dw-avviso-icona{color:#1a7f37}
.dw-avviso-fallito .dw-avviso-icona{color:#b42318}
.dw-avviso-testo{flex:1;min-width:0;overflow-wrap:anywhere}
.dw-avviso-chiudi{flex:0 0 auto;width:22px;height:22px;padding:0;border:0;border-radius:50%;background:transparent;color:var(--dw-tenue,#6b7280);font:18px/1 system-ui,sans-serif;cursor:pointer}
.dw-avviso-chiudi:hover{background:rgba(0,0,0,.06)}
/* la dissolvenza la fa una classe e non un timer nel JavaScript: cosi' la si cambia da un
   foglio di stile, e chi la vuole diversa non deve toccare il motore */
.dw-avviso.js-dw-esce{animation:dw-avviso-esce .35s ease forwards}
@keyframes dw-avviso-entra{from{transform:translateX(110%);opacity:0}to{transform:translateX(0);opacity:1}}
@keyframes dw-avviso-esce{to{transform:translateX(110%);opacity:0;margin-bottom:-46px}}
/* il modale: oscura e pretende un OK. Non e' un avviso piu' importante, e' un avviso che
   ferma quello che si stava facendo */
.dw-modale{position:fixed;inset:0;z-index:9998;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(17,20,24,.45);animation:dw-modale-entra .18s ease}
.dw-modale-scatola{max-width:520px;width:100%;background:var(--dw-fondo,#fff);border-radius:10px;box-shadow:0 20px 60px rgba(0,0,0,.3);padding:22px 24px 16px;animation:dw-modale-sale .22s cubic-bezier(.4,0,.2,1)}
.dw-modale-riga{display:flex;align-items:flex-start;gap:10px;margin:0 0 10px}
.dw-modale-piede{display:flex;justify-content:flex-end;margin-top:16px}
.dw-modale-ok{min-width:96px;padding:8px 16px;border:1px solid var(--dw-bordo,#d8dbe0);border-radius:6px;background:var(--dw-acc,#1f6feb);color:#fff;font:inherit;cursor:pointer}
.dw-modale-ok:hover{filter:brightness(1.08)}
@keyframes dw-modale-entra{from{opacity:0}to{opacity:1}}
@keyframes dw-modale-sale{from{transform:translateY(-14px);opacity:0}to{transform:translateY(0);opacity:1}}
/* gli errori JavaScript si vedono, sempre: una pagina che smette di rispondere in
   silenzio si debugga a naso */
#dw-errore{position:fixed;left:0;right:0;bottom:0;max-height:45vh;overflow:auto;background:#7f1d1d;color:#fff;padding:14px 18px;font:12px/1.5 ui-monospace,Consolas,monospace;white-space:pre-wrap;z-index:9999}
CSS;

    private const JS = <<<'JS'
(() => {
'use strict';

const DW = window.DW = {};

// ---------------------------------------------------------------- errori
// Un errore JavaScript che non si vede costa piu' di uno che urla: qui diventa sempre
// visibile in pagina, oltre che in console.

DW.errore = (messaggio) => {
    console.error(messaggio);

    let box = document.getElementById('dw-errore');

    if (!box) {
        box = document.createElement('div');
        box.id = 'dw-errore';
        document.body.appendChild(box);
    }

    box.textContent += messaggio + '\n';
};

addEventListener('error', e => DW.errore('JS: ' + (e.message || e.error) + '  @' + e.filename + ':' + e.lineno));
addEventListener('unhandledrejection', e => DW.errore('Promise non gestita: ' + e.reason));

// ---------------------------------------------------------------- ciclo di vita
// I listener del framework sono delegati sul document, quindi non serve riagganciarli.
// Questo resta per il codice di pagina: quello che apre (timer, editor, osservatori) deve
// chiudersi alla navigazione, o dopo venti pagine se ne trascinano venti copie vive.

let uscite = [];

DW.onLeave = fn => uscite.push(fn);

DW.teardown = () => {
    for (const fn of uscite) {
        try { fn(); } catch (e) { DW.errore('teardown: ' + e); }
    }
    uscite = [];
};

// ---------------------------------------------------------------- morph
// Aggiorna il DOM esistente invece di sostituirlo: i nodi che non cambiano restano gli
// stessi oggetti, quindi sopravvivono focus, selezione, scroll e listener.
// Se in pagina c'e' idiomorph si usa quello: fa un match migliore sugli inserimenti in
// mezzo a una lista. Questa e' la versione di scorta, autosufficiente.

DW.morph = (vecchio, nuovoHtml) => {
    const tmp = document.createElement('div');
    tmp.innerHTML = nuovoHtml.trim();

    const nuovo = tmp.firstElementChild;
    if (!nuovo) return;

    if (window.Idiomorph) {
        window.Idiomorph.morph(vecchio, nuovo, { morphStyle: 'outerHTML' });
        return;
    }

    morphNodo(vecchio, nuovo);
};

function morphNodo(o, n) {
    if (o.nodeType !== n.nodeType || o.nodeName !== n.nodeName) {
        o.replaceWith(n.cloneNode(true));
        return;
    }

    if (o.nodeType === Node.TEXT_NODE || o.nodeType === Node.COMMENT_NODE) {
        if (o.nodeValue !== n.nodeValue) o.nodeValue = n.nodeValue;
        return;
    }

    if (o.nodeType !== Node.ELEMENT_NODE) return;

    // sottoalbero di competenza del client: editor, datepicker, tutto cio' che il server
    // non ha renderizzato e non deve smontare
    if (o.hasAttribute('dw-preserve')) return;

    attributi(o, n);
    figli(o, n);
    valore(o, n);
}

function attributi(o, n) {
    for (const a of Array.from(o.attributes))
        if (a.name !== 'class' && !n.hasAttribute(a.name))
            o.removeAttribute(a.name);

    for (const a of Array.from(n.attributes))
        if (a.name !== 'class' && o.getAttribute(a.name) !== a.value)
            o.setAttribute(a.name, a.value);

    // Le classi hanno due padroni: il server le sue, il client quelle con prefisso js-.
    // Senza questa distinzione una classe messa da JavaScript sparisce ad ogni postback.
    const mie = Array.from(o.classList).filter(c => c.startsWith('js-'));
    const sue = (n.getAttribute('class') || '').split(/\s+/).filter(Boolean);
    const tutte = sue.concat(mie).join(' ');

    if (tutte) o.setAttribute('class', tutte);
    else o.removeAttribute('class');
}

function valore(o, n) {
    if (!(o instanceof HTMLInputElement || o instanceof HTMLTextAreaElement || o instanceof HTMLSelectElement))
        return;

    // Non si calpesta il campo su cui l'utente sta scrivendo: il server ha renderizzato il
    // valore vecchio, e sovrascriverlo farebbe sparire il testo sotto le dita.
    if (document.activeElement === o) return;

    if (o.type === 'checkbox' || o.type === 'radio') {
        const atteso = n.hasAttribute('checked');
        if (o.checked !== atteso) o.checked = atteso;
        return;
    }

    if (o instanceof HTMLSelectElement) {
        if (o.multiple) {
            // niente o.value qui: su un elenco multiplo indicherebbe una sola opzione e
            // spegnerebbe le altre
            const scelti = new Set([...n.querySelectorAll('option[selected]')].map(x => x.getAttribute('value')));
            for (const opzione of o.options) opzione.selected = scelti.has(opzione.value);
            return;
        }

        const scelta = n.querySelector('option[selected]');
        const atteso = scelta ? scelta.getAttribute('value') : '';
        if (o.value !== atteso) o.value = atteso;
        return;
    }

    const atteso = n.getAttribute('value');
    if (atteso !== null && o.value !== atteso) o.value = atteso;
}

function figli(o, n) {
    const perId = new Map();

    for (const f of Array.from(o.children))
        if (f.id) perId.set(f.id, f);

    let corrente = o.firstChild;

    for (const nuovo of Array.from(n.childNodes)) {
        // riconoscimento per id: e' cio' che permette di SPOSTARE una riga invece di
        // ricrearla quando se ne inserisce una prima di lei
        if (nuovo.nodeType === Node.ELEMENT_NODE && nuovo.id && perId.has(nuovo.id)) {
            const esistente = perId.get(nuovo.id);
            perId.delete(nuovo.id);

            if (esistente !== corrente) o.insertBefore(esistente, corrente);
            else corrente = corrente.nextSibling;

            morphNodo(esistente, nuovo);
            continue;
        }

        if (corrente && corrente.nodeType === nuovo.nodeType && corrente.nodeName === nuovo.nodeName
            && !(corrente.nodeType === Node.ELEMENT_NODE && corrente.id)) {
            morphNodo(corrente, nuovo);
            corrente = corrente.nextSibling;
            continue;
        }

        o.insertBefore(nuovo.cloneNode(true), corrente);
    }

    while (corrente) {
        const successivo = corrente.nextSibling;
        o.removeChild(corrente);
        corrente = successivo;
    }
}

// ---------------------------------------------------------------- postback

const radice = () => document.getElementById('dw-root');

let inCorso = Promise.resolve();

// Lo stato che attraversa le pagine: un pacchetto firmato dal server, che sta qui in
// memoria e riparte con ogni richiesta.
//
// Puo' stare in una variabile perche' in questo motore la pagina non si ricarica mai: un
// click e' una fetch e poi un morph, quindi questa memoria non si azzera in mezzo al
// lavoro. Non e' un cookie (non viaggerebbe solo qui), non e' la sessione (non tocca il
// server), non e' il querystring (non si vede). Il server lo firma, quindi da qui non lo si
// puo' riscrivere: e' uno stato, non un lasciapassare.
let portatile = '';

function leggiPortatile() {
    const root = radice();
    const valore = root && root.dataset.dwPortable;

    // l'attributo c'e' solo sulle pagine che hanno campi #[Portable]: dove manca si tiene
    // quello che si ha, altrimenti passare da una pagina che non lo usa lo cancellerebbe
    if (valore) portatile = valore;
}

// Il controllo che ha scatenato il postback resta spento finche' non torna la risposta.
//
// Due click su "Salva" salvano due volte, e la seconda l'utente non l'aveva chiesta: fra il
// click e la risposta c'e' una fetch, e in quel tempo il bottone e' li' che sembra pronto.
// Si spegne solo LUI: il resto della pagina continua a rispondere, perche' e' una difesa
// contro il doppio click, non un blocco della pagina.
//
// Riaccenderlo non serve ricordarselo: il render che torna e' quello che comanda, e nel suo
// HTML il bottone e' acceso. La classe pero' si toglie a mano, perche' le js- sopravvivono
// al morph apposta.

function occupa(id, evento) {
    // solo i click: spegnere una casella di testo mentre si scrive porterebbe via il fuoco
    // e le lettere digitate nel frattempo
    if (evento !== 'click') return null;

    const el = id && document.getElementById(id);
    if (!el) return null;

    el.classList.add('js-dw-occupato');

    // un <a> non ha disabled: si dice ai lettori di schermo che e' occupato, e il CSS gli
    // toglie i click
    if (el instanceof HTMLButtonElement || el instanceof HTMLInputElement)
        el.disabled = true;
    else
        el.setAttribute('aria-disabled', 'true');

    return el;
}

function libera(el) {
    if (!el) return;

    el.classList.remove('js-dw-occupato');

    if (el instanceof HTMLButtonElement || el instanceof HTMLInputElement)
        el.disabled = false;
    else
        el.removeAttribute('aria-disabled');
}

DW.occupato = el => !!el && el.classList.contains('js-dw-occupato');

// I postback si accodano. Due richieste sovrapposte sullo stesso stato lo lascerebbero in
// una via di mezzo fra i due esiti.
DW.postback = (target, evento, arg) => {
    // si spegne SUBITO, non dentro esegui(): li' si arriva quando la coda si svuota, e due
    // click veloci farebbero in tempo a entrare tutti e due
    const acceso = occupa(target, evento);

    inCorso = inCorso
        .then(() => esegui(target, evento, arg))
        .catch(e => DW.errore('postback: ' + e))
        .finally(() => libera(acceso));
};

async function esegui(target, evento, arg) {
    const root = radice();
    if (!root) return;

    const dati = new FormData();

    for (const el of root.querySelectorAll('input[name],select[name],textarea[name]')) {
        if (el.disabled) continue;
        if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) continue;

        // su un <select multiple> el.value da' SOLO la prima opzione scelta: vanno mandate
        // tutte, altrimenti la selezione multipla non e' mai piu' di una
        if (el instanceof HTMLSelectElement && el.multiple) {
            for (const opzione of el.selectedOptions) dati.append(el.name, opzione.value);
            continue;
        }

        dati.append(el.name, el.value);
    }

    dati.append('__dw_portable', portatile);
    dati.append('__dw_target', target);
    dati.append('__dw_event', evento);
    dati.append('__dw_arg', arg || '');

    document.body.classList.add('js-dw-attesa');

    let risposta;

    try {
        risposta = await fetch(location.href, {
            method: 'POST',
            headers: { 'X-DW-Postback': '1', 'X-Csrf-Token': window.DW_CSRF },
            body: dati
        });
    } finally {
        document.body.classList.remove('js-dw-attesa');
    }

    if (!risposta.ok) { location.reload(); return; }

    const esito = await risposta.json();

    // stato scaduto o sessione persa: meglio ricaricare pulito che restare a meta'
    if (esito.ricarica) { location.reload(); return; }

    // il server ci manda altrove. Si passa da vaiA e non da location.href: una navigazione
    // vera butterebbe via questa pagina e con lei lo stato portatile, mentre vaiA e' una
    // fetch - e se la destinazione non e' una pagina del motore ci pensa lei a fare la
    // navigazione vera
    if (esito.vai) {
        if (esito.portatile) portatile = esito.portatile;

        DW.vaiA(esito.vai, true);
        return;
    }

    DW.morph(radice(), esito.html);

    leggiPortatile();

    armaAvvisi();
}

// ---------------------------------------------------------------- eventi dei controlli

document.addEventListener('click', e => {
    const el = e.target.closest('[data-dw-click]');
    if (!el || el.disabled) return;

    // preventDefault anche se poi si annulla: il click e' comunque consumato, altrimenti
    // rispondere "no" a un LinkButton farebbe partire la navigazione verso il suo href
    e.preventDefault();

    // gia' occupato: e' il secondo click sullo stesso bottone mentre il primo e' in viaggio.
    // Il preventDefault sopra dev'essere gia' passato, altrimenti su un LinkButton questo
    // click finirebbe al gestore della navigazione, che ricaricherebbe la pagina da capo
    if (DW.occupato(el)) return;

    const conferma = el.dataset.dwConfirm;
    if (conferma && !window.confirm(conferma)) return;

    DW.postback(el.dataset.dwId, 'click', el.dataset.dwArg || '');
});

// change: scatta all'uscita dal campo, niente da trattenere
document.addEventListener('change', e => {
    const el = e.target.closest('[data-dw-change]');
    if (!el || el.disabled) return;

    DW.postback(el.dataset.dwId, 'change', '');
});

// input: scatta ad ogni tasto, quindi va trattenuto. Senza debounce ogni lettera sarebbe
// una richiesta HTTP con ricostruzione dell'albero e render: scrivendo "martello" sarebbero
// otto giri completi per un risultato che interessa solo l'ultimo.
const attese = new Map();

document.addEventListener('input', e => {
    const el = e.target.closest('[data-dw-input]');
    if (!el || el.disabled) return;

    const id = el.dataset.dwId;

    // la chiave e' l'id, non il nodo: cosi' il timer sopravvive a un morph che nel frattempo
    // ha toccato il campo, invece di restare appeso a un elemento sostituito
    clearTimeout(attese.get(id));

    attese.set(id, setTimeout(() => {
        attese.delete(id);
        DW.postback(id, 'input', '');
    }, parseInt(el.dataset.dwDelay, 10) || 300));
});

// ---------------------------------------------------------------- upload e trascinamento
// Il file non passa dal postback: va su FileUploadHandler.php per conto suo, torna un token, e solo
// quello entra nel form. Poi si scatena un postback normale, cosi' il server puo' mostrare
// l'anteprima con lo stesso giro di sempre.

const zonaDi = e => {
    const nodo = e.target instanceof Element ? e.target : e.target?.parentElement;
    return nodo ? nodo.closest('[data-dw-upload]') : null;
};

DW.carica = async (zona, file) => {
    if (!file) return;

    const dati = new FormData();
    dati.append('file', file);

    // a quale campo e' destinato: il server ne legge i vincoli e puo' rispondere subito
    // "questo non va bene", invece di farlo scoprire al salvataggio
    if (zona.dataset.dwVincoli) dati.append('vincoli', zona.dataset.dwVincoli);

    zona.classList.add('js-dw-caricando');

    let esito;

    try {
        const risposta = await fetch('/public/php/Common/WebForms/FileUploadHandler.php', {
            method: 'POST',
            headers: { 'X-Csrf-Token': window.DW_CSRF },
            body: dati
        });

        esito = await risposta.json();
    } catch (err) {
        DW.avviso('Caricamento non riuscito: ' + err);
        return;
    } finally {
        zona.classList.remove('js-dw-caricando');
    }

    // il file e' stato rifiutato: e' una risposta all'utente - il formato, il peso, le
    // misure - non un errore del programma, e va dove l'utente guarda
    if (esito.errore) { DW.avviso(esito.errore); return; }

    // per nome e non fra i figli: senza l'area di trascinamento il campo del token e'
    // un fratello dell'input, non un suo discendente
    const campo = document.getElementsByName(zona.dataset.dwId + '__token')[0];
    if (campo) campo.value = esito.token;

    DW.postback(zona.dataset.dwId, 'upload', esito.nome || '');
};

document.addEventListener('change', e => {
    if (!(e.target instanceof HTMLInputElement) || e.target.type !== 'file') return;

    const zona = zonaDi(e);
    if (zona) DW.carica(zona, e.target.files[0]);
});

// Il browser, se gli molli un file addosso, lo APRE e ti porta via dalla pagina. Va fermato
// su tutto il documento, non solo sulle zone: fuori si annulla e basta.
for (const evento of ['dragenter', 'dragover']) {
    document.addEventListener(evento, e => {
        e.preventDefault();
        const zona = zonaDi(e);
        if (zona) zona.classList.add('js-dw-sopra');
    });
}

document.addEventListener('dragleave', e => {
    const zona = zonaDi(e);
    if (zona) zona.classList.remove('js-dw-sopra');
});

document.addEventListener('drop', e => {
    e.preventDefault();

    const zona = zonaDi(e);
    if (!zona) return;

    zona.classList.remove('js-dw-sopra');

    DW.carica(zona, e.dataTransfer && e.dataTransfer.files[0]);
});

// ---------------------------------------------------------------- navigazione senza ricarico

DW.navigabile = (a, e) =>
    a && a.href
    && a.origin === location.origin
    && !a.target && !a.hasAttribute('download') && !a.hasAttribute('dw-no-nav')
    && !e.metaKey && !e.ctrlKey && !e.shiftKey && !e.altKey && e.button === 0;

document.addEventListener('click', e => {
    // un LinkButton e' un <a> a tutti gli effetti: se il gestore dei controlli ha gia'
    // consumato il click, qui non c'e' niente da navigare
    if (e.defaultPrevented) return;

    const a = e.target.closest('a');
    if (!DW.navigabile(a, e)) return;

    e.preventDefault();
    DW.vaiA(a.href, true);
});

DW.vaiA = async (url, push) => {
    let testo;

    // anche cambiare pagina e' un'attesa, e dura piu' di un postback: senza questa classe
    // l'UpdateProgress comparirebbe sui click e non sulle navigazioni, che e' il contrario
    // di quello che serve
    document.body.classList.add('js-dw-attesa');

    try {
        const r = await fetch(url, { headers: { 'X-DW-Nav': '1', 'X-DW-Portable': portatile } });

        // un redirect, un 401 o un errore non si fondono nel DOM: si naviga davvero
        if (!r.ok || r.redirected) { location.href = url; return; }

        testo = await r.text();
    } catch (err) {
        location.href = url;
        return;
    } finally {
        document.body.classList.remove('js-dw-attesa');
    }

    const doc = new DOMParser().parseFromString(testo, 'text/html');
    const nuovaRadice = doc.getElementById('dw-root');

    if (!nuovaRadice) { location.href = url; return; }

    DW.teardown();

    DW.morph(radice(), nuovaRadice.outerHTML);

    leggiPortatile();

    armaAvvisi();

    eseguiScript(radice());

    document.title = doc.title;

    if (push) history.pushState({ url }, '', url);

    scrollTo(0, 0);

    document.dispatchEvent(new CustomEvent('dw:pagina'));
};

// Uno <script> inserito nel DOM da codice NON viene eseguito: e' la sorpresa classica di
// chi aggiorna una pagina senza ricaricarla, e si manifesta come "il JavaScript di questa
// pagina non parte" senza alcun errore. Ricrearne il nodo lo fa eseguire.
// Solo in navigazione: nel postback la pagina e' la stessa e rieseguirli ad ogni click
// significherebbe far girare due volte cose scritte per girare una volta sola.
function eseguiScript(radice) {
    for (const vecchio of radice.querySelectorAll('script')) {
        if (vecchio.src) continue;

        const nuovo = document.createElement('script');
        nuovo.textContent = vecchio.textContent;

        vecchio.replaceWith(nuovo);
    }
}

//il pacchetto della prima pagina: da qui in poi lo tiene la memoria
leggiPortatile();

//e gli avvisi che il server ha gia' messo in pagina
armaAvvisi();

// ---------------------------------------------------------------- avvisi
// I riquadri li disegna il SERVER: qui si fa solo quello che succede dopo - il tempo che
// passa, il mouse che si ferma sopra, la x, l'OK del modale.
//
// Perche' un insieme di chiavi e non una classe sul nodo: il morph riusa il nodo che sta in
// quella posizione, quindi due avvisi diversi in due postback diversi sono lo STESSO nodo con
// il testo cambiato. Una classe "gia' armato" resterebbe attaccata e il secondo avviso non
// partirebbe mai. L'id invece cambia, perche' il server ci mette una chiave a caso.

const avvisiArmati = new Set();

// La colonna che si vede e' del CLIENT e sta fuori da dw-root: il morph non la tocca.
//
// Serve perche' il server disegna solo i messaggi NUOVI - la coda si consuma appena resa -
// e il morph riconcilia i figli per posizione: il riquadro ancora in pagina si ritroverebbe
// scritto sopra dal testo del prossimo. Due eliminazioni di fila darebbero un riquadro solo.
// Spostandoli fuori si impilano, ed e' anche l'unico modo perche' quello che sta uscendo in
// dissolvenza non venga cancellato a meta'.
function pila() {
    let colonna = document.querySelector('.dw-avvisi.js-dw-pila');

    if (colonna) return colonna;

    colonna = document.createElement('div');
    colonna.className = 'dw-avvisi js-dw-pila';
    colonna.setAttribute('aria-live', 'polite');

    document.body.appendChild(colonna);

    return colonna;
}

// Un avviso che nasce QUI e non dal server: serve quando la risposta arriva prima del
// prossimo render - un file rifiutato al caricamento, per esempio. La forma e' la stessa,
// cosi' non ci sono due modi di dire la stessa cosa all'utente.
DW.avviso = (testo, tipo) => {
    const successo = tipo === 'successo';

    const avviso = document.createElement('div');

    avviso.className = 'dw-avviso ' + (successo ? 'dw-avviso-successo' : 'dw-avviso-fallito');
    avviso.id = 'dw-avviso-' + Math.random().toString(36).slice(2);
    avviso.setAttribute('role', 'status');

    const icona = document.createElement('span');
    icona.className = 'dw-avviso-icona';
    icona.setAttribute('aria-hidden', 'true');
    icona.textContent = successo ? '\u2713' : '\u26a0';

    // textContent e non innerHTML: qui dentro finiscono messaggi che arrivano dal server e
    // nomi di file scelti dall'utente
    const corpo = document.createElement('div');
    corpo.className = 'dw-avviso-testo';
    corpo.textContent = testo;

    const chiudi = document.createElement('button');
    chiudi.type = 'button';
    chiudi.className = 'dw-avviso-chiudi';
    chiudi.setAttribute('aria-label', 'Chiudi');
    chiudi.innerHTML = '&times;';

    avviso.append(icona, corpo, chiudi);

    avvisiArmati.add(avviso.id);

    pila().appendChild(avviso);

    while (pila().children.length > 5) pila().firstElementChild.remove();

    arma(avviso, durataAvvisi());
};

// quanto durano, secondo il <dw:Alert> della master. Senza quello, cinque secondi.
function durataAvvisi() {
    const colonna = document.querySelector('.dw-avvisi:not(.js-dw-pila)');

    const durata = colonna ? parseInt(colonna.dataset.dwDurata, 10) : NaN;

    return isNaN(durata) ? 5000 : durata;
}

function armaAvvisi() {
    const colonna = document.querySelector('.dw-avvisi:not(.js-dw-pila)');

    if (colonna) {
        const durata = parseInt(colonna.dataset.dwDurata, 10);

        for (const avviso of [...colonna.querySelectorAll('.dw-avviso')]) {
            if (!avviso.id || avvisiArmati.has(avviso.id)) continue;

            avvisiArmati.add(avviso.id);

            pila().appendChild(avviso);

            // il tetto vale su quello che si VEDE: i piu' vecchi se ne vanno per fare posto,
            // che e' anche l'ordine in cui sono stati letti
            while (pila().children.length > 5) pila().firstElementChild.remove();

            arma(avviso, durata);
        }
    }

    const modale = document.querySelector('.dw-modale');

    if (modale && !modale.dataset.dwArmato) {
        modale.dataset.dwArmato = '1';

        // anche il modale esce da dw-root: un postback che arrivasse mentre e' aperto - una
        // notifica, un timer - se lo porterebbe via prima che qualcuno abbia cliccato OK
        document.body.appendChild(modale);

        const chiudi = () => modale.remove();

        modale.querySelector('.dw-modale-ok')?.addEventListener('click', chiudi);

        // Esc chiude come l'OK: e' un avviso, non una domanda a cui si deve rispondere
        // qualcosa. Il click sullo sfondo NO - troppo facile perderlo per sbaglio, ed e'
        // proprio il messaggio che si voleva far leggere per forza.
        document.addEventListener('keydown', function esc(e) {
            if (e.key !== 'Escape') return;

            document.removeEventListener('keydown', esc);
            chiudi();
        });

        modale.querySelector('.dw-modale-ok')?.focus();
    }
}

function arma(avviso, durata) {
    let timer = null;

    const via = () => {
        avviso.classList.add('js-dw-esce');

        // si toglie a dissolvenza finita: prima, la colonna salterebbe
        avviso.addEventListener('animationend', () => avviso.remove(), { once: true });

        // ...ma non SOLO a dissolvenza finita: in una scheda in secondo piano il browser
        // congela le animazioni, animationend non arriva mai e il riquadro resterebbe li'
        // ad aspettare per sempre. Provato.
        setTimeout(() => avviso.remove(), 800);
    };

    const parti = () => {
        if (durata > 0) timer = setTimeout(via, durata);
    };

    avviso.querySelector('.dw-avviso-chiudi')?.addEventListener('click', () => {
        clearTimeout(timer);
        via();
    });

    // col mouse sopra resta: si sta leggendo, ed e' l'unico momento in cui si e' sicuri
    // che quel messaggio interessa a qualcuno
    avviso.addEventListener('mouseenter', () => clearTimeout(timer));
    avviso.addEventListener('mouseleave', parti);

    parti();
}

history.scrollRestoration = 'manual';

addEventListener('popstate', () => DW.vaiA(location.href, false));

// ---------------------------------------------------------------- notifiche push
// Il messaggio non porta dati, solo i nomi dei topic cambiati: e' il postback che rilegge,
// con la sessione e i permessi di CHI RICEVE. Cosi' un evento nato dal salvataggio di un
// altro utente non puo' far arrivare qui niente che non si potesse gia' vedere.

let attesi = new Set();
let timer = null;

// Il nostro static non ha risposto: si riprova dal CDN pubblico. Senza libreria le notifiche
// sparirebbero in silenzio, e una griglia che non si aggiorna piu' non ha nessun sintomo.
DW.signalrDiScorta = () => {
    const s = document.createElement('script');

    s.src = DW_SIGNALR_SCORTA;
    s.defer = true;
    s.onload = () => DW.collegaNotifiche();
    s.onerror = () => console.warn('SignalR non caricato: niente notifiche in tempo reale.');

    document.head.appendChild(s);
};

// Collegamento al hub. Il messaggio arriva come Push(nome, valore): si instrada per nome,
// cosi' sullo stesso canale possono viaggiare cose diverse senza che si pestino i piedi.
DW.collegaNotifiche = () => {
    if (typeof signalR === 'undefined') return;

    const connessione = new signalR.HubConnectionBuilder()
        .withUrl('/signalrhub')
        .withAutomaticReconnect()
        .build();

    connessione.on('Push', (nome, valore) => {
        if (nome === 'DWEventi') window.DWEventi(valore);
    });

    const entra = () => connessione.invoke('JoinDomain', location.hostname).catch(() => {});

    // anche dopo una riconnessione: i gruppi si perdono con la connessione, e senza
    // rientrare la pagina smetterebbe di ricevere senza dare alcun segno
    connessione.onreconnected(entra);

    connessione.start().then(entra).catch(() => {
        // niente hub: la pagina resta perfettamente funzionante, senza notifiche
    });

    DW.notifiche = connessione;
};

window.DWEventi = (messaggio) => {
    let dato;

    try { dato = typeof messaggio === 'string' ? JSON.parse(messaggio) : messaggio; }
    catch (e) { return; }

    const root = radice();
    if (!root) return;

    // l'evento nato da questa stessa pagina si scarta: si e' gia' aggiornata col suo postback
    if (dato.o && dato.o === root.dataset.dwPush) return;

    for (const topic of (dato.t || [])) attesi.add(topic);

    if (timer) return;

    // raffica di salvataggi = un postback solo, e con un ritardo casuale cosi' i browser
    // collegati non partono tutti nello stesso istante
    timer = setTimeout(() => {
        const elenco = Array.from(attesi);
        attesi = new Set();
        timer = null;

        DW.postback('__push', 'push', JSON.stringify(elenco));
    }, 150 + Math.random() * 250);
};

})();
JS;
}
