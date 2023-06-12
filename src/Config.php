<?php

declare(strict_types=1);

namespace ricwein\Tokenizer;

/**
 * @author    richard.weinhold
 * @since     09.06.2023
 */
class Config
{
    protected const MAX_DEPTH = 128;

    public function __construct(
        public readonly int         $maxDepth = self::MAX_DEPTH,
        public readonly bool        $disableAutoTrim = false,
        public readonly null|string $escapeSymbol = null,
    )
    {
    }
}