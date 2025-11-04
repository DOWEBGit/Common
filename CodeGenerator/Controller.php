<?php
declare(strict_types=1);

$basePath = $_SERVER["DOCUMENT_ROOT"] . "\\Public\\Php";

$path = $basePath . '/Controller';

$tab = "    ";

/** @noinspection PhpUndefinedFunctionInspection */
$obj = PHPDOWEB();

$arr = $obj->DatiGetList();

$dati = $arr->Dati;


foreach ($dati as $index => $dato)
{
    $parent = $dato->ParentNome;
    $parent = str_replace(" ", "_", $parent);

    if ($parent == "")
        $parent = "baseModel";

    $code = "";

    $code .= "<?php\n";
    $code .= "declare(strict_types=1);\n\n";
    $code .= "namespace Controller;\n";

    $code .= "use \Common\Base\BaseModel;\n";
    $code .= "use \Common\Response\SaveResponseModel;\n";
    $code .= "use \Common\Response\SaveResponse;\n\n";

    $nomeClasse = str_replace(" ", "_", $dato->Nome);

    $code .= "class " . $nomeClasse . " extends \Common\Base\BaseController\n";
    $code .= "{" . "\n";

    $lowerClass = strtolower($nomeClasse);

    $code .=
        $tab . "/**\n" .
        $tab . "* @var \Model\\$nomeClasse | null \$$lowerClass\n" .
        $tab . "* @noinspection PhpUnused\n" .
        $tab . "* @noinspection PhpParameterNameChangedDuringInheritanceInspection\n" .
        $tab . "*/\n" .
        $tab . "public static function OnSave(?\Common\Base\BaseModel \$$lowerClass = null): string\n" .
        $tab . "{\n" .
        $tab . $tab . "return \"\";\n" .
        $tab . "}\n\n" .

        $tab . "/**\n";

    if ($parent != "baseModel")
        $code .= $tab . "* @var \Model\\$parent | null \$$parent\n";

    $code .= $tab . "* @noinspection PhpUnused\n" .
        $tab . "*/\n" .
        $tab . "public static function OnDelete(?\Common\Base\BaseModel \$$parent = null): string\n" .
        $tab . "{\n" .
        $tab . $tab . "return \"\";\n" .
        $tab . "}\n" .

    "}" . "\n";

    $path = $basePath . '\\Controller\\' . $nomeClasse . '.php';

    if (!is_dir($basePath . '\\Controller'))
        mkdir($basePath . '\\Controller', 0777, true);

    if (is_file($path))
        continue;

    $myfile = fopen($path, 'w');

    fwrite($myfile, $code);

    fclose($myfile);

    echo "Scritto file " . $path . "<br>";
}

