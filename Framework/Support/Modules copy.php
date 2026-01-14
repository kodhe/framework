<?php namespace Kodhe\Framework\Support;

global $CFG;

use Kodhe\Framework\Router\Router;

class Modules
{
    public static $routes = array();
    public static $registry = array();
    public static $locations = array();
    public static $assets = array();
    
    // Cache system
    protected static $cache = array();
    protected static $cache_ttl = 3600; // 1 hour default
    protected static $cache_enabled = true;
    protected static $cache_file = null;
    
    /**
     * Initialize module locations
     */
    public static function init()
    {
        if (empty(self::$locations)) {
            // Initialize cache
            self::init_cache();
            
            // Check if locations are cached
            $cache_key = 'module_locations';
            if (self::$cache_enabled && isset(self::$cache[$cache_key])) {
                self::$locations = self::$cache[$cache_key];
                return;
            }
            
            // Get module locations from config settings
            $config_locations = function_exists('config_item') ? config_item('modules_locations') : null;
            
            if (is_array($config_locations) && !empty($config_locations)) {
                self::$locations = $config_locations;
            } else {
                // Default module locations
                self::$locations = array(
                    resolve_path(APPPATH, 'modules/') => '../modules/',
                    // Add other default locations if needed
                );
                
                // Check if IGO core path exists and add it
                if (defined('BASHPATH')) {
                    $igo_path = resolve_path(BASEPATH, 'modules/');
                    if (is_dir($igo_path)) {
                        self::$locations[$igo_path] = '../../modules/';
                    }
                }
            }
            
            // Ensure all locations end with directory separator
            foreach (self::$locations as $key => $value) {
                if (substr($key, -1) !== '/') {
                    self::$locations[$key . '/'] = $value;
                    unset(self::$locations[$key]);
                }
            }
            
            // Cache the locations
            if (self::$cache_enabled) {
                self::$cache[$cache_key] = self::$locations;
                self::save_cache();
            }
        }
    }
    
    /**
     * Initialize cache system
     */
    protected static function init_cache()
    {
        if (!self::$cache_enabled) {
            return;
        }
        
        // Determine cache file path
        if (self::$cache_file === null) {
            $cache_dir = function_exists('config_item') ? config_item('cache_path') : null;
            if (empty($cache_dir) && defined('STORAGEPATH')) {
                $cache_dir = STORAGEPATH . 'cache/';
            }
            
            if ($cache_dir && is_dir($cache_dir) && is_writable($cache_dir)) {
                self::$cache_file = $cache_dir . 'modules_cache.php';
            } else {
                self::$cache_enabled = false;
                log_message('debug', 'Modules cache disabled - cache directory not writable');
                return;
            }
        }
        
        // Load cache from file if exists and not expired
        if (file_exists(self::$cache_file)) {
            $cache_data = include(self::$cache_file);
            if (is_array($cache_data) && isset($cache_data['expires']) && $cache_data['expires'] > time()) {
                self::$cache = $cache_data['data'];
                log_message('debug', 'Modules cache loaded from file');
            } else {
                // Cache expired
                log_message('debug', 'Modules cache expired');
            }
        }
        
        // Get cache TTL from config
        $config_ttl = function_exists('config_item') ? config_item('modules_cache_ttl') : null;
        if ($config_ttl !== null) {
            self::$cache_ttl = (int) $config_ttl;
        }
    }
    
    /**
     * Save cache to file
     */
    protected static function save_cache()
    {
        if (!self::$cache_enabled || empty(self::$cache_file)) {
            return;
        }
        
        $cache_data = array(
            'expires' => time() + self::$cache_ttl,
            'data' => self::$cache
        );
        
        $cache_content = "<?php\n// Modules cache file\n// Generated: " . date('Y-m-d H:i:s') . "\nreturn " . var_export($cache_data, true) . ";\n";
        
        if (@file_put_contents(self::$cache_file, $cache_content, LOCK_EX) !== false) {
            log_message('debug', 'Modules cache saved');
        } else {
            log_message('error', 'Failed to save modules cache');
        }
    }
    
    /**
     * Clear cache
     */
    public static function clear_cache()
    {
        self::$cache = array();
        if (self::$cache_file && file_exists(self::$cache_file)) {
            unlink(self::$cache_file);
            log_message('debug', 'Modules cache cleared');
        }
    }
    
    /**
     * Enable/disable cache
     */
    public static function set_cache_enabled($enabled)
    {
        self::$cache_enabled = (bool) $enabled;
        if (!$enabled) {
            self::clear_cache();
        }
    }
    
    /**
     * Get cache statistics
     */
    public static function get_cache_stats()
    {
        return array(
            'enabled' => self::$cache_enabled,
            'items' => count(self::$cache),
            'ttl' => self::$cache_ttl,
            'file' => self::$cache_file
        );
    }
    
    public static function run($module) 
    {   
        // Initialize locations if not already
        self::init();
        
        $method = 'index';
        
        if(($pos = strrpos($module, '/')) !== FALSE) 
        {
            $method = substr($module, $pos + 1);        
            $module = substr($module, 0, $pos);
        }
    
        if($class = self::load($module)) 
        {   
            if (method_exists($class, $method)) {
                ob_start();
                
                $args = func_get_args();
                $method_args = array_slice($args, 1);
                
                // Gunakan Reflection untuk handle parameter dengan lebih baik
                $reflection = new ReflectionMethod($class, $method);
                $num_required_params = $reflection->getNumberOfRequiredParameters();
                
                // Jika ada lebih banyak parameter yang diberikan daripada yang dibutuhkan
                if (count($method_args) > $reflection->getNumberOfParameters()) {
                    $method_args = array_slice($method_args, 0, $reflection->getNumberOfParameters());
                }
                
                // Jika ada parameter yang kurang dari yang dibutuhkan
                if (count($method_args) < $num_required_params) {
                    log_message('error', "Method {$method} requires {$num_required_params} parameters, but only " . count($method_args) . " provided");
                    ob_get_clean();
                    return FALSE;
                }
                
                $output = call_user_func_array(array($class, $method), $method_args);
                $buffer = ob_get_clean();
                return ($output !== NULL) ? $output : $buffer;
            }
        }
        
        log_message('error', "Module controller failed to run: {$module}/{$method}");
        return FALSE;
    }
    
    /** Load a module controller **/
    public static function load($module) 
    {
        // Initialize locations if not already
        self::init();
        
        $params = NULL;
        
        /* get the requested controller class name */
        $alias = strtolower(basename($module));

        /* create or return an existing controller from the registry */
        if (!isset(self::$registry[$alias])) 
        {
            // Check cache for controller location
            $cache_key = 'controller_' . $module;
            if (self::$cache_enabled && isset(self::$cache[$cache_key])) {
                $cached = self::$cache[$cache_key];
                $class = $cached['class'];
                $path = $cached['path'];
                $file_path = $cached['file_path'];
                
                log_message('debug', "Using cached controller location for: {$module}");
            } else {
                /* find the controller */
                $router = new Router();
                $located = $router->locate(explode('/', $module));
                
                // Handle the located segments properly
                $class = '';
                if (is_array($located) && !empty($located)) {
                    $class = $located[0];
                }
        
                /* controller cannot be located */
                if (empty($class)) {
                    log_message('debug', "Module controller not found: {$module}");
                    return FALSE;
                }
        
                /* set the module directory */
                $directory = $router->fetch_directory();
                $path = resolve_path(APPPATH, 'controllers/') . $directory;
                $file_path = $path . ucfirst($class) . '.php';
                
                // Cache the controller location
                if (self::$cache_enabled && file_exists($file_path)) {
                    self::$cache[$cache_key] = array(
                        'class' => $class,
                        'path' => $path,
                        'file_path' => $file_path
                    );
                    self::save_cache();
                }
            }
    
            /* load the controller class */
            $class_suffix = function_exists('config_item') ? config_item('controller_suffix') : '';
            if ($class_suffix && strpos($class, $class_suffix) === FALSE) {
                $class .= $class_suffix;
            }
            
            if (!file_exists($file_path)) {
                log_message('error', "Module controller file not found: {$file_path}");
                return FALSE;
            }
            
            self::load_file(ucfirst($class), $path);
            
            /* create and register the new controller */
            $controller = ucfirst($class);    
            self::$registry[$alias] = new $controller($params);
        }
        
        return self::$registry[$alias];
    }
    
    /** Load a module file **/
    public static function load_file($file, $path, $type = 'other', $result = TRUE)    
    {
        $file = str_replace('.php', '', $file);       
        $location = $path . $file . '.php';
        
        if (!file_exists($location)) {
            log_message('debug', "File not found: {$location}");
            return $result;
        }
        
        if ($type === 'other') 
        {           
            if (class_exists($file, FALSE))    
            {
                log_message('debug', "File already loaded: {$location}");             
                return $result;
            }   
            include_once $location;
        } 
        else 
        {
            /* load config or language array */
            include $location;

            if (!isset($$type) || !is_array($$type))                
                show_error("{$location} does not contain a valid {$type} array");

            $result = $$type;
        }
        log_message('debug', "File loaded: {$location}");
        return $result;
    }

    /** 
    * Find a file
    * Scans for files located within modules directories.
    * Also scans application directories for models, plugins and views.
    * Generates fatal error if file not found.
    **/
    public static function find($file, $module, $base) 
    {
        // Initialize locations if not already
        self::init();
        
        // Check cache for file location
        $cache_key = 'find_' . md5($file . '_' . $module . '_' . $base);
        if (self::$cache_enabled && isset(self::$cache[$cache_key])) {
            log_message('debug', "Using cached file location for: {$file} in module {$module}");
            return self::$cache[$cache_key];
        }
        
        $segments = explode('/', $file);

        $file = array_pop($segments);
        $file_ext = (pathinfo($file, PATHINFO_EXTENSION)) ? $file : $file . '.php';
        
        $path = ltrim(implode('/', $segments).'/', '/');    
        
        if ($module) {
            $modules = array($module => $path);
        } else {
            $modules = array();
        }
        
        if (!empty($segments)) 
        {
            $modules[array_shift($segments)] = ltrim(implode('/', $segments).'/','/');
        }   

        $result = array(FALSE, $file);
        
        foreach (self::$locations as $location => $offset) 
        {                   
            foreach($modules as $module_name => $subpath) 
            {           
                $fullpath = $location . $module_name . '/' . $base . $subpath;
                
                // Debug logging
                log_message('debug', "Looking for file in: {$fullpath}{$file_ext}");
                
                if ($base == 'libraries/' || $base == 'models/')
                {
                    if (is_file($fullpath . ucfirst($file_ext))) {
                        log_message('debug', "Found class file: {$fullpath}" . ucfirst($file_ext));
                        $result = array($fullpath, ucfirst($file));
                        
                        // Cache the result
                        if (self::$cache_enabled) {
                            self::$cache[$cache_key] = $result;
                            self::save_cache();
                        }
                        
                        return $result;
                    }
                }
                else
                /* load non-class files */
                if (is_file($fullpath . $file_ext)) {
                    log_message('debug', "Found file: {$fullpath}{$file_ext}");
                    $result = array($fullpath, $file);
                    
                    // Cache the result
                    if (self::$cache_enabled) {
                        self::$cache[$cache_key] = $result;
                        self::save_cache();
                    }
                    
                    return $result;
                }
            }
        }
        
        log_message('debug', "File not found: {$file} in module {$module}");
        
        // Cache negative result (with shorter TTL maybe)
        if (self::$cache_enabled) {
            self::$cache[$cache_key] = $result;
            self::save_cache();
        }
        
        return $result;    
    }
    
    /** Parse module routes **/
    public static function parse_routes($module, $uri) 
    {
        // Initialize locations if not already
        self::init();

        // Check cache for routes
        $cache_key = 'routes_' . $module;
        if (self::$cache_enabled && isset(self::$cache[$cache_key])) {
            $routes = self::$cache[$cache_key];
            log_message('debug', "Using cached routes for module: {$module}");
        } else {
            /* load the route file */
            if (!isset(self::$routes[$module])) 
            {
                list($path) = self::find('routes', $module, 'config/');
                if ($path) {
                    self::$routes[$module] = self::load_file('routes', $path, 'route');
                }
            }

            $routes = isset(self::$routes[$module]) ? self::$routes[$module] : array();
            
            // Cache the routes
            if (self::$cache_enabled && !empty($routes)) {
                self::$cache[$cache_key] = $routes;
                self::save_cache();
            }
        }

        if (empty($routes)) {
            return;
        }
            
        /* parse module routes */
        foreach ($routes as $key => $val) 
        {                       
            $key = str_replace(array(':any', ':num'), array('.+', '[0-9]+'), $key);
            
            // Validasi pattern regex sebelum digunakan
            $pattern = '#^'.$key.'$#';
            if (@preg_match($pattern, null) === false) {
                log_message('error', "Invalid regex pattern in route for module {$module}: {$pattern}");
                continue; // Skip route yang invalid
            }
            
            if (preg_match($pattern, $uri)) 
            {                           
                if (strpos($val, '$') !== FALSE && strpos($key, '(') !== FALSE) 
                {
                    $val = preg_replace($pattern, $val, $uri);
                }
                return explode('/', $module.'/'.$val);
            }
        }
    }
    
    /* begin custom */
    
    /**
     * Determine whether a controller exists for a module.
     *
     * @param $controller string The controller to look for (without the extension).
     * @param $module     string The module to look in.
     *
     * @return boolean True if the controller is found, else false.
     */
    public static function controller_exists($controller = null, $module = null)
    {
        if (empty($controller) || empty($module)) {
            return false;
        }

        // Initialize locations if not already
        self::init();
        
        // Check cache
        $cache_key = 'controller_exists_' . $module . '_' . $controller;
        if (self::$cache_enabled && isset(self::$cache[$cache_key])) {
            log_message('debug', "Using cached controller_exists result for: {$module}/{$controller}");
            return self::$cache[$cache_key];
        }

        $result = false;
        
        // Look in all module paths.
        foreach (self::folders() as $folder) {
            $controller_path = "{$folder}{$module}/controllers/{$controller}.php";
            $controller_path_uc = "{$folder}{$module}/controllers/" . ucfirst($controller) . '.php';
            
            log_message('debug', "Checking controller: {$controller_path}");
            
            if (is_file($controller_path)) {
                log_message('debug', "Controller found: {$controller_path}");
                $result = true;
                break;
            } elseif (is_file($controller_path_uc)) {
                log_message('debug', "Controller found: {$controller_path_uc}");
                $result = true;
                break;
            }
        }

        // Cache the result
        if (self::$cache_enabled) {
            self::$cache[$cache_key] = $result;
            self::save_cache();
        }

        if (!$result) {
            log_message('debug', "Controller not found: {$controller} in module {$module}");
        }
        
        return $result;
    }

    /**
     * Find the path to a module's file.
     *
     * @param $module string The name of the module to find.
     * @param $folder string The folder within the module to search for the file
     * (ie. controllers).
     * @param $file   string The name of the file to search for.
     *
     * @return string The full path to the file.
     */
    public static function file_path($module = null, $folder = null, $file = null)
    {
        if (empty($module) || empty($folder) || empty($file)) {
            return false;
        }

        // Initialize locations if not already
        self::init();
        
        // Check cache
        $cache_key = 'file_path_' . $module . '_' . $folder . '_' . $file;
        if (self::$cache_enabled && isset(self::$cache[$cache_key])) {
            log_message('debug', "Using cached file_path for: {$module}/{$folder}/{$file}");
            return self::$cache[$cache_key];
        }

        $folders = self::folders();
        $result = false;
        
        foreach ($folders as $module_folder) {
            $file_path = "{$module_folder}{$module}/{$folder}/{$file}";
            $file_path_uc = "{$module_folder}{$module}/{$folder}/" . ucfirst($file);
            
            log_message('debug', "Looking for file: {$file_path}");
            
            if (is_file($file_path)) {
                log_message('debug', "File found: {$file_path}");
                $result = $file_path;
                break;
            } elseif (is_file($file_path_uc)) {
                log_message('debug', "File found: {$file_path_uc}");
                $result = $file_path_uc;
                break;
            }
        }
        
        // Cache the result
        if (self::$cache_enabled) {
            self::$cache[$cache_key] = $result;
            self::save_cache();
        }
        
        if (!$result) {
            log_message('debug', "File not found: {$file} in {$module}/{$folder}");
        }
        
        return $result;
    }

    /**
     * Return the path to the module and its specified folder.
     *
     * @param $module string The name of the module (must match the folder name).
     * @param $folder string The folder name to search for (Optional).
     *
     * @return string The path, relative to the front controller.
     */
    public static function path($module = null, $folder = null)
    {
        if (empty($module)) {
            return false;
        }

        // Initialize locations if not already
        self::init();
        
        // Check cache
        $cache_key = 'path_' . $module . ($folder ? '_' . $folder : '');
        if (self::$cache_enabled && isset(self::$cache[$cache_key])) {
            log_message('debug', "Using cached path for: {$module}" . ($folder ? "/{$folder}" : ''));
            return self::$cache[$cache_key];
        }

        $result = false;
        
        foreach (self::folders() as $module_folder) {
            // Debug logging
            log_message('debug', "Checking module folder: {$module_folder}{$module}");
            
            // Check each folder for the module's folder.
            if (is_dir("{$module_folder}{$module}")) {
                // If $folder was specified and exists, return it.
                if (!empty($folder)
                    && is_dir("{$module_folder}{$module}/{$folder}")
                ) {
                    $path = "{$module_folder}{$module}/{$folder}";
                    log_message('debug', "Module path found with folder: {$path}");
                    $result = $path;
                    break;
                }

                // Return the module's folder.
                $path = "{$module_folder}{$module}/";
                log_message('debug', "Module path found: {$path}");
                $result = $path;
                break;
            }
        }
        
        // Cache the result
        if (self::$cache_enabled) {
            self::$cache[$cache_key] = $result;
            self::save_cache();
        }
        
        if (!$result) {
            log_message('debug', "Module not found: {$module}");
        }
        
        return $result;
    }

    /**
     * Return an associative array of files within one or more modules.
     *
     * @param $module_name   string  If not null, will return only files from that
     * module.
     * @param $module_folder string  If not null, will return only files within
     * that sub-folder of each module (ie 'views').
     * @param $exclude_core  boolean If true, excludes all core modules.
     *
     * @return array An associative array, like:
     * <code>
     * array(
     *     'module_name' => array(
     *         'folder' => array('file1', 'file2')
     *     )
     * )
     */
    public static function files($module_name = null, $module_folder = null, $exclude_core = false)
    {
        // Initialize locations if not already
        self::init();
        
        // Check cache
        $cache_key = 'files_' . md5(serialize(func_get_args()));
        if (self::$cache_enabled && isset(self::$cache[$cache_key])) {
            log_message('debug', "Using cached files list");
            return self::$cache[$cache_key];
        }

        // Ensure the create_directory_map() function is available.
        if (!function_exists('create_directory_map')) {
            // Try to load CI instance
            if (function_exists('get_instance')) {
                $CI =& get_instance();
                if (isset($CI->load)) {
                    $CI->load->helper('directory');
                }
            }
        }

        $files = array();
        foreach (self::folders() as $path) {
            // If excluding core modules, skip the core module folder.
            if ($exclude_core
                && stripos($path, 'igocore/modules') !== false
            ) {
                continue;
            }

            // Only map the whole modules directory if $module_name isn't passed.
            if (empty($module_name)) {
                $modules = create_directory_map($path);
            } elseif (is_dir($path . $module_name)) {
                // Only map the $module_name directory if it exists.
                $path = $path . $module_name;
                $modules[$module_name] = create_directory_map($path);
            } else {
                continue;
            }

            // If the element is not an array, it's a file, so ignore it. Otherwise,
            // it is assumed to be a module.
            if (empty($modules) || !is_array($modules)) {
                continue;
            }

            foreach ($modules as $modDir => $values) {
                if (is_array($values)) {
                    if (empty($module_folder)) {
                        // Add the entire module.
                        $files[$modDir] = $values;
                    } elseif (!empty($values[$module_folder])) {
                        // Add just the specified folder for this module.
                        $files[$modDir] = array(
                            $module_folder => $values[$module_folder],
                        );
                    }
                }
            }
        }

        $result = empty($files) ? false : $files;
        
        // Cache the result
        if (self::$cache_enabled) {
            self::$cache[$cache_key] = $result;
            self::save_cache();
        }
        
        return $result;
    }

    /**
     * Returns the 'module_config' array from a modules config/config.php file.
     *
     * The 'module_config' contains more information about a module, and even
     * provides enhanced features within the UI. All fields are optional.
     *
     * @author Liam Rutherford (http://www.liamr.com)
     *
     * <code>
     * $config['module_config'] = array(
     *  'name'          => 'Blog',          // The name that is displayed in the UI
     *  'description'   => 'Simple Blog',   // May appear at various places within the UI
     *  'author'        => 'Your Name',     // The name of the module's author
     *  'homepage'      => 'http://...',    // The module's home on the web
     *  'version'       => '1.0.1',         // Currently installed version
     *  'menu'          => array(           // A view file containing an <ul> that will be the sub-menu in the main nav.
     *      'context'   => 'path/to/view'
     *  )
     * );
     * </code>
     *
     * @param $module_name string  The name of the module.
     * @param $return_full boolean Ignored if the 'module_config' portion exists.
     * Otherwise, if true, will return the entire config array, else an empty array
     * is returned.
     *
     * @return array An array of config settings, or an empty array.
     */
    public static function config($module_name = null, $return_full = false)
    {
        if (empty($module_name)) {
            return array();
        }

        // Initialize locations if not already
        self::init();
        
        // Check cache
        $cache_key = 'config_' . $module_name . ($return_full ? '_full' : '');
        if (self::$cache_enabled && isset(self::$cache[$cache_key])) {
            log_message('debug', "Using cached config for module: {$module_name}");
            return self::$cache[$cache_key];
        }

        // Get the path of the file and determine whether it exists.
        $config_file = self::file_path($module_name, 'config', 'config.php');
        if (!$config_file || !file_exists($config_file)) {
            log_message('debug', "Config file not found for module: {$module_name}");
            return array();
        }

        // Include the file and determine whether it contains a config array.
        $config = array();
        include($config_file);
        
        if (!isset($config) || !is_array($config)) {
            return array();
        }

        $result = array();
        
        // Check for the optional module_config and serialize if exists.
        if (isset($config['module_config'])) {
            $result = $config['module_config'];
        } elseif ($return_full === true && is_array($config)) {
            // If 'module_config' did not exist, $return_full is true, and $config
            // is an array, return it.
            $result = $config;
        }

        // Cache the result
        if (self::$cache_enabled && !empty($result)) {
            self::$cache[$cache_key] = $result;
            self::save_cache();
        }
        
        return $result;
    }

    /**
     * Returns an array of the folders in which modules may be stored.
     *
     * @return array The folders in which modules may be stored.
     */
    public static function folders()
    {
        // Initialize locations if not already
        self::init();
        
        // This is already cached in init() via module_locations cache
        $folders = array_keys(self::$locations);
        log_message('debug', "Module folders: " . implode(', ', $folders));
        return $folders;
    }

    /**
     * Returns a list of all modules in the system.
     *
     * @param bool $exclude_core Whether to exclude the igocore core modules.
     *
     * @return array A list of all modules in the system.
     */
    public static function list_modules($exclude_core = false)
    {
        // Initialize locations if not already
        self::init();
        
        // Check cache
        $cache_key = 'list_modules_' . ($exclude_core ? 'nocore' : 'all');
        if (self::$cache_enabled && isset(self::$cache[$cache_key])) {
            log_message('debug', "Using cached modules list");
            return self::$cache[$cache_key];
        }

        // Ensure the create_directory_map function is available.
        if (!function_exists('create_directory_map')) {
            // Try to load CI instance
            if (function_exists('get_instance')) {
                $CI =& get_instance();
                if (isset($CI->load)) {
                    $CI->load->helper('directory');
                }
            }
        }

        $map = array();
        foreach (self::folders() as $folder) {
            // If excluding core modules, skip the core module folder.
            if ($exclude_core && stripos($folder, 'igocore/modules') !== false) {
                continue;
            }

            if (!is_dir($folder)) {
                log_message('debug', "Module folder not found: {$folder}");
                continue;
            }

            $dirs = create_directory_map($folder, 1);
            if (is_array($dirs)) {
                $map = array_merge($map, $dirs);
            }
        }

        $count = count($map);
        if (!$count) {
            $result = $map;
        } else {
            // Clean out any html or php files.
            for ($i = 0; $i < $count; $i++) {
                if (isset($map[$i]) && (stripos($map[$i], '.html') !== false
                    || stripos($map[$i], '.php') !== false)
                ) {
                    unset($map[$i]);
                }
            }

            $result = array_values($map);
        }
        
        // Cache the result
        if (self::$cache_enabled) {
            self::$cache[$cache_key] = $result;
            self::save_cache();
        }
        
        return $result;
    }
    
    /**
     * Returns a cached list of all modules (optimized version)
     */
    public static function list_modules_cached($exclude_core = false)
    {
        // This is just an alias for backward compatibility
        // The caching is now built into list_modules()
        return self::list_modules($exclude_core);
    }
    
    #Register your css js assets
    public static function register_asset($asset)
    {
        // Initialize locations if not already
        self::init();
        
        if (!is_array(self::$assets)) {
            self::$assets = array();
        }
        
        if (in_array($asset, self::$assets) === FALSE) {
            self::$assets[] = $asset;
        }
    }

    public static function assets()
    {
        // Initialize locations if not already
        self::init();
        
        if (!is_array(self::$assets)) {
            self::$assets = array();
        }
        return self::$assets;
    }
       
    /* end custom */
}


class_alias('Kodhe\Framework\Application\Modules', 'Modules');