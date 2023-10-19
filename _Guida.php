<?php
declare(strict_types=1);

/* 
 TYPE HINT
/** @var ClassName $object */

/*
 * 
 * VIEW
 * solo visualizzazione e chiamate javascript
 * il nome deve avere il percorso completo del file dove viene inclusa tranne per quelle generiche come il paginatore
 * i textbox devono avere maxlength uguale alla lunghezza limite impostata nel controllo
 
 * ACTION
 * nessun salvataggio
 * ritornano sempre void 
 * mai json_encode / decode, usare sempre \Common\State::TempRead/Write / WindowRead/Write / SessionRead/Write
 * mai usare exit/die, sempre return per uscire dalla funzione

 * 
 * CONTROLLER
 * solo metodi statici
 * ritornano solo ResponseAvviso/ResponseAvvisoModel/bool/int/string/void
 * no echo/print_r o output vari
 * leggono solo dai parametri in input e da state\sessionread
 * salvano solo il loro model, es: StudentiController salva solo StudentiModel 
 * 
 * 
 * BUSINESS
 * invio email
 * creazione pdf
 * scrittura lettura file
 * 
 * MODEL
 * generata automaticamente da modelgenerator.php, mai modificare toccare
 *
 *
 *
 * GENERALE
 * non usare direttamente $_SESSION ma appoggiarsi a \Common\State::SessionRead/SessionWrite
 * usate sempre save/delete/getitem/getlist/count dei model, non "phpobj"
 * Common\State::SessionRead/SessionWrite, TempRead/TempWrite, WindowRead/WindowWrite hanno la key insensitive, ritornano sempre stringa vuota se il valore non c'è,
 * declare(strict_types=1); sopra ogni riga, ogni file php


 */

