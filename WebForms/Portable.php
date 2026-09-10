<?php
declare(strict_types=1);

namespace Common\WebForms;

/**
 * Marca una proprieta' del codebehind che deve sopravvivere anche al CAMBIO DI PAGINA.
 *
 * E' il fratello di #[Persist]:
 *
 *   #[Persist]   vive quanto la pagina.  Cambi pagina, riparte da zero.
 *   #[Portable]  vive quanto la scheda del browser, e attraversa le pagine.
 *
 * Dove sta. Non in sessione, non in un cookie, non nel querystring: sta in un pacchetto
 * FIRMATO che il browser tiene in memoria e rimanda ad ogni postback e ad ogni navigazione.
 * Puo' farlo perche' qui non si ricarica mai la pagina - un click e' una fetch e poi un
 * morph - quindi quella memoria non si azzera mai in mezzo al lavoro.
 *
 * E' lo stesso pacchetto del ViewState nascosto: serialize, gzip, base64 e HMAC-SHA256.
 * Firmato, quindi il client non se lo puo' riscrivere; ma resta pur sempre roba che arriva
 * dal browser, quindi va trattato come STATO, non come autorizzazione: un id di cliente
 * portato di qua dice "stavo guardando questo", non "posso vedere questo".
 *
 * Il nome della proprieta' e' la chiave, ed e' GLOBALE: due pagine che dichiarano
 * #[Portable] private int $idCliente condividono lo stesso valore - ed e' esattamente il
 * punto. Il rovescio e' che due pagine che intendono cose diverse non devono chiamarle
 * uguale, come per le chiavi di sessione.
 *
 * Ci vanno solo scalari e array, come per #[Persist], e poca roba: il pacchetto viaggia su
 * ogni richiesta, e oltre 4 kB il motore si ferma invece di far viaggiare un'intestazione
 * che qualche proxy taglierebbe per conto suo.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Portable
{
}
