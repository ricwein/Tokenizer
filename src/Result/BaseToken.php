<?php

namespace ricwein\Tokenizer\Result;

use ricwein\Tokenizer\InputSymbols\Delimiter;

abstract class BaseToken
{
    protected int $line;
    protected ?Delimiter $delimiter;

    abstract public function __toString(): string;

    abstract public function content(): string;

    /**
     * @return Delimiter|null
     */
    public function delimiter(): ?Delimiter
    {
        return $this->delimiter;
    }

    public function line(): int
    {
        return $this->line;
    }

    /**
     * @param Delimiter|null $delimiter
     * @return $this
     */
    public function setDelimiter(?Delimiter $delimiter): self
    {
        $this->delimiter = $delimiter;
        return $this;
    }

    /**
     * @param string|null $delimiter
     * @return bool
     */
    public function isDelimiter(?string $delimiter): bool
    {
        if ($this->delimiter === null) {
            return $delimiter === null;
        }

        return $this->delimiter->is($delimiter);
    }

    /**
     * @return bool
     */
    public function isContextSwitching(): bool
    {
        if ($this->delimiter === null) {
            return false;
        }

        return $this->delimiter->isContextSwitching();
    }
}
