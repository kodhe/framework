<?php namespace Kodhe\Framework\Http\Kernel;

use Kodhe\Framework\Container\Container;
use Kodhe\Framework\Support\Facades\Facade;
use Kodhe\Framework\Http\Request;
use Kodhe\Framework\Http\Response;
use Kodhe\Framework\Routing\Router;
use Kodhe\Framework\Routing\ControllerExecutor;

class Kernel
{
    protected $container;
    protected $facade;
    protected $router;
    protected $executor;
    
    protected $booted = false;
    protected $running = false;
    
    public function __construct(Container $container = null)
    {
        $this->container = $container ?? new Container();
        $this->facade = Facade::getInstance();
        
        log_message('debug', 'Kernel constructed');
    }
    
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }
        
        log_message('debug', 'Booting Kernel...');
        
        // Initialize CI3 core (if not already)
        $this->initializeCI3();
        
        // Initialize hybrid router
        $this->router = new Router();
        $this->container->register('router', $this->router);
        $this->facade->set('router', $this->router);
        
        // Initialize controller executor
        $this->executor = new ControllerExecutor($this->facade);
        $this->container->register('controller.executor', $this->executor);
        
        // Set container in facade
        $this->facade->set('di', $this->container);
        
        // Register kernel in container
        $this->container->register('kernel', $this);
        
        $this->booted = true;
        
        log_message('debug', 'Kernel booted successfully');
    }
    
    /**
     * Initialize CI3 core components
     */
    protected function initializeCI3(): void
    {
        // Check if CI3 is already loaded
        if (class_exists('CI_Controller', false)) {
            log_message('debug', 'CI3 already initialized');
            return;
        }
        
        // Load CI3 core
        if (!defined('BASEPATH')) {
            require SYSPATH . 'core/CodeIgniter.php';
        }
        
        log_message('debug', 'CI3 core loaded');
        
        // Get CI instance and register in container
        $CI =& get_instance();
        $this->container->register('ci', $CI);
        
        // Register CI core components in facade
        $this->facade->set('load', $CI->load);
        $this->facade->set('input', $CI->input);
        $this->facade->set('lang', $CI->lang);
        $this->facade->set('config', $CI->config);
        $this->facade->set('uri', $CI->uri);
        $this->facade->set('output', $CI->output);
        $this->facade->set('security', $CI->security);
        $this->facade->set('benchmark', $CI->benchmark);
        
        log_message('debug', 'CI3 components registered');
    }
    
    public function run(Request $request): Response
    {
        if (!$this->booted) {
            $this->boot();
        }
        
        $this->running = true;
        
        log_message('debug', 'Kernel running with request: ' . $request->getUri()->getPath());
        
        // Register request in container
        $this->container->register('request', $request);
        
        // Handle CLI requests
        if (defined('REQ') && REQ === 'CLI') {
            return $this->handleCli($request);
        }
        
        // Call pre_controller hook
        $this->callHook('pre_controller');
        
        // Match route
        $routing = $this->router->matchRequest($request);
        
        if (!$routing) {
            // Fallback to CI3 default controller
            $defaultController = config_item('default_controller') ?? 'welcome';
            $routing = [
                'type' => 'legacy',
                'class' => $defaultController,
                'method' => 'index',
                'segments' => [],
                'source' => 'default',
            ];
            
            log_message('debug', 'Using default controller: ' . $defaultController);
        }
        
        // Store current routing globally
        $GLOBALS['current_route'] = $routing;
        $this->container->register('current.routing', $routing);
        
        log_message('debug', 'Executing routing: ' . print_r($routing, true));
        
        // Create response
        $response = new Response();
        $this->container->register('response', $response);
        
        // Execute controller
        $this->executor->execute($routing);
        
        // Get output from CI if available
        if (function_exists('get_instance')) {
            $CI =& get_instance();
            $ciOutput = $CI->output->get_output();
            
            if (!empty($ciOutput) && empty($response->getBody())) {
                $response->setBody($ciOutput);
            }
        }
        
        // Call post_controller hook
        $this->callHook('post_controller');
        
        log_message('debug', 'Kernel execution completed');
        
        return $response;
    }
    
    /**
     * Handle CLI request
     */
    protected function handleCli(Request $request): Response
    {
        $response = new Response();
        
        // For CLI, we might want to handle differently
        // For now, just return empty response
        log_message('debug', 'CLI request handled');
        
        return $response;
    }
    
    /**
     * Call CI3 hook
     */
    protected function callHook(string $hook): void
    {
        if (function_exists('get_instance')) {
            $CI =& get_instance();
            
            if (isset($CI->hooks) && is_object($CI->hooks)) {
                $CI->hooks->call_hook($hook);
            }
        }
    }
    
    /**
     * Get router instance
     */
    public function getRouter(): Router
    {
        return $this->router;
    }
    
    /**
     * Get controller executor
     */
    public function getControllerExecutor(): ControllerExecutor
    {
        return $this->executor;
    }
    
    /**
     * Get container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }
    
    /**
     * Get facade
     */
    public function getFacade(): Facade
    {
        return $this->facade;
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