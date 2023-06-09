<?php

namespace ricwein\Tokenizer\Result;

use ricwein\Tokenizer\InputSymbols\Delimiter;

class Token extends BaseToken
{
    public function __construct(private readonly string $token, ?Delimiter $delimiter, int $line = 1)
    {
        parent::__construct($delimiter, $line);
    }

    public function token(): string
    {
        return $this->token;
    }

    public function content(): string
    {
        return $this->token;
    }

    public function asGuessedType(): float|bool|int|string|null
    {
        return match (true) {
            in_array($this->token, ['true', 'TRUE'], true) => true,
            in_array($this->token, ['false', 'FALSE'], true) => false,
            in_array($this->token, ['null', 'NULL'], true) => null,
            is_numeric($this->token) && strlen($this->token) === strlen((string)(int)$this->token) => (int)$this->token,
            is_numeric($this->token) => (float)$this->token,
            default => $this->token,
        };

    }

    /**
     * rebuilds input-string from tokenized symbols
     */
    public function __toString(): string
    {
        return implode('', [
            (string)($this->delimiter ?? ''),
            $this->content(),
        ]);
    }

}
