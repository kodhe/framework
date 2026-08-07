<?php

declare(strict_types=1);

namespace Kodhe\Encryption\Tests\Handlers;

use Kodhe\Encryption\Handlers\OpenSslHandler;
use PHPUnit\Framework\TestCase;

/**
 * Class OpenSslHandlerTest
 *
 * Unit tests for the OpenSSL handler
 *
 * @package     Kodhe\Encryption\Tests\Handlers
 */
class OpenSslHandlerTest extends TestCase
{
    private OpenSslHandler $handler;
    private string $testKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testKey = '0123456789abcdef0123456789abcdef';
        $this->handler = new OpenSslHandler('aes-256', 'cbc');
    }

    public function testEncryptDecryptCbcMode(): void
    {
        $data = 'test data for CBC mode';
        $encrypted = $this->handler->encrypt($data, $this->testKey);
        $decrypted = $this->handler->decrypt($encrypted, $this->testKey);
        
        $this->assertSame($data, $decrypted);
    }

    public function testEncryptDecryptGcmMode(): void
    {
        $this->handler = new OpenSslHandler('aes-256', 'gcm');
        
        $data = 'test data for GCM mode';
        $encrypted = $this->handler->encrypt($data, $this->testKey);
        $decrypted = $this->handler->decrypt($encrypted, $this->testKey);
        
        $this->assertSame($data, $decrypted);
    }

    public function testEncryptDecryptRawData(): void
    {
        $data = 'raw binary test';
        $encrypted = $this->handler->encrypt($data, $this->testKey, true);
        $decrypted = $this->handler->decrypt($encrypted, $this->testKey, true);
        
        $this->assertSame($data, $decrypted);
    }

    public function testDecryptFailsWithWrongKey(): void
    {
        $data = 'secret';
        $encrypted = $this->handler->encrypt($data, $this->testKey);
        $wrongKey = 'wrongkey0123456789abcdef01234567';
        
        $decrypted = $this->handler->decrypt($encrypted, $wrongKey);
        $this->assertFalse($decrypted);
    }

    public function testDecryptFailsWhenTampered(): void
    {
        $data = 'tamper test';
        $encrypted = $this->handler->encrypt($data, $this->testKey);
        $tampered = substr($encrypted, 0, -2) . 'XX';
        
        $decrypted = $this->handler->decrypt($tampered, $this->testKey);
        $this->assertFalse($decrypted);
    }

    public function testSetCipherAndMode(): void
    {
        $this->handler->setCipher('aes-128');
        $this->handler->setMode('ctr');
        
        // Just verify no errors occur
        $data = 'test';
        $encrypted = $this->handler->encrypt($data, $this->testKey);
        $this->assertIsString($encrypted);
    }
}
