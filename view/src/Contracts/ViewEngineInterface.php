<?php

namespace Kodhe\Framework\View\Contracts;

/**
 * Interface ViewEngineInterface
 *
 * @package Kodhe\Framework\View\Contracts
 */
interface ViewEngineInterface
{
    /**
     * Render a view
     *
     * @param string $view
     * @param array $data
     * @return string
     */
    public function render(string $view, array $data = []): string;

    /**
     * Check if view exists
     *
     * @param string $view
     * @return bool
     */
    public function exists(string $view): bool;

    /**
     * Get engine name
     *
     * @return string
     */
    public function getName(): string;
}
