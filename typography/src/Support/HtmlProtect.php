<?php

declare(strict_types=0);

namespace Kodhe\Framework\Typography\Support;

/**
 * HTML Protector
 * 
 * Protects HTML elements and special characters during typography processing.
 */
class HtmlProtect
{
    /**
     * @var array Protected content placeholders
     */
    private $protected = [];

    /**
     * @var int Counter for placeholder IDs
     */
    private $counter = 0;

    /**
     * Protect content and return a placeholder.
     *
     * @param string $content
     * @param string $prefix
     * @return string
     */
    public function protect(string $content, string $prefix = 'PROT'): string
    {
        $id = $this->counter++;
        $placeholder = '{@' . $prefix . $id . '}';
        $this->protected[$placeholder] = $content;

        return $placeholder;
    }

    /**
     * Restore all protected content.
     *
     * @param string $text
     * @return string
     */
    public function restore(string $text): string
    {
        foreach ($this->protected as $placeholder => $content) {
            $text = str_replace($placeholder, $content, $text);
        }

        $this->protected = [];
        $this->counter = 0;

        return $text;
    }

    /**
     * Get all protected content.
     *
     * @return array
     */
    public function getProtected(): array
    {
        return $this->protected;
    }

    /**
     * Clear all protected content.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->protected = [];
        $this->counter = 0;
    }
}
