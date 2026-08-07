<?php

declare(strict_types=1);

namespace Kodhe\Agent\Drivers;

use Kodhe\Agent\Contracts\AgentDriverInterface;
use Kodhe\Agent\Parsers\UserAgentParser;
use Kodhe\Agent\Collections\BrowserCollection;

/**
 * Class BrowserDriver
 * 
 * Handles browser detection from user agent strings
 * 
 * @package Kodhe\Agent\Drivers
 * @author  Your Name
 * @version 2.0.0
 */
class BrowserDriver implements AgentDriverInterface
{
    /**
     * User agent parser instance
     *
     * @var UserAgentParser
     */
    protected UserAgentParser $parser;

    /**
     * Browser collection instance
     *
     * @var BrowserCollection
     */
    protected BrowserCollection $collection;

    /**
     * Detected browser name
     *
     * @var string
     */
    protected string $browser = '';

    /**
     * Detected browser version
     *
     * @var string
     */
    protected string $version = '';

    /**
     * Flag indicating if agent is a browser
     *
     * @var bool
     */
    protected bool $isBrowser = false;

    /**
     * Constructor
     *
     * @param UserAgentParser   $parser     User agent parser
     * @param BrowserCollection $collection Browser collection
     */
    public function __construct(
        UserAgentParser $parser,
        ?BrowserCollection $collection = null
    ) {
        $this->parser = $parser;
        $this->collection = $collection ?? new BrowserCollection();
        $this->detect();
    }

    /**
     * Detect the browser from user agent
     *
     * @return void
     */
    public function detect(): void
    {
        $this->browser = '';
        $this->version = '';
        $this->isBrowser = false;

        $browsers = $this->collection->all();

        if (empty($browsers)) {
            return;
        }

        foreach ($browsers as $key => $name) {
            // Try to match with version number first
            if ($this->parser->match('|' . preg_quote((string) $key) . '.*?([0-9\.]+)|i', $matches)) {
                $this->isBrowser = true;
                $this->browser = $name;
                $this->version = $matches[1] ?? '';
                return;
            }
        }
    }

    /**
     * Get the detected browser name
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->browser;
    }

    /**
     * Get the browser version
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Check if the agent is a browser
     *
     * @param string|null $key Optional specific browser key to check
     * @return bool
     */
    public function isMatch(?string $key = null): bool
    {
        if (!$this->isBrowser) {
            return false;
        }

        if ($key === null) {
            return true;
        }

        $browserName = $this->collection->get($key);
        return $browserName !== null && $this->browser === $browserName;
    }

    /**
     * Check if agent is a specific browser
     *
     * @param string $browser Browser name to check
     * @return bool
     */
    public function isBrowser(string $browser): bool
    {
        return $this->browser === $browser;
    }

    /**
     * Get browser collection
     *
     * @return BrowserCollection
     */
    public function getCollection(): BrowserCollection
    {
        return $this->collection;
    }

    /**
     * Set browser collection
     *
     * @param BrowserCollection $collection Browser collection
     * @return void
     */
    public function setCollection(BrowserCollection $collection): void
    {
        $this->collection = $collection;
        $this->detect();
    }
}
