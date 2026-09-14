<?php
declare(strict_types=1);

namespace Common\WebForms;

/**
 * Marca una proprieta' del codebehind che NON deve sopravvivere al postback.
 *
 * E' l'eccezione, non la regola: le variabili di pagina attraversano il postback tutte, come
 * i campi di una form di WinForms. Questo attributo serve al poco che deve davvero rinascere
 * ad ogni richiesta - una cache riempita in OnLoad, un conteggio buono solo per questo giro,
 * un valore grosso che non ha senso far viaggiare - e serve anche a dirlo a chi legge: senza,
 * un campo che si azzera sembra un difetto.
 *
 * Non serve sulle proprieta' tipizzate con una classe (controlli, master, Model): quelle
 * restano fuori da sole, perche' un oggetto non attraversa la serializzazione.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Transient
{
}
