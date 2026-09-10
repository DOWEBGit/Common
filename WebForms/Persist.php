<?php
declare(strict_types=1);

namespace Common\WebForms;

/**
 * Marca una proprieta' del codebehind che deve sopravvivere al postback.
 *
 * Serve perche' in PHP l'oggetto pagina muore a fine richiesta: senza questo attributo un
 * campo valorizzato in un handler risulta vuoto al click successivo, ed e' il primo
 * inciampo di chiunque arrivi da WinForms. WebForms costringeva a scrivere a mano
 * ViewState["righe"]; qui basta dichiararlo.
 *
 * Ci vanno solo scalari e array: risorse, connessioni e closure non attraversano la
 * serializzazione.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Persist
{
}
