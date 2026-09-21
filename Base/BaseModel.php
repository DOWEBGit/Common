<?php

declare(strict_types=1);

namespace Common\Base;

use Common\Attribute;
use Common\Attribute\PropertyAttribute;
use Common\Attribute\VincoliAttribute;
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

    /*
     * ---------------------------------------------------------------------------
     * Memoizzazione della reflection.
     *
     * I metadati di una classe non cambiano mai durante la richiesta, ma venivano
     * ricalcolati a ogni GetItem, a ogni GetList e - per le proprieta' "_XxxSet" -
     * a ogni RIGA letta. Qui si calcolano una volta sola per classe.
     *
     * Le cache sono statiche di processo: PHP le azzera a fine richiesta, quindi non
     * c'e' rischio di trascinare metadati stantii fra richieste diverse.
     * ---------------------------------------------------------------------------
     */

    /** @var array<string,\ReflectionClass> */
    private static array $cacheReflection = [];

    /** @var array<string,array> metadati delle proprieta' pubbliche con attributo */
    private static array $cacheMetadati = [];

    /** @var array<string,array> proprieta' private "_XxxSet" da riazzerare a ogni riga */
    private static array $cacheFlagSet = [];

    private static function ReflectionDi(string $tableName): \ReflectionClass
    {
        return self::$cacheReflection[$tableName] ??= new \ReflectionClass($tableName);
    }

    /**
     * Elenco delle colonne ricavate dagli attributi, calcolato una volta per classe.
     * Ogni voce: [property, nome colonna, tipo, univoco, nome attributo].
     */
    private static function MetadatiDi(string $tableName): array
    {
        if (isset(self::$cacheMetadati[$tableName])) {
            return self::$cacheMetadati[$tableName];
        }

        $meta = [];

        foreach (self::ReflectionDi($tableName)->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            //SOLO il PropertyAttribute: sopra una proprieta' ce n'e' anche un altro, quello
            //dei vincoli, e i suoi argomenti sono nominali - letti per posizione darebbero
            //una colonna senza nome, cioe' metadati sbagliati per tutta la classe
            foreach ($property->getAttributes(PropertyAttribute::class) as $attribute) {
                $arguments = $attribute->getArguments();

                $nome = $arguments['0'];
                $tipo = $arguments['1'];

                if ($tipo == "Dato") {
                    $nome .= "_FkId";
                }

                $meta[] = [$property, $nome, $tipo, $arguments['2'], $arguments['0']];
            }
        }

        return self::$cacheMetadati[$tableName] = $meta;
    }

    /**
     * Proprieta' private "_XxxSet" della classe e della sua base. Prima venivano
     * ricavate con due getProperties() piu' i controlli sul nome PER OGNI RIGA letta.
     */
    private static function FlagSetDi(\ReflectionClass $reflection): array
    {
        $chiave = $reflection->getName();

        if (isset(self::$cacheFlagSet[$chiave])) {
            return self::$cacheFlagSet[$chiave];
        }

        $flag = [];

        $classi = [$reflection];

        $base = $reflection->getParentClass();
        if ($base) {
            $classi[] = $base;
        }

        foreach ($classi as $classe) {
            foreach ($classe->getProperties(\ReflectionProperty::IS_PRIVATE) as $p) {
                if (str_starts_with($p->name, "_") && str_ends_with($p->name, "Set")) {
                    $flag[] = $p;
                }
            }
        }

        return self::$cacheFlagSet[$chiave] = $flag;
    }

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

        $reflection = self::ReflectionDi($tableName);

        $properties = [];
        $colonne = [];
        $tipi = [];
        $univoci = [];

        $filterColumns = count($selectColumns) > 0;

        //le colonne arrivano dai metadati gia' calcolati per questa classe
        foreach (self::MetadatiDi($tableName) as [$property, $nome, $tipo, $univoco, $attributo]) {
            if ($filterColumns && array_search($attributo, $selectColumns) === false) {
                continue;
            }

            $properties[] = $property;
            $colonne[] = $nome;
            $tipi[] = $tipo;

            if ($univoco) {
                $univoci[] = $attributo;
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
            // Deve restare POSIZIONALE: mai "parent: $parent". I metodi dell'estensione
            // sono registrati con un arginfo variadico (unico parametro, di nome "args"),
            // quindi un argomento nominato finisce in extra_named_params e lo ZPP
            // posizionale non lo vede mai. Il controllo che segnalerebbe l'anomalia
            // (ZPP_ERROR_UNEXPECTED_EXTRA_NAMED) esiste solo dentro Z_PARAM_VARIADIC_EX,
            // che qui non c'e': il valore veniva scartato in silenzio e il filtro sul
            // carrello spariva, restituendo elementi di altri carrelli.
            $parent
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

        // Elenco gia' filtrato una volta per classe: prima si facevano due getProperties()
        // piu' i controlli sul nome PER OGNI RIGA letta da una lista.
        foreach (self::FlagSetDi($reflection) as $p) {
            $p->setValue($tableObj, false);
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

        //Prima di scomodare il pipe: i vincoli sono gia' qui, scritti dagli attributi che il
        //generatore copia dal pannello. Un salvataggio che sbaglia si ferma adesso, con la
        //stessa frase che direbbe Kestrel - che ricontrolla comunque: questo taglia il
        //viaggio, non la guardia.
        $vincoli = $this->Valida($nuovo);

        if (!$vincoli->Success)
            return $vincoli;

        $tableName = get_class($this);

        \Common\Cache::ResetDati($tableName);

        $reflection = self::ReflectionDi($tableName);

        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PRIVATE);

        $colonne = [];

        //recupero le colonne della classe dalle etichette sulle variabili
        foreach ($properties as $property) {
            //SOLO il PropertyAttribute: sopra la stessa proprieta' c'e' anche quello dei
            //vincoli, che ha argomenti nominali - preso per posizione darebbe una colonna
            //senza nome, e la si scriverebbe nel database
            $attributes = $property->getAttributes(PropertyAttribute::class);

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
                            } elseif ($property->name != "ParentId" && $property->name != "Visibile") {
                                // ParentId e Visibile non passano di qui: viaggiano nei loro
                                // parametri, e scriverli anche fra le colonne li manderebbe due volte
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

                        // I byte del file viaggiano GREZZI, nella coda binaria della
                        // richiesta: l'estensione li mette da parte e nel JSON lascia solo
                        // {"Name":"...","Stream":K}. Il base64 costava il 33% di byte in
                        // piu' sul filo e, lato server, una stringa .NET in UTF-16 che
                        // pesava il doppio del base64 stesso.
                        $colonne[] = [
                            $nome,
                            [
                                $propertyValue->Nome,
                                \Common\Convert::ToBool($propertyValue->Base64Encoded)
                                    ? base64_decode($propertyValue->Bytes)
                                    : $propertyValue->Bytes,
                            ],
                        ];

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

        //l'entita' e' cambiata: le pagine aperte altrove che si erano iscritte a questo topic
        //se ne accorgono e rileggono. Sta DOPO l'assegnazione dell'Id perche' su un
        //inserimento prima di qui l'Id e' ancora 0, e un topic "Categorie/0" non lo aspetta
        //nessuno. L'evento parte a fine richiesta e non porta dati, vedi EntityEvents
        \Common\WebForms\EntityEvents::Notify(self::NomeEntita($tableName), $this->Id);

        $saveRespone->Success = true;
        return $saveRespone;
    }

    /**
     * Il nome con cui l'entita' si annuncia: la classe senza il namespace.
     *
     * Le pagine si iscrivono a "Categorie", non a "Model\Categorie": il topic e' il nome del
     * dato, e chi ascolta non deve sapere in che namespace sta la classe che l'ha scritto.
     */
    private static function NomeEntita(string $tableName): string
    {
        $taglio = strrpos($tableName, '\\');

        return $taglio === false ? $tableName : substr($tableName, $taglio + 1);
    }

    /**
     * Controlla i valori contro i vincoli dichiarati nel pannello.
     *
     * Cosa si controlla qui: obbligatorieta', lunghezza del testo, numero e lunghezza
     * delle parole, espressione regolare, minimo e massimo dei numeri. Sono tutte cose che
     * dipendono SOLO dal valore, quindi la risposta e' la stessa che darebbe Kestrel.
     *
     * Cosa NON si controlla: l'univocita', che vuole una lettura del database, e i file,
     * che si controllano quando si caricano. Quelle restano dove sono.
     *
     * Su un aggiornamento si guardano solo i campi TOCCATI: e' la stessa regola con cui il
     * motore scrive solo le colonne assegnate, e senza di quella una modifica parziale
     * verrebbe respinta per un campo che non si stava nemmeno cambiando.
     */
    public function Valida(bool $nuovo): SaveResponse
    {
        $risposta = new SaveResponse();
        $risposta->Success = true;

        foreach (self::ReflectionDi(get_class($this))->getProperties() as $property)
        {
            $vincoli = $property->getAttributes(VincoliAttribute::class);

            if ($vincoli === [])
                continue;

            $nome = $property->getName();

            //un campo non assegnato, su un aggiornamento, non si tocca e non si giudica
            if (!$nuovo && !$this->Assegnato($nome))
                continue;

            $regola = $vincoli[0]->newInstance();

            //newInstance() promette solo "object": il controllo costa niente e toglie di
            //mezzo il caso in cui qualcuno cambi il filtro di getAttributes()
            if (!$regola instanceof VincoliAttribute)
                continue;

            $avviso = self::Sbaglio($regola, $property, $this);

            if ($avviso === "")
                continue;

            $risposta->Success = false;
            $risposta->InternalAvvisi[$nome] = $avviso;
        }

        return $risposta;
    }

    /** Il campo e' stato assegnato in questa richiesta? Lo dice il flag _<nome>Set. */
    private function Assegnato(string $nome): bool
    {
        $flag = "_" . $nome . "Set";

        if (!property_exists($this, $flag))
            return true;

        $riflesso = new ReflectionProperty($this, $flag);

        return (bool)$riflesso->getValue($this);
    }

    /** Il tipo del campo come scritto nel PropertyAttribute (Testo, Numeri, Dato...), o "" se manca. */
    private static function TipoDato(ReflectionProperty $property): string
    {
        foreach ($property->getAttributes(PropertyAttribute::class) as $attribute)
            return (string)($attribute->getArguments()[1] ?? "");

        return "";
    }

    /** Cosa c'e' che non va in questo valore, o stringa vuota se va bene. */
    private static function Sbaglio(VincoliAttribute $vincoli, ReflectionProperty $property, object $modello): string
    {
        //una proprieta' non ancora inizializzata non ha un valore da giudicare
        if (!$property->isInitialized($modello))
            return "";

        $valore = $property->getValue($modello);

        //i file non si giudicano qui: si controllano quando si caricano, dove si ha il
        //file vero sotto mano invece di un percorso
        if (is_object($valore) || $valore === null)
            return "";

        if (is_bool($valore))
            return "";

        $mancante = $vincoli->AvvisoMancante !== "" ? $vincoli->AvvisoMancante : "Campo obbligatorio.";
        $nonValido = $vincoli->AvvisoNonValido !== "" ? $vincoli->AvvisoNonValido : "Valore non valido.";

        if (is_string($valore))
        {
            $pulito = trim(strip_tags($valore));

            if ($vincoli->Obbligatorio && $pulito === "")
                return $mancante;

            if ($pulito === "")
                return "";

            if ($vincoli->MaxCaratteri > 0 && mb_strlen($pulito) > $vincoli->MaxCaratteri)
                return $nonValido;

            $parole = preg_split("/\\s+/u", $pulito) ?: [];

            if ($vincoli->MaxParole > 0 && count($parole) > $vincoli->MaxParole)
                return $nonValido;

            if ($vincoli->LunghezzaParola > 0)
                foreach ($parole as $parola)
                    if (mb_strlen($parola) > $vincoli->LunghezzaParola)
                        return $nonValido;

            //l'espressione arriva dal pannello: se e' scritta male non deve buttare giu'
            //il salvataggio, quindi si prova a spegnere il rumore e in caso si lascia
            //passare - a dire di no ci pensa comunque Kestrel
            if ($vincoli->RegEx !== "" && @preg_match("/" . str_replace("/", "\\/", $vincoli->RegEx) . "/u", $pulito) === 0)
                return $nonValido;

            return "";
        }

        if (is_int($valore) || is_float($valore))
        {
            //Le foreign key (tipo Dato) non hanno un intervallo: il generatore scrive Min e Max
            //a 0, e presi alla lettera farebbero scartare qualunque id > 0, mentre Kestrel li
            //accetta. Sui campi Dato quindi Min/Max non si controllano
            if (self::TipoDato($property) === "Dato")
                return "";

            if ($vincoli->Min !== -1 && $valore < $vincoli->Min)
                return $nonValido;

            if ($vincoli->Max !== -1 && $valore > $vincoli->Max)
                return $nonValido;
        }

        return "";
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

        //come per la Save: chi guardava quell'elenco lo rilegge e la riga sparisce anche da la'
        \Common\WebForms\EntityEvents::Notify(self::NomeEntita($tableName), $this->Id);

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

        $reflection = self::ReflectionDi($tableName);

        $properties = [];
        $colonne = [];
        $tipi = [];
        $univoci = [];

        $filterColumns = count($selectColumns) > 0;

        //le colonne arrivano dai metadati gia' calcolati per questa classe
        foreach (self::MetadatiDi($tableName) as [$property, $nome, $tipo, $univoco, $attributo]) {
            if ($filterColumns && array_search($attributo, $selectColumns) === false) {
                continue;
            }

            $properties[] = $property;
            $colonne[] = $nome;
            $tipi[] = $tipo;

            if ($univoco) {
                $univoci[] = $attributo;
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