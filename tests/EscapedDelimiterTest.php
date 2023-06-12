<?php

declare(strict_types=1);

namespace ricwein\Tokenizer\Tests;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ricwein\Tokenizer\InputSymbols\Block;
use ricwein\Tokenizer\InputSymbols\Delimiter;
use ricwein\Tokenizer\Result\BlockToken;
use ricwein\Tokenizer\Result\Token;
use ricwein\Tokenizer\Result\TokenStream;
use ricwein\Tokenizer\Tokenizer;

class EscapedDelimiterTest extends TestCase
{
    public static function generateEscapedDelimiterTests(): Generator
    {
        $delimiter = new Delimiter('|', escapeSymbol: '\\');
        $tokenizer = new Tokenizer([$delimiter], []);

        yield 'simple' => [$tokenizer, 'this|is\|an|test', [
            new Token("this", null),
            new Token("is|an", $delimiter),
            new Token("test", $delimiter),
        ]];

        yield 'multi-escaped' => [$tokenizer, 'this|is\|an|test\|or\\\\|is|it', [
            new Token("this", null),
            new Token("is|an", $delimiter),
            new Token("test|or\|is", $delimiter),
            new Token("it", $delimiter),
        ]];
    }

    #[DataProvider('generateEscapedDelimiterTests')]
    public function testDelimiterEscape(Tokenizer $tokenizer, string $input, array $tokens): void
    {
        self::assertEquals(new TokenStream($tokens), $tokenizer->tokenize($input));
    }

    public static function generateEscapedBlockTests(): Generator
    {
        $delimiter = new Delimiter(' ');
        $quotedBlock = new Block('"', shouldTokenizeContent: true, escapeSymbol: '\\');
        $bracketBlock = new Block('(', symbolClose: ')', shouldTokenizeContent: true, escapeSymbol: '\\');
        $tokenizer = new Tokenizer([$delimiter], [$quotedBlock, $bracketBlock]);

        yield 'quoted' => [$tokenizer, '"this is \"an test\""', [
            (new BlockToken($quotedBlock, null))->withSymbols([
                new Token('this', null),
                new Token('is', $delimiter),
                (new BlockToken($quotedBlock, $delimiter))->withSymbols([
                    new Token('an', null),
                    new Token('test', $delimiter),
                ]),
            ]),
        ]];

        yield 'brackets' => [$tokenizer, '(this is \(an test\))', [
            (new BlockToken($bracketBlock, null))->withSymbols([
                new Token('this', null),
                new Token('is', $delimiter),
                (new BlockToken($bracketBlock, $delimiter))->withSymbols([
                    new Token('an', null),
                    new Token('test', $delimiter),
                ]),
            ]),
        ]];

//        yield 'multi-escaped brackets' => [$tokenizer, '(this is \(an\\\\( test\))', [
//            (new BlockToken($bracketBlock, null))->withSymbols([
//                new Token('this', null),
//                new Token('is', $delimiter),
//                (new BlockToken($bracketBlock, $delimiter))->withSymbols([
//                    new Token('an(', null),
//                    new Token('test', $delimiter),
//                ]),
//            ]),
//        ]];
    }

    #[DataProvider('generateEscapedBlockTests')]
    public function testBlockDelimiterEscape(Tokenizer $tokenizer, string $input, array $tokens): void
    {
        self::assertEquals(new TokenStream($tokens), $tokenizer->tokenize($input));
    }
}