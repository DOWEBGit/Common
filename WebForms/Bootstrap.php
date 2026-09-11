<?php
declare(strict_types=1);

/**
 * Unico punto di ingresso delle pagine WebForms.
 *
 * Sta qui e non nel markup perche' la riga di avvio di ogni pagina resti una sola: se un
 * giorno il bootstrap cambia, cambia in un posto solo invece che in tutte le pagine.
 */

//per __DIR__ e non per DOCUMENT_ROOT: questo file sta in Common/WebForms, due sopra c'e'
//la radice dei sorgenti, e cosi' vale anche da riga di comando e in un sito servito da
//un'altra cartella
require_once dirname(__DIR__, 2) . '/Start.php';

//Il motore NON apre la sessione e non ne ha bisogno: lo stato della pagina sta nel campo
//nascosto firmato, il token degli upload e' firmato anche lui, e il CSRF e' a doppio invio
//su un cookie. Se il sito la apre per conto suo va benissimo lo stesso - semplicemente qui
//non serve, e una pagina non smette di funzionare perche' la sessione e' scaduta.
