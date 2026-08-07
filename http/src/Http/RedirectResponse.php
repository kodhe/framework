<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Http;

/**
 * Class RedirectResponse
 * 
 * Redirect response with CodeIgniter 3 compatibility
 */
class RedirectResponse extends Response
{
    protected string $targetUrl;
    
    public function __construct(
        string $url,
        int $status = 302,
        array $headers = []
    ) {
        $this->targetUrl = $url;
        $headers['Location'] = $url;
        
        parent::__construct($status, $headers);
    }
    
    public function getTargetUrl(): string
    {
        return $this->targetUrl;
    }
    
    public function withCookie(string $name, string $value, int $expire = 0): self
    {
        $this->setCookie($name, $value, $expire);
        return $this;
    }
}
