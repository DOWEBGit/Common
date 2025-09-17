<?php
declare(strict_types=1);

namespace Common\Dati\Esempi;

class BlogTag
{
    public static function CreaDato(): void
    {
        // Creazione del dato BlogTag per relazione molti-a-molti
        $datoBlogTagId = \Common\Dati\Dati::CreaDato(
            id: 0,
            nome: "BlogTag",
            nomeVisualizzato: "Associazioni Blog-Tag",
            descrizione: "Relazioni molti-a-molti tra blog e tag",
            elementiMax: 500000,
            ordinamentoASC: true,
            parent: \Common\Dati\Registry::GetIdDato("Blog"), // Parent: Blog (uno-a-molti)
            onSave: "",
            onDelete: ""
        );
        \Common\Dati\Registry::RegistraDato("BlogTag", $datoBlogTagId);

        // FK verso Tag nel dato BlogTag
        \Common\Dati\Dati::AgganciaControllo(
            idControllo: \Common\Dati\Registry::GetIdControllo("FkDropDown"),
            idDato: $datoBlogTagId,
            nome: "Tag",
            obbligatorio: true,
            univoco: false,
            nascosto: false,
            autoIncrementante: false,
            colonnaTabelle: true,
            valoreDefault: '',
            controlloRefId: \Common\Dati\Registry::GetControlloRefId("Tag", "Nome"),
            descrizione: "Tag associato all'articolo"
        );

        echo "Dato BlogTag creato e configurato\n";
    }
}
