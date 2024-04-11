<?php

namespace Common\Collection;

/**
 * @property-read string $key
 * @property-read int $value
 */
class StringIntValue
{
    public string $key;
    public int $value;

    public function __construct(string $key, int $value)
    {
        $this->key = $key;
        $this->value = $value;
    }
}