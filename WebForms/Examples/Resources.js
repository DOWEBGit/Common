// Lo script dell'esempio Stylesheet e Script.
//
// Gira UNA volta, al caricamento vero: le pagine non si ricaricano. Per questo non cerca i
// suoi elementi subito e se li tiene - al primo morph sarebbero nodi fuori dalla pagina - ma
// si aggancia al documento e scrive ogni volta che serve.
(() => {
    const scrivi = (quando) => {
        const el = document.getElementById('ex-resources-script');
        if (el) el.textContent = 'partito alle ' + new Date().toLocaleTimeString() + ' (' + quando + ')';
    };

    // al caricamento vero...
    scrivi('caricamento');

    // ...e ad ogni navigazione senza ricarico che porta su una pagina con quell'elemento
    document.addEventListener('dw:pagina', () => scrivi('navigazione'));
})();
