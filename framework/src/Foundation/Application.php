<?php

declare(strict_types=1);

namespace Kodhe\Framework\Foundation;

use BadMethodCallException;
use Kodhe\Framework\Container\Container;
use RuntimeException;

/**
 * Modern Application Entry Point
 *
 * Requires the kodhe/http package (Kernel, Request, Response).
 */
final class Application
{
    /** @var object Kernel instance (Kodhe\Framework\Http\Kernel\Kernel) */
    private object $kernel;

    private Container $container;

    private bool $isBooted = false;

    private const KERNEL_CLASS = 'Kodhe\\Framework\\Http\\Kernel\\Kernel';
    private const REQUEST_CLASS = 'Kodhe\\Framework\\Http\\Request';

    /**
     * Create new application instance
     *
     * @throws RuntimeException if kodhe/http is not installed
     */
    public function __construct(?Container $container = null)
    {
        $this->ensureHttpPackage();

        $this->container = $container ?? new Container();
        $kernelClass = self::KERNEL_CLASS;
        $this->kernel = new $kernelClass($this->container);
    }

    /**
     * Create application instance statically
     */
    public static function create(?Container $container = null): self
    {
        return new self($container);
    }

    /**
     * Ensure kodhe/http classes are available
     */
    private function ensureHttpPackage(): void
    {
        if (class_exists(self::KERNEL_CLASS)) {
            return;
        }

        throw new RuntimeException(
            'Class "' . self::KERNEL_CLASS . '" not found.' . PHP_EOL
            . 'The Application entry point requires the kodhe/http package.' . PHP_EOL
            . PHP_EOL
            . 'Install it with:' . PHP_EOL
            . '  composer require kodhe/http' . PHP_EOL
            . PHP_EOL
            . 'If you use path repositories, make sure packages/http is required' . PHP_EOL
            . 'in your composer.json and run: composer update kodhe/http'
        );
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
     *
     * @param object $request Kodhe\Framework\Http\Request
     * @return object Kodhe\Framework\Http\Response
     */
    public function handle(object $request): object
    {
        $this->ensureBooted();
        return $this->kernel->run($request);
    }

    /**
     * Run application with optional request
     */
    public function run(?object $request = null): object
    {
        if ($request === null) {
            $requestClass = self::REQUEST_CLASS;
            if (!class_exists($requestClass)) {
                $this->ensureHttpPackage();
            }
            if (method_exists($requestClass, 'createFromGlobals')) {
                $request = $requestClass::createFromGlobals();
            } elseif (method_exists($requestClass, 'fromGlobals')) {
                $request = $requestClass::fromGlobals();
            } else {
                $request = new $requestClass();
            }
        }

        return $this->bootstrap()->handle($request);
    }

    /**
     * Terminate the application
     */
    public function terminate(): void
    {
        if (method_exists($this->kernel, 'shutdown')) {
            $this->kernel->shutdown();
        }
        $this->isBooted = false;
    }

    /**
     * Get kernel instance
     */
    public function getKernel(): object
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
            sprintf('Property %s::$%s does not exist', self::class, $property)
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
