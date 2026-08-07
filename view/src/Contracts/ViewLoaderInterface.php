<?php

namespace Kodhe\Framework\View\Contracts;

/**
 * Interface ViewLoaderInterface
 *
 * @package Kodhe\Framework\View\Contracts
 */
interface ViewLoaderInterface
{
    /**
     * Load a view
     *
     * @param string $view
     * @param array $data
     * @param bool $return
     * @return string|void
     */
    public function view(string $view, array $data = [], bool $return = true);

    /**
     * Check if view exists
     *
     * @param string $view
     * @return bool
     */
    public function exists(string $view): bool;
}
