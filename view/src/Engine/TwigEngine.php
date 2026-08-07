<?php

namespace Kodhe\Framework\View\Engine;

/**
 * Class TwigEngine
 *
 * @package Kodhe\Framework\View\Engine
 */
class TwigEngine extends AbstractEngine
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
        // Note: Full Twig implementation requires Twig package
        // This is a placeholder that falls back to PHP engine
        
        $file = $this->findViewFile($view);

        if ($file === null) {
            return '';
        }

        // For now, treat .twig files as regular PHP
        // In production, you would use Twig_Loader_Filesystem and Twig_Environment
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
