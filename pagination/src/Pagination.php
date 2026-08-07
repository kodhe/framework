<?php

declare(strict_types=1);

namespace Kodhe\Pagination;

use Kodhe\Pagination\Contracts\RendererInterface;
use Kodhe\Pagination\Contracts\UrlBuilderInterface;
use Kodhe\Pagination\Factory\RendererFactory;
use Kodhe\Pagination\Support\LinkCache;
use Kodhe\Pagination\Support\AttributeHelper;
use Kodhe\Pagination\ValueObjects\LinkData;
use Kodhe\Pagination\ValueObjects\PaginationConfig;

class Pagination
{
    // CI3 Config Properties
    public $base_url = '';
    public $prefix = '';
    public $suffix = '';
    public $total_rows = 0;
    public $per_page = 10;
    public $num_links = 2;
    public $cur_page = 0;
    public $use_page_numbers = false;
    public $first_link = '&lsaquo; First';
    public $next_link = '&gt;';
    public $prev_link = '&lt;';
    public $last_link = 'Last &rsaquo;';
    public $uri_segment = 3;
    public $full_tag_open = '';
    public $full_tag_close = '';
    public $first_tag_open = '';
    public $first_tag_close = '';
    public $last_tag_open = '';
    public $last_tag_close = '';
    public $first_url = '';
    public $cur_tag_open = '<strong>';
    public $cur_tag_close = '</strong>';
    public $next_tag_open = '';
    public $next_tag_close = '';
    public $prev_tag_open = '';
    public $prev_tag_close = '';
    public $num_tag_open = '';
    public $num_tag_close = '';
    public $page_query_string = false;
    public $query_string_segment = 'per_page';
    public $display_pages = true;
    public $anchor_class = '';

    // Modular Properties
    protected RendererInterface $renderer;
    protected UrlBuilderInterface $urlBuilder;
    protected LinkCache $cache;
    protected bool $enable_cache = true;
    protected ?PaginationConfig $configObject = null;
    protected string $renderer_name = 'default';

    /**
     * Constructor.
     *
     * Compatible with:
     * new Pagination($config)
     */
    public function __construct($params = [])
    {
        $this->cache = new LinkCache();

        if (!empty($params)) {
            $this->initialize($params);
        }
    }

    /**
     * Initialize preferences.
     *
     * Compatible with CI3:
     * $this->pagination->initialize($config);
     */
    public function initialize($params = [])
    {
        if (is_array($params) && $params !== []) {
            foreach ($params as $key => $val) {
                if (property_exists($this, $key)) {
                    $this->$key = $val;
                }
            }
        }

        $this->normalizeAttributes();
        $this->setupComponents();

        if ($this->enable_cache) {
            $this->cache->clear();
        }

        return $this;
    }

    /**
     * Normalize renderer/tag attributes.
     */
    protected function normalizeAttributes(): void
    {
        // Kept for CI3 compatibility.
        // Attribute normalization is handled by AttributeHelper/Renderer.
    }

    /**
     * Setup renderer and URL builder strategies.
     */
    protected function setupComponents(): void
    {
        $rendererName = $this->renderer_name;

        $this->renderer = RendererFactory::make(
            is_string($rendererName) && $rendererName !== ''
                ? $rendererName
                : 'default',
            $this
        );

        if ($this->page_query_string === true) {
            $this->urlBuilder = new Url\QueryStringUrlBuilder($this);
        } else {
            $this->urlBuilder = new Url\SegmentUrlBuilder($this);
        }
    }

    /**
     * Build a URL through the configured URL builder.
     *
     * Keeping this in one place prevents the old one-argument
     * build($page) API from leaking into Pagination again.
     *
     * @param int|string $page
     * @param array<string,mixed> $queryParams
     */
    protected function buildUrl($page, array $queryParams = []): string
    {
        return $this->urlBuilder->build(
            (string) $this->base_url,
            $page,
            $queryParams
        );
    }

    /**
     * Generate pagination links.
     *
     * Compatible with CI3:
     * $this->pagination->create_links()
     */
    public function create_links()
    {
        if ((int) $this->total_rows <= 0) {
            return '';
        }

        $perPage = max(1, (int) $this->per_page);
        $totalPages = (int) ceil((int) $this->total_rows / $perPage);

        if ($totalPages <= 1) {
            $this->cur_page = 1;
            return '';
        }

        $this->cur_page = $this->getCurrentPage($totalPages);

        $cacheKey = $this->generateCacheKey();

        if ($this->enable_cache && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $links = $this->buildLinks($totalPages);
        $html = $this->renderer->render($links, $this);

        if ($this->enable_cache) {
            $this->cache->set($cacheKey, $html);
        }

        return $html;
    }

    /**
     * Get current page number.
     *
     * In offset mode:
     *   0  => page 1
     *   5  => page 2
     *   10 => page 3
     *
     * In page-number mode:
     *   1 => page 1
     *   2 => page 2
     */
    protected function getCurrentPage(int $totalPages): int
    {
        $page = 0;

        if ($this->page_query_string === true) {
            $value = $_GET[$this->query_string_segment] ?? null;

            if ($value !== null && $value !== '') {
                $page = (int) $value;
            }
        } else {
            // Preserve CodeIgniter 3 compatibility.
            if (function_exists('get_instance')) {
                $ci = get_instance();

                if (
                    $ci !== null &&
                    isset($ci->uri) &&
                    method_exists($ci->uri, 'segment')
                ) {
                    $segment = $ci->uri->segment($this->uri_segment);

                    if ($segment !== false && $segment !== '') {
                        $page = (int) $segment;
                    }
                }
            }
        }

        if ($this->use_page_numbers === true) {
            return max(1, min($page, $totalPages));
        }

        $perPage = max(1, (int) $this->per_page);

        if ($page < 0) {
            $page = 0;
        }

        return max(
            1,
            min(
                (int) floor($page / $perPage) + 1,
                $totalPages
            )
        );
    }

    /**
     * Build LinkData objects.
     */
    protected function buildLinks(int $totalPages): array
    {
        $links = [];
        $currentPage = (int) $this->cur_page;
        $perPage = max(1, (int) $this->per_page);

        // Current offset used by offset-based pagination.
        $currentOffset = ($currentPage - 1) * $perPage;

        /*
         * Previous
         */
        if ($currentPage > 1) {
            $prevValue = $this->use_page_numbers === true
                ? $currentPage - 1
                : $currentOffset - $perPage;

            $url = $this->buildUrl($prevValue);

            $links[] = new LinkData(
                (string) $this->prev_link,
                $url,
                false,
                false,
                false,
                'prev',
                0,
                [],
                AttributeHelper::normalize($this->prev_tag_open)
            );
        }

        /*
         * First
         */
        if (
            $this->display_pages &&
            $currentPage > ($this->num_links + 1)
        ) {
            $firstValue = $this->use_page_numbers === true ? 1 : 0;

            /*
             * CI3 supports an explicit first_url.
             * Respect it before asking the URL strategy to build the URL.
             */
            $url = !empty($this->first_url)
                ? (string) $this->first_url
                : $this->buildUrl($firstValue);

            $links[] = new LinkData(
                (string) $this->first_link,
                $url,
                false,
                false,
                false,
                'first',
                1,
                [],
                AttributeHelper::normalize($this->first_tag_open)
            );
        }

        /*
         * Numbered pages
         */
        $start = max(1, $currentPage - (int) $this->num_links);
        $end = min($totalPages, $currentPage + (int) $this->num_links);

        if ($start > 1) {
            $links[] = new LinkData(
                '&hellip;',
                null,
                false,
                true,
                true,
                'break'
            );
        }

        for ($i = $start; $i <= $end; $i++) {
            $isCurrent = ($i === $currentPage);

            $pageValue = $this->use_page_numbers === true
                ? $i
                : (($i - 1) * $perPage);

            $url = $isCurrent
                ? null
                : $this->buildUrl($pageValue);

            $tagOpen = $isCurrent
                ? $this->cur_tag_open
                : $this->num_tag_open;

            $tagClose = $isCurrent
                ? $this->cur_tag_close
                : $this->num_tag_close;

            $links[] = new LinkData(
                (string) $i,
                $url,
                $isCurrent,
                $isCurrent,
                false,
                'page',
                $i,
                [],
                AttributeHelper::normalize($tagOpen)
            );
        }

        if ($end < $totalPages) {
            $links[] = new LinkData(
                '&hellip;',
                null,
                false,
                true,
                true,
                'break'
            );
        }

        /*
         * Last
         */
        if (
            $this->display_pages &&
            $currentPage < ($totalPages - (int) $this->num_links)
        ) {
            $lastValue = $this->use_page_numbers === true
                ? $totalPages
                : (($totalPages - 1) * $perPage);

            $url = $this->buildUrl($lastValue);

            $links[] = new LinkData(
                (string) $this->last_link,
                $url,
                false,
                false,
                false,
                'last',
                $totalPages,
                [],
                AttributeHelper::normalize($this->last_tag_open)
            );
        }

        /*
         * Next
         */
        if ($currentPage < $totalPages) {
            $nextValue = $this->use_page_numbers === true
                ? $currentPage + 1
                : $currentOffset + $perPage;

            $url = $this->buildUrl($nextValue);

            $links[] = new LinkData(
                (string) $this->next_link,
                $url,
                false,
                false,
                false,
                'next',
                0,
                [],
                AttributeHelper::normalize($this->next_tag_open)
            );
        }

        return $links;
    }

    /**
     * Generate unique cache key.
     */
    protected function generateCacheKey(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';

        return md5(
            $uri .
            '_' . $this->base_url .
            '_' . $this->total_rows .
            '_' . $this->per_page .
            '_' . $this->num_links .
            '_' . $this->cur_page .
            '_' . (int) $this->use_page_numbers .
            '_' . (int) $this->page_query_string
        );
    }

    /**
     * Legacy compatibility placeholder.
     *
     * Parser functionality does not belong to Pagination.
     */
    public function parse_string($template, $data, $return = false)
    {
        return '';
    }
}
