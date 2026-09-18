// <dw:RichTextBox> nel browser: la barra, i menu, la cronologia, l'incolla ripulito.
//
// Non sta dentro runtime.js perche' pesa e serve solo alle pagine che hanno un editor: lo
// carica il motore la prima volta che ne vede uno (armaEditor in runtime.js), e da li' in poi
// ad ogni render chiama DW.rte.arma(), che riallinea gli editor al server.
//
// COME SI SCRIVE. L'area e' un contenteditable e i comandi di base sono quelli del browser
// (document.execCommand): e' l'unico modo che funzioni uguale su Chrome, Safari e Firefox,
// da computer e da telefono, compresi la tastiera virtuale, la dettatura e il correttore. Il
// resto - interlinea, rientro dei paragrafi, tabelle, dimensioni in pixel - lo si fa qui, sul
// DOM, perche' il browser non lo sa fare o lo fa con i tag del 1998.
//
// CHI COMANDA IL TESTO. Mentre si scrive, il browser; quando il server lo cambia, il server.
// L'area ha dw-preserve, quindi il morph non la tocca mai: il valore del server arriva nel
// campo nascosto, e arma() decide - se l'utente e' dentro l'editor vince quello che ha scritto,
// altrimenti vince il server. Senza questa regola un postback partito mentre si scrive
// riporterebbe indietro le ultime lettere e il cursore.
//
// IL SERVER RIPULISCE COMUNQUE. La pulizia di qui serve a far vedere subito quello che restera'
// - l'incolla da Word arriva gia' spoglio - non a proteggere niente: le regole sono le stesse,
// le manda il server nella configurazione, e l'ultima parola e' sua.
//
// Tutto passa da delega sul document, come nel motore: niente da riagganciare dopo un morph.

(() => {
'use strict';

const DW = window.DW;
if (!DW) return;

/** lo stato di ogni editor, per il suo contenitore: il morph lo lascia lo stesso nodo */
const editori = new WeakMap();

// ---------------------------------------------------------------- la pulizia

// i tag che se ne vanno con il contenuto: il loro testo non e' testo per chi legge
const VIA = new Set(['script', 'style', 'template', 'iframe', 'frame', 'frameset', 'object', 'embed',
    'applet', 'noscript', 'head', 'title', 'meta', 'link', 'base', 'svg', 'math', 'canvas', 'audio',
    'video', 'source', 'track', 'picture', 'img', 'map', 'area', 'input', 'button', 'select', 'option',
    'optgroup', 'textarea', 'datalist', 'output', 'progress', 'meter', 'dialog', 'colgroup', 'col',
    'caption', 'xml', 'param', 'slot', 'portal']);

const esc = s => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

const COLORE = /^(#[0-9a-f]{3,4}|#[0-9a-f]{6}|#[0-9a-f]{8}|rgba?\(\s*[\d.]+%?\s*[, ]\s*[\d.]+%?\s*[, ]\s*[\d.]+%?\s*([,/]\s*[\d.]+%?\s*)?\)|hsla?\(\s*[\d.]+(deg)?\s*[, ]\s*[\d.]+%\s*[, ]\s*[\d.]+%\s*([,/]\s*[\d.]+%?\s*)?\)|[a-z]{3,20})$/;
const NON_COLORI = new Set(['transparent', 'inherit', 'initial', 'unset', 'currentcolor', 'windowtext']);

// la stessa regola di RichTextBox::Valore(): il valore buono nella sua forma, o stringa vuota
function valore(proprieta, grezzo, r) {
    const v = grezzo.trim().replace(/\s*!important\s*$/i, '').toLowerCase();

    if (/[<>\\{}]|\/\*|url\s*\(|expression|javascript|@/i.test(v)) return '';

    switch (proprieta) {
        case 'color':
        case 'background-color':
            return COLORE.test(v) && !NON_COLORI.has(v) ? v : '';
        case 'font-size': {
            const m = /^(\d{1,3}(?:\.\d+)?)(px|pt|em|rem|%)$/.exec(v);
            if (m) {
                const n = parseFloat(m[1]);
                const ok = { px: n >= 6 && n <= 96, pt: n >= 5 && n <= 72, em: n >= .4 && n <= 6, rem: n >= .4 && n <= 6, '%': n >= 40 && n <= 600 }[m[2]];
                return ok ? v : '';
            }
            return ['xx-small', 'x-small', 'small', 'medium', 'large', 'x-large', 'xx-large'].includes(v) ? v : '';
        }
        case 'font-family': {
            const primo = grezzo.split(',')[0].trim().replace(/^["']|["']$/g, '');
            const trovato = r.fonts.find(f => f.toLowerCase() === primo.toLowerCase());
            return trovato && /^[\p{L}\p{N} _-]+$/u.test(trovato) ? (trovato.includes(' ') ? "'" + trovato + "'" : trovato) : '';
        }
        case 'font-weight':
            return ['bold', 'bolder', '600', '700', '800', '900'].includes(v) ? 'bold' : '';
        case 'font-style':
            return v === 'italic' || v === 'oblique' ? 'italic' : '';
        case 'text-decoration': {
            const t = [];
            if (v.includes('underline') && 'u' in r.tags) t.push('underline');
            if (v.includes('line-through') && 's' in r.tags) t.push('line-through');
            return t.join(' ');
        }
        case 'vertical-align':
            return (v === 'super' && 'sup' in r.tags) || (v === 'sub' && 'sub' in r.tags) ? v : '';
        case 'text-align':
            return { left: 'left', start: 'left', center: 'center', '-webkit-center': 'center', right: 'right', end: 'right', justify: 'justify' }[v] || '';
        case 'line-height':
            return v === 'normal' || (/^\d(\.\d{1,2})?$/.test(v) && +v >= .8 && +v <= 4) ? v : '';
        case 'margin-left': {
            const m = /^(\d{1,3})px$/.exec(v);
            return m && +m[1] > 0 && +m[1] <= 400 ? v : '';
        }
    }
    return '';
}

function stile(testo, r) {
    if (!testo || r.styles.length === 0) return '';

    const tenute = new Map();

    for (const dichiarazione of testo.split(';')) {
        const i = dichiarazione.indexOf(':');
        if (i < 0) continue;

        let proprieta = dichiarazione.slice(0, i).trim().toLowerCase();
        if (proprieta === 'text-decoration-line') proprieta = 'text-decoration';
        if (!r.styles.includes(proprieta)) continue;

        const v = valore(proprieta, dichiarazione.slice(i + 1), r);
        if (v) tenute.set(proprieta, proprieta + ':' + v);
    }

    return [...tenute.values()].join(';');
}

// l'indirizzo di un link, se e' uno di quelli buoni: la stessa regola di RichTextBox::Indirizzo()
function indirizzo(href) {
    href = (href || '').replace(/[\x00-\x1F\x7F]+/g, '').trim();
    if (!href) return '';

    href = href.replace(/ /g, '%20');

    const m = /^([a-z][a-z0-9+.-]*):/i.exec(href);
    if (m) return ['http', 'https', 'mailto', 'tel'].includes(m[1].toLowerCase()) ? href : '';

    return href.startsWith('//') ? 'https:' + href : href;
}

// quello che l'utente scrive nella casella del link, reso un indirizzo: "doweb.it" e'
// https://doweb.it, "mario@x.it" e' una mail, "+39 045..." un telefono
function indirizzoScritto(testo) {
    const t = (testo || '').trim();
    if (!t) return '';

    if (/^[^\s@/]+@[^\s@/]+\.[^\s@/]+$/.test(t)) return 'mailto:' + t;
    if (/^\+?[\d\s().-]{6,}$/.test(t)) return 'tel:' + t.replace(/[^\d+]/g, '');
    if (/^[a-z][a-z0-9+.-]*:/i.test(t) || t.startsWith('/') || t.startsWith('#') || t.startsWith('?')) return indirizzo(t);
    if (/^[\w-]+(\.[\w-]+)+/.test(t)) return indirizzo('https://' + t);

    return indirizzo(t);
}

function figli(padre, r) {
    let out = '';
    for (const n of padre.childNodes) out += nodo(n, r);
    return out;
}

function nodo(n, r) {
    if (n.nodeType === Node.TEXT_NODE) return esc(n.data);
    if (n.nodeType !== Node.ELEMENT_NODE) return '';

    let nome = n.localName.toLowerCase();
    if (VIA.has(nome)) return '';

    let s = n.getAttribute('style') || '';

    if (nome === 'font') {
        nome = 'span';
        if (n.hasAttribute('color')) s += ';color:' + n.getAttribute('color');
        if (n.hasAttribute('face')) s += ';font-family:' + n.getAttribute('face');
    }

    nome = r.rename[nome] || nome;

    const dentro = figli(n, r);

    if (!(nome in r.tags)) return dentro;

    let attributi = '';

    s = stile(s, r);
    if (s) attributi += ' style="' + esc(s) + '"';

    if (nome === 'a') {
        const href = indirizzo(n.getAttribute('href'));
        if (!href) return dentro;

        attributi += ' href="' + esc(href) + '"';
        if ((n.getAttribute('target') || '').trim().toLowerCase() === '_blank') attributi += ' target="_blank" rel="noopener noreferrer"';
    }

    if (nome === 'span' && !attributi) return dentro;
    if (nome === 'br' || nome === 'hr') return '<' + nome + attributi + '>';

    return '<' + nome + attributi + '>' + dentro + '</' + nome + '>';
}

function pulisci(html, r) {
    const doc = new DOMParser().parseFromString('<!DOCTYPE html><html><body>' + (html || '') + '</body></html>', 'text/html');
    return figli(doc.body, r);
}

// ---------------------------------------------------------------- il testo, per il conto

const PAROLA = /[\p{L}\p{N}]+(?:['’.,-][\p{L}\p{N}]+)*/gu;

// "12 parole, 80 caratteri", o "80 / 2000 caratteri": lo stesso testo di RichTextBox::Conto()
function conto(testo, massimo) {
    const caratteri = [...testo].length;
    const parole = (testo.match(PAROLA) || []).length;

    return parole + (parole === 1 ? ' parola' : ' parole') + ', '
        + (massimo > 0 ? caratteri + ' / ' + massimo : caratteri)
        + (caratteri === 1 && massimo === 0 ? ' carattere' : ' caratteri');
}

const testoDi = area => (area.innerText || '').replace(/ /g, ' ').replace(/[ \t]*\n[ \t\n]*/g, '\n').trim();

// ---------------------------------------------------------------- gli editor

function statoDi(w) {
    let st = editori.get(w);

    if (!st) {
        st = { w, storia: [], pos: -1, selezione: null, menu: null, dimensione: '', inviato: null, alFocus: null, timer: 0 };
        editori.set(w, st);
    }

    return st;
}

const areaDi = st => st.w.querySelector('.dw-rte-area');
const campoDi = st => st.w.querySelector('input[type=hidden]');

// DW.rte.arma(): dopo ogni render. Ogni editor prende la sua configurazione - puo' essere
// cambiata dal server - e si riallinea al valore che il server ha rimesso nel campo
function arma() {
    for (const w of document.querySelectorAll('[data-dw-rte]')) {
        try { armaUno(w); } catch (e) { DW.error('RichTextBox ' + w.id + ': ' + e); }
    }
}

function armaUno(w) {
    const st = statoDi(w);

    st.cfg = JSON.parse(w.dataset.dwRte || '{}');

    const area = areaDi(st), campo = campoDi(st);
    if (!area || !campo) return;

    // l'area ha dw-preserve: i suoi attributi li rimette qui chi sa cosa vuole il server
    area.contentEditable = st.cfg.enabled ? 'true' : 'false';
    area.style.minHeight = st.cfg.minHeight + 'px';
    area.style.maxHeight = st.cfg.maxHeight ? st.cfg.maxHeight + 'px' : '';

    if (st.cfg.placeholder) area.dataset.placeholder = st.cfg.placeholder;
    else delete area.dataset.placeholder;

    if (st.inviato === null) {
        // la prima volta: il contenuto e' quello che ha reso il server, e la cronologia parte da li'
        st.inviato = campo.value;
        registra(st);
    } else if (campo.value !== st.inviato) {
        const dentro = area.contains(document.activeElement) || w.classList.contains('js-dw-rte-sorgente');

        if (dentro) {
            // sta scrivendo: vince lui, e il campo torna a dire quello che c'e' nell'editor
            campo.value = st.inviato;
        } else {
            area.innerHTML = campo.value;
            st.inviato = campo.value;
            registra(st);
        }
    }

    aggiornaAspetto(st);
}

// il valore da mandare: vuoto se non c'e' niente da leggere, come fa il server
function valoreDi(st) {
    const area = areaDi(st);
    return testoDi(area) === '' && !area.querySelector('hr,table,li') ? '' : area.innerHTML;
}

function aggiornaCampo(st) {
    const v = valoreDi(st);
    campoDi(st).value = v;
    st.inviato = v;
    aggiornaAspetto(st);
}

function aggiornaAspetto(st) {
    const area = areaDi(st);
    const testo = testoDi(area);

    st.w.classList.toggle('js-dw-rte-vuoto', testo === '' && !area.querySelector('hr,table,li'));

    const quanti = [...testo].length;
    st.w.classList.toggle('js-dw-rte-troppo', st.cfg.maxLength > 0 && quanti > st.cfg.maxLength);

    const c = st.w.querySelector('.dw-rte-conto');
    if (c) c.textContent = conto(testo, st.cfg.maxLength);

    if (st.ultimoColore) for (const [menu, colore] of Object.entries(st.ultimoColore)) {
        const b = st.w.querySelector('[data-rte-menu=' + menu + ']');
        if (b) b.style.setProperty('--dw-rte-ultimo', colore);
    }
}

const acceso = (st, funzione) => st.cfg.enabled && st.cfg.features.includes(funzione);

// ---------------------------------------------------------------- la cronologia
// Propria e non quella del browser: il browser ricorda solo i suoi comandi, e annullando
// salterebbe l'interlinea, il rientro, le tabelle - tutto quello che si fa qui sul DOM.

function offset(area, contenitore, pos) {
    const r = document.createRange();
    r.setStart(area, 0);
    try { r.setEnd(contenitore, pos); } catch (e) { return 0; }
    return r.toString().length;
}

function cattura(st) {
    const area = areaDi(st);
    const sel = getSelection();
    let da = null, a = null;

    if (sel.rangeCount && area.contains(sel.anchorNode)) {
        const r = sel.getRangeAt(0);
        da = offset(area, r.startContainer, r.startOffset);
        a = offset(area, r.endContainer, r.endOffset);
    }

    return { html: area.innerHTML, da, a };
}

function puntoA(area, quanto) {
    const giro = document.createTreeWalker(area, NodeFilter.SHOW_TEXT);
    let n, resto = quanto, ultimo = null;

    while ((n = giro.nextNode())) {
        if (resto <= n.data.length) return [n, resto];
        resto -= n.data.length;
        ultimo = n;
    }

    return ultimo ? [ultimo, ultimo.data.length] : [area, area.childNodes.length];
}

function registra(st) {
    clearTimeout(st.timer);

    const s = cattura(st);
    const cima = st.storia[st.pos];

    if (cima && cima.html === s.html) { cima.da = s.da ?? cima.da; cima.a = s.a ?? cima.a; return; }

    st.storia.splice(st.pos + 1);
    st.storia.push(s);
    if (st.storia.length > 100) st.storia.shift();
    st.pos = st.storia.length - 1;
}

function applica(st, s) {
    const area = areaDi(st);
    area.innerHTML = s.html;

    if (s.da !== null && document.activeElement === area) {
        const r = document.createRange();
        r.setStart(...puntoA(area, s.da));
        r.setEnd(...puntoA(area, s.a ?? s.da));
        const sel = getSelection();
        sel.removeAllRanges();
        sel.addRange(r);
    }

    cambiato(st, false);
}

function annulla(st) {
    registra(st);
    if (st.pos > 0) applica(st, st.storia[--st.pos]);
}

function ripeti(st) {
    if (st.pos < st.storia.length - 1) applica(st, st.storia[++st.pos]);
}

// dopo ogni modifica: il campo, l'aspetto, e un input per chi ascolta - il postback con
// AutoPostBackDelay parte da li'
function cambiato(st, conCronologia = true) {
    normalizza(st);
    aggiornaCampo(st);

    if (conCronologia) registra(st);

    areaDi(st).dispatchEvent(new Event('input', { bubbles: true }));
    aggiornaStato(st);
}

// ---------------------------------------------------------------- la selezione
// Toccare un bottone sul telefono porta via il fuoco e con lui la selezione: la si tiene qui,
// aggiornata ad ogni movimento, e la si rimette prima di ogni comando.

document.addEventListener('selectionchange', () => {
    const sel = getSelection();
    if (!sel.rangeCount) return;

    const area = sel.anchorNode && (sel.anchorNode.nodeType === 1 ? sel.anchorNode : sel.anchorNode.parentElement)?.closest('.dw-rte-area');
    if (!area) return;

    const w = area.closest('[data-dw-rte]');
    const st = w && editori.get(w);
    if (!st) return;

    st.selezione = sel.getRangeAt(0).cloneRange();
    aggiornaStato(st);
});

function ripristina(st) {
    const area = areaDi(st);
    area.focus({ preventScroll: true });

    const sel = getSelection();

    if (st.selezione && area.contains(st.selezione.startContainer)) {
        sel.removeAllRanges();
        sel.addRange(st.selezione);
        return;
    }

    // mai scritto: il cursore in fondo
    const r = document.createRange();
    r.selectNodeContents(area);
    r.collapse(false);
    sel.removeAllRanges();
    sel.addRange(r);
}

function selezione(st) {
    const sel = getSelection();
    const area = areaDi(st);
    return sel.rangeCount && area.contains(sel.anchorNode) ? sel.getRangeAt(0) : null;
}

function elementoDi(nodo) {
    return nodo && (nodo.nodeType === 1 ? nodo : nodo.parentElement);
}

// l'elemento piu' vicino alla selezione che risponde al selettore, dentro l'area
function dentro(st, selettore) {
    const r = selezione(st);
    const el = r && elementoDi(r.startContainer)?.closest(selettore);
    return el && areaDi(st).contains(el) ? el : null;
}

const BLOCCHI = 'p,h1,h2,h3,h4,li,blockquote,div,td,th';

// i blocchi toccati dalla selezione, i piu' interni: e' su di loro che si mettono interlinea e rientro
function blocchi(st) {
    const area = areaDi(st);
    let r = selezione(st);
    if (!r) return [];

    const trova = () => [...area.querySelectorAll(BLOCCHI)]
        .filter(b => r.intersectsNode(b) && !b.querySelector(BLOCCHI));

    let trovati = trova();

    // del testo sciolto, direttamente nell'area: prima lo si mette in un paragrafo
    if (trovati.length === 0) {
        exec('formatBlock', '<p>');
        r = selezione(st);
        trovati = r ? trova() : [];
    }

    return trovati;
}

// ---------------------------------------------------------------- i comandi

function exec(comando, valore) {
    document.execCommand('styleWithCSS', false, false);
    return document.execCommand(comando, false, valore ?? null);
}

const SEMPLICI = {
    bold: 'bold', italic: 'italic', underline: 'underline', strikethrough: 'strikeThrough',
    superscript: 'superscript', subscript: 'subscript',
    alignleft: 'justifyLeft', aligncenter: 'justifyCenter', alignright: 'justifyRight', alignjustify: 'justifyFull',
    bulletedlist: 'insertUnorderedList', numberedlist: 'insertOrderedList', horizontalrule: 'insertHorizontalRule',
};

// il nome della funzione che ogni comando richiede: un comando spento non parte, nemmeno
// dalla tastiera
const FUNZIONE = {
    undo: 'UndoRedo', redo: 'UndoRedo', bold: 'Bold', italic: 'Italic', underline: 'Underline',
    strikethrough: 'Strikethrough', superscript: 'Superscript', subscript: 'Subscript',
    alignleft: 'Align', aligncenter: 'Align', alignright: 'Align', alignjustify: 'Align',
    bulletedlist: 'BulletedList', numberedlist: 'NumberedList', indent: 'Indent', outdent: 'Indent',
    quote: 'Quote', horizontalrule: 'HorizontalRule', clearformatting: 'ClearFormatting',
    source: 'SourceView', fullscreen: 'FullScreen',
    heading: 'Headings', fontname: 'FontName', fontsize: 'FontSize', forecolor: 'ForeColor',
    backcolor: 'BackColor', lineheight: 'LineHeight', link: 'Link', table: 'Table',
};

function comando(st, nome) {
    if (!acceso(st, FUNZIONE[nome])) return;

    if (nome === 'source') { sorgente(st); return; }
    if (nome === 'fullscreen') { schermoIntero(st); return; }
    if (st.w.classList.contains('js-dw-rte-sorgente')) return;

    if (nome === 'undo') { annulla(st); return; }
    if (nome === 'redo') { ripeti(st); return; }

    ripristina(st);
    registra(st);

    if (SEMPLICI[nome]) exec(SEMPLICI[nome]);
    else if (nome === 'indent' || nome === 'outdent') rientro(st, nome === 'indent' ? 1 : -1);
    else if (nome === 'quote') citazione(st);
    else if (nome === 'clearformatting') {
        exec('removeFormat');
        for (const b of blocchi(st)) b.removeAttribute('style');
    }

    cambiato(st);
}

function rientro(st, verso) {
    // negli elenchi il rientro e' l'annidamento, e lo sa fare il browser
    if (dentro(st, 'li')) { exec(verso > 0 ? 'indent' : 'outdent'); return; }

    for (const b of blocchi(st)) {
        const adesso = parseInt(b.style.marginLeft, 10) || 0;
        const nuovo = Math.max(0, Math.min(400, adesso + verso * 40));
        b.style.marginLeft = nuovo ? nuovo + 'px' : '';
        if (!b.getAttribute('style')) b.removeAttribute('style');
    }
}

function citazione(st) {
    const q = dentro(st, 'blockquote');

    if (!q) { exec('formatBlock', '<blockquote>'); return; }

    // la si toglie: il contenuto resta dov'era, senza la citazione attorno
    const primo = q.firstChild;
    q.replaceWith(...q.childNodes);
    if (primo && primo.nodeType === Node.TEXT_NODE) {
        const p = document.createElement('p');
        primo.replaceWith(p);
        p.appendChild(primo);
    }
}

// i <font> che scrivono i comandi del browser diventano span con lo stile, e gli span che non
// dicono piu' niente si sciolgono
const DIMENSIONI = { 1: 'x-small', 2: 'small', 3: 'medium', 4: 'large', 5: 'x-large', 6: 'xx-large' };

function normalizza(st) {
    const area = areaDi(st);

    for (const f of area.querySelectorAll('font')) {
        const s = document.createElement('span');
        const size = f.getAttribute('size');

        if (size) s.style.fontSize = size === '7' ? (st.dimensione || '') : (DIMENSIONI[size] || '');
        if (f.getAttribute('face')) s.style.fontFamily = f.getAttribute('face');
        if (f.getAttribute('color')) s.style.color = f.getAttribute('color');

        s.append(...f.childNodes);
        f.replaceWith(s);
    }

    for (const s of area.querySelectorAll('span[style]')) {
        const bg = s.style.backgroundColor;
        if (bg === 'transparent' || bg === 'rgba(0, 0, 0, 0)') s.style.backgroundColor = '';
        if (!s.getAttribute('style')) s.removeAttribute('style');
    }

    for (const s of area.querySelectorAll('span:not([style]),span[style=""]')) s.replaceWith(...s.childNodes);
}

// toglie una proprieta' di stile dagli span toccati dalla selezione: "Automatico", "Predefinito"
function togliStile(st, proprieta) {
    const area = areaDi(st);
    const r = selezione(st);
    if (!r) return;

    const toccati = [...area.querySelectorAll('span[style]')].filter(s => r.intersectsNode(s));
    const qui = elementoDi(r.startContainer)?.closest('span[style]');
    if (qui && area.contains(qui)) toccati.push(qui);

    for (const s of toccati) s.style[proprieta] = '';
}

// ---------------------------------------------------------------- i menu

const TITOLI = [['p', 'Paragrafo'], ['h1', 'Titolo 1'], ['h2', 'Titolo 2'], ['h3', 'Titolo 3'], ['h4', 'Titolo 4']];
const INTERLINEE = ['1', '1.15', '1.5', '2', '2.5', '3'];

function chiudiMenu(st) {
    if (!st.menu) return;

    st.menu.remove();
    st.menu = null;

    for (const b of st.w.querySelectorAll('[data-rte-menu][aria-expanded=true]')) b.setAttribute('aria-expanded', 'false');
}

function voce(testo, azione, attivo) {
    const b = document.createElement('button');
    b.type = 'button';
    b.className = 'dw-rte-voce' + (attivo ? ' js-dw-rte-attivo' : '');
    if (testo instanceof Node) b.append(testo); else b.textContent = testo;
    b.addEventListener('click', azione);
    return b;
}

function apriMenu(st, nome, bottone) {
    const eraAperto = st.menu && st.menu.dataset.nome === nome;
    chiudiMenu(st);
    if (eraAperto || !acceso(st, FUNZIONE[nome])) return;
    if (st.w.classList.contains('js-dw-rte-sorgente')) return;

    const m = document.createElement('div');
    m.className = 'dw-rte-menu';
    m.dataset.nome = nome;
    m.setAttribute('data-dw-client', '');
    m.setAttribute('role', 'menu');

    // quello che si scrive nel menu - il link, il colore - e' del menu: non deve far partire
    // il postback dell'editor, che ascolta input e change sul contenitore
    for (const evento of ['input', 'change']) m.addEventListener(evento, e => e.stopPropagation());

    const fatto = () => { chiudiMenu(st); areaDi(st).focus({ preventScroll: true }); };

    // ogni scelta: la selezione si rimette, si registra lo stato di prima, si agisce, si chiude
    const scelta = fn => () => { ripristina(st); registra(st); fn(); cambiato(st); fatto(); };

    ({
        heading: () => {
            const attuale = (dentro(st, 'p,h1,h2,h3,h4')?.localName) || 'p';
            for (const [tag, testo] of TITOLI) {
                const anteprima = document.createElement(tag);
                anteprima.textContent = testo;
                m.append(voce(anteprima, scelta(() => exec('formatBlock', '<' + tag + '>')), tag === attuale));
            }
        },
        fontname: () => {
            m.append(voce('Predefinito', scelta(() => togliStile(st, 'fontFamily'))));
            for (const f of st.cfg.fontNames) {
                const t = document.createElement('span');
                t.textContent = f;
                t.style.fontFamily = f;
                m.append(voce(t, scelta(() => exec('fontName', f))));
            }
        },
        fontsize: () => {
            m.append(voce('Predefinita', scelta(() => togliStile(st, 'fontSize'))));
            for (const px of st.cfg.fontSizes)
                m.append(voce(px + ' px', scelta(() => { st.dimensione = px + 'px'; exec('fontSize', '7'); })));
        },
        forecolor: () => colori(st, m, 'forecolor', scelta),
        backcolor: () => colori(st, m, 'backcolor', scelta),
        lineheight: () => {
            const attuale = dentro(st, BLOCCHI)?.style.lineHeight || '';
            m.append(voce('Predefinita', scelta(() => { for (const b of blocchi(st)) { b.style.lineHeight = ''; if (!b.getAttribute('style')) b.removeAttribute('style'); } }), attuale === ''));
            for (const v of INTERLINEE)
                m.append(voce(v.replace('.', ','), scelta(() => { for (const b of blocchi(st)) b.style.lineHeight = v; }), attuale === v));
        },
        link: () => menuLink(st, m, fatto),
        table: () => menuTabella(st, m, scelta),
    })[nome]();

    st.w.append(m);
    st.menu = m;
    bottone.setAttribute('aria-expanded', 'true');

    // sotto il bottone, senza uscire dall'editor. Sul telefono lo mette in basso il CSS
    const bw = st.w.getBoundingClientRect(), bb = bottone.getBoundingClientRect();
    m.style.top = (bb.bottom - bw.top + 4) + 'px';
    m.style.left = Math.max(0, Math.min(bb.left - bw.left, bw.width - m.offsetWidth)) + 'px';

    m.querySelector('input:not([type=checkbox]),button')?.focus({ preventScroll: true });
}

function colori(st, m, menu, scelta) {
    const testo = menu === 'forecolor';

    const imposta = colore => {
        if (testo) exec('foreColor', colore);
        else {
            document.execCommand('styleWithCSS', false, true);
            document.execCommand('hiliteColor', false, colore);
            document.execCommand('styleWithCSS', false, false);
        }
        st.ultimoColore = { ...(st.ultimoColore || {}), [menu]: colore };
    };

    m.append(voce(testo ? 'Automatico' : 'Nessuno', scelta(() => togliStile(st, testo ? 'color' : 'backgroundColor'))));

    const griglia = document.createElement('div');
    griglia.className = 'dw-rte-colori';

    for (const c of st.cfg.colors) {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'dw-rte-colore';
        b.style.background = c;
        b.title = c;
        b.setAttribute('aria-label', c);
        b.addEventListener('click', scelta(() => imposta(c)));
        griglia.append(b);
    }

    m.append(griglia);

    // un colore qualunque, con il selettore del sistema: sul telefono e' quello nativo
    const riga = document.createElement('label');
    riga.className = 'dw-rte-riga';
    const altro = document.createElement('input');
    altro.type = 'color';
    altro.addEventListener('change', scelta(() => imposta(altro.value)));
    riga.append(altro, document.createTextNode('Altro colore…'));
    m.append(riga);
}

function menuLink(st, m, fatto) {
    const esistente = dentro(st, 'a');
    const r = selezione(st) || st.selezione;
    const vuota = !r || r.collapsed;

    const campo = (etichetta, valore, tipo) => {
        const l = document.createElement('label');
        l.className = 'dw-rte-titolo';
        l.textContent = etichetta;
        const i = document.createElement('input');
        i.className = 'dw-rte-campo';
        i.type = tipo;
        i.value = valore;
        m.append(l, i);
        return i;
    };

    const url = campo('Indirizzo', esistente ? esistente.getAttribute('href') : '', 'url');
    url.placeholder = 'https://…  mail  telefono';
    url.inputMode = 'url';

    const testo = vuota && !esistente ? campo('Testo da mostrare', '', 'text') : null;

    const spunta = document.createElement('label');
    spunta.className = 'dw-rte-spunta';
    const nuova = document.createElement('input');
    nuova.type = 'checkbox';
    nuova.checked = !!esistente && esistente.target === '_blank';
    spunta.append(nuova, document.createTextNode('Apri in una nuova scheda'));
    m.append(spunta);

    const azioni = document.createElement('div');
    azioni.className = 'dw-rte-azioni';

    const bottone = (etichetta, primaria, azione) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'dw-rte-azione' + (primaria ? ' dw-rte-primaria' : '');
        b.textContent = etichetta;
        b.addEventListener('click', azione);
        azioni.append(b);
    };

    if (esistente) bottone('Rimuovi', false, () => {
        ripristina(st); registra(st);
        esistente.replaceWith(...esistente.childNodes);
        cambiato(st); fatto();
    });

    bottone('Annulla', false, fatto);

    const applicaLink = () => {
        const href = indirizzoScritto(url.value);
        if (!href) { url.setCustomValidity('Indirizzo non valido'); url.reportValidity(); return; }

        ripristina(st);
        registra(st);

        const bersaglio = a => {
            a.setAttribute('href', href);
            if (nuova.checked) { a.target = '_blank'; a.rel = 'noopener noreferrer'; }
            else { a.removeAttribute('target'); a.removeAttribute('rel'); }
        };

        if (esistente) bersaglio(esistente);
        else if (testo) {
            const a = document.createElement('a');
            a.textContent = testo.value.trim() || url.value.trim();
            bersaglio(a);
            exec('insertHTML', a.outerHTML);
        } else {
            exec('createLink', href);
            for (const a of areaDi(st).querySelectorAll('a[href]')) if (a.getAttribute('href') === href) bersaglio(a);
        }

        cambiato(st);
        fatto();
    };

    bottone('Applica', true, applicaLink);

    url.addEventListener('input', () => url.setCustomValidity(''));
    m.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); applicaLink(); } });

    m.append(azioni);
}

function menuTabella(st, m, scelta) {
    const cella = dentro(st, 'td,th');

    if (cella) {
        const riga = cella.parentElement;
        const tabella = cella.closest('table');
        const indice = [...riga.children].indexOf(cella);

        const nuovaCella = tag => { const c = document.createElement(tag); c.append(document.createElement('br')); return c; };
        const nuovaRiga = () => { const r = document.createElement('tr'); for (let i = 0; i < riga.children.length; i++) r.append(nuovaCella('td')); return r; };
        const righe = () => [...tabella.querySelectorAll('tr')];

        const azioni = [
            ['rows', 'Riga sopra', () => riga.before(nuovaRiga())],
            ['rows', 'Riga sotto', () => riga.after(nuovaRiga())],
            ['columns', 'Colonna a sinistra', () => { for (const r of righe()) r.children[indice]?.before(nuovaCella(r.children[indice].localName)); }],
            ['columns', 'Colonna a destra', () => { for (const r of righe()) r.children[indice]?.after(nuovaCella(r.children[indice].localName)); }],
            ['delete', 'Elimina riga', () => { if (righe().length > 1) riga.remove(); else tabella.remove(); }],
            ['delete', 'Elimina colonna', () => { if (riga.children.length > 1) for (const r of righe()) r.children[indice]?.remove(); else tabella.remove(); }],
            ['delete', 'Elimina tabella', () => tabella.remove()],
        ];

        for (const [icona, testo, fn] of azioni) {
            const t = document.createElement('span');
            const i = document.createElement('span');
            i.className = 'dw-rte-i dw-rte-i-' + icona;
            t.append(i, document.createTextNode(testo));
            if (icona === 'delete' && testo === 'Elimina riga') { const s = document.createElement('div'); s.className = 'dw-rte-sep'; m.append(s); }
            m.append(voce(t, scelta(fn)));
        }
        return;
    }

    // la griglia di Word: si passa sopra, si vede "3 × 4", si tocca
    const colonne = matchMedia('(pointer:coarse)').matches ? 6 : 8;
    const titolo = document.createElement('div');
    titolo.className = 'dw-rte-titolo';
    titolo.textContent = 'Inserisci tabella';
    const griglia = document.createElement('div');
    griglia.className = 'dw-rte-griglia';

    const segna = (rr, cc) => {
        titolo.textContent = rr ? rr + ' × ' + cc : 'Inserisci tabella';
        griglia.querySelectorAll('span').forEach((s, i) => s.classList.toggle('js-dw-rte-sopra', Math.floor(i / colonne) < rr && i % colonne < cc));
    };

    for (let i = 0; i < colonne * colonne; i++) {
        const s = document.createElement('span');
        const rr = Math.floor(i / colonne) + 1, cc = i % colonne + 1;
        s.setAttribute('role', 'button');
        s.setAttribute('aria-label', rr + ' righe per ' + cc + ' colonne');
        s.addEventListener('pointerenter', () => segna(rr, cc));
        s.addEventListener('pointerdown', () => segna(rr, cc));
        s.addEventListener('click', scelta(() => {
            const riga = '<tr>' + '<td><br></td>'.repeat(cc) + '</tr>';
            exec('insertHTML', '<table><tbody>' + riga.repeat(rr) + '</tbody></table><p><br></p>');
        }));
        griglia.append(s);
    }

    m.append(titolo, griglia);
}

// ---------------------------------------------------------------- HTML e schermo intero

function sorgente(st) {
    const w = st.w;
    let t = w.querySelector('.dw-rte-sorgente');
    chiudiMenu(st);

    if (!w.classList.contains('js-dw-rte-sorgente')) {
        if (!t) {
            t = document.createElement('textarea');
            t.className = 'dw-rte-sorgente';
            t.setAttribute('data-dw-client', '');
            t.spellcheck = false;
            t.addEventListener('input', () => {
                const v = pulisci(t.value, st.cfg.rules);
                campoDi(st).value = v;
                st.inviato = v;
            });
            areaDi(st).after(t);
        }

        // un a capo dopo ogni blocco: si legge, e non cambia niente di quello che si vede
        t.value = areaDi(st).innerHTML.replace(/(<\/(p|h[1-4]|li|blockquote|tr|table|ul|ol)>|<br>|<hr>)(?!\n)/g, '$1\n');
        t.style.minHeight = areaDi(st).offsetHeight + 'px';
        w.classList.add('js-dw-rte-sorgente');
        t.focus();
    } else {
        w.classList.remove('js-dw-rte-sorgente');
        areaDi(st).innerHTML = pulisci(t.value, st.cfg.rules);
        cambiato(st);
        areaDi(st).focus({ preventScroll: true });
    }

    aggiornaStato(st);
}

function schermoIntero(st) {
    const pieno = st.w.classList.toggle('js-dw-rte-pieno');
    document.documentElement.classList.toggle('js-dw-rte-bloccato', pieno);

    // cambiando pagina il blocco dello scorrimento deve andarsene con lei
    if (pieno) DW.onLeave(() => document.documentElement.classList.remove('js-dw-rte-bloccato'));

    areaDi(st).focus({ preventScroll: true });
}

// ---------------------------------------------------------------- lo stato dei bottoni

const STATI = {
    bold: 'bold', italic: 'italic', underline: 'underline', strikethrough: 'strikeThrough',
    superscript: 'superscript', subscript: 'subscript', bulletedlist: 'insertUnorderedList',
    numberedlist: 'insertOrderedList', alignleft: 'justifyLeft', aligncenter: 'justifyCenter',
    alignright: 'justifyRight', alignjustify: 'justifyFull',
};

function aggiornaStato(st) {
    const sorg = st.w.classList.contains('js-dw-rte-sorgente');

    for (const b of st.w.querySelectorAll('.dw-rte-barra [data-rte],.dw-rte-barra [data-rte-menu]')) {
        const nome = b.dataset.rte || b.dataset.rteMenu;

        b.disabled = !st.cfg.enabled || (sorg && nome !== 'source' && nome !== 'fullscreen')
            || (nome === 'undo' && st.pos <= 0 && st.storia[st.pos]?.html === areaDi(st).innerHTML)
            || (nome === 'redo' && st.pos >= st.storia.length - 1);

        let attivo = false;
        try {
            if (STATI[nome] && selezione(st)) attivo = document.queryCommandState(STATI[nome]);
        } catch (e) { /* un comando che il browser non conosce: spento */ }

        if (nome === 'quote') attivo = !!dentro(st, 'blockquote');
        if (nome === 'source') attivo = sorg;
        if (nome === 'link') attivo = !!dentro(st, 'a');

        b.classList.toggle('js-dw-rte-attivo', attivo);
    }
}

// ---------------------------------------------------------------- gli eventi

const editorDi = el => {
    const w = el instanceof Element ? el.closest('[data-dw-rte]') : null;
    return w ? statoDi(w) : null;
};

// un bottone toccato non deve portare via il fuoco all'area: la tastiera del telefono resta
// su e la selezione resta dov'e'. I campi dei menu invece il fuoco lo devono prendere
document.addEventListener('pointerdown', e => {
    const t = e.target instanceof Element ? e.target : null;
    if (!t) return;

    if (t.closest('.dw-rte-barra button,.dw-rte-menu button,.dw-rte-griglia span')) e.preventDefault();

    // fuori dal menu aperto, lo chiude
    for (const m of document.querySelectorAll('.dw-rte-menu')) {
        if (m.contains(t)) continue;
        const w = m.closest('[data-dw-rte]');
        const b = w && w.querySelector('[data-rte-menu=' + m.dataset.nome + ']');
        if (b && b.contains(t)) continue;
        if (w) chiudiMenu(statoDi(w));
    }
});

document.addEventListener('click', e => {
    const b = e.target instanceof Element ? e.target.closest('.dw-rte-barra button') : null;
    if (!b || b.disabled) return;

    const st = editorDi(b);
    if (!st || !st.cfg) return;

    e.preventDefault();

    try {
        if (b.dataset.rteMenu) apriMenu(st, b.dataset.rteMenu, b);
        else { chiudiMenu(st); comando(st, b.dataset.rte); }
    } catch (err) { DW.error('RichTextBox: ' + err); }
});

document.addEventListener('input', e => {
    const area = e.target instanceof Element ? e.target.closest('.dw-rte-area') : null;
    if (!area) return;

    const st = editorDi(area);
    if (!st || !st.cfg) return;

    // il fontSize del browser scrive <font size=7> anche sul testo scritto DOPO averlo scelto
    // col cursore fermo: lo si converte appena compare
    if (area.querySelector('font')) normalizza(st);

    aggiornaCampo(st);

    // la cronologia si segna quando ci si ferma, non ad ogni lettera
    clearTimeout(st.timer);
    st.timer = setTimeout(() => registra(st), 400);
});

document.addEventListener('focusin', e => {
    const area = e.target instanceof Element && e.target.classList.contains('dw-rte-area') ? e.target : null;
    if (!area) return;

    const st = editorDi(area);
    if (!st || !st.cfg || !st.cfg.enabled) return;

    // Invio fa un <p>, non un <div>: e' quello che il server tiene
    document.execCommand('defaultParagraphSeparator', false, 'p');

    if (st.alFocus === null) st.alFocus = campoDi(st).value;

    // l'area vuota: un paragrafo da cui partire, cosi' anche la prima riga e' un paragrafo
    if (!area.firstChild) {
        area.innerHTML = '<p><br></p>';
        const r = document.createRange();
        r.setStart(area.firstChild, 0);
        r.collapse(true);
        getSelection().removeAllRanges();
        getSelection().addRange(r);
    }
});

// con AutoPostBack senza ritardo il postback parte all'uscita, come il change di una casella:
// un contenteditable il change non ce l'ha, lo si fa qui. Uscire verso la barra o un menu
// dello stesso editor non e' uscire
document.addEventListener('focusout', e => {
    const area = e.target instanceof Element && e.target.classList.contains('dw-rte-area') ? e.target : null;
    if (!area) return;

    const st = editorDi(area);
    if (!st || !st.cfg || st.w.contains(e.relatedTarget)) return;

    registra(st);

    if (st.alFocus !== null && st.alFocus !== campoDi(st).value) st.w.dispatchEvent(new Event('change', { bubbles: true }));
    st.alFocus = null;
});

document.addEventListener('keydown', e => {
    const t = e.target instanceof Element ? e.target : null;
    const st = t && t.closest('.dw-rte') ? editorDi(t) : null;
    if (!st || !st.cfg) return;

    if (e.key === 'Escape') {
        if (st.menu) { chiudiMenu(st); areaDi(st).focus({ preventScroll: true }); e.preventDefault(); return; }
        if (st.w.classList.contains('js-dw-rte-pieno')) { schermoIntero(st); e.preventDefault(); }
        return;
    }

    if (!t.classList.contains('dw-rte-area')) return;

    const mod = e.ctrlKey || e.metaKey;
    const tasto = e.key.toLowerCase();

    // Tab negli elenchi: rientro, come in Word. Fuori resta il Tab di sempre, che porta al
    // campo dopo - chiuderci dentro chi usa la tastiera sarebbe peggio
    if (e.key === 'Tab' && !mod && acceso(st, 'Indent') && dentro(st, 'li')) {
        e.preventDefault();
        comando(st, e.shiftKey ? 'outdent' : 'indent');
        return;
    }

    if (!mod || e.altKey) return;

    let nome = null;

    if (!e.shiftKey) nome = { b: 'bold', i: 'italic', u: 'underline', z: 'undo', y: 'redo', l: 'alignleft', e: 'aligncenter', r: 'alignright', j: 'alignjustify', k: 'link' }[tasto];
    else nome = { z: 'redo' }[tasto] || { Digit7: 'numberedlist', Digit8: 'bulletedlist' }[e.code];

    if (!nome) return;

    // la scorciatoia e' nostra in ogni caso: spenta non deve fare niente, e Ctrl+R non deve
    // ricaricare la pagina mentre si scrive
    e.preventDefault();

    if (nome === 'link') { const b = st.w.querySelector('[data-rte-menu=link]'); if (b && acceso(st, 'Link')) apriMenu(st, 'link', b); return; }

    // con Annulla spento resta l'annulla del browser: e' meglio di niente
    if ((nome === 'undo' || nome === 'redo') && !acceso(st, 'UndoRedo')) { document.execCommand(nome); return; }

    comando(st, nome);
});

// quello che arriva dalla tastiera del telefono e dai menu del sistema: la B del correttore di
// iOS, l'annulla scuotendo, il limite di lunghezza
document.addEventListener('beforeinput', e => {
    const area = e.target instanceof Element && e.target.classList.contains('dw-rte-area') ? e.target : null;
    if (!area) return;

    const st = editorDi(area);
    if (!st || !st.cfg) return;

    const formato = { formatBold: 'Bold', formatItalic: 'Italic', formatUnderline: 'Underline', formatStrikeThrough: 'Strikethrough',
        formatSuperscript: 'Superscript', formatSubscript: 'Subscript', insertOrderedList: 'NumberedList', insertUnorderedList: 'BulletedList',
        formatIndent: 'Indent', formatOutdent: 'Indent', formatFontColor: 'ForeColor', formatBackColor: 'BackColor', insertLink: 'Link',
        insertHorizontalRule: 'HorizontalRule' }[e.inputType];

    if (formato && !acceso(st, formato)) { e.preventDefault(); return; }

    if ((e.inputType === 'historyUndo' || e.inputType === 'historyRedo') && acceso(st, 'UndoRedo')) {
        e.preventDefault();
        if (e.inputType === 'historyUndo') annulla(st); else ripeti(st);
        return;
    }

    // oltre il limite non si scrive. La composizione (tastiere del telefono, IME) non si puo'
    // fermare senza rompere la parola a meta': li' si lascia fare e il conto diventa rosso
    if (st.cfg.maxLength > 0 && e.inputType === 'insertText' && [...testoDi(area)].length >= st.cfg.maxLength) {
        const r = selezione(st);
        if (!r || r.collapsed) e.preventDefault();
    }
});

// l'incolla: quello che arriva da Word, da una pagina web, da un'altra app, si ripulisce con le
// regole del server PRIMA di entrare. Il testo semplice resta testo, con gli a capo
document.addEventListener('paste', e => {
    const area = e.target instanceof Element ? e.target.closest('.dw-rte-area') : null;
    if (!area) return;

    const st = editorDi(area);
    if (!st || !st.cfg || !e.clipboardData) return;

    const html = e.clipboardData.getData('text/html');
    const testo = e.clipboardData.getData('text/plain');

    e.preventDefault();
    registra(st);

    let pezzo = html ? pulisci(html, st.cfg.rules) : '';

    if (!pezzo.trim()) {
        const paragrafi = testo.replace(/\r\n?/g, '\n').split(/\n{2,}/);
        pezzo = paragrafi.length > 1
            ? paragrafi.map(p => '<p>' + esc(p).replace(/\n/g, '<br>') + '</p>').join('')
            : esc(testo).replace(/\n/g, '<br>');
    }

    exec('insertHTML', pezzo);
    cambiato(st);
});

// la prima chiamata la fa il motore, appena questo file e' caricato: vedi armaEditor in runtime.js
DW.rte = { arma, pulisci, indirizzo, indirizzoScritto, conto, valore };

})();
