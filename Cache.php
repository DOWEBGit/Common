<?php
declare(strict_types=1);

namespace Common;

class Cache
{
    const TTL = 60 * 10; //10 minuti

    //https://www.php.net/manual/en/book.apcu.php

    static function UpdateCache(): void
    {
        $siteName = $_SERVER['PIPENAME'];

        $cacheKey = $siteName . "|Cache";

        $dati = PHPDOWEB()->Cache()->O; // = in serverpipe.cs

        $pos = 0;

        $remoteCache = [];

        $type = "";
        $key = "";

        //prendo le tabelle da pipe
        foreach ($dati as $value)
        {
            if ($pos == 0)
            {
                $type = $value->T;
            }

            if ($pos == 1)
            {
                if ($type == "D")
                {
                    $key = $siteName . "|D|" . $value->N; //i dati hanno 3 posizioni, Tipo, NomeDato, Valore
                }
                else
                {
                    $key = $siteName . "|" . $type; //le aree, etichette, pagine, paginedati hanno 2 posizioni, Tipo, Valore

                    $remoteCache[] = [$key, $value->V];

                    $pos = -1;
                }
            }

            if ($pos == 2)
            {
                $remoteCache[] = [$key, $value->V];

                $pos = -1;
            }

            $pos++;
        }

        // $remoteCache contiene un elenco fatto così
        // sitoweb.ext|A|202303231234
        // ...
        // sitoweb.ext|D|Slider, 202303231234
        // sitoweb.ext|D|Immagini, 202303231234
        // ..
        // sitoweb.ext|DP|202303231234
        // ..
        // sitoweb.ext|P|202303231234
        // ..

        $localCache = apcu_fetch($cacheKey, $success);

        //non c'è, aggiungo
        if (!$success)
        {
            apcu_store($cacheKey, $remoteCache, self::TTL);
            return;
        }

        //c'è ma è lunga diversa, resetto e aggiungo
        if (count($localCache) != count($remoteCache))
        {
            self::ResetDati();
            self::ResetPagine();
            self::ResetDatiPagine();
            self::ResetEtichette();
            self::ResetAree();

            apcu_store($cacheKey, $remoteCache, self::TTL);
            return;
        }

        $toReset = [];

        for ($i = 0; $i < count($localCache); $i++)
        {
            if ($localCache[$i][1] != $remoteCache[$i][1])
                $toReset[] = $localCache[$i][0];
        }

        if (empty($toReset))
            return;

        apcu_store($cacheKey, $remoteCache, self::TTL);

        $dati = $siteName . "|D|";
        $datiPagine = $siteName . "|DP";
        $pagine = $siteName . "|P";
        $aree = $siteName . "|A";
        $etichette = $siteName . "|E";

        //resetto quelle scadute
        foreach ($toReset as $key)
        {
            if (str_starts_with($key, $dati))
            {
                self::ResetDati($key);
                continue;
            }

            if ($key == $datiPagine)
            {
                self::ResetDatiPagine();
                continue;
            }

            if ($key == $pagine)
            {
                self::ResetPagine();
                continue;
            }

            if ($key == $aree)
            {
                self::ResetAree();
                continue;
            }

            if ($key == $etichette)
            {
                self::ResetEtichette();
            }
        }
    }

    private static function ResetDatiPagine(): void
    {
        $siteName = $_SERVER['PIPENAME'];
        apcu_delete(new \APCUIterator('/^' . $siteName . '\|DP|/'));
    }

    private static function ResetPagine(): void
    {
        $siteName = $_SERVER['PIPENAME'];
        apcu_delete(new \APCUIterator('/^' . $siteName . '\|P|/'));
    }

    private static function ResetAree(): void
    {
        $siteName = $_SERVER['PIPENAME'];
        apcu_delete(new \APCUIterator('/^' . $siteName . '\|A|/'));
    }

    private static function ResetEtichette(): void
    {
        $siteName = $_SERVER['PIPENAME'];
        apcu_delete(new \APCUIterator('/^' . $siteName . '\|E|/'));
    }

    static function ResetDati(string $tableName = null): void
    {
        $siteName = $_SERVER['PIPENAME'];

        if (!empty($tableName))
        {
            $tableName = str_replace("model\\", "", $tableName);
            $tableName = strtolower(preg_quote($tableName . '|', '/'));

            apcu_delete(new \APCUIterator('/^' . $siteName . '\|item\|' . $tableName . '/'));
            apcu_delete(new \APCUIterator('/^' . $siteName . '\|list\|' . $tableName . '/'));
            apcu_delete(new \APCUIterator('/^' . $siteName . '\|count\|' . $tableName . '/'));

            return;
        }

        apcu_delete(new \APCUIterator('/^' . $siteName . '\|item\|/'));
        apcu_delete(new \APCUIterator('/^' . $siteName . '\|list\|/'));
        apcu_delete(new \APCUIterator('/^' . $siteName . '\|count\|/'));
    }

    static function GetDati(string $key, bool &$success): mixed
    {
        $siteName = $_SERVER['PIPENAME'];

        $key = $siteName . "|" . str_replace("model\\", "", $key);

        //orario e array della cache
        $item = apcu_fetch($key, $success);

        if (!$success)
        {
            return null;
        }

        return $item;
    }

    static function SetDati(string $key, mixed $model): void
    {
        $siteName = $_SERVER['PIPENAME'];

        $key = $siteName . "|" . str_replace("model\\", "", $key);

        apcu_store($key, $model, self::TTL);
    }
}




