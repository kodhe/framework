<?php

declare(strict_types=1);

namespace Kodhe\Pagination\Url;

use Kodhe\Pagination\Contracts\UrlBuilderInterface;

/**
 * URI Segment URL Builder
 * 
 * Builds URLs using URI segments (traditional CodeIgniter style)
 */
class SegmentUrlBuilder implements UrlBuilderInterface
{
    private string $baseUrl = '';
    private string $prefix = '';
    private string $suffix = '';
    private bool $reuseQueryString = false;
    private array $queryParams = [];
    
    /**
     * Build a pagination URL
     */
    public function build(string $baseUrl, $page, array $queryParams = []): string
    {
        $url = rtrim($baseUrl, '/');
        
        // Add prefix if exists
        if (!empty($this->prefix)) {
            $url .= '/' . $this->prefix;
        }
        
        // Add page number
        $url .= '/' . $page;
        
        // Add suffix if exists
        if (!empty($this->suffix)) {
            $url .= $this->suffix;
        }
        
        // Add query parameters
        if (!empty($queryParams)) {
            $separator = (strpos($url, '?') === false) ? '?' : '&';
            $url .= $separator . http_build_query($queryParams);
        }
        
        return $url;
    }
    
    public function getCurrentPage(string $segment, bool $usePageNumbers): int
    {
        // Default implementation returns 0
        // Concrete implementations should integrate with framework
        return 0;
    }
    
    public function setConfig(array $config): void
    {
        $this->baseUrl = $config['base_url'] ?? $this->baseUrl;
        $this->prefix = $config['prefix'] ?? $this->prefix;
        $this->suffix = $config['suffix'] ?? $this->suffix;
        $this->reuseQueryString = $config['reuse_query_string'] ?? $this->reuseQueryString;
        $this->queryParams = $config['query_params'] ?? $this->queryParams;
    }
    
    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }
    
    public function setSuffix(string $suffix): void
    {
        $this->suffix = $suffix;
    }
    
    public function setQueryParams(array $params): void
    {
        $this->queryParams = $params;
    }
}
