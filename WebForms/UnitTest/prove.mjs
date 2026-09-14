// Le prove del pezzo che gira nel browser, senza browser.
//
// runtime.js e' un file solo, scritto per il DOM; qui se ne ritagliano i pezzi che hanno una
// logica propria - il cassetto delle pagine tenute, la navigazione, la consegna dei messaggi -
// e si fanno girare con un DOM finto grande quanto basta. Non e' il morph, che si guarda nel
// browser: e' quello che si puo' sbagliare senza che il browser lo dica.
//
// Le lancia Esegui.php insieme alle prove PHP, se c'e' node; da sola: node prove.mjs
// Stesso formato di uscita di Prova.php, cosi' si leggono insieme.

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const qui = path.dirname(fileURLToPath(import.meta.url));
const sorgente = fs.readFileSync(path.join(qui, '..', 'runtime.js'), 'utf8');

let fatte = 0;
const rosse = [];

function uguale(nome, atteso, ottenuto) {
    fatte++;
    const a = JSON.stringify(atteso), o = JSON.stringify(ottenuto);
    if (a === o) { console.log('ok      ' + nome); return; }
    rosse.push(nome);
    console.log('ROSSA   ' + nome + '\n        atteso:   ' + a + '\n        ottenuto: ' + o);
}

/** Il pezzo di runtime.js fra due marcatori, come funzione con le dipendenze passate a mano. */
function ritaglio(da, a, dipendenze) {
    const i = sorgente.indexOf(da), j = sorgente.indexOf(a, i);
    if (i < 0 || j < 0) throw new Error('marcatore non trovato: ' + (i < 0 ? da : a));
    const corpo = sorgente.slice(i, j + a.length);
    const nomi = Object.keys(dipendenze);
    return new Function(...nomi, corpo + '\nreturn { ' + (dipendenze.__esporta || '') + ' };')(...nomi.map(n => dipendenze[n]));
}

// ---------------------------------------------------------------- il cassetto: tetto in byte
{
    console.log('\n--- javascript: il cassetto delle pagine tenute ---');

    let stato = '', tieni = true, corrente = 'http://x/a';
    const scena = {
        radice: () => ({ dataset: { dwTieni: tieni ? '1' : undefined } }),
        campoStato: () => ({ value: stato }),
        location: { href: 'http://x/' },
        console: { info: () => {} },
        __esporta: 'tieniStato, tenute, byte: () => tenuteByte, vai: (u) => { paginaCorrente = u; }',
    };

    const s = ritaglio('const TENUTE_BYTE', 'dimentica(tenute.keys().next().value);\n};', scena);   // fino alla fine di tieniStato
    // il tetto e' 50 MB: per la prova lo si abbassa dal di fuori non si puo', quindi si misura
    // la logica con pagine grandi
    const K = 'x'.repeat(1024 * 1024);                            // 1 MB

    const lascia = (url, contenuto, mantieni = true) => { s.vai(url); stato = contenuto; tieni = mantieni; s.tieniStato(); };

    for (let i = 0; i < 49; i++) lascia('http://x/p' + i, K);
    uguale('49 pagine da 1 MB stanno sotto il tetto', 49, s.tenute.size);

    lascia('http://x/p49', K); lascia('http://x/p50', K);
    uguale('alla 51esima cade la piu\' vecchia: si resta a 50', 50, s.tenute.size);
    uguale('ed e\' caduta p0, la prima entrata', false, s.tenute.has('http://x/p0'));

    lascia('http://x/p1', 'piccola');
    uguale('riscrivere una pagina la rimette in fondo e aggiorna i byte', true, [...s.tenute.keys()].at(-1) === 'http://x/p1' && s.byte() < 50 * 1024 * 1024);

    lascia('http://x/p2', 'x', false);
    uguale('una pagina che smette di tenersi esce dal cassetto', false, s.tenute.has('http://x/p2'));

    lascia('http://x/q?mese=2026-09', 'con-qs');
    uguale('la chiave e\' il percorso, senza querystring', true, s.tenute.has('http://x/q'));
}

// ---------------------------------------------------------------- la navigazione e il tasto indietro
{
    console.log('\n--- javascript: navigazione, e il tasto indietro ---');

    // un DOM finto: la radice con dwTieni, il campo dello stato, il documento che arriva
    const location = { href: 'http://x/Tabella.php' };
    let campo = { value: 'STATO-TABELLA' };
    const richieste = [];
    const documenti = {
        'http://x/Stato.php':   { titolo: 'Stato',   stato: 'STATO-STATO' },
        'http://x/Tabella.php': { titolo: 'Tabella', stato: 'STATO-TABELLA-DAL-SERVER' },
    };

    const scena = {
        location,
        window: { DW_CSRF: 'csrf' },
        history: { pushState: () => {} },
        scrollTo: () => {},
        CustomEvent: class { constructor(n) { this.type = n; } },
        URLSearchParams: URLSearchParams,
        URL: URL,
        document: { body: { classList: { add() {}, remove() {} } }, dispatchEvent() {}, title: '' },
        DOMParser: class {
            parseFromString(testo) {
                const d = JSON.parse(testo);
                return { title: d.titolo, getElementById: (id) => id === 'dw-root' ? { outerHTML: '<div id="dw-root"></div>' } : { value: d.stato } };
            }
        },
        fetch: async (url, opzioni) => {
            richieste.push({ url: new URL(url, location.href).href, metodo: opzioni.method || 'GET', stato: opzioni.body ? opzioni.body.get('__dw_state') : null });
            const d = documenti[new URL(url, location.href).href];
            return { ok: true, redirected: false, text: async () => JSON.stringify(d) };
        },
        radice: () => ({ dataset: { dwTieni: '1' } }),
        campoStato: () => campo,
        portatile: '',
        inCorso: Promise.resolve(),
        DW: { teardown() {}, morph() {} },
        leggiPortatile: () => {},
        armaAvvisi: () => {},
        eseguiScript: () => {},
        console: { info: () => {} },
        __esporta: 'tenute, navigate: DW.navigate',
    };

    const s = ritaglio('const TENUTE_BYTE', "document.dispatchEvent(new CustomEvent('dw:pagina'));\n};", scena);

    // 1. da Tabella si clicca il link verso Stato
    await s.navigate('Stato.php', true);

    uguale('lasciando Tabella il suo stato finisce nel SUO cassetto', 'STATO-TABELLA', s.tenute.get('http://x/Tabella.php'));
    uguale('verso una pagina mai vista si fa una GET', 'GET', richieste[0].metodo);
    uguale('e il campo dello stato ora e\' quello di Stato', 'STATO-STATO', campo.value);

    // 2. il tasto INDIETRO: il browser cambia l'indirizzo PRIMA di avvisare, e solo poi parte
    //    la navigazione verso quell'indirizzo - il DOM e' ancora quello di Stato
    location.href = 'http://x/Tabella.php';

    await s.navigate(location.href, false);

    uguale('sull\'indietro lo stato di Stato va nel cassetto di STATO, non in quello dell\'indirizzo nuovo',
        'STATO-STATO', s.tenute.get('http://x/Stato.php'));
    uguale('e il cassetto di Tabella e\' ancora il suo', 'STATO-TABELLA', s.tenute.get('http://x/Tabella.php'));
    uguale('il ritorno su Tabella e\' un ripristino: POST con il SUO stato', { metodo: 'POST', stato: 'STATO-TABELLA' },
        { metodo: richieste[1].metodo, stato: richieste[1].stato });

    // 3. avanti di nuovo: anche Stato torna col suo
    location.href = 'http://x/Stato.php';

    await s.navigate(location.href, false);

    uguale('e avanti su Stato e\' un ripristino con lo stato di Stato', 'STATO-STATO', richieste[2].stato);

    // 4. "aggiungo una riga e clicco il link": il postback e' ancora in volo quando parte la
    //    navigazione. Lo stato da tenere e' quello di DOPO il postback, non quello di prima.
    const corsa = { ...scena, location: { href: 'http://x/Tabella.php' },
        inCorso: new Promise(r => setTimeout(() => { campo.value = 'STATO-CON-LA-RIGA'; r(); }, 30)) };
    campo = { value: 'STATO-SENZA-LA-RIGA' };

    const s2 = ritaglio('const TENUTE_BYTE', "document.dispatchEvent(new CustomEvent('dw:pagina'));\n};", corsa);

    await s2.navigate('Stato.php', true);

    uguale('con un postback in volo, la navigazione aspetta e tiene lo stato di DOPO',
        'STATO-CON-LA-RIGA', s2.tenute.get('http://x/Tabella.php'));
}

// ---------------------------------------------------------------- i messaggi con dati: DW.on
{
    console.log('\n--- javascript: DW.on e la consegna dei messaggi ---');

    const errori = [];
    let push = 'PAGINA-A';
    const scena = {
        DW: { error: m => errori.push(m) },
        radice: () => ({ dataset: { dwPush: push } }),
        __esporta: 'on: DW.on, off: DW.off, deliver: DW.deliver, teardown: DW.teardown',
    };

    const s = ritaglio('let uscite = [];', 'DW.deliver = consegna;', scena);

    const ricevuti = [];
    const via = s.on('Prezzo', d => ricevuti.push('pagina:' + d.valore));
    s.on('Prezzo', d => ricevuti.push('sempre:' + d.valore), { sempre: true });

    s.deliver('Prezzo', JSON.stringify({ o: '', d: { valore: 1 } }));
    s.deliver('Prezzo', JSON.stringify({ o: 'PAGINA-A', d: { valore: 2 } }));
    s.deliver('Prezzo', JSON.stringify({ o: 'PAGINA-B', d: { valore: 3 } }));
    s.deliver('Altro', JSON.stringify({ o: '', d: { valore: 4 } }));
    s.deliver('Prezzo', 'non json');
    via();
    s.deliver('Prezzo', JSON.stringify({ o: '', d: { valore: 5 } }));
    s.teardown();
    s.deliver('Prezzo', JSON.stringify({ o: '', d: { valore: 6 } }));

    uguale('a tutti arriva; al mittente che si e\' escluso no; un altro mittente si', ['pagina:1', 'sempre:1', 'pagina:3', 'sempre:3'], ricevuti.slice(0, 4));
    uguale('tolto un ascolto, resta l\'altro', 'sempre:5', ricevuti[4]);
    uguale('dopo il cambio pagina resta solo quello "per sempre"', 'sempre:6', ricevuti[5]);
    uguale('un messaggio che non e\' JSON e\' un errore in console, non un\'eccezione', 1, errori.length);
}

console.log('');

if (rosse.length === 0) {
    console.log('tutte verdi: ' + fatte + ' prove javascript');
} else {
    console.log(rosse.length + ' rosse su ' + fatte + ' javascript:\n  ' + rosse.join('\n  '));
    process.exit(1);
}
