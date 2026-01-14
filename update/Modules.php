<?php namespace Kodhe\Framework\Support;

global $CFG;

use Kodhe\Framework\Router\Router;
use Kodhe\Framework\Exceptions\Http\BadRequestException;

class Modules
{
    public static $routes = array();
    public static $registry = array();
    public static $locations = array();
    public static $assets = array();
    
    /**
     * @var array Cache of all modules with their paths
     */
    protected static $modulesCache = array();
    
    /**
     * @var string Cache file path
     */
    protected static $cacheFile;
    
    /**
     * @var string Current module name
     */
    protected static $currentModule = '';
    
    /**
     * Initialize module locations and cache
     */
    public static function init()
    {
        if (empty(self::$locations)) {
            // Initialize cache file path
            self::initCache();
            
            // Try to load from cache first
            if (self::loadFromCache()) {
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
                    $igo_path = resolve_path(BASHPATH, 'modules/');
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
            
            // Scan and cache all modules with their paths
            self::scanAndCacheAllModules();
        }
    }
    
    /**
     * Scan all module locations and cache all modules with their paths
     */
    protected static function scanAndCacheAllModules(): void
    {
        self::$modulesCache = array();
        
        foreach (self::$locations as $location => $offset) {
            if (!is_dir($location)) {
                continue;
            }
            
            // Scan directory for modules
            $items = @scandir($location);
            if (!$items) {
                continue;
            }
            
            foreach ($items as $item) {
                // Skip . and .. and non-directories
                if ($item === '.' || $item === '..' || !is_dir($location . $item)) {
                    continue;
                }
                
                $module_name = $item;
                $module_path = $location . $item . '/';
                
                // Store module with its full path
                if (!isset(self::$modulesCache[$module_name])) {
                    self::$modulesCache[$module_name] = array();
                }
                
                // Add this path to the module's paths array
                self::$modulesCache[$module_name][] = $module_path;
            }
        }
        
        // Sort modules alphabetically
        ksort(self::$modulesCache);
        
        // Cache the data
        self::cache();
    }
    
    /**
     * Get all cached modules with their paths
     */
    public static function getAllCachedModules(): array
    {
        self::init();
        return self::$modulesCache;
    }
    
    /**
     * Get module paths from cache
     */
    public static function getModulePaths(string $module_name): array
    {
        self::init();
        return self::$modulesCache[$module_name] ?? array();
    }
    
    /**
     * Check if module exists using cache
     */
    public static function moduleExists(string $module_name): bool
    {
        self::init();
        return isset(self::$modulesCache[$module_name]);
    }
    
    /**
     * Get list of all module names from cache
     */
    public static function getCachedModuleNames(): array
    {
        self::init();
        return array_keys(self::$modulesCache);
    }
    
    /**
     * Find the first valid path for a module
     */
    public static function getFirstModulePath(string $module_name): ?string
    {
        self::init();
        $paths = self::$modulesCache[$module_name] ?? array();
        return !empty($paths) ? $paths[0] : null;
    }
    
    /**
     * Initialize cache file path
     */
    protected static function initCache(): void
    {
        $path = function_exists('app') && isset(app()->config) 
            ? app()->config->item('cache_path') 
            : '';
            
        $cache_path = ($path === '') ? STORAGEPATH.'cache/' : $path;
        self::$cacheFile = $cache_path . 'modules.cache.php';
    }
    
    /**
     * Cache modules data
     */
    public static function cache(): bool
    {
        // Prepare cache data
        $cacheData = self::prepareCacheData();
        
        if (empty($cacheData['locations'])) {
            return false;
        }

        // Create cache directory if not exists
        $cacheDir = dirname(self::$cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        // Encode to JSON
        $jsonData = json_encode($cacheData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        if ($jsonData === false) {
            throw new BadRequestException('Failed to encode modules to JSON: ' . json_last_error_msg());
        }

        // Write to file
        $content = "<?php\n// Modules Cache File - DO NOT EDIT MANUALLY\n// Generated: " . date('Y-m-d H:i:s') . "\nreturn <<<'CACHE'\n{$jsonData}\nCACHE;\n";
        
        $result = file_put_contents(self::$cacheFile, $content, LOCK_EX);
        
        if ($result !== false) {
            return true;
        }
        
        throw new BadRequestException("Failed to write cache file: " . self::$cacheFile);
    }

    /**
     * Prepare cache data for JSON encoding
     */
    protected static function prepareCacheData(): array
    {
        return [
            'locations' => self::$locations,
            'modules_list' => self::$modulesCache,
            'timestamp' => time(),
            'location_count' => count(self::$locations),
            'modules_count' => count(self::$modulesCache),
            'current_module' => self::$currentModule
        ];
    }

    /**
     * Load modules data from cache
     */
    public static function loadFromCache(): bool
    {
        // Debug mode: disable cache untuk development
        if (defined('ENVIRONMENT') && ENVIRONMENT !== 'production') {
            return false;
        }
        
        if (!file_exists(self::$cacheFile)) {
            return false;
        }

        try {
            // Read JSON from heredoc
            $content = file_get_contents(self::$cacheFile);
            
            // Extract JSON from heredoc
            if (preg_match("/return <<<'CACHE'\n(.*?)\nCACHE;/s", $content, $matches)) {
                $jsonData = $matches[1];
            } else {
                // Try direct JSON
                $jsonData = trim(str_replace(['<?php', '//'], '', $content));
            }
            
            $cacheData = json_decode($jsonData, true);
            
            if (!$cacheData || !isset($cacheData['locations'])) {
                self::clearCache();
                return false;
            }

            // Restore all data from cache
            self::$locations = $cacheData['locations'];
            self::$modulesCache = $cacheData['modules_list'] ?? array();
            self::$currentModule = $cacheData['current_module'] ?? '';
            
            // Validate cache freshness
            if (self::isCacheFresh(86400)) { // 24 hours
                return true;
            }
            
            return true;
            
        } catch (\Exception $e) {
            self::clearCache();
            return false;
        }
    }

    /**
     * Clear module cache
     */
    public static function clearCache(): bool
    {
        if (file_exists(self::$cacheFile)) {
            return unlink(self::$cacheFile);
        }

        return true;
    }

    /**
     * Is cache fresh?
     */
    public static function isCacheFresh(int $maxAge = 3600): bool
    {
        if (!file_exists(self::$cacheFile)) {
            return false;
        }

        $cacheTime = filemtime(self::$cacheFile);
        return (time() - $cacheTime) < $maxAge;
    }
    
    /**
     * Refresh cache - force rescan and recache
     */
    public static function refreshCache(): bool
    {
        // Reset data to force rescan
        self::$locations = array();
        self::$modulesCache = array();
        self::$currentModule = '';
        
        // Clear existing cache
        self::clearCache();
        
        // Reinitialize with fresh scan
        self::init();
        
        return true;
    }
    
    /**
     * Optimized find file using cache
     */
    public static function findCachedFile(string $file, string $module, string $base): array
    {
        self::init();
        
        $segments = explode('/', $file);
        $file_name = array_pop($segments);
        $file_ext = (pathinfo($file_name, PATHINFO_EXTENSION)) ? $file_name : $file_name . '.php';
        $subpath = ltrim(implode('/', $segments).'/', '/');
        
        // Check in cached module paths
        $module_paths = self::$modulesCache[$module] ?? array();
        
        foreach ($module_paths as $module_path) {
            $full_path = $module_path . $base . $subpath . $file_ext;
            $full_path_uc = $module_path . $base . $subpath . ucfirst($file_ext);
            
            log_message('debug', "Looking for cached file: {$full_path}");
            
            if (($base == 'libraries/' || $base == 'models/') && is_file($full_path_uc)) {
                log_message('debug', "Found cached class file: {$full_path_uc}");
                return array($full_path_uc, ucfirst($file_name));
            } elseif (is_file($full_path)) {
                log_message('debug', "Found cached file: {$full_path}");
                return array($full_path, $file_name);
            }
        }
        
        log_message('debug', "Cached file not found: {$file} in module {$module}");
        return array(FALSE, $file);
    }
    
    /**
     * Check if controller exists using cache
     */
    public static function controllerExistsCached(string $controller, string $module): bool
    {
        self::init();
        
        $module_paths = self::$modulesCache[$module] ?? array();
        
        foreach ($module_paths as $module_path) {
            $controller_path = $module_path . 'controllers/' . $controller . '.php';
            $controller_path_uc = $module_path . 'controllers/' . ucfirst($controller) . '.php';
            
            if (is_file($controller_path) || is_file($controller_path_uc)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get module config file path from cache
     */
    public static function getConfigFilePath(string $module_name): ?string
    {
        self::init();
        
        $module_paths = self::$modulesCache[$module_name] ?? array();
        
        foreach ($module_paths as $module_path) {
            $config_file = $module_path . 'config/config.php';
            if (is_file($config_file)) {
                return $config_file;
            }
        }
        
        return null;
    }
    
    /**
     * Set current module
     */
    public static function setCurrentModule(string $module): void
    {
        self::$currentModule = $module;
        
        // Update cache jika ada
        if (!empty(self::$currentModule)) {
            self::cache();
        }
    }
    
    /**
     * Get current module
     */
    public static function getCurrentModule(): string
    {
        return self::$currentModule;
    }
    
    /**
     * Get module info termasuk path, config, dll
     */
    public static function getModuleInfo(string $module = ''): array
    {
        if (empty($module)) {
            $module = self::getCurrentModule();
        }
        
        if (empty($module) || !self::moduleExists($module)) {
            return [];
        }
        
        $paths = self::getModulePaths($module);
        $firstPath = $paths[0] ?? '';
        
        return [
            'name' => $module,
            'paths' => $paths,
            'main_path' => $firstPath,
            'controllers_path' => $firstPath ? $firstPath . 'controllers/' : '',
            'views_path' => $firstPath ? $firstPath . 'views/' : '',
            'models_path' => $firstPath ? $firstPath . 'models/' : '',
            'libraries_path' => $firstPath ? $firstPath . 'libraries/' : '',
            'config' => self::config($module, true),
            'exists' => true,
            'is_current' => $module === self::getCurrentModule()
        ];
    }
    
    /**
     * Check if in specific module
     */
    public static function inModule(string $module): bool
    {
        $current = self::getCurrentModule();
        return !empty($current) && $current === $module;
    }
    
    public static function run($module) 
    {   
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
                
                $reflection = new ReflectionMethod($class, $method);
                $num_required_params = $reflection->getNumberOfRequiredParameters();
                
                if (count($method_args) > $reflection->getNumberOfParameters()) {
                    $method_args = array_slice($method_args, 0, $reflection->getNumberOfParameters());
                }
                
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
        self::init();
        
        $params = NULL;
        $alias = strtolower(basename($module));

        if (!isset(self::$registry[$alias])) 
        {
            $router = new Router();
            $located = $router->locate(explode('/', $module));
            
            $class = '';
            if (is_array($located) && !empty($located)) {
                $class = $located[0];
            }
    
            if (empty($class)) {
                log_message('debug', "Module controller not found: {$module}");
                return FALSE;
            }
    
            $directory = $router->fetch_directory();
            $path = resolve_path(APPPATH, 'controllers/') . $directory;
            
            $class_suffix = function_exists('config_item') ? config_item('controller_suffix') : '';
            if ($class_suffix && strpos($class, $class_suffix) === FALSE) {
                $class .= $class_suffix;
            }
            
            $file_path = $path . ucfirst($class) . '.php';
            
            if (!file_exists($file_path)) {
                log_message('error', "Module controller file not found: {$file_path}");
                return FALSE;
            }
            
            self::load_file(ucfirst($class), $path);
            
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
            include $location;

            if (!isset($$type) || !is_array($$type))                
                show_error("{$location} does not contain a valid {$type} array");

            $result = $$type;
        }
        log_message('debug', "File loaded: {$location}");
        return $result;
    }

    /** 
    * Find a file - Optimized with cache
    **/
    public static function find($file, $module, $base) 
    {
        self::init();
        
        // Try cached version first
        $cached_result = self::findCachedFile($file, $module, $base);
        if ($cached_result[0] !== FALSE) {
            return $cached_result;
        }
        
        // Fallback to original method
        $segments = explode('/', $file);
        $file_name = array_pop($segments);
        $file_ext = (pathinfo($file_name, PATHINFO_EXTENSION)) ? $file_name : $file_name . '.php';
        $subpath = ltrim(implode('/', $segments).'/', '/');
        
        // Check all module paths
        $module_paths = self::$modulesCache[$module] ?? array();
        
        foreach ($module_paths as $module_path) {
            $fullpath = $module_path . $base . $subpath . $file_ext;
            $fullpath_uc = $module_path . $base . $subpath . ucfirst($file_ext);
            
            log_message('debug', "Looking for file in: {$fullpath}");
            
            if (($base == 'libraries/' || $base == 'models/') && is_file($fullpath_uc)) {
                log_message('debug', "Found class file: {$fullpath_uc}");
                return array($fullpath_uc, ucfirst($file_name));
            } elseif (is_file($fullpath)) {
                log_message('debug', "Found file: {$fullpath}");
                return array($fullpath, $file_name);
            }
        }
        
        log_message('debug', "File not found: {$file} in module {$module}");
        return array(FALSE, $file);    
    }
    
    /** Parse module routes **/
    public static function parse_routes($module, $uri) 
    {
        self::init();

        if (empty(self::$routes[$module])) 
        {
            list($path) = self::find('routes', $module, 'config/');
            if ($path) {
                self::$routes[$module] = self::load_file('routes', $path, 'route');
            }
        }

        if (empty(self::$routes[$module])) {
            return;
        }
            
        if(is_array(self::$routes[$module])) {
            foreach (self::$routes[$module] as $key => $val) 
            {                       
                $key = str_replace(array(':any', ':num'), array('.+', '[0-9]+'), $key);
                
                $pattern = '#^'.$key.'$#';
                if (@preg_match($pattern, null) === false) {
                    log_message('error', "Invalid regex pattern in route for module {$module}: {$pattern}");
                    continue;
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

    }
    
    /**
     * Determine whether a controller exists for a module.
     */
    public static function controller_exists($controller = null, $module = null)
    {
        if (empty($controller) || empty($module)) {
            return false;
        }

        self::init();

        // Try cached version first
        if (self::controllerExistsCached($controller, $module)) {
            return true;
        }

        // Fallback to checking all module paths
        $module_paths = self::$modulesCache[$module] ?? array();
        
        foreach ($module_paths as $module_path) {
            $controller_path = "{$module_path}controllers/{$controller}.php";
            $controller_path_uc = "{$module_path}controllers/" . ucfirst($controller) . '.php';
            
            log_message('debug', "Checking controller: {$controller_path}");
            
            if (is_file($controller_path) || is_file($controller_path_uc)) {
                log_message('debug', "Controller found");
                return true;
            }
        }

        log_message('debug', "Controller not found: {$controller} in module {$module}");
        return false;
    }

    /**
     * Find the path to a module's file.
     */
    public static function file_path($module = null, $folder = null, $file = null)
    {
        if (empty($module) || empty($folder) || empty($file)) {
            return false;
        }

        self::init();

        // Try cached version first
        $cached_result = self::findCachedFile($file, $module, $folder . '/');
        if ($cached_result[0] !== FALSE) {
            return $cached_result[0];
        }

        // Fallback to checking all module paths
        $module_paths = self::$modulesCache[$module] ?? array();
        
        foreach ($module_paths as $module_path) {
            $file_path = "{$module_path}{$folder}/{$file}";
            $file_path_uc = "{$module_path}{$folder}/" . ucfirst($file);
            
            log_message('debug', "Looking for file: {$file_path}");
            
            if (is_file($file_path) || is_file($file_path_uc)) {
                $found_path = is_file($file_path) ? $file_path : $file_path_uc;
                log_message('debug', "File found: {$found_path}");
                return $found_path;
            }
        }
        
        log_message('debug', "File not found: {$file} in {$module}/{$folder}");
        return false;
    }

    /**
     * Return the path to the module and its specified folder.
     */
    public static function path($module = null, $folder = null)
    {
        if (empty($module)) {
            return false;
        }

        self::init();

        // Get first module path from cache
        $first_path = self::getFirstModulePath($module);
        if ($first_path) {
            if (!empty($folder)) {
                $folder_path = $first_path . $folder . '/';
                if (is_dir($folder_path)) {
                    log_message('debug', "Module path found with folder: {$folder_path}");
                    return $folder_path;
                }
            }
            
            log_message('debug', "Module path found: {$first_path}");
            return $first_path;
        }
        
        log_message('debug', "Module not found: {$module}");
        return false;
    }

    /**
     * Return an associative array of files within one or more modules.
     */
    public static function files($module_name = null, $module_folder = null, $exclude_core = false)
    {
        self::init();

        $files = array();
        
        foreach (self::$modulesCache as $mod_name => $paths) {
            // Filter by module name if specified
            if ($module_name && $mod_name !== $module_name) {
                continue;
            }
            
            // Use first path
            $module_path = $paths[0] ?? null;
            if (!$module_path) {
                continue;
            }
            
            if ($module_folder) {
                $folder_path = $module_path . $module_folder . '/';
                if (is_dir($folder_path)) {
                    $dir_files = @scandir($folder_path);
                    if ($dir_files) {
                        $file_list = array();
                        foreach ($dir_files as $file) {
                            if ($file !== '.' && $file !== '..') {
                                $file_list[] = $file;
                            }
                        }
                        $files[$mod_name] = array($module_folder => $file_list);
                    }
                }
            } else {
                // Scan all directories in module
                $dirs = @scandir($module_path);
                if ($dirs) {
                    $module_dirs = array();
                    foreach ($dirs as $dir) {
                        if ($dir !== '.' && $dir !== '..' && is_dir($module_path . $dir)) {
                            $dir_files = @scandir($module_path . $dir);
                            if ($dir_files) {
                                $file_list = array();
                                foreach ($dir_files as $file) {
                                    if ($file !== '.' && $file !== '..') {
                                        $file_list[] = $file;
                                    }
                                }
                                $module_dirs[$dir] = $file_list;
                            }
                        }
                    }
                    $files[$mod_name] = $module_dirs;
                }
            }
        }
        
        return empty($files) ? false : $files;
    }

    /**
     * Returns the 'module_config' array from a modules config/config.php file.
     */
    public static function config($module_name = null, $return_full = false)
    {
        if (empty($module_name)) {
            return array();
        }

        self::init();

        // Get config file path from cache
        $config_file = self::getConfigFilePath($module_name);
        if (!$config_file) {
            log_message('debug', "Config file not found for module: {$module_name}");
            return array();
        }

        // Include the file
        $config = array();
        include($config_file);
        
        if (!isset($config) || !is_array($config)) {
            return array();
        }

        if (isset($config['module_config'])) {
            return $config['module_config'];
        } elseif ($return_full === true) {
            return $config;
        }

        return array();
    }

    /**
     * Returns an array of the folders in which modules may be stored.
     */
    public static function folders()
    {
        self::init();
        
        $folders = array_keys(self::$locations);
        log_message('debug', "Module folders: " . implode(', ', $folders));
        return $folders;
    }

    /**
     * Returns a list of all modules in the system.
     */
    public static function list_modules($exclude_core = false)
    {
        self::init();

        $modules = array_keys(self::$modulesCache);
        
        if ($exclude_core) {
            // Filter out core modules
            $core_modules = array('core', 'system', 'admin', 'settings', 'auth');
            $modules = array_diff($modules, $core_modules);
        }
        
        sort($modules);
        
        return $modules;
    }
    
    /**
     * Register your css js assets
     */
    public static function register_asset($asset)
    {
        self::init();
        
        if (!is_array(self::$assets)) {
            self::$assets = array();
        }
        
        if (!in_array($asset, self::$assets)) {
            self::$assets[] = $asset;
        }
    }

    /**
     * Get registered assets
     */
    public static function assets()
    {
        self::init();
        
        if (!is_array(self::$assets)) {
            self::$assets = array();
        }
        return self::$assets;
    }
}


class_alias('Kodhe\Framework\Support\Modules', 'Modules');