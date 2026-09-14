// Il ricevitore del banco: ascolta i messaggi "Saluto" mandati da EntityEvents::Broadcast()
// e li scrive in pagina. L'ascolto vive quanto la pagina: cambiandola si toglie da solo.
DW.on('Saluto', (dati) => {
    const dove = document.getElementById('ricevuti');

    if (!dove) return;

    const riga = document.createElement('div');

    // e' roba del client, il server non la conosce: senza questo marcatore il morph del
    // postback successivo la toglierebbe come "figlio in piu'"
    riga.dataset.dwClient = '1';

    riga.textContent = 'Ricevuto alle ' + new Date().toLocaleTimeString() + ': "' + dati.testo + '" mandato da ' + dati.da + ' alle ' + dati.ora;

    dove.prepend(riga);
});
