<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Http;

use GuzzleHttp\Psr7\Uri as Psr7Uri;

/**
 * Class Uri
 * 
 * PSR-7 URI implementation with CodeIgniter 3 extensions
 */
class Uri extends Psr7Uri
{
    protected array $segments = [];
    
    public function __construct(string $uri = '')
    {
        parent::__construct($uri);
        $this->segments = $this->parseSegments();
    }
    
    protected function parseSegments(): array
    {
        $path = $this->getPath();
        $segments = explode('/', trim($path, '/'));
        return array_values(array_filter($segments, fn($s) => $s !== ''));
    }
    
    public function getSegment(int $index, string $default = ''): string
    {
        $index = $index - 1; // CI uses 1-based index
        return $this->segments[$index] ?? $default;
    }
    
    public function getSegments(): array
    {
        return $this->segments;
    }
    
    public function getTotalSegments(): int
    {
        return count($this->segments);
    }
    
    public function getBaseUri(): string
    {
        $scheme = $this->getScheme();
        $host = $this->getHost();
        $port = $this->getPort();
        
        $base = "{$scheme}://{$host}";
        
        if ($port && !in_array($port, ['80', '443'])) {
            $base .= ":{$port}";
        }
        
        return $base;
    }
    
    public function getRelativeUri(): string
    {
        return $this->getPath() . ($this->getQuery() ? '?' . $this->getQuery() : '');
    }
}
