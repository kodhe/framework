<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Kernel;

use Kodhe\Framework\Http\Contracts\PipelineInterface;
use Kodhe\Framework\Http\Contracts\RequestInterface;
use Kodhe\Framework\Http\Contracts\ResponseInterface;
use Closure;
use Exception;

/**
 * Pipeline - Pass request through a stack of middleware
 * 
 * Compatible with CodeIgniter 3 while providing modern PSR-based architecture
 */
class Pipeline implements PipelineInterface
{
    /**
     * The application instance
     *
     * @var mixed
     */
    protected $app;

    /**
     * The object being passed through the pipeline
     *
     * @var mixed
     */
    protected $passable;

    /**
     * The array of pipes (middleware)
     *
     * @var array
     */
    protected $pipes = [];

    /**
     * The method to call on each pipe
     *
     * @var string
     */
    protected $method = 'handle';

    /**
     * Create a new pipeline instance
     *
     * @param mixed $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Set the object being sent through the pipeline
     *
     * @param mixed $passable
     * @return $this
     */
    public function send($passable): self
    {
        $this->passable = $passable;

        return $this;
    }

    /**
     * Set the array of pipes
     *
     * @param array|mixed $pipes
     * @return $this
     */
    public function through($pipes): self
    {
        if (is_array($pipes)) {
            $this->pipes = $pipes;
        } else {
            $this->pipes[] = $pipes;
        }

        return $this;
    }

    /**
     * Set the method to call on the pipes
     *
     * @param string $method
     * @return $this
     */
    public function via(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Run the pipeline with a final destination callback
     *
     * @param Closure $destination
     * @return mixed
     * @throws Exception
     */
    public function then(Closure $destination)
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            [$this, 'carry'],
            $this->prepareDestination($destination)
        );

        return $pipeline($this->passable);
    }

    /**
     * Run the pipeline with a final destination callback, but catch exceptions
     *
     * @param Closure $destination
     * @return mixed
     */
    public function thenReturn(Closure $destination)
    {
        try {
            return $this->then($destination);
        } catch (Exception $e) {
            return $this->app->get('response')
                ->setStatusCode(500)
                ->setBody($e->getMessage());
        }
    }

    /**
     * Prepare the destination callback
     *
     * @param Closure $destination
     * @return Closure
     */
    protected function prepareDestination(Closure $destination): Closure
    {
        return function ($passable) use ($destination) {
            try {
                return $destination($passable);
            } catch (Exception $e) {
                return $this->app->get('response')
                    ->setStatusCode(500)
                    ->setBody($e->getMessage());
            }
        };
    }

    /**
     * Get a Closure that represents a slice of the pipeline onion
     *
     * @param mixed $pipe
     * @return Closure
     */
    protected function carry($stack, $pipe): Closure
    {
        return function ($passable) use ($stack, $pipe) {
            try {
                // Resolve the pipe
                $resolvedPipe = $this->resolvePipe($pipe);

                // Call the pipe's handle method
                if (is_callable($resolvedPipe)) {
                    return $resolvedPipe($passable, $stack);
                }

                if (is_object($resolvedPipe)) {
                    return $resolvedPipe->{$this->method}($passable, $stack);
                }

                // If it's a class name, resolve it from the container
                if (is_string($resolvedPipe)) {
                    $instance = $this->app->make($resolvedPipe);
                    return $instance->{$this->method}($passable, $stack);
                }

                // If it's an array [Class, method]
                if (is_array($resolvedPipe)) {
                    $instance = is_object($resolvedPipe[0]) 
                        ? $resolvedPipe[0] 
                        : $this->app->make($resolvedPipe[0]);
                    
                    return $instance->{$resolvedPipe[1]}($passable, $stack);
                }

                return $stack($passable);
            } catch (Exception $e) {
                throw $e;
            }
        };
    }

    /**
     * Resolve a pipe instance
     *
     * @param mixed $pipe
     * @return mixed
     */
    protected function resolvePipe($pipe)
    {
        if (is_object($pipe) || is_callable($pipe)) {
            return $pipe;
        }

        if (is_string($pipe)) {
            // Check if it's a middleware alias
            if ($this->app->has('middleware.registry')) {
                $registry = $this->app->get('middleware.registry');
                if ($registry->has($pipe)) {
                    return $registry->get($pipe);
                }
            }

            // Try to resolve from container
            if ($this->app->has($pipe)) {
                return $this->app->get($pipe);
            }

            // Return as class name to be instantiated later
            return $pipe;
        }

        return $pipe;
    }

    /**
     * Get the prepared Closure
     *
     * @param Closure $destination
     * @return callable
     */
    protected function getSlice(Closure $destination): callable
    {
        return $this->prepareDestination($destination);
    }
}
