<?php
declare(strict_types=1);

namespace Common\Dati\Esempi;

class Tag
{
    public static function CreaDato(): void
    {
        // Creazione del dato Tag
        $datoTagId = \Common\Dati\Dati::CreaDato(
            id: 0,
            nome: "Tag",
            nomeVisualizzato: "Gestione Tag",
            descrizione: "Tag per categorizzare gli articoli del blog",
            elementiMax: 1000,
            ordinamentoASC: true,
            parent: 0,
            onSave: "",
            onDelete: ""
        );
        \Common\Dati\Registry::RegistraDato("Tag", $datoTagId);

        // Nome tag (univoco e obbligatorio - può essere usato come FK target)
        \Common\Dati\Dati::AgganciaControllo(
            idControllo: \Common\Dati\Registry::GetIdControllo("Titolo50"),
            idDato: $datoTagId,
            nome: "Nome",
            obbligatorio: true,
            univoco: true,
            nascosto: false,
            autoIncrementante: false,
            colonnaTabelle: true,
            valoreDefault: '',
            descrizione: "Nome del tag"
        );

        // Descrizione tag
        \Common\Dati\Dati::AgganciaControllo(
            idControllo: \Common\Dati\Registry::GetIdControllo("Descrizione500"),
            idDato: $datoTagId,
            nome: "Descrizione",
            obbligatorio: false,
            univoco: false,
            nascosto: false,
            autoIncrementante: false,
            colonnaTabelle: false,
            valoreDefault: '',
            descrizione: "Descrizione del tag"
        );

        echo "Dato Tag creato e configurato\n";
    }
}
