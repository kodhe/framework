<?php declare(strict_types=1);

namespace Kodhe\Framework\Foundation;

use Kodhe\Framework\Container\Container;
use Kodhe\Framework\Http\Kernel\Kernel;
use Kodhe\Framework\Http\Request;
use Kodhe\Framework\Http\Response;
use BadMethodCallException;
use RuntimeException;

/**
 * Modern Application Entry Point
 */
final class Application
{
    private Kernel $kernel;
    private Container $container;
    private bool $isBooted = false;

    /**
     * Create new application instance
     */
    public function __construct(?Container $container = null)
    {
        $this->container = $container ?? new Container();
        $this->kernel = new Kernel($this->container);
    }

    /**
     * Create application instance statically
     */
    public static function create(?Container $container = null): self
    {
        return new self($container);
    }

    /**
     * Bootstrap the application
     */
    public function bootstrap(): self
    {
        if (!$this->isBooted) {
            $this->kernel->boot();
            $this->isBooted = true;
        }
        
        return $this;
    }

    /**
     * Handle HTTP request
     */
    public function handle(Request $request): Response
    {
        $this->ensureBooted();
        return $this->kernel->run($request);
    }

    /**
     * Run application with optional request
     */
    public function run(?Request $request = null): Response
    {
        $request ??= Request::createFromGlobals();
        return $this->bootstrap()->handle($request);
    }

    /**
     * Terminate the application
     */
    public function terminate(): void
    {
        $this->kernel->shutdown();
        $this->isBooted = false;
    }

    /**
     * Get kernel instance
     */
    public function getKernel(): Kernel
    {
        return $this->kernel;
    }

    /**
     * Get container instance
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Magic method calls to kernel
     */
    public function __call(string $method, array $args)
    {
        if (method_exists($this->kernel, $method)) {
            return $this->kernel->$method(...$args);
        }
        
        throw new BadMethodCallException(
            sprintf('Method %s::%s does not exist', self::class, $method)
        );
    }

    /**
     * Magic property access to kernel
     */
    public function __get(string $property)
    {
        if (property_exists($this->kernel, $property)) {
            return $this->kernel->$property;
        }
        
        throw new RuntimeException(
            sprintf('Property %s::%s does not exist', self::class, $property)
        );
    }

    /**
     * Check if application is booted
     */
    public function isBooted(): bool
    {
        return $this->isBooted;
    }

    /**
     * Ensure application is booted
     */
    private function ensureBooted(): void
    {
        if (!$this->isBooted) {
            throw new RuntimeException('Application must be booted before handling requests');
        }
    }
}