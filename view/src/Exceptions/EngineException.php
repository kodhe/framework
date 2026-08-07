<?php

namespace Kodhe\Framework\View\Exceptions;

/**
 * Class EngineException
 *
 * @package Kodhe\Framework\View\Exceptions
 */
class EngineException extends ViewException
{
    /**
     * Create a new instance for an unsupported engine
     *
     * @param string $engine
     * @return self
     */
    public static function unsupported(string $engine): self
    {
        return new self("Unsupported engine: {$engine}");
    }

    /**
     * Create a new instance for a missing engine
     *
     * @param string $engine
     * @return self
     */
    public static function notFound(string $engine): self
    {
        return new self("Engine not found: {$engine}");
    }
}
