<?php
declare(strict_types=1);


//CREO IL FILE REPLACE CON ETICHETTE E VARS CHE FINISCE DENTRO \Code\Replace.php

/** @noinspection PhpUndefinedFunctionInspection */
$obj = PHPDOWEB();

$basePath = $_SERVER["DOCUMENT_ROOT"] . "\\Public\\Php\\Code";

$tab = "    ";

if (!is_dir($basePath))
    mkdir($basePath, 0777, true);

$etichette = $obj->SitoEtichetteGetList()->SitoEtichette;

$code = "<?php\n";
$code .= "declare(strict_types=1);\n\n";
$code .= "namespace Code;\n\n";

$code .= "class Replace\n";
$code .= "{\n\n";

$contenuto = "contenuto";

$code .= $tab."public static function EtichetteReplace(string \$$contenuto): string\n";
$code .= $tab."{\n";

foreach ($etichette as $index => $etichetta)
{
    $val = $etichetta->Nome;

    $code .= $tab.$tab."\$$contenuto = str_replace(\"[".strtoupper($val)."]\", \Common\Etichette::GetValoreIso(\Code\Enum\EtichetteEnum::".$val."), \$$contenuto);\n";
}

$code .= $tab.$tab."return \$$contenuto;\n"
        . $tab."}\n\n";

$code .= $tab."public static function VarsReplace(string \$$contenuto): string\n";
    $code .= $tab."{\n"
    . $tab.$tab."\$$contenuto = str_replace(\"[WEBPATH]\", \Common\SiteVars::Value(\Common\VarsEnum::webpath), \$$contenuto);\n"
    . $tab.$tab."return str_replace(\"[PROTOCOLLO]\", \Common\SiteVars::Value(\Common\VarsEnum::protocollo), \$$contenuto);\n"
    . $tab."}\n\n"

. "}";

$path = $basePath . '\\Replace.php';

if (is_file($path))
    die();

$myfile = fopen($path, 'w');

fwrite($myfile, $code);

fclose($myfile);

echo "Scritto file " . $path . "<br>";