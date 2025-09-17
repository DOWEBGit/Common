<?php
declare(strict_types=1);

namespace Common\Dati\Esempi;

class Blog
{
    public static function CreaDato(): void
    {
        // Creazione del dato Blog
        $datoBlogId = \Common\Dati\Dati::CreaDato(
            id: 0,
            nome: "Blog",
            nomeVisualizzato: "Gestione Blog",
            descrizione: "Entità per la gestione degli articoli del blog",
            elementiMax: 50000,
            ordinamentoASC: false,
            parent: 0,
            onSave: "",
            onDelete: ""
        );
        \Common\Dati\Registry::RegistraDato("Blog", $datoBlogId);

        // Titolo articolo
        \Common\Dati\Dati::AgganciaControllo(
            idControllo: \Common\Dati\Registry::GetIdControllo("Titolo50"),
            idDato: $datoBlogId,
            nome: "Titolo",
            obbligatorio: true,
            univoco: false,
            nascosto: false,
            autoIncrementante: false,
            colonnaTabelle: true,
            valoreDefault: '',
            descrizione: "Titolo dell'articolo"
        );

        // Riassunto articolo
        \Common\Dati\Dati::AgganciaControllo(
            idControllo: \Common\Dati\Registry::GetIdControllo("Descrizione500"),
            idDato: $datoBlogId,
            nome: "Riassunto",
            obbligatorio: true,
            univoco: false,
            nascosto: false,
            autoIncrementante: false,
            colonnaTabelle: true,
            valoreDefault: '',
            descrizione: "Riassunto dell'articolo"
        );

        // Contenuto articolo
        \Common\Dati\Dati::AgganciaControllo(
            idControllo: \Common\Dati\Registry::GetIdControllo("ContenutoRich"),
            idDato: $datoBlogId,
            nome: "Contenuto",
            obbligatorio: true,
            univoco: false,
            nascosto: false,
            autoIncrementante: false,
            colonnaTabelle: false,
            valoreDefault: '',
            descrizione: "Contenuto completo dell'articolo"
        );

        // FK verso Utenti (autore del blog) - riferimento al controllo Email dell'utente
        \Common\Dati\Dati::AgganciaControllo(
            idControllo: \Common\Dati\Registry::GetIdControllo("FkDropDown"),
            idDato: $datoBlogId,
            nome: "Autore",
            obbligatorio: true,
            univoco: false,
            nascosto: false,
            autoIncrementante: false,
            colonnaTabelle: true,
            valoreDefault: '',
            controlloRefId: \Common\Dati\Registry::GetControlloRefId("Utenti", "Email"),
            descrizione: "Autore dell'articolo"
        );

        echo "Dato Blog creato e configurato\n";
    }
}
