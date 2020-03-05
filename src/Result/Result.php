<?php

namespace ricwein\Tokenizer\Result;

use ArrayAccess;

class Result implements ArrayAccess
{

    /**
     * @var ResultBlock[]|ResultSymbol[]
     */
    private array $results = [];

    public function __construct(array $results)
    {
        $this->results = $results;
    }

    public function add(ResultSymbolInterface $symbol)
    {
        $this->results[] = $symbol;
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset): bool
    {
        return isset($this->results[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return isset($this->results[$offset]) ? $this->results[$offset] : null;
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        if ($offset !== null) {
            $this->results[] = $value;
        } else {
            $this->results[$offset] = $value;
        }
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        unset($this->results[$offset]);
    }

    public function __toString(): string
    {
        $lines = [];
        foreach ($this->results as $key => $symbol) {
            $line = PHP_EOL . "START: [{$key}] ====================" . PHP_EOL;

            $symbol = explode(PHP_EOL, (string)$symbol);
            foreach ($symbol as $symbolLine) {
                $line .= "    {$symbolLine}" . PHP_EOL;
            }

            $line .= "END: [{$key}] ====================" . PHP_EOL;

            $lines[] = $line;
        }

        return implode(PHP_EOL, $lines);
    }
}
