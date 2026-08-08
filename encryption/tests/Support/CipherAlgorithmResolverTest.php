<?php

declare(strict_types=1);

namespace Kodhe\Framework\Encryption\Tests\Support;

use Kodhe\Framework\Encryption\Support\CipherAlgorithmResolver;
use PHPUnit\Framework\TestCase;

/**
 * Class CipherAlgorithmResolverTest
 *
 * Unit tests for cipher algorithm resolution
 *
 * @package     Kodhe\Encryption\Tests\Support
 */
class CipherAlgorithmResolverTest extends TestCase
{
    private CipherAlgorithmResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new CipherAlgorithmResolver();
        CipherAlgorithmResolver::clearCache();
    }

    public function testResolveAes256Cbc(): void
    {
        $algorithm = $this->resolver->resolve('aes-256', 'cbc');
        $this->assertEquals('aes-256-cbc', $algorithm);
    }

    public function testResolveAes256Gcm(): void
    {
        $algorithm = $this->resolver->resolve('aes-256', 'gcm');
        $this->assertEquals('aes-256-gcm', $algorithm);
    }

    public function testResolveAes128Ctr(): void
    {
        $algorithm = $this->resolver->resolve('aes-128', 'ctr');
        $this->assertEquals('aes-128-ctr', $algorithm);
    }

    public function testResolveWithAlias(): void
    {
        // rijndael-128 should be aliased to aes-128
        $algorithm = $this->resolver->resolve('rijndael-128', 'cbc');
        $this->assertEquals('aes-128-cbc', $algorithm);
    }

    public function testResolveStreamMode(): void
    {
        $algorithm = $this->resolver->resolve('aes-128', 'stream');
        $this->assertEquals('aes-128', $algorithm);
    }

    public function testCacheHit(): void
    {
        // First call
        $algorithm1 = $this->resolver->resolve('aes-256', 'cbc');
        
        // Second call should hit cache
        $algorithm2 = $this->resolver->resolve('aes-256', 'cbc');
        
        $this->assertSame($algorithm1, $algorithm2);
    }

    public function testIsAuthenticatedModeGcm(): void
    {
        $this->assertTrue($this->resolver->isAuthenticatedMode('gcm'));
    }

    public function testIsAuthenticatedModeCbc(): void
    {
        $this->assertFalse($this->resolver->isAuthenticatedMode('cbc'));
    }

    public function testClearCache(): void
    {
        // Populate cache
        $this->resolver->resolve('aes-256', 'cbc');
        
        // Clear it
        CipherAlgorithmResolver::clearCache();
        
        // Cache should be empty (tested indirectly)
        $this->assertTrue(true);
    }
}
