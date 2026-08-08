<?php

declare(strict_types=1);

namespace Kodhe\Framework\Tests;

use Kodhe\Framework\Parser\Parser;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Parser class
 */
class ParserTest extends TestCase
{
    /**
     * @var Parser
     */
    private $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new Parser();
    }

    /**
     * Test simple variable replacement
     */
    public function testSimpleVariableReplacement(): void
    {
        $template = 'Hello, {name}! Welcome to {site}.';
        $data = ['name' => 'John', 'site' => 'MyApp'];
        $result = $this->parser->parse_string($template, $data, true);

        $this->assertEquals('Hello, John! Welcome to MyApp.', $result);
    }

    /**
     * Test tag pair (loop) functionality
     */
    public function testTagPairLoop(): void
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
     * Test nested tag pairs
     */
    public function testNestedTagPairs(): void
    {
        $template = '{menu}{items}<li>{item}</li>{/items}{/menu}';
        $data = [
            'menu' => [
                [
                    'items' => [
                        ['item' => 'Home'],
                        ['item' => 'About']
                    ]
                ],
                [
                    'items' => [
                        ['item' => 'Contact'],
                        ['item' => 'Help']
                    ]
                ]
            ]
        ];
        $result = $this->parser->parse_string($template, $data, true);

        $this->assertStringContainsString('<li>Home</li>', $result);
        $this->assertStringContainsString('<li>Contact</li>', $result);
    }

    /**
     * Test custom delimiters
     */
    public function testCustomDelimiters(): void
    {
        $this->parser->set_delimiters('{{', '}}');
        $template = 'Hello, {{name}}!';
        $result = $this->parser->parse_string($template, ['name' => 'World'], true);

        $this->assertEquals('Hello, World!', $result);
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
     * Test multiple variables
     */
    public function testMultipleVariables(): void
    {
        $this->parser->set_delimiters('{', '}'); // Reset to default
        $template = '{greeting}, {name}! You have {count} new messages.';
        $data = [
            'greeting' => 'Good morning',
            'name' => 'Alice',
            'count' => 5
        ];
        $result = $this->parser->parse_string($template, $data, true);

        $this->assertEquals('Good morning, Alice! You have 5 new messages.', $result);
    }

    /**
     * Test that delimiter properties are public and can be modified
     */
    public function testDelimiterProperties(): void
    {
        $this->parser->l_delim = '[[';
        $this->parser->r_delim = ']]';
        
        $template = 'Hello, [[name]]!';
        $result = $this->parser->parse_string($template, ['name' => 'Test'], true);

        $this->assertEquals('Hello, Test!', $result);
    }

    /**
     * Test with data containing special characters
     */
    public function testSpecialCharactersInData(): void
    {
        $template = 'Message: {message}';
        $data = ['message' => 'Hello & Goodbye <test>'];
        $result = $this->parser->parse_string($template, $data, true);

        $this->assertEquals('Message: Hello & Goodbye <test>', $result);
    }

    /**
     * Test with non-existent variable in template
     */
    public function testNonExistentVariable(): void
    {
        $template = 'Hello, {name}!';
        $data = ['other' => 'value'];
        $result = $this->parser->parse_string($template, $data, true);

        // Variable should remain as-is if not found in data
        $this->assertEquals('Hello, {name}!', $result);
    }

    /**
     * Test with null value in data
     */
    public function testNullValueInData(): void
    {
        $template = 'Value: {value}';
        $data = ['value' => null];
        $result = $this->parser->parse_string($template, $data, true);

        $this->assertEquals('Value: ', $result);
    }

    /**
     * Test complex nested structure
     */
    public function testComplexNestedStructure(): void
    {
        $template = '{users}<div>{name} - {email}</div>{/users}';
        $data = [
            'users' => [
                ['name' => 'John', 'email' => 'john@example.com'],
                ['name' => 'Jane', 'email' => 'jane@example.com']
            ]
        ];
        $result = $this->parser->parse_string($template, $data, true);

        $this->assertStringContainsString('<div>John - john@example.com</div>', $result);
        $this->assertStringContainsString('<div>Jane - jane@example.com</div>', $result);
    }
}
