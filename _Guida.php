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
 * API
 * Le chiamate fatte da sistemi esterni,
 * es: Stripe, Paypal, Javascript ecc
 *
 *
 * GENERALE
 * le variabili sempre camelCase, gli enum PascalCase
 * non usare direttamente $_SESSION ma appoggiarsi a \Common\State::SessionRead/SessionWrite
 * usate sempre save/delete/getitem/getlist/count dei model, non "phpobj"
 * Common\State::SessionRead/SessionWrite, TempRead/TempWrite, WindowRead/WindowWrite hanno la key insensitive, ritornano sempre stringa vuota se il valore non c'è,
 * declare(strict_types=1); sopra ogni riga, ogni file php

Dentor CODE, HTML i nomi dei file -> classi devono fare riferimento all'oggetto e non all'elemento html o altro, es.: NO Select.php -> GetSelectPaesi, SI Paesi.php GetSelect(), Pager.php -> GetPublic().. GetPrivate()


tutti i file sono classi, tranne i file dentro API

se ci sono casi ripetuti, es.: tipopagamento, stripe, bonifico, contrassegno, usare sempre un enum, con il valore stringa assegnato, usare l'enum ovunque

gli enum devono partire sempre con la lettera maiuscola, non contenere spazi, assegnare quindi al relativo eunm il valore in stringa es.: case DivizionePerZero = "Divisione per zero";

se un nome è in inglese, nelle entita/tabelle MAI usare la versione plurale, es. NO Cats, Dogs, SI Cat, Dog
il nome delle tabelle deve essere sempre al plurale, partite con una lettera maiuscola, no -> ordine, si -> Ordini
nelle tabelle non devono esserci spazi

nel caso di colonne che vanno un valore allo stesso oggetto, es.: Uomini { Peso, Altezza, ..., Occhi* }, NO -> (ColoreOcchi, DimensioneOcchi, OcchiAperti) SI -> (OcchiDimesione, OcchiColore, OcchiAperti ) Occhi*, ...

STRUTTURA BASE

ACTION
API
CODE
    ENUM
    HTML
    INCLUDE

COMMON
CONTROLLER
MODEL
VENDOR (se necessaria)
VIEW

in root

Start.php
composer.json (se necessario)

 */

