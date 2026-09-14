<?php
declare(strict_types=1);

namespace Common;

class PagineInterne
{
    /**
     * @param \Code\Enum\PagineInterneEnum $pagineInternaEnum la pagina interna
     * @param array $queryString coppie nome => valore da mettere nell'indirizzo
     * @return string
     */
    public static function GetUrl(\Code\Enum\PagineInterneEnum $pagineInternaEnum, array $queryString = []) : string
    {
        //recupero con reflection il valore dell'attributo che contiene l'identificativo

        $reflection = new \ReflectionEnum($pagineInternaEnum);

        $case = $reflection->getCase($pagineInternaEnum->name);

        $attribute = $case->getAttributes()[0];

        $args = $attribute->getArguments();

        $id = $args[0];

        /** @noinspection PhpUndefinedFunctionInspection */
        $result = PHPDOWEB()->AdminPaginaInternaUrl($id, $queryString);

        return $result->Url;
    }

    /**
     * ritorna l'array namevalue passato con GetUrl, letto dalla querystring della richiesta
     * @return array
     */
    public static function GetQuery() : array
    {
        /** @noinspection PhpUndefinedFunctionInspection */
        $result = PHPDOWEB()->AdminPaginaInternaQuery($_SERVER['QUERY_STRING']);

        return $result->NameValues;
    }

    /**uso self::GetQuery, ma formatto l'array come key => value
     * @return array
     */
    public static function FormatQueryString() : array
    {
        $result = [];

        $raw = self::GetQuery();

        foreach($raw as $item)
            $result[$item[0]] = $item[1];

        return $result;
    }

    /**
     * porta alla pagina admin dell'elenco
     * @param \Code\Enum\ModelEnum $modelEnum il nome del dato
     * @param \Common\Base\BaseModel|null $parentModel un eventuale dato elenco padre
     * @return string il link della pagina elenco
     */
    public static function AdminDatiElenco(\Code\Enum\ModelEnum $modelEnum, ?\Common\Base\BaseModel $parentModel = null) : string
    {
        $reflection = new \ReflectionEnum($modelEnum);

        $case = $reflection->getCase($modelEnum->name);

        $attribute = $case->getAttributes()[0];

        $args = $attribute->getArguments();

        $name = $args[0];

        $idParent = 0;

        if ($parentModel)
            $idParent = $parentModel->Id;

        /** @noinspection PhpUndefinedFunctionInspection */
        $url = PHPDOWEB()->AdminDatiElenco($name, $idParent)->Url; //non metto in linea solo per debug

        return $url;
    }

    /**
     * Porta alla pagina admin dell'editor, per salvare o per modificare
     * @param \Code\Enum\ModelEnum $modelEnum il nome del dato
     * @param \Common\Base\BaseModel|null $parentModel un eventuale dato elenco padre
     * @param \Common\Base\BaseModel|null $model il dato da modificare, null per uno nuovo
     * @return string il link della pagina editor
     */
    public static function AdminDatiEditor(\Code\Enum\ModelEnum $modelEnum, ?\Common\Base\BaseModel $parentModel = null, ?\Common\Base\BaseModel $model = null) : string
    {
        $reflection = new \ReflectionEnum($modelEnum);

        $case = $reflection->getCase($modelEnum->name);

        $attribute = $case->getAttributes()[0];

        $args = $attribute->getArguments();

        $name = $args[0];

        $idParent = 0;
        $id = 0;

        if ($parentModel)
            $idParent = $parentModel->Id;

        if ($model)
        {
            $idParent = $model->ParentId;
            $id = $model->Id;
        }

        /** @noinspection PhpUndefinedFunctionInspection */
        $url = PHPDOWEB()->AdminDatiEditor($name, $id, $idParent)->Url; //non metto in linea solo per debug

        return $url;
    }
}