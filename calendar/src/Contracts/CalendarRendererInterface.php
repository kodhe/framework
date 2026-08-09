<?php

declare(strict_types=1);

namespace Kodhe\Framework\Calendar\Contracts;

/**
 * Interface CalendarRendererInterface
 *
 * @package Kodhe\Calendar\Contracts
 */
interface CalendarRendererInterface
{
    /**
     * Render calendar structure
     *
     * @param array $structure
     * @param array $data
     * @param array $config
     * @return string
     */
    public function render(array $structure, array $data, array $config): string;

    /**
     * Get default template
     *
     * @return array
     */
    public function defaultTemplate(): array;
}
