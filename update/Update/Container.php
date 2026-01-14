<?php namespace Kodhe\Framework\Container;

use Closure;
use Exception;
use Kodhe\Framework\Container\Binding\ConcreteBinding;
use Kodhe\Framework\Container\Binding\BindingInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

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
     * @var array Cache untuk singleton instances yang sudah dibuat
     */
    protected $singletonInstances = array();

    /**
     * @var bool Throw exception on duplicate registration?
     */
    protected $throwOnDuplicate = true;

    /**
     * @var array Cache untuk resolved class instances
     */
    protected $resolvedInstances = [];

    /**
     * Check if a service exists in the container
     */
    public function has(string $name): bool
    {
        if (strpos($name, ':') === false) {
            $name = static::NATIVE_PREFIX . $name;
        }
        
        return isset($this->registry[$name]) || class_exists($name);
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
            // Wrap closure untuk singleton
            $that = $this;
            $singletonClosure = function ($di) use ($object, $that) {
                $hash = spl_object_hash($object) . ':' . $name;
                if (!isset($that->singletonInstances[$hash])) {
                    $that->singletonInstances[$hash] = $object($di);
                }
                return $that->singletonInstances[$hash];
            };
            
            $this->assignToRegistry($name, $singletonClosure, $this->singletonRegistry);
            return $this->register($name, $singletonClosure);
        }

        // Simpan instance langsung untuk singleton
        $this->singletonInstances[$name] = $object;
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
     * @param string $name The name of the registered service to be retrieved
     *                     in format 'Vendor/Module:Namespace\Class'.
     * @param mixed ...$arguments (Optional) Any additional arguments the service needs on
     *                     initialization.
     *
     * @throws RuntimeException On attempts to access a service that hasn't
     *                     been registered.
     *
     * @return Object An instance of the service being requested.
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

        // Cek apakah sudah ada di cache resolved instances
        if (isset($this->resolvedInstances[$name]) && empty($arguments)) {
            return $this->resolvedInstances[$name];
        }

        $object = null;
        
        // Cek apakah service terdaftar
        if (isset($this->registry[$name])) {
            $object = $this->registry[$name];
        }
        // Jika tidak terdaftar, coba apakah itu nama class yang valid
        elseif (class_exists($name)) {
            $object = $name; // Simpan sebagai nama class untuk di-resolve
        }
        // Cek di singleton registry
        elseif (isset($this->singletonRegistry[$name])) {
            $object = $this->singletonRegistry[$name];
        }
        else {
            throw new \RuntimeException('Dependency Injection: Unregistered service "' . $name . '"');
        }

        // Jika object adalah Closure
        if ($object instanceof Closure) {
            array_unshift($arguments, $di);
            $instance = call_user_func_array($object, $arguments);
            
            // Cache instance jika tidak ada parameter tambahan
            if (empty($arguments)) {
                $this->resolvedInstances[$name] = $instance;
            }
            
            return $instance;
        }
        // Jika object adalah nama class (string), buat instance dengan DI
        elseif (is_string($object) && class_exists($object)) {
            $instance = $this->resolveClass($object, $arguments);
            
            // Cache instance jika tidak ada parameter tambahan
            if (empty($arguments)) {
                $this->resolvedInstances[$name] = $instance;
            }
            
            return $instance;
        }
        // Jika object sudah berupa instance (untuk singleton)
        else {
            return $object;
        }
    }

    /**
     * Resolve a class with automatic dependency injection
     *
     * @param string $class Class name to resolve
     * @param array $arguments Additional arguments
     * @return object Resolved instance
     * @throws ReflectionException
     */
    protected function resolveClass($class, $arguments = [])
    {
        try {
            $reflector = new ReflectionClass($class);
            
            // Cek jika class tidak bisa di-instantiate (abstract/interface)
            if (!$reflector->isInstantiable()) {
                throw new Exception("Class {$class} is not instantiable");
            }
            
            // Dapatkan constructor
            $constructor = $reflector->getConstructor();
            
            // Jika tidak ada constructor, langsung buat instance
            if ($constructor === null) {
                return $reflector->newInstance();
            }
            
            // Resolve constructor parameters
            $parameters = $constructor->getParameters();
            $dependencies = $this->resolveDependencies($parameters, $arguments);
            
            // Buat instance dengan dependencies
            return $reflector->newInstanceArgs($dependencies);
            
        } catch (ReflectionException $e) {
            throw new Exception("Cannot resolve class {$class}: " . $e->getMessage());
        }
    }

    /**
     * Resolve dependencies for parameters
     *
     * @param ReflectionParameter[] $parameters
     * @param array $additionalArguments Additional arguments passed to make()
     * @return array
     */
    protected function resolveDependencies($parameters, $additionalArguments = [])
    {
        $dependencies = [];
        $additionalArgsIndex = 0;
        
        foreach ($parameters as $parameter) {
            $dependency = $parameter->getType();
            
            // Jika ada additional arguments yang diberikan, gunakan mereka
            if ($additionalArgsIndex < count($additionalArguments)) {
                $dependencies[] = $additionalArguments[$additionalArgsIndex];
                $additionalArgsIndex++;
                continue;
            }
            
            // Jika parameter punya type hint
            if ($dependency && !$dependency->isBuiltin()) {
                $dependencyClass = $dependency->getName();
                
                // Coba resolve dependency dari container
                try {
                    $dependencies[] = $this->make($dependencyClass);
                    continue;
                } catch (Exception $e) {
                    // Jika tidak bisa di-resolve, lanjut ke default value
                }
            }
            
            // Jika parameter punya default value
            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
                continue;
            }
            
            // Jika semua gagal
            throw new Exception(
                "Cannot resolve dependency \${$parameter->getName()} " .
                "in {$parameter->getDeclaringClass()->getName()}::{$parameter->getDeclaringFunction()->getName()}"
            );
        }
        
        // Tambahkan sisa additional arguments
        while ($additionalArgsIndex < count($additionalArguments)) {
            $dependencies[] = $additionalArguments[$additionalArgsIndex];
            $additionalArgsIndex++;
        }
        
        return $dependencies;
    }

    /**
     * Resolve a class dengan DI otomatis (alias untuk make())
     *
     * @param string $class Class name
     * @param mixed ...$arguments Constructor arguments
     * @return object
     */
    public function resolve($class, ...$arguments)
    {
        return $this->make($class, ...$arguments);
    }

    /**
     * Bind an interface to implementation
     *
     * @param string $interface Interface or abstract class
     * @param string|Closure $implementation Concrete class or closure
     * @return self
     */
    public function bindInterface($interface, $implementation)
    {
        return $this->register($interface, function($di) use ($implementation) {
            if ($implementation instanceof Closure) {
                return $implementation($di);
            }
            return $di->make($implementation);
        });
    }

    /**
     * Bind interface as singleton
     *
     * @param string $interface Interface or abstract class
     * @param string|Closure $implementation Concrete class or closure
     * @return self
     */
    public function singletonInterface($interface, $implementation)
    {
        return $this->registerSingleton($interface, function($di) use ($implementation) {
            if ($implementation instanceof Closure) {
                return $implementation($di);
            }
            return $di->make($implementation);
        });
    }

    /**
     * Clear resolved instances cache
     *
     * @return self
     */
    public function clearCache()
    {
        $this->resolvedInstances = [];
        return $this;
    }
}

// EOF