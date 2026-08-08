<?php

declare(strict_types=0);

namespace Kodhe\Framework\Profiler\Contracts;

/**
 * Renderer Interface
 * 
 * Interface for all output renderers
 */
interface RendererInterface
{
    /**
     * Render the collected data
     *
     * @param array $data
     * @return string
     */
    public function render(array $data): string;

    /**
     * Set language helper
     *
     * @param object $lang
     * @return void
     */
    public function setLanguage(object $lang): void;

    /**
     * Get renderer type (html, text, json, etc)
     *
     * @return string
     */
    public function getType(): string;
}
