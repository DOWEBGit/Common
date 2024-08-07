<?php
declare(strict_types=1);

namespace Common\Collection;

/**
 * array con integer univoci e array
 */
class DictionaryIntArray implements \IteratorAggregate, \ArrayAccess
{
    public array $dictionary;

    public function __construct()
    {
        $this->dictionary = array();
    }

    public function Add(int $key, array $value): bool
    {
        if ($this->ContainsKey($key))
        {
            return false;
        }

        $this->dictionary[$key] = $value;
        return true;
    }

    public function AddOrUpdate(int $key, array $value): void
    {
        $this->dictionary[$key] = $value;
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

    public function TryGet(int $key, array &$value): bool
    {
        if ($this->ContainsKey($key))
        {
            $value = $this->dictionary[$key];
            return true;
        }

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

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->dictionary[$offset]);
    }

    public function offsetGet(mixed $offset): ?int
    {
        return $this->dictionary[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset))
        {
            $this->Add(0, $value);
        }
        else
        {
            $this->dictionary[$offset] = $value;
        }
    }

    //elimina un elemento dalla key
    public function offsetUnset(mixed $offset): void
    {
        unset($this->dictionary[$offset]);
    }

    public function SortByValue(bool $ascending = true): void
    {
        uasort($this->dictionary, function ($a, $b) use ($ascending) {
            return $ascending ? $a <=> $b : $b <=> $a;
        });
    }

    public function SortByKey(bool $ascending = true): void
    {
        uksort($this->dictionary, function ($a, $b) use ($ascending) {
            return $ascending ? $a <=> $b : $b <=> $a;
        });
    }
}