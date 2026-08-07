<?php

declare(strict_types=1);

namespace Kodhe\Pagination\Contracts;

/**
 * URL Builder Interface
 * 
 * Strategy pattern interface for different URL building strategies
 */
interface UrlBuilderInterface
{
    /**
     * Build a pagination URL
     * 
     * @param string $baseUrl Base URL
     * @param int|string $page Page number or offset
     * @param array $queryParams Query parameters
     * @return string Built URL
     */
    public function build(string $baseUrl, $page, array $queryParams = []): string;
    
    /**
     * Get current page from request
     * 
     * @param string $segment URI segment or query parameter name
     * @param bool $usePageNumbers Whether using page numbers or offset
     * @return int Current page number
     */
    public function getCurrentPage(string $segment, bool $usePageNumbers): int;
    
    /**
     * Set configuration
     * 
     * @param array $config Configuration options
     * @return void
     */
    public function setConfig(array $config): void;
}
