<?php

namespace Kodhe\Framework\Email\Traits;

/**
 * Trait untuk Debug Message Handling
 *
 * @package     Kodhe\Email
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 */
trait DebugTrait
{
    /**
     * @var array Debug messages
     */
    private $debugMessages = [];

    /**
     * @var bool Debug mode enabled
     */
    private $debugMode = true;

    /**
     * Add debug message
     *
     * @param string $message
     * @return self
     */
    public function addDebugMessage(string $message): self
    {
        if ($this->debugMode) {
            $this->debugMessages[] = $message;
        }
        return $this;
    }

    /**
     * Get all debug messages
     *
     * @return array
     */
    public function getDebugMessages(): array
    {
        return $this->debugMessages;
    }

    /**
     * Get debug messages as string
     *
     * @param string $separator
     * @return string
     */
    public function getDebugString(string $separator = "\n"): string
    {
        return implode($separator, $this->debugMessages);
    }

    /**
     * Clear debug messages
     *
     * @return self
     */
    public function clearDebugMessages(): self
    {
        $this->debugMessages = [];
        return $this;
    }

    /**
     * Enable debug mode
     *
     * @return self
     */
    public function enableDebug(): self
    {
        $this->debugMode = true;
        return $this;
    }

    /**
     * Disable debug mode
     *
     * @return self
     */
    public function disableDebug(): self
    {
        $this->debugMode = false;
        return $this;
    }

    /**
     * Check if debug mode is enabled
     *
     * @return bool
     */
    public function isDebugEnabled(): bool
    {
        return $this->debugMode;
    }
}
