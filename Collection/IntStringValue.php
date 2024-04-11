<?php

namespace Common\Collection;

/**
 * @property-read int $key
 * @property-read string $value
 */
class IntStringValue
{
    public int $key;
    public string $value;

    public function __construct(int $key, string $value)
    {
        $this->key = $key;
        $this->value = $value;
    }
}