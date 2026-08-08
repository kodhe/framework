<?php

declare(strict_types=0);

namespace Kodhe\Framework\Agent\Drivers;

use Kodhe\Framework\Agent\Contracts\AgentDriverInterface;
use Kodhe\Framework\Agent\Parsers\UserAgentParser;
use Kodhe\Framework\Agent\Collections\DeviceCollection;

/**
 * Class DeviceDriver
 * 
 * Handles mobile device detection from user agent strings
 * 
 * @package Kodhe\Agent\Drivers
 * @author  Your Name
 * @version 2.0.0
 */
class DeviceDriver implements AgentDriverInterface
{
    /**
     * User agent parser instance
     *
     * @var UserAgentParser
     */
    protected UserAgentParser $parser;

    /**
     * Device collection instance
     *
     * @var DeviceCollection
     */
    protected DeviceCollection $collection;

    /**
     * Detected mobile device name
     *
     * @var string
     */
    protected string $mobile = '';

    /**
     * Flag indicating if agent is a mobile device
     *
     * @var bool
     */
    protected bool $isMobile = false;

    /**
     * Constructor
     *
     * @param UserAgentParser  $parser     User agent parser
     * @param DeviceCollection $collection Device collection
     */
    public function __construct(
        UserAgentParser $parser,
        ?DeviceCollection $collection = null
    ) {
        $this->parser = $parser;
        $this->collection = $collection ?? new DeviceCollection();
        $this->detect();
    }

    /**
     * Detect the mobile device from user agent
     *
     * @return void
     */
    public function detect(): void
    {
        $this->mobile = '';
        $this->isMobile = false;

        $mobiles = $this->collection->all();

        if (empty($mobiles)) {
            return;
        }

        foreach ($mobiles as $key => $name) {
            // Use simple string matching for mobile devices
            if ($this->parser->contains((string) $key)) {
                $this->isMobile = true;
                $this->mobile = $name;
                return;
            }
        }
    }

    /**
     * Get the detected mobile device name
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->mobile;
    }

    /**
     * Get the mobile device name
     *
     * @return string
     */
    public function getMobile(): string
    {
        return $this->mobile;
    }

    /**
     * Check if the agent is a mobile device
     *
     * @param string|null $key Optional specific mobile key to check
     * @return bool
     */
    public function isMatch(?string $key = null): bool
    {
        if (!$this->isMobile) {
            return false;
        }

        if ($key === null) {
            return true;
        }

        $mobileName = $this->collection->get($key);
        return $mobileName !== null && $this->mobile === $mobileName;
    }

    /**
     * Check if agent is a mobile device
     *
     * @return bool
     */
    public function isMobile(): bool
    {
        return $this->isMobile;
    }

    /**
     * Check if agent is a tablet
     *
     * @return bool
     */
    public function isTablet(): bool
    {
        $tabletPatterns = ['iPad', 'PlayBook', 'HP Table', 'Kindle', 'Silk'];
        
        foreach ($tabletPatterns as $pattern) {
            if ($this->parser->contains($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if agent is a phone (not tablet)
     *
     * @return bool
     */
    public function isPhone(): bool
    {
        return $this->isMobile() && !$this->isTablet();
    }

    /**
     * Check if agent is a desktop device
     *
     * @return bool
     */
    public function isDesktop(): bool
    {
        return !$this->isMobile();
    }

    /**
     * Get device collection
     *
     * @return DeviceCollection
     */
    public function getCollection(): DeviceCollection
    {
        return $this->collection;
    }

    /**
     * Set device collection
     *
     * @param DeviceCollection $collection Device collection
     * @return void
     */
    public function setCollection(DeviceCollection $collection): void
    {
        $this->collection = $collection;
        $this->detect();
    }
}
