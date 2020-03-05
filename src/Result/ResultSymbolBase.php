<?php

namespace ricwein\Tokenizer\Result;

use ricwein\Tokenizer\InputSymbols\Delimiter;

abstract class ResultSymbolBase
{
    protected ?Delimiter $delimiter;

    abstract public function __toString(): string;

    /**
     * @return Delimiter|null
     */
    public function delimiter(): ?Delimiter
    {
        return $this->delimiter;
    }

}
