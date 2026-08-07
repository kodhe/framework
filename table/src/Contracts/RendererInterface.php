<?php

declare(strict_types=1);

namespace Kodhe\Table\Contracts;

/**
 * Interface for table renderers
 */
interface RendererInterface
{
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
    ): string;
}
