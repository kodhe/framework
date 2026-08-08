<?php

declare(strict_types=0);

namespace Kodhe\Framework\Pagination\Support;

/**
 * Pagination Builder
 * 
 * Fluent interface for building pagination configuration
 */
class PaginationBuilder
{
    private array $config = [];
    
    public function baseUrl(string $url): self
    {
        $this->config['base_url'] = $url;
        return $this;
    }
    
    public function totalRows(int $count): self
    {
        $this->config['total_rows'] = $count;
        return $this;
    }
    
    public function perPage(int $count): self
    {
        $this->config['per_page'] = $count;
        return $this;
    }
    
    public function numLinks(int $count): self
    {
        $this->config['num_links'] = $count;
        return $this;
    }
    
    public function curPage(int $page): self
    {
        $this->config['cur_page'] = $page;
        return $this;
    }
    
    public function usePageNumbers(bool $use = true): self
    {
        $this->config['use_page_numbers'] = $use;
        return $this;
    }
    
    public function prefix(string $prefix): self
    {
        $this->config['prefix'] = $prefix;
        return $this;
    }
    
    public function suffix(string $suffix): self
    {
        $this->config['suffix'] = $suffix;
        return $this;
    }
    
    public function firstUrl(string $url): self
    {
        $this->config['first_url'] = $url;
        return $this;
    }
    
    public function uriSegment(int $segment): self
    {
        $this->config['uri_segment'] = $segment;
        return $this;
    }
    
    public function pageQueryString(bool $use = true): self
    {
        $this->config['page_query_string'] = $use;
        return $this;
    }
    
    public function queryStringSegment(string $segment): self
    {
        $this->config['query_string_segment'] = $segment;
        return $this;
    }
    
    public function reuseQueryString(bool $use = true): self
    {
        $this->config['reuse_query_string'] = $use;
        return $this;
    }
    
    public function displayPages(bool $display = true): self
    {
        $this->config['display_pages'] = $display;
        return $this;
    }
    
    public function links(string $first, string $prev, string $next, string $last): self
    {
        $this->config['first_link'] = $first;
        $this->config['prev_link'] = $prev;
        $this->config['next_link'] = $next;
        $this->config['last_link'] = $last;
        return $this;
    }
    
    public function tags(array $tags): self
    {
        foreach ($tags as $key => $value) {
            $this->config[$key] = $value;
        }
        return $this;
    }
    
    public function attributes(array $attrs): self
    {
        $this->config['attributes'] = $attrs;
        return $this;
    }
    
    public function renderer($renderer): self
    {
        $this->config['renderer'] = $renderer;
        return $this;
    }
    
    public function getConfig(): array
    {
        return $this->config;
    }
    
    public function build(): array
    {
        return $this->config;
    }
}
