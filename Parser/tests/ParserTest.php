<?php
/**
 * Parser Test Suite
 *
 * @package CodeIgniter\Parser\Tests
 */

namespace CodeIgniter\Parser\Tests;

use PHPUnit\Framework\TestCase;
use CodeIgniter\Parser\Parser;
use CodeIgniter\Parser\Factory\ParserFactory;
use CodeIgniter\Parser\Cache\TemplateCache;
use CodeIgniter\Parser\Lexer\TemplateLexer;
use CodeIgniter\Parser\Compiler\TemplateCompiler;

class ParserTest extends TestCase
{
    /**
     * @var Parser
     */
    private $parser;

    protected function setUp(): void
    {
        parent::setUp();
        TemplateCache::resetStats();
        $this->parser = new Parser();
    }

    public function testParseVariable()
    {
        $template = 'Hello, {name}!';
        $data = ['name' => 'World'];
        $result = $this->parser->parse($template, $data, true);
        
        $this->assertEquals('Hello, World!', $result);
    }

    public function testParseMultipleVariables()
    {
        $template = '{greeting}, {name}! You have {count} messages.';
        $data = [
            'greeting' => 'Hello',
            'name' => 'John',
            'count' => 5
        ];
        $result = $this->parser->parse($template, $data, true);
        
        $this->assertEquals('Hello, John! You have 5 messages.', $result);
    }

    public function testParseLoop()
    {
        $template = '{loop items}<li>{items}</li>{/loop}';
        $data = ['items' => ['apple', 'banana', 'cherry']];
        $result = $this->parser->parse($template, $data, true);
        
        $this->assertEquals('<li>apple</li><li>banana</li><li>cherry</li>', $result);
    }

    public function testParseNestedLoop()
    {
        $template = '{loop categories}{category}: {loop products}{products}, {/loop}{/loop}';
        $data = [
            'categories' => [
                ['category' => 'Fruits', 'products' => ['Apple', 'Banana']],
                ['category' => 'Vegetables', 'products' => ['Carrot', 'Potato']]
            ]
        ];
        $result = $this->parser->parse($template, $data, true);
        
        $this->assertStringContainsString('Fruits: Apple, Banana, ', $result);
        $this->assertStringContainsString('Vegetables: Carrot, Potato, ', $result);
    }

    public function testParseConditional()
    {
        $template = '{if show}Visible{/if}';
        $data = ['show' => 'yes'];
        $result = $this->parser->parse($template, $data, true);
        
        $this->assertEquals('Visible', $result);
    }

    public function testParseConditionalFalse()
    {
        $template = '{if show}Visible{/if}';
        $data = ['show' => ''];
        $result = $this->parser->parse($template, $data, true);
        
        $this->assertEquals('', $result);
    }

    public function testParseConditionalNegation()
    {
        $template = '{if !hidden}Visible{/if}';
        $data = ['hidden' => ''];
        $result = $this->parser->parse($template, $data, true);
        
        $this->assertEquals('Visible', $result);
    }

    public function testParseInclude()
    {
        // Create a temporary include file
        $tempDir = sys_get_temp_dir() . '/parser_test';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        
        $includeFile = $tempDir . '/header.html';
        file_put_contents($includeFile, '<header>Site Header</header>');
        
        $this->parser->setViewPaths([$tempDir]);
        $template = '{include "header.html"}<main>Content</main>';
        $result = $this->parser->parse($template, [], true);
        
        $this->assertStringContainsString('<header>Site Header</header>', $result);
        $this->assertStringContainsString('<main>Content</main>', $result);
        
        // Cleanup
        unlink($includeFile);
        rmdir($tempDir);
    }

    public function testCacheHit()
    {
        $template = 'Cached: {value}';
        $data = ['value' => 'test'];
        
        // First parse (cache miss)
        $this->parser->parse($template, $data, true);
        
        // Second parse (cache hit)
        $this->parser->parse($template, $data, true);
        
        $stats = TemplateCache::getStats();
        $this->assertEquals(1, $stats['hits']);
        $this->assertEquals(1, $stats['misses']);
    }

    public function testCacheDisabled()
    {
        $this->parser->setCacheEnabled(false);
        $template = 'No cache: {value}';
        $data = ['value' => 'test'];
        
        $this->parser->parse($template, $data, true);
        $this->parser->parse($template, $data, true);
        
        $stats = TemplateCache::getStats();
        $this->assertEquals(0, $stats['hits']);
        $this->assertEquals(2, $stats['misses']);
    }

    public function testCustomDelimiters()
    {
        $this->parser->set_delimiters('[', ']');
        $template = 'Hello, [name]!';
        $data = ['name' => 'Custom'];
        $result = $this->parser->parse($template, $data, true);
        
        $this->assertEquals('Hello, Custom!', $result);
    }

    public function testParseStringAlias()
    {
        $template = 'Alias test: {value}';
        $data = ['value' => 'success'];
        $result = $this->parser->parse_string($template, $data, true);
        
        $this->assertEquals('Alias test: success', $result);
    }

    public function testDotNotation()
    {
        $template = 'User: {user.name}, Email: {user.email}';
        $data = [
            'user' => [
                'name' => 'Jane',
                'email' => 'jane@example.com'
            ]
        ];
        $result = $this->parser->parse($template, $data, true);
        
        $this->assertEquals('User: Jane, Email: jane@example.com', $result);
    }

    public function testFactoryCreation()
    {
        $parser = ParserFactory::make();
        $this->assertInstanceOf(Parser::class, $parser);
    }

    public function testFactoryWithConfig()
    {
        $parser = ParserFactory::makeWithConfig([
            'left_delimiter' => '{{',
            'right_delimiter' => '}}',
            'cache_enabled' => false
        ]);
        
        $template = '{{greeting}}, {{name}}!';
        $data = ['greeting' => 'Hi', 'name' => 'Factory'];
        $result = $parser->parse($template, $data, true);
        
        $this->assertEquals('Hi, Factory!', $result);
    }

    public function testTokenReuse()
    {
        $lexer = new TemplateLexer();
        $template = '{var1} {var2} {var3}';
        
        // Tokenize multiple times to verify token creation
        $tokens1 = $lexer->tokenize($template);
        $tokens2 = $lexer->tokenize($template);
        
        $this->assertCount(5, $tokens1); // 3 variables + 2 text (spaces)
        $this->assertCount(5, $tokens2);
    }

    public function testEmptyVariable()
    {
        $template = 'Value: {missing}';
        $result = $this->parser->parse($template, [], true);
        
        $this->assertEquals('Value: ', $result);
    }

    public function testComplexTemplate()
    {
        $template = '
{if title}
<h1>{title}</h1>
{/if}
{loop items}
<div>{items.name} - ${items.price}</div>
{/loop}
{if hasMore}
<p>More items available</p>
{/if}
';
        $data = [
            'title' => 'Product List',
            'items' => [
                ['name' => 'Widget', 'price' => '9.99'],
                ['name' => 'Gadget', 'price' => '19.99']
            ],
            'hasMore' => 'yes'
        ];
        $result = $this->parser->parse($template, $data, true);
        
        $this->assertStringContainsString('<h1>Product List</h1>', $result);
        $this->assertStringContainsString('Widget - $9.99', $result);
        $this->assertStringContainsString('Gadget - $19.99', $result);
        $this->assertStringContainsString('More items available', $result);
    }
}
