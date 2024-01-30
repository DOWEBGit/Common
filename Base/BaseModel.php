<?php
declare(strict_types=1);

namespace Common\Base;

use Attribute;
use Common\Response\SaveResponse;
use DateTime;
use ReflectionClass;
use ReflectionProperty;

#[Attribute]
class PropertyAttribute
{
    function __construct(string $nomeColonna, string $tipoDato, bool $univoco)
    {

    }
}

class BaseModel
{
    function __construct()
    {
        $this->Id = 0;
        $this->ParentId = 0;
        $this->_ParentId = 0;
        $this->Visibile = true;
        $this->_Visibile = false; //differente, cosi nella save aggiunge anche il salvataggio della visibile
        $this->Aggiornamento = new \DateTime();
        $this->Inserimento = new \DateTime();
    }

    public function __toString(): string
    {
        $fields = get_object_vars($this);

        $output = "";

        foreach ($fields as $name => $value)
        {
            if (str_starts_with($name, "_"))
                continue;

            $output .= $name . ": ";

            switch (gettype($value))
            {
                case 'object':
                    if ($value instanceof DateTime)
                    {
                        if ($value->format('H:i') == '00:00')
                        {
                            $output .= $value->format('Y-m-d');
                        }
                        else
                        {
                            $output .= $value->format('Y-m-d H:i:s');
                        }
                    }
                    else
                    {
                        $output .= 'Object';
                    }
                    break;
                case 'integer':
                case 'string':
                $output .= $value;
                break;
                case 'boolean':
                    $output .= $value ? "true" : "false";
                    break;
                default:
                    $output .= 'Unknown Type';
                    break;
            }

            $output .= ", ";
        }

        return $output;
    }

    public function HtmlDecode(): void
    {
        $fields = get_object_vars($this);

        foreach ($fields as $name => $value)
        {
            if (gettype($value) !== "string")
                continue;

            $this->$name = html_entity_decode($value);
        }
    }

    public function EqualsValues(BaseModel $external): bool
    {
        $externalFields = get_object_vars($external);
        unset($externalFields["Id"]);
        unset($externalFields["Visibile"]);
        unset($externalFields["_Visibile"]);
        unset($externalFields["_ParentId"]);
        unset($externalFields["Aggiornamento"]);
        unset($externalFields["Inserimento"]);

        $thisFields = get_object_vars($this);
        unset($thisFields["Id"]);
        unset($thisFields["Visibile"]);
        unset($thisFields["_Visibile"]);
        unset($thisFields["_ParentId"]);
        unset($thisFields["Aggiornamento"]);
        unset($thisFields["Inserimento"]);

        return $thisFields == $externalFields;
    }

    #[PropertyAttribute('Id', 'Numeri', true)]
    public int $Id;

    #[PropertyAttribute('ParentId', 'Numeri', false)]
    public int $ParentId;
    private int $_ParentId;

    #[PropertyAttribute('Visibile', 'Numeri', false)]
    public bool $Visibile;
    private bool $_Visibile;

    #[PropertyAttribute('Aggiornamento', 'Data', false)]
    public DateTime $Aggiornamento;

    #[PropertyAttribute('Inserimento', 'Data', false)]
    public DateTime $Inserimento;

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    //    non ci sono i tipi anonimi in PHP quindi passo l'oggetto come parametro
    static function GetItem(object $tableObj, string $uniqueColumn = "Id", $uniqueValue = "", string $iso = "", bool $webP = true, array $selectColumns = []): ?BaseModel
    {
        $tableName = get_class($tableObj);

        $searchKey = strtolower("item|" . $tableName . "|" . $uniqueColumn . "|" . $uniqueValue . "|" . $iso);

        $success = false;

        $value = \Common\Cache::GetDati($searchKey, $success);

        if ($success)
        {
            if (!$value)
                return null;

            return clone $value;
        }

        $reflection = new \ReflectionClass($tableName);

        $properties = [];
        $colonne = [];
        $tipi = [];
        $univoci = [];

        $filterColumns = count($selectColumns) > 0;

        //recupero le colonne della classe dalle etichette sulle variabili
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property)
        {
            $attributes = $property->getAttributes();

            foreach ($attributes as $attribute)
            {
                $arguments = $attribute->getArguments();

                $nome = $arguments['0'];
                $tipo = $arguments['1'];
                $univoco = $arguments['2'];

                if ($tipo == "Dato")
                {
                    $nome .= "_FkId";
                }

                if ($filterColumns)
                {
                    $found = array_search($nome, $selectColumns);

                    if ($found !== false)
                    {
                        $properties[] = $property;
                        $colonne[] = $nome;
                        $tipi[] = $tipo;

                        if ($univoco)
                        {
                            $univoci[] = $arguments['0'];
                        }
                    }
                }
                else
                {
                    $properties[] = $property;
                    $colonne[] = $nome;
                    $tipi[] = $tipo;

                    if ($univoco)
                    {
                        $univoci[] = $arguments['0'];
                    }
                }
            }
        }

        //del nome Model\Tipo, prendo solo l'ultimo pezzo: Tipo
        $parts = explode("\\", $tableName);
        $partialName = end($parts);

        //i nomi delle classi hanno lo spazio sostituito il simbolo
        $partialName = str_replace("_", " ", $partialName);

        $obj = PHPDOWEB();

        //prendo i valori dal db
        $result = $obj->DatiElencoGetItem($partialName, $uniqueColumn, (string)$uniqueValue, $iso, $colonne, (string)$webP);

        if (\Common\Convert::ToBool($result->Errore))
        {
            $e = new \Exception();
            $trace = $e->getTraceAsString();

            $obj->LogError("BaseModel->GetItem({$tableName}, {$uniqueColumn}) " . $result->Avviso . " -> " . $trace);
            return null;
        }

        $valori = $result->Values;

        if (count($valori) == 0)
        {
            \Common\Cache::SetDati($searchKey, null);

            return null;
        }

        //imposto i valori nella istanza di classe
        self::ImpostoIValoriNellaIstanzaDiClasse($iso, !$filterColumns, $properties, $tipi, $univoci, $tableObj, $valori, $reflection);

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
    private static function ImpostoIValoriNellaIstanzaDiClasse(string $iso, bool $cache, array $properties, array $tipi, array $univoci, object &$tableObj, $valori, \ReflectionClass $reflection): void
    {
        //in questo modo se salvo questa istanza a cui rimane l'id a -1, ovvero non viene recuperato l'id, mi tira un'errore
        $tableObj->Id = -1;

        $baseClass = $reflection->getParentClass();

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
                        $old = '_' . $prop->name;

                        if ($typeName == "bool")
                        {
                            if ($prop->name == "Visibile") // visible fa parte della classe ereditata basemodel
                            {
                                $property = $baseClass->getProperty($prop->name);
                                $property->setValue($tableObj, $valori[$i] === "true" || $valori[$i] === "1");

                                $propertyOld = $baseClass->getProperty($old);
                                $propertyOld->setValue($tableObj, $valori[$i] === "true" || $valori[$i] === "1");
                            }
                            else
                            {
                                $prop->setValue($tableObj, $valori[$i] === "true" || $valori[$i] === "1");
                                $propertyOld = $reflection->getProperty($old);
                                $propertyOld->setValue($tableObj, $valori[$i] === "true" || $valori[$i] === "1");
                            }
                        }
                        else //in teoria è sempre int
                        {
                            if ($prop->name == "ParentId")
                            {
                                $property = $baseClass->getProperty($prop->name);
                                $property->setValue($tableObj, (int)$valori[$i]);

                                $propertyOld = $baseClass->getProperty($old);
                                $propertyOld->setValue($tableObj, (int)$valori[$i]);
                            }
                            elseif ($prop->name != "Id")
                            {
                                $propertyOld = $reflection->getProperty($old);
                                $propertyOld->setValue($tableObj, (int)$valori[$i]);
                                $prop->setValue($tableObj, (int)$valori[$i]);
                            }
                            else
                                $prop->setValue($tableObj, (int)$valori[$i]);
                        }
                    }
                    break;

                case "Dato":
                    {
                        $prop->setValue($tableObj, (int)$valori[$i]);

                        $old = '_' . $prop->name;
                        $propertyOld = $reflection->getProperty($old);
                        $propertyOld->setValue($tableObj, (int)$valori[$i]);
                    }
                    break;

                case "Testo":
                    {
                        $prop->setValue($tableObj, $valori[$i]);

                        $old = '_' . $prop->name;
                        $propertyOld = $reflection->getProperty($old);
                        $propertyOld->setValue($tableObj, $valori[$i]);
                    }
                    break;

                case "Data":
                    {
                        $old = '_' . $prop->name;


                        $len = strlen($valori[$i]);

                        $a = $valori[$i];

                        if ($len == 10)
                        {
                            $str = $a[0] . $a[1] . '/' . $a[3] . $a[4] . '/' . $a[6] . $a[7] . $a[8] . $a[9] . ' ' . "00:00:00";

                            $prop->setValue($tableObj, \DateTime::createFromFormat('d/m/Y H:i:s', $str));

                            $propertyOld = $reflection->getProperty($old);
                            $propertyOld->setValue($tableObj, \DateTime::createFromFormat('d/m/Y H:i:s', $str));
                        }

                        if ($len == 19)
                        {
                            $str = $a[0] . $a[1] . '/' . $a[3] . $a[4] . '/' . $a[6] . $a[7] . $a[8] . $a[9] . ' ' .
                                $a[11] . $a[12] . ':' . $a[14] . $a[15] . ':' . $a[17] . $a[18];

                            $prop->setValue($tableObj, \DateTime::createFromFormat('d/m/Y H:i:s', $str));

                            if ($prop->name == "Aggiornamento" || $prop->name == "Inserimento")
                                break;

                            $propertyOld = $reflection->getProperty($old);
                            $propertyOld->setValue($tableObj, \DateTime::createFromFormat('d/m/Y H:i:s', $str));
                        }
                    }
                    break;

                default: //immagini, file e senza definizione
                    $prop->setValue($tableObj, $valori[$i]);
                    break;
            }
        }

        if (!$cache)
            return;

        $tableName = get_class($tableObj);

        //salvo in cache ogni valore univoco
        foreach ($univoci as $univoco)
        {
            $propertyName = str_replace(" ", "_", $univoco);

            $uniqueValue = $tableObj->$propertyName;

            // verifica se $uniqueValue è un istanza di DateTime
            if($uniqueValue instanceof DateTime)
            {
                // se lo è, lo formatta come stringa
                $uniqueValue = $uniqueValue->format('Y-m-d H:i:s');
            }

            $searchKey = strtolower("item|" . $tableName . "|" . $univoco . "|" . $uniqueValue . "|" . $iso);

            \Common\Cache::SetDati($searchKey, $tableObj);
        }
    }

    function Save(bool $onSave, string $iso): SaveResponse
    {
        $nuovo = $this->Id == 0;

        $tableName = get_class($this);

        \Common\Cache::ResetDati($tableName);

        $reflection = new ReflectionClass($tableName);

        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PRIVATE);

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
                {
                    continue;
                }

                $propertyValue = $property->getValue($this);

                switch ($tipo)
                {
                    case "Dato":
                    case "Testo":
                    case "Numeri":
                    {
                        $oldValue = "";

                        if ($nuovo)
                        {
                            $colonne[] = [$nome, $propertyValue];
                        }
                        else
                        {
                            if ($property->name == "Id")
                            {
                                $colonne[] = [$nome, $propertyValue];
                            }
                            elseif ($property->name == "ParentId")
                            {
                                $propertyOld = $reflection->getParentClass()->getProperty("_" . $property->name); //parentid è nella basemodel
                                $oldValue = $propertyOld->getValue($this);
                            }
                            elseif ($property->name == "Visibile")
                            {
                                $propertyOld = $reflection->getParentClass()->getProperty("_" . $property->name); //Visibile è nella basemodel
                                $oldValue = $propertyOld->getValue($this);
                            }
                            else
                            {
                                $propertyOld = $reflection->getProperty("_" . $property->name);
                                $oldValue = $propertyOld->getValue($this);
                            }

                            //salvo solo se il valore è stato modificato
                            if ($oldValue !== $propertyValue)
                            {
                                $colonne[] = [$nome, $propertyValue];
                            }
                        }

                        break;
                    }

                    case "Data":
                    {
                        if ($property->name == "Aggiornamento" || $property->name == "Inserimento")
                        {
                            break;
                        }

                        $dateNew = $propertyValue->format('d/m/Y');

                        if ($nuovo)
                        {
                            $colonne[] = [$nome, $dateNew];
                        }
                        else
                        {
                            $propertyOld = $reflection->getProperty("_" . $property->name);
                            $oldValue = $propertyOld->getValue($this);
                            $dateOld = $oldValue->format('d/m/Y');

                            //salvo solo se il valore è stato modificato
                            if ($dateOld != $dateNew)
                            {
                                $colonne[] = [$nome, $dateNew];
                            }
                        }

                        break;
                    }

                    case "Immagini":
                    case "File":
                    {
                        if (!isset($propertyValue))
                        {
                            break;
                        }

                        if (\Common\Convert::ToBool($propertyValue->Base64Encoded))
                        {
                            $colonne[] = [$nome, [$propertyValue->Nome, $propertyValue->Bytes]];
                        }
                        else
                        {
                            $colonne[] = [$nome, [$propertyValue->Nome, base64_encode($propertyValue->Bytes)]];
                        }

                        break;
                    }
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

        $parentId = 0;

        //non aggiorno il padre se è sempre uguale
        if ($this->_ParentId != $this->ParentId)
            $parentId = $this->ParentId; //se è 0 c# serverpipe non lo aggiorna

        $visible = "";

        if ($this->_Visibile != $this->Visibile)
            $visible = $this->Visibile;

        //prendo i valori dal db
        $result = $obj->DatiElencoSaveAvvisi(
            $partialName,
            $this->Id,
            $parentId,
            $visible,
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
                {
                    $saveRespone->InternalAvvisi[$controlloAvviso->Controllo] = $controlloAvviso->Avviso;
                }
            }

            return $saveRespone;
        }

        $this->Id = $result->Id;

        $saveRespone->Success = true;
        return $saveRespone;
    }

    function Delete(): SaveResponse
    {
        $tableName = get_class($this);

        \Common\Cache::ResetDati($tableName);

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
        int    $item4page = -1,
        int    $page = -1,
        string $wherePredicate = '',
        array  $whereValues = [],
        string $orderPredicate = '',
        string $iso = '',
        int    $parentId = 0,
        bool   $visible = null,
        bool   $webP = true,
        bool   $encode = false,
        array  $selectColumns = [])
    {
        $searchKey = strtolower("list|" .
                                $tableName . "|" .
                                $item4page . "|" .
                                $page . "|" .
                                $wherePredicate . "|" .
                                implode(",", $whereValues) . "|" .
                                $orderPredicate . "|" .
                                $iso . "|" .
                                $parentId . "|" .
                                $visible . "|" .
                                $webP . "|" .
                                $encode . "|" .
                                implode(",", $selectColumns));

        $success = false;

        $items = \Common\Cache::GetDati($searchKey, $success);

        if ($success)
        {
            foreach ($items as $item)
                yield clone $item;

            return;
        }

        $reflection = new \ReflectionClass($tableName);

        $properties = [];
        $colonne = [];
        $tipi = [];
        $univoci = [];

        $filterColumns = count($selectColumns) > 0;

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property)
        {
            $attributes = $property->getAttributes();

            foreach ($attributes as $attribute)
            {
                $arguments = $attribute->getArguments();

                $nome = $arguments['0'];
                $tipo = $arguments['1'];
                $univoco = $arguments['2'];

                if ($tipo == "Dato")
                {
                    $nome .= "_FkId";
                }

                if ($filterColumns) //se sto recuperando solo alcune colonne
                {
                    $found = array_search($nome, $selectColumns);

                    if ($found !== false)
                    {
                        $properties[] = $property;
                        $colonne[] = $nome;
                        $tipi[] = $tipo;

                        if ($univoco)
                        {
                            $univoci[] = $arguments['0'];
                        }
                    }
                }
                else
                {
                    $properties[] = $property;
                    $colonne[] = $nome;
                    $tipi[] = $tipo;

                    if ($univoco)
                    {
                        $univoci[] = $arguments['0'];
                    }
                }
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
            $e = new \Exception();
            $trace = $e->getTraceAsString();

            //viene già loggata da doweb
            throw new \Exception("Errore nella GetList " . $trace . ", " . $result->Avviso);
        }

        $cache = [];

        $terminated = false;

        $count = 0;

        try
        {
            while (true)
            {
                $valori = $obj->FetchRead();

                if ($valori == null)
                {
                    $terminated = true;

                    return;
                }

                $count++;

                $tableObj = $reflection->newInstance();

                //imposto i valori nella istanza di classe
                self::ImpostoIValoriNellaIstanzaDiClasse($iso, !$filterColumns, $properties, $tipi, $univoci, $tableObj, $valori, $reflection);

                $cache[] = $tableObj;

                yield $tableObj;
            }


        }
        finally
        {
            $obj->FetchClose();

            if ($terminated || $count == $item4page)
                \Common\Cache::SetDati($searchKey, $cache);
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
        $searchKey = strtolower("count|" .
                                $tableName . "|" .
                                $wherePredicate . "|" .
                                implode(",", $whereValues) . "|" .
                                $iso . "|" .
                                $parentId . "|" .
                                $visible . "|" .
                                $encode . "|");

        $success = false;

        $count = \Common\Cache::GetDati($searchKey, $success);

        //if ($success)
          //  return $count;

        //del nome Model\Tipo, prendo solo l'ultimo pezzo: Tipo
        $parts = explode("\\", $tableName);
        $datoNome = end($parts);

        //i nomi delle classi hanno lo spazio sostituito il simbolo
        $datoNome = str_replace("_", " ", $datoNome);

        $obj = PHPDOWEB();

        //prendo i valori dal db
        $result = $obj->DatiElencoGetCount($datoNome, $parentId, $visible, $iso, $wherePredicate, $whereValues, $encode);

        if (\Common\Convert::ToBool($result->Errore))
        {
            //viene loggata da doweb
            //$obj->LogError("BaseModel->BaseList({$tableName}, {$wherePredicate}) " . $result->Avviso);

            $e = new \Exception();
            $trace = $e->getTraceAsString();

            //viene già loggata da doweb
            throw new \Exception("Errore nella GetCount " . $trace . ", " . $result->Avviso);

        }

        $tot = intval($result->Count);

        \Common\Cache::SetDati($searchKey, $tot);

        return $tot;
    }
}