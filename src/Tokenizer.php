<?php

namespace ricwein\Tokenizer;

use ricwein\Tokenizer\InputSymbols\Block;
use ricwein\Tokenizer\InputSymbols\Delimiter;
use ricwein\Tokenizer\Result\Result;
use ricwein\Tokenizer\Result\ResultBlock;
use ricwein\Tokenizer\Result\ResultSymbol;
use UnexpectedValueException;

class Tokenizer
{
    protected const MAX_DEPTH = 128;

    /** @var Delimiter[] */
    private array $delimiterToken = [];

    /** @var Block[] */
    private array $blockToken = [];

    private int $maxDepth;

    /**
     * Tokenizer constructor.
     * @param Delimiter[] $delimiterToken
     * @param Block[] $blockToken
     * @param int $maxDepth
     * @throws UnexpectedValueException
     */
    public function __construct(array $delimiterToken, array $blockToken, int $maxDepth = self::MAX_DEPTH)
    {
        $this->maxDepth = $maxDepth;
        $takenSymbols = [];

        foreach ($delimiterToken as $delimiter) {
            if (in_array($delimiter->symbol(), $takenSymbols, true)) {
                throw new UnexpectedValueException("Found duplicated symbol in Tokenizer for '{$delimiter->symbol()}' Delimiter.", 500);
            }
            $takenSymbols[] = $delimiter->symbol();
            $this->delimiterToken[] = $delimiter;
        }

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
    }

    /**
     * @param string $input
     * @return Result
     */
    public function tokenize(string $input): Result
    {
        return new Result($this->process($input, 0));
    }

    /**
     * splits given string into blocks and symbols
     * @param string $input
     * @param int $depth
     * @return ResultBlock[]|ResultSymbol[]
     */
    private function process(string $input, int $depth): array
    {
        // abort tokenizing after reaching the max block depth
        // just return the input string as the remaining symbol
        if ($depth > $this->maxDepth) {
            return [new ResultSymbol(trim($input), null)];
        }

        /** @var ResultBlock[]|ResultSymbol[] $result */
        $result = [];

        /** @var [ResultBlock, int]|null $openBlocks */
        $openBlocks = [];

        $lastSymbol = null;

        /** @var Delimiter|null $lastDelimiter */
        $lastDelimiter = null;

        $lastOffset = 0;
        $remaining = $input;

        foreach (str_split($input) as $offset => $char) {

            $lastOpenBlock = end($openBlocks);
            if (false !== $lastOpenBlock && $lastOpenBlock['block']->block()->close()->symbol() === substr($input, $offset, $lastOpenBlock['block']->block()->close()->length())) {

                // remove current block from list of open blocks
                $lastResultBlock = array_pop($openBlocks);

                /** @var ResultBlock $resultBlock */
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
                        $blockSymbols = $this->process($blockContent, $depth + 1);
                        $lastSymbol = $resultBlock->withSymbols($blockSymbols);
                        $result[] = $lastSymbol;
                    } else {

                        // keep the whole block content-untouched
                        $lastSymbol = $resultBlock->withSymbols([
                            new ResultSymbol(trim($blockContent), null)
                        ]);
                        $result[] = $lastSymbol;
                    }
                }

                continue;
            }

            foreach ($this->blockToken as $block) {

                if ($block->open()->symbol() === substr($input, $offset, $block->open()->length())) {

                    $resultBlock = new ResultBlock($block, $lastDelimiter);
                    if ($lastOffset < $offset) {
                        $resultBlock->withPrefix(trim(substr($input, $lastOffset, $offset - $lastOffset)));
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
                        $content = trim(substr($input, $lastOffset, $offset - $lastOffset));

                        // encounter of symbol directly after an block (no delimiter in between)
                        if ($lastSymbol instanceof ResultBlock) {
                            $lastSymbol->withSuffix($content);

                            // we need to reset the last-symbol, since we processed the
                            // current symbol as an block-suffix
                            $lastSymbol = null;
                        } else {
                            $lastSymbol = new ResultSymbol($content, $lastDelimiter);;
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

        $remaining = trim($remaining, ' ');
        if (strlen($remaining) > 0) {
            if ($lastSymbol instanceof ResultBlock) {
                $lastSymbol->withSuffix($remaining);
            } else {
                $result[] = new ResultSymbol($remaining, $lastDelimiter);
            }
        }

        return $result;
    }

}
