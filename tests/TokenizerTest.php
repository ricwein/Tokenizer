<?php declare(strict_types=1);

namespace units;

use PHPUnit\Framework\TestCase;
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
        $expected = [new Token('test', null), new Token('123', new Delimiter('.'))];
        $this->assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));

        $testString = 'test-123';
        $expected = [new Token('test-123', null)];
        $this->assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));

        $testString = 'test.123 | something';
        $expected = [new Token('test', null), new Token('123', new Delimiter('.')), new Token('something', new Delimiter('|'))];
        $this->assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));

        $testString = 'really.long.test.something.last';
        $expected = [
            new Token('really', null),
            new Token('long', new Delimiter('.')),
            new Token('test', new Delimiter('.')),
            new Token('something', new Delimiter('.')),
            new Token('last', new Delimiter('.'))
        ];
        $this->assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));
    }

    public function testSimpleBlocks()
    {
        $testString = '[test]';
        $expected = [
            (new BlockToken(new Block('[', ']', true), null))->withSymbols([
                new Token('test', null)
            ])
        ];
        $this->assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));

        $testString = '[(test)]';
        $expected = [
            (new BlockToken(new Block('[', ']', true), null))->withSymbols([
                (new BlockToken(new Block('(', ')', true), null))->withSymbols([
                    new Token('test', null)
                ])
            ])
        ];
        $this->assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));

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
        $this->assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));
    }

    public function testBlockSymbols()
    {
        $testString = '[test.123]';
        $expected = [
            (new BlockToken(new Block('[', ']', true), null))->withSymbols([
                new Token('test', null), new Token('123', new Delimiter('.'))
            ])
        ];
        $this->assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));

        $testString = '[(test).123]';
        $expected = [
            (new BlockToken(new Block('[', ']', true), null))->withSymbols([
                (new BlockToken(new Block('(', ')', true), null))->withSymbols([
                    new Token('test', null)
                ]),
                new Token('123', new Delimiter('.'))
            ])
        ];
        $this->assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));

        $testString = '["(test)".123]';
        $expected = [
            (new BlockToken(new Block('[', ']', true), null))->withSymbols([
                (new BlockToken(new Block('"', '"', false), null))->withSymbols([
                    new Token('(test)', null),
                ]),
                new Token('123', new Delimiter('.'))
            ])
        ];
        $this->assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));
    }

    public function testBlockPrefix()
    {
        $testString = 'functionCall()';
        $expected = [
            (new BlockToken(new Block('(', ')', true), null))->withPrefix('functionCall'),
        ];
        $this->assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));

        $testString = 'really.functionCall().last';
        $expected = [
            new Token('really', null),
            (new BlockToken(new Block('(', ')', true), new Delimiter('.')))->withPrefix('functionCall'),
            new Token('last', new Delimiter('.'))
        ];
        $this->assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));
    }

    public function testBlockSuffix()
    {
        $testString = 'really.()test';
        $expected = [
            new Token('really', null),
            (new BlockToken(new Block('(', ')', true), new Delimiter('.')))->withSuffix('test'),
        ];
        $this->assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));

        $testString = '()test';
        $expected = [
            (new BlockToken(new Block('(', ')', true), null))->withSuffix('test'),
        ];
        $this->assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));

        $testString = 'really.()test.last';
        $expected = [
            new Token('really', null),
            (new BlockToken(new Block('(', ')', true), new Delimiter('.')))->withSuffix('test'),
            new Token('last', new Delimiter('.')),

        ];
        $this->assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));
    }

    public function testNestedBlocks()
    {
        $testString = 'var.functionCall(test).last';
        $expected = [
            new Token('var', null),
            (new BlockToken(new Block('(', ')', true), new Delimiter('.')))->withPrefix('functionCall')->withSymbols([
                new Token('test', null),
            ]),
            new Token('last', new Delimiter('.'))
        ];
        $this->assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));

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
        $this->assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));
    }

    public function testIntegration()
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
        $this->assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));
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
            'var.functionCall([test,"another"]|first()).0',
            "(nested.unExisting??'was nil')??'doh'"
        ];

        foreach ($testStrings as $testString) {
            $tokenized = $this->tokenizer->tokenize($testString);
            $reStructuredString = (string)$tokenized;
            $this->assertSame($testString, $reStructuredString);

            $reTokenized = $this->tokenizer->tokenize($reStructuredString);
            $this->assertEquals($tokenized, $reTokenized);
        }
    }

    public function testDelimiterBlockMatchingPriority()
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

        $this->assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));
    }

    public function testDelimiterSeparation()
    {
        $testString = "true || false";

        $delimiter = [new Delimiter('|'), new Delimiter('||')];
        $customTokenizer = new Tokenizer($delimiter, []);

        $expected = [
            new Token("true", null),
            new Token("false", new Delimiter('||')),
        ];

        $this->assertEquals(new TokenStream($expected), $customTokenizer->tokenize($testString));
    }

    public function testNestingMaxDepthLimit()
    {
        $testString = "['key_test', ['value']]";

        // custom tokenizer with small depth-limit:
        $delimiter = [new Delimiter(',')];
        $blocks = [new Block('[', ']', true), new Block('\'', null, false),];
        $limitedTokenizer = new Tokenizer($delimiter, $blocks, 1);

        $expected = [
            (new BlockToken(new Block('[', ']', true), null))->withSymbols([
                new Token("'key_test', ['value']", null),
            ]),
        ];

        $this->assertEquals(new TokenStream($expected), $limitedTokenizer->tokenize($testString));
    }

    public function testLineTracking()
    {
        $testString = file_get_contents(__DIR__ . '/test.txt');
        $expected = [
            new Token('first', null),
            (new BlockToken(new Block('(', ')', true), new Delimiter('.'), 2))->withPrefix('second' . PHP_EOL)->withSymbols([
                new Token('line:2', null, 2),
            ])->withSuffix(PHP_EOL . 'end' . PHP_EOL),
        ];
        $this->assertEquals(new TokenStream($expected), $this->tokenizer->tokenize($testString));

        $delimiter = [new Delimiter(PHP_EOL)];
        $blocks = [];
        $customTokenizer = new Tokenizer($delimiter, $blocks);

        $expected = [
            new Token('first.second', null, 1),
            new Token('(line:2)', new Delimiter(PHP_EOL), 2),
            new Token('end', new Delimiter(PHP_EOL), 3),
        ];
        $this->assertEquals(new TokenStream($expected), $customTokenizer->tokenize($testString));

    }

}
