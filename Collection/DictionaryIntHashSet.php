<?php
declare(strict_types=1);

namespace Common\Collection;

/**
 * dizionario creato a int e come valore un hashset
 */

class DictionaryIntHashSet implements \IteratorAggregate
{
    public array $dictionary;

    public function __construct()
    {
        $this->dictionary = array();
    }

    /**
     * aggiunge la chiave e il valore, verifica che siano entrambi univoci, il valore è un hashset
    */
    public function Add(int $key, int $value): bool
    {
        if (!$this->ContainsKey($key)) {
            $this->dictionary[$key] = new HashSetInt();
        }

        return $this->dictionary[$key]->Add($value);
    }

    public function Remove(int $key): bool
    {
        $index = array_search($key, $this->dictionary);

        if ($index !== false) {
            unset($this->dictionary[$index]);
            return true;
        }

        return false;
    }

    public function TryGet(int $key, &$hashSet): bool
    {
        if ($this->ContainsKey($key)) {
            $hashSet = $this->dictionary[$key];
            return true;
        }

        $hashSet = null;
        return false;
    }

    private function ContainsKey(int $key): bool
    {
        return array_key_exists($key, $this->dictionary);
    }

    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->dictionary);
    }

    public function Count(): int
    {
        return count($this->dictionary);
    }
}