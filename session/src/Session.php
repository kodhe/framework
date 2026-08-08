<?php

declare(strict_types=0);

namespace Kodhe\Framework\Session;

use Kodhe\Framework\Session\Contracts\SessionHandlerInterface;
use Kodhe\Framework\Session\Contracts\SessionInterface;
use Kodhe\Framework\Session\Contracts\StorageInterface;
use Kodhe\Framework\Session\Exceptions\SessionException;
use Kodhe\Framework\Session\Factory\DriverFactory;
use Kodhe\Framework\Session\Flash\FlashDataManager;
use Kodhe\Framework\Session\Storage\SessionStorage;
use Kodhe\Framework\Session\Support\CookieManager;
use Kodhe\Framework\Session\Support\SessionConfig;
use Kodhe\Framework\Session\Support\SessionIdGenerator;
use Kodhe\Framework\Session\ValueObjects\SessionId;

/**
 * CodeIgniter Session Class - Refactored Modular Version
 * 
 * Maintains 100% backward compatibility with CI3 API while using
 * modern design patterns internally.
 * 
 * @package Kodhe\Framework\Session
 */
class Session implements SessionInterface
{
    /**
     * Userdata array - reference to $_SESSION for BC
     */
    public $userdata;

    /**
     * @var SessionConfig Configuration
     */
    protected SessionConfig $config;

    /**
     * @var SessionHandlerInterface|null Session driver (lazy-loaded)
     */
    protected ?SessionHandlerInterface $driver = null;

    /**
     * @var StorageInterface Storage backend
     */
    protected StorageInterface $storage;

    /**
     * @var FlashDataManager Flash and temp data manager
     */
    protected FlashDataManager $flashManager;

    /**
     * @var CookieManager Cookie handler
     */
    protected CookieManager $cookieManager;

    /**
     * @var SessionIdGenerator Session ID generator
     */
    protected SessionIdGenerator $idGenerator;

    /**
     * @var string Session ID regex pattern
     */
    protected string $sidRegexp;

    /**
     * @var bool Whether session is initialized
     */
    protected bool $initialized = false;

    /**
     * @var int Last regeneration timestamp
     */
    protected int $lastRegenerate = 0;

    /**
     * Class constructor
     * 
     * @param array $params Configuration parameters
     */
    public function __construct(array $params = [])
    {
        // No sessions under CLI
        if ($this->isCli()) {
            $this->log('debug', 'Session: Initialization under CLI aborted.');
            return;
        }

        // Check for auto_start
        if ((bool) ini_get('session.auto_start')) {
            $this->log('error', 'Session: session.auto_start is enabled in php.ini. Aborting.');
            return;
        }

        // Determine driver
        $driver = $this->resolveDriver($params);

        // Build configuration
        $this->config = new SessionConfig(array_merge($params, ['driver' => $driver]));
        
        // Configure SID pattern
        $this->sidRegexp = $this->config->getSidPattern();

        // Initialize components
        $this->idGenerator = new SessionIdGenerator(
            $this->config->get('sid_length'),
            $this->config->get('sid_bits_per_character')
        );

        $this->cookieManager = new CookieManager([
            'cookie_name' => $this->config->get('cookie_name'),
            'cookie_lifetime' => $this->config->get('cookie_lifetime', $this->config->get('expiration')),
            'cookie_path' => $this->config->get('cookie_path'),
            'cookie_domain' => $this->config->get('cookie_domain'),
            'cookie_secure' => $this->config->get('cookie_secure'),
            'cookie_httponly' => true,
            'cookie_samesite' => $this->config->get('cookie_samesite', 'Lax'),
        ]);

        // Create driver instance (lazy load until needed)
        $this->prepareDriver($driver);

        // Start session
        $this->startSession();

        // Handle auto-regeneration
        $this->handleAutoRegenerate();

        // Initialize flash data processing
        $this->initVars();

        $this->initialized = true;
        $this->log('info', "Session: Class initialized using '{$driver}' driver.");
    }

    /**
     * Resolve which driver to use
     */
    private function resolveDriver(array &$params): string
    {
        if (!empty($params['driver'])) {
            $driver = $params['driver'];
            unset($params['driver']);
            return $driver;
        }

        // Try config item
        $driver = $this->configItem('sess_driver');
        if (!empty($driver)) {
            return $driver;
        }

        // BC fallback
        if ($this->configItem('sess_use_database')) {
            $this->log('debug', 'Session: "sess_driver" is empty; using BC fallback to "sess_use_database".');
            return 'database';
        }

        return 'files';
    }

    /**
     * Prepare the session driver
     */
    private function prepareDriver(string $driver): void
    {
        $configArray = $this->config->all();
        $configArray['_sid_regexp'] = $this->sidRegexp;

        try {
            $handler = DriverFactory::create($driver, $configArray);
            
            if (PHP_VERSION_ID >= 50400) {
                session_set_save_handler($handler, true);
            } else {
                session_set_save_handler(
                    [$handler, 'open'],
                    [$handler, 'close'],
                    [$handler, 'read'],
                    [$handler, 'write'],
                    [$handler, 'destroy'],
                    [$handler, 'gc']
                );
                register_shutdown_function('session_write_close');
            }

            $this->driver = $handler;
        } catch (\Exception $e) {
            $this->log('error', "Session: Driver '{$driver}' failed to initialize: " . $e->getMessage());
        }
    }

    /**
     * Start the PHP session
     */
    private function startSession(): void
    {
        // Validate cookie
        $cookieName = $this->config->get('cookie_name');
        if (isset($_COOKIE[$cookieName])) {
            $sessionId = $_COOKIE[$cookieName];
            
            if (!is_string($sessionId) || !preg_match('#\A' . $this->sidRegexp . '\z#', $sessionId)) {
                unset($_COOKIE[$cookieName]);
            }
        }

        // Apply security settings
        @ini_set('session.use_trans_sid', '0');
        @ini_set('session.use_strict_mode', '1');
        @ini_set('session.use_cookies', '1');
        @ini_set('session.use_only_cookies', '1');

        @session_start();

        // Ensure cookie is sent
        if (isset($_COOKIE[$cookieName]) && $_COOKIE[$cookieName] === session_id()) {
            $this->cookieManager->send(session_id(), time() + $this->config->get('expiration'));
        }
    }

    /**
     * Handle automatic session ID regeneration
     */
    private function handleAutoRegenerate(): void
    {
        $regenerateTime = $this->configItem('sess_time_to_update', 300);
        
        if ($regenerateTime <= 0) {
            return;
        }

        // Ignore AJAX requests
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return;
        }

        $currentTime = time();
        
        if (!isset($_SESSION['__ci_last_regenerate'])) {
            $_SESSION['__ci_last_regenerate'] = $currentTime;
        } elseif ($_SESSION['__ci_last_regenerate'] < ($currentTime - $regenerateTime)) {
            $this->sess_regenerate((bool) $this->configItem('sess_regenerate_destroy', false));
        }
    }

    /**
     * Initialize session variables and process flash data
     */
    private function initVars(): void
    {
        $this->storage = new SessionStorage();
        $this->flashManager = new FlashDataManager();
        
        // Process flash data (convert new->old, remove expired temp)
        $this->flashManager->processFlash();

        // Set userdata reference for BC
        $this->userdata =& $_SESSION;
    }

    /**
     * Get userdata value
     * 
     * @param string|null $key Session data key
     * @return mixed
     */
    public function userdata(?string $key = null)
    {
        if ($key !== null) {
            return $_SESSION[$key] ?? null;
        }

        if (empty($_SESSION)) {
            return [];
        }

        $exclude = array_merge(
            ['__ci_vars'],
            $this->get_flash_keys(),
            $this->get_temp_keys()
        );

        $userdata = [];
        foreach (array_keys($_SESSION) as $key) {
            if (!in_array($key, $exclude, true)) {
                $userdata[$key] = $_SESSION[$key];
            }
        }

        return $userdata;
    }

    /**
     * Set userdata
     * 
     * @param string|array $data Session data key or array
     * @param mixed|null $value Value to store
     * @return void
     */
    public function set_userdata($data, $value = null): void
    {
        if (is_array($data)) {
            foreach ($data as $key => &$val) {
                $_SESSION[$key] = $val;
            }
            return;
        }

        $_SESSION[$data] = $value;
    }

    /**
     * Unset userdata
     * 
     * @param string|array $key Session data key(s)
     * @return void
     */
    public function unset_userdata($key): void
    {
        if (is_array($key)) {
            foreach ($key as $k) {
                unset($_SESSION[$k]);
            }
            return;
        }

        unset($_SESSION[$key]);
    }

    /**
     * Check if userdata exists
     * 
     * @param string $key Session data key
     * @return bool
     */
    public function has_userdata(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Get all userdata
     * 
     * @return array
     */
    public function all_userdata(): array
    {
        return $this->userdata();
    }

    /**
     * Set flashdata
     * 
     * @param string|array $data Session data key or array
     * @param mixed|null $value Value to store
     * @return void
     */
    public function set_flashdata($data, $value = null): void
    {
        $this->set_userdata($data, $value);
        $keys = is_array($data) ? array_keys($data) : $data;
        $this->mark_as_flash($keys);
    }

    /**
     * Get flashdata
     * 
     * @param string|null $key Session data key
     * @return mixed
     */
    public function flashdata(?string $key = null)
    {
        return $this->flashManager->getFlashdata($key);
    }

    /**
     * Keep flashdata for another request
     * 
     * @param string|array $key Session data key(s)
     * @return void
     */
    public function keep_flashdata($key): void
    {
        $this->mark_as_flash($key);
    }

    /**
     * Mark data as flash
     * 
     * @param string|array $key Session data key(s)
     * @return bool
     */
    public function mark_as_flash($key): bool
    {
        return $this->flashManager->markAsFlash($key);
    }

    /**
     * Get flash keys
     * 
     * @return array
     */
    public function get_flash_keys(): array
    {
        return $this->flashManager->getFlashKeys();
    }

    /**
     * Unmark flash data
     * 
     * @param string|array $key Session data key(s)
     * @return void
     */
    public function unmark_flash($key): void
    {
        $this->flashManager->unmarkFlash($key);
    }

    /**
     * Get tempdata
     * 
     * @param string|null $key Session data key
     * @return mixed
     */
    public function tempdata(?string $key = null)
    {
        return $this->flashManager->getTempdata($key);
    }

    /**
     * Set tempdata
     * 
     * @param string|array $data Session data key or array
     * @param mixed|null $value Value to store
     * @param int $ttl Time-to-live in seconds
     * @return void
     */
    public function set_tempdata($data, $value = null, int $ttl = 300): void
    {
        $this->set_userdata($data, $value);
        $keys = is_array($data) ? array_keys($data) : $data;
        $this->mark_as_temp($keys, $ttl);
    }

    /**
     * Unset tempdata
     * 
     * @param string|array $key Session data key(s)
     * @return void
     */
    public function unset_tempdata($key): void
    {
        $this->unmark_temp($key);
    }

    /**
     * Mark data as temp
     * 
     * @param string|array $key Session data key(s)
     * @param int $ttl Time-to-live in seconds
     * @return bool
     */
    public function mark_as_temp($key, int $ttl = 300): bool
    {
        return $this->flashManager->markAsTemp($key, $ttl);
    }

    /**
     * Get temp keys
     * 
     * @return array
     */
    public function get_temp_keys(): array
    {
        return $this->flashManager->getTempKeys();
    }

    /**
     * Unmark temp data
     * 
     * @param string|array $key Session data key(s)
     * @return void
     */
    public function unmark_temp($key): void
    {
        $this->flashManager->unmarkTemp($key);
    }

    /**
     * Destroy session
     * 
     * @return void
     */
    public function sess_destroy(): void
    {
        session_destroy();
    }

    /**
     * Regenerate session ID
     * 
     * @param bool $destroy Destroy old session data
     * @return void
     */
    public function sess_regenerate(bool $destroy = false): void
    {
        $_SESSION['__ci_last_regenerate'] = time();
        session_regenerate_id($destroy);
    }

    /**
     * Get userdata reference
     * 
     * @return array
     */
    public function &get_userdata(): array
    {
        return $_SESSION;
    }

    /**
     * Get session ID
     * 
     * @return string
     */
    public function session_id(): string
    {
        return session_id();
    }

    /**
     * Magic getter
     * 
     * @param string $key Property name
     * @return mixed
     */
    public function __get($key)
    {
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }

        if ($key === 'session_id') {
            return session_id();
        }

        return null;
    }

    /**
     * Magic isset
     * 
     * @param string $key Property name
     * @return bool
     */
    public function __isset($key)
    {
        if ($key === 'session_id') {
            return session_status() === PHP_SESSION_ACTIVE;
        }

        return isset($_SESSION[$key]);
    }

    /**
     * Magic setter
     * 
     * @param string $key Property name
     * @param mixed $value Value
     * @return void
     */
    public function __set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Helper to get config items (CI3 compatibility)
     */
    protected function configItem(string $key, $default = null)
    {
        // Try global config function first
        if (function_exists('config_item')) {
            $value = config_item($key);
            if ($value !== null) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Check if running in CLI mode
     */
    protected function isCli(): bool
    {
        if (function_exists('is_cli')) {
            return is_cli();
        }
        return PHP_SAPI === 'cli';
    }

    /**
     * Log message (CI3 compatibility)
     */
    protected function log(string $level, string $message): void
    {
        if (function_exists('log_message')) {
            log_message($level, $message);
        }
    }
}
