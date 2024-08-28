<?php
declare(strict_types=1);


//CREO L'ENUM PER LE ETICHETTE CHE FINISCE DENTRO CODE/ENUM/ETICHETTEENUM.PHP

$obj = PHPDOWEB();

$basePath = $_SERVER["DOCUMENT_ROOT"] . "\\Public\\Php";

$tab = "    ";

$enumPath = $basePath . '\\Code\\Enum';

if (!is_dir($enumPath))
    mkdir($enumPath, 0777, true);

$etichette = $obj->SitoEtichetteGetList()->SitoEtichette;

$code = "<?php\n";
$code .= "declare(strict_types=1);\n\n";
$code .= "namespace Code\\Enum;\n\n";

$code .= "enum EtichetteEnum\n";
$code .= "{\n";

foreach ($etichette as $index => $etichetta)
{
    $val = $etichetta->Nome;

    $code .= $tab . "case " . $val . ";\n";
}

$code .= "}";


$file = $enumPath . "\\EtichetteEnum.php";

if (is_file($file))
{
    unlink($file); // Delete the file
    echo "Eliminato il file " . $file . "<br>";
}

$myfile = fopen($file, 'w');

fwrite($myfile, $code);

fclose($myfile);

echo "Scritto file " . $file . "<br>";


