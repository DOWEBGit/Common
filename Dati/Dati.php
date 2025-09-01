<?php
declare(strict_types=1);

namespace Common\Dati;

class Dati
{
    public static function CreaDato(
        int $id,
        string $nome,
        string $nomeVisualizzato,
        string $descrizione,
        int $elementiMax,
        bool $ordinamentoASC = true,
        int $parent = 0,
        string $onSave = "",
        string $onDelete = "",
    ): int
    {
        $obj = PHPDOWEB();

        $dati = $obj->DatiGetList()->Dati;
        foreach ($dati as $dato)
        {
            if ($dato->Nome == $nome)
                return (int)$dato->Id;
        }

        $result = $obj->DatiSave(
            $id,
            $nome,
            $nomeVisualizzato,
            $descrizione,
            (string)$elementiMax,
            'False',
            'False',
            'False',
            'False',
            'False',
            'False',
            'False',
            ucfirst((string)$ordinamentoASC),
            '0',
            '0',
            $parent,
            $onSave,
            $onDelete
        );

        if (!$result->Errore)
            return (int)$result->Id;

        \Common\Log::Error("Impossibile creare il dato $nome: " . $result->Avviso );

        return -1;
    }
}