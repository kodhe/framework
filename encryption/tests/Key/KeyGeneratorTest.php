<?php

declare(strict_types=1);

namespace Kodhe\Encryption\Tests\Key;

use Kodhe\Encryption\Key\KeyGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Class KeyGeneratorTest
 *
 * Unit tests for random key generation
 *
 * @package     Kodhe\Encryption\Tests\Key
 */
class KeyGeneratorTest extends TestCase
{
    private KeyGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new KeyGenerator();
    }

    public function testGenerateCorrectLength(): void
    {
        $key = $this->generator->generate(32);
        
        $this->assertIsString($key);
        $this->assertEquals(32, strlen($key));
    }

    public function testGenerateDifferentKeys(): void
    {
        $key1 = $this->generator->generate(32);
        $key2 = $this->generator->generate(32);
        
        $this->assertNotSame($key1, $key2);
    }

    public function testGenerateHex(): void
    {
        $hex = $this->generator->generateHex(32);
        
        $this->assertIsString($hex);
        $this->assertEquals(64, strlen($hex)); // hex is 2x the byte length
        $this->assertTrue(ctype_xdigit($hex));
    }

    public function testGenerateBase64(): void
    {
        $base64 = $this->generator->generateBase64(32);
        
        $this->assertIsString($base64);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/]+=*$/', $base64);
    }
}
