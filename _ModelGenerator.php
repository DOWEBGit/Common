<?php

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

$colonne = $arr->Dati;

$bigFile = "";

foreach ($colonne as $index => $inner)
{
    $bigFile .= "prendi questa codice e memorizzalo, non descriverlo, dimmi solo ok quando lo hai letto";

    $parent = $inner->ParentNome;

    $code = "";

    $code .= "<?php\n";

    $code .= "namespace Model;\n";

    $code .= "use Common\BaseModel;\n\n";
    $code .=  "use Common\PropertyAttribute;\n\n";

    $nomeClasse = str_replace(" ", "_", $inner->Nome);

    $code .= "/**\n";
    $code .= "* " . $obj->Decode($inner->Descrizione) . "\n";
    $code .= "*/\n";
    $code .= "class " . $nomeClasse . " extends BaseModel\n";
    $code .= "{" . "\n\n";

    /*     * ****************** */
    //costruttore inizio
    /*     * ****************** */

    $code .= $tab . "function __construct()\n";
    $code .= $tab . "{\n";
    $code .= $tab . $tab . "parent::__construct();\n";

    $arr = $obj->DatiElencoGetColonne($inner->Nome);

    $colonneDettagliate = $arr->Colonne;

    $getItemUnivoche = "\n";
    $getItemUnivoche .= $tab . "static function GetItemById(int $" . "Id, string $" . "iso = '') : ?" . $nomeClasse . "\n";
    $getItemUnivoche .= $tab . "{\n";
    $getItemUnivoche .= $tab . $tab . "return BaseModel::GetItem(new " . $nomeClasse . "(), 'Id', $" . "Id, $" . "iso);\n";
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
                    }
                    break;
                }

            case "Data":
                {
                    $code .= $tab . $tab . "$" . "this->" . $identificativo . " = new \DateTime();\n";
                    break;
                }

            case "Numeri":
                {
                    if ($colonna->Univoco === "True" && $colonna->Obbligatorio === "True")
                    {
                        $getItemUnivoche .= "\n";

                        if ($colonna->Multilingua == "True")
                        {
                            $getItemUnivoche .= $tab . "public static function GetItemBy" . $identificativo . "(int $" . $identificativo . ", string $" . "iso = '') : " . $nomeClasse . "\n";
                            $getItemUnivoche .= $tab . "{\n";
                            $getItemUnivoche .= $tab . $tab . "return BaseModel::GetItem(new " . $nomeClasse . "(), " . $colonna->Identificativo . "', $" . $identificativo . ", $" . "iso);\n";
                            $getItemUnivoche .= $tab . "}\n";
                        }
                        else
                        {
                            $getItemUnivoche .= $tab . "public static function GetItemBy" . $identificativo . "(int $" . $identificativo . ") : " . $nomeClasse . "\n";
                            $getItemUnivoche .= $tab . "{\n";
                            $getItemUnivoche .= $tab . $tab . "return BaseModel::GetItem(new " . $nomeClasse . "(), '" . $colonna->Identificativo . "', $" . $identificativo . ");\n";
                            $getItemUnivoche .= $tab . "}\n";
                        }
                    }
                    
                    if ($colonna->TipoInput == "CheckBox")
                    {
                        $code .= $tab . $tab . "$" . "this->" . $identificativo . " = false;\n";
                    }
                    else
                    {
                        $code .= $tab . $tab . "$" . "this->" . $identificativo . " = 0;\n";
                    }
                    break;
                }

            case "Testo":
                {
                    if ($colonna->Univoco === "True" && $colonna->Obbligatorio === "True")
                    {
                        $getItemUnivoche .= "\n";

                        if ($colonna->Multilingua == "True")
                        {
                            $getItemUnivoche .= $tab . "public static function GetItemBy" . $identificativo . "(string $" . $identificativo . ", string $" . "iso) : ?" . $nomeClasse . "\n";
                            $getItemUnivoche .= $tab . "{\n";
                            $getItemUnivoche .= $tab . $tab . "return BaseModel::GetItem(new " . $nomeClasse . "(), '" . $colonna->Identificativo . "', $" . $identificativo . ", $" . "iso);\n";
                            $getItemUnivoche .= $tab . "}\n";
                        }
                        else
                        {
                            $getItemUnivoche .= $tab . "public static function GetItemBy" . $identificativo . "(string $" . $identificativo . ") : ?" . $nomeClasse . "\n";
                            $getItemUnivoche .= $tab . "{\n";
                            $getItemUnivoche .= $tab . $tab . "return BaseModel::GetItem(new " . $nomeClasse . "(), '" . $colonna->Identificativo . "', $" . $identificativo . ");\n";
                            $getItemUnivoche .= $tab . "}\n";
                        }
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

                        $code .= $tab . "#[PropertyAttribute('" . $colonnaDettagliata->Identificativo . "', 'Dato')]\n";
                        $code .= $tab . "public int $" . $identificativo . ";\n";
                        $code .= $tab . "public function " . $identificativo . "_get($" . "iso = '') : ?" . $identificativoRef . "\n";
                        $code .= $tab . "{ return ".$identificativoRef."::GetItemById($" . "this->" . $identificativo . ", $" . "iso); }\n";
                        $code .= $tab . "public function " . $identificativo . "_set(?" . $identificativoRef . " $" . "value)\n";
                        $code .= $tab . "{ $" . "this-> " . $identificativo . " = $" . "value === null ? 0 : $" . "value->Id; }\n\n";
                    }
                    break;
                }

            case "Data":
                {
                    $code .= $tab . "#[PropertyAttribute('" . $colonnaDettagliata->Identificativo . "', 'Data')]\n";
                    $code .= $tab . "public \DateTime $" . $identificativo . ";\n\n";
                    break;
                }

            case "Numeri":
                {
                    $type = "int";

                    if ($colonnaDettagliata->TipoInput == "CheckBox")
                        $type = "bool";

                    $code .= $tab . "#[PropertyAttribute('" . $colonnaDettagliata->Identificativo . "', 'Numeri')]\n";
                    $code .= $tab . "public {$type} $" . $identificativo . ";\n\n";
                    break;
                }

            case "Testo":
                {
                    $code .= $tab . "#[PropertyAttribute('" . $colonnaDettagliata->Identificativo . "', 'Testo')]\n";
                    $code .= $tab . "public string $" . $identificativo . ";\n";
                    $code .= $tab . "private string $" . "_" . $identificativo . ";\n\n";
                    break;
                }

            case "File":
                {
                    $code .= $tab . "#[PropertyAttribute('" . $colonnaDettagliata->Identificativo . "_Percorso', '')]\n";
                    $code .= $tab . "public string $" . $identificativo . "_Percorso;\n";
                    $code .= $tab . "#[PropertyAttribute('" . $colonnaDettagliata->Identificativo . "', 'File')]\n";
                    $code .= $tab . "private ?\Common\ControlloFile $" . $identificativo . ";\n";
                    $code .= $tab . "public function " . $identificativo . "Get() : ?\Common\ControlloFile\n";
                    $code .= $tab . "{\n";
                    $code .= $tab . $tab . "if (isset($" . "this->" . $identificativo . "))\n";
                    $code .= $tab . $tab . $tab . "return $" . "this->" . $identificativo . ";\n";
                    $code .= $tab . $tab . "$" . "obj = PHPDOWEB()->DatiElencoFileInfo($" . "this->Id, '" . $colonnaDettagliata->Identificativo . "', $" . "iso = '');\n";
                    $code .= $tab . $tab . "$" . "this->" . $identificativo . " = new \Common\ControlloFile();\n";
                    $code .= $tab . $tab . "$" . "this->" . $identificativo . "->Nome = $" . "obj->Nome;\n";
                    $code .= $tab . $tab . "$" . "this->" . $identificativo . "->Bytes = $" . "obj->Bytes;\n";
                    $code .= $tab . $tab . "$" . "this->" . $identificativo . "->DimensioneReale = $" . "obj->DimensioneReale;\n";
                    $code .= $tab . $tab . "$" . "this->" . $identificativo . "->DimensioneCompressa = $" . "obj->DimensioneCompressa;\n";
                    $code .= $tab . $tab . "return $" . "this->" . $identificativo . ";\n";
                    $code .= $tab . "}\n";
                    $code .= $tab . "public function " . $identificativo . "Set(\Common\ControlloFile $" . "controlloFile) : void\n";
                    $code .= $tab . "{\n";
                    $code .= $tab . $tab . "$" . "this->" . $identificativo . " = $" . "controlloFile;\n";
                    $code .= $tab . "}\n\n";
                    break;
                }

            case "Immagini":
                {
                    $code .= $tab . "#[PropertyAttribute('" . $colonnaDettagliata->Identificativo . "_Percorso', '')]\n";
                    $code .= $tab . "public string $" . $identificativo . "_Percorso;\n";
                    $code .= $tab . "#[PropertyAttribute('" . $colonnaDettagliata->Identificativo . "', 'Immagini')]\n";
                    $code .= $tab . "private ?\Common\ControlloImmagine $" . $identificativo . ";\n";
                    $code .= $tab . "public function " . $identificativo . "Get() : ?\Common\ControlloImmagine\n";
                    $code .= $tab . "{\n";
                    $code .= $tab . $tab . "if (isset($" . "this->" . $identificativo . "))\n";
                    $code .= $tab . $tab . $tab . "return $" . "this->" . $identificativo . ";\n";
                    $code .= $tab . $tab . "$" . "obj = PHPDOWEB()->DatiElencoFileInfo('" . $colonnaDettagliata->Nome . "', $" . "this->Id, '" . $colonnaDettagliata->Identificativo . "', $" . "iso = '');\n";
                    $code .= $tab . $tab . "$" . "this->" . $identificativo . " = new \Common\ControlloImmagine();\n";
                    $code .= $tab . $tab . "$" . "this->" . $identificativo . "->Nome = $" . "obj->Nome;\n";
                    $code .= $tab . $tab . "$" . "this->" . $identificativo . "->Bytes = $" . "obj->Bytes;\n";
                    $code .= $tab . $tab . "$" . "this->" . $identificativo . "->DimensioneReale = $" . "obj->DimensioneReale;\n";
                    $code .= $tab . $tab . "$" . "this->" . $identificativo . "->DimensioneCompressa = $" . "obj->DimensioneCompressa;\n";
                    $code .= $tab . $tab . "$" . "this->" . $identificativo . "->ImmagineAltezza = $" . "obj->ImmagineAltezza;\n";
                    $code .= $tab . $tab . "$" . "this->" . $identificativo . "->ImmagineLarghezza = $" . "obj->ImmagineLarghezza;\n";
                    $code .= $tab . $tab . "return $" . "this->" . $identificativo . ";\n";
                    $code .= $tab . "}\n";
                    $code .= $tab . "public function " . $identificativo . "Set(\Common\ControlloImmagine $" . "controlloImmagine) : void\n";
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

        $code .= $tab . "public function Parent_get($" . "iso = '') : ?" . $parentName . "\n";
        $code .= $tab . "{ return " . $parentName . "::GetItemById($" . "this->ParentId, $" . "iso); }\n";
        $code .= $tab . "public function Parent_set(?" . $parentName . " $" . "value)\n";
        $code .= $tab . "{ $" . "this->ParentId = $" . "value === null ? 0 : $" . "value->Id; }\n\n";
    }


    $code .= $getItemUnivoche;

    $code .= "\n";

    $code .= $tab . "public function Save(bool $" . "onSave = false, string $" . "iso = '') : \Common\SaveResponse\n";
    $code .= $tab . "{\n";
    $code .= $tab . $tab . "return parent::Save($" . "onSave, $" . "iso);\n";
    $code .= $tab . "}\n";

    $code .= "\n";

    $code .= $tab . "public function Delete() : \Common\SaveResponse\n";
    $code .= $tab . "{\n";
    $code .= $tab . $tab . "return parent::Delete();\n";
    $code .= $tab . "}\n";

    $code .= "\n";

    $code .= $tab . "/** @return " . $nomeClasse . "[] */\n";
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
    $code .= $tab . $tab . "bool $" . "encode = false)\n";
    $code .= $tab . $tab . "{\n";
    $code .= $tab . $tab . $tab . "return BaseModel::BaseList('\Model\\" . $nomeClasse . "', $" . "item4page, $" . "page, $" . "wherePredicate, $" . "whereValues, $" . "orderPredicate, $" . "iso, $" . "parentId, $" . "visible, $" . "webP, $" . "encode);\n";
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

    $code .= "}" . "\n\n\n";

    $bigFile .= $code . "\n\n\n";

    $path = $basePath . '\\Model\\' . $nomeClasse . '.php';

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

echo "Scritto file " . $path . "<br>";
