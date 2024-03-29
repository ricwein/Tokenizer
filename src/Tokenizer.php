<?php

declare(strict_types=1);

namespace ricwein\Tokenizer;

use ricwein\Tokenizer\InputSymbols\Block;
use ricwein\Tokenizer\InputSymbols\Delimiter;
use ricwein\Tokenizer\Result\BaseToken;
use ricwein\Tokenizer\Result\TokenStream;
use ricwein\Tokenizer\Result\BlockToken;
use ricwein\Tokenizer\Result\Token;
use UnexpectedValueException;

class Tokenizer
{
    /** @var Delimiter[] */
    private array $delimiterToken = [];

    /** @var Block[] */
    private array $blockToken = [];

    /**
     * @param Delimiter[] $delimiterToken
     * @param Block[] $blockToken
     * @throws UnexpectedValueException
     */
    public function __construct(
        array                   $delimiterToken,
        array                   $blockToken,
        private readonly Config $config = new Config()
    )
    {
        $takenSymbols = [];

        foreach ($delimiterToken as $delimiter) {
            if (in_array($delimiter->symbol(), $takenSymbols, true)) {
                throw new UnexpectedValueException("Found duplicated symbol in Tokenizer for '{$delimiter->symbol()}' Delimiter.", 500);
            }

            if ($this->config->escapeSymbol !== null && $delimiter->getEscapeSymbol() === null) {
                $delimiter->setEscapeSymbol($this->config->escapeSymbol);
            }

            $takenSymbols[] = $delimiter->symbol();
            $this->delimiterToken[] = $delimiter;
        }

        /**
         * Sort delimiters by longest first, this assures that multichar delimiters can match
         * before a similar single-char delimiter is checked (which could also match)
         */
        usort(
            $this->delimiterToken,
            static fn(Delimiter $lhs, Delimiter $rhs): int => $rhs->length() - $lhs->length()
        );

        foreach ($blockToken as $block) {
            if (in_array($block->open()->symbol(), $takenSymbols, true)) {
                throw new UnexpectedValueException("Found duplicated symbol in Tokenizer for '{$block->open()->symbol()}' Block open-symbol.", 500);
            }
            if (in_array($block->close()->symbol(), $takenSymbols, true)) {
                throw new UnexpectedValueException("Found duplicated symbol in Tokenizer for '{$block->close()->symbol()}' Block close-symbol.", 500);
            }

            if ($this->config->escapeSymbol !== null) {
                if ($block->open()->getEscapeSymbol() === null) {
                    $block->open()->setEscapeSymbol($this->config->escapeSymbol);
                }
                if ($block->close()->getEscapeSymbol() === null) {
                    $block->close()->setEscapeSymbol($this->config->escapeSymbol);
                }
            }

            $takenSymbols[] = $block->open()->symbol();
            if ($block->open()->symbol() !== $block->close()->symbol()) {
                $takenSymbols[] = $block->close()->symbol();
            }
            $this->blockToken[] = $block;
        }

        usort(
            $this->blockToken,
            static fn(Block $lhs, Block $rhs): int => $rhs->open()->length() - $lhs->open()->length()
        );
    }

    public function tokenize(string $input, int $startLine = 1): TokenStream
    {
        return new TokenStream($this->process($input, 0, $startLine));
    }

    /**
     * splits given string into blocks and symbols
     * @return BlockToken[]|Token[]
     */
    private function process(string $input, int $depth, int $line): array
    {
        // abort tokenizing after reaching the max block depth
        // just return the input string as the remaining symbol
        if ($this->config->maxDepth > 0 && $depth >= $this->config->maxDepth) {
            return [new Token($input, null, $line)];
        }

        /** @var BlockToken[]|Token[] $result */
        $result = [];

        /** @var array<array{block: BlockToken, startOffset: int}>|null $openBlocks 'block' => BlockToken, 'startOffset' => int */
        $openBlocks = [];

        /** @var BaseToken|null $lastSymbol */
        $lastSymbol = null;

        /** @var Delimiter|null $lastDelimiter */
        $lastDelimiter = null;

        /** @var array<Block|Delimiter> $escapedSymbols */
        $escapedSymbols = [];

        /** single char from previous iteration */
        $lastChar = '';

        $lastOffset = 0;
        $remaining = $input;

        foreach (str_split($input) as $offset => $char) {
            if ($lastChar === PHP_EOL) {
                ++$line;
            }
            $lastChar = $char;

            // fast-forward for matched multi-char delimiters
            if ($lastOffset > $offset) {
                continue;
            }

            /** @var BlockToken|null $lastOpenBlock */
            $lastOpenBlock = null;
            if (false !== $lastOpen = end($openBlocks)) {
                $lastOpenBlock = $lastOpen['block'];
            }

            if ($lastOpenBlock !== null) {
                $blockClosed = $this->checkAndProcessBlockCloseToken(
                    $input,
                    $offset,
                    $lastOffset,
                    $depth,
                    $line,
                    $lastOpenBlock,
                    $lastSymbol,
                    $escapedSymbols,
                    $openBlocks,
                    $remaining,
                    $result
                );
                if ($blockClosed) {
                    continue;
                }
            }

            // scan for block-open token
            $blockOpened = $this->checkAndProcessBlockOpenTokens(
                $input,
                $offset,
                $lastOffset,
                $line,
                $lastDelimiter,
                $lastSymbol,
                $escapedSymbols,
                $openBlocks,
                $result
            );
            if ($blockOpened) {
                continue;
            }

            /**
             * scanning for delimiters inside blocks is not required, since it's done inside
             * sub-{@see self::process() process()} calls, where the currently open block is the main block
             */
            if (!empty($openBlocks)) {
                continue;
            }

            $this->checkAndProcessDelimiterTokens(
                $input,
                $offset,
                $lastOffset,
                $line,
                $lastDelimiter,
                $lastSymbol,
                $escapedSymbols,
                $remaining,
                $result
            );
        }

        // handle remaining tokens
        if (!$this->config->disableAutoTrim) {
            $remaining = ltrim($remaining, ' ');
        }

        if ($remaining !== '') {
            if ($lastSymbol instanceof BlockToken) {

                if ($lastSymbol->block()->splitAffixIntoSymbols()) {
                    $result[] = new Token($remaining, null, $line);
                } else {
                    $lastSymbol->withSuffix($remaining);
                }

            } else {
                $result[] = new Token($remaining, $lastDelimiter, $line);
            }
        }
        return $result;
    }

    /**
     * @param array<Block|Delimiter> $escapedSymbols
     * @param array<array{block: BlockToken, startOffset: int}>|null $openBlocks
     * @param BlockToken[]|Token[] $result
     */
    private function checkAndProcessBlockOpenTokens(
        string         &$input,
        int            $offset,
        int            $lastOffset,
        int            $line,
        null|Delimiter &$lastDelimiter,
        null|BaseToken &$lastSymbol,
        array          &$escapedSymbols,
        array          &$openBlocks,
        array          &$result
    ): bool
    {
        foreach ($this->blockToken as $block) {
            $openBlockDelimiter = $block->open();

            $foundEscapedDelimiter = self::checkAndProcessEscapedDelimiter($input, $offset, $escapedSymbols, $block, $openBlockDelimiter);
            if ($foundEscapedDelimiter === true) {
                return true;
            }

            if ($openBlockDelimiter->symbol() !== substr($input, $offset, $openBlockDelimiter->length())) {
                continue;
            }

            $resultBlock = new BlockToken($block, $lastDelimiter, $line);
            if ($lastOffset < $offset) {

                $prefix = substr($input, $lastOffset, $offset - $lastOffset);
                if (!$this->config->disableAutoTrim) {
                    $prefix = trim($prefix);
                }
                if (!empty($prefix)) {
                    if ($block->splitAffixIntoSymbols()) {
                        $lastSymbol = new Token($prefix, $lastDelimiter, $line);

                        $resultBlock->setDelimiter(null);
                        $lastDelimiter = null;

                        $result[] = $lastSymbol;
                    } else {
                        $resultBlock->withPrefix($prefix);
                    }
                }
            }

            $openBlocks[] = ['block' => $resultBlock, 'startOffset' => ($offset + $openBlockDelimiter->length())];
            return true;
        }

        return false;
    }

    /**
     * @param array<Block|Delimiter> $escapedSymbols
     * @param array<array{block: BlockToken, startOffset: int}>|null $openBlocks
     * @param BlockToken[]|Token[] $result
     */
    private function checkAndProcessBlockCloseToken(
        string         &$input,
        int            $offset,
        int            &$lastOffset,
        int            $depth,
        int            $line,
        BlockToken     $lastOpenBlock,
        null|BaseToken &$lastSymbol,
        array          &$escapedSymbols,
        array          &$openBlocks,
        string         &$remaining,
        array          &$result,
    ): bool
    {
        $block = $lastOpenBlock->block();
        $closeBlockDelimiter = $block->close();

        $foundEscapedDelimiter = self::checkAndProcessEscapedDelimiter($input, $offset, $escapedSymbols, $block, $closeBlockDelimiter);
        if ($foundEscapedDelimiter === true) {
            return true;
        }

        if ($closeBlockDelimiter->symbol() === substr($input, $offset, $closeBlockDelimiter->length())) {

            // remove current block from list of open blocks
            $lastResultBlock = array_pop($openBlocks);

            /** @var BlockToken $resultBlock */
            $resultBlock = $lastResultBlock['block'];
            $blockStartOffset = $lastResultBlock['startOffset'];

            $remaining = substr($input, $offset + $resultBlock->block()->close()->length());
            $lastOffset = $offset + $resultBlock->block()->close()->length();

            // we only want to process the current block, if it's the root-block
            // otherwise the block is handled inside the next sub-process() call
            if (count($openBlocks) <= 0) {
                $blockContent = substr($input, $blockStartOffset, $offset - $blockStartOffset);

                if ($resultBlock->block()->shouldTokenizeContent()) {
                    // insert block with sub-symbols as a new node to our result-tree
                    $blockSymbols = $this->process($blockContent, $depth + 1, $line);
                    $lastSymbol = $resultBlock->withSymbols($blockSymbols);
                } else {
                    // keep the whole block content-untouched
                    $lastSymbol = $resultBlock->withSymbols([
                        new Token($blockContent, null, $line)
                    ]);
                }

                $result[] = $lastSymbol;
            }

            return true;
        }

        if (!$block->shouldTokenizeContent()) {
            return true;
        }

        return false;
    }

    /**
     * @param array<Block|Delimiter> $escapedSymbols
     * @param BlockToken[]|Token[] $result
     */
    private function checkAndProcessDelimiterTokens(
        string         &$input,
        int            $offset,
        int            &$lastOffset,
        int            $line,
        null|Delimiter &$lastDelimiter,
        null|BaseToken &$lastSymbol,
        array          &$escapedSymbols,
        string         &$remaining,
        array          &$result
    ): void
    {
        foreach ($this->delimiterToken as $delimiter) {
            $foundEscapedDelimiter = self::checkAndProcessEscapedDelimiter($input, $offset, $escapedSymbols, $delimiter, $delimiter);
            if ($foundEscapedDelimiter === true) {
                return;
            }

            if (substr($input, $offset, $delimiter->length()) !== $delimiter->symbol()) {
                continue;
            }

            if ($lastOffset < $offset) {

                // only keep symbol, if it's not already processed before
                // e.g. if the previous symbol was a block
                $content = substr($input, $lastOffset, $offset - $lastOffset);

                if (!$this->config->disableAutoTrim) {
                    $content = rtrim($content);
                }

                // encounter of symbol directly after a block (no delimiter in between)
                if ($lastSymbol instanceof BlockToken) {

                    if (!empty($content)) {
                        if ($lastSymbol->block()->splitAffixIntoSymbols()) {
                            $result[] = new Token($content, null, $line);
                        } else {
                            $lastSymbol->withSuffix($content);
                        }
                    }

                    // we need to reset the last-symbol, since we processed the
                    // current symbol as a block-suffix
                    $lastSymbol = null;
                } else {
                    $lastSymbol = new Token($content, $lastDelimiter, $line);
                    $result[] = $lastSymbol;
                }

            } else {

                // set last-symbol since we encountered a delimiter, but
                // because we can skip the symbol, we set last-symbol to null
                $lastSymbol = null;

            }

            $remaining = substr($input, $offset + $delimiter->length());
            $lastOffset = $offset + $delimiter->length();
            $lastDelimiter = $delimiter;
            return;
        }
    }

    private static function checkAndProcessEscapedDelimiter(string &$input, int $offset, array &$escapedSymbols, Delimiter|Block $symbol, Delimiter $delimiter): ?bool
    {
        if (null === $escapeSymbol = $delimiter->getEscapeSymbol()) {
            return null;
        }

        if (substr($input, $offset, $escapeSymbol->length()) === $escapeSymbol->symbol()) {
            $escapedSymbols[] = $symbol;
            $input = substr($input, 0, $offset) . $delimiter->symbol() . substr($input, $offset + $escapeSymbol->length());
            return true;
        }

        if (false !== $key = array_search($symbol, $escapedSymbols, true)) {
            unset($escapedSymbols[$key]);
        }

        return false;
    }
}
