<?php

namespace Kodhe\Framework\View\Variant;

/**
 * Class Variant
 *
 * @package Kodhe\Framework\View\Variant
 */
class Variant
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $userAgents = [];

    /**
     * Create a new Variant instance
     *
     * @param string $name
     * @param array $userAgents
     */
    public function __construct(string $name, array $userAgents = [])
    {
        $this->name = $name;
        $this->userAgents = $userAgents;
    }

    /**
     * Get variant name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get user agents
     *
     * @return array
     */
    public function getUserAgents(): array
    {
        return $this->userAgents;
    }

    /**
     * Check if user agent matches
     *
     * @param string $userAgent
     * @return bool
     */
    public function matches(string $userAgent): bool
    {
        foreach ($this->userAgents as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }

        return false;
    }
}
