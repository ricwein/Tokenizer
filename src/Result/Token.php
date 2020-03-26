<?php

namespace ricwein\Tokenizer\Result;

use ricwein\Tokenizer\InputSymbols\Delimiter;

class Token extends BaseToken
{
    private string $token;

    /**
     * ResultSymbol constructor.
     * @param string $token
     * @param Delimiter|null $delimiter
     * @param int $line
     */
    public function __construct(string $token, ?Delimiter $delimiter, int $line = 1)
    {
        $this->token = $token;
        $this->delimiter = $delimiter;
        $this->line = $line;
    }

    /**
     * @return string
     */
    public function token(): string
    {
        return $this->token;
    }

    public function content(): string
    {
        return $this->token;
    }

    public function asGuessedType()
    {
        switch (true) {
            case in_array($this->token, ['true', 'TRUE'], true):
                return true;
            case in_array($this->token, ['false', 'FALSE'], true):
                return false;
            case in_array($this->token, ['null', 'NULL'], true):
                return null;

            case is_numeric($this->token) && strlen($this->token) === strlen((string)(int)$this->token):
                return (int)$this->token;
            case is_numeric($this->token):
                return (float)$this->token;
        }

        return $this->token;
    }

    /**
     * rebuilds input-string from tokenized symbols
     * @return string
     */
    public function __toString(): string
    {
        return implode('', [
            $this->delimiter ?? '',
            $this->content(),
        ]);
    }

}
