<?php declare(strict_types=1);

namespace units;

use PHPUnit\Framework\TestCase;
use ricwein\Tokenizer\InputSymbols\Block;
use ricwein\Tokenizer\InputSymbols\Delimiter;
use ricwein\Tokenizer\Result\Result;
use ricwein\Tokenizer\Result\ResultBlock;
use ricwein\Tokenizer\Result\ResultSymbol;
use ricwein\Tokenizer\Tokenizer;

class TokenizerTest extends TestCase
{
    protected Tokenizer $tokenizer;

    protected function setUp(): void
    {
        parent::setUp();

        $delimiter = [new Delimiter('.'), new Delimiter('|'), new Delimiter(',')];
        $blocks = [
            new Block('[', ']', true),
            new Block('(', ')', true),
            new Block('{', '}', false),
            new Block('\'', null, false),
            new Block('"', null, false),
        ];

        $this->tokenizer = new Tokenizer($delimiter, $blocks);
    }

    public function testSimpleDelimiter()
    {
        $testString = 'test.123';
        $expected = [new ResultSymbol('test', null), new ResultSymbol('123', new Delimiter('.'))];
        $this->assertEquals(new Result($expected), $this->tokenizer->tokenize($testString));

        $testString = 'test-123';
        $expected = [new ResultSymbol('test-123', null)];
        $this->assertEquals(new Result($expected), $this->tokenizer->tokenize($testString));

        $testString = 'test.123 | something';
        $expected = [new ResultSymbol('test', null), new ResultSymbol('123', new Delimiter('.')), new ResultSymbol('something', new Delimiter('|'))];
        $this->assertEquals(new Result($expected), $this->tokenizer->tokenize($testString));

        $testString = 'really.long.test.something.last';
        $expected = [
            new ResultSymbol('really', null),
            new ResultSymbol('long', new Delimiter('.')),
            new ResultSymbol('test', new Delimiter('.')),
            new ResultSymbol('something', new Delimiter('.')),
            new ResultSymbol('last', new Delimiter('.'))
        ];
        $this->assertEquals(new Result($expected), $this->tokenizer->tokenize($testString));
    }

    public function testSimpleBlocks()
    {
        $testString = '[test]';
        $expected = [
            (new ResultBlock(new Block('[', ']', true), null))->withSymbols([
                new ResultSymbol('test', null)
            ])
        ];
        $this->assertEquals(new Result($expected), $this->tokenizer->tokenize($testString));

        $testString = '[(test)]';
        $expected = [
            (new ResultBlock(new Block('[', ']', true), null))->withSymbols([
                (new ResultBlock(new Block('(', ')', true), null))->withSymbols([
                    new ResultSymbol('test', null)
                ])
            ])
        ];
        $this->assertEquals(new Result($expected), $this->tokenizer->tokenize($testString));

        $testString = '[("test.123")]';
        $expected = [
            (new ResultBlock(new Block('[', ']', true), null))->withSymbols([
                (new ResultBlock(new Block('(', ')', true), null))->withSymbols([
                    (new ResultBlock(new Block('"', '"', false), null))->withSymbols([
                        new ResultSymbol('test.123', null)
                    ])
                ])
            ])
        ];
        $this->assertEquals(new Result($expected), $this->tokenizer->tokenize($testString));
    }

    public function testBlockSymbols()
    {
        $testString = '[test.123]';
        $expected = [
            (new ResultBlock(new Block('[', ']', true), null))->withSymbols([
                new ResultSymbol('test', null), new ResultSymbol('123', new Delimiter('.'))
            ])
        ];
        $this->assertEquals(new Result($expected), $this->tokenizer->tokenize($testString));

        $testString = '[(test).123]';
        $expected = [
            (new ResultBlock(new Block('[', ']', true), null))->withSymbols([
                (new ResultBlock(new Block('(', ')', true), null))->withSymbols([
                    new ResultSymbol('test', null)
                ]),
                new ResultSymbol('123', new Delimiter('.'))
            ])
        ];
        $this->assertEquals(new Result($expected), $this->tokenizer->tokenize($testString));

        $testString = '["(test)".123]';
        $expected = [
            (new ResultBlock(new Block('[', ']', true), null))->withSymbols([
                (new ResultBlock(new Block('"', '"', false), null))->withSymbols([
                    new ResultSymbol('(test)', null),
                ]),
                new ResultSymbol('123', new Delimiter('.'))
            ])
        ];
        $this->assertEquals(new Result($expected), $this->tokenizer->tokenize($testString));
    }

    public function testBlockPrefix()
    {
        $testString = 'functionCall()';
        $expected = [
            (new ResultBlock(new Block('(', ')', true), null))->withPrefix('functionCall'),
        ];
        $this->assertEquals(new Result($expected), $this->tokenizer->tokenize($testString));

        $testString = 'really.functionCall().last';
        $expected = [
            new ResultSymbol('really', null),
            (new ResultBlock(new Block('(', ')', true), new Delimiter('.')))->withPrefix('functionCall'),
            new ResultSymbol('last', new Delimiter('.'))
        ];
        $this->assertEquals(new Result($expected), $this->tokenizer->tokenize($testString));
    }

    public function testBlockSuffix()
    {
        $testString = 'really.()test';
        $expected = [
            new ResultSymbol('really', null),
            (new ResultBlock(new Block('(', ')', true), new Delimiter('.')))->withSuffix('test'),
        ];
        $this->assertEquals(new Result($expected), $this->tokenizer->tokenize($testString));

        $testString = '()test';
        $expected = [
            (new ResultBlock(new Block('(', ')', true), null))->withSuffix('test'),
        ];
        $this->assertEquals(new Result($expected), $this->tokenizer->tokenize($testString));

        $testString = 'really.()test.last';
        $expected = [
            new ResultSymbol('really', null),
            (new ResultBlock(new Block('(', ')', true), new Delimiter('.')))->withSuffix('test'),
            new ResultSymbol('last', new Delimiter('.')),

        ];
        $this->assertEquals(new Result($expected), $this->tokenizer->tokenize($testString));
    }

    public function testNestedBlocks()
    {
        $testString = 'var.functionCall(test).last';
        $expected = [
            new ResultSymbol('var', null),
            (new ResultBlock(new Block('(', ')', true), new Delimiter('.')))->withPrefix('functionCall')->withSymbols([
                new ResultSymbol('test', null),
            ]),
            new ResultSymbol('last', new Delimiter('.'))
        ];
        $this->assertEquals(new Result($expected), $this->tokenizer->tokenize($testString));

    }

    public function testIntegration()
    {
        $testString = 'var.functionCall([test, "another"] | first()).0';
        $expected = [
            new ResultSymbol('var', null),
            (new ResultBlock(new Block('(', ')', true), new Delimiter('.')))->withPrefix('functionCall')->withSymbols([
                (new ResultBlock(new Block('[', ']', true), null))->withSymbols([
                    new ResultSymbol('test', null),
                    (new ResultBlock(new Block('"', '"', false), new Delimiter(',')))->withSymbols([
                        new ResultSymbol('another', null),
                    ]),
                ]),
                (new ResultBlock(new Block('(', ')', true), new Delimiter('|')))->withPrefix('first'),
            ]),
            new ResultSymbol('0', new Delimiter('.'))
        ];
        $this->assertEquals(new Result($expected), $this->tokenizer->tokenize($testString));
    }

    public function testRestructuring()
    {
        $testStrings = [
            'test',
            '"test2"',
            '[(test3)]',
            '[("test[4]")]',
            'functionCall()',
            'really.long.test.something.last',
            'really.()test.last',
            'var.functionCall(test).last',
            'var.functionCall([test, "another"] | first()).0',
        ];

        foreach ($testStrings as $testString) {
            $tokenized = $this->tokenizer->tokenize($testString);
            $reStructuredString = (string)$tokenized;
            $this->assertSame(str_replace(' ', '', $testString), $reStructuredString);

            $reTokenized = $this->tokenizer->tokenize($reStructuredString);
            $this->assertEquals($tokenized, $reTokenized);
        }
    }

    public function testDelimiterBlockMatchingPriority()
    {
        $testString = "['key_test', ['value']]";
        $expected = [
            (new ResultBlock(new Block('[', ']', true), null))->withSymbols([
                (new ResultBlock(new Block('\'', '\'', false), null))->withSymbols([
                    new ResultSymbol('key_test', null),
                ]),
                (new ResultBlock(new Block('[', ']', true), new Delimiter(',')))->withSymbols([
                    (new ResultBlock(new Block('\'', '\'', false), null))->withSymbols([
                        new ResultSymbol('value', null),
                    ]),
                ]),
            ]),
        ];

        $this->assertEquals(new Result($expected), $this->tokenizer->tokenize($testString));
    }

    public function testNestingMaxDepthLimit()
    {
        $testString = "['key_test', ['value']]";

        // custom tokenizer with small depth-limit:
        $delimiter = [new Delimiter(',')];
        $blocks = [new Block('[', ']', true), new Block('\'', null, false),];
        $limitedTokenizer = new Tokenizer($delimiter, $blocks, 1);

        $expected = [
            (new ResultBlock(new Block('[', ']', true), null))->withSymbols([
                new ResultSymbol("'key_test', ['value']", null),
            ]),
        ];

        $this->assertEquals(new Result($expected), $limitedTokenizer->tokenize($testString));
    }

}
