<?php
declare(strict_types=1);

namespace Common;

class PagineInterne
{
   /**
     * @param int $id
     * @param array $queryString
     * @return string
     */
    public static function GetUrl(\Code\Enum\PagineInterneEnum $pagineInternaEnum, array $queryString) : string
    {
        //recupero con reflection il valore dell'attributo che contiene l'identificativo

        $reflection = new \ReflectionEnum($pagineInternaEnum);

        $case = $reflection->getCase($pagineInternaEnum->name);

        $attribute = $case->getAttributes()[0];

        $args = $attribute->getArguments();

        $id = $args[0];

        $result = PHPDOWEB()->AdminPaginaInternaUrl($id, $queryString);

        return $result->Url;
    }

    /**
     * ritorna l'array namevalue passato con GetUrl
     * @param string $url, il link dato da GetUrl
     * @return array
     */
    public static function GetQuery() : array
    {
        $result = PHPDOWEB()->AdminPaginaInternaQuery($_SERVER['QUERY_STRING']);

        return $result->NameValues;
    }

    /**
     * porta alla pagina admin dell'elenco
     * @param \Code\Enum\ModelEnum $modelEnum il nome del dato
     * @param \Common\Base\BaseModel $parentModel un eventuale dato elenco padre id
     * @return string il link della pagina elenco
     */
    public static function AdminDatiElenco(\Code\Enum\ModelEnum $modelEnum, \Common\Base\BaseModel $parentModel = null) : string
    {
        $reflection = new \ReflectionEnum($modelEnum);

        $case = $reflection->getCase($modelEnum->name);

        $attribute = $case->getAttributes()[0];

        $args = $attribute->getArguments();

        $name = $args[0];

        $idParent = 0;

        if ($parentModel)
            $idParent = $parentModel->Id;

        $url = PHPDOWEB()->AdminDatiElenco($name, $idParent)->Url; //non metto in linea solo per debug

        return $url;
    }

    /**
     * Porta alla pagina admin dell'editor, per salvare o per modificare
     * @param \Code\Enum\ModelEnum $modelEnum il nome del dato
     * @param \Common\Base\BaseModel $model un eventuale dato elenco padre id
     * @return string il link della pagina elenco
     */
    public static function AdminDatiEditor(\Code\Enum\ModelEnum $modelEnum, \Common\Base\BaseModel $parentModel = null, \Common\Base\BaseModel $model = null) : string
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

        $url = PHPDOWEB()->AdminDatiEditor($name, $id, $idParent)->Url; //non metto in linea solo per debug

        return $url;
    }
}