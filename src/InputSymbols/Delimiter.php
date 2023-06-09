<?php

namespace ricwein\Tokenizer\InputSymbols;

class Delimiter
{
    private int $length;

    public function __construct(private readonly string $symbol, private readonly bool $isContextSwitching = false)
    {
        $this->length = strlen($symbol);
    }

    public function symbol(): string
    {
        return $this->symbol;
    }

    public function is(string $delimiter): bool
    {
        return $this->symbol === $delimiter;
    }

    public function length(): int
    {
        return $this->length;
    }

    public function isContextSwitching(): bool
    {
        return $this->isContextSwitching;
    }

    public function __toString()
    {
        return $this->symbol();
    }
}
