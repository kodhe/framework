<?php namespace Kodhe\Framework\Foundation\Service;

use FilesystemIterator;
use Kodhe\Framework\Container\Binding\BindingInterface;
use Kodhe\Framework\Http\Response;
use Kodhe\Framework\Http\Request;
use Kodhe\Framework\Support\Autoloader;

/**
 * Core Application
 */
class ServiceManager
{
    /**
     * @var ServiceLocator
     */
    protected $registry;

    /**
     * @var Autoloader object
     */
    protected $autoloader;

    /**
     * @var BindingInterface Dependency object
     */
    protected $dependencies;

    /**
     * @var Request Current request
     */
    protected $request;

    /**
     * @var Response Current response
     */
    protected $response;

    /**
     * @param Autoloader $autoloader Autoloader instance
     * @param BindingInterface $dependencies Dependency object for this application
     * @param ServiceLocator $registry Application component provider registry
     */
    public function __construct(BindingInterface $dependencies, ServiceLocator $registry)
    {
        $this->autoloader = Autoloader::getInstance();
        $this->dependencies = $dependencies;
        $this->registry = $registry;
    }

    /**
     * Set request
     */
    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    /**
     * Get request
     */
    public function getRequest(): ?Request
    {
        return $this->request;
    }

    /**
     * Set response
     */
    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }

    /**
     * Get response
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }

    /**
     * Setup addons from directory
     *
     * @param string $path Path to addon folder
     */
    public function setupAddons($path): void
    {
        if (!is_dir($path)) {
            log_message('error', "Addon path does not exist: {$path}");
            return;
        }

        try {
            $folders = new FilesystemIterator($path, FilesystemIterator::UNIX_PATHS | FilesystemIterator::SKIP_DOTS);

            foreach ($folders as $item) {
                if ($item->isDir()) {
                    $addonPath = $item->getPathname();

                    // for now only setup those that define an addon.setup file
                    if (!file_exists($addonPath . '/addon.setup.php')) {
                        continue;
                    }

                    $this->addProvider($addonPath);
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Error setting up addons: ' . $e->getMessage());
        }
    }

    /**
     * Get dependencies
     *
     * @return BindingInterface Dependency object
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }

    /**
     * Check for a component provider
     *
     * @param string $prefix Component name/prefix
     * @return bool Exists?
     */
    public function has($prefix): bool
    {
        return $this->registry->has($prefix);
    }

    /**
     * Get a component provider
     *
     * @param string $prefix Component name/prefix
     * @return ServiceProvider Component provider
     */
    public function get($prefix)
    {
        return $this->registry->get($prefix);
    }

    /**
     * Get prefixes
     *
     * @return array of all prefixes
     */
    public function getPrefixes(): array
    {
        return array_keys($this->registry->all());
    }

    /**
     * Get namespaces
     *
     * @return array [prefix => namespace]
     */
    public function getNamespaces(): array
    {
        return $this->forward('getNamespace');
    }

    /**
     * List vendors
     *
     * @return array of vendor names
     */
    public function getVendors(): array
    {
        $vendors = $this->forward('getVendor');
        return array_unique(array_keys($vendors));
    }

    /**
     * Get all providers
     *
     * @return array of all providers [prefix => object]
     */
    public function getProviders(): array
    {
        return $this->registry->all();
    }

    /**
     * Get all models
     *
     * @return array [prefix:model-alias => fqcn]
     */
    public function getModels(): array
    {
        return $this->forward('getModels');
    }

    /**
     * Set up class aliases
     */
    public function setClassAliases(): void
    {
        $this->forward('setClassAliases');
    }

    /**
     * Add a provider
     *
     * @param string $path Root path for the provider namespace
     * @param string $file Name of the setup file
     * @param string|null $prefix Prefix for our service provider [optional]
     * @return ServiceProvider
     * @throws \Exception
     */
    public function addProvider($path, $file = 'addon.setup.php', $prefix = null)
    {
        $path = rtrim($path, '/');
        $setupFile = $path . '/' . $file;

        $prefix = $prefix ?: basename($path);

        if (!file_exists($setupFile)) {
            return;
            //throw new \Exception("Cannot read setup file: {$setupFile}");
        }

        // We found another addon with the same name. This could be a problem.
        if ($this->registry->has($prefix)) {
            $provider = $this->registry->get($prefix);
            
            // Check if existing provider is pro version or first-party
            if ($provider instanceof ServiceProvider) {
                $providerPath = $provider->getPath();
                
                // Pro version has precedence
                if (strpos($providerPath, 'Addons/pro/levelups') !== false) {
                    log_message('debug', "Pro version addon already loaded for prefix '{$prefix}', skipping.");
                    return $provider;
                }
                
                // First-party add-ons have higher precedence as well
                if (strpos($providerPath, 'ExpressionEngine/Addons') !== false) {
                    log_message('debug', "First-party addon already loaded for prefix '{$prefix}', skipping.");
                    return $provider;
                }
            }
        }

        $config = require $setupFile;
        
        if (!is_array($config)) {
            throw new \Exception("Setup file must return an array: {$setupFile}");
        }

        $provider = new ServiceProvider(
            $this->dependencies,
            $path,
            $config
        );

        $provider->setPrefix($prefix);
        $provider->setAutoloader($this->autoloader);

        $this->registry->register($prefix, $provider);

        log_message('debug', "Registered provider: {$prefix} from {$path}");

        return $provider;
    }

    /**
     * Helper function to collect data from all providers
     *
     * @param string $method Method to forward to
     * @return array Array of method results, nested arrays are flattened
     */
    public function forward($method): array
    {
        $result = [];

        foreach ($this->registry->all() as $prefix => $provider) {
            if (!method_exists($provider, $method)) {
                continue;
            }

            $forwarded = $provider->$method();

            if (is_array($forwarded)) {
                foreach ($forwarded as $key => $value) {
                    $result[$prefix . ':' . $key] = $value;
                }
            } else {
                $result[$prefix] = $forwarded;
            }
        }

        return $result;
    }
}

// EOF