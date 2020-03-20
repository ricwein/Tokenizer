<?php

namespace ricwein\Tokenizer\Result;

use ArrayAccess;
use Countable;

class TokenStream implements ArrayAccess, Countable
{
    /**
     * @var BlockToken[]|Token[]
     */
    private array $tokens = [];
    private int $currentOffset = 0;

    public function __construct(array $tokens)
    {
        $this->tokens = $tokens;
    }

    /**
     * @param BaseToken $token
     */
    public function add(BaseToken $token)
    {
        $this->tokens[] = $token;
    }

    public function isEmpty(): bool
    {
        return count($this->tokens) <= 0;
    }

    /**
     * @return Token[]|BlockToken[]
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
        $this->currentOffset += 1;
        return $token;
    }

    public function prev(): ?BaseToken
    {
        if (!isset($this->tokens[$this->currentOffset - 1])) {
            return null;
        }

        return $this->tokens[$this->currentOffset - 1];
    }

    public function reset(): void
    {
        $this->currentOffset = 0;
    }

    /**
     * @inheritDoc
     * @param int $offset
     */
    public function offsetExists($offset): bool
    {
        return isset($this->tokens[$offset]);
    }

    /**
     * @inheritDoc
     * @param int $offset
     * @return BlockToken|Token
     */
    public function offsetGet($offset)
    {
        return isset($this->tokens[$offset]) ? $this->tokens[$offset] : null;
    }

    /**
     * @inheritDoc
     * @param null|int $offset
     * @param BlockToken|Token $value
     */
    public function offsetSet($offset, $value)
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
    public function offsetUnset($offset)
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
