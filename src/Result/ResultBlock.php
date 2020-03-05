<?php

namespace ricwein\Tokenizer\Result;

use ricwein\Tokenizer\InputSymbols\Block;
use ricwein\Tokenizer\InputSymbols\Delimiter;

class ResultBlock extends ResultSymbolBase
{
    private Block $blockSymbol;

    /** @var ResultSymbol[]|ResultBlock[] */
    private array $symbols = [];
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
     * @param string $prefix
     * @return $this
     */
    public function withPrefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * @param string $suffix
     * @return $this
     */
    public function withSuffix(string $suffix): self
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
     * Compares open/close tokens for easy block identification
     * @param string $token
     * @param string|null $closeToken
     * @return bool
     */
    public function isBlock(string $token, ?string $closeToken): bool
    {
        return $this->block()->is($token, $closeToken);
    }

    /**
     * @param ResultSymbol[]|ResultBlock[] $symbols
     * @return $this
     */
    public function withSymbols(array $symbols): self
    {
        $this->symbols = $symbols;
        return $this;
    }

    /**
     * @return ResultSymbol[]|ResultBlock[]
     */
    public function symbols(): array
    {
        return $this->symbols;
    }

    /**
     * rebuilds input-string from tokenized symbols
     * @return string
     */
    public function __toString(): string
    {
        return implode('', array_filter([
            $this->delimiter,
            $this->prefix,
            $this->block()->open(),
            implode('', $this->symbols()),
            $this->block()->close(),
            $this->suffix
        ], function (?string $input): bool {
            return $input !== null;
        }));
    }
}
