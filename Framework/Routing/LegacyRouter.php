<?php namespace Kodhe\Framework\Routing;

use Kodhe\Framework\Http\Request;
use Kodhe\Framework\Http\Response;
use Kodhe\Framework\Support\Legacy\URI;

abstract class LegacyRouter
{
    public $routes = [];
    public $class = '';
    public $method = 'index';
    public $directory;
    public $default_controller;
    public $translate_uri_dashes = false;
    public $enable_query_strings = false;
    public URI $uri;

    
    public function __construct($routing = null)
    {
        
        $this->uri = new URI();

        $this->enable_query_strings = (!is_cli() && app()->config->item('enable_query_strings') === true);
        
        // Load routes
        $this->_load_routes();
        
        // PERBAIKAN: Pastikan $routing tidak null
        if (is_array($routing) && isset($routing['directory'])) {
            $this->set_directory($routing['directory']);
        }
        
        $this->_set_routing();
        
        // PERBAIKAN: Tambah null check
        if (is_array($routing)) {
            if (!empty($routing['controller'])) {
                $this->set_class($routing['controller']);
            }
            if (!empty($routing['function'])) {
                $this->set_method($routing['function']);
            }
        }
        
        log_message('info', 'Router Class Initialized');
    }
    
    /**
     * Load routes from config files
     */
    protected function _load_routes(): void
    {
        $route = [];
        
        // Load main routes
        if (file_exists(APPPATH.'config/routes.php')) {
            include(APPPATH.'config/routes.php');
        }

        // Load environment routes
        if (file_exists(APPPATH.'config/'.ENVIRONMENT.'/routes.php')) {
            include(APPPATH.'config/'.ENVIRONMENT.'/routes.php');
        }

        // Validate & get reserved routes
        if (isset($route) && is_array($route)) {
            // Set default controller dengan fallback
            if (isset($route['default_controller'])) {
                $this->default_controller = $route['default_controller'];
            } else {
                // Default fallback jika tidak ada di config
                $this->default_controller = 'welcome';
            }
            
            // Set translate uri dashes
            if (isset($route['translate_uri_dashes'])) {
                $this->translate_uri_dashes = $route['translate_uri_dashes'];
            }
            
            // Remove reserved keys
            unset($route['default_controller'], $route['translate_uri_dashes']);
            $this->routes = $route;
        } else {
            // Jika tidak ada route config, set default
            $this->default_controller = 'welcome';
        }
        
        log_message('debug', 'Default controller: ' . $this->default_controller);
    }
    
    public function _set_routing()
    {
        // Routes sudah diload di constructor
        
        if ($this->enable_query_strings)
        {
            // If the directory is set at this time, it means an override exists, so skip the checks
            if ( ! isset($this->directory))
            {
                $_d = app()->config->item('directory_trigger');
                $_d = isset($_GET[$_d]) ? trim($_GET[$_d], " \t\n\r\0\x0B/") : '';

                if ($_d !== '')
                {
                    $this->uri->filter_uri($_d);
                    $this->set_directory($_d);
                }
            }

            $_c = trim(app()->config->item('controller_trigger'));
            if ( ! empty($_GET[$_c]))
            {
                $this->uri->filter_uri($_GET[$_c]);
                $this->set_class($_GET[$_c]);

                $_f = trim(app()->config->item('function_trigger'));
                if ( ! empty($_GET[$_f]))
                {
                    $this->uri->filter_uri($_GET[$_f]);
                    $this->set_method($_GET[$_f]);
                }

                $this->uri->rsegments = array(
                    1 => $this->class,
                    2 => $this->method
                );
            }
            else
            {
                $this->_set_default_controller();
            }

            return;
        }

        // Is there anything to parse?
        if ($this->uri->uri_string !== '')
        {
            $this->_parse_routes();
        }
        else
        {
            $this->_set_default_controller();
        }
    }

    protected function _set_default_controller()
    {
        log_message('debug', '_set_default_controller called');
        log_message('debug', 'Default controller config: ' . $this->default_controller);
        log_message('debug', 'Current directory: ' . $this->directory);
        
        if (empty($this->default_controller))
        {
            log_message('error', 'Default controller is empty');
            
            // Coba dapatkan dari config
            $default = app()->config->item('default_controller');
            if (!empty($default)) {
                $this->default_controller = $default;
                log_message('debug', 'Using default controller from config: ' . $default);
            } else {
                // Fallback ke welcome
                $this->default_controller = 'welcome';
                log_message('debug', 'Using fallback default controller: welcome');
            }
        }

        // Is the method being specified?
        $class = $this->default_controller;
        $method = 'index';
        
        if (sscanf($this->default_controller, '%[^/]/%s', $class, $method) !== 2)
        {
            // Default method is index
            $method = 'index';
        }
        
        // Clean class name
        $class = str_replace('.php', '', $class);
        
        log_message('debug', 'Parsed default controller - Class: ' . $class . ', Method: ' . $method);
        log_message('debug', 'Looking for controller: ' . APPPATH.'controllers/'.$this->directory.ucfirst($class).'.php');

        // Check if controller file exists
        $controller_file = APPPATH.'controllers/'.$this->directory.ucfirst($class).'.php';
        if ( ! file_exists($controller_file))
        {
            log_message('error', 'Controller file not found: ' . $controller_file);
            
            // Coba dengan suffix
            $suffix = app()->config->item('controller_suffix');
            if ($suffix) {
                $controller_file_with_suffix = APPPATH.'controllers/'.$this->directory.ucfirst($class).$suffix.'.php';
                log_message('debug', 'Trying with suffix: ' . $controller_file_with_suffix);
                
                if (file_exists($controller_file_with_suffix)) {
                    $controller_file = $controller_file_with_suffix;
                    $class = $class . $suffix;
                    log_message('debug', 'Found controller with suffix: ' . $controller_file);
                }
            }
            
            // Jika masih tidak ditemukan, coba lowercase
            if (!file_exists($controller_file)) {
                $controller_file_lower = APPPATH.'controllers/'.$this->directory.strtolower($class).'.php';
                log_message('debug', 'Trying lowercase: ' . $controller_file_lower);
                
                if (file_exists($controller_file_lower)) {
                    $controller_file = $controller_file_lower;
                    $class = strtolower($class);
                    log_message('debug', 'Found lowercase controller: ' . $controller_file);
                }
            }
            
            // Jika tetap tidak ditemukan, show error
            if (!file_exists($controller_file)) {
                log_message('error', 'Default controller not found after all attempts');
                
                // Jangan langsung show_error, biarkan system handle 404
                $this->class = 'Kodhe\Controllers\Error\FileNotFound';
                $this->method = 'index';
                return;
            }
        }

        $this->set_class($class);
        $this->set_method($method);

        // Assign routed segments, index starting from 1
        $this->uri->rsegments = array(
            1 => $class,
            2 => $method
        );

        log_message('debug', 'Default controller set: ' . $class . '::' . $method . '()');
    }

    // --------------------------------------------------------------------

    protected function _validate_request($segments)
    {
        $c = count($segments);
        $directory_override = isset($this->directory);

        while ($c-- > 0)
        {
            $test = $this->directory
                .ucfirst($this->translate_uri_dashes === TRUE ? str_replace('-', '_', $segments[0]) : $segments[0]);

            if ( ! file_exists(APPPATH.'controllers/'.$test.'.php')
                && $directory_override === FALSE
                && is_dir(APPPATH.'controllers/'.$this->directory.$segments[0])
            )
            {
                $this->set_directory(array_shift($segments), TRUE);
                continue;
            }

            return $segments;
        }

        // This means that all segments were actually directories
        return $segments;
    }

    protected function _parse_routes()
    {
        $uri = implode('/', $this->uri->segments);

        $http_verb = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : 'cli';

        foreach ($this->routes as $key => $val)
        {
            if (is_array($val))
            {
                $val = array_change_key_case($val, CASE_LOWER);
                if (isset($val[$http_verb]))
                {
                    $val = $val[$http_verb];
                }
                else
                {
                    continue;
                }
            }

            $key = str_replace(array(':any', ':num'), array('[^/]+', '[0-9]+'), $key);

            if (preg_match('#^'.$key.'$#', $uri, $matches))
            {
                if ( ! is_string($val) && is_callable($val))
                {
                    array_shift($matches);

                    $val = call_user_func_array($val, $matches);
                }
                elseif (strpos($val, '$') !== FALSE && strpos($key, '(') !== FALSE)
                {
                    $val = preg_replace('#^'.$key.'$#', $val, $uri);
                }

                $this->_set_request(explode('/', $val));
                return;
            }
        }

        $this->_set_request(array_values($this->uri->segments));
    }


    public function set_class($class)
    {
        log_message('debug', 'set_class called with: ' . $class);
        
        // Clean class name
        $class = str_replace(array('/', '.'), '', $class);
        
        $suffix = app()->config->item('controller_suffix');
        if ($suffix && strpos($class, $suffix) === FALSE)
        {
            // Cek jika file dengan suffix ada
            $controller_file = APPPATH.'controllers/'.$this->directory.ucfirst($class).$suffix.'.php';
            if (file_exists($controller_file)) {
                $class .= $suffix;
                log_message('debug', 'Adding suffix to class: ' . $class);
            }
        }

        $this->class = $class;
        log_message('debug', 'Class set to: ' . $this->class);
    }


    public function fetch_class()
    {
        return $this->class;
    }


    public function set_method($method)
    {
        $this->method = $method;
        log_message('debug', 'Method set to: ' . $this->method);
    }


    public function fetch_method()
    {
        return $this->method;
    }


    public function set_directory($dir, $append = FALSE)
    {
        if ($append !== TRUE OR empty($this->directory))
        {
            $this->directory = str_replace('.', '', trim($dir, '/')).'/';
        }
        else
        {
            $this->directory .= str_replace('.', '', trim($dir, '/')).'/';
        }
        
        log_message('debug', 'Directory set to: ' . $this->directory);
    }

    public function fetch_directory()
    {
        return $this->directory;
    }
    
    /**
     * Override untuk handle null route
     */
    public function _set_module_path(string &$_route = ''): void
    {
        if (empty($_route)) {
            return;
        }
        
        $_route = (string)$_route;
        
        // Original implementation
        $parsed = sscanf($_route, '%[^/]/%[^/]/%[^/]/%s', $module, $directory, $class, $method);
        
        // ... rest of the implementation
    }
    
    /**
     * Untuk compatibility dengan CI 3
     */
    public function _set_overrides(array $routing): void
    {
        if (isset($routing['directory'])) {
            $this->set_directory($routing['directory']);
        }
        
        if (isset($routing['controller'])) {
            $this->set_class($routing['controller']);
        }
        
        if (isset($routing['function'])) {
            $this->set_method($routing['function']);
        }
    }
    
    /**
     * Match request to route
     */
    public function matchRequest(Request $request): ?array
    {
        // Legacy router uses _set_routing instead of request matching
        $uri = $request->getUri()->getQuery();
        $uri = trim($uri, '/');
        
        // Simulate legacy routing by setting up URI
        $this->uri->uri_string = $uri;
        $this->uri->segments = explode('/', $uri);
        
        // Reset
        $this->class = '';
        $this->method = 'index';
        $this->directory = '';
        
        // Parse routing
        $this->_set_routing();
        
        return $this->getRouting();
    }

    /**
     * Execute route
     */
    public function execute(array $routing, Request $request, Response $response): mixed
    {
        // Legacy router doesn't execute directly, Kernel handles it
        return $response;
    }
    
}