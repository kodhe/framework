<?php

namespace Kodhe\Framework\View\Exceptions;

/**
 * Class ViewNotFoundException
 *
 * @package Kodhe\Framework\View\Exceptions
 */
class ViewNotFoundException extends ViewException
{
    /**
     * Create a new instance for a missing view
     *
     * @param string $view
     * @return self
     */
    public static function make(string $view): self
    {
        return new self("View not found: {$view}");
    }
}
