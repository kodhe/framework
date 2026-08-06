<?php

namespace Kodhe\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Unit test for Exception classes
 */
class ExceptionsTest extends TestCase
{
    public function testNotFoundException(): void
    {
        $exception = new \Kodhe\Framework\Exceptions\Http\NotFoundException('Resource not found');
        
        $this->assertEquals('Resource not found', $exception->getMessage());
        $this->assertEquals(404, $exception->getStatusCode());
    }

    public function testBadRequestException(): void
    {
        $exception = new \Kodhe\Framework\Exceptions\Http\BadRequestException('Invalid input');
        
        $this->assertEquals('Invalid input', $exception->getMessage());
        $this->assertEquals(400, $exception->getStatusCode());
    }

    public function testForbiddenException(): void
    {
        $exception = new \Kodhe\Framework\Exceptions\Http\ForbiddenException('Access denied');
        
        $this->assertEquals('Access denied', $exception->getMessage());
        $this->assertEquals(403, $exception->getStatusCode());
    }

    public function testUnauthorizedException(): void
    {
        $exception = new \Kodhe\Framework\Exceptions\Http\UnauthorizedException('Not authenticated');
        
        $this->assertEquals('Not authenticated', $exception->getMessage());
        $this->assertEquals(401, $exception->getStatusCode());
    }

    public function testMethodNotAllowedException(): void
    {
        $exception = new \Kodhe\Framework\Exceptions\Http\MethodNotAllowedException(['GET', 'POST']);
        
        $this->assertStringContainsString('Method Not Allowed', $exception->getMessage());
        $this->assertEquals(405, $exception->getStatusCode());
    }

    public function testInternalServerException(): void
    {
        $exception = new \Kodhe\Framework\Exceptions\Http\InternalServerException('Server error');
        
        $this->assertEquals('Server error', $exception->getMessage());
        $this->assertEquals(500, $exception->getStatusCode());
    }
}
