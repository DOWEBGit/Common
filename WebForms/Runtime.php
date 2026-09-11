<?php
declare(strict_types=1);

namespace Common\WebForms;

use Common\WebForms\Controls\StaticResource;

/**
 * I due tag che il motore mette in ogni pagina: lo stile funzionale in testa, gli script in
 * coda.
 *
 * Qui dentro non c'e' ne' JavaScript ne' CSS: ci sono i due indirizzi e l'ordine in cui
 * mettere i tag. Il codice sta in runtime.js e runtime.css, accanto a questo file.
 *
 * Il motivo e' pratico: dentro un const di PHP quella roba non e' codice per nessuno - non
 * per l'editor, non per il controllo di sintassi, non per il debugger del browser, che la
 * chiama "inline" e non sa dirti a che riga sei. Fuori sono due file veri, con la loro
 * evidenziazione, i loro numeri di riga e la cache del browser dalla loro parte.
 */
class Runtime
{
    /** Il motore lato browser. L'indirizzo lo compone StaticResource, marca temporale inclusa. */
    private const RUNTIME_JS = 'Common/WebForms/runtime.js';

    /** Lo stile funzionale, stessa storia. */
    private const RUNTIME_CSS = 'Common/WebForms/runtime.css';

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
        return '<link rel="stylesheet" href="'
            . StaticResource::Url(self::RUNTIME_CSS, 'Il motore') . '">';
    }

    public static function Scripts(): string
    {
        return '<script>window.DW_CSRF=' . json_encode(Csrf::Token()) . ';'
            . 'window.DW_SIGNALR_SCORTA=' . json_encode(self::SIGNALR_SCORTA) . ';</script>'
            //defer e non async anche per il motore: gli script differiti girano NELL'ORDINE
            //in cui stanno scritti, quindi quando tocca a SignalR il DW che il suo onload
            //chiama esiste gia'. Con async l'ordine non sarebbe garantito.
            . '<script defer src="' . StaticResource::Url(self::RUNTIME_JS, 'Il motore') . '"></script>'
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
            . ' onload="DW.connectNotifications()" onerror="DW.signalrFallback()"></script>';
    }
}
