<?php
declare(strict_types=1);


/** @noinspection PhpUndefinedFunctionInspection */
$obj = PHPDOWEB();

$basePath = $_SERVER["DOCUMENT_ROOT"] . "\\Public\\Php";

$tab = "    ";

$enumPath = $basePath . '\\Code\\Enum';

if (!is_dir($enumPath))
    mkdir($enumPath, 0777, true);

$code = "<?php\n";
$code .= "declare(strict_types=1);\n\n";
$code .= "namespace Code\\Enum;\n\n";

$code .= "use Common\Attribute\ModelAttribute as ModelAttribute;\n\n";

$pagine = [];

$code .= "enum ModelEnum\n";
$code .= "{\n";


$arr = $obj->DatiGetList();

$dati = $arr->Dati;

foreach ($dati as $index => $dato)
{
    $nomeClasse = str_replace(" ", "_", $dato->Nome);


        $code .= $tab . "#[ModelAttribute(\"" . $dato->Nome . "\")]\n";
        $code .= $tab . "case " . $nomeClasse . ";\n";
}

$code .= "}";

$file = $enumPath . "\\ModelEnum.php";

if (is_file($file))
{
    unlink($file); // Delete the file
    echo "Eliminato il file " . $file . "<br>";
}

$myfile = fopen($file, 'w');

fwrite($myfile, $code);

fclose($myfile);

echo "Scritto file " . $file . "<br>";


