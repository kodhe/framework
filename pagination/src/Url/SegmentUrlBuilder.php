<?php

declare(strict_types=0);

namespace Kodhe\Framework\Pagination\Url;

use Kodhe\Framework\Pagination\Contracts\UrlBuilderInterface;

class SegmentUrlBuilder implements UrlBuilderInterface
{
    /**
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * Build a pagination URL.
     */
    public function build(
        string $baseUrl,
        $page,
        array $queryParams = []
    ): string {
        $baseUrl = rtrim($baseUrl, '/');

        $prefix = (string) ($this->config['prefix'] ?? '');
        $suffix = (string) ($this->config['suffix'] ?? '');

        $url = $baseUrl . '/' . $prefix . (int) $page . $suffix;

        if ($queryParams !== []) {
            $query = http_build_query(
                $queryParams,
                '',
                '&',
                PHP_QUERY_RFC3986
            );

            if ($query !== '') {
                $url .= '?' . $query;
            }
        }

        return $url;
    }

    /**
     * Get current page.
     */
    public function getCurrentPage(
        string $segment,
        bool $usePageNumbers
    ): int {
        $page = 0;

        if (isset($_GET[$segment])) {
            $page = (int) $_GET[$segment];
        }

        if ($usePageNumbers) {
            return max(1, $page);
        }

        return max(0, $page);
    }

    /**
     * Set configuration.
     *
     * @param array<string, mixed> $config
     */
    public function setConfig(array $config): void
    {
        $this->config = array_merge(
            $this->config,
            $config
        );
    }
}