<?php
declare(strict_types=1);
header("Cache-Control: no-cache, no-store, must-revalidate");
header("expires: -1");

$basePath = $_SERVER["DOCUMENT_ROOT"] . "\\Public\\Php";

$path = $basePath . '/Controller';

$tab = "    ";

$obj = PHPDOWEB();

$arr = $obj->DatiGetList();

$dati = $arr->Dati;


foreach ($dati as $index => $dato)
{
    $parent = $dato->ParentNome;
    $parent = str_replace(" ", "_", $parent);

    if ($parent == "")
        $parent = "ModelBase";

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
        $tab . "* @var \Model\\$nomeClasse \$$lowerClass\n" .
        $tab . "* @noinspection PhpUnused\n" .
        $tab . "*/\n" .
        $tab . "public static function OnSave(\Common\Base\BaseModel \$$lowerClass = null): string\n" .
        $tab . "{\n" .
        $tab . $tab . "return \"\";\n" .
        $tab . "}\n\n" .

        $tab . "/**\n";

    if ($parent != "ModelBase")
        $code .= $tab . "* @var \Model\\$parent \$$parent\n";

    $parent = strtolower($parent);

    $code .= $tab . "* @noinspection PhpUnused\n" .
        $tab . "*/\n" .
        $tab . "public static function OnDelete(\Common\Base\BaseModel \$$parent = null): string\n" .
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

