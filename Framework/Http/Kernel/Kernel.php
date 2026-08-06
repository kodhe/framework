<?php namespace Kodhe\Framework\Http\Kernel;

use Kodhe\Framework\Container\Container;
use Kodhe\Error\FileNotFound;
use Kodhe\Error\CPException;
use Kodhe\Cli\Cli;
use Kodhe\Framework\Http\Response;
use Kodhe\Framework\Http\Request;
use Kodhe\Framework\Foundation\Service\ServiceLocator;
use Kodhe\Framework\Foundation\Service\ServiceManager;
use Kodhe\Framework\Support\Facades\Facade;
use Kodhe\Framework\Routing\RoutingManager;
use Kodhe\Framework\Routing\ControllerExecutor;
use Kodhe\Framework\Routing\ModernRouter;
use Kodhe\Framework\Routing\Router;
use Kodhe\Framework\Http\Middleware\MiddlewareRegistry;
use Kodhe\Framework\Http\Middleware\MiddlewareInterface;

/**
 * Core Application Kernel
 */
class Kernel
{
    /**
     * @var bool Application done booting?
     */
    protected $booted = false;

    /**
     * @var bool Application started?
     */
    protected $running = false;

    /**
     * @var ServiceManager Application instance
     */
    protected $servicemanager = null;

    /**
     * @var Facade Facade instance
     */
    protected Facade $facade;

    /**
     * @var Container Dependency injection container
     */
    protected $container;

    /**
     * @var RoutingManager Routing manager
     */
    protected $routingManager;

    /**
     * @var ControllerExecutor Controller executor
     */
    protected $controllerExecutor;

    /**
     * @var ModernRouter Modern router instance
     */
    protected $modernRouter;

    /**
     * @var MiddlewarePipelineManager Middleware pipeline manager
     */
    protected $pipelineManager;

    protected $router_ready = false;
    
    /**
     * Constructor
     */
    public function __construct(Container $container = null)
    {
        // Initialize container
        $this->container = $container ?? new Container();
        $this->facade = Facade::getInstance();

        if (method_exists($this->container, 'setThrowOnDuplicate')) {
            $this->container->setThrowOnDuplicate(false);
        }       
    }

    /**
     * Boot the servicemanager
     */
    public function boot()
    {
        // Call pre_system hook
        $this->callHook('pre_system');
        $this->setTimeLimit(300);

        $this->servicemanager = $this->loadApplicationCore();
        // Set class aliases
        $this->servicemanager->setClassAliases();

        $this->startBenchmark();
        
        $this->initializeFacade();
        $this->initializeRoutingComponents();

        $this->includeBaseController();
        $this->booted = true;

        register_shutdown_function([$this, 'shutdown']);
    }




    /**
     * Initialize routing components using container
     */
    protected function initializeRoutingComponents(): void
    {
        log_message('debug', 'Initializing routing components');
        
        // Initialize middleware pipeline manager
        $this->pipelineManager = new MiddlewarePipelineManager($this->container, $this->getConfigPath());
        
        // Register RoutingManager with modern router support
        $this->container->registerSingleton('RoutingManager', function ($di) {
            // Create legacy router as fallback
            $legacyRouter = new Router();
            
            // Create routing manager with configuration
            $manager = new RoutingManager();
            
            // Configure to support modern routing
            $config = [
                'enable_modern_routing' => true,
                'cache_routes' => ENVIRONMENT === 'production',
                'router_type' => 'hybrid', // Supports both types
            ];
            
            $manager->setConfig($config);
            
            // Clear cache in development
            if (ENVIRONMENT !== 'production') {
                $manager->clearCache();
            }
            
            return $manager;
        });

        // Register ControllerExecutor with all dependencies
        $this->container->registerSingleton('ControllerExecutor', function ($di) {
            $facade = $this->facade;
            $routingManager = $di->make('RoutingManager');
            
            $executor = new ControllerExecutor($facade, $routingManager);
            
 
            
            // Set container reference
            $executorReflection = new \ReflectionClass($executor);
            if ($executorReflection->hasProperty('container')) {
                $containerProp = $executorReflection->getProperty('container');
                $containerProp->setAccessible(true);
                $containerProp->setValue($executor, $di);
            }
            
            return $executor;
        });

        // Get instances from container
        $this->routingManager = $this->container->make('RoutingManager');
        $this->controllerExecutor = $this->container->make('ControllerExecutor');

        // Register these instances for easy access
        $this->container->register('routing.manager', $this->routingManager);
        $this->container->register('controller.executor', $this->controllerExecutor);
        $this->container->register('modern.router', $this->modernRouter);
        $this->container->register('pipeline.manager', $this->pipelineManager);
        
        log_message('debug', 'Routing components initialized');
    }

    /**
     * Initialize facade and services with container support
     */
    protected function initializeFacade(): void
    {
        $this->facade->set('load', $this->container->make('load'));
        $this->facade->set('input', $this->container->make('input'));
        $this->facade->set('hooks', $this->container->make('hooks'));
        $this->facade->set('lang', $this->container->make('lang'));
        $this->facade->set('config', $this->container->make('config'));
        $this->facade->set('router', $this->container->make('router'));
        $this->facade->set('uri', $this->container->make('uri'));
        $this->facade->set('output', $this->container->make('output'));
        $this->facade->set('utf8', $this->container->make('utf8'));
        $this->facade->set('security', $this->container->make('security'));
        $this->facade->set('benchmark', $this->container->make('benchmark'));
        

        // Register modern router di facade jika belum ada
        if (!$this->facade->has('route')) {
            $this->facade->set('route', $this->modernRouter);
        }

        $this->container->make('load')->setFacade($this->facade);
        // Also make container available through facade
        $this->facade->set('di', $this->container);
    }

    /**
     * Run servicemanager with request
     */
    public function run(Request $request)
    {
        if (!$this->booted) {
            throw new \Exception('Application must be booted before running.');
        }

        $this->running = true;
        $servicemanager = $this->servicemanager;

        // Set request
        $servicemanager->setRequest($request);

        // Register request in container
        $this->container->register('Request', $request);

        // Handle CLI requests
        if (defined('REQ') && REQ === 'CLI') {
            return $this->bootCli();
        }

        // Handle boot only mode
        if (defined('BOOT_ONLY')) {
            return $this->bootOnly($request);
        }

        // Call pre_controller hook
        $this->callHook('pre_controller');

        // Resolve routing using routing manager dari container
        $routingManager = $this->container->make('RoutingManager');
        $routing = $routingManager->resolve($request);

        log_message('debug', 'Resolved routing: ' . print_r($routing, true));

        // Register routing in container
        $this->container->register('current.routing', $routing);

        // Set response
        $response = new Response();
        $servicemanager->setResponse($response);
        $this->container->register('Response', $response);

        // Run middleware pipeline for modern routing
        if (isset($routing['type']) && $routing['type'] === 'modern') {
            $middlewareResponse = $this->pipelineManager->run($servicemanager, $routing);
            
            if ($middlewareResponse !== null) {
                log_message('debug', 'Middleware pipeline completed');
                return $middlewareResponse;
            }
        }

        // Execute controller menggunakan controller executor
        $controllerExecutor = $this->container->make('ControllerExecutor');
        $controllerExecutor->execute($routing);

        // Call hooks
        $this->callHook('post_controller_constructor');
        $this->enableProfiler();
        $this->callHook('post_controller');
        $this->callHook('display_override');

        return $servicemanager->getResponse();
    }

    /**
     * Run middleware pipeline for modern routing using PipelineManager
     */
    protected function runMiddlewarePipeline($servicemanager, $routing)
    {
        // Delegate to pipeline manager
        return $this->pipelineManager->run($servicemanager, $routing);
    }

    /**
     * Include base controller
     */
    public function includeBaseController(): void
    {
  
        if (!class_exists(\CI_Controller::class, false) && 
            class_exists(\Kodhe\Framework\Http\Controllers\BaseController::class)) {
            class_alias(\Kodhe\Framework\Http\Controllers\BaseController::class, 'CI_Controller');
        }
        
        $subclassPrefix = $this->container->make('config')->item('subclass_prefix') ?? 'MY_';
        $controllerFile = resolve_path(APPPATH, 'Core') . $subclassPrefix . 'Controller.php';
        
        if (file_exists($controllerFile) && !class_exists('App\\' . $subclassPrefix . 'Controller', false)) {
            require_once $controllerFile;
        }
    }

    /**
     * Start benchmark
     */
    protected function startBenchmark(): void
    { 
        $BM = $this->container->make('benchmark');
        $BM->mark('total_execution_time_start');
    }


    /**
     * Boot CLI
     */
    protected function bootCli()
    {
        $this->includeBaseController();
        
        $cli = new Cli();
        $cli->process();
        die();
    }

    /**
     * Boot only mode
     */
    protected function bootOnly(Request $request)
    {
        if (defined('INSTALLER') && INSTALLER) {
            $routing = [
                'class' => 'wizard',
                'method' => 'index',
                'segments' => [],
                'type' => 'legacy',
                'source' => 'installer'
            ];
            
            $controllerExecutor = $this->container->make('ControllerExecutor');
            $controllerExecutor->execute($routing);
            return;
        }

        $this->includeBaseController();
        
        if (class_exists('\BaseController')) {
            \BaseController::setFacade($this->getFacade());
            new \BaseController();
        }
    }

    /**
     * Enable profiler
     */
    protected function enableProfiler()
    {
        if (function_exists('get_instance')) {
            $CI = get_instance();
            // $CI->output->enable_profiler();
        }
    }

    /**
     * Load servicemanager core with container integration
     */
    public function loadApplicationCore()
    {
        if (!is_null($this->servicemanager)) {
            return $this->servicemanager;
        }

        $dependencies = $this->container;
        
        // Register container itself for self-reference
        $dependencies->registerSingleton('di', $dependencies);

        $providers = new ServiceLocator($dependencies);
        $servicemanager = new ServiceManager($dependencies, $providers);

        // Register servicemanager in container
        $dependencies->registerSingleton('ServiceManager', $servicemanager);

        $provider = $servicemanager->addProvider(
            SYSPATH,
            'Framework/Config/Setup.php',
            'kodhe'
        );

        $provider->setConfigPath($this->getConfigPath());

        $dependencies->register('kodhe', $servicemanager->get('kodhe'));

        // Application Provider
        $appProvider = $servicemanager->addProvider(
            $this->getConfigPath(),
            'app.php',
            'appication'
        );

        if($servicemanager->has('appication')) {
            $dependencies->register('app', $servicemanager->get('appication'));
        }

        // Register servicemanager dengan pola akses berbeda
        $dependencies->register('App', function ($di, $prefix = null) use ($servicemanager) {
            if (isset($prefix)) {
                return $servicemanager->get($prefix);
            }
            return $servicemanager;
        });

        // Register kernel di container
        $dependencies->registerSingleton('Kernel', $this);

        $this->servicemanager = $servicemanager;

        return $servicemanager;
    }

    /**
     * Get config path
     */
    protected function getConfigPath(): string
    {
        return resolve_path(APPPATH, 'config') . DIRECTORY_SEPARATOR;
    }

    /**
     * Set execution time limit
     */
    public function setTimeLimit($t)
    {
        if (function_exists("set_time_limit") && php_sapi_name() !== 'cli') {
            @set_time_limit($t);
        }
    }

    /**
     * Call hook
     */
    protected function callHook($hook)
    {
        if (function_exists('load_class')) {
            $hooks = new \Kodhe\Framework\Support\Legacy\Hooks();
            if ($hooks->call_hook($hook) === false) {
                log_message('debug', "Hook '$hook' tidak ditemukan atau tidak aktif.");
            }
        }
    }

    /**
     * Shutdown process
     */
    public function shutdown()
    {
        $this->callHook('post_system');
    }

    /**
     * Get facade instance
     */
    public function getFacade(): Facade
    {
        return $this->facade;
    }

    /**
     * Get container instance
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Get routing manager instance
     */
    public function getRoutingManager(): RoutingManager
    {
        return $this->routingManager;
    }

    /**
     * Get controller executor instance
     */
    public function getControllerExecutor(): ControllerExecutor
    {
        return $this->controllerExecutor;
    }

    /**
     * Get modern router instance
     */
    public function getModernRouter(): ModernRouter
    {
        return $this->modernRouter;
    }

    public function overrideConfig(array $config): void
    {
        if (isset($this->servicemanager)) {
            // Implementasi override config
        }
    }

    public function overrideRouting(array $routing): void
    {
        if ($this->routingManager) {
            $this->routingManager->overrideRouting($routing);
        }
    }

    /**
     * Check if kernel is booted
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * Check if kernel is running
     */
    public function isRunning(): bool
    {
        return $this->running;
    }
}