<?php

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
    protected const MAX_DEPTH = 128;

    /** @var Delimiter[] */
    private array $delimiterToken = [];

    /** @var Block[] */
    private array $blockToken = [];

    // config flags
    private int $maxDepth = self::MAX_DEPTH;
    private bool $disableAutoTrim = false;

    /**
     * Tokenizer constructor.
     * @param Delimiter[] $delimiterToken
     * @param Block[] $blockToken
     * @param array $config
     * @throws UnexpectedValueException
     */
    public function __construct(array $delimiterToken, array $blockToken, array $config = [])
    {
        // read type-safe config entries
        $this->parseConfig($config);

        $takenSymbols = [];

        foreach ($delimiterToken as $delimiter) {
            if (in_array($delimiter->symbol(), $takenSymbols, true)) {
                throw new UnexpectedValueException("Found duplicated symbol in Tokenizer for '{$delimiter->symbol()}' Delimiter.", 500);
            }
            $takenSymbols[] = $delimiter->symbol();
            $this->delimiterToken[] = $delimiter;
        }

        // sort delimiters by longest first, this assures,
        // that multichar delimiters can match before a similar singlechar delimiter
        // is check which could also match
        usort($this->delimiterToken, function (Delimiter $lhs, Delimiter $rhs): int {
            return $rhs->length() - $lhs->length();
        });

        foreach ($blockToken as $block) {
            if (in_array($block->open()->symbol(), $takenSymbols, true)) {
                throw new UnexpectedValueException("Found duplicated symbol in Tokenizer for '{$block->symbolOpen()}' Block open-symbol.", 500);
            }
            if (in_array($block->close()->symbol(), $takenSymbols, true)) {
                throw new UnexpectedValueException("Found duplicated symbol in Tokenizer for '{$block->symbolClose()}' Block close-symbol.", 500);
            }

            $takenSymbols[] = $block->open()->symbol();
            if ($block->open() !== $block->close()) {
                $takenSymbols[] = $block->close()->symbol();
            }
            $this->blockToken[] = $block;
        }

        usort($this->blockToken, function (Block $lhs, Block $rhs): int {
            return $rhs->open()->length() - $lhs->open()->length();
        });
    }

    private function parseConfig(array $config): void
    {
        if (array_key_exists('maxDepth', $config) && is_int($config['maxDepth'])) {
            $this->maxDepth = $config['maxDepth'];
        }
        if (array_key_exists('disableAutoTrim', $config) && is_bool($config['disableAutoTrim'])) {
            $this->disableAutoTrim = $config['disableAutoTrim'];
        }
    }

    /**
     * @param string $input
     * @param int $startLine
     * @return TokenStream
     */
    public function tokenize(string $input, int $startLine = 1): TokenStream
    {
        return new TokenStream($this->process($input, 0, $startLine));
    }

    /**
     * splits given string into blocks and symbols
     * @param string $input
     * @param int $depth
     * @param int $line
     * @return BlockToken[]|Token[]
     */
    private function process(string $input, int $depth, int $line): array
    {
        // abort tokenizing after reaching the max block depth
        // just return the input string as the remaining symbol
        if ($this->maxDepth > 0 && $depth >= $this->maxDepth) {
            return [new Token($input, null, $line)];
        }

        /** @var BlockToken[]|Token[] $result */
        $result = [];

        /** @var array|null $openBlocks 'block' => BlockToken, 'startOffset' => int */
        $openBlocks = [];

        /** @var BaseToken|null $lastSymbol */
        $lastSymbol = null;

        /** @var Delimiter|null $lastDelimiter */
        $lastDelimiter = null;

        /** @var string $lastChar single char from previous iteration */
        $lastChar = '';

        $lastOffset = 0;
        $remaining = $input;

        foreach (str_split($input) as $offset => $char) {
            if ($lastChar === PHP_EOL) {
                $line += 1;
            }
            $lastChar = $char;

            // fast forward for match multi-char delimiters
            if ($lastOffset > $offset) {
                continue;
            }

            /** @var BlockToken|null $lastOpenBlock */
            $lastOpenBlock = null;
            if (false !== $lastOpen = end($openBlocks)) {
                $lastOpenBlock = $lastOpen['block'];
            }

            if ($lastOpenBlock !== null) {

                if ($lastOpenBlock->block()->close()->symbol() === substr($input, $offset, $lastOpenBlock->block()->close()->length())) {

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
                            $result[] = $lastSymbol;
                        } else {

                            // keep the whole block content-untouched
                            $lastSymbol = $resultBlock->withSymbols([
                                new Token($blockContent, null, $line)
                            ]);
                            $result[] = $lastSymbol;
                        }
                    }

                    continue;
                }

                if (!$lastOpenBlock->block()->shouldTokenizeContent()) {
                    continue;
                }
            }

            // scan for block-open token
            foreach ($this->blockToken as $block) {

                if ($block->open()->symbol() === substr($input, $offset, $block->open()->length())) {

                    $resultBlock = new BlockToken($block, $lastDelimiter, $line);
                    if ($lastOffset < $offset) {

                        $prefix = substr($input, $lastOffset, $offset - $lastOffset);
                        if (!$this->disableAutoTrim) {
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

                    $openBlocks[] = ['block' => $resultBlock, 'startOffset' => ($offset + $block->open()->length())];
                    continue 2;
                }
            }

            // scanning for delimiters inside blocks is not required, since it's done inside
            // sub- process() calls, where the currently open block is the main block
            if (!empty($openBlocks)) {
                continue;
            }

            foreach ($this->delimiterToken as $delimiter) {
                if (substr($input, $offset, $delimiter->length()) === $delimiter->symbol()) {

                    if ($lastOffset < $offset) {

                        // only keep symbol, if it's not already processed before
                        // e.g. if the previous symbol was a block
                        $content = substr($input, $lastOffset, $offset - $lastOffset);
                        if (!$this->disableAutoTrim) {
                            $content = rtrim($content);
                        }

                        // encounter of symbol directly after an block (no delimiter in between)
                        if ($lastSymbol instanceof BlockToken) {

                            if (!empty($content)) {

                                if ($lastSymbol->block()->splitAffixIntoSymbols()) {
                                    $result[] = new Token($content, null, $line);
                                } else {
                                    $lastSymbol->withSuffix($content);
                                }

                            }

                            // we need to reset the last-symbol, since we processed the
                            // current symbol as an block-suffix
                            $lastSymbol = null;
                        } else {
                            $lastSymbol = new Token($content, $lastDelimiter, $line);
                            $result[] = $lastSymbol;
                        }

                    } else {

                        // set last-symbol since we encountered an delimiter, but
                        // because we can skip the symbol, we set last-symbol to null
                        $lastSymbol = null;

                    }

                    $remaining = substr($input, $offset + $delimiter->length());
                    $lastOffset = $offset + $delimiter->length();
                    $currentDelimiter = $delimiter;
                    $lastDelimiter = $currentDelimiter;
                    continue 2;
                }
            }
        }

        // handle remaining tokens
        if (!$this->disableAutoTrim) {
            $remaining = ltrim($remaining, ' ');
        }
        if (strlen($remaining) > 0) {
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

}
