<?php

namespace Kodhe\Calendar\Renderers;

use Kodhe\Calendar\Contracts\CalendarRendererInterface;

/**
 * Class JsonRenderer
 *
 * Renders calendar as JSON for JavaScript consumption
 *
 * @package     Kodhe\Calendar
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
class JsonRenderer implements CalendarRendererInterface
{
    /**
     * Render calendar structure to JSON
     *
     * @param array $structure Calendar structure from generator
     * @param array $data      Event/data for specific days
     * @param array $config    Configuration options
     * @return string          JSON output
     */
    public function render(array $structure, array $data, array $config): string
    {
        $events = [];

        foreach ($data as $day => $eventData) {
            $date = sprintf('%04d-%02d-%02d', $structure['year'], $structure['month'], $day);

            if (is_array($eventData)) {
                $events[] = [
                    'date'  => $date,
                    'title' => $eventData['title'] ?? '',
                    'url'   => $eventData['url'] ?? null,
                    'data'  => $eventData,
                ];
            } else {
                $events[] = [
                    'date'  => $date,
                    'title' => (string) $eventData,
                    'url'   => is_string($eventData) ? $eventData : null,
                ];
            }
        }

        $output = [
            'year'       => $structure['year'],
            'month'      => $structure['month'],
            'total_days' => $structure['total_days'],
            'start_day'  => $structure['start_day'],
            'weeks'      => count($structure['weeks']),
            'events'     => $events,
        ];

        $options = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE;
        if (defined('JSON_THROW_ON_ERROR')) {
            $options |= JSON_THROW_ON_ERROR;
        }

        return json_encode($output, $options);
    }

    /**
     * Get default template array (not used for JSON renderer)
     *
     * @return array
     */
    public function defaultTemplate(): array
    {
        return [];
    }
}
