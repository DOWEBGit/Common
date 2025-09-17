<?php
declare(strict_types=1);

namespace Common\Dati\Esempi;

class ControlliComuni
{
    /**
     * Crea e registra tutti i controlli riutilizzabili nel Registry
     */
    public static function CreaControlliRiutilizzabili(): void
    {
        // Controllo per titoli brevi (50 caratteri)
        $controlloTitolo50Id = \Common\Dati\Controlli::CreaControlloTestoTextBox(
            id: 0,
            nome: "Titolo50",
            descrizione: "Campo titolo con massimo 50 caratteri",
            avvisoCampoNonValido: "Il titolo non è valido",
            avvisoCampoDuplicato: "Questo titolo è già presente",
            avvisoCampoMancante: "Il titolo è obbligatorio",
            testoMaxCaratteri: 50
        );
        \Common\Dati\Registry::RegistraControllo("Titolo50", $controlloTitolo50Id);

        // Controllo per nomi/titoli (100 caratteri)
        $controlloNome100Id = \Common\Dati\Controlli::CreaControlloTestoTextBox(
            id: 0,
            nome: "Nome100",
            descrizione: "Campo nome con massimo 100 caratteri",
            avvisoCampoNonValido: "Il nome non è valido",
            avvisoCampoDuplicato: "Questo nome è già presente",
            avvisoCampoMancante: "Il nome è obbligatorio",
            testoMaxCaratteri: 100
        );
        \Common\Dati\Registry::RegistraControllo("Nome100", $controlloNome100Id);

        // Controllo per email con validazione
        $controlloEmailId = \Common\Dati\Controlli::CreaControlloTestoTextBox(
            id: 0,
            nome: "Email",
            descrizione: "Campo email con validazione",
            avvisoCampoNonValido: "Formato email non valido",
            avvisoCampoDuplicato: "Questa email è già registrata",
            avvisoCampoMancante: "L'email è obbligatoria",
            testoMaxCaratteri: 255,
            testoRegEx: '^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$'
        );
        \Common\Dati\Registry::RegistraControllo("Email", $controlloEmailId);

        // Controllo per codici (20 caratteri, univoco)
        $controlloCodiceId = \Common\Dati\Controlli::CreaControlloTestoTextBox(
            id: 0,
            nome: "Codice20",
            descrizione: "Campo codice alfanumerico",
            avvisoCampoNonValido: "Il codice non è valido",
            avvisoCampoDuplicato: "Questo codice è già utilizzato",
            avvisoCampoMancante: "Il codice è obbligatorio",
            testoMaxCaratteri: 20,
            testoRegEx: '^[A-Z0-9]+$'
        );
        \Common\Dati\Registry::RegistraControllo("Codice20", $controlloCodiceId);

        // Controllo per descrizioni (500 caratteri)
        $controlloDescrizione500Id = \Common\Dati\Controlli::CreaControlloTestoTextArea(
            id: 0,
            nome: "Descrizione500",
            descrizione: "Campo descrizione con massimo 500 caratteri",
            avvisoCampoNonValido: "La descrizione non è valida",
            avvisoCampoDuplicato: "Questa descrizione è già presente",
            avvisoCampoMancante: "La descrizione è obbligatoria",
            testoMaxCaratteri: 500,
            adminRighe: 5,
            adminColonne: 4
        );
        \Common\Dati\Registry::RegistraControllo("Descrizione500", $controlloDescrizione500Id);

        // Controllo per contenuti lunghi (editor ricco)
        $controlloContenutoRichId = \Common\Dati\Controlli::CreaControlloTestoRichTextBox(
            id: 0,
            nome: "ContenutoRich",
            descrizione: "Editor di contenuto con formattazione",
            avvisoCampoNonValido: "Il contenuto non è valido",
            avvisoCampoDuplicato: "Questo contenuto è già presente",
            avvisoCampoMancante: "Il contenuto è obbligatorio",
            testoMaxCaratteri: 10000,
            adminColonne: 4
        );
        \Common\Dati\Registry::RegistraControllo("ContenutoRich", $controlloContenutoRichId);

        // Controllo per prezzi (numerico)
        $controlloPrezzoId = \Common\Dati\Controlli::CreaControlloNumeriTextBox(
            id: 0,
            nome: "Prezzo",
            descrizione: "Campo prezzo",
            avvisoCampoNonValido: "Inserire un prezzo valido",
            avvisoCampoDuplicato: "",
            avvisoCampoMancante: "Il prezzo è obbligatorio",
            numeriValoreMin: 0,
            numeriValoreMax: 999999
        );
        \Common\Dati\Registry::RegistraControllo("Prezzo", $controlloPrezzoId);

        // Controllo FK generico per selezione singola (DropDownList)
        $controlloFkDropDownId = \Common\Dati\Controlli::CreaControlloDatoDropDownList(
            id: 0,
            nome: "FkDropDown",
            descrizione: "Foreign key generica per selezione singola",
            avvisoCampoNonValido: "Selezionare un elemento valido",
            avvisoCampoDuplicato: "",
            avvisoCampoMancante: "La selezione è obbligatoria"
        );
        \Common\Dati\Registry::RegistraControllo("FkDropDown", $controlloFkDropDownId);

        // Controllo FK generico per selezione multipla (ListBox)
        $controlloFkListBoxId = \Common\Dati\Controlli::CreaControlloDatoListBox(
            id: 0,
            nome: "FkListBox",
            descrizione: "Foreign key generica per selezione multipla",
            avvisoCampoNonValido: "Selezionare elementi validi",
            avvisoCampoDuplicato: "",
            avvisoCampoMancante: ""
        );
        \Common\Dati\Registry::RegistraControllo("FkListBox", $controlloFkListBoxId);

        echo "Controlli riutilizzabili creati e registrati nel Registry\n";
    }
}
