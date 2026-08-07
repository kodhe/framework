<?php

declare(strict_types=1);

namespace Kodhe\Pagination\Url;

use Kodhe\Pagination\Contracts\UrlBuilderInterface;

class QueryStringUrlBuilder implements UrlBuilderInterface
{
    /**
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * Build a query-string pagination URL.
     */
    public function build(
        string $baseUrl,
        $page,
        array $queryParams = []
    ): string {
        $baseUrl = rtrim($baseUrl, '/');

        $segment = (string) (
            $this->config['query_string_segment']
            ?? 'per_page'
        );

        $params = $queryParams;

        $params[$segment] = (int) $page;

        $query = http_build_query(
            $params,
            '',
            '&',
            PHP_QUERY_RFC3986
        );

        return $baseUrl . '?' . $query;
    }

    /**
     * Get current page.
     */
    public function getCurrentPage(
        string $segment,
        bool $usePageNumbers
    ): int {
        $page = isset($_GET[$segment])
            ? (int) $_GET[$segment]
            : 0;

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