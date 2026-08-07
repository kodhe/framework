<?php

declare(strict_types=1);

namespace Kodhe\Framework\Tests\Console;

use Kodhe\Framework\Console\Output;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Console Output class
 */
class OutputTest extends TestCase
{
    protected $stream;
    protected Output $output;

    protected function setUp(): void
    {
        $this->stream = fopen('php://memory', 'w+');
        $this->output = new Output($this->stream);
    }

    protected function tearDown(): void
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }

    protected function getOutput(): string
    {
        rewind($this->stream);
        return stream_get_contents($this->stream) ?: '';
    }

    public function testWrite(): void
    {
        $this->output->write('Hello');
        $this->assertEquals('Hello', $this->getOutput());
    }

    public function testWriteWithNewline(): void
    {
        $this->output->write('Hello', true);
        $this->assertEquals("Hello\n", $this->getOutput());
    }

    public function testWriteln(): void
    {
        $this->output->writeln('Hello');
        $this->assertEquals("Hello\n", $this->getOutput());
    }

    public function testVerbosityLevels(): void
    {
        $this->assertEquals(Output::VERBOSITY_NORMAL, $this->output->getVerbosity());
        
        $this->output->setVerbosity(Output::VERBOSITY_QUIET);
        $this->assertTrue($this->output->isQuiet());
        
        $this->output->setVerbosity(Output::VERBOSITY_VERBOSE);
        $this->assertTrue($this->output->isVerbose());
        
        $this->output->setVerbosity(Output::VERBOSITY_VERY_VERBOSE);
        $this->assertTrue($this->output->isVeryVerbose());
        
        $this->output->setVerbosity(Output::VERBOSITY_DEBUG);
        $this->assertTrue($this->output->isDebug());
    }

    public function testInfoMessage(): void
    {
        $this->output->info('Test info');
        $output = $this->getOutput();
        $this->assertStringContainsString('Test info', $output);
    }

    public function testSuccessMessage(): void
    {
        $this->output->success('Test success');
        $output = $this->getOutput();
        $this->assertStringContainsString('Test success', $output);
    }

    public function testWarningMessage(): void
    {
        $this->output->warning('Test warning');
        $output = $this->getOutput();
        $this->assertStringContainsString('Test warning', $output);
    }

    public function testErrorMessage(): void
    {
        $this->output->error('Test error');
        $output = $this->getOutput();
        $this->assertStringContainsString('Test error', $output);
    }

    public function testDebugMessageOnlyInDebugMode(): void
    {
        $this->output->setVerbosity(Output::VERBOSITY_NORMAL);
        $this->output->debug('Test debug');
        $output = $this->getOutput();
        $this->assertEmpty($output);
        
        $this->output->setVerbosity(Output::VERBOSITY_DEBUG);
        $this->output->debug('Test debug');
        $output = $this->getOutput();
        $this->assertStringContainsString('Test debug', $output);
    }

    public function testTable(): void
    {
        $headers = ['Name', 'Age'];
        $rows = [
            ['John', '30'],
            ['Jane', '25'],
        ];
        
        $this->output->table($headers, $rows);
        $output = $this->getOutput();
        
        $this->assertStringContainsString('Name', $output);
        $this->assertStringContainsString('Age', $output);
        $this->assertStringContainsString('John', $output);
        $this->assertStringContainsString('Jane', $output);
    }

    public function testEmptyTable(): void
    {
        $this->output->table([], []);
        $this->assertEmpty($this->getOutput());
    }

    public function testProgress(): void
    {
        $this->output->progress(5, 10, 'Processing');
        $output = $this->getOutput();
        
        $this->assertStringContainsString('50%', $output);
        $this->assertStringContainsString('Processing', $output);
    }

    public function testProgressWithZeroTotal(): void
    {
        $this->output->progress(5, 0);
        $this->assertEmpty($this->getOutput());
    }

    public function testGetStream(): void
    {
        $this->assertSame($this->stream, $this->output->getStream());
    }

    public function testFormat(): void
    {
        $formatted = $this->output->format('Test', 'info');
        $this->assertStringContainsString('Test', $formatted);
    }

    public function testClearLine(): void
    {
        $this->output->clearLine();
        $output = $this->getOutput();
        $this->assertNotEmpty($output);
    }

    public function testMoveCursorUp(): void
    {
        $this->output->moveCursorUp(2);
        $output = $this->getOutput();
        $this->assertStringContainsString('2A', $output);
    }
}
