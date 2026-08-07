<?php

namespace Kodhe\Framework\View\Engine;

/**
 * Class BladeEngine
 *
 * @package Kodhe\Framework\View\Engine
 */
class BladeEngine extends AbstractEngine
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
        // Note: Full Blade implementation requires Laravel's blade package
        // This is a placeholder that falls back to PHP engine
        
        $file = $this->findViewFile($view);

        if ($file === null) {
            return '';
        }

        // For now, treat .blade.php files as regular PHP
        // In production, you would compile Blade templates here
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
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
