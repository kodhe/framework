<?php

declare(strict_types=1);

namespace Kodhe\Framework\Tests\Console;

use Kodhe\Framework\Console\Input;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Console Input class
 */
class InputTest extends TestCase
{
    public function testConstructorWithDefaultArgs(): void
    {
        $input = new Input(['console', 'help']);
        $this->assertEquals('help', $input->getFirstArgument());
    }

    public function testConstructorWithCustomTokens(): void
    {
        $tokens = ['console', 'make:command', 'TestCommand'];
        $input = new Input($tokens);
        
        $this->assertEquals('make:command', $input->getFirstArgument());
        $this->assertEquals('TestCommand', $input->getArgument(0));
    }

    public function testGetArguments(): void
    {
        $input = new Input(['console', 'test', 'arg1', 'arg2']);
        
        $args = $input->getArguments();
        $this->assertArrayHasKey('command', $args);
        $this->assertEquals('test', $args['command']);
    }

    public function testGetArgumentByName(): void
    {
        $input = new Input(['console', 'test', '--name=value']);
        
        $this->assertEquals('test', $input->getArgument('command'));
    }

    public function testGetArgumentByIndex(): void
    {
        $input = new Input(['console', 'command', 'first', 'second']);
        
        $this->assertEquals('first', $input->getArgument(0));
        $this->assertEquals('second', $input->getArgument(1));
    }

    public function testGetArgumentWithDefault(): void
    {
        $input = new Input(['console', 'test']);
        
        $this->assertEquals('default', $input->getArgument('missing', 'default'));
    }

    public function testHasArgument(): void
    {
        $input = new Input(['console', 'test', 'arg1']);
        
        $this->assertTrue($input->hasArgument('command'));
        $this->assertTrue($input->hasArgument(0));
        $this->assertFalse($input->hasArgument('missing'));
    }

    public function testSetArgument(): void
    {
        $input = new Input(['console', 'test']);
        
        $input->setArgument('custom', 'value');
        $this->assertEquals('value', $input->getArgument('custom'));
        
        $input->setArgument(0, 'new_value');
        $this->assertEquals('new_value', $input->getArgument(0));
    }

    public function testGetOptions(): void
    {
        $input = new Input(['console', 'test', '--verbose', '--name=John']);
        
        $options = $input->getOptions();
        $this->assertTrue($options['verbose']);
        $this->assertEquals('John', $options['name']);
    }

    public function testGetOption(): void
    {
        $input = new Input(['console', 'test', '--force']);
        
        $this->assertTrue($input->getOption('force'));
        $this->assertEquals('default', $input->getOption('missing', 'default'));
    }

    public function testHasOption(): void
    {
        $input = new Input(['console', 'test', '--quiet']);
        
        $this->assertTrue($input->hasOption('quiet'));
        $this->assertFalse($input->hasOption('verbose'));
    }

    public function testSetOption(): void
    {
        $input = new Input(['console', 'test']);
        
        $input->setOption('custom', 'value');
        $this->assertEquals('value', $input->getOption('custom'));
    }

    public function testLongOptionWithValue(): void
    {
        $input = new Input(['console', 'test', '--output=file.txt']);
        
        $this->assertEquals('file.txt', $input->getOption('output'));
    }

    public function testLongOptionWithSeparateValue(): void
    {
        $input = new Input(['console', 'test', '--output', 'file.txt']);
        
        $this->assertEquals('file.txt', $input->getOption('output'));
    }

    public function testShortOptions(): void
    {
        $input = new Input(['console', 'test', '-abc']);
        
        $this->assertTrue($input->hasOption('a'));
        $this->assertTrue($input->hasOption('b'));
        $this->assertTrue($input->hasOption('c'));
    }

    public function testShortOptionWithValue(): void
    {
        $input = new Input(['console', 'test', '-o', 'file.txt']);
        
        $this->assertEquals('file.txt', $input->getOption('o'));
    }

    public function testEmptyInput(): void
    {
        $input = new Input([]);
        
        $this->assertNull($input->getFirstArgument());
        $this->assertEmpty($input->getArguments());
        $this->assertEmpty($input->getOptions());
    }

    public function testGetTokens(): void
    {
        $tokens = ['console', 'test', '--verbose'];
        $input = new Input($tokens);
        
        $this->assertEquals($tokens, $input->getTokens());
    }

    public function testGetCommandName(): void
    {
        $input = new Input(['console', 'my:command']);
        
        $this->assertEquals('my:command', $input->getCommandName());
    }
}
