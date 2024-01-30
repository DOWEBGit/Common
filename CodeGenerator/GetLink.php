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
        $tab . "public static function " . $url . "() : string\n" .
        $tab . "{\n" .
        $tab . $tab . "return \Common\Pagine::GetUrlIso(\Code\Enum\PagineEnum::". $pagina->Nome .");\n" .
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

$code .= "class GetLinkQuery\n";
$code .= "{\n";

$arr = $obj->DatiGetList();

$dati = $arr->Dati;

foreach ($dati as $dato)
{
    $nome = $dato->Nome;

    $nome = str_replace(" ", "_", $nome);

    $code .=
        $tab . "Get" . $nome . "() : ".$dato->Nome."\n" .
        $tab . "{\n" .
        $tab . $tab . "\$url = \Common\Convert::GetDecodedQueryString(\$_GET['url']);\n" .
        $tab . $tab . "\$url = \Common\Convert::GetDecodedQueryString(\$_GET['url']);\n" .
        $tab . "}\n" .

    $parent = $dato->Nome;
}




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
        $tab . "public static function " . $url . "() : string\n" .
        $tab . "{\n" .
        $tab . $tab . "return \Common\Pagine::GetUrlIso(\Code\Enum\PagineEnum::". $pagina->Nome .");\n" .
        $tab . "}\n\n";
}

$code .= "}";














$file = $enumPath . "\\GetLinkQuery.php";

if (is_file($file))
{
    unlink($file); // Delete the file
    echo "Eliminato il file " . $file . "<br>";
}

$myfile = fopen($file, 'w');

fwrite($myfile, $code);

fclose($myfile);

echo "Scritto file " . $file . "<br>";









$file = $enumPath . "\\GetLinkParams.php";

if (is_file($file))
    return;

$code = "<?php\n";
$code .= "declare(strict_types=1);\n\n";
$code .= "namespace Code;\n\n";
$code .= "class GetLinkParams\n";
$code .= "{\n\n";
$code .= "}";

$myfile = fopen($file, 'w');

fwrite($myfile, $code);
fclose($myfile);

echo "Scritto file " . $file . "<br>";


