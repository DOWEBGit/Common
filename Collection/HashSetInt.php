<?php

namespace Common\Collection;

/**
 * array con integer univoci
 */
class HashSetInt implements \IteratorAggregate, \ArrayAccess
{
    private $internalArray;

    public function __construct()
    {
        $this->internalArray = array();
    }

    public function Add(int $value): bool
    {
        if (!$this->Contains($value)) {
            $this->internalArray[] = $value;
            return true;
        }

        return false;
    }

    public function Remove(int $value): bool
    {
        $index = array_search($value, $this->internalArray);

        if ($index !== false) {
            unset($this->internalArray[$index]);
            return true;
        }

        return false;
    }

    public function Contains(int $value): bool
    {
        return in_array($value, $this->internalArray);
    }

    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->internalArray);
    }

    public function Count(): int
    {
        return count($this->internalArray);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->internalArray[$offset]);
    }

    public function offsetGet(mixed $offset): ?int
    {
        return $this->internalArray[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->Add($value);
        } else {
            $this->internalArray[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->internalArray[$offset]);
    }
}
