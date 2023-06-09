<?php

namespace ricwein\Tokenizer\Result;

use ricwein\Tokenizer\InputSymbols\Block;
use ricwein\Tokenizer\InputSymbols\Delimiter;

class BlockToken extends BaseToken
{
    /** @var Token[]|BlockToken[] */
    private array $tokens = [];

    private ?string $prefix = null;
    private ?string $suffix = null;

    public function __construct(private readonly Block $blockSymbol, ?Delimiter $delimiter, int $line = 1)
    {
        parent::__construct($delimiter, $line);
    }

    public function withPrefix(?string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function withSuffix(?string $suffix): self
    {
        $this->suffix = $suffix;
        return $this;
    }

    public function block(): Block
    {
        return $this->blockSymbol;
    }

    /**
     * returns re-synthesized block content without:
     *  - delimiters
     *  - pre/suffixes
     *  - open/close block-delimiters
     */
    public function content(): string
    {
        return implode('', $this->tokens());
    }

    /**
     * Compares open/close tokens for easy block identification
     */
    public function isBlock(string $token, ?string $closeToken = null): bool
    {
        return $this->block()->is($token, $closeToken);
    }

    /**
     * @param Token[]|BlockToken[] $tokens
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
     */
    public function __toString(): string
    {
        return implode('', [
            (string)($this->delimiter ?? ''),
            $this->prefix ?? '',
            $this->block()->open(),
            $this->content(),
            $this->block()->close(),
            $this->suffix ?? ''
        ]);
    }
}
