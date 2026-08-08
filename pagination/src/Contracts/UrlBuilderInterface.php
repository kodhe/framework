<?php

declare(strict_types=0);

namespace Kodhe\Framework\Pagination\Contracts;

/**
 * URL Builder Interface
 *
 * Strategy contract for generating pagination URLs.
 */
interface UrlBuilderInterface
{
    /**
     * Build a pagination URL.
     *
     * @param string $baseUrl
     * @param int|string $page
     * @param array<string, mixed> $queryParams
     */
    public function build(
        string $baseUrl,
        $page,
        array $queryParams = []
    ): string;

    /**
     * Get current page from the request/context.
     *
     * @param string $segment
     * @param bool $usePageNumbers
     */
    public function getCurrentPage(
        string $segment,
        bool $usePageNumbers
    ): int;

    /**
     * Configure the URL builder.
     *
     * @param array<string, mixed> $config
     */
    public function setConfig(array $config): void;
}