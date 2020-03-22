<?php

namespace ricwein\Tokenizer\Result;

use ricwein\Tokenizer\InputSymbols\Block;
use ricwein\Tokenizer\InputSymbols\Delimiter;

class BlockToken extends BaseToken
{
    private Block $blockSymbol;

    /** @var Token[]|BlockToken[] */
    private array $tokens = [];
    private ?string $prefix = null;
    private ?string $suffix = null;

    /**
     * ResultBlock constructor.
     * @param Block $blockSymbol
     * @param Delimiter|null $delimiter
     */
    public function __construct(Block $blockSymbol, ?Delimiter $delimiter)
    {
        $this->blockSymbol = $blockSymbol;
        $this->delimiter = $delimiter;
    }

    /**
     * @param string|null $prefix
     * @return $this
     */
    public function withPrefix(?string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * @param string|null $suffix
     * @return $this
     */
    public function withSuffix(?string $suffix): self
    {
        $this->suffix = $suffix;
        return $this;
    }

    /**
     * @return Block
     */
    public function block(): Block
    {
        return $this->blockSymbol;
    }

    /**
     * returns re-synthesized block content without:
     *  - delimiters
     *  - pre/suffixes
     *  - open/close block-delimiters
     * @return string
     */
    public function content(): string
    {
        return implode('', $this->tokens());
    }

    /**
     * Compares open/close tokens for easy block identification
     * @param string $token
     * @param string|null $closeToken
     * @return bool
     */
    public function isBlock(string $token, ?string $closeToken = null): bool
    {
        return $this->block()->is($token, $closeToken);
    }

    /**
     * @param Token[]|BlockToken[] $tokens
     * @return $this
     */
    public function withSymbols(array $tokens): self
    {
        $this->tokens = $tokens;
        return $this;
    }

    /**
     * @return Token[]|BlockToken[]
     */
    public function tokens(): array
    {
        return $this->tokens;
    }

    public function prefix(): ?string
    {
        return $this->prefix;
    }

    public function suffix(): ?string
    {
        return $this->suffix;
    }

    /**
     * rebuilds input-string from tokenized symbols
     * @return string
     */
    public function __toString(): string
    {
        return implode('', [
            $this->delimiter ?? '',
            $this->prefix ?? '',
            $this->block()->open(),
            $this->content(),
            $this->block()->close(),
            $this->suffix ?? ''
        ]);
    }
}
