<?php

declare(strict_types=1);

namespace Kodhe\Agent\Contracts;

/**
 * Interface AgentInterface
 * 
 * Main interface for the Agent library
 * 
 * @package Kodhe\Agent\Contracts
 * @author  Your Name
 * @version 2.0.0
 */
interface AgentInterface
{
    /**
     * Get the browser name
     *
     * @return string
     */
    public function browser(): string;

    /**
     * Get the browser version
     *
     * @return string
     */
    public function version(): string;

    /**
     * Get the platform/OS name
     *
     * @return string
     */
    public function platform(): string;

    /**
     * Get the mobile device name
     *
     * @return string
     */
    public function mobile(): string;

    /**
     * Get the robot name
     *
     * @return string
     */
    public function robot(): string;

    /**
     * Check if the agent is a browser
     *
     * @param string|null $key Optional specific browser key
     * @return bool
     */
    public function isBrowser(?string $key = null): bool;

    /**
     * Check if the agent is a robot/crawler
     *
     * @param string|null $key Optional specific robot key
     * @return bool
     */
    public function isRobot(?string $key = null): bool;

    /**
     * Check if the agent is a mobile device
     *
     * @param string|null $key Optional specific mobile key
     * @return bool
     */
    public function isMobile(?string $key = null): bool;

    /**
     * Check if the agent is a desktop device
     *
     * @return bool
     */
    public function isDesktop(): bool;

    /**
     * Check if this is a referral from another site
     *
     * @return bool
     */
    public function isReferral(): bool;

    /**
     * Get the full user agent string
     *
     * @return string|null
     */
    public function agentString(): ?string;

    /**
     * Get accepted languages
     *
     * @return array
     */
    public function languages(): array;

    /**
     * Get accepted character sets
     *
     * @return array
     */
    public function charsets(): array;

    /**
     * Test for a particular language
     *
     * @param string $lang Language code to test
     * @return bool
     */
    public function acceptLang(string $lang = 'en'): bool;

    /**
     * Test for a particular character set
     *
     * @param string $charset Character set to test
     * @return bool
     */
    public function acceptCharset(string $charset = 'utf-8'): bool;

    /**
     * Get the referrer URL
     *
     * @return string
     */
    public function referrer(): string;

    /**
     * Parse a custom user-agent string
     *
     * @param string $string Custom user agent string
     * @return void
     */
    public function parse(string $string): void;
}
