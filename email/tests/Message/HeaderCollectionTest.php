<?php

declare(strict_types=1);

namespace Kodhe\Framework\Email\Tests\Message;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\Email\Message\HeaderCollection;

/**
 * Test class for HeaderCollection
 *
 * @package     Kodhe\Email\Tests
 * @covers      \Kodhe\Framework\Email\Message\HeaderCollection
 */
class HeaderCollectionTest extends TestCase
{
    private HeaderCollection $headers;

    protected function setUp(): void
    {
        $this->headers = new HeaderCollection();
    }

    public function testSetAndGet(): void
    {
        $this->headers->set('From', 'test@example.com');
        $this->assertEquals('test@example.com', $this->headers->get('From'));
    }

    public function testHas(): void
    {
        $this->headers->set('Subject', 'Test');
        
        $this->assertTrue($this->headers->has('Subject'));
        $this->assertFalse($this->headers->has('NonExistent'));
    }

    public function testRemove(): void
    {
        $this->headers->set('X-Custom', 'value');
        $this->assertTrue($this->headers->has('X-Custom'));
        
        $this->headers->remove('X-Custom');
        $this->assertFalse($this->headers->has('X-Custom'));
    }

    public function testAppend(): void
    {
        $this->headers->set('To', 'first@example.com');
        $this->headers->append('To', 'second@example.com');
        
        $this->assertEquals('first@example.com, second@example.com', $this->headers->get('To'));
    }

    public function testAppendToArrayValue(): void
    {
        $this->headers->set('Cc', ['first@example.com']);
        $this->headers->append('Cc', 'second@example.com');
        
        $result = $this->headers->get('Cc');
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function testAll(): void
    {
        $this->headers->set('From', 'from@example.com');
        $this->headers->set('To', 'to@example.com');
        
        $all = $this->headers->all();
        
        $this->assertCount(2, $all);
        $this->assertArrayHasKey('From', $all);
        $this->assertArrayHasKey('To', $all);
    }

    public function testClear(): void
    {
        $this->headers->set('From', 'from@example.com');
        $this->headers->clear();
        
        $this->assertCount(0, $this->headers->all());
    }

    public function testToString(): void
    {
        $this->headers->set('From', 'from@example.com');
        $this->headers->set('Subject', 'Test Subject');
        
        $expected = "From: from@example.com\nSubject: Test Subject\n";
        $this->assertEquals($expected, $this->headers->toString());
    }

    public function testCount(): void
    {
        $this->headers->set('From', 'from@example.com');
        $this->headers->set('To', 'to@example.com');
        
        $this->assertEquals(2, $this->headers->count());
    }

    public function testBuildStandardHeaders(): void
    {
        $date = date('D, j M Y H:i:s O');
        $messageId = '<' . uniqid() . '@example.com>';
        
        $this->headers->buildStandardHeaders(
            'from@example.com',
            'Test Subject',
            $date,
            $messageId
        );
        
        $this->assertTrue($this->headers->has('Date'));
        $this->assertTrue($this->headers->has('From'));
        $this->assertTrue($this->headers->has('Message-ID'));
        $this->assertTrue($this->headers->has('Subject'));
    }

    public function testBuildMimeHeaders(): void
    {
        $boundary = '----=_Part_123';
        
        $this->headers->buildMimeHeaders($boundary, 'UTF-8', 'html');
        
        $this->assertEquals('1.0', $this->headers->get('MIME-Version'));
        $this->assertStringContainsString($boundary, $this->headers->get('Content-Type'));
    }

    public function testSetContentTransferEncoding(): void
    {
        $this->headers->setContentTransferEncoding('quoted-printable');
        
        $this->assertEquals('quoted-printable', $this->headers->get('Content-Transfer-Encoding'));
    }

    public function testCustomNewline(): void
    {
        $headers = new HeaderCollection("\r\n");
        $headers->set('From', 'test@example.com');
        
        $expected = "From: test@example.com\r\n";
        $this->assertEquals($expected, $headers->toString());
    }
}
