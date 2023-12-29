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

        //echo "Exist 1 : " . apcu_exists($cacheKey) . "<br>";

        $success = false;

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

        //echo "Exist 2 : " . apcu_exists($cacheKey) . "<br>";


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
                $table = str_replace($dati,"", $key);
                self::ResetDati($table);
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
        apcu_delete(new \APCUIterator('/^' . $siteName . '\|DP\|/'));
    }

    private static function ResetAree(): void
    {
        $siteName = $_SERVER['PIPENAME'];
        apcu_delete(new \APCUIterator('/^' . $siteName . '\|A\|/'));
    }

    #region Pagine

    private static function ResetPagine(): void
    {
        $siteName = $_SERVER['PIPENAME'];
        apcu_delete(new \APCUIterator('/^' . $siteName . '\|P\|/'));
    }

    static function GetPagine(string $key, bool &$success): mixed
    {
        $siteName = $_SERVER['PIPENAME'];

        $key = $siteName . "|P|" . $key;

        // Verifica se la cache è già stata inizializzata
        if (!isset($GLOBALS['CachePagine']))
            $GLOBALS['CachePagine'] = [];

        $globalCache = &$GLOBALS['CachePagine'];

        if (array_key_exists($key, $globalCache))
        {
            echo "localcache<br>";
            return $globalCache[$key];
        }

        //orario e array della cache
        $item = apcu_fetch($key, $success);

        if (!$success)
        {
            echo "nocache<br>";
            return null;
        }

        $globalCache[$key] = $item;

        echo "apcucache<br>";
        return $item;
    }

    static function SetPagine(string $key, mixed $value): void
    {
        $siteName = $_SERVER['PIPENAME'];

        $key = $siteName . "|P|" . $key;

        // Verifica se la cache è già stata inizializzata
        if (!isset($GLOBALS['CachePagine']))
            $GLOBALS['CachePagine'] = [];

        $globalCache = &$GLOBALS['CachePagine'];

        //orario e array della cache
        apcu_store($key, $value, self::TTL);

        $globalCache[$key] = $value;
    }

    #endregion

    #region Etichette

    private static function ResetEtichette(): void
    {
        $siteName = $_SERVER['PIPENAME'];
        apcu_delete(new \APCUIterator('/^' . $siteName . '\|E\|/'));
    }

    static function GetEtichette(\Code\Enum\EtichetteEnum $etichetteEnum, string $iso, bool $encode, bool &$success): mixed
    {
        $siteName = $_SERVER['PIPENAME'];

        $key = $siteName . "|E|" . $etichetteEnum->name . "|" . $encode . "|" . $iso;

        // Verifica se la cache è già stata inizializzata
        if (!isset($GLOBALS['CacheEtichette']))
        {
            $GLOBALS['CacheEtichette'] = [];
        }

        $globalCache = &$GLOBALS['CacheEtichette'];

        if (array_key_exists($key, $globalCache))
        {
            echo "localcache<br>";
            return $globalCache[$key];
        }

        //orario e array della cache
        $item = apcu_fetch($key, $success);

        if (!$success)
        {
            echo "nocache<br>";
            return null;
        }

        $globalCache[$key] = $item;

        echo "apcucache<br>";
        return $item;
    }

    static function SetEtichette(\Code\Enum\EtichetteEnum $etichetteEnum, string $iso, bool $encode, mixed $value): void
    {
        $siteName = $_SERVER['PIPENAME'];

        $key = $siteName . "|E|" . $etichetteEnum->name . "|" . $encode . "|" . $iso;

        // Verifica se la cache è già stata inizializzata
        if (!isset($GLOBALS['CacheEtichette']))
            $GLOBALS['CacheEtichette'] = [];

        $globalCache = &$GLOBALS['CacheEtichette'];

        $globalCache[$key] = $value;

        apcu_store($key, $value, self::TTL);
    }

    #endregion

    #region Dati

    static function ResetDati(string $tableName = null): void
    {
        if (isset($GLOBALS['CacheDati']))
            unset($GLOBALS['CacheDati']);

        $siteName = $_SERVER['PIPENAME'];

        if (!empty($tableName))
        {
            $tableName = str_replace("model\\", "", strtolower($tableName));
            $tableName = strtolower(preg_quote($tableName . '|', '/'));

            echo "Reset: " . $tableName . "<br>";

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
        $key = $_SERVER['PIPENAME'] . "|" . str_replace("model\\", "", $key);

        // Verifica se la cache è già stata inizializzata
        if (!isset($GLOBALS['CacheDati']))
            $GLOBALS['CacheDati'] = [];

        $globalCache = &$GLOBALS['CacheDati'];

        if (array_key_exists($key, $globalCache))
        {
            echo "localcache<br>";
            return $globalCache[$key];
        }

        $item = apcu_fetch($key, $success);

        if (!$success)
        {
            echo "nocache<br>";
            return null;
        }

        $globalCache[$key] = $item;

        echo "apcu<br>";

        return $item;
    }

    static function SetDati(string $key, mixed $model): void
    {
        $key = $_SERVER['PIPENAME'] . "|" . str_replace("model\\", "", $key);

        // Verifica se la cache è già stata inizializzata
        if (!isset($GLOBALS['CacheDati']))
            $GLOBALS['CacheDati'] = [];

        $globalCache = &$GLOBALS['CacheDati'];

        $globalCache[$key] = $model;

        apcu_store($key, $model, self::TTL);
    }

    #endregion
}




