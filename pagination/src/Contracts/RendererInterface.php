<?php

declare(strict_types=1);

namespace Kodhe\Pagination\Contracts;

/**
 * Pagination Renderer Interface
 * 
 * Strategy pattern interface for different pagination renderers
 */
interface RendererInterface
{
    /**
     * Render the pagination links
     * 
     * @param array $links Array of link data
     * @return string Rendered HTML
     */
    public function render(array $links): string;
    
    /**
     * Set renderer configuration
     * 
     * @param array $config Configuration options
     * @return void
     */
    public function setConfig(array $config): void;
}
