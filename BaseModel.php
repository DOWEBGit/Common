<?php

namespace Common;

use Attribute;
use DateTime;
use Exception;
use ReflectionClass;
use ReflectionProperty;

#[Attribute]
class PropertyAttribute
{
    function __construct(string $nomeColonna, string $tipoDato)
    {

    }
}

class BaseModel
{
    function __construct()
    {
        $this->Id = 0;
        $this->ParentId = 0;
        $this->Visibile = true;
        $this->Aggiornamento = new \DateTime();
        $this->Inserimento = new \DateTime();
    }

    public function __toString(): string
    {
        $fields = get_object_vars($this);

        $output = "";

        foreach ($fields as $name => $value)
        {
            $output .= $name . ": ";

            switch (gettype($value))
            {
                case 'object':
                    if ($value instanceof DateTime)
                    {
                        $output .= $value->format('Y-m-d H:i:s');
                    }
                    else
                    {
                        $output .= 'Object';
                    }
                    break;
                case 'integer':
                case 'string':
                case 'boolean':
                    $output .= $value;
                    break;
                default:
                    $output .= 'Unknown Type';
                    break;
            }

            $output .= ", ";
        }

        return $output;
    }

    public function EqualsValues(\Common\BaseModel $external): bool
    {
        $externalFields = get_object_vars($external);
        unset($externalFields["Id"]);
        unset($externalFields["Visible"]);
        unset($externalFields["Aggiornamento"]);
        unset($externalFields["Inserimento"]);

        $thisFields = get_object_vars($this);
        unset($thisFields["Id"]);
        unset($thisFields["Visible"]);
        unset($thisFields["Aggiornamento"]);
        unset($thisFields["Inserimento"]);

        return $thisFields == $externalFields;
    }

    #[PropertyAttribute('Id', 'Numeri')]
    public int $Id;

    #[PropertyAttribute('ParentId', 'Numeri')]
    public int $ParentId;

    #[PropertyAttribute('Visibile', 'Numeri')]
    public bool $Visibile;

    #[PropertyAttribute('Aggiornamento', 'Data')]
    public DateTime $Aggiornamento;

    #[PropertyAttribute('Inserimento', 'Data')]
    public DateTime $Inserimento;

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    //    non ci sono i tipi anonimi in PHP quindi passo l'oggetto come parametro
    static function GetItem(object $tableObj, string $uniqueColumn = "Id", $uniqueValue = "", string $iso = "", bool $webP = true): ?BaseModel
    {
        $tableName = get_class($tableObj);

        $reflection = new \ReflectionClass($tableName);

        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        $colonne = [];
        $tipi = [];

        //recupero le colonne della classe dalle etichette sulle variabili
        foreach ($properties as $property)
        {
            $attributes = $property->getAttributes();

            foreach ($attributes as $attribute)
            {
                $nome = $attribute->getArguments()['0'];
                $tipo = $attribute->getArguments()['1'];

                if ($tipo == "Dato")
                    $nome .= "_FkId";

                $colonne[] = $nome;
                $tipi[] = $tipo;
            }
        }

        //del nome Model\Tipo, prendo solo l'ultimo pezzo: Tipo
        $parts = explode("\\", $tableName);
        $partialName = end($parts);

        //i nomi delle classi hanno lo spazio sostituito il simbolo
        $partialName = str_replace("_", " ", $partialName);

        $obj = PHPDOWEB();

        //prendo i valori dal db
        $result = $obj->DatiElencoGetItem($partialName, $uniqueColumn, $uniqueValue, $iso, $colonne, $webP);

        if ($result->Errore == 1)
        {
            $obj->LogError("BaseModel->GetItem({$tableName}, {$uniqueColumn}) " . $result->Avviso);
            return null;
        }

        $valori = $result->Values;

        if (count($valori) == 0)
            return null;

        //imposto i valori nella istanza di classe
        self::ImpostoIValoriNellaIstanzaDiClasse($properties, $tipi, $tableObj, $valori, $reflection);

        return $tableObj;
    }

    /**
     * @param array $properties
     * @param array $tipi
     * @param object $tableObj
     * @param $valori
     * @param \ReflectionClass $reflection
     * @return void
     * @throws \ReflectionException
     */
    private static function ImpostoIValoriNellaIstanzaDiClasse(array $properties, array $tipi, object $tableObj, $valori, \ReflectionClass $reflection): void
    {
        for ($i = 0; $i < count($tipi); $i++)
        {
            $prop = $properties[$i];

            $type = $prop->getType();
            $typeName = $type->getName(); //ritorna in stringa "int" "string "bool"

            $tipo = $tipi[$i];

            switch ($tipo)
            {
                case "Numeri":
                    {
                        if ($typeName == "bool")
                        {
                            $prop->setValue($tableObj, $valori[$i] === "true" || $valori[$i] === "1");
                        }
                        else //in teoria è sempre int
                        {
                            $prop->setValue($tableObj, (int)$valori[$i]);
                        }
                    }
                    break;

                case "Dato":
                    {
                        $prop->setValue($tableObj, (int)$valori[$i]);
                    }
                    break;

                case "Testo":
                    {
                        $prop->setValue($tableObj, $valori[$i]);

                        //imposto il valore anche a quella da usare per confronto
                        $old = '_' . $prop->name;

                        $propertyOld = $reflection->getProperty($old);

                        $propertyOld->setValue($tableObj, $valori[$i]);
                    }
                    break;

                case "Data":
                    {
                        $len = strlen($valori[$i]);

                        if ($len == 10)
                        {
                            $prop->setValue($tableObj, \DateTime::createFromFormat('d/m/Y', $valori[$i]));
                        }

                        if ($len == 19)
                        {
                            $a = $valori[$i];

                            $str = $a[0] . $a[1] . '/' . $a[3] . $a[4] . '/' . $a[6] . $a[7] . $a[8] . $a[9] . ' ' .
                                   $a[11] . $a[12] . ':' . $a[14] . $a[15] . ':' . $a[17] . $a[18];

                            $prop->setValue($tableObj, \DateTime::createFromFormat('d/m/Y H:i:s', $str));
                        }
                    }
                    break;

                default: //immagini, file, testo e senza definizione
                    $prop->setValue($tableObj, $valori[$i]);
                    break;
            }
        }
    }

    function Save(bool $onSave, string $iso): SaveResponse
    {
        $tableName = get_class($this);

        $reflection = new ReflectionClass($tableName);

        $properties = $reflection->getProperties(ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PUBLIC);

        $colonne = [];

        //recupero le colonne della classe dalle etichette sulle variabili
        foreach ($properties as $property)
        {
            $attributes = $property->getAttributes();

            //c'è massimo un solo attributo che è il nome del database, per adesso
            foreach ($attributes as $attribute)
            {
                $nome = $attribute->getArguments()['0'];
                $tipo = $attribute->getArguments()['1'];

                if ($tipo == "")
                    continue;

                $propertyValue = $property->getValue($this);

                switch ($tipo)
                {
                    case "Dato":
                    case "Numeri":
                        $colonne[] = [$nome, $propertyValue];
                        break;

                    case "Testo":
                        {
                            $propertyOld = $reflection->getProperty("_" . $property->name);

                            $oldValue = $propertyOld->getValue($this);

                            //salvo solo se il valore è stato modificato
                            if ($oldValue !== $propertyValue)
                                $colonne[] = [$nome, $propertyValue];
                        }

                        break;

                    case "Data":
                        $colonne[] = [$nome, $propertyValue->format('d/m/Y')];
                        break;

                    case "Immagini":
                    case "File":
                        if (!isset($propertyValue))
                            break;

                        $colonne[] = [$nome, [$propertyValue->Nome, base64_encode($propertyValue->Bytes)]];

                        break;

                }
            }
        }

        //var_dump($colonne);
        //del nome Model\Tipo, prendo solo l'ultimo pezzo: Tipo
        $parts = explode("\\", $tableName);
        $partialName = end($parts);

        //i nomi delle classi hanno lo spazio sostituito il simbolo
        $partialName = str_replace("_", " ", $partialName);

        $obj = PHPDOWEB();

        //prendo i valori dal db
        $result = $obj->DatiElencoSaveAvvisi(
            $partialName,
            $this->Id,
            $this->ParentId,
            $this->Visibile,
            $iso,
            $colonne,
            $onSave);

        $saveRespone = new SaveResponse();

        if (\Common\Convert::ToBool($result->Errore))
        {
            $saveRespone->Success = false;

            if ($result->Avviso !== "")
            {
                $saveRespone->InternalAvviso = $result->Avviso;
            }
            else
            {
                foreach ($result->Avvisi as $controlloAvviso)
                    $saveRespone->InternalAvvisi[$controlloAvviso->Controllo] = $controlloAvviso->Avviso;
            }

            return $saveRespone;
        }

        $this->Id = $result->Id;

        $saveRespone->Success = true;
        return $saveRespone;
    }

    function Delete(): SaveResponse
    {
        $obj = PHPDOWEB();

        //prendo i valori dal db
        $result = $obj->DatiElencoDelete($this->Id);

        $response = new SaveResponse();

        if (\Common\Convert::ToBool($result->Errore))
        {
            $response->Success = false;
            $response->InternalAvviso = $result->Avviso;
            return $response;
        }

        $response->Success = true;

        return $response;
    }

    static function BaseList(
        string $tableName,
        int $item4page = -1,
        int $page = -1,
        string $wherePredicate = '',
        array $whereValues = [],
        string $orderPredicate = '',
        string $iso = '',
        int $parentId = 0,
        bool $visible = null,
        bool $webP = true,
        bool $encode = false)
    {
        $reflection = new \ReflectionClass($tableName);

        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        $colonne = [];
        $tipi = [];

        //recupero le colonne della classe dalle etichette sulle variabili
        foreach ($properties as $property)
        {
            $attributes = $property->getAttributes();

            foreach ($attributes as $attribute)
            {
                $nome = $attribute->getArguments()['0'];
                $tipo = $attribute->getArguments()['1'];

                if ($tipo == "Dato")
                    $nome .= "_FkId";

                $colonne[] = $nome;
                $tipi[] = $tipo;
            }
        }

        //del nome Model\Tipo, prendo solo l'ultimo pezzo: Tipo
        $parts = explode("\\", $tableName);
        $datoNome = end($parts);

        //i nomi delle classi hanno lo spazio sostituito il simbolo
        $datoNome = str_replace("_", " ", $datoNome);

        $obj = PHPDOWEB();

        $result = $obj->FetchOpen($datoNome, $parentId, $visible, $iso, $wherePredicate, $whereValues, $colonne, $orderPredicate, $item4page, $page, $webP, $encode);

        if (\Common\Convert::ToBool($result->Errore))
        {
            //viene già loggata da doweb
            throw new \Exception("Errore nella query: " . $result->Avviso);
        }

        try
        {
            while (true)
            {
                $valori = $obj->FetchRead();

                if ($valori == null)
                    return;

                $tableObj = $reflection->newInstance();

                //imposto i valori nella istanza di classe
                self::ImpostoIValoriNellaIstanzaDiClasse($properties, $tipi, $tableObj, $valori, $reflection);

                yield $tableObj;
            }
        }
        finally
        {
            $obj->FetchClose();
        }
    }

    static function BaseCount(
        string $tableName,
        string $wherePredicate = '',
        array  $whereValues = [],
        string $iso = '',
        int    $parentId = 0,
        bool   $visible = null,
        bool   $encode = false): int
    {
        //del nome Model\Tipo, prendo solo l'ultimo pezzo: Tipo
        $parts = explode("\\", $tableName);
        $datoNome = end($parts);

        //i nomi delle classi hanno lo spazio sostituito il simbolo
        $datoNome = str_replace("_", " ", $datoNome);

        $obj = PHPDOWEB();

        //prendo i valori dal db
        $result = $obj->DatiElencoGetCount($datoNome, $parentId, $visible, $iso, $wherePredicate, $whereValues, $encode);

        //var_dump($result);

        if (\Common\Convert::ToBool($result->Errore))
        {
            //viene loggata da doweb
            //$obj->LogError("BaseModel->BaseList({$tableName}, {$wherePredicate}) " . $result->Avviso);

            throw new Exception("Errore nella query: " . $result->Avviso);
        }

        return $result->Count;
    }
}