<?php

declare(strict_types=1);

namespace Kodhe\Framework\Agent\Drivers;

use Kodhe\Framework\Agent\Contracts\AgentDriverInterface;
use Kodhe\Framework\Agent\Parsers\UserAgentParser;
use Kodhe\Framework\Agent\Collections\OsCollection;

/**
 * Class OsDriver
 * 
 * Handles operating system/platform detection from user agent strings
 * 
 * @package Kodhe\Agent\Drivers
 * @author  Your Name
 * @version 2.0.0
 */
class OsDriver implements AgentDriverInterface
{
    /**
     * User agent parser instance
     *
     * @var UserAgentParser
     */
    protected UserAgentParser $parser;

    /**
     * OS collection instance
     *
     * @var OsCollection
     */
    protected OsCollection $collection;

    /**
     * Detected platform/OS name
     *
     * @var string
     */
    protected string $platform = '';

    /**
     * Flag indicating if platform was detected
     *
     * @var bool
     */
    protected bool $isDetected = false;

    /**
     * Constructor
     *
     * @param UserAgentParser $parser     User agent parser
     * @param OsCollection    $collection OS collection
     */
    public function __construct(
        UserAgentParser $parser,
        ?OsCollection $collection = null
    ) {
        $this->parser = $parser;
        $this->collection = $collection ?? new OsCollection();
        $this->detect();
    }

    /**
     * Detect the platform from user agent
     *
     * @return void
     */
    public function detect(): void
    {
        $this->platform = '';
        $this->isDetected = false;

        $platforms = $this->collection->all();

        if (empty($platforms)) {
            $this->platform = 'Unknown Platform';
            return;
        }

        foreach ($platforms as $key => $name) {
            // Try regex match for platforms with version patterns
            if ($this->parser->match('|' . preg_quote((string) $key) . '|i')) {
                $this->isDetected = true;
                $this->platform = $name;
                return;
            }
        }

        $this->platform = 'Unknown Platform';
    }

    /**
     * Get the detected platform name
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->platform;
    }

    /**
     * Get the platform/OS name
     *
     * @return string
     */
    public function getPlatform(): string
    {
        return $this->platform;
    }

    /**
     * Check if the platform matches
     *
     * @param string|null $key Optional specific platform key to check
     * @return bool
     */
    public function isMatch(?string $key = null): bool
    {
        if (!$this->isDetected) {
            return false;
        }

        if ($key === null) {
            return true;
        }

        $platformName = $this->collection->get($key);
        return $platformName !== null && $this->platform === $platformName;
    }

    /**
     * Check if platform is a specific OS
     *
     * @param string $os OS name to check
     * @return bool
     */
    public function isOs(string $os): bool
    {
        return $this->platform === $os;
    }

    /**
     * Check if platform is Windows
     *
     * @return bool
     */
    public function isWindows(): bool
    {
        return stripos($this->platform, 'Windows') !== false;
    }

    /**
     * Check if platform is Mac
     *
     * @return bool
     */
    public function isMac(): bool
    {
        return stripos($this->platform, 'Mac') !== false;
    }

    /**
     * Check if platform is Linux
     *
     * @return bool
     */
    public function isLinux(): bool
    {
        return stripos($this->platform, 'Linux') !== false;
    }

    /**
     * Get OS collection
     *
     * @return OsCollection
     */
    public function getCollection(): OsCollection
    {
        return $this->collection;
    }

    /**
     * Set OS collection
     *
     * @param OsCollection $collection OS collection
     * @return void
     */
    public function setCollection(OsCollection $collection): void
    {
        $this->collection = $collection;
        $this->detect();
    }
}
