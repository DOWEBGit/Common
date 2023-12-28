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

        $remoteTableDates = [];

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
                $remoteTableDates[] = [$key, $value->V];
            }

            $isT = !$isT;
        }

        $localTableDates = apcu_fetch("TableDates", $success);

        //non c'è, aggiungo
        if (!$success)
        {
            apcu_store("TableDates", $remoteTableDates, self::TTL);
            return;
        }

        //c'è ma è lunga diversa, resetto e aggiungo
        if (count($localTableDates) != count($remoteTableDates))
        {
            apcu_clear_cache();
            apcu_store("TableDates", $remoteTableDates, self::TTL);
            return;
        }

        $toReset = [];

        for ($i = 0; $i < count($localTableDates); $i++)
        {
            if ($localTableDates[$i][1] !=  $remoteTableDates[$i][1])
                $toReset[] = $localTableDates[$i][0];
        }

        if (empty($toReset))
            return;

        apcu_store("TableDates", $remoteTableDates, self::TTL);

        //resetto quelle scadute
        foreach ($toReset as $table)
        {
            self::Reset($table);
        }
    }

    static function Reset(string $tableName): void
    {
        $tableName = str_replace("model\\", "", $tableName);

        $tableName = strtolower(preg_quote($tableName . '|', '/'));

        apcu_delete(new \APCUIterator('/^item\|' . $tableName . '/'));
        apcu_delete(new \APCUIterator('/^list\|' . $tableName . '/'));
        apcu_delete(new \APCUIterator('/^count\|' . $tableName . '/'));
    }

    static function Get(string $key, bool &$success): mixed
    {
        $key = str_replace("model\\", "", $key);

        //orario e array della cache
        $item = apcu_fetch($key, $success);

        if (!$success)
        {
            return null;
        }

        return $item;
    }

    static function Set(string $key, mixed $model): void
    {
        $key = str_replace("model\\", "", $key);

        apcu_store($key, $model, self::TTL);
    }
}




