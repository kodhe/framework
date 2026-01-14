<?php namespace Kodhe\Framework\Routing;

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
        
        $this->enable_query_strings = (!is_cli() && config_item('enable_query_strings') === true);
        
        // Load routes
        $this->_load_routes();
        
        // Set routing from constructor if provided
        if (is_array($routing)) {
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
        
        $this->_set_routing();
        
        log_message('debug', 'LegacyRouter Class Initialized');
    }
    
    /**
     * Load routes from config files (CI3 style)
     */
    protected function _load_routes(): void
    {
        $route = [];
        
        // Load main routes
        if (file_exists(APPPATH . 'config/routes.php')) {
            include(APPPATH . 'config/routes.php');
        }
        
        // Load environment routes
        if (file_exists(APPPATH . 'config/' . ENVIRONMENT . '/routes.php')) {
            include(APPPATH . 'config/' . ENVIRONMENT . '/routes.php');
        }
        
        // Validate & get reserved routes
        if (isset($route) && is_array($route)) {
            // Set default controller
            if (isset($route['default_controller'])) {
                $this->default_controller = $route['default_controller'];
            } else {
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
            $this->default_controller = 'welcome';
        }
    }
    
    /**
     * Set routing (CI3 style)
     */
    public function _set_routing(): void
    {
        // Routes sudah diload di constructor
        
        if ($this->enable_query_strings) {
            // Query string routing
            $this->_set_query_string_routing();
            return;
        }
        
        // Is there anything to parse?
        if ($this->uri->uri_string !== '') {
            $this->_parse_routes();
        } else {
            $this->_set_default_controller();
        }
    }
    
    /**
     * Set query string routing
     */
    protected function _set_query_string_routing(): void
    {
        // If the directory is set at this time, it means an override exists
        if (!isset($this->directory)) {
            $_d = config_item('directory_trigger');
            $_d = isset($_GET[$_d]) ? trim($_GET[$_d], " \t\n\r\0\x0B/") : '';
            
            if ($_d !== '') {
                $this->uri->filter_uri($_d);
                $this->set_directory($_d);
            }
        }
        
        $_c = trim(config_item('controller_trigger'));
        if (!empty($_GET[$_c])) {
            $this->uri->filter_uri($_GET[$_c]);
            $this->set_class($_GET[$_c]);
            
            $_f = trim(config_item('function_trigger'));
            if (!empty($_GET[$_f])) {
                $this->uri->filter_uri($_GET[$_f]);
                $this->set_method($_GET[$_f]);
            }
            
            $this->uri->rsegments = [
                1 => $this->class,
                2 => $this->method,
            ];
        } else {
            $this->_set_default_controller();
        }
    }
    
    /**
     * Set default controller (CI3 style)
     */
    protected function _set_default_controller(): void
    {
        if (empty($this->default_controller)) {
            log_message('error', 'Default controller is empty');
            
            $default = config_item('default_controller');
            if (!empty($default)) {
                $this->default_controller = $default;
            } else {
                $this->default_controller = 'welcome';
            }
        }
        
        // Parse controller/method
        $class = $this->default_controller;
        $method = 'index';
        
        if (sscanf($this->default_controller, '%[^/]/%s', $class, $method) !== 2) {
            $method = 'index';
        }
        
        // Clean class name
        $class = str_replace('.php', '', $class);
        
        // Check if controller file exists
        $controller_file = APPPATH . 'controllers/' . $this->directory . ucfirst($class) . '.php';
        
        if (!file_exists($controller_file)) {
            // Try with suffix
            $suffix = config_item('controller_suffix');
            if ($suffix) {
                $controller_file_with_suffix = APPPATH . 'controllers/' . $this->directory . ucfirst($class) . $suffix . '.php';
                if (file_exists($controller_file_with_suffix)) {
                    $controller_file = $controller_file_with_suffix;
                    $class = $class . $suffix;
                }
            }
            
            // Try lowercase
            if (!file_exists($controller_file)) {
                $controller_file_lower = APPPATH . 'controllers/' . $this->directory . strtolower($class) . '.php';
                if (file_exists($controller_file_lower)) {
                    $controller_file = $controller_file_lower;
                    $class = strtolower($class);
                }
            }
            
            // If still not found
            if (!file_exists($controller_file)) {
                log_message('error', 'Default controller not found: ' . $controller_file);
                $this->class = 'FileNotFound';
                $this->method = 'index';
                return;
            }
        }
        
        $this->set_class($class);
        $this->set_method($method);
        
        // Assign routed segments
        $this->uri->rsegments = [
            1 => $class,
            2 => $method,
        ];
    }
    
    /**
     * Parse routes (CI3 style)
     */
    protected function _parse_routes(): void
    {
        $uri = implode('/', $this->uri->segments);
        $http_verb = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : 'cli';
        
        foreach ($this->routes as $key => $val) {
            if (is_array($val)) {
                $val = array_change_key_case($val, CASE_LOWER);
                if (isset($val[$http_verb])) {
                    $val = $val[$http_verb];
                } else {
                    continue;
                }
            }
            
            // Convert CI3 patterns
            $key = str_replace([':any', ':num'], ['[^/]+', '[0-9]+'], $key);
            
            if (preg_match('#^' . $key . '$#', $uri, $matches)) {
                if (!is_string($val) && is_callable($val)) {
                    array_shift($matches);
                    $val = call_user_func_array($val, $matches);
                } elseif (strpos($val, '$') !== false && strpos($key, '(') !== false) {
                    $val = preg_replace('#^' . $key . '$#', $val, $uri);
                }
                
                $this->_set_request(explode('/', $val));
                return;
            }
        }
        
        $this->_set_request(array_values($this->uri->segments));
    }
    
    /**
     * Set request segments
     */
    protected function _set_request(array $segments = []): void
    {
        $segments = $this->_validate_request($segments);
        
        if (empty($segments)) {
            $this->_set_default_controller();
            return;
        }
        
        $this->set_class($segments[0]);
        
        if (isset($segments[1])) {
            $this->set_method($segments[1]);
        } else {
            $segments[1] = 'index';
        }
        
        array_unshift($segments, null);
        unset($segments[0]);
        
        $this->uri->rsegments = $segments;
    }
    
    /**
     * Validate request segments
     */
    protected function _validate_request($segments): array
    {
        $c = count($segments);
        $directory_override = isset($this->directory);
        
        while ($c-- > 0) {
            $test = $this->directory
                . ucfirst($this->translate_uri_dashes === true ? str_replace('-', '_', $segments[0]) : $segments[0]);
            
            if (!file_exists(APPPATH . 'controllers/' . $test . '.php')
                && $directory_override === false
                && is_dir(APPPATH . 'controllers/' . $this->directory . $segments[0])) {
                $this->set_directory(array_shift($segments), true);
                continue;
            }
            
            return $segments;
        }
        
        return $segments;
    }
    
    /**
     * Set class
     */
    public function set_class($class): void
    {
        // Clean class name
        $class = str_replace(['/', '.'], '', $class);
        
        $suffix = config_item('controller_suffix');
        if ($suffix && strpos($class, $suffix) === false) {
            // Check if file with suffix exists
            $controller_file = APPPATH . 'controllers/' . $this->directory . ucfirst($class) . $suffix . '.php';
            if (file_exists($controller_file)) {
                $class .= $suffix;
            }
        }
        
        $this->class = $class;
    }
    
    /**
     * Set method
     */
    public function set_method($method): void
    {
        $this->method = $method;
    }
    
    /**
     * Set directory
     */
    public function set_directory($dir, $append = false): void
    {
        if ($append !== true || empty($this->directory)) {
            $this->directory = str_replace('.', '', trim($dir, '/')) . '/';
        } else {
            $this->directory .= str_replace('.', '', trim($dir, '/')) . '/';
        }
    }
    
    /**
     * Fetch class
     */
    public function fetch_class(): string
    {
        return $this->class;
    }
    
    /**
     * Fetch method
     */
    public function fetch_method(): string
    {
        return $this->method;
    }
    
    /**
     * Fetch directory
     */
    public function fetch_directory(): string
    {
        return $this->directory ?? '';
    }
    
    /**
     * Get routing info for hybrid system
     */
    public function getRouting(): ?array
    {
        $directory = $this->fetch_directory();
        $class = $this->fetch_class();
        $method = $this->fetch_method();
        
        if (empty($class)) {
            return null;
        }
        
        // Get segments from rsegments
        $segments = [];
        if (!empty($this->uri->rsegments)) {
            $source = $this->uri->rsegments;
            $startIndex = (count($source) > 2) ? 2 : 1;
            $segments = array_slice($source, $startIndex);
            
            if (isset($segments[0]) && $segments[0] === $method) {
                array_shift($segments);
            }
        }
        
        return [
            'directory' => $directory,
            'class' => $class,
            'method' => $method,
            'segments' => $segments,
        ];
    }
}