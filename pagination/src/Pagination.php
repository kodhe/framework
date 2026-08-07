<?php

declare(strict_types=1);

namespace Kodhe\Pagination;

use Kodhe\Pagination\Contracts\RendererInterface;
use Kodhe\Pagination\Contracts\UrlBuilderInterface;
use Kodhe\Pagination\Factory\RendererFactory;
use Kodhe\Pagination\Support\LinkCache;
use Kodhe\Pagination\Url\SegmentUrlBuilder;
use Kodhe\Pagination\Url\QueryStringUrlBuilder;
use Kodhe\Pagination\ValueObjects\LinkData;
use Kodhe\Pagination\Renderers\DefaultRenderer;

/**
 * Pagination Class - Refactored Version
 * 
 * Maintains backward compatible API with modular architecture
 * 
 * @package Kodhe\Pagination
 */
class Pagination
{
    protected string $base_url = '';
    protected string $prefix = '';
    protected string $suffix = '';
    protected int $total_rows = 0;
    protected int $num_links = 2;
    public int $per_page = 10;
    public int $cur_page = 0;
    protected bool $use_page_numbers = false;
    protected $first_link = '&lsaquo; First';
    protected $next_link = '&gt;';
    protected $prev_link = '&lt;';
    protected $last_link = 'Last &rsaquo;';
    protected int $uri_segment = 0;
    protected bool $page_query_string = false;
    protected string $query_string_segment = 'per_page';
    protected bool $reuse_query_string = false;
    protected bool $display_pages = true;
    protected string $first_url = '';
    protected bool $use_global_url_suffix = false;
    protected string $full_tag_open = '';
    protected string $full_tag_close = '';
    protected string $first_tag_open = '';
    protected string $first_tag_close = '';
    protected string $last_tag_open = '';
    protected string $last_tag_close = '';
    protected string $cur_tag_open = '<strong>';
    protected string $cur_tag_close = '</strong>';
    protected string $next_tag_open = '';
    protected string $next_tag_close = '';
    protected string $prev_tag_open = '';
    protected string $prev_tag_close = '';
    protected string $num_tag_open = '';
    protected string $num_tag_close = '';
    protected string $_attributes = '';
    protected array $_link_types = [];
    protected string $data_page_attr = 'data-ci-pagination-page';
    protected ?RendererInterface $renderer = null;
    protected ?UrlBuilderInterface $urlBuilder = null;
    protected bool $enable_cache = true;
    protected $CI;

    public function __construct($params = [])
    {
        $this->CI = kodhe();
        if (method_exists($this->CI->lang, 'line')) {
            $this->CI->load->language('pagination');
            foreach (['first_link', 'next_link', 'prev_link', 'last_link'] as $key) {
                if (($val = $this->CI->lang->line('pagination_' . $key)) !== false) {
                    $this->$key = $val;
                }
            }
        }
        if (!isset($params['attributes'])) {
            $params['attributes'] = [];
        }
        $this->initialize($params);
        if (function_exists('log_message')) {
            log_message('info', 'Pagination Class Initialized');
        }
    }

    public function initialize(array $params = []): Pagination
    {
        if (isset($params['attributes']) && is_array($params['attributes'])) {
            $this->_parse_attributes($params['attributes']);
            unset($params['attributes']);
        }
        if (isset($params['anchor_class'])) {
            if (!empty($params['anchor_class'])) {
                $params['attributes']['class'] = $params['anchor_class'];
            }
            unset($params['anchor_class']);
        }
        if (isset($params['renderer'])) {
            $this->setRenderer($params['renderer']);
            unset($params['renderer']);
        }
        if (isset($params['enable_cache'])) {
            $this->enable_cache = (bool) $params['enable_cache'];
            unset($params['enable_cache']);
        }
        foreach ($params as $key => $val) {
            if (property_exists($this, $key)) {
                $this->$key = $val;
            }
        }
        if ($this->CI->config->item('enable_query_strings') === true) {
            $this->page_query_string = true;
        }
        if ($this->use_global_url_suffix === true) {
            $this->suffix = $this->CI->config->item('url_suffix');
        }
        return $this;
    }

    public function create_links(): string
    {
        if ($this->total_rows == 0 || $this->per_page == 0) {
            return '';
        }
        $num_pages = (int) ceil($this->total_rows / $this->per_page);
        if ($num_pages === 1) {
            return '';
        }
        $this->num_links = (int) $this->num_links;
        if ($this->num_links < 0) {
            show_error('Your number of links must be a non-negative number.');
        }
        if ($this->enable_cache) {
            $cacheKey = $this->generateCacheKey();
            $cached = LinkCache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }
        $get = $this->reuse_query_string ? $this->CI->input->get() : [];
        unset($get['c'], $get['m'], $get[$this->query_string_segment]);
        $base_url = trim($this->base_url);
        $first_url = $this->first_url;
        $query_string_sep = (strpos($base_url, '?') === false) ? '?' : '&amp;';
        $query_string = ''; // Initialize query_string to avoid undefined variable error
        
        if ($this->page_query_string === true) {
            if ($first_url === '') {
                $first_url = $base_url;
                if (!empty($get)) {
                    $first_url .= $query_string_sep . http_build_query($get);
                }
            }
            $base_url .= $query_string_sep . http_build_query(array_merge($get, [$this->query_string_segment => '']));
        } else {
            if (!empty($get)) {
                $query_string = $query_string_sep . http_build_query($get);
                $this->suffix .= $query_string;
            }
            if ($this->reuse_query_string === true && ($base_query_pos = strpos($base_url, '?')) !== false) {
                $base_url = substr($base_url, 0, $base_query_pos);
            }
            if ($first_url === '') {
                $first_url = $base_url . $query_string;
            }
            $base_url = rtrim($base_url, '/') . '/';
        }
        $base_page = $this->use_page_numbers ? 1 : 0;
        if ($this->page_query_string === true) {
            $this->cur_page = (int) $this->CI->input->get($this->query_string_segment);
        } elseif (empty($this->cur_page)) {
            if ($this->uri_segment === 0) {
                $this->uri_segment = count($this->CI->uri->segment_array());
            }
            $segment_value = $this->CI->uri->segment($this->uri_segment);
            $this->cur_page = (int) ($segment_value ?: 0);
            if ($this->prefix !== '' || $this->suffix !== '') {
                $cleaned = str_replace([$this->prefix, $this->suffix], '', (string) $segment_value);
                $this->cur_page = (int) ($cleaned ?: 0);
            }
        }
        
        // Ensure cur_page is always an integer
        if (!ctype_digit((string) $this->cur_page) || ($this->use_page_numbers && (int) $this->cur_page === 0)) {
            $this->cur_page = $base_page;
        } else {
            $this->cur_page = (int) $this->cur_page;
        }
        if ($this->use_page_numbers) {
            if ($this->cur_page > $num_pages) {
                $this->cur_page = $num_pages;
            }
        } elseif ($this->cur_page > $this->total_rows) {
            $this->cur_page = ($num_pages - 1) * $this->per_page;
        }
        $uri_page_number = $this->cur_page;
        if (!$this->use_page_numbers) {
            $this->cur_page = (int) floor(($this->cur_page / $this->per_page) + 1);
        }
        $start = (($this->cur_page - $this->num_links) > 0) ? $this->cur_page - ($this->num_links - 1) : 1;
        $end = (($this->cur_page + $this->num_links) < $num_pages) ? $this->cur_page + $this->num_links : $num_pages;
        $links = $this->buildLinks($base_url, $first_url, $num_pages, $start, $end, $uri_page_number, $base_page);
        $output = $this->renderLinks($links);
        $output = $this->full_tag_open . $output . $this->full_tag_close;
        $output = preg_replace('#([^:])//+#', '\1/', $output);
        if ($this->enable_cache && isset($cacheKey)) {
            LinkCache::set($cacheKey, $output);
        }
        return $output;
    }

    protected function buildLinks(string $base_url, string $first_url, int $num_pages, int $start, int $end, int $uri_page_number, int $base_page): array
    {
        $links = [];
        if ($this->first_link !== false && $this->cur_page > ($this->num_links + 1 + !$this->num_links)) {
            $attributes = $this->buildAttributes(1);
            $links[] = new LinkData((string) $this->first_link, $first_url, false, true, false, false, false, 1, $this->parseRelAttr('start', $attributes));
        }
        if ($this->prev_link !== false && $this->cur_page !== 1) {
            $i = $this->use_page_numbers ? $uri_page_number - 1 : $uri_page_number - $this->per_page;
            $attributes = $this->buildAttributes($this->cur_page - 1);
            $url = ($i === $base_page) ? $first_url : $base_url . $this->prefix . $i . $this->suffix;
            $links[] = new LinkData((string) $this->prev_link, $url, false, false, false, true, false, $this->cur_page - 1, $this->parseRelAttr('prev', $attributes));
        }
        if ($this->display_pages !== false) {
            for ($loop = $start - 1; $loop <= $end; $loop++) {
                $i = $this->use_page_numbers ? $loop : ($loop * $this->per_page) - $this->per_page;
                $attributes = $this->buildAttributes($loop);
                if ($i >= $base_page) {
                    if ($this->cur_page === $loop) {
                        $links[] = new LinkData((string) $loop, '', true, false, false, false, false, $loop);
                    } elseif ($i === $base_page) {
                        $links[] = new LinkData((string) $loop, $first_url, false, false, false, false, false, $loop, $this->parseRelAttr('start', $attributes));
                    } else {
                        $links[] = new LinkData((string) $loop, $base_url . $this->prefix . $i . $this->suffix, false, false, false, false, false, $loop, $attributes);
                    }
                }
            }
        }
        if ($this->next_link !== false && $this->cur_page < $num_pages) {
            $i = $this->use_page_numbers ? $this->cur_page + 1 : $this->cur_page * $this->per_page;
            $attributes = $this->buildAttributes($this->cur_page + 1);
            $links[] = new LinkData((string) $this->next_link, $base_url . $this->prefix . $i . $this->suffix, false, false, false, false, true, $this->cur_page + 1, $this->parseRelAttr('next', $attributes));
        }
        if ($this->last_link !== false && ($this->cur_page + $this->num_links + !$this->num_links) < $num_pages) {
            $i = $this->use_page_numbers ? $num_pages : ($num_pages * $this->per_page) - $this->per_page;
            $attributes = $this->buildAttributes($num_pages);
            $links[] = new LinkData((string) $this->last_link, $base_url . $this->prefix . $i . $this->suffix, false, false, true, false, false, $num_pages, $attributes);
        }
        return $links;
    }

    protected function renderLinks(array $links): string
    {
        $renderer = $this->getRenderer();
        return $renderer->render($links);
    }

    protected function buildAttributes(int $page): array
    {
        $attrs = [];
        
        // Parse existing string attributes to array
        if (!empty($this->_attributes)) {
            preg_match_all('/(\w+)="([^"]*)"/', $this->_attributes, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $attrs[$match[1]] = $match[2];
            }
        }
        
        // Add data-page attribute
        $attrs[$this->data_page_attr] = (string) $page;
        
        return $attrs;
    }

    protected function parseRelAttr(string $type, array $attributes): array
    {
        $result = $attributes;
        
        if (isset($this->_link_types[$type])) {
            unset($this->_link_types[$type]);
            $result['rel'] = $type;
        }
        return $result;
    }

    protected function _parse_attributes($attributes): void
    {
        isset($attributes['rel']) OR $attributes['rel'] = true;
        $this->_link_types = $attributes['rel'] ? ['start' => 'start', 'prev' => 'prev', 'next' => 'next'] : [];
        unset($attributes['rel']);
        $this->_attributes = '';
        foreach ($attributes as $key => $value) {
            $this->_attributes .= ' ' . $key . '="' . $value . '"';
        }
    }

    protected function _attr_rel(string $type): string
    {
        if (isset($this->_link_types[$type])) {
            unset($this->_link_types[$type]);
            return ' rel="' . $type . '"';
        }
        return '';
    }

    public function setRenderer($renderer): void
    {
        if ($renderer instanceof RendererInterface) {
            $this->renderer = $renderer;
        } elseif (is_string($renderer) || is_array($renderer)) {
            $this->renderer = RendererFactory::make($renderer);
        }
        if ($this->renderer instanceof DefaultRenderer) {
            $this->renderer->setConfig([
                'cur_tag_open' => $this->cur_tag_open,
                'cur_tag_close' => $this->cur_tag_close,
                'num_tag_open' => $this->num_tag_open,
                'num_tag_close' => $this->num_tag_close,
                'first_tag_open' => $this->first_tag_open,
                'first_tag_close' => $this->first_tag_close,
                'last_tag_open' => $this->last_tag_open,
                'last_tag_close' => $this->last_tag_close,
                'next_tag_open' => $this->next_tag_open,
                'next_tag_close' => $this->next_tag_close,
                'prev_tag_open' => $this->prev_tag_open,
                'prev_tag_close' => $this->prev_tag_close,
            ]);
        }
    }

    public function getRenderer(): RendererInterface
    {
        if ($this->renderer === null) {
            $this->renderer = new DefaultRenderer();
            $this->renderer->setConfig([
                'cur_tag_open' => $this->cur_tag_open,
                'cur_tag_close' => $this->cur_tag_close,
                'num_tag_open' => $this->num_tag_open,
                'num_tag_close' => $this->num_tag_close,
            ]);
        }
        return $this->renderer;
    }

    public function setUrlBuilder(UrlBuilderInterface $builder): void
    {
        $this->urlBuilder = $builder;
    }

    public function getUrlBuilder(): UrlBuilderInterface
    {
        if ($this->urlBuilder === null) {
            $this->urlBuilder = $this->page_query_string ? new QueryStringUrlBuilder() : new SegmentUrlBuilder();
        }
        return $this->urlBuilder;
    }

    public function enableCache(bool $enable = true): void
    {
        $this->enable_cache = $enable;
    }

    public function clearCache(): void
    {
        LinkCache::clear();
    }

    protected function generateCacheKey(): string
    {
        return LinkCache::generateKey([
            'base_url' => $this->base_url,
            'total_rows' => $this->total_rows,
            'per_page' => $this->per_page,
            'cur_page' => $this->cur_page,
            'num_links' => $this->num_links,
            'tags' => ['cur_tag_open' => $this->cur_tag_open, 'cur_tag_close' => $this->cur_tag_close]
        ]);
    }
}
