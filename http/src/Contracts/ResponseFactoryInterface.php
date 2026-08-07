<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Contracts;

use Psr\Http\Message\ResponseInterface;

/**
 * Interface ResponseFactoryInterface
 * 
 * PSR-17 Response Factory Interface
 */
interface ResponseFactoryInterface
{
    /**
     * Create a new response
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface;
}
