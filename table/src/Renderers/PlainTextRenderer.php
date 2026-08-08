<?php

declare(strict_types=0);

namespace Kodhe\Framework\Table\Renderers;

use Kodhe\Framework\Table\Contracts\RendererInterface;

/**
 * Plain text renderer for table output
 */
class PlainTextRenderer implements RendererInterface
{
    /**
     * @var string Newline character
     */
    private string $newline = "\n";

    /**
     * @var string Column separator
     */
    private string $separator = ' | ';

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
     * Set column separator
     *
     * @param string $separator
     * @return self
     */
    public function setSeparator(string $separator): self
    {
        $this->separator = $separator;
        return $this;
    }

    /**
     * Render the table as plain text
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
        $out = '';

        // Add caption if present
        if ($caption !== null && $caption !== '') {
            $out .= $caption . $this->newline;
        }

        // Render heading
        if (!empty($heading)) {
            $cells = [];
            foreach ($heading as $cell) {
                $cells[] = strip_tags($cell['data'] ?? '');
            }
            $out .= implode($this->separator, $cells) . $this->newline;
        }

        // Render rows
        if (!empty($rows)) {
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    break;
                }

                $cells = [];
                foreach ($row as $cell) {
                    $cellData = $cell['data'] ?? '';
                    
                    if ($cellData === '' || $cellData === null) {
                        $cellData = $empty_cells;
                    } elseif ($function !== null && is_callable($function)) {
                        $cellData = call_user_func($function, $cellData);
                    }

                    $cells[] = strip_tags($cellData);
                }
                $out .= implode($this->separator, $cells) . $this->newline;
            }
        }

        return $out;
    }
}
