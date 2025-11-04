<?php
declare(strict_types=1);

//CREO L'ENUM PER LE ETICHETTE CHE FINISCE DENTRO CODE/ENUM/ETICHETTEENUM.PHP

/** @noinspection PhpUndefinedFunctionInspection */
$obj = PHPDOWEB();

$basePath = $_SERVER["DOCUMENT_ROOT"] . "\\Public\\Php";

$tab = "    ";

$enumPath = $basePath . '\\Code\\Enum';

if (!is_dir($enumPath))
    mkdir($enumPath, 0777, true);

$areeObj = $obj->AreeGetList()->Aree;
$controlliObj = $obj->ControlliGetList()->Controlli;

$code = "<?php\n";
$code .= "declare(strict_types=1);\n\n";
$code .= "namespace Code\\Enum;\n\n";

$code .= "use Common\Attribute\ControlliAttribute as ControlliAttribute;\n\n";

$aree = [];

$code .= "enum AreeControlliEnum\n";
$code .= "{\n";

foreach ($areeObj as $index => $area)
{
    $area = $area->Nome;

    $controlli = $obj->AreeControlliList($area)->Controlli;

    $val = str_replace(" ", "_", $area) . "_";

    foreach ($controlli as $controllo)
    {
        $decode = false;
        $tipoInput = "";

        foreach ($controlliObj as $controlloObj)
        {
            if ($controlloObj->Id == $controllo->IdControllo)
            {
                if ($controlloObj->TipoInput == "RichTextBox" || $controlloObj->TipoInput == "RichTextBoxMini")
                    $decode = true;

                if ($controlloObj->Decode == "true")
                    $decode = true;

                $tipoInput = $controlloObj->TipoInput;

                break;
            }
        }

        $valCon = $val . str_replace(" ", "_", $controllo->Identificativo);

        $code .= $tab . "#[ControlliAttribute(\"" . $area . "\", \"" . $controllo->Identificativo . "\", \"" . $tipoInput . "\", " . ($decode ? "true" : "false") . ")]\n";
        $code .= $tab . "case " . $valCon . ";\n";
    }
}

$code .= "}";

$file = $enumPath . "\\AreeControlliEnum.php";

if (is_file($file))
{
    unlink($file); // Delete the file
    echo "Eliminato il file " . $file . "<br>";
}

$myfile = fopen($file, 'w');

fwrite($myfile, $code);

fclose($myfile);

echo "Scritto file " . $file . "<br>";


