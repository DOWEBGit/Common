<?php

//CREO L'ENUM PER LE ETICHETTE CHE FINISCE DENTRO CODE/ENUM/ETICHETTEENUM.PHP

$obj = PHPDOWEB();

$basePath = $_SERVER["DOCUMENT_ROOT"] . "\\Public\\Php";

$tab = "    ";

$enumPath = $basePath . '\\Code\\Enum';

if (!is_dir($enumPath))
    mkdir($enumPath, 0777, true);

$pagine = [];

$controlliObj = $obj->ControlliGetList()->Controlli;
$pagineObj = $obj->PagineDatiGetList()->Pagine;

$code = "<?php\n\n";
$code .= "namespace Code\\Enum;\n\n";

$code .= "enum PagineDatiControlliEnum\n";
$code .= "{\n";

foreach ($pagineObj as $pagina)
{
    $nome = $pagina->Nome;

    $controlli = $obj->PagineDatiControlliList($nome)->Controlli;

    $val = str_replace(" ", "_", $nome) . "_";

    foreach ($controlli as $controllo)
    {
        $decode = false;

        foreach ($controlliObj as $controlloObj)
        {
            if ($controlloObj->Id == $controllo->IdControllo)
            {
                if ($controlloObj->TipoInput == "RichTextBox" || $controlloObj->TipoInput == "RichTextBoxMini")
                    $decode = true;

                break;
            }
        }

        $valCon = $val . str_replace(" ", "_", $controllo->Identificativo);

        $code .= $tab . "#[EnumAttribute(\"" . $nome . "\", \"" . $controllo->Identificativo . "\", " . ($decode ? "true" : "false") . ")]\n";
        $code .= $tab . "case " . $valCon . ";\n";

    }
}

$code .= "}";



$file = $enumPath . "\\PagineDatiControlliEnum.php";

if (is_file($file))
{
    unlink($file); // Delete the file
    echo "Eliminato il file " . $file . "<br>";
}

$myfile = fopen($file, 'w');

fwrite($myfile, $code);

fclose($myfile);

echo "Scritto file " . $file . "<br>";

