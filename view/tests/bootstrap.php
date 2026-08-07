<?php
/**
 * PHPUnit Bootstrap for Kodhe View Component
 * 
 * This bootstrap file sets up the testing environment for the View component.
 * It mocks CodeIgniter 3 core components to allow isolated testing.
 */

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define base path
define('VIEW_COMPONENT_ROOT', dirname(__DIR__));
define('FCPATH', VIEW_COMPONENT_ROOT . '/');
define('APPPATH', FCPATH . 'application/');
define('SYSPATH', FCPATH . 'system/');
define('STORAGEPATH', FCPATH . 'storage/');
define('VIEWPATH', FCPATH . 'views/');

// Create necessary directories if they don't exist
$dirs = [APPPATH, SYSPATH, STORAGEPATH, VIEWPATH];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Autoload composer dependencies
if (file_exists(VIEW_COMPONENT_ROOT . '/vendor/autoload.php')) {
    require_once VIEW_COMPONENT_ROOT . '/vendor/autoload.php';
}

// Mock CodeIgniter super object
class MockCodeIgniterSuperObject
{
    public $load;
    public $output;
    public $session;
    public $router;
    public $input;
    public $config;
    public $response;
    public $benchmark;
    
    private static $instance;
    
    public static function &get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct()
    {
        $this->load = new MockLoader();
        $this->output = new MockOutput();
        $this->session = new MockSession();
        $this->router = new MockRouter();
        $this->input = new MockInput();
        $this->config = new MockConfig();
        $this->response = new MockResponse();
        $this->benchmark = new MockBenchmark();
    }
}

// Mock Loader class
class MockLoader
{
    protected $_ci_view_paths = [];
    
    public function view($view, $vars = [], $return = false)
    {
        if ($return) {
            return "View: {$view}";
        }
        echo "View: {$view}";
    }
    
    public function add_view_path($path)
    {
        $this->_ci_view_paths[$path] = true;
    }
    
    public function get_view_paths()
    {
        return array_keys($this->_ci_view_paths);
    }
}

// Mock Output class
class MockOutput
{
    protected $output = '';
    
    public function append_output($output)
    {
        $this->output .= $output;
    }
    
    public function get_output()
    {
        return $this->output;
    }
}

// Mock Session class
class MockSession
{
    protected $userdata = [];
    
    public function userdata($key = null)
    {
        if ($key === null) {
            return $this->userdata;
        }
        return $this->userdata[$key] ?? null;
    }
    
    public function set_userdata($data)
    {
        if (is_array($data)) {
            $this->userdata = array_merge($this->userdata, $data);
        }
    }
    
    public function unset_userdata($key)
    {
        unset($this->userdata[$key]);
    }
}

// Mock Router class
class MockRouter
{
    public $directory = '';
    public $class = '';
    public $method = 'index';
}

// Mock Input class
class MockInput
{
    public function get($key = null, $default = null)
    {
        return $_GET[$key] ?? $default;
    }
    
    public function user_agent()
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
}

// Mock Config class
class MockConfig
{
    protected $config = [];
    
    public function item($key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
    
    public function load($file, $use_sections = false)
    {
        // Mock config loading
        return true;
    }
}

// Mock Response class
class MockResponse
{
    protected $body = '';
    protected $statusCode = 200;
    
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }
    
    public function getBody()
    {
        return $this->body;
    }
    
    public function send($withHeaders = true)
    {
        echo $this->body;
        return $this;
    }
    
    public function setStatusCode($code)
    {
        $this->statusCode = $code;
        return $this;
    }
}

// Mock Benchmark class
class MockBenchmark
{
    protected $markers = [];
    
    public function mark($name)
    {
        $this->markers[$name] = microtime(true);
    }
    
    public function elapsed_time($point1 = '', $point2 = '')
    {
        if ($point1 === 'total_execution_time_start' && $point2 === 'total_execution_time_end') {
            return '0.0123';
        }
        return '0.0000';
    }
}

// Helper functions that mimic CI3
if (!function_exists('get_instance')) {
    function &get_instance()
    {
        return MockCodeIgniterSuperObject::get_instance();
    }
}

if (!function_exists('base_url')) {
    function base_url($uri = '')
    {
        return 'http://example.com/' . ltrim($uri, '/');
    }
}

if (!function_exists('log_message')) {
    function log_message($level, $message)
    {
        // Silent in tests
        return true;
    }
}

if (!function_exists('resolve_path')) {
    function resolve_path(...$paths)
    {
        return implode('/', $paths);
    }
}

if (!function_exists('ENVIRONMENT')) {
    define('ENVIRONMENT', 'testing');
}

if (!function_exists('app')) {
    function app($service = null)
    {
        static $instance = null;
        
        if ($instance === null) {
            $instance = get_instance();
        }
        
        if ($service !== null) {
            return $instance->$service ?? null;
        }
        
        return $instance;
    }
}

echo "PHPUnit bootstrap loaded successfully.\n";
