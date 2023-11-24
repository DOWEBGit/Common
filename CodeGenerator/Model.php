<?php
declare(strict_types=1);
header("Cache-Control: no-cache, no-store, must-revalidate");
header("expires: -1");

$basePath = $_SERVER["DOCUMENT_ROOT"] . "\\Public\\Php";


$path = $basePath . '/Model';

$files = glob($path . '/*'); // Get all files in the directory

foreach ($files as $file)
{
    if (is_file($file))
    {
        unlink($file); // Delete the file
        echo "Eliminato il file " . $file . "<br>";
    }
}


$tab = "    ";

$obj = PHPDOWEB();

$arr = $obj->DatiGetList();

$dati = $arr->Dati;

$bigFile = "";

foreach ($dati as $index => $dato)
{
    $figli = [];

    $externalCollection = "";

    foreach ($dati as $ar)
    {
        if ($ar->Parent == $dato->Id)
        {
            $nomeClasseFiglio = str_replace(" ", "_", $ar->Nome);

            $externalCollection .= $tab . "/** @return \Generator|" . $nomeClasseFiglio . "[] */\n";
            $externalCollection .= $tab . "public function " . $nomeClasseFiglio . "GetList(\n";
            $externalCollection .= $tab . $tab . "int $" . "item4page = -1,\n";
            $externalCollection .= $tab . $tab . "int $" . "page = -1,\n";
            $externalCollection .= $tab . $tab . "string $" . "wherePredicate = '',\n";
            $externalCollection .= $tab . $tab . "array $" . "whereValues = [],\n";
            $externalCollection .= $tab . $tab . "string $" . "orderPredicate = '',\n";
            $externalCollection .= $tab . $tab . "string $" . "iso = '',\n";
            $externalCollection .= $tab . $tab . "bool $" . "visible = null,\n";
            $externalCollection .= $tab . $tab . "bool $" . "webP = true,\n";
            $externalCollection .= $tab . $tab . "bool $" . "encode = false,\n";
            $externalCollection .= $tab . $tab . "array $" . "selectColumns = []) : \Generator\n";
            $externalCollection .= $tab . $tab . "{\n";
            $externalCollection .= $tab . $tab . $tab . "return BaseModel::BaseList('\Model\\" . $nomeClasseFiglio . "', $" . "item4page, $" . "page, $" . "wherePredicate, $" . "whereValues, $" . "orderPredicate, $" . "iso, $" . "this->Id, $" . "visible, $" . "webP, $" . "encode, $" . "selectColumns);\n";
            $externalCollection .= $tab . $tab . "}\n";

            $externalCollection .= "\n";

            $externalCollection .= $tab . "public function " . $nomeClasseFiglio . "GetCount(\n";
            $externalCollection .= $tab . $tab . "string $" . "wherePredicate = '',\n";
            $externalCollection .= $tab . $tab . "array $" . "whereValues = [],\n";
            $externalCollection .= $tab . $tab . "string $" . "iso = '',\n";
            $externalCollection .= $tab . $tab . "bool $" . "visible = null,\n";
            $externalCollection .= $tab . $tab . "bool $" . "encode = false) : int\n";
            $externalCollection .= $tab . $tab . "{\n";
            $externalCollection .= $tab . $tab . $tab . "return BaseModel::BaseCount('\Model\\" . $nomeClasseFiglio . "', $" . "wherePredicate, $" . "whereValues, $" . "iso, $" . "this->Id, $" . "visible, $" . "encode);\n";
            $externalCollection .= $tab . $tab . "}\n";

            $externalCollection .= "\n";
        }
    }

    $bigFile .= "prendi questa codice e memorizzalo, non descriverlo, dimmi solo ok quando lo hai letto";

    $parent = $dato->ParentNome;

    $code = "";

    $code .= "<?php\n";
    $code .= "declare(strict_types=1);\n\n";
    $code .= "namespace Model;\n";

    $code .= "use \Common\Base\BaseModel;\n";
    $code .= "use \Common\Base\PropertyAttribute;\n";
    $code .= "use \Common\Controlli\ControlloFile;\n";
    $code .= "use \Common\Controlli\ControlloImmagine;\n";
    $code .= "use \Common\Response\SaveResponse;\n\n";


    $nomeClasse = str_replace(" ", "_", $dato->Nome);

    $code .= "/**\n";
    $code .= "* " . $obj->Decode($dato->Descrizione) . "\n";
    $code .= "*/\n";
    $code .= "class " . $nomeClasse . " extends BaseModel\n";
    $code .= "{" . "\n\n";

    /*     * ****************** */
    //costruttore inizio
    /*     * ****************** */

    $code .= $tab . "function __construct()\n";
    $code .= $tab . "{\n";
    $code .= $tab . $tab . "parent::__construct();\n";

    $arr = $obj->DatiElencoGetColonne($dato->Nome);

    $colonneDettagliate = $arr->Colonne;

    $getItemUnivoche = "\n";
    $getItemUnivoche .= $tab . "/** @noinspection PhpIncompatibleReturnTypeInspection */\n";
    $getItemUnivoche .= $tab . "static function GetItemById(int $" . "Id, string $" . "iso = '', array $" . "selectColumns = []) : ?" . $nomeClasse . "\n";
    $getItemUnivoche .= $tab . "{\n";
    $getItemUnivoche .= $tab . $tab . "return BaseModel::GetItem(new " . $nomeClasse . "(), 'Id', $" . "Id, $" . "iso, selectColumns: $" . "selectColumns);\n";
    $getItemUnivoche .= $tab . "}\n";

    foreach ($colonneDettagliate as $colonna)
    {
        $identificativo = str_replace(" ", "_", $colonna->Identificativo);

        switch ($colonna->TipoDato)
        {
            case "Dato":
            {
                if ($colonna->DatiRefNome !== "")
                {
                    $identificativoRef = str_replace(" ", "_", $colonna->DatiRefNome);
                    $code .= $tab . $tab . "$" . "this->" . $identificativo . " = 0;\n";
                    $code .= $tab . $tab . "$" . "this->_" . $identificativo . " = 0;\n";
                }
                break;
            }

            case "Data":
            {
                $code .= $tab . $tab . "$" . "this->" . $identificativo . " = new \DateTime();\n";
                $code .= $tab . $tab . "$" . "this->_" . $identificativo . " = new \DateTime();\n";
                break;
            }

            case "Numeri":
            {
                if ($colonna->Univoco === "true" && $colonna->Obbligatorio === "true")
                {
                    $getItemUnivoche .= "\n";

                    $getItemUnivoche .= $tab . "/** @noinspection PhpIncompatibleReturnTypeInspection */\n";
                    $getItemUnivoche .= $tab . "public static function GetItemBy" . $identificativo . "(int $" . $identificativo . ", string $" . "iso = '', array $" . "selectColumns = []) : " . $nomeClasse . "\n";
                    $getItemUnivoche .= $tab . "{\n";
                    $getItemUnivoche .= $tab . $tab . "return BaseModel::GetItem(new " . $nomeClasse . "(), '" . $colonna->Identificativo . "', $" . $identificativo . ", $" . "iso, selectColumns: $" . "selectColumns);\n";
                    $getItemUnivoche .= $tab . "}\n";
                }

                if ($colonna->TipoInput == "CheckBox")
                {
                    $code .= $tab . $tab . "$" . "this->" . $identificativo . " = false;\n";
                    $code .= $tab . $tab . "$" . "this->_" . $identificativo . " = false;\n";
                }
                else
                {
                    $code .= $tab . $tab . "$" . "this->" . $identificativo . " = 0;\n";
                    $code .= $tab . $tab . "$" . "this->_" . $identificativo . " = 0;\n";
                }
                break;
            }

            case "Testo":
            {
                if ($colonna->Univoco === "true" && $colonna->Obbligatorio === "true")
                {
                    $getItemUnivoche .= "\n";

                    $getItemUnivoche .= $tab . "/** @noinspection PhpIncompatibleReturnTypeInspection */\n";
                    $getItemUnivoche .= $tab . "public static function GetItemBy" . $identificativo . "(string $" . $identificativo . ", string $" . "iso = '', array $" . "selectColumns = []) : ?" . $nomeClasse . "\n";
                    $getItemUnivoche .= $tab . "{\n";
                    $getItemUnivoche .= $tab . $tab . "return BaseModel::GetItem(new " . $nomeClasse . "(), '" . $colonna->Identificativo . "', $" . $identificativo . ", $" . "iso, selectColumns: $" . "selectColumns);\n";
                    $getItemUnivoche .= $tab . "}\n";
                }

                $code .= $tab . $tab . "$" . "this->" . $identificativo . " = '';\n";
                $code .= $tab . $tab . "$" . "this->_" . $identificativo . " = '';\n";
                break;
            }

            case "File":
            {
                $code .= $tab . $tab . "$" . "this->" . $identificativo . " = null;\n";
                break;
            }

            case "Immagini":
            {
                $code .= $tab . $tab . "$" . "this->" . $identificativo . "_Percorso = '';\n";
                $code .= $tab . $tab . "$" . "this->" . $identificativo . " = null;\n";
                break;
            }
        }
    }

    $code .= $tab . "}\n\n";

    foreach ($colonneDettagliate as $colonnaDettagliata)
    {
        $identificativo = str_replace(" ", "_", $colonnaDettagliata->Identificativo);

        $code .= $tab . "/**\n";
        $code .= $tab . "* " . $obj->Decode($colonnaDettagliata->Descrizione) . "\n";
        $code .= $tab . "*/\n";

        switch ($colonnaDettagliata->TipoDato)
        {
            case "Dato":
            {
                if ($colonnaDettagliata->DatiRefNome !== "")
                {
                    $identificativoRef = str_replace(" ", "_", $colonnaDettagliata->DatiRefNome);

                    $code .= $tab . "#[PropertyAttribute('" . $colonnaDettagliata->Identificativo . "', 'Dato', " . $colonnaDettagliata->Univoco . ")]\n";
                    $code .= $tab . "public int $" . $identificativo . ";\n";
                    $code .= $tab . "public function " . $identificativo . "_get($" . "iso = '', array $" . "selectColumns = []) : ?" . $identificativoRef . "\n";
                    $code .= $tab . "{ return " . $identificativoRef . "::GetItemById($" . "this->" . $identificativo . ", $" . "iso, $" . "selectColumns); }\n";
                    $code .= $tab . "public function " . $identificativo . "_set(?" . $identificativoRef . " $" . "value) : void\n";
                    $code .= $tab . "{ $" . "this-> " . $identificativo . " = $" . "value === null ? 0 : $" . "value->Id; }\n";
                    $code .= $tab . "private int $" . "_" . $identificativo . ";\n\n";
                }
                break;
            }

            case "Data":
            {
                $code .= $tab . "#[PropertyAttribute('" . $colonnaDettagliata->Identificativo . "', 'Data', " . $colonnaDettagliata->Univoco . ")]\n";
                $code .= $tab . "public \DateTime $" . $identificativo . ";\n";
                $code .= $tab . "private \DateTime $" . "_" . $identificativo . ";\n\n";
                break;
            }

            case "Numeri":
            {
                $type = "int";

                if ($colonnaDettagliata->TipoInput == "CheckBox")
                {
                    $type = "bool";
                }

                $code .= $tab . "#[PropertyAttribute('" . $colonnaDettagliata->Identificativo . "', 'Numeri', " . $colonnaDettagliata->Univoco . ")]\n";
                $code .= $tab . "public {$type} $" . $identificativo . ";\n";
                $code .= $tab . "private {$type} $" . "_" . $identificativo . ";\n\n";
                break;
            }

            case "Testo":
            {
                $code .= $tab . "#[PropertyAttribute('" . $colonnaDettagliata->Identificativo . "', 'Testo', " . $colonnaDettagliata->Univoco . ")]\n";
                $code .= $tab . "public string $" . $identificativo . ";\n";
                $code .= $tab . "private string $" . "_" . $identificativo . ";\n\n";
                break;
            }

            case "File":
            {
                $code .= $tab . "#[PropertyAttribute('" . $colonnaDettagliata->Identificativo . "_Percorso', '', " . $colonnaDettagliata->Univoco . ")]\n";
                $code .= $tab . "public string $" . $identificativo . "_Percorso;\n";
                $code .= $tab . "#[PropertyAttribute('" . $colonnaDettagliata->Identificativo . "', 'File', " . $colonnaDettagliata->Univoco . ")]\n";
                $code .= $tab . "private ?ControlloFile $" . $identificativo . ";\n";
                $code .= $tab . "public function " . $identificativo . "Get() : ?ControlloFile\n";
                $code .= $tab . "{\n";
                $code .= $tab . $tab . "if (isset($" . "this->" . $identificativo . "))\n";
                $code .= $tab . $tab . $tab . "return $" . "this->" . $identificativo . ";\n";
                $code .= $tab . $tab . "$" . "obj = PHPDOWEB()->DatiElencoFileInfo($" . "this->Id, '" . $colonnaDettagliata->Identificativo . "', $" . "iso = '');\n";
                $code .= $tab . $tab . "$" . "this->" . $identificativo . " = new ControlloFile();\n";
                $code .= $tab . $tab . "$" . "this->" . $identificativo . "->Nome = $" . "obj->Nome;\n";
                $code .= $tab . $tab . "$" . "this->" . $identificativo . "->Bytes = $" . "obj->Bytes;\n";
                $code .= $tab . $tab . "$" . "this->" . $identificativo . "->DimensioneReale = intval($" . "obj->DimensioneReale);\n";
                $code .= $tab . $tab . "$" . "this->" . $identificativo . "->DimensioneCompressa = intval($" . "obj->DimensioneCompressa);\n";
                $code .= $tab . $tab . "$" . "this->" . $identificativo . "->Base64Encoded = true;\n";
                $code .= $tab . $tab . "return $" . "this->" . $identificativo . ";\n";
                $code .= $tab . "}\n";
                $code .= $tab . "public function " . $identificativo . "Set(ControlloFile $" . "controlloFile) : void\n";
                $code .= $tab . "{\n";
                $code .= $tab . $tab . "$" . "this->" . $identificativo . " = $" . "controlloFile;\n";
                $code .= $tab . "}\n\n";
                break;
            }

            case "Immagini":
            {
                $code .= $tab . "#[PropertyAttribute('" . $colonnaDettagliata->Identificativo . "_Percorso', '', " . $colonnaDettagliata->Univoco . ")]\n";
                $code .= $tab . "public string $" . $identificativo . "_Percorso;\n";
                $code .= $tab . "#[PropertyAttribute('" . $colonnaDettagliata->Identificativo . "', 'Immagini', " . $colonnaDettagliata->Univoco . ")]\n";
                $code .= $tab . "private ?ControlloImmagine $" . $identificativo . ";\n";
                $code .= $tab . "public function " . $identificativo . "Get() : ?ControlloImmagine\n";
                $code .= $tab . "{\n";
                $code .= $tab . $tab . "if (isset($" . "this->" . $identificativo . "))\n";
                $code .= $tab . $tab . $tab . "return $" . "this->" . $identificativo . ";\n";
                $code .= $tab . $tab . "$" . "obj = PHPDOWEB()->DatiElencoFileInfo($" . "this->Id, '" . $colonnaDettagliata->Identificativo . "', $" . "iso = '');\n";
                $code .= $tab . $tab . "$" . "this->" . $identificativo . " = new ControlloImmagine();\n";
                $code .= $tab . $tab . "$" . "this->" . $identificativo . "->Nome = $" . "obj->Nome;\n";
                $code .= $tab . $tab . "$" . "this->" . $identificativo . "->Bytes = $" . "obj->Bytes;\n";
                $code .= $tab . $tab . "$" . "this->" . $identificativo . "->DimensioneReale = intval($" . "obj->DimensioneReale);\n";
                $code .= $tab . $tab . "$" . "this->" . $identificativo . "->DimensioneCompressa = intval($" . "obj->DimensioneCompressa);\n";
                $code .= $tab . $tab . "$" . "this->" . $identificativo . "->ImmagineAltezza = intval($" . "obj->ImmagineAltezza);\n";
                $code .= $tab . $tab . "$" . "this->" . $identificativo . "->ImmagineLarghezza = intval($" . "obj->ImmagineLarghezza);\n";
                $code .= $tab . $tab . "$" . "this->" . $identificativo . "->Base64Encoded = true;\n";
                $code .= $tab . $tab . "return $" . "this->" . $identificativo . ";\n";
                $code .= $tab . "}\n";
                $code .= $tab . "public function " . $identificativo . "Set(ControlloImmagine $" . "controlloImmagine) : void\n";
                $code .= $tab . "{\n";
                $code .= $tab . $tab . "$" . "this->" . $identificativo . " = $" . "controlloImmagine;\n";
                $code .= $tab . "}\n\n";
                break;
            }
        }
    }

    if ($parent !== "")
    {
        $parentName = str_replace(" ", "_", $parent);

        $code .= $tab . "public function Parent_get($" . "iso = '', array $" . "selectColumns = []) : ?" . $parentName . "\n";
        $code .= $tab . "{ return " . $parentName . "::GetItemById($" . "this->ParentId, $" . "iso, $" . "selectColumns); }\n";
        $code .= $tab . "public function Parent_set(?" . $parentName . " $" . "value) : void\n";
        $code .= $tab . "{ $" . "this->ParentId = $" . "value === null ? 0 : $" . "value->Id; }\n\n";
    }


    $code .= $getItemUnivoche;

    $code .= "\n";

    $code .= $tab . "public function Save(bool $" . "onSave = false, string $" . "iso = '') : SaveResponse\n";
    $code .= $tab . "{\n";
    $code .= $tab . $tab . "return parent::Save($" . "onSave, $" . "iso);\n";
    $code .= $tab . "}\n";

    $code .= "\n";

    $code .= $tab . "public function SaveLog(bool $" . "onSave = false, string $" . "iso = '') : SaveResponse\n";
    $code .= $tab . "{\n";
    $code .= $tab . $tab . "$" . "result = parent::Save($" . "onSave, $" . "iso);\n";
    $code .= $tab . $tab . "if (!$" . "result->Success)\n";
    $code .= $tab . $tab . "{\n";
    $code .= $tab . $tab . $tab . "$" . "e = new \Exception;\n";
    $code .= $tab . $tab . $tab . "$" . "trace = $" . "e->getTraceAsString();\n";
    $code .= $tab . $tab . $tab . "\\Common\\Log::Error(\"SaveLog: \" . \$" . "result->Avviso() . \", \" . $" . "trace . \"->\" . $" . "this);\n";
    $code .= $tab . $tab . "}\n";
    $code .= $tab . $tab . "return $" . "result;\n";
    $code .= $tab . "}\n";

    $code .= "\n";

    $code .= $tab . "public function Delete() : SaveResponse\n";
    $code .= $tab . "{\n";
    $code .= $tab . $tab . "return parent::Delete();\n";
    $code .= $tab . "}\n";

    $code .= "\n";

    $code .= $tab . "public function DeleteLog() : SaveResponse\n";
    $code .= $tab . "{\n";
    $code .= $tab . $tab . "$" . "result = parent::Delete();\n";
    $code .= $tab . $tab . "if (!$" . "result->Success)\n";
    $code .= $tab . $tab . "{\n";
    $code .= $tab . $tab . $tab . "$" . "e = new \Exception;\n";
    $code .= $tab . $tab . $tab . "$" . "trace = $" . "e->getTraceAsString();\n";
    $code .= $tab . $tab . $tab . "\\Common\\Log::Error(\"DeleteLog: \" . \$" . "result->Avviso() . \", \" . $" . "trace . \"->\" . $" . "this);\n";
    $code .= $tab . $tab . "}\n";
    $code .= $tab . $tab . "return $" . "result;\n";

    $code .= $tab . "}\n";

    $code .= "\n";

    $code .= $tab . "/** @return \Generator|" . $nomeClasse . "[] */\n";
    $code .= $tab . "public static function GetList(\n";
    $code .= $tab . $tab . "int $" . "item4page = -1,\n";
    $code .= $tab . $tab . "int $" . "page = -1,\n";
    $code .= $tab . $tab . "string $" . "wherePredicate = '',\n";
    $code .= $tab . $tab . "array $" . "whereValues = [],\n";
    $code .= $tab . $tab . "string $" . "orderPredicate = '',\n";
    $code .= $tab . $tab . "string $" . "iso = '',\n";
    $code .= $tab . $tab . "int $" . "parentId = 0,\n";
    $code .= $tab . $tab . "bool $" . "visible = null,\n";
    $code .= $tab . $tab . "bool $" . "webP = true,\n";
    $code .= $tab . $tab . "bool $" . "encode = false,\n";
    $code .= $tab . $tab . "array $" . "selectColumns = []) : \Generator\n";
    $code .= $tab . $tab . "{\n";
    $code .= $tab . $tab . $tab . "return BaseModel::BaseList('\Model\\" . $nomeClasse . "', $" . "item4page, $" . "page, $" . "wherePredicate, $" . "whereValues, $" . "orderPredicate, $" . "iso, $" . "parentId, $" . "visible, $" . "webP, $" . "encode, $" . "selectColumns);\n";
    $code .= $tab . $tab . "}\n";

    $code .= "\n";

    $code .= $tab . "public static function GetCount(\n";
    $code .= $tab . $tab . "string $" . "wherePredicate = '',\n";
    $code .= $tab . $tab . "array $" . "whereValues = [],\n";
    $code .= $tab . $tab . "string $" . "iso = '',\n";
    $code .= $tab . $tab . "int $" . "parentId = 0,\n";
    $code .= $tab . $tab . "bool $" . "visible = null,\n";
    $code .= $tab . $tab . "bool $" . "encode = false) : int\n";
    $code .= $tab . $tab . "{\n";
    $code .= $tab . $tab . $tab . "return BaseModel::BaseCount('\Model\\" . $nomeClasse . "', $" . "wherePredicate, $" . "whereValues, $" . "iso, $" . "parentId, $" . "visible, $" . "encode);\n";
    $code .= $tab . $tab . "}\n";

    $code .= "\n";

    $code .= $externalCollection;

    $code .= "}" . "\n\n\n";

    $bigFile .= $code . "\n\n\n";

    $path = $basePath . '\\Model\\' . $nomeClasse . '.php';

    if (!is_dir($basePath . '\\Model'))
    {
        mkdir($basePath . '\\Model', 0777, true);
    }


    //if (!file_exists($path))
    //{
    $myfile = fopen($path, 'w');

    fwrite($myfile, $code);

    fclose($myfile);

    echo "Scritto file " . $path . "<br>";
    //}    
}

//commento per adesso, ora non mi serve
//$path = $basePath . '\\bigClass.txt';

//$myfile = fopen($path, 'w');

//fwrite($myfile, $bigFile);

//fclose($myfile);