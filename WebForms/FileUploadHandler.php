<?php
declare(strict_types=1);

/**
 * Punto di arrivo degli upload: e' l'unico file del framework che si chiama da URL.
 *
 * Sta a parte dal postback perche' un file non passa da li': il postback e' una POST di
 * campi che risponde JSON, questo riceve multipart e risponde un token.
 */

//per __DIR__ e non per DOCUMENT_ROOT: questo file sta in Common/WebForms, due sopra c'e'
//la radice dei sorgenti, e cosi' vale anche da riga di comando e in un sito servito da
//un'altra cartella
require_once dirname(__DIR__, 2) . '/Start.php';

//GET con un token: e' la richiesta di anteprima di un file caricato e non ancora salvato.
//POST: e' il caricamento vero.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET')
    \Common\WebForms\Upload::Show((string)($_GET['token'] ?? ''));
else
    \Common\WebForms\Upload::Receive();
