<?php declare(strict_types=1);

namespace ricwein\Tokenizer\Tests;

use PHPUnit\Framework\TestCase;
use ricwein\Tokenizer\Config;
use ricwein\Tokenizer\InputSymbols\Block;
use ricwein\Tokenizer\InputSymbols\Delimiter;
use ricwein\Tokenizer\Result\TokenStream;
use ricwein\Tokenizer\Result\BlockToken;
use ricwein\Tokenizer\Result\Token;
use ricwein\Tokenizer\Tokenizer;

class TokenizerTest extends TestCase
{
    protected Tokenizer $tokenizer;

    protected function setUp(): void
    {
        parent::setUp();

        $delimiter = [new Delimiter('.'), new Delimiter('|'), new Delimiter(',')];
        $blocks = [
            new Block('[', ']', true, false),
            new Block('(', ')', true, false),
            new Block('{', '}', false, false),
            new Block('\'', null, false, false),
            new Block('"', null, false, false),

            new Block('{{', '}}', false, true),
            new Block('{%', '%}', true, true),
        ];

        $this->tokenizer = new Tokenizer($delimiter, $blocks);
    }

    public function testLeadingDelimiter(): void
    {
        $testString = '.123';
        $expected = [new Token('123', new Delimiter('.'))];
        self::assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));
    }

    public function testTrailingDelimiter(): void
    {
        $testString = '123.';
        $expected = [new Token('123', null)];
        self::assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));
    }

    public function testSimpleDelimiter(): void
    {
        $testString = 'test.123';
        $expected = [new Token('test', null), new Token('123', new Delimiter('.'))];
        self::assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));

        $testString = 'test-123';
        $expected = [new Token('test-123', null)];
        self::assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));

        $testString = 'test.123 | something';
        $expected = [new Token('test', null), new Token('123', new Delimiter('.')), new Token('something', new Delimiter('|'))];
        self::assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));

        $testString = 'really.long.test.something.last';
        $expected = [
            new Token('really', null),
            new Token('long', new Delimiter('.')),
            new Token('test', new Delimiter('.')),
            new Token('something', new Delimiter('.')),
            new Token('last', new Delimiter('.'))
        ];
        self::assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));
    }

    public function testSimpleBlocks(): void
    {
        $testString = '[test]';
        $expected = [
            (new BlockToken(new Block('[', ']', true), null))->withSymbols([
                new Token('test', null)
            ])
        ];
        self::assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));

        $testString = '[(test)]';
        $expected = [
            (new BlockToken(new Block('[', ']', true), null))->withSymbols([
                (new BlockToken(new Block('(', ')', true), null))->withSymbols([
                    new Token('test', null)
                ])
            ])
        ];
        self::assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));

        $testString = '[("test.123")]';
        $expected = [
            (new BlockToken(new Block('[', ']', true), null))->withSymbols([
                (new BlockToken(new Block('(', ')', true), null))->withSymbols([
                    (new BlockToken(new Block('"', '"', false), null))->withSymbols([
                        new Token('test.123', null)
                    ])
                ])
            ])
        ];
        self::assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));
    }

    public function testBlockSymbols(): void
    {
        $testString = '[test.123]';
        $expected = [
            (new BlockToken(new Block('[', ']', true), null))->withSymbols([
                new Token('test', null), new Token('123', new Delimiter('.'))
            ])
        ];
        self::assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));

        $testString = '[(test).123]';
        $expected = [
            (new BlockToken(new Block('[', ']', true), null))->withSymbols([
                (new BlockToken(new Block('(', ')', true), null))->withSymbols([
                    new Token('test', null)
                ]),
                new Token('123', new Delimiter('.'))
            ])
        ];
        self::assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));

        $testString = '["(test)".123]';
        $expected = [
            (new BlockToken(new Block('[', ']', true), null))->withSymbols([
                (new BlockToken(new Block('"', '"', false), null))->withSymbols([
                    new Token('(test)', null),
                ]),
                new Token('123', new Delimiter('.'))
            ])
        ];
        self::assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));
    }

    public function testBlockPrefix(): void
    {
        $testString = 'functionCall()';
        $expected = [
            (new BlockToken(new Block('(', ')', true), null))->withPrefix('functionCall'),
        ];
        self::assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));

        $testString = 'really.functionCall().last';
        $expected = [
            new Token('really', null),
            (new BlockToken(new Block('(', ')', true), new Delimiter('.')))->withPrefix('functionCall'),
            new Token('last', new Delimiter('.'))
        ];
        self::assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));
    }

    public function testBlockSuffix(): void
    {
        $testString = 'really.()test';
        $expected = [
            new Token('really', null),
            (new BlockToken(new Block('(', ')', true), new Delimiter('.')))->withSuffix('test'),
        ];
        self::assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));

        $testString = '()test';
        $expected = [
            (new BlockToken(new Block('(', ')', true), null))->withSuffix('test'),
        ];
        self::assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));

        $testString = 'really.()test.last';
        $expected = [
            new Token('really', null),
            (new BlockToken(new Block('(', ')', true), new Delimiter('.')))->withSuffix('test'),
            new Token('last', new Delimiter('.')),

        ];
        self::assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));
    }

    public function testNestedBlocks(): void
    {
        $testString = 'var.functionCall(test).last';
        $expected = [
            new Token('var', null),
            (new BlockToken(new Block('(', ')', true), new Delimiter('.')))->withPrefix('functionCall')->withSymbols([
                new Token('test', null),
            ]),
            new Token('last', new Delimiter('.'))
        ];
        self::assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));

        $testString = "'(x'|job.task.cores|')'";
        $expected = [
            (new BlockToken(new Block('\'', '\'', false), null))->withSymbols([
                new Token('(x', null),
            ]),
            new Token('job', new Delimiter('|')),
            new Token('task', new Delimiter('.')),
            new Token('cores', new Delimiter('.')),
            (new BlockToken(new Block('\'', '\'', false), new Delimiter('|')))->withSymbols([
                new Token(')', null),
            ]),
        ];
        self::assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));
    }

    public function testIntegration(): void
    {
        $testString = 'var.functionCall([test, "another"] | first()).0';
        $expected = [
            new Token('var', null),
            (new BlockToken(new Block('(', ')', true), new Delimiter('.')))->withPrefix('functionCall')->withSymbols([
                (new BlockToken(new Block('[', ']', true), null))->withSymbols([
                    new Token('test', null),
                    (new BlockToken(new Block('"', '"', false), new Delimiter(',')))->withSymbols([
                        new Token('another', null),
                    ]),
                ]),
                (new BlockToken(new Block('(', ')', true), new Delimiter('|')))->withPrefix('first'),
            ]),
            new Token('0', new Delimiter('.'))
        ];
        self::assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));
    }

    public function testRestructuring(): void
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
            'var.functionCall([test,"another"]|first()).0',
            "(nested.unExisting??'was nil')??'doh'"
        ];

        foreach ($testStrings as $testString) {
            $tokenized = $this->tokenizer->tokenize($testString);
            $reStructuredString = (string)$tokenized;
            self::assertSame($testString, $reStructuredString);

            $reTokenized = $this->tokenizer->tokenize($reStructuredString);
            self::assertEquals($tokenized, $reTokenized);
        }
    }

    public function testDelimiterBlockMatchingPriority(): void
    {
        $testString = "['key_test', ['value']]";
        $expected = [
            (new BlockToken(new Block('[', ']', true), null))->withSymbols([
                (new BlockToken(new Block('\'', '\'', false), null))->withSymbols([
                    new Token('key_test', null),
                ]),
                (new BlockToken(new Block('[', ']', true), new Delimiter(',')))->withSymbols([
                    (new BlockToken(new Block('\'', '\'', false), null))->withSymbols([
                        new Token('value', null),
                    ]),
                ]),
            ]),
        ];

        self::assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));
    }

    public function testDelimiterSeparation(): void
    {
        $testString = "true || false";

        $delimiter = [new Delimiter('|'), new Delimiter('||')];
        $customTokenizer = new Tokenizer($delimiter, []);

        $expected = [
            new Token("true", null),
            new Token("false", new Delimiter('||')),
        ];

        self::assertEquals(new TokenStream($expected), $customTokenizer->tokenize($testString));
    }

    public function testNestingMaxDepthLimit(): void
    {
        $testString = "['key_test', ['value']]";

        // custom tokenizer with small depth-limit:
        $delimiter = [new Delimiter(',')];
        $blocks = [new Block('[', ']', true), new Block('\'', null, false),];
        $limitedTokenizer = new Tokenizer($delimiter, $blocks, new Config(maxDepth: 1));

        $expected = [
            (new BlockToken(new Block('[', ']', true), null))->withSymbols([
                new Token("'key_test', ['value']", null),
            ]),
        ];

        self::assertEquals(new TokenStream($expected), $limitedTokenizer->tokenize($testString));
    }

    public function testLineTracking(): void
    {
        $testString = file_get_contents(__DIR__ . '/test.txt');
        $expected = [
            new Token('first', null),
            (new BlockToken(new Block('(', ')', true), new Delimiter('.'), 2))->withPrefix('second')->withSymbols([
                new Token('line:2', null, 2),
            ])->withSuffix(PHP_EOL . 'end' . PHP_EOL),
        ];
        self::assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));

        $delimiter = [new Delimiter(PHP_EOL)];
        $blocks = [];
        $customTokenizer = new Tokenizer($delimiter, $blocks);

        $expected = [
            new Token('first.second', null, 1),
            new Token('(line:2)', new Delimiter(PHP_EOL), 2),
            new Token('end', new Delimiter(PHP_EOL), 3),
        ];
        self::assertEquals(new TokenStream($expected), $customTokenizer->tokenize($testString));
    }

    public function testAffixSplitting(): void
    {
        $testString = "before {{ test }} after";
        $expected = [
            new Token('before', null),
            (new BlockToken(new Block('{{', '}}', false, true), null))->withSymbols([
                new Token(' test ', null),
            ]),
            new Token('after', null),
        ];
        self::assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));

        $testString = "before.one {% test.first %} 'after'";
        $expected = [
            new Token('before', null),
            new Token('one', new Delimiter('.')),
            (new BlockToken(new Block('{%', '%}', true, true), null))->withSymbols([
                new Token(' test', null),
                new Token('first ', new Delimiter('.')),
            ]),
            (new BlockToken(new Block('\'', '\'', false), null))->withSymbols([
                new Token('after', null),
            ]),
        ];
        self::assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));
    }

}
