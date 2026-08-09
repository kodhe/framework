<?php

declare(strict_types=0);

namespace Kodhe\Framework\Pagination\ValueObjects;

/**
 * Pagination Configuration Value Object
 */
class PaginationConfig
{
    private string $baseUrl;
    private int $totalRows;
    private int $perPage;
    private int $numLinks;
    private bool $usePageNumbers;
    private string $prefix;
    private string $suffix;
    private string $firstUrl;
    private int $uriSegment;
    private bool $pageQueryString;
    private string $queryStringSegment;
    private bool $reuseQueryString;
    private bool $displayPages;
    
    // Links text
    private $firstLink;
    private $nextLink;
    private $prevLink;
    private $lastLink;
    
    // HTML tags
    private array $tags;
    
    // Attributes
    private array $attributes;
    
    public function __construct(array $config = [])
    {
        $this->baseUrl = $config['base_url'] ?? '';
        $this->totalRows = (int) ($config['total_rows'] ?? 0);
        $this->perPage = (int) ($config['per_page'] ?? 10);
        $this->numLinks = (int) ($config['num_links'] ?? 2);
        $this->usePageNumbers = (bool) ($config['use_page_numbers'] ?? false);
        $this->prefix = $config['prefix'] ?? '';
        $this->suffix = $config['suffix'] ?? '';
        $this->firstUrl = $config['first_url'] ?? '';
        $this->uriSegment = (int) ($config['uri_segment'] ?? 0);
        $this->pageQueryString = (bool) ($config['page_query_string'] ?? false);
        $this->queryStringSegment = $config['query_string_segment'] ?? 'per_page';
        $this->reuseQueryString = (bool) ($config['reuse_query_string'] ?? false);
        $this->displayPages = (bool) ($config['display_pages'] ?? true);
        
        $this->firstLink = $config['first_link'] ?? '&lsaquo; First';
        $this->nextLink = $config['next_link'] ?? '&gt;';
        $this->prevLink = $config['prev_link'] ?? '&lt;';
        $this->lastLink = $config['last_link'] ?? 'Last &rsaquo;';
        
        $this->tags = [
            'full_tag_open' => $config['full_tag_open'] ?? '',
            'full_tag_close' => $config['full_tag_close'] ?? '',
            'first_tag_open' => $config['first_tag_open'] ?? '',
            'first_tag_close' => $config['first_tag_close'] ?? '',
            'last_tag_open' => $config['last_tag_open'] ?? '',
            'last_tag_close' => $config['last_tag_close'] ?? '',
            'cur_tag_open' => $config['cur_tag_open'] ?? '<strong>',
            'cur_tag_close' => $config['cur_tag_close'] ?? '</strong>',
            'next_tag_open' => $config['next_tag_open'] ?? '',
            'next_tag_close' => $config['next_tag_close'] ?? '',
            'prev_tag_open' => $config['prev_tag_open'] ?? '',
            'prev_tag_close' => $config['prev_tag_close'] ?? '',
            'num_tag_open' => $config['num_tag_open'] ?? '',
            'num_tag_close' => $config['num_tag_close'] ?? '',
        ];
        $this->attributes = $config['attributes'] ?? [];
    }
    
    private int $curPage = 0;
    
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
    
    public function getTotalRows(): int
    {
        return $this->totalRows;
    }
    
    public function getPerPage(): int
    {
        return $this->perPage;
    }
    
    public function getNumLinks(): int
    {
        return $this->numLinks;
    }
    
    public function usePageNumbers(): bool
    {
        return $this->usePageNumbers;
    }
    
    public function getPrefix(): string
    {
        return $this->prefix;
    }
    
    public function getSuffix(): string
    {
        return $this->suffix;
    }
    
    public function getFirstUrl(): string
    {
        return $this->firstUrl;
    }
    
    public function getUriSegment(): int
    {
        return $this->uriSegment;
    }
    
    public function isPageQueryString(): bool
    {
        return $this->pageQueryString;
    }
    
    public function getQueryStringSegment(): string
    {
        return $this->queryStringSegment;
    }
    
    public function reuseQueryString(): bool
    {
        return $this->reuseQueryString;
    }
    
    public function displayPages(): bool
    {
        return $this->displayPages;
    }
    
    public function getFirstLink()
    {
        return $this->firstLink;
    }
    
    public function getNextLink()
    {
        return $this->nextLink;
    }
    
    public function getPrevLink()
    {
        return $this->prevLink;
    }
    
    public function getLastLink()
    {
        return $this->lastLink;
    }
    
    public function getTag(string $name, string $default = ''): string
    {
        return $this->tags[$name] ?? $default;
    }
    
    public function getAttributes(): array
    {
        return $this->attributes;
    }
    
    public function getCurrentPage(): int
    {
        return $this->curPage;
    }
    
    public function setCurrentPage(int $curPage): self
    {
        $new = clone $this;
        $new->curPage = $curPage;
        return $new;
    }
    
    public function getAllTags(): array
    {
        return $this->tags;
    }
}
