<?php
declare(strict_types=1);
// Esempio completo di setup per un sistema di blog con foreign key usando classi separate

// Include le classi necessarie
require_once 'ControlliComuni.php';
require_once 'Utenti.php';
require_once 'Tag.php';
require_once 'Blog.php';
require_once 'BlogTag.php';

echo "=== SETUP BLOG - STRUTTURA MODULARE ===\n\n";

// 1. CREAZIONE CONTROLLI RIUTILIZZABILI
echo "1. Creazione controlli riutilizzabili...\n";
\Common\Dati\Esempi\ControlliComuni::CreaControlliRiutilizzabili();
echo "\n";

// 2. CREAZIONE ENTITÀ IN ORDINE LOGICO
echo "2. Creazione entità del dominio...\n";

// Prima le entità senza dipendenze
\Common\Dati\Esempi\Utenti::CreaDato();
\Common\Dati\Esempi\Tag::CreaDato();

// Poi le entità che dipendono dalle precedenti
\Common\Dati\Esempi\Blog::CreaDato();

// Infine le entità di relazione
\Common\Dati\Esempi\BlogTag::CreaDato();

echo "\n=== SETUP COMPLETATO CON SUCCESSO! ===\n";
echo "Struttura creata:\n";
echo "- Controlli riutilizzabili registrati nel Registry\n";
echo "- Utenti (con Email univoca per FK)\n";
echo "- Tag (con Nome univoco per FK)\n";
echo "- Blog (con FK verso Utenti)\n";
echo "- BlogTag (relazione molti-a-molti Blog-Tag)\n";
echo "\nTutte le entità sono registrate nel Registry e pronte per l'uso!\n";
