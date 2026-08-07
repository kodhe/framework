<?php

declare(strict_types=1);

namespace Kodhe\Agent\Parsers;

/**
 * Class UserAgentParser
 * 
 * Parses user agent strings and provides utility methods
 * 
 * @package Kodhe\Agent\Parsers
 * @author  Your Name
 * @version 2.0.0
 */
class UserAgentParser
{
    /**
     * Current user agent string
     *
     * @var string|null
     */
    protected ?string $userAgent = null;

    /**
     * Constructor
     *
     * @param string|null $userAgent Optional user agent string
     */
    public function __construct(?string $userAgent = null)
    {
        $this->userAgent = $userAgent ?? $this->getUserAgentFromServer();
    }

    /**
     * Get user agent from server variables
     *
     * @return string|null
     */
    protected function getUserAgentFromServer(): ?string
    {
        return isset($_SERVER['HTTP_USER_AGENT']) ? trim($_SERVER['HTTP_USER_AGENT']) : null;
    }

    /**
     * Get the current user agent string
     *
     * @return string|null
     */
    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    /**
     * Set a custom user agent string
     *
     * @param string $userAgent User agent string to set
     * @return void
     */
    public function setUserAgent(string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    /**
     * Check if user agent string contains a pattern
     *
     * @param string $pattern Pattern to search for
     * @return bool
     */
    public function contains(string $pattern): bool
    {
        if ($this->userAgent === null) {
            return false;
        }
        return stripos($this->userAgent, $pattern) !== false;
    }

    /**
     * Match a pattern in the user agent string
     *
     * @param string $pattern Regex pattern to match
     * @param array|null $matches Optional array to store matches
     * @return bool
     */
    public function match(string $pattern, ?array &$matches = null): bool
    {
        if ($this->userAgent === null) {
            return false;
        }
        return (bool) preg_match($pattern, $this->userAgent, $matches);
    }

    /**
     * Extract version number from user agent
     *
     * @param string $pattern Pattern to match before version
     * @return string Version number or empty string
     */
    public function extractVersion(string $pattern): string
    {
        if ($this->userAgent === null) {
            return '';
        }

        $fullPattern = '|' . preg_quote($pattern, '|') . '.*?([0-9\.]+)|i';
        if (preg_match($fullPattern, $this->userAgent, $matches)) {
            return $matches[1] ?? '';
        }

        return '';
    }

    /**
     * Find first matching pattern from a list
     *
     * @param array $patterns Array of patterns to check
     * @param string|null $matchedKey Reference to store the matched key
     * @return string|null Matched value or null
     */
    public function findFirstMatch(array $patterns, ?string &$matchedKey = null): ?string
    {
        if ($this->userAgent === null) {
            return null;
        }

        foreach ($patterns as $key => $value) {
            if (preg_match('|' . preg_quote((string) $key) . '|i', $this->userAgent)) {
                $matchedKey = $key;
                return $value;
            }
        }

        return null;
    }

    /**
     * Find first matching pattern using stripos
     *
     * @param array $patterns Array of patterns to check
     * @param string|null $matchedKey Reference to store the matched key
     * @return string|null Matched value or null
     */
    public function findFirstMatchSimple(array $patterns, ?string &$matchedKey = null): ?string
    {
        if ($this->userAgent === null) {
            return null;
        }

        foreach ($patterns as $key => $value) {
            if (stripos($this->userAgent, (string) $key) !== false) {
                $matchedKey = $key;
                return $value;
            }
        }

        return null;
    }
}
