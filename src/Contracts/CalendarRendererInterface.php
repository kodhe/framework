<?php

namespace Kodhe\Calendar\Contracts;

/**
 * Interface CalendarRendererInterface
 *
 * Interface for calendar renderers (HTML, JSON, iCal, etc.)
 *
 * @package     Kodhe\Calendar
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
interface CalendarRendererInterface
{
    /**
     * Render calendar structure to output format
     *
     * @param array $structure Calendar structure from generator
     * @param array $data      Event/data for specific days
     * @param array $config    Configuration options
     * @return string          Rendered output
     */
    public function render(array $structure, array $data, array $config): string;

    /**
     * Get default template array
     *
     * @return array
     */
    public function defaultTemplate(): array;
}
