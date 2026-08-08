<?php

declare(strict_types=1);

namespace Kodhe\Email\Tests;

use PHPUnit\Framework\TestCase;
use Kodhe\Email\Email;
use Kodhe\Email\Contracts\EmailInterface;

/**
 * Test class for Email facade
 *
 * @package     Kodhe\Email\Tests
 * @covers      \Kodhe\Email\Email
 */
class EmailTest extends TestCase
{
    private Email $email;

    protected function setUp(): void
    {
        $this->email = new Email();
    }

    public function testConstructor(): void
    {
        $config = [
            'protocol' => 'mail',
            'mailtype' => 'html',
        ];
        
        $email = new Email($config);
        
        $this->assertEquals('mail', $email->protocol);
        $this->assertEquals('html', $email->mailtype);
    }

    public function testInitialize(): void
    {
        $config = [
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
        ];
        
        $result = $this->email->initialize($config);
        
        $this->assertInstanceOf(EmailInterface::class, $result);
        $this->assertEquals('smtp.example.com', $this->email->smtp_host);
        $this->assertEquals(587, $this->email->smtp_port);
    }

    public function testFrom(): void
    {
        $result = $this->email->from('test@example.com', 'Test User');
        
        $this->assertInstanceOf(EmailInterface::class, $result);
    }

    public function testFromWithInvalidEmail(): void
    {
        $this->email->validate = true;
        $result = $this->email->from('invalid-email', 'Test User');
        
        $this->assertInstanceOf(EmailInterface::class, $result);
    }

    public function testReplyTo(): void
    {
        $result = $this->email->replyTo('reply@example.com', 'Reply User');
        
        $this->assertInstanceOf(EmailInterface::class, $result);
    }

    public function testTo(): void
    {
        $result = $this->email->to('recipient@example.com');
        
        $this->assertInstanceOf(EmailInterface::class, $result);
    }

    public function testToWithMultipleRecipients(): void
    {
        $result = $this->email->to(['user1@example.com', 'user2@example.com']);
        
        $this->assertInstanceOf(EmailInterface::class, $result);
    }

    public function testCc(): void
    {
        $result = $this->email->cc('cc@example.com');
        
        $this->assertInstanceOf(EmailInterface::class, $result);
    }

    public function testBcc(): void
    {
        $result = $this->email->bcc('bcc@example.com');
        
        $this->assertInstanceOf(EmailInterface::class, $result);
    }

    public function testSubject(): void
    {
        $result = $this->email->subject('Test Subject');
        
        $this->assertInstanceOf(EmailInterface::class, $result);
    }

    public function testMessage(): void
    {
        $result = $this->email->message('Test message body');
        
        $this->assertInstanceOf(EmailInterface::class, $result);
    }

    public function testClear(): void
    {
        $this->email->subject('Test')->message('Body');
        $result = $this->email->clear();
        
        $this->assertInstanceOf(EmailInterface::class, $result);
    }

    public function testPrintDebugger(): void
    {
        $debug = $this->email->printDebugger();
        
        $this->assertIsString($debug);
    }

    public function testPropertyDefaults(): void
    {
        $this->assertEquals('CodeIgniter', $this->email->useragent);
        $this->assertEquals('/usr/sbin/sendmail', $this->email->mailpath);
        $this->assertEquals('mail', $this->email->protocol);
        $this->assertEquals(25, $this->email->smtp_port);
        $this->assertEquals(3, $this->email->priority);
        $this->assertTrue($this->email->wordwrap);
        $this->assertFalse($this->email->validate);
    }

    public function testTypedProperties(): void
    {
        // Verify properties are typed (PHP 8.1+)
        $this->email->smtp_port = 465;
        $this->email->smtp_keepalive = true;
        $this->email->wordwrap = false;
        
        $this->assertSame(465, $this->email->smtp_port);
        $this->assertTrue($this->email->smtp_keepalive);
        $this->assertFalse($this->email->wordwrap);
    }

    public function testBackwardCompatibility(): void
    {
        // Test old-style method chaining still works
        $this->email
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Test')
            ->message('Hello World');
        
        $this->expectNotToPerformAssertions();
    }
}
