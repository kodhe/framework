<?php

declare(strict_types=1);

namespace Kodhe\Parser\Tests;

use Kodhe\Parser\Factory\ParserFactory;
use Kodhe\Parser\Parser;
use PHPUnit\Framework\TestCase;

/**
 * Parser Tests
 *
 * Tests for CodeIgniter 3 Parser compatibility and modular features.
 */
class ParserTest extends TestCase
{
    private Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser();
    }

    /**
     * Test simple variable replacement
     */
    public function testVariableReplacement(): void
    {
        $template = 'Hello, {name}!';
        $data = ['name' => 'World'];
        
        $result = $this->parser->parse_string($template, $data, true);
        
        $this->assertEquals('Hello, World!', $result);
    }

    /**
     * Test multiple variables
     */
    public function testMultipleVariables(): void
    {
        $template = '{greeting}, {name}! Welcome to {place}.';
        $data = [
            'greeting' => 'Hello',
            'name' => 'John',
            'place' => 'CodeIgniter'
        ];
        
        $result = $this->parser->parse_string($template, $data, true);
        
        $this->assertEquals('Hello, John! Welcome to CodeIgniter.', $result);
    }

    /**
     * Test loop (tag pair)
     */
    public function testLoop(): void
    {
        $template = '<ul>{items}<li>{item}</li>{/items}</ul>';
        $data = [
            'items' => [
                ['item' => 'First'],
                ['item' => 'Second'],
                ['item' => 'Third']
            ]
        ];
        
        $result = $this->parser->parse_string($template, $data, true);
        
        $this->assertEquals('<ul><li>First</li><li>Second</li><li>Third</li></ul>', $result);
    }

    /**
     * Test nested loops
     */
    public function testNestedLoop(): void
    {
        $template = '{categories}<h2>{cat_name}</h2><ul>{items}<li>{item}</li>{/items}</ul>{/categories}';
        $data = [
            'categories' => [
                [
                    'cat_name' => 'Fruits',
                    'items' => [
                        ['item' => 'Apple'],
                        ['item' => 'Banana']
                    ]
                ],
                [
                    'cat_name' => 'Vegetables',
                    'items' => [
                        ['item' => 'Carrot'],
                        ['item' => 'Broccoli']
                    ]
                ]
            ]
        ];
        
        $result = $this->parser->parse_string($template, $data, true);
        
        $expected = '<h2>Fruits</h2><ul><li>Apple</li><li>Banana</li></ul>'
                  . '<h2>Vegetables</h2><ul><li>Carrot</li><li>Broccoli</li></ul>';
        
        $this->assertEquals($expected, $result);
    }

    /**
     * Test conditional (variable exists)
     */
    public function testConditionalWithExistingVariable(): void
    {
        $template = '{show}<p>Content shown!</p>{/show}';
        $data = [
            'show' => [
                ['dummy' => 'value']
            ]
        ];
        
        $result = $this->parser->parse_string($template, $data, true);
        
        $this->assertEquals('<p>Content shown!</p>', $result);
    }

    /**
     * Test custom delimiters
     */
    public function testCustomDelimiters(): void
    {
        $this->parser->set_delimiters('{{', '}}');
        
        $template = 'Hello, {{name}}!';
        $data = ['name' => 'World'];
        
        $result = $this->parser->parse_string($template, $data, true);
        
        $this->assertEquals('Hello, World!', $result);
    }

    /**
     * Test cache functionality
     */
    public function testCache(): void
    {
        $template = 'Cached: {value}';
        $data = ['value' => 'test'];
        
        // First parse
        $result1 = $this->parser->parse_string($template, $data, true);
        
        // Second parse (should use cache)
        $result2 = $this->parser->parse_string($template, $data, true);
        
        $this->assertEquals($result1, $result2);
        $this->assertEquals('Cached: test', $result1);
    }

    /**
     * Test empty template
     */
    public function testEmptyTemplate(): void
    {
        $result = $this->parser->parse_string('', [], true);
        
        $this->assertFalse($result);
    }

    /**
     * Test factory pattern
     */
    public function testFactoryPattern(): void
    {
        $parser = ParserFactory::create();
        
        $template = 'Factory: {value}';
        $data = ['value' => 'works'];
        
        $result = $parser->parse_string($template, $data, true);
        
        $this->assertEquals('Factory: works', $result);
    }

    /**
     * Test factory with custom delimiters
     */
    public function testFactoryWithDelimiters(): void
    {
        $parser = ParserFactory::createWithDelimiters('[', ']');
        
        $template = 'Hello, [name]!';
        $data = ['name' => 'Factory'];
        
        $result = $parser->parse_string($template, $data, true);
        
        $this->assertEquals('Hello, Factory!', $result);
    }

    /**
     * Test include simulation (nested data)
     */
    public function testIncludeSimulation(): void
    {
        $template = '{header}{content}{footer}';
        $data = [
            'header' => [['title' => 'My Site']],
            'content' => [['body' => 'Page content']],
            'footer' => [['copyright' => '2024']]
        ];
        
        $result = $this->parser->parse_string($template, $data, true);
        
        $this->assertStringContainsString('My Site', $result);
        $this->assertStringContainsString('Page content', $result);
        $this->assertStringContainsString('2024', $result);
    }

    /**
     * Test lexer tokenization
     */
    public function testLexerTokenization(): void
    {
        $lexer = new \Kodhe\Parser\Lexer\TemplateLexer();
        $tokens = $lexer->tokenize('Hello {name}, you have {count} messages.');
        
        $this->assertCount(5, $tokens);
        $this->assertEquals('text', $tokens[0]->getType());
        $this->assertEquals('variable', $tokens[1]->getType());
        $this->assertEquals('name', $tokens[1]->getName());
    }

    /**
     * Test compiler with tokens
     */
    public function testCompiler(): void
    {
        $lexer = new \Kodhe\Parser\Lexer\TemplateLexer();
        $compiler = new \Kodhe\Parser\Compiler\TemplateCompiler();
        
        $tokens = $lexer->tokenize('Hello {name}!');
        $result = $compiler->compile($tokens, ['name' => 'World']);
        
        $this->assertEquals('Hello World!', $result);
    }

    /**
     * Test cache class
     */
    public function testCacheClass(): void
    {
        $cache = new \Kodhe\Parser\Cache\TemplateCache();
        
        $cache->set('key1', 'value1');
        
        $this->assertTrue($cache->has('key1'));
        $this->assertEquals('value1', $cache->get('key1'));
        
        $cache->remove('key1');
        $this->assertFalse($cache->has('key1'));
    }

    /**
     * Test parse context
     */
    public function testParseContext(): void
    {
        $context = new \Kodhe\Parser\Context\ParseContext('{', '}', ['key' => 'value']);
        
        $this->assertEquals('value', $context->get('key'));
        $this->assertTrue($context->has('key'));
        $this->assertFalse($context->has('nonexistent'));
    }

    /**
     * Test deeply nested loops
     */
    public function testDeeplyNestedLoops(): void
    {
        $template = '{level1}[{level2}({level3}){/level2}]{/level1}';
        $data = [
            'level1' => [
                [
                    'level2' => [
                        [
                            'level3' => 'deep'
                        ]
                    ]
                ]
            ]
        ];
        
        $result = $this->parser->parse_string($template, $data, true);
        
        $this->assertStringContainsString('(deep)', $result);
    }
}
