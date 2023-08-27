<?php

namespace Common\Collection;

/**
 * array con integer univoci e stringhe
 */
class DictionaryIntString implements \IteratorAggregate
{
    private $dictionary;

    public function __construct()
    {
        $this->dictionary = array();
    }

    public function Add(int $key, string $value): bool
    {
        if ($this->ContainsKey($key))
        {
            return false;
        }

        $this->dictionary[$key] = $value;
        return true;
    }

    public function Remove(int $key): bool
    {
        if ($this->ContainsKey($key))
        {
            unset($this->dictionary[$key]);
            return true;
        }

        return false;
    }

    public function TryGet(int $key, string &$value): bool
    {
        if ($this->ContainsKey($key))
        {
            $value = $this->dictionary[$key];
            return true;
        }

        $value = null;
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