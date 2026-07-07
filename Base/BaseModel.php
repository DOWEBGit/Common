<?php

declare(strict_types=1);

namespace Common\Base;

use Common\Attribute;
use Common\Attribute\PropertyAttribute;
use Common\Response\SaveResponse;
use DateTime;
use ReflectionClass;
use ReflectionProperty;

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

        foreach ($fields as $name => $value) {
            if (str_starts_with($name, "_")) {
                continue;
            }

            $output .= $name . ": ";

            switch (gettype($value)) {
                case 'object':
                    if ($value instanceof DateTime) {
                        if ($value->format('H:i') == '00:00') {
                            $output .= $value->format('Y-m-d');
                        } else {
                            $output .= $value->format('Y-m-d H:i:s');
                        }
                    } else {
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

        foreach ($fields as $name => $value) {
            if (gettype($value) !== "string") {
                continue;
            }

            $this->$name = html_entity_decode($value);
        }
    }

    /**
     * Confronta i valori delle proprietà tra due oggetti, trattando le stringhe HTML-encoded e decodificate come equivalenti.
     * Salta i campi tecnici come Id, ParentId, Visibile, Aggiornamento, Inserimento e quelli che iniziano con _
     * @param mixed $other
     * @return bool
     */
    public function EqualsValues(BaseModel $other): bool
    {
        if (!is_object($other)) {
            return false;
        }

        foreach ($this as $key => $value) {
            // Salta proprietà tecniche e di sistema
            if (
                $key === 'Id' ||
                $key === 'Visibile' ||
                $key === 'Aggiornamento' ||
                $key === 'Inserimento'
            ) {
                continue;
            }

            if (!property_exists($other, $key)) {
                continue;
            }

            $otherValue = $other->$key;

            // Se entrambi sono stringhe, confronta dopo aver decodificato HTML
            if (is_string($value) && is_string($otherValue)) {
                $decodedA = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $decodedB = html_entity_decode($otherValue, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if ($decodedA !== $decodedB) {
                    return false;
                }
            } // Se entrambi sono array, confronta ricorsivamente
            elseif (is_array($value) && is_array($otherValue)) {
                if (count($value) !== count($otherValue)) {
                    return false;
                }
                foreach ($value as $k => $v) {
                    if (!array_key_exists($k, $otherValue)) {
                        return false;
                    }
                    // Ricorsivo se array di oggetti
                    if (is_object($v) && is_object($otherValue[$k])) {
                        if (!$v->EqualsValues($otherValue[$k])) {
                            return false;
                        }
                    } elseif ($v !== $otherValue[$k]) {
                        return false;
                    }
                }
            } // Se entrambi sono oggetti, confronta ricorsivamente
            elseif (is_object($value) && is_object($otherValue)) {
                if (method_exists($value, 'EqualsValues')) {
                    if (!$value->EqualsValues($otherValue)) {
                        return false;
                    }
                } elseif ($value != $otherValue) {
                    return false;
                }
            } // Altri tipi: confronto diretto
            elseif ($value !== $otherValue) {
                return false;
            }
        }
        return true;
    }

    #[PropertyAttribute('Id', 'Numeri', true)]
    public int $Id;

    #[PropertyAttribute('ParentId', 'Numeri', false)]
    public int $ParentId
        {
            get{
                return $this->ParentId;
            }

            set(int $parentId){
                $this->ParentId = $parentId;
                $this->_ParentIdSet = true;
            }
        }

    private bool $_ParentIdSet = false;

    #[PropertyAttribute('Visibile', 'Numeri', false)]
    public bool $Visibile
        {
            get {
                return $this->Visibile;
            }

            set {
                $this->_VisibileSet = true;
                $this->Visibile = $value;
            }
        }

    private bool $_VisibileSet = false;

    #[PropertyAttribute('Aggiornamento', 'Data', false)]
    public DateTime $Aggiornamento;

    #[PropertyAttribute('Inserimento', 'Data', false)]
    public DateTime $Inserimento;

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    //    non ci sono i tipi anonimi in PHP quindi passo l'oggetto come parametro
    static function GetItem(
        object $tableObj,
        int $parent = 0,
        string $uniqueColumn = "Id",
        $uniqueValue = "",
        string $iso = "",
        bool $webP = true,
        array $selectColumns = []
    ): ?BaseModel {
        $tableName = get_class($tableObj);

        // verifica se $uniqueValue è un istanza di DateTime
        if ($uniqueValue instanceof DateTime) {
            // se lo è, lo formatta come stringa
            $uniqueValue = $uniqueValue->format('d-m-Y H:i:s');  //nel named pipe viene letto in questo formato
        }

        $searchKey = strtolower(
            "item|" . $tableName . "|" . $parent . "|" . $uniqueColumn . "|" . $uniqueValue . "|" . $iso . "|" . implode(
                "-",
                $selectColumns
            )
        );

        $success = false;

        $value = \Common\Cache::GetDati($searchKey, $success);

        if ($success) {
            if (!$value) {
                return null;
            }

            return clone $value;
        }

        $reflection = new \ReflectionClass($tableName);

        $properties = [];
        $colonne = [];
        $tipi = [];
        $univoci = [];

        $filterColumns = count($selectColumns) > 0;

        //recupero le colonne della classe dalle etichette sulle variabili
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $attributes = $property->getAttributes();

            foreach ($attributes as $attribute) {
                $arguments = $attribute->getArguments();

                $nome = $arguments['0'];
                $tipo = $arguments['1'];
                $univoco = $arguments['2'];

                if ($tipo == "Dato") {
                    $nome .= "_FkId";
                }

                if ($filterColumns) {
                    $found = array_search($arguments['0'], $selectColumns);

                    if ($found !== false) {
                        $properties[] = $property;
                        $colonne[] = $nome;
                        $tipi[] = $tipo;

                        if ($univoco) {
                            $univoci[] = $arguments['0'];
                        }
                    }
                } else {
                    $properties[] = $property;
                    $colonne[] = $nome;
                    $tipi[] = $tipo;

                    if ($univoco) {
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

        /** @noinspection PhpUndefinedFunctionInspection */
        $obj = PHPDOWEB();

        //prendo i valori dal db
        $result = $obj->DatiElencoGetItem(
            $partialName,
            $uniqueColumn,
            (string)$uniqueValue,
            $iso,
            $colonne,
            (string)$webP,
            false,
            parent: $parent
        );

        if (\Common\Convert::ToBool($result->Errore)) {
            $e = new \Exception();
            $trace = $e->getTraceAsString();

            $obj->LogError(
                "BaseModel->GetItem({$tableName}, {$uniqueColumn}, {$parent}) " . $result->Avviso . " -> " . $trace
            );
            return null;
        }

        $valori = $result->Values;

        if (count($valori) == 0) {
            \Common\Cache::SetDati($searchKey, null);

            return null;
        }

        self::ImpostoIValoriNellaIstanzaDiClasse(
            $parent,
            $iso,
            !$filterColumns,
            $properties,
            $tipi,
            $univoci,
            $tableObj,
            $valori,
            $reflection
        );

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
    private static function ImpostoIValoriNellaIstanzaDiClasse(
        int $parent,
        string $iso,
        bool $cache,
        array $properties,
        array $tipi,
        array $univoci,
        object &$tableObj,
        $valori,
        \ReflectionClass $reflection
    ): void {
        //in questo modo se salvo questa istanza a cui rimane l'id a -1, ovvero non viene recuperato l'id, mi tira un'errore
        $tableObj->Id = -1;

        $baseClass = $reflection->getParentClass();

        for ($i = 0; $i < count($tipi); $i++) {
            $prop = $properties[$i];

            $type = $prop->getType();
            $typeName = $type->getName(); //ritorna in stringa "int" "string "bool"

            $tipo = $tipi[$i];

            switch ($tipo) {
                case "Numeri":
                    {
                        if ($typeName == "bool") {
                            $val = $valori[$i] === "true" || $valori[$i] === "1";
                            if ($prop->name == "Visibile") {
                                $baseClass->getProperty($prop->name)->setRawValue($tableObj, $val);
                            } else {
                                $prop->setRawValue($tableObj, $val);
                            }
                        } else {
                            $val = (int)$valori[$i];
                            if ($prop->name == "ParentId") {
                                $baseClass->getProperty($prop->name)->setRawValue($tableObj, $val);
                            } else {
                                $prop->setRawValue($tableObj, $val);
                            }
                        }
                    }
                    break;

                case "Dato":
                    {
                        $prop->setRawValue($tableObj, (int)$valori[$i]);
                    }
                    break;

                case "Testo":
                    {
                        $prop->setRawValue($tableObj, $valori[$i]);
                    }
                    break;

                case "DataOra":
                case "Data":
                    {
                        $len = strlen($valori[$i]);
                        $a = $valori[$i];

                        if ($len == 10) {
                            $str = $a[0] . $a[1] . '/' . $a[3] . $a[4] . '/' . $a[6] . $a[7] . $a[8] . $a[9] . ' ' . "00:00:00";
                            $prop->setRawValue($tableObj, \DateTime::createFromFormat('d/m/Y H:i:s', $str));
                        }

                        if ($len == 16) {
                            $str = $a[0] . $a[1] . '/' . $a[3] . $a[4] . '/' . $a[6] . $a[7] . $a[8] . $a[9] . ' ' .
                                $a[11] . $a[12] . ':' . $a[14] . $a[15] . ':00';

                            if ($prop->name == "Aggiornamento" || $prop->name == "Inserimento") {
                                break;
                            }

                            $prop->setRawValue($tableObj, \DateTime::createFromFormat('d/m/Y H:i:s', $str));
                        }

                        if ($len == 19) {
                            $str = $a[0] . $a[1] . '/' . $a[3] . $a[4] . '/' . $a[6] . $a[7] . $a[8] . $a[9] . ' ' .
                                $a[11] . $a[12] . ':' . $a[14] . $a[15] . ':' . $a[17] . $a[18];

                            $prop->setRawValue($tableObj, \DateTime::createFromFormat('d/m/Y H:i:s', $str));
                        }
                    }
                    break;

                default: //immagini, file e senza definizione
                    $prop->setValue($tableObj, $valori[$i]);
                    break;
            }
        }

        foreach ($reflection->getProperties(ReflectionProperty::IS_PRIVATE) as $p) {
            if (str_starts_with($p->name, "_") && str_ends_with($p->name, "Set")) {
                $p->setValue($tableObj, false);
            }
        }
        foreach ($baseClass->getProperties(ReflectionProperty::IS_PRIVATE) as $p) {
            if (str_starts_with($p->name, "_") && str_ends_with($p->name, "Set")) {
                $p->setValue($tableObj, false);
            }
        }

        if (!$cache) {
            return;
        }

        $tableName = get_class($tableObj);

        //salvo in cache ogni valore univoco
        foreach ($univoci as $uniqueColumn) {
            $propertyName = str_replace(" ", "_", $uniqueColumn);

            $uniqueValue = $tableObj->$propertyName;

            // verifica se $uniqueValue è un istanza di DateTime
            if ($uniqueValue instanceof DateTime) {
                // se lo è, lo formatta come stringa
                $uniqueValue = $uniqueValue->format('d-m-Y H:i:s'); //nel named pipe viene letto in questo formato
            }

            $searchKey = strtolower(
                "item|" . $tableName . "|" . $parent . "|" . $uniqueColumn . "|" . $uniqueValue . "|" . $iso
            );

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
        foreach ($properties as $property) {
            $attributes = $property->getAttributes();

            //c'è massimo un solo attributo che è il nome del database, per adesso
            foreach ($attributes as $attribute) {
                $nome = $attribute->getArguments()['0'];
                $tipo = $attribute->getArguments()['1'];

                if ($tipo == "") {
                    continue;
                }

                $propertyValue = $property->getValue($this);

                switch ($tipo) {
                    case "Dato":
                    case "Testo":
                    case "Numeri":
                    {
                        if ($nuovo) {
                            $colonne[] = [$nome, $propertyValue];
                        } else {
                            if ($property->name == "Id") {
                                $colonne[] = [$nome, $propertyValue];
                            } elseif ($property->name == "ParentId" || $property->name == "Visibile") {
                                // gestiti separatamente tramite $parentId e $visible
                            } else {
                                $setFlag = $reflection->getProperty('_' . $property->name . "Set");
                                if ($setFlag->getValue($this)) {
                                    $colonne[] = [$nome, $propertyValue];
                                }
                            }
                        }

                        break;
                    }

                    case "DataOra":
                    {
                        if ($property->name == "Aggiornamento" || $property->name == "Inserimento") {
                            break;
                        }

                        $dateNew = $propertyValue->format('d/m/Y H:i:s');

                        if ($nuovo) {
                            $colonne[] = [$nome, $dateNew];
                        } else {
                            $setFlag = $reflection->getProperty('_' . $property->name . "Set");
                            if ($setFlag->getValue($this)) {
                                $colonne[] = [$nome, $dateNew];
                            }
                        }

                        break;
                    }

                    case "Data":
                    {
                        if ($property->name == "Aggiornamento" || $property->name == "Inserimento") {
                            break;
                        }

                        $dateNew = $propertyValue->format('d/m/Y');

                        if ($nuovo) {
                            $colonne[] = [$nome, $dateNew];
                        } else {
                            $setFlag = $reflection->getProperty('_' . $property->name . "Set");
                            if ($setFlag->getValue($this)) {
                                $colonne[] = [$nome, $dateNew];
                            }
                        }

                        break;
                    }

                    case "Immagini":
                    case "File":
                    {
                        if (!isset($propertyValue)) {
                            break;
                        }

                        if (\Common\Convert::ToBool($propertyValue->Base64Encoded)) {
                            $colonne[] = [$nome, [$propertyValue->Nome, $propertyValue->Bytes]];
                        } else {
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

        /** @noinspection PhpUndefinedFunctionInspection */
        $obj = PHPDOWEB();

        $parentId = 0;

        //non aggiorno il padre se è sempre uguale
        if ($this->_ParentIdSet) {
            $parentId = $this->ParentId;
        } //se è 0 c# serverpipe non lo aggiorna

        $visible = "";

        if ($this->_VisibileSet) {
            $visible = $this->Visibile;
        }

        //prendo i valori dal db
        $result = $obj->DatiElencoSaveAvvisi(
            $partialName,
            $this->Id,
            $parentId,
            $visible,
            $iso,
            $colonne,
            $onSave
        );

        $saveRespone = new SaveResponse();

        if (\Common\Convert::ToBool($result->Errore)) {
            $saveRespone->Success = false;

            if ($result->Avviso !== "") {
                $saveRespone->InternalAvviso = $result->Avviso;
            } else {
                foreach ($result->Avvisi as $controlloAvviso) {
                    $saveRespone->InternalAvvisi[$controlloAvviso->Controllo] = $controlloAvviso->Avviso;
                }
            }

            return $saveRespone;
        }


        // azzero i Set flag così una eventuale successiva save funziona
        foreach ($properties as $property) {
            if (!$property->isPrivate()) {
                continue;
            }
            if (!str_starts_with($property->name, "_") || !str_ends_with($property->name, "Set")) {
                continue;
            }

            $property->setValue($this, false);
        }

        $this->_ParentIdSet = false;
        $this->_VisibileSet = false;

        $this->Id = $result->Id;

        $saveRespone->Success = true;
        return $saveRespone;
    }

    function Delete(bool $onDelete = true): SaveResponse
    {
        $tableName = get_class($this);

        \Common\Cache::ResetDati($tableName);

        /** @noinspection PhpUndefinedFunctionInspection */
        $obj = PHPDOWEB();

        //prendo i valori dal db
        $result = $obj->DatiElencoDelete($this->Id, $onDelete);

        $response = new SaveResponse();

        if (\Common\Convert::ToBool($result->Errore)) {
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
        ?bool $visible = null,
        bool $webP = true,
        bool $encode = false,
        array $selectColumns = [],
        array $groupBy = []
    ) {
        for ($i = 0; $i < count($whereValues); $i++) {
            if ($whereValues[$i] instanceof \DateTime) {
                $whereValues[$i] = $whereValues[$i]->format("d/m/Y H:i");
            }

            if ($whereValues[$i] instanceof \DateTimeImmutable) {
                $whereValues[$i] = $whereValues[$i]->format("d/m/Y H:i");
            }

            if (is_bool($whereValues[$i])) {
                $whereValues[$i] = $whereValues[$i] ? 1 : 0;
            }
        }

        $searchKey = strtolower(
            "list|" .
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
            implode(",", $selectColumns) . "|" .
            implode(",", $groupBy)
        );

        $success = false;

        $items = \Common\Cache::GetDati($searchKey, $success);

        if ($success) {
            foreach ($items as $item) {
                yield clone $item;
            }

            return;
        }

        $reflection = new \ReflectionClass($tableName);

        $properties = [];
        $colonne = [];
        $tipi = [];
        $univoci = [];

        $filterColumns = count($selectColumns) > 0;

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $attributes = $property->getAttributes();

            foreach ($attributes as $attribute) {
                $arguments = $attribute->getArguments();

                $nome = $arguments['0'];
                $tipo = $arguments['1'];
                $univoco = $arguments['2'];

                if ($tipo == "Dato") {
                    $nome .= "_FkId";
                }

                if ($filterColumns) //se sto recuperando solo alcune colonne
                {
                    $found = array_search($arguments['0'], $selectColumns);

                    if ($found !== false) {
                        $properties[] = $property;
                        $colonne[] = $nome;
                        $tipi[] = $tipo;

                        if ($univoco) {
                            $univoci[] = $arguments['0'];
                        }
                    }
                } else {
                    $properties[] = $property;
                    $colonne[] = $nome;
                    $tipi[] = $tipo;

                    if ($univoco) {
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

        /** @noinspection PhpUndefinedFunctionInspection */
        $obj = PHPDOWEB();

        $result = $obj->FetchOpen(
            $datoNome,
            $parentId,
            $visible,
            $iso,
            $wherePredicate,
            $whereValues,
            $colonne,
            $orderPredicate,
            $item4page,
            $page,
            $webP,
            $encode,
            $groupBy
        );

        if (\Common\Convert::ToBool($result->Errore)) {
            $e = new \Exception();
            $trace = $e->getTraceAsString();

            //viene già loggata da doweb
            throw new \Exception("Errore nella GetList, controlla il log error, " . $trace . ", " . $result->Avviso);
        }

        $cache = [];

        $terminated = false;

        $count = 0;

        try {
            while (true) {
                $valori = $obj->FetchRead();

                if ($valori == null) {
                    $terminated = true;

                    return;
                }

                $count++;

                $tableObj = $reflection->newInstance();

                //imposto i valori nella istanza di classe
                self::ImpostoIValoriNellaIstanzaDiClasse(
                    $parentId,
                    $iso,
                    !$filterColumns,
                    $properties,
                    $tipi,
                    $univoci,
                    $tableObj,
                    $valori,
                    $reflection
                );

                $cache[] = $tableObj;

                yield $tableObj;
            }
        }
        finally {
            $obj->FetchClose();

            if ($terminated || $count == $item4page) {
                \Common\Cache::SetDati($searchKey, $cache);
            }
        }
    }

    static function BaseCount(
        string $tableName,
        string $wherePredicate = '',
        array $whereValues = [],
        string $iso = '',
        int $parentId = 0,
        ?bool $visible = null,
        bool $encode = false,
        array $groupBy = []
    ): int {
        for ($i = 0; $i < count($whereValues); $i++) {
            if ($whereValues[$i] instanceof \DateTime) {
                $whereValues[$i] = $whereValues[$i]->format("d/m/Y H:i");
            }

            if ($whereValues[$i] instanceof \DateTimeImmutable) {
                $whereValues[$i] = $whereValues[$i]->format("d/m/Y H:i");
            }

            if (is_bool($whereValues[$i])) {
                $whereValues[$i] = $whereValues[$i] ? 1 : 0;
            }
        }


        $searchKey = strtolower(
            "count|" .
            $tableName . "|" .
            $wherePredicate . "|" .
            implode(",", $whereValues) . "|" .
            $iso . "|" .
            $parentId . "|" .
            $visible . "|" .
            $encode . "|" .
            implode(",", $groupBy)
        );

        $success = false;

        $count = \Common\Cache::GetDati($searchKey, $success);

        if ($success) {
            return $count;
        }

        //del nome Model\Tipo, prendo solo l'ultimo pezzo: Tipo
        $parts = explode("\\", $tableName);
        $datoNome = end($parts);

        //i nomi delle classi hanno lo spazio sostituito il simbolo
        $datoNome = str_replace("_", " ", $datoNome);

        /** @noinspection PhpUndefinedFunctionInspection */
        $obj = PHPDOWEB();

        //prendo i valori dal db
        $result = $obj->DatiElencoGetCount(
            $datoNome,
            $parentId,
            $visible,
            $iso,
            $wherePredicate,
            $whereValues,
            $encode,
            $groupBy
        );

        if (\Common\Convert::ToBool($result->Errore)) {
            //viene loggata da doweb
            //$obj->LogError("BaseModel->BaseList({$tableName}, {$wherePredicate}) " . $result->Avviso);

            $e = new \Exception();
            $trace = $e->getTraceAsString();

            //viene già loggata da doweb
            throw new \Exception("Errore nella GetCount, controlla il log error, " . $trace . ", " . $result->Avviso);
        }

        $tot = intval($result->Count);

        \Common\Cache::SetDati($searchKey, $tot);

        return $tot;
    }
}