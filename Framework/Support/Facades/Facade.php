<?php namespace Kodhe\Framework\Support\Facades;

use InvalidArgumentException;
use RuntimeException;
use BadMethodCallException;

/**
 * Independent Facade - Singleton Service Container
 */
class Facade
{
    protected static ?self $instance = null;
    protected array $loaded = [];
    protected array $callbacks = [];
    
    /** @var array<string, string> Map of deprecated names */
    private const DEPRECATED = [
        'blacklist' => 'blockedlist',
    ];

    // Private constructor untuk singleton
    private function __construct() {}

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
            
            // Set instance in global untuk backward compatibility
            $GLOBALS['kodhe'] = self::$instance;
        }
        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
        unset($GLOBALS['kodhe']);
    }

    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    public function __isset(string $name): bool
    {
        return $this->has($name);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->set($name, $value);
    }

    public function __call(string $method, array $args): mixed
    {
        // Priority 1: Check if method exists on loader
        if ($this->has('load') && method_exists($this->loaded['load'], $method)) {
            return call_user_func_array([$this->loaded['load'], $method], $args);
        }
        
        // Priority 2: Check registered callbacks
        if (isset($this->callbacks[$method])) {
            return call_user_func_array($this->callbacks[$method], $args);
        }
        
        throw new BadMethodCallException("Method {$method} not found.");
    }

    public function set(string $name, mixed $object): bool
    {
        if ($this->has($name)) {
            if ($this->isDevelopment()) {
                log_message('debug', "Overriding existing facade property: {$name}");
            }
            // Allow override in development, silent in production
            if (!$this->isDevelopment()) {
                return false;
            }
        }

        $this->loaded[$name] = $object;
        return true;
    }

    public function remove(string $name): void
    {
        unset($this->loaded[$name]);
    }

    public function get(string $name): mixed
    {
        if ($this->has($name)) {
            return $this->loaded[$name];
        }
    
        // Check deprecated mappings
        if (array_key_exists($name, self::DEPRECATED)) {
            $newName = self::DEPRECATED[$name];
            
            if ($this->has($newName)) {
                if ($this->isDevelopment()) {
                    trigger_error(
                        sprintf('Property "%s" is deprecated, use "%s" instead', $name, $newName),
                        E_USER_DEPRECATED
                    );
                }
                return $this->get($newName);
            }
        }
        
        throw new InvalidArgumentException("No such property: '{$name}'");
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->loaded);
    }

    public function register(string $method, callable $callback): void
    {
        $this->callbacks[$method] = $callback;
    }

    public function all(): array
    {
        return $this->loaded;
    }

    private function isDevelopment(): bool
    {
        return defined('ENVIRONMENT') && ENVIRONMENT == 'development';
    }
}