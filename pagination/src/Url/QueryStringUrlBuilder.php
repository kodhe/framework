<?php

declare(strict_types=1);

namespace Kodhe\Pagination\Url;

use Kodhe\Pagination\Contracts\UrlBuilderInterface;

/**
 * Query String URL Builder
 * 
 * Builds URLs using query strings for pagination
 */
class QueryStringUrlBuilder implements UrlBuilderInterface
{
    private string $baseUrl = '';
    private string $queryStringSegment = 'per_page';
    private bool $reuseQueryString = false;
    private array $queryParams = [];
    
    /**
     * Build a pagination URL
     */
    public function build(string $baseUrl, $page, array $queryParams = []): string
    {
        // Start with base URL
        $url = $baseUrl;
        
        // Determine query string separator
        $separator = (strpos($baseUrl, '?') === false) ? '?' : '&';
        
        // Merge with existing query params
        $params = array_merge($this->queryParams, $queryParams);
        
        // Add page parameter
        $params[$this->queryStringSegment] = $page;
        
        // Build query string
        $url .= $separator . http_build_query($params);
        
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
        $this->queryStringSegment = $config['query_string_segment'] ?? $this->queryStringSegment;
        $this->reuseQueryString = $config['reuse_query_string'] ?? $this->reuseQueryString;
        $this->queryParams = $config['query_params'] ?? $this->queryParams;
    }
    
    public function setQueryStringSegment(string $segment): void
    {
        $this->queryStringSegment = $segment;
    }
    
    public function setQueryParams(array $params): void
    {
        $this->queryParams = $params;
    }
}
