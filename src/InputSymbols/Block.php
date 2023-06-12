<?php

declare(strict_types=1);

namespace ricwein\Tokenizer\InputSymbols;

class Block
{
    private Delimiter $symbolOpen;
    private Delimiter $symbolClose;

    public function __construct(
        string                $symbol,
        ?string               $symbolClose = null,
        private readonly bool $shouldTokenizeContent = true,
        private readonly bool $splitAffixIntoSymbols = false,
        null|string           $escapeSymbol = null
    )
    {
        $this->symbolOpen = new Delimiter($symbol, escapeSymbol: $escapeSymbol);

        if ($symbolClose !== null) {
            $this->symbolClose = new Delimiter($symbolClose, escapeSymbol: $escapeSymbol);
        } else {
            $this->symbolClose = $this->symbolOpen;
        }
    }

    public function open(): Delimiter
    {
        return $this->symbolOpen;
    }

    public function close(): Delimiter
    {
        return $this->symbolClose;
    }

    /**
     * Compares open/close tokens for easy block identification
     */
    public function is(string $token, ?string $closeToken = null): bool
    {
        if ($closeToken !== null) {
            return $this->open()->symbol() === $token && $this->close()->symbol() === $closeToken;
        }
        return "{$this->open()}{$this->close()}" === $token;
    }

    public function shouldTokenizeContent(): bool
    {
        return $this->shouldTokenizeContent;
    }

    public function splitAffixIntoSymbols(): bool
    {
        return $this->splitAffixIntoSymbols;
    }

    public function __toString()
    {
        return "{$this->open()}{$this->close()}";
    }
}
