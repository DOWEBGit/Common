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
            $ordinamentoASC ? "True" : "False",
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

    public static function GetIdControlloRefId(string $nomeDato, string $nomeControllo): int
    {
        $controlloRefId = 0;
        $idRef = 0;

        $obj = PHPDOWEB();
        $dati = $obj->DatiGetList()->Dati;

        foreach ($dati as $dato)
        {
            if ($dato->Nome == $nomeDato)
            {
                $idRef = $dato->Id;
                break;
            }
        }

        if ($idRef == 0)
        {
            \Common\Log::Error('Non trovo il dato esterno '.$nomeDato.' usato in una foreign key');
            return -1;
        }

        $datiControlli = $obj->DatiControlliGetList($idRef)->DatiControlli;
        foreach ($datiControlli as $datoControllo)
        {
            if ($datoControllo->Identificativo == $nomeControllo)
            {
                $controlloRefId = $datoControllo->Id;
                break;
            }
        }

        if ($controlloRefId == 0)
        {
            echo 'Non trovo il controllo '.$nomeControllo.' nella tabella '.$nomeDato;
            return -1;
        }

        return $controlloRefId;
    }

    public static function AgganciaControllo(
        int $idControllo,
        int $idDato,
        string $nome,
        bool $obbligatorio = true,
        bool $univoco = false,
        bool $nascosto = false,
        bool $autoIncrementante = false,
        bool $colonnaTabelle = false,
        string $valoreDefault = '',
        bool $multiLingua = false,
        int $adminColonne = 1,
        int $adminRighe = 1,
        bool $ordinamentoASC = true,
        int $controlloRefId = 0,
        \Common\Dati\Enum\TipoEliminazioneFkEnum $tipoEliminazioneFk = \Common\Dati\Enum\TipoEliminazioneFkEnum::Blocco,
        \Common\Dati\Enum\TipoControlloInLinea $tipoControlloInLinea = \Common\Dati\Enum\TipoControlloInLinea::SolaLettura,
        bool $mobile = false,
        string $descrizione = '',
        string $avvisoCampoNonValido = 'Valore non valido',
        string $avvisoCampoDuplicato = 'Valore già presente',
        string $avvisoCampoVuoto = 'Il campo è obbligatorio'

    ): bool
    {
        $obj = PHPDOWEB();

        $identificativo = str_replace(" ", "", $nome);

        $tagReplace = "[DC:".strtoupper($identificativo)."]";

        $arr = $obj->DatiControlliSave('0',
            $idControllo,
            $idDato,
            $identificativo,
            $obbligatorio ? 'True' : 'False',
            $univoco ? 'True' : 'False',
            $nascosto ? 'True' : 'False',
            $autoIncrementante ? 'True' : 'False',
            $colonnaTabelle ? 'True' : 'False',
            $valoreDefault,
            $multiLingua ? 'True' : 'False',
            $tagReplace,
            $adminColonne,
            $adminRighe,
            $ordinamentoASC ? 'True' : 'False',
            $controlloRefId,
            $tipoEliminazioneFk->value,
            $tipoControlloInLinea->value,
            $mobile ? 'True' : 'False',
            $nome,
            $descrizione != '' ? $descrizione : $nome,
            $avvisoCampoNonValido,
            $avvisoCampoDuplicato,
            $avvisoCampoVuoto);

        if ($arr->Errore)
        {
            \Common\Log::Error($arr->Avviso);
            return false;
        }
        else
            return true;
    }

}