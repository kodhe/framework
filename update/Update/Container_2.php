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
     * @var array An associative array of singleton bindings
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
        $normalizedName = $this->normalizeName($name);
        return isset($this->registry[$normalizedName]) || 
               isset($this->singletonRegistry[$normalizedName]) ||
               class_exists($name);
    }
    
    /**
     * Normalize service name
     */
    protected function normalizeName(string $name): string
    {
        if (strpos($name, ':') === false) {
            return static::NATIVE_PREFIX . $name;
        }
        return $name;
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
        $name = $this->normalizeName($name);

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
        $name = $this->normalizeName($name);

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
        $name = $this->normalizeName($name);
        $this->registry[$name] = $object;
        return $this;
    }

    /**
     * Temporarily bind a dependency. 
     * Creates a new ConcreteBinding instance for isolated bindings
     *
     * @param string      $name   The name of the dependency in the form
     *                            Vendor:Namespace
     * @param Closure|obj $object The object to use
     * @return ConcreteBinding Returns binding isolation object
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
        $name = $this->normalizeName($name);
        
        if ($object instanceof Closure) {
            // Wrap closure untuk singleton
            $singletonClosure = function ($di) use ($object, $name) {
                if (!isset($this->singletonInstances[$name])) {
                    $this->singletonInstances[$name] = $object($di);
                }
                return $this->singletonInstances[$name];
            };
            
            $this->singletonRegistry[$name] = true;
            return $this->register($name, $singletonClosure);
        }

        // Simpan instance langsung untuk singleton
        $this->singletonInstances[$name] = $object;
        $this->singletonRegistry[$name] = true;
        return $this->register($name, $object);
    }

    /**
     * This will execute the provided Closure exactly once, storing the result
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
     * callback methods or automatic DI for unregistered classes.
     *
     * @param string|ConcreteBinding $name The name of the registered service 
     *                                     or ConcreteBinding instance
     * @param mixed ...$arguments Additional arguments
     *
     * @return object An instance of the service being requested.
     */
    public function make()
    {
        $arguments = func_get_args();
        $name = array_shift($arguments);

        // Handle ConcreteBinding delegation
        if ($name instanceof ConcreteBinding) {
            return $name->make(...$arguments);
        }

        $normalizedName = $this->normalizeName($name);
        
        // Cek apakah sudah ada di cache resolved instances
        if (isset($this->resolvedInstances[$normalizedName]) && empty($arguments)) {
            return $this->resolvedInstances[$normalizedName];
        }

        $object = null;
        
        // Cek apakah service terdaftar di registry
        if (isset($this->registry[$normalizedName])) {
            $object = $this->registry[$normalizedName];
        }
        // Jika tidak terdaftar, coba apakah itu nama class yang valid
        elseif (class_exists($name)) {
            $object = $name; // Simpan sebagai nama class untuk di-resolve
        }
        else {
            throw new \RuntimeException('Dependency Injection: Unregistered service "' . $name . '"');
        }

        $instance = null;
        
        // Jika object adalah Closure
        if ($object instanceof Closure) {
            array_unshift($arguments, $this);
            $instance = $object(...$arguments);
        }
        // Jika object adalah nama class (string), buat instance dengan DI
        elseif (is_string($object) && class_exists($object)) {
            $instance = $this->resolveClass($object, $arguments);
        }
        // Jika object sudah berupa instance (untuk singleton)
        else {
            $instance = $object;
        }

        // Cache instance jika tidak ada parameter tambahan dan bukan singleton registry
        if (empty($arguments) && !isset($this->singletonRegistry[$normalizedName])) {
            $this->resolvedInstances[$normalizedName] = $instance;
        }

        return $instance;
    }

    /**
     * Resolve a class with automatic dependency injection
     *
     * @param string $class Class name to resolve
     * @param array $arguments Additional arguments
     * @return object Resolved instance
     */
    protected function resolveClass($class, $arguments = [])
    {
        try {
            $reflector = new ReflectionClass($class);
            
            // Cek jika class tidak bisa di-instantiate (abstract/interface)
            if (!$reflector->isInstantiable()) {
                // Coba resolve dari registry jika abstract/interface
                $normalizedClass = $this->normalizeName($class);
                if (isset($this->registry[$normalizedClass])) {
                    return $this->make($normalizedClass, ...$arguments);
                }
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
            // Jika ada additional arguments yang diberikan, gunakan mereka
            if ($additionalArgsIndex < count($additionalArguments)) {
                $dependencies[] = $additionalArguments[$additionalArgsIndex];
                $additionalArgsIndex++;
                continue;
            }
            
            $dependency = $parameter->getType();
            
            // Jika parameter punya type hint class/interface
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
            
            // Cek jika parameter bisa nullable
            if ($dependency && $dependency->allowsNull()) {
                $dependencies[] = null;
                continue;
            }
            
            // Jika semua gagal
            throw new Exception(
                "Cannot resolve dependency \${$parameter->getName()} " .
                "in {$parameter->getDeclaringClass()->getName()}::{$parameter->getDeclaringFunction()->getName()}"
            );
        }
        
        // Tambahkan sisa additional arguments
        for (; $additionalArgsIndex < count($additionalArguments); $additionalArgsIndex++) {
            $dependencies[] = $additionalArguments[$additionalArgsIndex];
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
        if ($implementation instanceof Closure) {
            return $this->register($interface, $implementation);
        }
        
        return $this->register($interface, function($di) use ($implementation) {
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
        if ($implementation instanceof Closure) {
            return $this->registerSingleton($interface, $implementation);
        }
        
        return $this->registerSingleton($interface, function($di) use ($implementation) {
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

    /**
     * Get instance from cache or create new one
     * 
     * @param string $name Service name
     * @return mixed
     */
    public function getCached($name)
    {
        $normalizedName = $this->normalizeName($name);
        return $this->resolvedInstances[$normalizedName] ?? null;
    }
}

// EOF