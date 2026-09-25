// Le prove del pezzo che gira nel browser, senza browser.
//
// runtime.js e' un file solo, scritto per il DOM; qui se ne ritagliano i pezzi che hanno una
// logica propria - la navigazione, gli script, la consegna dei messaggi, l'editor -
// e si fanno girare con un DOM finto grande quanto basta. Non e' il morph, che si guarda nel
// browser: e' quello che si puo' sbagliare senza che il browser lo dica.
//
// Le lancia Esegui.php insieme alle prove PHP, se c'e' node; da sola: node prove.mjs
// Stesso formato di uscita di Prova.php, cosi' si leggono insieme.

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const qui = path.dirname(fileURLToPath(import.meta.url));
// i fine riga si normalizzano: il checkout su Windows li fa diventare CRLF, e i marcatori dei
// ritagli sono scritti con il solo a capo: senza, ogni ritaglio si ferma a "marcatore non trovato"
const sorgente = fs.readFileSync(path.join(qui, '..', 'runtime.js'), 'utf8').replace(/\r\n/g, '\n');

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

// ---------------------------------------------------------------- la navigazione e il tasto indietro
{
    console.log('\n--- javascript: navigazione, e il tasto indietro ---');

    // un DOM finto: il campo dello stato, il documento che arriva
    const location = { href: 'http://x/Elenco.php' };
    const campo = { value: 'STATO-ELENCO-CON-I-DATI-DI-PRIMA' };
    const richieste = [];
    const documenti = {
        'http://x/Scheda.php': { titolo: 'Scheda', stato: 'STATO-SCHEDA' },
        'http://x/Elenco.php': { titolo: 'Elenco', stato: 'STATO-ELENCO-NUOVO' },
    };

    const scena = {
        location,
        window: { DW_CSRF: 'csrf' },
        history: { pushState: () => {} },
        scrollTo: () => {},
        CustomEvent: class { constructor(n) { this.type = n; } },
        URL: URL,
        document: { body: { classList: { add() {}, remove() {} } }, dispatchEvent() {}, title: '' },
        DOMParser: class {
            parseFromString(testo) {
                const d = JSON.parse(testo);
                return { title: d.titolo, getElementById: (id) => id === 'dw-root' ? { outerHTML: '<div id="dw-root"></div>' } : { value: d.stato } };
            }
        },
        fetch: async (url, opzioni) => {
            richieste.push({ url: new URL(url, location.href).href, metodo: opzioni.method || 'GET', corpo: opzioni.body || null, portatile: opzioni.headers['X-DW-Portable'] });
            const d = documenti[new URL(url, location.href).href];
            return { ok: true, redirected: false, text: async () => JSON.stringify(d) };
        },
        radice: () => ({}),
        campoStato: () => campo,
        portatile: 'PACCHETTO-PORTATILE',
        inCorso: Promise.resolve(),
        DW: { teardown() {}, morph() {} },
        leggiPortatile: () => {},
        armaAvvisi: () => {},
        armaPopup: () => {},
        armaEditor: () => {},
        riepilogoStato: () => {},
        eseguiScript: () => {},
        __esporta: 'navigate: DW.navigate',
    };

    const s = ritaglio('DW.navigate = async', "document.dispatchEvent(new CustomEvent('dw:pagina'));\n};", scena);

    // 1. dall'elenco si apre la scheda
    await s.navigate('Scheda.php', true);

    uguale('verso un\'altra pagina si fa una GET, senza corpo', { metodo: 'GET', corpo: null }, { metodo: richieste[0].metodo, corpo: richieste[0].corpo });
    uguale('il pacchetto #[Portable] parte con la navigazione', 'PACCHETTO-PORTATILE', richieste[0].portatile);
    uguale('e il campo dello stato ora e\' quello della scheda', 'STATO-SCHEDA', campo.value);

    // 2. si salva nella scheda e si torna all'elenco col tasto INDIETRO: il browser cambia
    //    l'indirizzo e il runtime naviga verso quell'indirizzo
    location.href = 'http://x/Elenco.php';

    await s.navigate(location.href, false);

    uguale('tornare sull\'elenco e\' una GET: nessuno stato di prima rimandato al server',
        { metodo: 'GET', corpo: null }, { metodo: richieste[1].metodo, corpo: richieste[1].corpo });
    uguale('anche sull\'indietro il pacchetto #[Portable] viaggia', 'PACCHETTO-PORTATILE', richieste[1].portatile);
    uguale('e l\'elenco riparte dallo stato nuovo del server, con i dati riletti', 'STATO-ELENCO-NUOVO', campo.value);

    // 3. "salvo e clicco subito il link": il postback e' ancora in volo quando parte la
    //    navigazione. La navigazione aspetta: la risposta del postback non deve finire fusa
    //    nella pagina nuova, e il Portable che porta con se' deve partire con il link
    const ordine = [];
    const corsa = { ...scena,
        inCorso: new Promise(r => setTimeout(() => { ordine.push('postback finito'); r(); }, 30)),
        fetch: async (url, opzioni) => { ordine.push('navigazione partita'); return scena.fetch(url, opzioni); } };

    const s2 = ritaglio('DW.navigate = async', "document.dispatchEvent(new CustomEvent('dw:pagina'));\n};", corsa);

    await s2.navigate('Scheda.php', true);

    uguale('con un postback in volo, la navigazione parte solo dopo', ['postback finito', 'navigazione partita'], ordine);
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

// ---------------------------------------------------------------- gli script di una pagina raggiunta navigando
{
    console.log('\n--- javascript: gli script dopo una navigazione ---');

    // un DOM finto: gli script gia' in pagina, e quelli che il morph ha appena messo
    const creati = [];
    const nodo = (src, testo) => ({ src: src || '', textContent: testo || '', type: '', sostituito: null, replaceWith(n) { this.sostituito = n; } });

    const scena = {
        document: {
            scripts: [nodo('http://x/Common/WebForms/runtime.js?v=1'), nodo('http://x/Layouts/Sito.js?v=5')],
            createElement: () => { const n = { src: '', textContent: '', type: '', async: true }; creati.push(n); return n; },
        },
        __esporta: 'eseguiScript',
    };

    const s = ritaglio('const scriptCaricati', "        vecchio.replaceWith(nuovo);\n    }\n}", scena);

    const inline  = nodo('', 'console.log(1)');
    const nuovo   = nodo('http://x/Common/WebForms/Examples/Resources.js?v=9');
    const vecchio = nodo('http://x/Layouts/Sito.js?v=6');            // marca diversa, stesso file: e' gia' in memoria

    s.eseguiScript({ querySelectorAll: () => [inline, nuovo, vecchio] });

    uguale('uno script inline si ricrea, cosi\' il browser lo esegue', 'console.log(1)', inline.sostituito && inline.sostituito.textContent);
    uguale('uno script esterno mai visto si carica, in ordine', { src: 'http://x/Common/WebForms/Examples/Resources.js?v=9', async: false },
        { src: nuovo.sostituito && nuovo.sostituito.src, async: nuovo.sostituito && nuovo.sostituito.async });
    uguale('uno gia\' caricato al primo arrivo NON si ricarica, anche con una marca diversa', null, vecchio.sostituito);

    s.eseguiScript({ querySelectorAll: () => [nodo('http://x/Common/WebForms/Examples/Resources.js?v=9')] });

    uguale('e la seconda volta nemmeno quello nuovo: e\' in memoria', 2, creati.length);
}

// ---------------------------------------------------------------- il 500 di PHP, leggibile
{
    console.log('\n--- javascript: l\'errore del server, com\'e\' arrivato ---');

    // il DOMParser finto fa quello che fa il vero su un testo: via i tag, entita' decodificate
    const scena = {
        DOMParser: class {
            parseFromString(html) {
                return { body: { textContent: html.replace(/<[^>]+>/g, '').replace(/&gt;/g, '>').replace(/&lt;/g, '<') } };
            }
        },
        __esporta: 'testoErrore',
    };

    const s = ritaglio('function testoErrore', "+ '…' : testo;\n}", scena);

    uguale('il corpo HTML di PHP diventa una riga di testo, con le frecce vere',
        'Fatal error: Uncaught TypeError: x in Page.php:589 Stack trace: #0 Page->Run()',
        s.testoErrore('<br />\n<b>Fatal error</b>:  Uncaught TypeError: x in Page.php:589\nStack trace:\n#0 Page-&gt;Run()'));

    uguale('e non piu\' di 600 caratteri: lo stack intero sta nel log', 601, s.testoErrore('x'.repeat(2000)).length);
}

// ---------------------------------------------------------------- l'editor: il caricamento, una volta sola
{
    console.log('\n--- javascript: RichTextBox, il caricamento ---');

    // tre editor in pagina: lo script si chiede UNA volta, e ad ogni render si riarma
    const aggiunti = [];
    let arma = 0, errori = [];
    const DW = { error: m => errori.push(m) };
    const primo = { dataset: { dwRteJs: '/public/php/Common/WebForms/RichTextBox/RichTextBox.js?v=1' } };
    let editori = [primo, {}, {}];

    const scena = {
        DW,
        document: {
            querySelector: sel => (sel === '[data-dw-rte-js]' ? editori[0] || null : null),
            createElement: () => ({}),
            head: { appendChild: s => aggiunti.push(s) },
        },
        __esporta: 'armaEditor',
    };

    const s = ritaglio('let editorPronto = null;', "DW.error('RichTextBox: ' + e.message));\n}", scena);

    s.armaEditor();
    s.armaEditor();
    s.armaEditor();

    uguale('tre render con tre editor: lo script si chiede una volta sola', 1, aggiunti.length);
    uguale('con l\'indirizzo che il controllo ha scritto, marca temporale compresa', primo.dataset.dwRteJs, aggiunti[0].src);

    DW.rte = { arma: () => arma++ };
    aggiunti[0].onload();
    await new Promise(fatto => setTimeout(fatto, 0));

    uguale('caricato, ogni render chiede di riarmare', 3, arma);

    s.armaEditor();
    await new Promise(fatto => setTimeout(fatto, 0));
    uguale('e i render dopo riarmano senza ricaricare', [4, 1], [arma, aggiunti.length]);

    // uno script che non arriva: si riprova al render dopo, invece di restare senza editor
    const aggiunti2 = [];
    const scena2 = { ...scena, DW: { error: m => errori.push(m) }, document: { ...scena.document, head: { appendChild: x => aggiunti2.push(x) } } };
    const s2 = ritaglio('let editorPronto = null;', "DW.error('RichTextBox: ' + e.message));\n}", scena2);

    s2.armaEditor();
    aggiunti2[0].onerror();
    await new Promise(fatto => setTimeout(fatto, 0));

    uguale('se lo script non arriva lo si dice', true, errori.some(e => e.includes('non riesco a caricare')));

    s2.armaEditor();
    uguale('e al render dopo lo si richiede', 2, aggiunti2.length);

    editori = [];
    const prima = aggiunti.length;
    s.armaEditor();
    uguale('una pagina senza editor non carica niente', prima, aggiunti.length);
}

// ---------------------------------------------------------------- l'editor: piu' editor nella stessa pagina
{
    console.log('\n--- javascript: RichTextBox, piu\' editor insieme ---');

    const rte = caricaEditor();

    // un editor finto, grande quanto serve ad arma(): il contenitore, l'area, il campo nascosto
    const editore = (id, html) => {
        const area = {
            innerHTML: html, innerText: html.replace(/<[^>]+>/g, ''), style: {}, dataset: {}, contentEditable: '',
            querySelector: () => null, contains: n => n === area,
        };
        Object.defineProperty(area, 'innerText', { get: () => area.innerHTML.replace(/<[^>]+>/g, '') });
        const campo = { value: html };
        const conto = { textContent: '' };
        const classi = new Set();
        const w = {
            id, area, campo,
            dataset: { dwRte: JSON.stringify({ placeholder: 'p-' + id, minHeight: 100, maxHeight: 0, maxLength: id === 'b' ? 5 : 0, counter: true, enabled: true, features: ['Bold'] }) },
            classList: { toggle: (c, si) => (si ? classi.add(c) : classi.delete(c)), contains: c => classi.has(c), classi },
            querySelector: sel => ({ '.dw-rte-area': area, 'input[type=hidden]': campo, '.dw-rte-conto': conto }[sel] || null),
            querySelectorAll: () => [],
            conto,
        };
        return w;
    };

    const a = editore('a', '<p>uno</p>'), b = editore('b', '<p>duetre</p>'), c = editore('c', '');
    rte.pagina.editori = [a, b, c];

    rte.DW.rte.arma();

    uguale('ogni editor prende la sua configurazione', ['p-a', 'p-b', 'p-c'], [a.area.dataset.placeholder, b.area.dataset.placeholder, c.area.dataset.placeholder]);
    uguale('ognuno il suo conto', ['1 parola, 3 caratteri', '1 parola, 6 / 5 caratteri', '0 parole, 0 caratteri'], [a.conto.textContent, b.conto.textContent, c.conto.textContent]);
    uguale('il limite e\' solo di chi ce l\'ha: rosso solo il secondo', [false, true, false], [a, b, c].map(w => w.classList.contains('js-dw-rte-troppo')));
    uguale('vuoto solo il terzo', [false, false, true], [a, b, c].map(w => w.classList.contains('js-dw-rte-vuoto')));

    // il server cambia il testo del SECONDO: arma() rimette quello, e basta
    b.campo.value = '<p>dal server</p>';
    rte.DW.rte.arma();

    uguale('il server cambia un editor: cambia quello', '<p>dal server</p>', b.area.innerHTML);
    uguale('gli altri restano come sono', ['<p>uno</p>', ''], [a.area.innerHTML, c.area.innerHTML]);

    // l'utente sta scrivendo nel PRIMO mentre torna un postback col valore vecchio
    a.area.innerHTML = '<p>uno e mezzo</p>';
    rte.pagina.fuoco = a.area;
    a.campo.value = '<p>la versione di prima, dal server</p>';
    rte.DW.rte.arma();

    uguale('dentro l\'editor vince quello che si sta scrivendo', '<p>uno e mezzo</p>', a.area.innerHTML);
    uguale('e il campo non tiene il valore vecchio del server', false, a.campo.value === '<p>la versione di prima, dal server</p>');

    rte.pagina.fuoco = null;
    c.campo.value = '<p>anche il terzo</p>';
    rte.DW.rte.arma();

    uguale('col fuoco altrove, il server vince sul terzo', '<p>anche il terzo</p>', c.area.innerHTML);
    uguale('senza toccare il primo, che ha il suo testo', '<p>uno e mezzo</p>', a.area.innerHTML);
}

// ---------------------------------------------------------------- l'editor: le stesse regole del server
{
    console.log('\n--- javascript: RichTextBox, le regole ---');

    const { DW } = caricaEditor();
    const r = { tags: { u: [], s: [], sup: [] }, fonts: ['Arial', 'Times New Roman'] };

    uguale('javascript: nel link non passa', '', DW.rte.indirizzo('javascript:alert(1)'));
    uguale('nemmeno col tab in mezzo', '', DW.rte.indirizzo('java\tscript:alert(1)'));
    uguale('nemmeno con le maiuscole e gli spazi davanti', '', DW.rte.indirizzo('  JaVaScRiPt:alert(1)'));
    uguale('data: non passa', '', DW.rte.indirizzo('data:text/html,x'));
    uguale('https passa', 'https://doweb.it/a?b=1', DW.rte.indirizzo('https://doweb.it/a?b=1'));
    uguale('//altro.sito si scrive per intero', 'https://altro.sito', DW.rte.indirizzo('//altro.sito'));

    uguale('scritto "doweb.it" e\' https://doweb.it', 'https://doweb.it', DW.rte.indirizzoScritto('doweb.it'));
    uguale('una mail diventa mailto:', 'mailto:info@doweb.srl', DW.rte.indirizzoScritto('info@doweb.srl'));
    uguale('un telefono diventa tel:, senza spazi', 'tel:+39045123456', DW.rte.indirizzoScritto('+39 045 123456'));
    uguale('un percorso del sito resta', '/contatti', DW.rte.indirizzoScritto('/contatti'));

    uguale('colore buono', '#ff0000', DW.rte.valore('color', '#FF0000', r));
    uguale('url() nel colore no', '', DW.rte.valore('background-color', 'url(x)', r));
    uguale('trasparente no', '', DW.rte.valore('background-color', 'transparent', r));
    uguale('il carattere dell\'elenco, scritto bene', "'Times New Roman'", DW.rte.valore('font-family', '"times new roman", serif', r));
    uguale('quello fuori elenco no', '', DW.rte.valore('font-family', 'Calibri', r));
    uguale('interlinea fuori misura no', '', DW.rte.valore('line-height', '40', r));
    uguale('rientro oltre 400 no', '', DW.rte.valore('margin-left', '4000px', r));
    uguale('text-align start e\' left, come sul server', 'left', DW.rte.valore('text-align', 'start', r));

    uguale('il conto, come quello del server', '5 parole, 29 / 2000 caratteri', DW.rte.conto("Ciao mondo, l'albero è verde.", 2000));
    uguale('senza limite', '1 parola, 1 carattere', DW.rte.conto('a', 0));
}

/**
 * RichTextBox.js caricato in un mondo finto: un document che raccoglie i listener e risponde
 * agli editor che la prova gli mette in mano. Basta per arma() e per le funzioni pure.
 */
function caricaEditor() {
    const codice = fs.readFileSync(path.join(qui, '..', 'RichTextBox', 'RichTextBox.js'), 'utf8');
    const pagina = { editori: [], fuoco: null };
    const DW = { error: m => { throw new Error(m); }, onLeave() {} };
    const document = {
        addEventListener() {},
        querySelectorAll: sel => (sel === '[data-dw-rte]' ? pagina.editori : []),
        get activeElement() { return pagina.fuoco; },
    };
    const window = { DW };
    const getSelection = () => ({ rangeCount: 0 });
    new Function('window', 'document', 'getSelection', 'Node', codice)(window, document, getSelection, { TEXT_NODE: 3, ELEMENT_NODE: 1 });
    return { DW, pagina };
}

console.log('');

if (rosse.length === 0) {
    console.log('tutte verdi: ' + fatte + ' prove javascript');
} else {
    console.log(rosse.length + ' rosse su ' + fatte + ' javascript:\n  ' + rosse.join('\n  '));
    process.exit(1);
}
