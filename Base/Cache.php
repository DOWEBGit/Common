<?php
declare(strict_types=1);

namespace Common\Base;

class Cache
{
    const TTL = 60 * 10; //10 minuti

    //https://www.php.net/manual/en/book.apcu.php

    static function UpdateCache(): void
    {
        $dati = PHPDOWEB()->DatiCache()->DatiCache;

        $isT = true;

        $nameValues = [];

        $key = "";

        //prendo le tabelle da pipe
        foreach ($dati as $value)
        {
            if ($isT)
            {
                $key = $value->T;
            }
            else
            {
                $nameValues[] = [$key, $value->V];
            }

            $isT = !$isT;
        }


        //resetto quelle scadute
        foreach ($nameValues as $nameValue)
        {
            $tableName = "Model\\" . $nameValue[0];
            $tableTime = $nameValue[1];

            $success = false;

            $timeValue = apcu_fetch($tableName, $success);

            if (!$success) //non c'è la tabella nella cache, la aggiungo con un array vuoto
            {
                $timeValue = [$tableTime, []];
                apcu_store($tableName, $timeValue, self::TTL);
                continue;
            }

            $timeLocal = $timeValue[0];

            if ($timeLocal == $tableTime)
            {
                continue;
            }

            $timeValue[0] = $tableTime;
            $timeValue[1] = [];
            apcu_store($tableName, $timeValue, self::TTL);
        }
    }

    static function Reset(string $tableName): bool
    {
        $success = false;

        $timeValues = apcu_fetch($tableName, $success);

        if (!$success)
        {
            return false;
        }

        //0 contiene la data e ora di ultimo aggiornamento
        //1 contiene l'array key value
        $timeValues[1] = [];

        apcu_store($tableName, $timeValues, self::TTL);

        return true;
    }

    static function Get(string $tableName, string $key, bool &$success): mixed
    {
        //orario e array della cache
        $timeValues = apcu_fetch($tableName, $success);

        if (!$success)
        {
            return null;
        }

        //l'array è fatto da key e valori
        $keyValues = $timeValues[1];

        if (!array_key_exists($key, $keyValues))
        {
            $success = false;
            return null;
        }

        return $keyValues[$key];
    }

    static function Set(string $tableName, string $key, mixed $model): void
    {
        //orario e array della cache
        $timeValues = apcu_fetch($tableName, $success);

        /**
         * c'è sempre in teoria, viene inizializzata nella @see UpdateCache()
         */
        if (!$success)
        {
            return;
        }

        //0 contiene la data e ora di ultimo aggiornamento
        //1 contiene l'array key value
        $timeValues[1][$key] = $model;

        apcu_store($tableName, $timeValues, self::TTL);
    }
}




