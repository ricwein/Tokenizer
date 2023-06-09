<?php

namespace ricwein\Tokenizer\Result;

use ArrayAccess;
use Countable;

class TokenStream implements ArrayAccess, Countable
{
    private int $currentOffset = 0;

    /**
     * @param BaseToken[] $tokens
     */
    public function __construct(private array $tokens)
    {
    }

    public function add(BaseToken $token): self
    {
        $this->tokens[] = $token;
        return $this;
    }

    public function isEmpty(): bool
    {
        return count($this->tokens) <= 0;
    }

    /**
     * @return BaseToken[]
     */
    public function tokens(): array
    {
        return $this->tokens;
    }

    public function next(): ?BaseToken
    {
        if (!isset($this->tokens[$this->currentOffset])) {
            return null;
        }

        $token = $this->tokens[$this->currentOffset];
        ++$this->currentOffset;
        return $token;
    }

    public function prev(): ?BaseToken
    {
        return $this->tokens[$this->currentOffset - 1] ?? null;
    }

    public function reset(int $offset = 0): void
    {
        $this->currentOffset = $offset;
    }

    /**
     * @inheritDoc
     * @param int $offset
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->tokens[$offset]);
    }

    /**
     * @inheritDoc
     * @param int $offset
     */
    public function offsetGet($offset): ?BaseToken
    {
        return $this->tokens[$offset] ?? null;
    }

    /**
     * @inheritDoc
     * @param null|int $offset
     * @param BaseToken $value
     */
    public function offsetSet($offset, $value): void
    {
        if ($offset !== null) {
            $this->tokens[] = $value;
        } else {
            $this->tokens[$offset] = $value;
        }
    }

    /**
     * @inheritDoc
     * @param int $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->tokens[$offset]);
    }

    public function __toString(): string
    {
        return implode('', $this->tokens);
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->tokens);
    }
}
