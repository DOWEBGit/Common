<?php
declare(strict_types=1);

namespace Common;

class Cache
{
    const int TTL = 60 * 10; //10 minuti

    //https://www.php.net/manual/en/book.apcu.php

    static function UpdateCache(): void
    {
        $siteName = $_SERVER['PIPENAME'];

        $cacheKey = $siteName . "|Cache";

        /** @noinspection PhpUndefinedFunctionInspection */
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
            self::ResetLingue();
            self::ResetSiteVars();

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
        $lingue = $siteName . "|L";
        $impostazioni = $siteName . "|I";

        //resetto quelle scadute
        foreach ($toReset as $key)
        {
            if (str_starts_with($key, $dati))
            {
                $table = str_replace($dati, "", $key);
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

            if ($key == $lingue)
            {
                self::ResetLingue();
                continue;
            }

            if ($key == $etichette)
            {
                self::ResetEtichette();
            }

            if ($key == $impostazioni)
            {
                self::ResetSiteVars();
            }
        }
    }

    #region Lingue

    private static function ResetLingue(): void
    {
        $siteName = $_SERVER['PIPENAME'];
        apcu_delete(new \APCUIterator('/^' . $siteName . '\|L\|/'));
    }

    static function GetLingue(string $key, bool &$success): mixed
    {
        $siteName = $_SERVER['PIPENAME'];

        $key = $siteName . "|L|" . $key;

        // Verifica se la cache è già stata inizializzata
        if (!isset($GLOBALS['CacheLingue']))
            $GLOBALS['CacheLingue'] = [];

        $globalCache = &$GLOBALS['CacheLingue'];

        if (array_key_exists($key, $globalCache))
        {
            //echo "localcache<br>";
            $success = true;
            return $globalCache[$key];
        }

        //orario e array della cache
        $item = apcu_fetch($key, $success);

        if (!$success)
        {
            //echo "nocache<br>";
            return null;
        }

        $globalCache[$key] = $item;

        //echo "apcucache<br>";
        return $item;
    }

    static function SetLingue(string $key, mixed $value): void
    {
        $siteName = $_SERVER['PIPENAME'];

        $key = $siteName . "|L|" . $key;

        // Verifica se la cache è già stata inizializzata
        if (!isset($GLOBALS['CacheLingue']))
            $GLOBALS['CacheLingue'] = [];

        $globalCache = &$GLOBALS['CacheLingue'];

        //orario e array della cache
        apcu_store($key, $value, self::TTL);

        $globalCache[$key] = $value;
    }

    #endregion

    #region Aree

    private static function ResetAree(): void
    {
        $siteName = $_SERVER['PIPENAME'];
        apcu_delete(new \APCUIterator('/^' . $siteName . '\|A\|/'));
    }

    static function GetAree(string $key, bool &$success): mixed
    {
        $siteName = $_SERVER['PIPENAME'];

        $key = $siteName . "|A|" . $key;

        // Verifica se la cache è già stata inizializzata
        if (!isset($GLOBALS['CacheAree']))
            $GLOBALS['CacheAree'] = [];

        $globalCache = &$GLOBALS['CacheAree'];

        if (array_key_exists($key, $globalCache))
        {
            //echo "localcache<br>";
            $success = true;
            return $globalCache[$key];
        }

        //orario e array della cache
        $item = apcu_fetch($key, $success);

        if (!$success)
        {
            //echo "nocache<br>";
            return null;
        }

        $globalCache[$key] = $item;

        //echo "apcucache<br>";
        return $item;
    }

    static function SetAree(string $key, mixed $value): void
    {
        $siteName = $_SERVER['PIPENAME'];

        $key = $siteName . "|A|" . $key;

        // Verifica se la cache è già stata inizializzata
        if (!isset($GLOBALS['CacheAree']))
            $GLOBALS['CacheAree'] = [];

        $globalCache = &$GLOBALS['CacheAree'];

        //orario e array della cache
        apcu_store($key, $value, self::TTL);

        $globalCache[$key] = $value;
    }

    #endregion

    #region PagineDati

    private static function ResetDatiPagine(): void
    {
        $siteName = $_SERVER['PIPENAME'];
        apcu_delete(new \APCUIterator('/^' . $siteName . '\|DP\|/'));
    }

    static function GetDatiPagine(string $key, bool &$success): mixed
    {
        $siteName = $_SERVER['PIPENAME'];

        $key = $siteName . "|DP|" . $key;

        // Verifica se la cache è già stata inizializzata
        if (!isset($GLOBALS['CacheDatiPagine']))
            $GLOBALS['CacheDatiPagine'] = [];

        $globalCache = &$GLOBALS['CacheDatiPagine'];

        if (array_key_exists($key, $globalCache))
        {
            //echo "localcache<br>";
            $success = true;
            return $globalCache[$key];
        }

        //orario e array della cache
        $item = apcu_fetch($key, $success);

        if (!$success)
        {
            //echo "nocache<br>";
            return null;
        }

        $globalCache[$key] = $item;

        //echo "apcucache<br>";
        return $item;
    }

    static function SetDatiPagine(string $key, mixed $value): void
    {
        $siteName = $_SERVER['PIPENAME'];

        $key = $siteName . "|DP|" . $key;

        // Verifica se la cache è già stata inizializzata
        if (!isset($GLOBALS['CacheDatiPagine']))
            $GLOBALS['CacheDatiPagine'] = [];

        $globalCache = &$GLOBALS['CacheDatiPagine'];

        //orario e array della cache
        apcu_store($key, $value, self::TTL);

        $globalCache[$key] = $value;
    }

    #endregion

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
            //echo "localcache<br>";
            $success = true;
            return $globalCache[$key];
        }

        //orario e array della cache
        $item = apcu_fetch($key, $success);

        if (!$success)
        {
            //echo "nocache<br>";
            return null;
        }

        $globalCache[$key] = $item;

        //echo "apcucache<br>";
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

    #region Impostazioni

    private static function ResetSiteVars(): void
    {
        $siteName = $_SERVER['PIPENAME'];
        apcu_delete(new \APCUIterator('/^' . $siteName . '\|SV\|/'));
    }

    static function GetSiteVars(\Common\VarsEnum $impostazioniEnum): ?string
    {
        $siteName = $_SERVER['PIPENAME'];

        $key = $siteName . "|SV|" . $impostazioniEnum->name;

        // Verifica se la cache è già stata inizializzata
        if (!isset($GLOBALS['CacheSiteVars']))
            $GLOBALS['CacheSiteVars'] = [];

        $globalCache = &$GLOBALS['CacheSiteVars'];

        if (array_key_exists($key, $globalCache))
        {
            //echo "localcache<br>";
            return $globalCache[$key];
        }

        //orario e array della cache
        $item = apcu_fetch($key, $success);

        if (!$success)
        {
            //echo "nocache<br>";
            return null;
        }

        $globalCache[$key] = $item;

        //echo "apcucache<br>";
        return $item;
    }

    static function SetSiteVars(\Common\VarsEnum $impostazioniEnum, string $value): void
    {
        $siteName = $_SERVER['PIPENAME'];

        $key = $siteName . "|SV|" . $impostazioniEnum->name;

        // Verifica se la cache è già stata inizializzata
        if (!isset($GLOBALS['CacheSiteVars']))
            $GLOBALS['CacheSiteVars'] = [];

        $globalCache = &$GLOBALS['CacheSiteVars'];

        $globalCache[$key] = $value;

        apcu_store($key, $value, self::TTL);
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
            //echo "localcache<br>";
            $success = true;
            return $globalCache[$key];
        }

        //orario e array della cache
        $item = apcu_fetch($key, $success);

        if (!$success)
        {
            //echo "nocache<br>";
            return null;
        }

        $globalCache[$key] = $item;

        //echo "apcucache<br>";
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

    static function ResetDati(?string $tableName = null): void
    {
        if (isset($GLOBALS['CacheDati']))
            unset($GLOBALS['CacheDati']);

        $siteName = $_SERVER['PIPENAME'];

        if (!empty($tableName))
        {
            $tableName = str_replace(" ", "_", $tableName);

            $tableName = str_replace("model\\", "", strtolower($tableName));
            $tableName = strtolower(preg_quote($tableName . '|', '/'));

            //echo "Reset: " . $tableName . "<br>";

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
            //echo "localcache<br>";
            $success = true;
            return $globalCache[$key];
        }

        $item = apcu_fetch($key, $success);

        if (!$success)
        {
            //echo "nocache<br>";
            return null;
        }

        $globalCache[$key] = $item;

        //echo "apcu<br>";

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




