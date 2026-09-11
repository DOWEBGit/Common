// Il pezzo di motore che gira nel browser: intercettazione degli eventi, postback, morph del
// DOM, navigazione senza ricarico, notifiche push.
//
// Lo include Runtime::Scripts(), in coda al body, con "defer" e con la marca temporale del
// file nell'indirizzo: il browser lo tiene in cache finche' non lo si tocca, e appena lo si
// tocca l'indirizzo cambia da solo.
//
// Sta qui e non dentro un const di PHP per un motivo pratico: cosi' e' JavaScript per
// l'editor, per il controllo di sintassi e per il debugger del browser, che gli da' un nome
// di file e dei numeri di riga veri invece di "inline #3".
//
// Tutto passa da delega sul document: nessun listener viene attaccato ai singoli controlli,
// quindi non c'e' niente da riagganciare dopo un aggiornamento e non si accumula nulla
// navigando. Il ciclo init/leave resta esposto per il codice di pagina, che invece deve
// pulire quello che apre.

(() => {
'use strict';

const DW = window.DW = {};

// ---------------------------------------------------------------- errori
// Un errore JavaScript che non si vede costa piu' di uno che urla: qui diventa sempre
// visibile in pagina, oltre che in console.

DW.error = (messaggio) => {
    console.error(messaggio);

    let box = document.getElementById('dw-errore');

    if (!box) {
        box = document.createElement('div');
        box.id = 'dw-errore';
        document.body.appendChild(box);
    }

    box.textContent += messaggio + '\n';
};

addEventListener('error', e => DW.error('JS: ' + (e.message || e.error) + '  @' + e.filename + ':' + e.lineno));
addEventListener('unhandledrejection', e => DW.error('Promise non gestita: ' + e.reason));

// ---------------------------------------------------------------- ciclo di vita
// I listener del framework sono delegati sul document, quindi non serve riagganciarli.
// Questo resta per il codice di pagina: quello che apre (timer, editor, osservatori) deve
// chiudersi alla navigazione, o dopo venti pagine se ne trascinano venti copie vive.

let uscite = [];

DW.onLeave = fn => uscite.push(fn);

DW.teardown = () => {
    for (const fn of uscite) {
        try { fn(); } catch (e) { DW.error('teardown: ' + e); }
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
    //
    // L'unione passa da un Set perche' i due padroni possono nominare la stessa classe: se
    // il server emette lui una classe js- - CssClass="js-riga-scelta" e' legittimo - senza
    // Set uscirebbe scritta due volte nell'attributo.
    const mie = Array.from(o.classList).filter(c => c.startsWith('js-'));
    const sue = (n.getAttribute('class') || '').split(/\s+/).filter(Boolean);
    const tutte = Array.from(new Set(sue.concat(mie))).join(' ');

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

// Lo stato sta FUORI da dw-root, quindi nessun morph lo sfiora: si trova sempre qui, come
// una volta ci si fidava della sessione. Se manca - una pagina servita a pezzi, un DOM
// manomesso - se lo crea il client invece di perdere il postback.
const campoStato = () => {
    let campo = document.getElementById('__dw_state');

    if (!campo) {
        campo = document.createElement('input');
        campo.type = 'hidden';
        campo.name = '__dw_state';
        campo.id = '__dw_state';

        document.body.appendChild(campo);
    }

    return campo;
};

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

DW.busy = el => !!el && el.classList.contains('js-dw-occupato');

// I postback si accodano. Due richieste sovrapposte sullo stesso stato lo lascerebbero in
// una via di mezzo fra i due esiti.
DW.postback = (target, evento, arg) => {
    // si spegne SUBITO, non dentro esegui(): li' si arriva quando la coda si svuota, e due
    // click veloci farebbero in tempo a entrare tutti e due
    const acceso = occupa(target, evento);

    inCorso = inCorso
        .then(() => esegui(target, evento, arg))
        .catch(e => DW.error('postback: ' + e))
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

    dati.append('__dw_state', campoStato().value);
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

        DW.navigate(esito.vai, true);
        return;
    }

    // PRIMA lo stato, poi il DOM: se il morph solleva a meta' - un HTML storto, un nodo che
    // non si lascia riconciliare - il campo e' gia' quello nuovo e il click dopo parte da uno
    // stato coerente con quello che il server ha in mano
    campoStato().value = esito.stato || '';

    DW.morph(radice(), esito.html);

    leggiPortatile();

    armaAvvisi();

    riepilogoStato();
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
    if (DW.busy(el)) return;

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

DW.load = async (zona, file) => {
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
        DW.alert('Caricamento non riuscito: ' + err);
        return;
    } finally {
        zona.classList.remove('js-dw-caricando');
    }

    // il file e' stato rifiutato: e' una risposta all'utente - il formato, il peso, le
    // misure - non un errore del programma, e va dove l'utente guarda
    if (esito.errore) { DW.alert(esito.errore); return; }

    // per nome e non fra i figli: senza l'area di trascinamento il campo del token e'
    // un fratello dell'input, non un suo discendente
    const campo = document.getElementsByName(zona.dataset.dwId + '__token')[0];
    if (campo) campo.value = esito.token;

    DW.postback(zona.dataset.dwId, 'upload', esito.nome || '');
};

document.addEventListener('change', e => {
    if (!(e.target instanceof HTMLInputElement) || e.target.type !== 'file') return;

    const zona = zonaDi(e);
    if (zona) DW.load(zona, e.target.files[0]);
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

    DW.load(zona, e.dataTransfer && e.dataTransfer.files[0]);
});

// ---------------------------------------------------------------- navigazione senza ricarico

DW.isNavigable = (a, e) =>
    a && a.href
    && a.origin === location.origin
    && !a.target && !a.hasAttribute('download') && !a.hasAttribute('dw-no-nav')
    && !e.metaKey && !e.ctrlKey && !e.shiftKey && !e.altKey && e.button === 0;

document.addEventListener('click', e => {
    // un LinkButton e' un <a> a tutti gli effetti: se il gestore dei controlli ha gia'
    // consumato il click, qui non c'e' niente da navigare
    if (e.defaultPrevented) return;

    const a = e.target.closest('a');
    if (!DW.isNavigable(a, e)) return;

    e.preventDefault();
    DW.navigate(a.href, true);
});

// ---------------------------------------------------------------- pagine che si tengono
// Una pagina con data-dw-tieni si comporta come una form di WinForms: la si lascia, si torna,
// e la si ritrova com'era. Lo stato sta QUI, nella memoria di questa scheda - non sul server,
// che non tiene niente, e non in sessionStorage, che il tasto indietro e altre schede
// vedrebbero. Chiudere la scheda o premere F5 lo butta via, ed e' quello che deve succedere.
//
// Il tetto e' in BYTE, non in numero di pagine: una pagina normale pesa 250 byte e una
// costruita a mano con duecento righe ne pesa 9000, e un conteggio non distingue le due.
// Cinquanta megabyte non si raggiungono con l'uso normale e fermano una scheda che naviga
// per ore fra indirizzi sempre diversi. Superato, si buttano le pagine con l'ultimo accesso
// piu' vecchio finche' non si rientra: ogni accesso rimette la pagina in fondo alla Map,
// quindi la piu' vecchia e' sempre la prima chiave.
const TENUTE_BYTE = 50 * 1024 * 1024;

const tenute = new Map();

let tenuteByte = 0;

// La chiave e' il PERCORSO, senza querystring. Cambiare la querystring non e' cambiare
// pagina: e' la stessa pagina con un parametro diverso - un altro mese, un altro filtro -
// e lo stato tenuto va rimesso lo stesso. Il server vede la $_GET nuova, e la pagina decide
// in OnLoad cosa ricaricare: e' lavoro suo, non del motore.
const chiaveDi = (url) => { const u = new URL(url, location.href); return u.origin + u.pathname; };

// Quanto pesa lo stato in questo momento, in console: la pagina corrente e le tenute. Esce
// alla fine di ogni postback e di ogni navigazione, cosi' chi sviluppa vede crescere il
// pacchetto mentre lavora invece di scoprirlo quando e' gia' grosso.
const riepilogoStato = () => {
    // l'unita' segue la misura: "0.00 MB" non dice niente, "1.1 KB" si'
    const misura = (b) => b < 1024 * 1024 ? (b / 1024).toFixed(1) + ' KB' : (b / 1024 / 1024).toFixed(2) + ' MB';

    console.info('DW stato: pagina ' + misura(campoStato().value.length)
        + ' · tenute ' + tenute.size + ' pagine, ' + misura(tenuteByte) + ' su ' + misura(TENUTE_BYTE));
};

const dimentica = (chiave) => {
    const vecchio = tenute.get(chiave);

    if (vecchio === undefined) return;

    tenuteByte -= vecchio.length;
    tenute.delete(chiave);
};

const tieniStato = () => {
    const root = radice();

    if (!root) return;

    const chiave = chiaveDi(location.href);

    // La pagina ha smesso di volersi tenere - una casella spenta, una condizione cambiata:
    // quello che c'era in serbo va buttato, o al ritorno si rimetterebbe in piedi uno stato
    // che la pagina stessa ha appena rinnegato.
    if (!root.dataset.dwTieni) {
        dimentica(chiave);

        return;
    }

    const stato = campoStato().value;

    // rimessa in fondo: cosi' la piu' vecchia e' davvero quella lasciata da piu' tempo
    dimentica(chiave);

    tenute.set(chiave, stato);
    tenuteByte += stato.length;

    while (tenuteByte > TENUTE_BYTE && tenute.size > 1)
        dimentica(tenute.keys().next().value);
};

DW.navigate = async (url, push) => {
    let testo;

    // prima di andarsene: se questa pagina si tiene, ci si tiene il suo stato
    tieniStato();

    const tenuto = tenute.get(chiaveDi(url));

    // anche cambiare pagina e' un'attesa, e dura piu' di un postback: senza questa classe
    // l'UpdateProgress comparirebbe sui click e non sulle navigazioni, che e' il contrario
    // di quello che serve
    document.body.classList.add('js-dw-attesa');

    try {
        // Con uno stato da rimettere la richiesta diventa una POST: un ViewState non ci sta
        // in un'intestazione. Risponde comunque con un documento intero, perche' resta una
        // navigazione - il server lo sa dall'intestazione X-DW-Ripristina.
        const r = tenuto === undefined
            ? await fetch(url, { headers: { 'X-DW-Nav': '1', 'X-DW-Portable': portatile } })
            : await fetch(url, {
                method: 'POST',
                headers: {
                    'X-DW-Nav': '1',
                    'X-DW-Ripristina': '1',
                    'X-DW-Portable': portatile,
                    'X-Csrf-Token': window.DW_CSRF
                },
                body: new URLSearchParams({ __dw_state: tenuto })
            });

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

    campoStato().value = doc.getElementById('__dw_state')?.value || '';

    DW.morph(radice(), nuovaRadice.outerHTML);

    leggiPortatile();

    armaAvvisi();

    riepilogoStato();

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

riepilogoStato();

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
DW.alert = (testo, tipo) => {
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

    while (pila().children.length > 5) { avvisiArmati.delete(pila().firstElementChild.id); pila().firstElementChild.remove(); }

    arma(avviso, durataAvvisi());
};

// quanto durano, secondo il <dw:Alert> della master. Senza quello, cinque secondi.
function durataAvvisi() {
    const colonna = document.querySelector('.dw-avvisi:not(.js-dw-pila)');

    const durata = colonna ? parseInt(colonna.dataset.dwDuration, 10) : NaN;

    return isNaN(durata) ? 5000 : durata;
}

function armaAvvisi() {
    const colonna = document.querySelector('.dw-avvisi:not(.js-dw-pila)');

    if (colonna) {
        const durata = parseInt(colonna.dataset.dwDuration, 10);

        for (const avviso of [...colonna.querySelectorAll('.dw-avviso')]) {
            if (!avviso.id || avvisiArmati.has(avviso.id)) continue;

            avvisiArmati.add(avviso.id);

            pila().appendChild(avviso);

            // il tetto vale su quello che si VEDE: i piu' vecchi se ne vanno per fare posto,
            // che e' anche l'ordine in cui sono stati letti
            while (pila().children.length > 5) { avvisiArmati.delete(pila().firstElementChild.id); pila().firstElementChild.remove(); }

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

    // L'id esce dall'insieme degli armati insieme al riquadro: quell'insieme serve a non
    // armare due volte lo stesso avviso, e un avviso tolto non tornera' mai - il server ci
    // mette una chiave a caso. Tenerlo sarebbe una perdita piccola ma senza fine: una scheda
    // che resta aperta giorni accumulerebbe un id per ogni avviso mai mostrato.
    const togli = () => {
        avvisiArmati.delete(avviso.id);
        avviso.remove();
    };

    const via = () => {
        avviso.classList.add('js-dw-esce');

        // si toglie a dissolvenza finita: prima, la colonna salterebbe
        avviso.addEventListener('animationend', togli, { once: true });

        // ...ma non SOLO a dissolvenza finita: in una scheda in secondo piano il browser
        // congela le animazioni, animationend non arriva mai e il riquadro resterebbe li'
        // ad aspettare per sempre. Provato.
        setTimeout(togli, 800);
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

addEventListener('popstate', () => DW.navigate(location.href, false));

// ---------------------------------------------------------------- notifiche push
// Il messaggio non porta dati, solo i nomi dei topic cambiati: e' il postback che rilegge,
// con la sessione e i permessi di CHI RICEVE. Cosi' un evento nato dal salvataggio di un
// altro utente non puo' far arrivare qui niente che non si potesse gia' vedere.

let attesi = new Set();
let timer = null;

// Il nostro static non ha risposto: si riprova dal CDN pubblico. Senza libreria le notifiche
// sparirebbero in silenzio, e una griglia che non si aggiorna piu' non ha nessun sintomo.
DW.signalrFallback = () => {
    const s = document.createElement('script');

    s.src = DW_SIGNALR_SCORTA;
    s.defer = true;
    s.onload = () => DW.connectNotifications();
    s.onerror = () => console.warn('SignalR non caricato: niente notifiche in tempo reale.');

    document.head.appendChild(s);
};

// Collegamento al hub. Il messaggio arriva come Push(nome, valore): si instrada per nome,
// cosi' sullo stesso canale possono viaggiare cose diverse senza che si pestino i piedi.
DW.connectNotifications = () => {
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

    DW.notifications = connessione;
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
