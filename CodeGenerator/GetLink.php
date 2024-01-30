<?php
declare(strict_types=1);

use Code\Enum\PagineEnum;

header("Cache-Control: no-cache, no-store, must-revalidate");
header("expires: -1");


//CREO L'ENUM PER LE ETICHETTE CHE FINISCE DENTRO CODE/ENUM/ETICHETTEENUM.PHP

$obj = PHPDOWEB();

$basePath = $_SERVER["DOCUMENT_ROOT"] . "\\Public\\Php";

$tab = "    ";

$enumPath = $basePath . '\\Code';

if (!is_dir($enumPath))
    mkdir($enumPath, 0777, true);

$pagine = $obj->PagineGetList()->Pagine;

$code = "<?php\n";
$code .= "declare(strict_types=1);\n\n";
$code .= "namespace Code;\n\n";

$code .= "class GetLink\n";
$code .= "{\n";

/*
        Avviso = string.Empty, Errore = false, Pagine =
        Url = pagina.Url,
        FullUrl = Pagine.GetFullUrl(pagina, Lingue.DefaultLanguage).ToCachedStringAndDispose(),
        Multilingua = pagina.Multilingua,
        Avviso = string.Empty,
        TagReplace = pagina.TagReplace,
        Parent = pagina.Parent,
        Attiva = pagina.Attiva,
        Errore = false,
        Home = pagina.Home,
        Nome = pagina.Nome,
        Sitemap = pagina.Sitemap

  */

$code .=
    $tab . "private static function GetTokens(array \$models): string\n" .
    $tab . "{\n" .
    $tab . $tab . "\$qs = \"\";\n" .
    $tab . $tab . "\$token = \"?\";\n" .
    $tab .$tab .  "foreach (\$models as \$model)\n" .
    $tab . $tab . "{\n" .
    $tab . $tab . $tab . "\$tableName = get_class(\$model);\n" .
    $tab . $tab . $tab . "\$tableName = str_replace(\"Model\\\\\", \"\", \$tableName);\n" .
    $tab . $tab . $tab . "\$qs .= \$token . \$tableName . \"Id=\" . \$model->Id;\n" .
    $tab . $tab . $tab . "\$token = \"&\";\n" .
    $tab . $tab . "}\n" .
    $tab . $tab .  "return \$qs;\n" .
    $tab . "}\n\n";

foreach ($pagine as $pagina)
{
    //$val = $pagina->FullUrl;

    $localPagina = $pagina;

    $url = $localPagina->Nome;

    while ($localPagina->Parent != 0)
    {
        foreach ($pagine as $paginaTmp)
        {
            if ($paginaTmp->Id == $localPagina->Parent)
            {
                $localPagina = $paginaTmp;
                break;
            }
        }

        $url = $localPagina->Nome . "_" . $url;
    }

    if (str_ends_with($url, "_"))
        $url = substr($url, 0, -1);

    $url = str_replace(" ", "_", $url);

    $code .=
        $tab . "public static function " . $url . "(array \$model) : string\n" .
        $tab . "{\n" .
        $tab . $tab . "return \Common\Convert::GetEncodedLink(\Common\Pagine::GetUrlIso(\Code\Enum\PagineEnum::" . $pagina->Nome . ") . self::GetTokens(\$model));\n" .
        $tab . "}\n\n";
}

$code .= "}";


$file = $enumPath . "\\GetLink.php";

if (is_file($file))
{
    unlink($file); // Delete the file
    echo "Eliminato il file " . $file . "<br>";
}

$myfile = fopen($file, 'w');

fwrite($myfile, $code);

fclose($myfile);

echo "Scritto file " . $file . "<br>";


$code = "<?php\n";
$code .= "declare(strict_types=1);\n\n";
$code .= "namespace Code;\n\n";

$code .= "class ObjectFromQuery\n";
$code .= "{\n";

$arr = $obj->DatiGetList();

$dati = $arr->Dati;

foreach ($dati as $dato)
{
    $nome = $dato->Nome;

    $nome = str_replace(" ", "_", $nome);

    $code .=
        $tab . "static public function Get" . $nome . "() : ?\Model\\" . $dato->Nome . "\n" .
        $tab . "{\n" .
        $tab . $tab . "\$keyValue = \Common\Convert::GetDecodedQueryString(\$_SERVER['QUERY_STRING']);\n" .
        $tab . $tab . "if (!isset(\$keyValue[\"{$nome}Id\"]))\n" .
        $tab . $tab . $tab . "return null;\n" .
        $tab . $tab . "return \Model\\" . $dato->Nome . "::GetItemById(intval(\$keyValue[\"{$nome}Id\"]));\n" .
        $tab . "}\n";
}

$code .= "}\n";

$file = $enumPath . "\\ObjectFromQuery.php";

if (is_file($file))
{
    unlink($file); // Delete the file
    echo "Eliminato il file " . $file . "<br>";
}

$myfile = fopen($file, 'w');

fwrite($myfile, $code);

fclose($myfile);

echo "Scritto file " . $file . "<br>";