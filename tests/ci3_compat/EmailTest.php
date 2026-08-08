<?php

declare(strict_types=1);

namespace Kodhe\Framework\Tests\CI3Compat;

use PHPUnit\Framework\TestCase;
use Kodhe\Framework\Email\Email;

/**
 * Test Email library compatibility with CodeIgniter 3 API
 * 
 * Ensures all public methods and properties work exactly like CI3
 */
class EmailTest extends TestCase
{
    private Email $email;

    protected function setUp(): void
    {
        parent::setUp();
        $this->email = new Email();
    }

    // =========================================================================
    // DEFAULT PROPERTIES TESTS
    // =========================================================================

    public function testDefaultUserAgent(): void
    {
        $this->assertEquals('CodeIgniter', $this->email->useragent);
    }

    public function testDefaultProtocol(): void
    {
        $this->assertEquals('mail', $this->email->protocol);
    }

    public function testDefaultMailpath(): void
    {
        $this->assertEquals('/usr/sbin/sendmail', $this->email->mailpath);
    }

    public function testDefaultSmtpHost(): void
    {
        $this->assertEquals('', $this->email->smtp_host);
    }

    public function testDefaultSmtpUser(): void
    {
        $this->assertEquals('', $this->email->smtp_user);
    }

    public function testDefaultSmtpPass(): void
    {
        $this->assertEquals('', $this->email->smtp_pass);
    }

    public function testDefaultSmtpPort(): void
    {
        $this->assertEquals(25, $this->email->smtp_port);
    }

    public function testDefaultSmtpTimeout(): void
    {
        $this->assertEquals(5, $this->email->smtp_timeout);
    }

    public function testDefaultSmtpKeepalive(): void
    {
        $this->assertFalse($this->email->smtp_keepalive);
    }

    public function testDefaultSmtpCrypto(): void
    {
        $this->assertEquals('', $this->email->smtp_crypto);
    }

    public function testDefaultWordwrap(): void
    {
        $this->assertTrue($this->email->wordwrap);
    }

    public function testDefaultWrapchars(): void
    {
        $this->assertEquals(76, $this->email->wrapchars);
    }

    public function testDefaultMailtype(): void
    {
        $this->assertEquals('text', $this->email->mailtype);
    }

    public function testDefaultCharset(): void
    {
        $this->assertEquals('UTF-8', $this->email->charset);
    }

    public function testDefaultAltMessage(): void
    {
        $this->assertEquals('', $this->email->alt_message);
    }

    public function testDefaultValidate(): void
    {
        $this->assertFalse($this->email->validate);
    }

    public function testDefaultPriority(): void
    {
        $this->assertEquals(3, $this->email->priority);
    }

    public function testDefaultNewline(): void
    {
        $this->assertEquals("\n", $this->email->newline);
    }

    public function testDefaultCrlf(): void
    {
        $this->assertEquals("\n", $this->email->crlf);
    }

    public function testDefaultEncoding(): void
    {
        $this->assertEquals('quoted-printable', $this->email->encoding);
    }

    // =========================================================================
    // INITIALIZATION TESTS
    // =========================================================================

    public function testInitializeWithConfigArray(): void
    {
        $config = [
            'protocol' => 'smtp',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'smtp_user' => 'test@example.com',
            'smtp_pass' => 'secret',
            'smtp_crypto' => 'tls',
            'mailtype' => 'html',
            'charset' => 'iso-8859-1',
            'wordwrap' => false,
            'wrapchars' => 100,
            'priority' => 1,
            'newline' => "\r\n",
            'crlf' => "\r\n",
            'encoding' => 'base64',
        ];

        $email = new Email($config);

        $this->assertEquals('smtp', $email->protocol);
        $this->assertEquals('smtp.example.com', $email->smtp_host);
        $this->assertEquals(587, $email->smtp_port);
        $this->assertEquals('test@example.com', $email->smtp_user);
        $this->assertEquals('secret', $email->smtp_pass);
        $this->assertEquals('tls', $email->smtp_crypto);
        $this->assertEquals('html', $email->mailtype);
        $this->assertEquals('iso-8859-1', $email->charset);
        $this->assertFalse($email->wordwrap);
        $this->assertEquals(100, $email->wrapchars);
        $this->assertEquals(1, $email->priority);
        $this->assertEquals("\r\n", $email->newline);
        $this->assertEquals("\r\n", $email->crlf);
        $this->assertEquals('base64', $email->encoding);
    }

    public function testInitializeMethodReturnsSelf(): void
    {
        $result = $this->email->initialize([]);
        $this->assertSame($this->email, $result);
    }

    public function testClearMethod(): void
    {
        $this->email->from('test@example.com', 'Test');
        $this->email->to('recipient@example.com');
        $this->email->subject('Test Subject');
        $this->email->message('Test Message');
        
        $result = $this->email->clear();
        
        $this->assertSame($this->email, $result);
    }

    // =========================================================================
    // FROM ADDRESS TESTS
    // =========================================================================

    public function testFromMethodWithEmailOnly(): void
    {
        $result = $this->email->from('sender@example.com');
        
        $this->assertSame($this->email, $result);
        $this->assertEquals('sender@example.com', $this->email->from_email);
        $this->assertEquals('', $this->email->from_name);
    }

    public function testFromMethodWithEmailAndName(): void
    {
        $result = $this->email->from('sender@example.com', 'Sender Name');
        
        $this->assertSame($this->email, $result);
        $this->assertEquals('sender@example.com', $this->email->from_email);
        $this->assertEquals('Sender Name', $this->email->from_name);
    }

    public function testFromMethodChaining(): void
    {
        $result = $this->email
            ->from('sender@example.com', 'Sender')
            ->to('recipient@example.com')
            ->subject('Test')
            ->message('Message');
        
        $this->assertSame($this->email, $result);
    }

    // =========================================================================
    // REPLY-TO TESTS
    // =========================================================================

    public function testReplyToMethod(): void
    {
        $result = $this->email->reply_to('reply@example.com', 'Reply Name');
        
        $this->assertSame($this->email, $result);
        $this->assertEquals('reply@example.com', $this->email->reply_to_email);
        $this->assertEquals('Reply Name', $this->email->reply_to_name);
    }

    // =========================================================================
    // RECIPIENTS TESTS
    // =========================================================================

    public function testToMethodWithSingleEmail(): void
    {
        $result = $this->email->to('recipient@example.com');
        
        $this->assertSame($this->email, $result);
    }

    public function testToMethodWithMultipleEmails(): void
    {
        $emails = ['one@example.com', 'two@example.com', 'three@example.com'];
        $result = $this->email->to($emails);
        
        $this->assertSame($this->email, $result);
    }

    public function testToMethodWithCommaSeparated(): void
    {
        $result = $this->email->to('one@example.com, two@example.com');
        
        $this->assertSame($this->email, $result);
    }

    public function testCcMethod(): void
    {
        $result = $this->email->cc('cc@example.com');
        
        $this->assertSame($this->email, $result);
    }

    public function testBccMethod(): void
    {
        $result = $this->email->bcc('bcc@example.com');
        
        $this->assertSame($this->email, $result);
    }

    // =========================================================================
    // SUBJECT AND MESSAGE TESTS
    // =========================================================================

    public function testSubjectMethod(): void
    {
        $result = $this->email->subject('Test Subject');
        
        $this->assertSame($this->email, $result);
    }

    public function testMessageMethod(): void
    {
        $result = $this->email->message('Test Message Content');
        
        $this->assertSame($this->email, $result);
    }

    // =========================================================================
    // ATTACHMENT TESTS
    // =========================================================================

    public function testAttachMethod(): void
    {
        // Create a temporary file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'email_test');
        file_put_contents($tempFile, 'Test attachment content');
        
        $result = $this->email->attach($tempFile);
        
        $this->assertSame($this->email, $result);
        
        // Clean up
        unlink($tempFile);
    }

    public function testAttachMethodWithFilename(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'email_test');
        file_put_contents($tempFile, 'Test content');
        
        $result = $this->email->attach($tempFile, '', 'custom_name.txt');
        
        $this->assertSame($this->email, $result);
        
        unlink($tempFile);
    }

    // =========================================================================
    // SEND METHOD TESTS (will fail without actual mail server)
    // =========================================================================

    public function testSendMethodExists(): void
    {
        $this->assertTrue(method_exists($this->email, 'send'));
    }

    // =========================================================================
    // HELPER METHODS TESTS
    // =========================================================================

    public function testGetMethod(): void
    {
        $this->email->from('test@example.com');
        $value = $this->email->get('from_email');
        
        $this->assertEquals('test@example.com', $value);
    }

    public function testPrintDebuggerMethod(): void
    {
        $result = $this->email->printDebugger(['headers']);
        
        // Should return string or echo
        $this->assertTrue(is_string($result) || is_null($result));
    }

    public function testPrintDebuggerMethodWithAllParts(): void
    {
        $result = $this->email->printDebugger(['headers', 'subject', 'body']);
        
        $this->assertTrue(is_string($result) || is_null($result));
    }
}
