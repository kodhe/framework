<?php

namespace Kodhe\Framework\View\Exceptions;

/**
 * Class ThemeNotFoundException
 *
 * @package Kodhe\Framework\View\Exceptions
 */
class ThemeNotFoundException extends ViewException
{
    /**
     * Create a new instance for a missing theme
     *
     * @param string $theme
     * @return self
     */
    public static function make(string $theme): self
    {
        return new self("Theme not found: {$theme}");
    }
}
