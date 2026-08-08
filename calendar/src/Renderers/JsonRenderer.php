<?php

declare(strict_types=0);

namespace Kodhe\Framework\Calendar\Renderers;

use Kodhe\Framework\Calendar\Contracts\CalendarRendererInterface;

/**
 * Class JsonRenderer
 *
 * Renders calendar as JSON data for JavaScript calendars
 *
 * @package Kodhe\Calendar\Renderers
 */
class JsonRenderer implements CalendarRendererInterface
{
    /**
     * Render calendar structure to JSON
     *
     * @param array $structure
     * @param array $data
     * @param array $config
     * @return string
     */
    public function render(array $structure, array $data, array $config): string
    {
        $events = [];

        foreach ($data as $day => $eventData) {
            if (is_array($eventData)) {
                $events[] = [
                    'date' => sprintf('%04d-%02d-%02d', $structure['year'], $structure['month'], $day),
                    'title' => $eventData['title'] ?? '',
                    'url' => $eventData['url'] ?? null,
                    'description' => $eventData['description'] ?? null,
                ];
            } else {
                $events[] = [
                    'date' => sprintf('%04d-%02d-%02d', $structure['year'], $structure['month'], $day),
                    'title' => (string) $eventData,
                    'url' => is_string($eventData) && filter_var($eventData, FILTER_VALIDATE_URL) ? $eventData : null,
                ];
            }
        }

        return json_encode([
            'year' => $structure['year'],
            'month' => $structure['month'],
            'total_days' => $structure['total_days'],
            'start_day' => $structure['start_day'],
            'weeks' => count($structure['weeks']),
            'events' => $events,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get default template (not used for JSON)
     *
     * @return array
     */
    public function defaultTemplate(): array
    {
        return [];
    }
}
