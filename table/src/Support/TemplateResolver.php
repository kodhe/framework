<?php

declare(strict_types=1);

namespace Kodhe\Framework\Table\Support;

/**
 * Template resolver for table templates
 */
class TemplateResolver
{
    /**
     * Default template keys
     */
    private const TEMPLATE_KEYS = [
        'table_open',
        'thead_open',
        'thead_close',
        'heading_row_start',
        'heading_row_end',
        'heading_cell_start',
        'heading_cell_end',
        'tbody_open',
        'tbody_close',
        'row_start',
        'row_end',
        'cell_start',
        'cell_end',
        'row_alt_start',
        'row_alt_end',
        'cell_alt_start',
        'cell_alt_end',
        'table_close',
    ];

    /**
     * Get the default template
     *
     * @return array
     */
    public function getDefaultTemplate(): array
    {
        return [
            'table_open'         => '<table border="0" cellpadding="4" cellspacing="0">',
            'thead_open'         => '<thead>',
            'thead_close'        => '</thead>',
            'heading_row_start'  => '<tr>',
            'heading_row_end'    => '</tr>',
            'heading_cell_start' => '<th>',
            'heading_cell_end'   => '</th>',
            'tbody_open'         => '<tbody>',
            'tbody_close'        => '</tbody>',
            'row_start'          => '<tr>',
            'row_end'            => '</tr>',
            'cell_start'         => '<td>',
            'cell_end'           => '</td>',
            'row_alt_start'      => '<tr>',
            'row_alt_end'        => '</tr>',
            'cell_alt_start'     => '<td>',
            'cell_alt_end'       => '</td>',
            'table_close'        => '</table>',
        ];
    }

    /**
     * Compile and validate template
     *
     * @param array|null $template
     * @return array
     */
    public function resolve(?array $template): array
    {
        if ($template === null) {
            return $this->getDefaultTemplate();
        }

        $default = $this->getDefaultTemplate();
        
        foreach (self::TEMPLATE_KEYS as $key) {
            if (!isset($template[$key])) {
                $template[$key] = $default[$key];
            }
        }

        return $template;
    }

    /**
     * Merge custom template with defaults
     *
     * @param array $custom
     * @return array
     */
    public function merge(array $custom): array
    {
        return array_merge($this->getDefaultTemplate(), $custom);
    }

    /**
     * Validate template has required keys
     *
     * @param array $template
     * @return bool
     */
    public function isValid(array $template): bool
    {
        foreach (self::TEMPLATE_KEYS as $key) {
            if (!isset($template[$key])) {
                return false;
            }
        }
        return true;
    }
}
