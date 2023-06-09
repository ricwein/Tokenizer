<?php

namespace ricwein\Tokenizer\InputSymbols;

class Block
{
    private Delimiter $symbolOpen;
    private Delimiter $symbolClose;

    public function __construct(
        string                $symbolOpen,
        ?string               $symbolClose,
        private readonly bool $shouldTokenizeContent,
        private readonly bool $splitAffixIntoSymbols = false
    )
    {
        $this->symbolOpen = new Delimiter($symbolOpen);

        if ($symbolClose !== null) {
            $this->symbolClose = new Delimiter($symbolClose);
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
