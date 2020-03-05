<?php

namespace ricwein\Tokenizer\Result;

use ricwein\Tokenizer\InputSymbols\Delimiter;

class ResultSymbol extends ResultSymbolBase
{
    private string $symbol;

    /**
     * ResultSymbol constructor.
     * @param string $symbol
     * @param Delimiter|null $delimiter
     */
    public function __construct(string $symbol, ?Delimiter $delimiter)
    {
        $this->symbol = $symbol;
        $this->delimiter = $delimiter;
    }

    /**
     * @return string
     */
    public function symbol(): string
    {
        return $this->symbol;
    }

    public function asGuessedType()
    {
        switch (true) {
            case in_array($this->symbol, ['true', 'TRUE'], true):
                return true;
            case in_array($this->symbol, ['false', 'FALSE'], true):
                return false;
            case in_array($this->symbol, ['null', 'NULL'], true):
                return null;

            case is_numeric($this->symbol) && strlen($this->symbol) === strlen((string)(int)$this->symbol):
                return (int)$this->symbol;
            case is_numeric($this->symbol):
                return (float)$this->symbol;
        }

        return $this->symbol;
    }

    /**
     * rebuilds input-string from tokenized symbols
     * @return string
     */
    public function __toString(): string
    {
        return trim(sprintf("%s%s", $this->delimiter ?? '', $this->symbol));
    }

}
