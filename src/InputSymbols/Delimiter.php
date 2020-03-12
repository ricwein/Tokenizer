<?php

namespace ricwein\Tokenizer\InputSymbols;

class Delimiter
{
    private string $symbol;
    private int $length;
    private bool $isContextSwitching;

    /**
     * Delimiter constructor.
     * @param string $symbol
     * @param bool $isContextSwitching
     */
    public function __construct(string $symbol, bool $isContextSwitching = false)
    {
        $this->symbol = $symbol;
        $this->length = strlen($symbol);
        $this->isContextSwitching = $isContextSwitching;
    }

    /**
     * @return string
     */
    public function symbol(): string
    {
        return $this->symbol;
    }

    /**
     * @param string $delimiter
     * @return bool
     */
    public function is(string $delimiter): bool
    {
        return $this->symbol === $delimiter;
    }

    /**
     * @return int
     */
    public function length(): int
    {
        return $this->length;
    }

    /**
     * @return bool
     */
    public function isContextSwitching(): bool
    {
        return $this->isContextSwitching;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->symbol();
    }
}
