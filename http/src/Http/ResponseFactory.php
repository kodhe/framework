<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Http;

use Kodhe\Framework\Http\Contracts\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ResponseFactory
 * 
 * PSR-17 Response Factory implementation
 */
class ResponseFactory implements ResponseFactoryInterface
{
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new Response($code, [], '', '1.1', $reasonPhrase ?: $this->getReasonPhrase($code));
    }
    
    protected function getReasonPhrase(int $code): string
    {
        $phrases = [
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            204 => 'No Content',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            307 => 'Temporary Redirect',
            308 => 'Permanent Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Payload Too Large',
            414 => 'URI Too Long',
            415 => 'Unsupported Media Type',
            418 => "I'm a teapot",
            422 => 'Unprocessable Entity',
            425 => 'Too Early',
            429 => 'Too Many Requests',
            431 => 'Request Header Fields Too Large',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
        ];
        
        return $phrases[$code] ?? 'Unknown Status';
    }
}
