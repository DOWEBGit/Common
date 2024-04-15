<?php
declare(strict_types=1);
header("Cache-Control: no-cache, no-store, must-revalidate");
header("expires: -1");


//CREO L'ENUM PER LE PAGINE INTERNE, DA COMPLETATE, FINISCE DENTRO CODE/ENUM/PAGINEINTERNEENUM.PHP

$obj = PHPDOWEB();

$basePath = $_SERVER["DOCUMENT_ROOT"] . "\\Public\\Php";

$tab = "    ";

$enumPath = $basePath . '\\Code\\Enum';

if (!is_dir($enumPath))
{
    mkdir($enumPath, 0777, true);
}

$pagineObj = $obj->AdminPagineInterneGetList()->Pagine;

$code = "<?php\n";
$code .= "declare(strict_types=1);\n\n";
$code .= "namespace Code\\Enum;\n\n";

$code .= "use Common\Attribute\PagineInterneAttribute as PagineInterneAttribute;\n\n";

$pagine = [];

$code .= "enum PagineInterneEnum : string\n";
$code .= "{\n";

foreach ($pagineObj as $index => $pagina)
{
    $val = $pagina->Nome;

    $pagine[] = $pagina->Nome;

    $val = str_replace(" ", "_", $val);

    $code .= $tab . "#[PagineInterneAttribute(" . $pagina->Id . ")]\n";
    $code .= $tab . "case " . $val . " = \"" . $pagina->Nome . "\";\n";
}

$code .= "}";

$file = $enumPath . "\\PagineInterneEnum.php";

if (is_file($file))
{
    unlink($file); // Delete the file
    echo "Eliminato il file " . $file . "<br>";
}

$myfile = fopen($file, 'w');

fwrite($myfile, $code);

fclose($myfile);

echo "Scritto file " . $file . "<br>";

