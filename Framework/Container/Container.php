<?php namespace Kodhe\Framework\Container;

use Closure;
use Exception;
use Kodhe\Framework\Container\Binding\ConcreteBinding;
use Kodhe\Framework\Container\Binding\BindingInterface;
/**
 * Dependency Injection Container
 *
 * A service to track dependencies in other services and act as a service
 * factory and instance container.
 */
class Container implements BindingInterface
{
    /**
     * @var string Native prefix
     */
    const NATIVE_PREFIX = 'kodhe:';

    /**
     * @var array An associative array of registered dependencies
     */
    protected $registry = array();

    /**
     * @var array An associative array of singletons
     */
    protected $singletonRegistry = array();

    /**
     * @var bool Throw exception on duplicate registration?
     */
    protected $throwOnDuplicate = true;


    /**
     * Check if a service exists in the container
     */
    public function has(string $name): bool
    {
        if (strpos($name, ':') === false) {
            $name = static::NATIVE_PREFIX . $name;
        }
        
        return isset($this->registry[$name]);
    }
    
    /**
     * Get all registered bindings
     */
    public function getBindings(): array
    {
        return array_keys($this->registry);
    }
    
    /**
     * Get all singleton bindings
     */
    public function getSingletonBindings(): array
    {
        return array_keys($this->singletonRegistry);
    }

    /**
     * Set whether to throw exception on duplicate registration
     */
    public function setThrowOnDuplicate(bool $throw): self
    {
        $this->throwOnDuplicate = $throw;
        return $this;
    }

    /**
     * Get current throw on duplicate setting
     */
    public function getThrowOnDuplicate(): bool
    {
        return $this->throwOnDuplicate;
    }

    /**
     * Registers a dependency with the container
     *
     * @param string      $name   The name of the dependency in the form
     *                            Vendor:Namespace
     * @param Closure|obj $object The object to use
     * @param array       $registry Which registry are we acting on?
     * @return void
     */
    private function assignToRegistry($name, $object, &$registry)
    {
        if (strpos($name, ':') === false) {
            $name = static::NATIVE_PREFIX . $name;
        }

        if (isset($registry[$name]) && $this->throwOnDuplicate) {
            throw new Exception('Attempt to reregister existing class ' . $name);
        }

        $registry[$name] = $object;
    }

    /**
     * Registers a dependency with the container
     *
     * @param string      $name   The name of the dependency in the form
     *                            Vendor:Namespace
     * @param Closure|obj $object The object to use
     * @return self Returns this InjectionContainer object
     */
    public function register($name, $object)
    {
        $this->assignToRegistry($name, $object, $this->registry);

        return $this;
    }

    /**
     * Replace an existing binding
     *
     * @param string      $name   The name of the dependency
     * @param Closure|obj $object The object to use
     * @return self Returns this InjectionContainer object
     */
    public function replace($name, $object)
    {
        if (strpos($name, ':') === false) {
            $name = static::NATIVE_PREFIX . $name;
        }

        if (!isset($this->registry[$name])) {
            throw new Exception('Cannot replace non-existent binding: ' . $name);
        }

        $this->registry[$name] = $object;

        return $this;
    }

    /**
     * Register or replace a binding (no exception on duplicate)
     *
     * @param string      $name   The name of the dependency
     * @param Closure|obj $object The object to use
     * @return self Returns this InjectionContainer object
     */
    public function set($name, $object)
    {
        if (strpos($name, ':') === false) {
            $name = static::NATIVE_PREFIX . $name;
        }

        $this->registry[$name] = $object;

        return $this;
    }

    /**
     * Temporarily bind a dependency. Calls $this->register with $temp as TRUE
     *
     * @param string      $name   The name of the dependency in the form
     *                            Vendor:Namespace
     * @param Closure|obj $object The object to use
     * @return self Returns this InjectionContainer object
     */
    public function bind($name, $object)
    {
        $binding_isolation = new ConcreteBinding($this);
        $binding_isolation->bind($name, $object);

        return $binding_isolation;
    }

    /**
     * Registers a singleton dependency with the container
     *
     * @param string      $name   The name of the dependency in the form
     *                            Vendor:Namespace
     * @param Closure|obj $object The object to use
     * @return self Returns this InjectionContainer object
     */
    public function registerSingleton($name, $object)
    {
        if ($object instanceof Closure) {
            $that = $this;

            return $this->register($name, function ($di) use ($object, $that) {
                return $that->singleton($object);
            });
        }

        return $this->register($name, $object);
    }

    /**
     * This will exectute the provided Closure exactly once, storing the result
     * of the execution in an array and always returning that array element.
     *
     * @param Closure $object The Closure to execute
     * @return mixed The result of the Closure $object
     */
    public function singleton(Closure $object)
    {
        $hash = spl_object_hash($object);

        if (! isset($this->singletonRegistry[$hash])) {
            $this->singletonRegistry[$hash] = $object($this);
        }

        return $this->singletonRegistry[$hash];
    }

    /**
     * Make an instance of a Service
     *
     * Retrieves an instance of a service from the DIC using the registered
     * callback methods.
     *
     * @param	string	$name	The name of the registered service to be retrieved
     * 		in format 'Vendor/Module:Namespace\Class'.
     *
     * @param	...	(Optional) Any additional arguments the service needs on
     * 		initialization.
     *
     * @throws	RuntimeException	On attempts to access a service that hasn't
     * 		been registered, will throw a RuntimeException.
     *
     * @return	Object	An instance of the service being requested.
     */
    public function make()
    {
        $arguments = func_get_args();

        $di = $this;
        $name = array_shift($arguments);

        if ($name instanceof ConcreteBinding) {
            $di = $name;
            $name = array_shift($arguments);
        }

        if (strpos($name, ':') === false) {
            $name = static::NATIVE_PREFIX . $name;
        }

        if (! isset($this->registry[$name])) {
            throw new \RuntimeException('Dependency Injection: Unregistered service "' . $name . '"');
            return;
        } else {
            $object = $this->registry[$name];
        }

        
        if ($object instanceof Closure) {
            array_unshift($arguments, $di);

            return call_user_func_array($object, $arguments);
        }

        return $object;
    }
}

// EOF