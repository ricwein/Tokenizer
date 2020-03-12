<?php

namespace ricwein\Tokenizer\Result;

use ricwein\Tokenizer\InputSymbols\Delimiter;

abstract class ResultSymbolBase
{
    protected ?Delimiter $delimiter;

    abstract public function __toString(): string;

    /**
     * @return Delimiter|null
     */
    public function delimiter(): ?Delimiter
    {
        return $this->delimiter;
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
        return $this->delimiter->isContextSwitching();
    }
}
