<?php

declare(strict_types=0);

namespace Kodhe\Framework\Agent;

use Kodhe\Framework\Agent\Contracts\AgentInterface;
use Kodhe\Framework\Agent\Drivers\BrowserDriver;
use Kodhe\Framework\Agent\Drivers\DeviceDriver;
use Kodhe\Framework\Agent\Drivers\OsDriver;
use Kodhe\Framework\Agent\Drivers\RobotDriver;
use Kodhe\Framework\Agent\Parsers\UserAgentParser;
use Kodhe\Framework\Agent\Collections\BrowserCollection;
use Kodhe\Framework\Agent\Collections\DeviceCollection;
use Kodhe\Framework\Agent\Collections\OsCollection;
use Kodhe\Framework\Agent\Collections\RobotCollection;

/**
 * Agent Library for CodeIgniter 3
 * 
 * Detect user agent, browser, device, platform, and robot
 * 
 * This class provides a modular, PSR-4 compatible implementation
 * while maintaining 100% backward compatibility with CodeIgniter 3 API
 * 
 * @package     Kodhe\Agent
 * @author      Your Name
 * @version     2.0.0
 * @license     MIT
 * @link        https://github.com/yourname/agent
 */
class Agent implements AgentInterface
{
    /**
     * Browser driver instance
     *
     * @var BrowserDriver|null
     */
    protected ?BrowserDriver $browserDriver = null;

    /**
     * Device driver instance
     *
     * @var DeviceDriver|null
     */
    protected ?DeviceDriver $deviceDriver = null;

    /**
     * OS driver instance
     *
     * @var OsDriver|null
     */
    protected ?OsDriver $osDriver = null;

    /**
     * Robot driver instance
     *
     * @var RobotDriver|null
     */
    protected ?RobotDriver $robotDriver = null;

    /**
     * User agent parser instance
     *
     * @var UserAgentParser
     */
    protected UserAgentParser $parser;

    /**
     * Configuration array
     *
     * @var array
     */
    protected array $config = [];

    /**
     * Accepted languages cache
     *
     * @var array|null
     */
    protected ?array $languages = null;

    /**
     * Accepted character sets cache
     *
     * @var array|null
     */
    protected ?array $charsets = null;

    /**
     * Referrer cache
     *
     * @var bool|null
     */
    protected ?bool $referer = null;

    /**
     * Constructor
     *
     * @param array $config Optional configuration array
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        
        // Initialize parser with user agent from server
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? trim($_SERVER['HTTP_USER_AGENT']) : null;
        $this->parser = new UserAgentParser($userAgent);
        
        // Only initialize drivers if we have a user agent
        if ($userAgent !== null) {
            $this->initializeDrivers();
        }
        
        log_message('info', 'Agent Class Initialized');
    }

    /**
     * Initialize all drivers (lazy loading)
     *
     * @return void
     */
    protected function initializeDrivers(): void
    {
        // Initialize with default collections
        $this->browserDriver = new BrowserDriver($this->parser);
        $this->deviceDriver = new DeviceDriver($this->parser);
        $this->osDriver = new OsDriver($this->parser);
        $this->robotDriver = new RobotDriver($this->parser);
    }

    /**
     * Ensure drivers are initialized
     *
     * @return void
     */
    protected function ensureDriversInitialized(): void
    {
        if ($this->browserDriver === null) {
            $this->initializeDrivers();
        }
    }

    /**
     * Load configuration from file (CI3 compatibility)
     *
     * @param string|null $configPath Optional config file path
     * @return bool
     */
    public function loadConfig(?string $configPath = null): bool
    {
        if ($configPath === null) {
            $configPath = APPPATH . 'config/agent.php';
        }

        if (file_exists($configPath)) {
            $config = include $configPath;
            if (is_array($config) && isset($config['agent'])) {
                $this->config = array_merge($this->config, $config['agent']);
                return true;
            }
        }

        return false;
    }

    // =========================================================================
    // CI3 COMPATIBILITY METHODS (snake_case)
    // =========================================================================

    /**
     * Get the browser name (CI3 alias)
     *
     * @return string
     */
    public function browser(): string
    {
        $this->ensureDriversInitialized();
        return $this->browserDriver->getValue();
    }

    /**
     * Get the browser version (CI3 alias)
     *
     * @return string
     */
    public function version(): string
    {
        $this->ensureDriversInitialized();
        return $this->browserDriver->getVersion();
    }

    /**
     * Get the platform/OS name (CI3 alias)
     *
     * @return string
     */
    public function platform(): string
    {
        $this->ensureDriversInitialized();
        return $this->osDriver->getPlatform();
    }

    /**
     * Get the mobile device name (CI3 alias)
     *
     * @return string
     */
    public function mobile(): string
    {
        $this->ensureDriversInitialized();
        return $this->deviceDriver->getMobile();
    }

    /**
     * Get the robot name (CI3 alias)
     *
     * @return string
     */
    public function robot(): string
    {
        $this->ensureDriversInitialized();
        return $this->robotDriver->getRobot();
    }

    /**
     * Check if the agent is a browser (CI3 alias)
     *
     * @param string|null $key Optional specific browser key
     * @return bool
     */
    public function is_browser(?string $key = null): bool
    {
        $this->ensureDriversInitialized();
        return $this->browserDriver->isMatch($key);
    }

    /**
     * Check if the agent is a robot (CI3 alias)
     *
     * @param string|null $key Optional specific robot key
     * @return bool
     */
    public function is_robot(?string $key = null): bool
    {
        $this->ensureDriversInitialized();
        return $this->robotDriver->isMatch($key);
    }

    /**
     * Check if the agent is a mobile device (CI3 alias)
     *
     * @param string|null $key Optional specific mobile key
     * @return bool
     */
    public function is_mobile(?string $key = null): bool
    {
        $this->ensureDriversInitialized();
        return $this->deviceDriver->isMatch($key);
    }

    /**
     * Check if this is a referral from another site (CI3 alias)
     *
     * @return bool
     */
    public function is_referral(): bool
    {
        if ($this->referer === null) {
            if (empty($_SERVER['HTTP_REFERER'])) {
                $this->referer = false;
            } else {
                $refererHost = @parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_HOST);
                $ownHost = parse_url(config_item('base_url') ?? '', PHP_URL_HOST);
                $this->referer = ($refererHost && $refererHost !== $ownHost);
            }
        }

        return $this->referer;
    }

    /**
     * Get the full user agent string (CI3 alias)
     *
     * @return string|null
     */
    public function agent_string(): ?string
    {
        return $this->parser->getUserAgent();
    }

    /**
     * Get accepted languages (CI3 alias)
     *
     * @return array
     */
    public function languages(): array
    {
        if ($this->languages === null) {
            $this->_set_languages();
        }

        return $this->languages;
    }

    /**
     * Get accepted character sets (CI3 alias)
     *
     * @return array
     */
    public function charsets(): array
    {
        if ($this->charsets === null) {
            $this->_set_charsets();
        }

        return $this->charsets;
    }

    /**
     * Test for a particular language (CI3 alias)
     *
     * @param string $lang Language code to test
     * @return bool
     */
    public function accept_lang(string $lang = 'en'): bool
    {
        return in_array(strtolower($lang), $this->languages(), true);
    }

    /**
     * Test for a particular character set (CI3 alias)
     *
     * @param string $charset Character set to test
     * @return bool
     */
    public function accept_charset(string $charset = 'utf-8'): bool
    {
        return in_array(strtolower($charset), $this->charsets(), true);
    }

    /**
     * Get the referrer URL (CI3 alias)
     *
     * @return string
     */
    public function referrer(): string
    {
        return empty($_SERVER['HTTP_REFERER']) ? '' : trim($_SERVER['HTTP_REFERER']);
    }

    /**
     * Parse a custom user-agent string (CI3 alias)
     *
     * @param string $string Custom user agent string
     * @return void
     */
    public function parse(string $string): void
    {
        // Reset all drivers
        $this->browserDriver = null;
        $this->deviceDriver = null;
        $this->osDriver = null;
        $this->robotDriver = null;
        $this->languages = null;
        $this->charsets = null;
        $this->referer = null;

        // Set new user agent and reinitialize
        $this->parser->setUserAgent($string);
        
        if (!empty($string)) {
            $this->initializeDrivers();
        }
    }

    // =========================================================================
    // PSR-4 STYLE METHODS (camelCase)
    // =========================================================================

    /**
     * Check if the agent is a browser
     *
     * @param string|null $key Optional specific browser key
     * @return bool
     */
    public function isBrowser(?string $key = null): bool
    {
        return $this->is_browser($key);
    }

    /**
     * Check if the agent is a robot/crawler
     *
     * @param string|null $key Optional specific robot key
     * @return bool
     */
    public function isRobot(?string $key = null): bool
    {
        return $this->is_robot($key);
    }

    /**
     * Check if the agent is a mobile device
     *
     * @param string|null $key Optional specific mobile key
     * @return bool
     */
    public function isMobile(?string $key = null): bool
    {
        return $this->is_mobile($key);
    }

    /**
     * Check if the agent is a desktop device
     *
     * @return bool
     */
    public function isDesktop(): bool
    {
        $this->ensureDriversInitialized();
        return $this->deviceDriver->isDesktop();
    }

    /**
     * Check if this is a referral from another site
     *
     * @return bool
     */
    public function isReferral(): bool
    {
        return $this->is_referral();
    }

    /**
     * Get the full user agent string
     *
     * @return string|null
     */
    public function agentString(): ?string
    {
        return $this->agent_string();
    }

    /**
     * Test for a particular language
     *
     * @param string $lang Language code to test
     * @return bool
     */
    public function acceptLang(string $lang = 'en'): bool
    {
        return $this->accept_lang($lang);
    }

    /**
     * Test for a particular character set
     *
     * @param string $charset Character set to test
     * @return bool
     */
    public function acceptCharset(string $charset = 'utf-8'): bool
    {
        return $this->accept_charset($charset);
    }

    // =========================================================================
    // PROTECTED HELPER METHODS
    // =========================================================================

    /**
     * Set the accepted languages
     *
     * @return void
     */
    protected function _set_languages(): void
    {
        if (empty($this->languages) && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $this->languages = explode(
                ',',
                preg_replace('/(;\\s?q=[0-9\\.]+)|\\s/i', '', strtolower(trim($_SERVER['HTTP_ACCEPT_LANGUAGE'])))
            );
        }

        if (empty($this->languages)) {
            $this->languages = ['Undefined'];
        }
    }

    /**
     * Set the accepted character sets
     *
     * @return void
     */
    protected function _set_charsets(): void
    {
        if (empty($this->charsets) && !empty($_SERVER['HTTP_ACCEPT_CHARSET'])) {
            $this->charsets = explode(
                ',',
                preg_replace('/(;\\s?q=.+)|\\s/i', '', strtolower(trim($_SERVER['HTTP_ACCEPT_CHARSET'])))
            );
        }

        if (empty($this->charsets)) {
            $this->charsets = ['Undefined'];
        }
    }

    // =========================================================================
    // GETTERS FOR DRIVERS (for advanced usage)
    // =========================================================================

    /**
     * Get the browser driver
     *
     * @return BrowserDriver
     */
    public function getBrowserDriver(): BrowserDriver
    {
        $this->ensureDriversInitialized();
        return $this->browserDriver;
    }

    /**
     * Get the device driver
     *
     * @return DeviceDriver
     */
    public function getDeviceDriver(): DeviceDriver
    {
        $this->ensureDriversInitialized();
        return $this->deviceDriver;
    }

    /**
     * Get the OS driver
     *
     * @return OsDriver
     */
    public function getOsDriver(): OsDriver
    {
        $this->ensureDriversInitialized();
        return $this->osDriver;
    }

    /**
     * Get the robot driver
     *
     * @return RobotDriver
     */
    public function getRobotDriver(): RobotDriver
    {
        $this->ensureDriversInitialized();
        return $this->robotDriver;
    }

    /**
     * Get the user agent parser
     *
     * @return UserAgentParser
     */
    public function getParser(): UserAgentParser
    {
        return $this->parser;
    }
}
