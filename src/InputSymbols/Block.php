<?php

namespace ricwein\Tokenizer\InputSymbols;

class Block
{
    private Delimiter $symbolOpen;
    private Delimiter $symbolClose;

    private bool $shouldTokenizeContent;

    /**
     * Block constructor.
     * @param string $symbolOpen
     * @param string|null $symbolClose
     * @param bool $shouldTokenizeContent
     */
    public function __construct(string $symbolOpen, ?string $symbolClose, bool $shouldTokenizeContent)
    {
        $this->symbolOpen = new Delimiter($symbolOpen);

        if ($symbolClose !== null) {
            $this->symbolClose = new Delimiter($symbolClose);
        } else {
            $this->symbolClose = $this->symbolOpen;
        }

        $this->shouldTokenizeContent = $shouldTokenizeContent;
    }

    /**
     * @return Delimiter
     */
    public function open(): Delimiter
    {
        return $this->symbolOpen;
    }

    /**
     * @return Delimiter
     */
    public function close(): Delimiter
    {
        return $this->symbolClose;
    }

    /**
     * Compares open/close tokens for easy block identification
     * @param string $token
     * @param string|null $closeToken
     * @return bool
     */
    public function is(string $token, ?string $closeToken): bool
    {
        if ($closeToken !== null) {
            return $this->open()->symbol() === $token && $this->close()->symbol() === $closeToken;
        }
        return "{$this->open()}{$this->close()}" === $token;
    }

    /**
     * @return bool
     */
    public function shouldTokenizeContent(): bool
    {
        return $this->shouldTokenizeContent;
    }

    public function __toString()
    {
        return "{$this->open()}{$this->close()}";
    }
}
