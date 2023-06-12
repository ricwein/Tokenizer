<?php

declare(strict_types=1);

namespace ricwein\Tokenizer\InputSymbols;

class Delimiter
{
    private int $length;
    private ?Delimiter $escapeSymbol;

    public function __construct(
        private readonly string $symbol,
        private readonly bool   $isContextSwitching = false,
        null|string             $escapeSymbol = null
    )
    {
        $this->length = strlen($symbol);
        $this->escapeSymbol = $escapeSymbol === null ? null : new Delimiter("$escapeSymbol$this->symbol");
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

    public function getEscapeSymbol(): ?self
    {
        return $this->escapeSymbol;
    }

    /**
     * @internal
     */
    public function setEscapeSymbol(Delimiter|string $escapeSymbol): self
    {
        $this->escapeSymbol = $escapeSymbol instanceof self ? $escapeSymbol : new self($escapeSymbol);
        return $this;
    }
}
