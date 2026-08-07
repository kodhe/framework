<?php

declare(strict_types=1);

namespace Kodhe\Encryption\Tests;

use Kodhe\Encryption\Encryption;
use PHPUnit\Framework\TestCase;

/**
 * Class EncryptionTest
 *
 * Unit tests for the main Encryption class
 *
 * @package     Kodhe\Encryption\Tests
 * @author      Your Name
 * @version     2.0.0
 */
class EncryptionTest extends TestCase
{
    private Encryption $encryption;
    private string $testKey;

    protected function setUp(): void
    {
        parent::setUp();
        // Use a 32-byte key for AES-256
        $this->testKey = '0123456789abcdef0123456789abcdef';
        $this->encryption = new Encryption([
            'cipher' => 'aes-256',
            'mode' => 'cbc',
            'key' => $this->testKey,
        ]);
    }

    public function testEncryptDecryptRoundTripCbc(): void
    {
        $data = 'halo dunia';
        $encrypted = $this->encryption->encrypt($data);
        
        $this->assertNotSame($data, $encrypted);
        $this->assertIsString($encrypted);
        
        $decrypted = $this->encryption->decrypt($encrypted);
        $this->assertSame($data, $decrypted);
    }

    public function testEncryptDecryptRoundTripGcm(): void
    {
        $this->encryption = new Encryption([
            'cipher' => 'aes-256',
            'mode' => 'gcm',
            'key' => $this->testKey,
        ]);

        $data = 'data sensitif';
        $encrypted = $this->encryption->encrypt($data);
        
        $this->assertNotSame($data, $encrypted);
        $this->assertIsString($encrypted);
        
        $decrypted = $this->encryption->decrypt($encrypted);
        $this->assertSame($data, $decrypted);
    }

    public function testDecryptFailsWhenTampered(): void
    {
        $data = 'rahasia';
        $encrypted = $this->encryption->encrypt($data);
        
        // Tamper with the encrypted data (modify last 2 chars)
        $tampered = substr($encrypted, 0, -2) . 'zz';
        
        $decrypted = $this->encryption->decrypt($tampered);
        $this->assertFalse($decrypted);
    }

    public function testCreateKeyReturnsCorrectLength(): void
    {
        $length = 32;
        $key = $this->encryption->create_key($length);
        
        $this->assertIsString($key);
        $this->assertEquals($length, strlen($key));
    }

    public function testHkdfIsDeterministicForSameInput(): void
    {
        $derived1 = $this->encryption->hkdf('master-key', 'sha256', 'salt', 32);
        $derived2 = $this->encryption->hkdf('master-key', 'sha256', 'salt', 32);
        
        $this->assertIsString($derived1);
        $this->assertSame($derived1, $derived2);
    }

    public function testEncryptDecryptWithRawData(): void
    {
        $data = 'binary data test';
        $encrypted = $this->encryption->encrypt($data, ['raw_data' => true]);
        
        $this->assertIsString($encrypted);
        
        $decrypted = $this->encryption->decrypt($encrypted, ['raw_data' => true]);
        $this->assertSame($data, $decrypted);
    }

    public function testInitializeChangesCipherAndMode(): void
    {
        $this->encryption->initialize([
            'cipher' => 'aes-128',
            'mode' => 'ctr',
        ]);

        $this->assertEquals('aes-128', $this->encryption->get_cipher());
        $this->assertEquals('ctr', $this->encryption->get_mode());
    }

    public function testEncryptEmptyString(): void
    {
        $data = '';
        $encrypted = $this->encryption->encrypt($data);
        $decrypted = $this->encryption->decrypt($encrypted);
        
        $this->assertSame($data, $decrypted);
    }

    public function testEncryptLongString(): void
    {
        $data = str_repeat('This is a long string. ', 100);
        $encrypted = $this->encryption->encrypt($data);
        $decrypted = $this->encryption->decrypt($encrypted);
        
        $this->assertSame($data, $decrypted);
    }

    public function testDecryptWithWrongKey(): void
    {
        $data = 'secret message';
        $encrypted = $this->encryption->encrypt($data);
        
        // Create new instance with different key
        $wrongEncryption = new Encryption([
            'cipher' => 'aes-256',
            'mode' => 'cbc',
            'key' => 'wrongkey0123456789abcdef01234567',
        ]);
        
        $decrypted = $wrongEncryption->decrypt($encrypted);
        $this->assertFalse($decrypted);
    }

    public function testEncryptManyBatchOperation(): void
    {
        $items = ['item1', 'item2', 'item3'];
        $encrypted = $this->encryption->encrypt_many($items);
        
        $this->assertIsArray($encrypted);
        $this->assertCount(3, $encrypted);
        
        $decrypted = $this->encryption->decrypt_many($encrypted);
        $this->assertSame($items, $decrypted);
    }
}
