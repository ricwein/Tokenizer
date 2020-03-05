<?php

namespace ricwein\Tokenizer\Result;

use ricwein\Tokenizer\InputSymbols\Block;
use ricwein\Tokenizer\InputSymbols\Delimiter;

class ResultBlock implements ResultSymbolInterface
{
    private Block $blockSymbol;
    private ?Delimiter $delimiter;

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
     * Helpful for debugging
     * @return string
     */
    public function __toString(): string
    {
        $symbolString = "";
        foreach ($this->symbols as $key => $symbol) {
            $symbolString .= sprintf("%s   [%d]: %s%s", PHP_EOL, $key, PHP_EOL, $symbol);
        }

        return <<<EOD
        >>>> BLOCK <<<<
        Block: {$this->block()}
        Delimiter: {$this->delimiter}
        Symbols: {$symbolString}
        Prefix: {$this->prefix}
        Suffix: {$this->suffix}
        EOD;
    }
}
