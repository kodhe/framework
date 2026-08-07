<?php

namespace Kodhe\Framework\View\Engine;

/**
 * Class PhpEngine
 *
 * @package Kodhe\Framework\View\Engine
 */
class PhpEngine extends AbstractEngine
{
    /**
     * Render a view
     *
     * @param string $view
     * @param array $data
     * @return string
     */
    public function render(string $view, array $data = []): string
    {
        $file = $this->findViewFile($view);

        if ($file === null) {
            return '';
        }

        // Extract data to variables
        extract($data, EXTR_SKIP);

        // Start output buffering
        ob_start();

        // Include the view file
        include $file;

        // Get and clean output
        return ob_get_clean();
    }

    /**
     * Check if view exists
     *
     * @param string $view
     * @return bool
     */
    public function exists(string $view): bool
    {
        return $this->findViewFile($view) !== null;
    }
}
