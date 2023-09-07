<?php

//CREO L'ENUM PER LE ETICHETTE CHE FINISCE DENTRO CODE/ENUM/ETICHETTEENUM.PHP

$obj = PHPDOWEB();

$basePath = $_SERVER["DOCUMENT_ROOT"] . "\\Public\\Php";

$tab = "    ";

$enumPath = $basePath . '\\Code\\Enum';

if (!is_dir($enumPath))
    mkdir($enumPath, 0777, true);

$pagine = [];

$pagineObj = $obj->PagineDatiGetList()->Pagine;

$code = "<?php\n\n";
$code .= "namespace Code\\Enum;\n\n";

$code .= "enum PagineDatiEnum : string\n";
$code .= "{\n";

foreach ($pagineObj as $index => $pagina)
{
    $val = $pagina->Nome;

    $pagine[] = $pagina->Nome;

    $val = str_replace(" ", "_", $val);

    $code .= $tab . "case " . $val . " = \"" . $pagina->Nome . "\";\n";
}

$code .= "}";

$file = $enumPath . "\\PagineDatiEnum.php";

if (is_file($file))
{
    unlink($file); // Delete the file
    echo "Eliminato il file " . $file . "<br>";
}

$myfile = fopen($file, 'w');

fwrite($myfile, $code);

fclose($myfile);

echo "Scritto file " . $file . "<br>";

