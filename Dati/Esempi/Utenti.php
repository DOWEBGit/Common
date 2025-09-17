<?php
declare(strict_types=1);

namespace Common\Dati\Esempi;

class Utenti
{
    public static function CreaDato(): void
    {
        // Creazione del dato Utenti
        $datoUtentiId = \Common\Dati\Dati::CreaDato(
            id: 0,
            nome: "Utenti",
            nomeVisualizzato: "Gestione Utenti",
            descrizione: "Entità per la gestione degli utenti del sistema",
            elementiMax: 10000,
            ordinamentoASC: true,
            parent: 0,
            onSave: "",
            onDelete: ""
        );
        \Common\Dati\Registry::RegistraDato("Utenti", $datoUtentiId);

        // Nome utente
        \Common\Dati\Dati::AgganciaControllo(
            idControllo: \Common\Dati\Registry::GetIdControllo("Titolo50"),
            idDato: $datoUtentiId,
            nome: "Nome",
            obbligatorio: true,
            univoco: false,
            nascosto: false,
            autoIncrementante: false,
            colonnaTabelle: true,
            valoreDefault: '',
            descrizione: "Nome dell'utente"
        );

        // Cognome utente
        \Common\Dati\Dati::AgganciaControllo(
            idControllo: \Common\Dati\Registry::GetIdControllo("Titolo50"),
            idDato: $datoUtentiId,
            nome: "Cognome",
            obbligatorio: true,
            univoco: false,
            nascosto: false,
            autoIncrementante: false,
            colonnaTabelle: true,
            valoreDefault: '',
            descrizione: "Cognome dell'utente"
        );

        // Email utente (univoca e obbligatoria - può essere usata come FK target)
        \Common\Dati\Dati::AgganciaControllo(
            idControllo: \Common\Dati\Registry::GetIdControllo("Email"),
            idDato: $datoUtentiId,
            nome: "Email",
            obbligatorio: true,
            univoco: true,
            nascosto: false,
            autoIncrementante: false,
            colonnaTabelle: true,
            valoreDefault: '',
            descrizione: "Email dell'utente"
        );

        echo "Dato Utenti creato e configurato\n";
    }
}
