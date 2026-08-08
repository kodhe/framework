<?php

declare(strict_types=1);

namespace Kodhe\Framework\Table\Renderers;

use Kodhe\Framework\Table\Contracts\RendererInterface;

/**
 * HTML renderer for table output
 */
class HtmlRenderer implements RendererInterface
{
    /**
     * @var string Newline character
     */
    private string $newline = "\n";

    /**
     * Set newline character
     *
     * @param string $newline
     * @return self
     */
    public function setNewline(string $newline): self
    {
        $this->newline = $newline;
        return $this;
    }

    /**
     * Get newline character
     *
     * @return string
     */
    public function getNewline(): string
    {
        return $this->newline;
    }

    /**
     * Render the table
     *
     * @param array $heading
     * @param array $rows
     * @param array $template
     * @param string $empty_cells
     * @param string|null $caption
     * @param callable|null $function
     * @return string
     */
    public function render(
        array $heading,
        array $rows,
        array $template,
        string $empty_cells,
        ?string $caption = null,
        ?callable $function = null
    ): string {
        $out = $template['table_open'] . $this->newline;

        // Add any caption here
        if ($caption !== null && $caption !== '') {
            $out .= '<caption>' . $caption . '</caption>' . $this->newline;
        }

        // Is there a table heading to display?
        if (!empty($heading)) {
            $out .= $template['thead_open'] . $this->newline 
                  . $template['heading_row_start'] . $this->newline;

            foreach ($heading as $cell) {
                $temp = $template['heading_cell_start'];

                foreach ($cell as $key => $val) {
                    if ($key !== 'data') {
                        $temp = str_replace('<th', '<th ' . $key . '="' . $val . '"', $temp);
                    }
                }

                $out .= $temp . ($cell['data'] ?? '') . $template['heading_cell_end'];
            }

            $out .= $template['heading_row_end'] . $this->newline 
                  . $template['thead_close'] . $this->newline;
        }

        // Build the table rows
        if (!empty($rows)) {
            $out .= $template['tbody_open'] . $this->newline;

            $i = 1;
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    break;
                }

                // We use modulus to alternate the row colors
                $name = fmod($i++, 2) ? '' : 'alt_';

                $out .= $template['row_' . $name . 'start'] . $this->newline;

                foreach ($row as $cell) {
                    $temp = $template['cell_' . $name . 'start'];

                    foreach ($cell as $key => $val) {
                        if ($key !== 'data') {
                            $temp = str_replace('<td', '<td ' . $key . '="' . $val . '"', $temp);
                        }
                    }

                    $cellData = $cell['data'] ?? '';
                    $out .= $temp;

                    if ($cellData === '' || $cellData === null) {
                        $out .= $empty_cells;
                    } elseif ($function !== null && is_callable($function)) {
                        $out .= call_user_func($function, $cellData);
                    } else {
                        $out .= $cellData;
                    }

                    $out .= $template['cell_' . $name . 'end'];
                }

                $out .= $template['row_' . $name . 'end'] . $this->newline;
            }

            $out .= $template['tbody_close'] . $this->newline;
        }

        $out .= $template['table_close'];

        return $out;
    }
}
