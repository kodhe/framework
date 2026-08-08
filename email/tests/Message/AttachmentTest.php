<?php

declare(strict_types=1);

namespace Kodhe\Email\Tests\Message;

use PHPUnit\Framework\TestCase;
use Kodhe\Email\Message\Attachment;

/**
 * Test class for Attachment
 *
 * @package     Kodhe\Email\Tests
 * @covers      \Kodhe\Email\Message\Attachment
 */
class AttachmentTest extends TestCase
{
    private string $testFilename = '/path/to/file.txt';
    private string $testMime = 'text/plain';

    public function testConstructor(): void
    {
        $attachment = new Attachment($this->testFilename, 'attachment', 'custom.txt', $this->testMime);

        $this->assertEquals($this->testFilename, $attachment->getFilename());
        $this->assertEquals('attachment', $attachment->getDisposition());
        $this->assertEquals('custom.txt', $attachment->getNewname());
        $this->assertEquals($this->testMime, $attachment->getMime());
    }

    public function testDefaultDisposition(): void
    {
        $attachment = new Attachment($this->testFilename);
        $this->assertEquals('attachment', $attachment->getDisposition());
    }

    public function testNullNewname(): void
    {
        $attachment = new Attachment($this->testFilename);
        $this->assertNull($attachment->getNewname());
    }

    public function testSetContent(): void
    {
        $content = 'Test file content';
        $attachment = new Attachment($this->testFilename);
        
        $attachment->setContent($content);
        
        $this->assertEquals($content, $attachment->getContent());
        $this->assertTrue($attachment->hasContent());
    }

    public function testHasContent(): void
    {
        $attachment = new Attachment($this->testFilename);
        $this->assertFalse($attachment->hasContent());
        
        $attachment->setContent('content');
        $this->assertTrue($attachment->hasContent());
    }

    public function testIsInline(): void
    {
        $inlineAttachment = new Attachment($this->testFilename, 'inline');
        $regularAttachment = new Attachment($this->testFilename, 'attachment');
        
        $this->assertTrue($inlineAttachment->isInline());
        $this->assertFalse($regularAttachment->isInline());
    }

    public function testToArray(): void
    {
        $content = 'file content';
        $attachment = new Attachment($this->testFilename, 'inline', 'renamed.pdf', 'application/pdf');
        $attachment->setContent($content);
        
        $array = $attachment->toArray();
        
        $this->assertEquals([
            'filename' => $this->testFilename,
            'disposition' => 'inline',
            'newname' => 'renamed.pdf',
            'mime' => 'application/pdf',
            'content' => $content,
        ], $array);
    }

    public function testGetters(): void
    {
        $attachment = new Attachment($this->testFilename, 'attachment', 'test.doc', 'application/msword');
        
        $this->assertEquals($this->testFilename, $attachment->getFilename());
        $this->assertEquals('attachment', $attachment->getDisposition());
        $this->assertEquals('test.doc', $attachment->getNewname());
        $this->assertEquals('application/msword', $attachment->getMime());
    }
}
