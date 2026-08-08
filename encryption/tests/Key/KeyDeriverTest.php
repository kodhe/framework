<?php

declare(strict_types=1);

namespace Kodhe\Framework\Encryption\Tests\Key;

use Kodhe\Framework\Encryption\Key\KeyDeriver;
use PHPUnit\Framework\TestCase;

/**
 * Class KeyDeriverTest
 *
 * Unit tests for HKDF key derivation
 *
 * @package     Kodhe\Encryption\Tests\Key
 */
class KeyDeriverTest extends TestCase
{
    private KeyDeriver $deriver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->deriver = new KeyDeriver();
    }

    public function testHkdfDeterministicOutput(): void
    {
        $result1 = $this->deriver->hkdf('master-key', 'sha256', 'salt', 32, 'info');
        $result2 = $this->deriver->hkdf('master-key', 'sha256', 'salt', 32, 'info');
        
        $this->assertSame($result1, $result2);
    }

    public function testHkdfDifferentLengths(): void
    {
        $result16 = $this->deriver->hkdf('key', 'sha256', 'salt', 16);
        $result32 = $this->deriver->hkdf('key', 'sha256', 'salt', 32);
        
        $this->assertEquals(16, strlen($result16));
        $this->assertEquals(32, strlen($result32));
    }

    public function testHkdfDefaultDigestSize(): void
    {
        // sha256 default output is 32 bytes
        $result = $this->deriver->hkdf('key', 'sha256', 'salt');
        $this->assertEquals(32, strlen($result));
    }

    public function testHkdfInvalidDigest(): void
    {
        $result = $this->deriver->hkdf('key', 'invalid-digest');
        $this->assertFalse($result);
    }

    public function testSupportedDigests(): void
    {
        $digests = $this->deriver->getSupportedDigests();
        
        $this->assertArrayHasKey('sha256', $digests);
        $this->assertArrayHasKey('sha512', $digests);
    }
}
