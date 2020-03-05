<?php

namespace ricwein\Tokenizer\Result;

use ArrayAccess;
use Countable;

class Result implements ArrayAccess, Countable
{
    /**
     * @var ResultBlock[]|ResultSymbol[]
     */
    private array $results = [];

    public function __construct(array $results)
    {
        $this->results = $results;
    }

    /**
     * @param ResultSymbolBase $symbol
     */
    public function add(ResultSymbolBase $symbol)
    {
        $this->results[] = $symbol;
    }

    /**
     * @inheritDoc
     * @param int $offset
     */
    public function offsetExists($offset): bool
    {
        return isset($this->results[$offset]);
    }

    /**
     * @inheritDoc
     * @param int $offset
     * @return ResultBlock|ResultSymbol
     */
    public function offsetGet($offset)
    {
        return isset($this->results[$offset]) ? $this->results[$offset] : null;
    }

    /**
     * @inheritDoc
     * @param null|int $offset
     * @param ResultBlock|ResultSymbol $value
     */
    public function offsetSet($offset, $value)
    {
        if ($offset !== null) {
            $this->results[] = $value;
        } else {
            $this->results[$offset] = $value;
        }
    }

    public function isEmpty(): bool
    {
        return count($this->results) <= 0;
    }

    /**
     * @inheritDoc
     * @param int $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->results[$offset]);
    }

    public function __toString(): string
    {
        return implode('', $this->results);
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->results);
    }
}
