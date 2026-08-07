<?php

declare(strict_types=1);

namespace Kodhe\Framework\Tests\Console;

use Kodhe\Framework\Console\Console;
use Kodhe\Framework\Console\Commands\HelpCommand;
use Kodhe\Framework\Console\Commands\ListCommand;
use Kodhe\Framework\Console\Commands\VersionCommand;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Console Application
 */
class ConsoleTest extends TestCase
{
    protected Console $console;

    protected function setUp(): void
    {
        // Reset singleton instance
        $reflection = new \ReflectionClass(Console::class);
        $instanceProperty = $reflection->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, null);
        
        $this->console = Console::getInstance();
    }

    public function testSingletonInstance(): void
    {
        $instance1 = Console::getInstance();
        $instance2 = Console::getInstance();
        
        $this->assertSame($instance1, $instance2);
    }

    public function testDefaultCommandsRegistered(): void
    {
        $this->assertTrue($this->console->hasCommand('help'));
        $this->assertTrue($this->console->hasCommand('list'));
        $this->assertTrue($this->console->hasCommand('version'));
    }

    public function testAddCommand(): void
    {
        $command = new HelpCommand();
        $this->console->addCommand($command);
        
        $this->assertTrue($this->console->hasCommand('help'));
        $this->assertSame($command, $this->console->getCommand('help'));
    }

    public function testAddCommandWithEmptyName(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Command name cannot be empty');
        
        $command = new class extends \Kodhe\Framework\Console\Command {
            protected string $name = '';
            protected string $description = '';
            public function handle(): int { return 0; }
        };
        
        $this->console->addCommand($command);
    }

    public function testAddMultipleCommands(): void
    {
        $commands = [
            new HelpCommand(),
            new ListCommand($this->console),
        ];
        
        $this->console->addCommands($commands);
        
        $this->assertTrue($this->console->hasCommand('help'));
        $this->assertTrue($this->console->hasCommand('list'));
    }

    public function testHasCommand(): void
    {
        $this->assertTrue($this->console->hasCommand('help'));
        $this->assertFalse($this->console->hasCommand('nonexistent'));
    }

    public function testGetCommand(): void
    {
        $command = $this->console->getCommand('help');
        $this->assertInstanceOf(HelpCommand::class, $command);
    }

    public function testGetNonExistentCommand(): void
    {
        $command = $this->console->getCommand('nonexistent');
        $this->assertNull($command);
    }

    public function testGetCommands(): void
    {
        $commands = $this->console->getCommands();
        $this->assertIsArray($commands);
        $this->assertNotEmpty($commands);
    }

    public function testSetNameAndGet(): void
    {
        $this->console->setName('Test Console');
        $this->assertEquals('Test Console', $this->console->getName());
    }

    public function testSetVersionAndGet(): void
    {
        $this->console->setVersion('2.0.0');
        $this->assertEquals('2.0.0', $this->console->getVersion());
    }

    public function testCommandAliases(): void
    {
        $command = new HelpCommand();
        $this->console->addCommand($command);
        
        // HelpCommand has '?' as alias
        $this->assertTrue($this->console->hasCommand('?'));
        $this->assertInstanceOf(HelpCommand::class, $this->console->getCommand('?'));
    }

    public function testGetLongestCommandName(): void
    {
        $length = $this->console->getLongestCommandName();
        $this->assertGreaterThan(0, $length);
    }

    public function testConsoleHasOutputAndInput(): void
    {
        $output = $this->console->getOutput();
        $input = $this->console->getInput();
        
        $this->assertInstanceOf(\Kodhe\Framework\Console\Output::class, $output);
        $this->assertInstanceOf(\Kodhe\Framework\Console\Input::class, $input);
    }
}
