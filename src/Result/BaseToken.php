<?php

declare(strict_types=1);

namespace ricwein\Tokenizer\Result;

use ricwein\Tokenizer\InputSymbols\Delimiter;

abstract class BaseToken
{
    public function __construct(protected ?Delimiter $delimiter, private readonly int $line = 1)
    {
    }

    abstract public function __toString(): string;

    abstract public function content(): string;

    public function delimiter(): ?Delimiter
    {
        return $this->delimiter;
    }

    public function line(): int
    {
        return $this->line;
    }

    public function setDelimiter(?Delimiter $delimiter): self
    {
        $this->delimiter = $delimiter;
        return $this;
    }

    public function isDelimiter(?string $delimiter): bool
    {
        if ($this->delimiter === null) {
            return $delimiter === null;
        }

        return $this->delimiter->is($delimiter);
    }

    public function isContextSwitching(): bool
    {
        if ($this->delimiter === null) {
            return false;
        }

        return $this->delimiter->isContextSwitching();
    }
}
