<?php

declare(strict_types=1);

namespace Kodhe\Framework\Support;

use Kodhe\Framework\Exceptions\Http\BadRequestException;

// Guarantee the case-insensitive app path helpers (app_path_in(),
// app_config_folder(), app_folder(), app_controller_file(), ...) are
// available even when this package is loaded without composer's autoload
// "files" section (manual includes, bundled copies, etc.).
if ( ! function_exists('app_path_in'))
{
    foreach (array(
        __DIR__ . '/app_path.php',
        dirname(__DIR__, 2).'/framework/src/Support/app_path.php',
        dirname(__DIR__, 2).'/../framework/src/Support/app_path.php',
    ) as $__app_path_helper) {
        if (is_file($__app_path_helper)) {
            require_once $__app_path_helper;
            break;
        }
    }
}
unset($__app_path_helper);

// Modules::load() instantiates the legacy-capable Router from the http
// package. That package is not a hard dependency of the framework Support
// layer, so guarantee the class is loadable before referencing it; the
// monorepo layout keeps the file at a stable relative path.
if ( ! class_exists(\Kodhe\Framework\Http\Routing\Router::class, false))
{
    foreach (array(
        // Monorepo layout: packages/http/src/Routing/Router.php
        dirname(__DIR__, 2).'/http/src/Routing/Router.php',
        // Installed as standalone packages side by side
        dirname(__DIR__, 4).'/http/src/Routing/Router.php',
    ) as $__router_file) {
        if (is_file($__router_file)) {
            require_once $__router_file;
            break;
        }
    }
    spl_autoload_call(\Kodhe\Framework\Http\Routing\Router::class);
}
unset($__router_file);

class Modules
{
    public static $routes = array();
    public static $registry = array();
    public static $locations = array();
    public static $assets = array();
    
    /**
     * @var string The module currently being dispatched, when known.
     *             UnifiedRouter and the module-aware loader keep this in
     *             sync; helpers like current_module() read it back.
     */
    protected static $currentModule = '';

    /**
     * @var bool Whether init() has completed a location resolution pass.
     *           Guards against re-scanning on every Modules::init() call
     *           once the singleton state is warm (and after refreshCache()
     *           resets $locations deliberately).
     */
    protected static $initialized = false;
    
    /**
     * @var array Cache of all modules with their paths
     */
    protected static $modulesCache = array();
    
    /**
     * @var string Cache file path
     */
    protected static $cacheFile;
    
    /**
     * Initialize module locations and cache
     */
    public static function init()
    {
        if (self::$initialized || empty(self::$locations)) {
            return;
        }

        self::$initialized = true;

        {
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
                // Default module locations. Projects store modules under a
                // first-level application folder that may be spelled either
                // lowercase ('modules/', plain CI3 style) or PascalCase
                // ('Modules/', Kodhe style). On case-sensitive filesystems
                // (Linux) a hardcoded lowercase lookup silently finds no
                // modules at all, so resolve the real on-disk name with the
                // case-insensitive app_folder() helper and register both the
                // absolute path and its web-relative offset (used by the
                // router when building asset/view URLs).
                self::$locations = array();

                if (defined('APPPATH')) {
                    $modules_folder    = app_folder('modules');
                    $modules_folder_lc = strtolower($modules_folder);

                    self::$locations[resolve_path(APPPATH, $modules_folder . '/')] = '../' . $modules_folder_lc . '/';
                }

                // Check if IGO core path exists and add it
                if (defined('BASHPATH')) {
                    $igo_folder = is_dir(BASHPATH . 'modules') ? 'modules' : (is_dir(BASHPATH . 'Modules') ? 'Modules' : null);
                    if ($igo_folder !== null) {
                        $igo_path = resolve_path(BASHPATH, $igo_folder . '/');
                        if (is_dir($igo_path)) {
                            self::$locations[$igo_path] = '../../' . strtolower($igo_folder) . '/';
                        }
                    }
                }

                if (empty(self::$locations)) {
                    // No usable base path defined: keep the historical
                    // fallback so behavior stays identical to plain CI3.
                    self::$locations = array(
                        resolve_path(APPPATH ?? '', 'modules/') => '../modules/',
                    );
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
     * @var bool Whether registerAutoloading() has run this request.
     */
    protected static $autoloadingRegistered = false;

    /**
     * Register every discovered module's namespace with the Kodhe
     * Autoloader, based on each module's setup.php ('namespace' key).
     *
     * Without this, classes like Modules\Blog\Controllers\Post are simply
     * not autoloadable: no PSR-4 prefix maps 'Modules\' to the modules
     * folder, so route resolution finds the route but controller execution
     * fails and the visitor gets a 404 page. We add both the exact
     * namespace declared in setup.php and the conventional
     * 'Modules\<StudlyFolderName>' mapping (pointing at the real,
     * case-correct folder path), and guarantee the autoloader is actually
     * registered with spl_autoload_register().
     *
     * Idempotent: safe to call from every boot path.
     */
    public static function registerAutoloading(): void
    {
        if (self::$autoloadingRegistered) {
            return;
        }
        self::$autoloadingRegistered = true;

        self::init();

        if (!class_exists('\\Kodhe\\Framework\\Support\\Autoloader')) {
            return;
        }

        $autoloader = \Kodhe\Framework\Support\Autoloader::getInstance();

        foreach (self::$modulesCache as $module => $paths) {
            foreach ($paths as $path) {
                $path = rtrim($path, '/\\') . DIRECTORY_SEPARATOR;

                // Conventional mapping: Modules\<StudlyCaseFolder> => <path>
                $folder = basename($path);
                $autoloader->addPrefix('Modules\\' . $folder, $path);

                // Exact mapping from the module manifest, when present.
                $setupFile = app_path_in($path, 'setup.php');
                if (is_file($setupFile)) {
                    $setup = require $setupFile;
                    if (is_array($setup) && !empty($setup['namespace'])) {
                        $autoloader->addPrefix(trim((string) $setup['namespace'], '\\'), $path);
                    }
                    unset($setup);
                }
            }
        }

        // Make sure our loader actually runs (composer's autoloader may be
        // the only one registered when bootstrap/app.php was skipped).
        $autoloader->register();
    }

    /**
     * Set the module currently being dispatched.
     *
     * Called by the routers/loaders once a module has been resolved for the
     * active request so helpers like current_module() can read it back.
     */
    public static function setCurrentModule(string $module): void
    {
        self::$currentModule = $module;
    }

    /**
     * Get the module currently being dispatched ('' when outside a module).
     */
    public static function getCurrentModule(): string
    {
        return self::$currentModule;
    }

    /**
     * Return basic metadata about a module: name, on-disk path(s), and the
     * contents of its module_config array (when present).
     */
    public static function getModuleInfo(string $module_name): array
    {
        self::init();

        if (!isset(self::$modulesCache[$module_name])) {
            return array();
        }

        return array(
            'name'   => $module_name,
            'path'   => self::getFirstModulePath($module_name),
            'paths'  => self::$modulesCache[$module_name],
            'config' => self::config($module_name),
        );
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
            
        // Config::item() returns NULL when the optional 'cache_path' key is
        // not defined. Passing that straight to rtrim() fatals under
        // declare(strict_types=1) and aborts route execution (Modules::init()
        // runs from Router::__construct), so every route - including closure
        // routes - then looks "not found". The helper always yields a usable
        // directory, falling back to STORAGEPATH (or the temp dir when even
        // that constant is undefined).
        if ( ! function_exists('kodhe_cache_path')) {
            require_once __DIR__ . '/storage_path.php';
        }

        $cache_path = kodhe_cache_path($path);
        self::$cacheFile = rtrim($cache_path, '/\\') . DIRECTORY_SEPARATOR . 'modules.cache.json';
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

        try {
            // Pure-JSON payload, written atomically (temp+rename) so
            // concurrent requests never read a half-written file. The
            // file is no longer executable PHP.
            CacheFileWriter::write(self::$cacheFile, $cacheData);

            // Remove the legacy .cache.php sibling if it still exists.
            $legacy = CacheFileWriter::legacyPath(self::$cacheFile);
            if (is_file($legacy)) {
                @unlink($legacy);
            }

            return true;
        } catch (\RuntimeException $e) {
            throw new BadRequestException($e->getMessage(), 0, $e);
        }
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
            // Top-level mtimes of each location: cheap dev-mode invalidation
            // signal (a module added/removed bumps the parent dir mtime).
            'location_mtimes' => array_map(
                static fn ($location) => @filemtime($location) ?: 0,
                array_keys(self::$locations)
            ),
        ];
    }

    /**
     * Load modules data from cache
     */
    public static function loadFromCache(): bool
    {
        // Development mode: the on-disk cache is never *trusted*, but we still
        // read it as a cheap baseline and only rescan when a module location's
        // mtime changed (module added/removed) or a cached entry disappeared
        // from disk. This avoids a full scandir() of every location on each
        // request while keeping newly created/deleted modules visible.
        if (defined('ENVIRONMENT') && ENVIRONMENT !== 'production') {
            return self::loadFromCacheDev();
        }
        
        // New JSON cache first; fall back to a legacy .cache.php heredoc
        // file (if any) and migrate it transparently to the new format.
        $cacheData = CacheFileWriter::read(self::$cacheFile, 'locations');

        if ($cacheData === null) {
            $legacy = CacheFileWriter::legacyPath(self::$cacheFile);
            $cacheData = CacheFileWriter::readLegacy($legacy, 'locations');

            if ($cacheData === null) {
                // Missing or corrupt - drop both and force a rescan.
                self::clearCache();
                return false;
            }

            @unlink($legacy);
            try {
                CacheFileWriter::write(self::$cacheFile, $cacheData);
            } catch (\RuntimeException $e) {
                // Migration write failed; still usable in-memory this request.
            }
        }

        // Restore all data from cache
        self::$locations = $cacheData['locations'];
        self::$modulesCache = $cacheData['modules_list'] ?? array();

        return true;
    }

    /**
     * Development-mode cache load with cheap invalidation.
     *
     * Reads the JSON cache (if valid) and validates it without a full scan:
     *   1. any module root directory that vanished -> stale;
     *   2. any location directory whose mtime changed since caching
     *      (a module was added/removed at top level) -> stale.
     * Only when stale do we fall back to the normal rescan path (init()
     * calls scanAndCacheAllModules(), which rewrites the cache).
     *
     * @return bool TRUE when the cached data is still fresh and restored.
     */
    protected static function loadFromCacheDev(): bool
    {
        $cacheData = CacheFileWriter::read(self::$cacheFile, 'locations');

        if ($cacheData === null || empty($cacheData['locations'])) {
            return false;
        }

        $mtimes = $cacheData['location_mtimes'] ?? array();

        foreach ($cacheData['locations'] as $location => $offset) {
            clearstatcache(true, $location);
            if (!is_dir($location)) {
                return false; // a module root disappeared -> rescan
            }
            $current = @filemtime($location);
            if ($current === false || !isset($mtimes[$location]) || $mtimes[$location] !== $current) {
                return false; // top-level entries changed -> rescan
            }
        }

        self::$locations = $cacheData['locations'];
        self::$modulesCache = $cacheData['modules_list'] ?? array();

        return true;
    }

    /**
     * Clear module cache
     */
    public static function clearCache(): bool
    {
        $result = true;

        foreach (array(self::$cacheFile, CacheFileWriter::legacyPath(self::$cacheFile)) as $file) {
            if (is_file($file) && !@unlink($file)) {
                $result = false;
            }
        }

        return $result;
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
        self::$initialized = false;
        
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

            // Case-insensitive fallback for renamed folders/spellings
            $fallback = self::ci_find_fallback($module_path, $base, $subpath, $file_name, $file_ext);
            if ($fallback[0] !== FALSE) {
                log_message('debug', "Found cached ci-resolved file: {$fallback[0]}");
                return $fallback;
            }
        }
        
        log_message('debug', "Cached file not found: {$file} in module {$module}");
        return array(FALSE, $file);
    }
    
    /**
     * Case-insensitive fallback lookup for module files.
     *
     * Handles renamed folders (Libraries/, Models/, Language/) and the usual
     * CI3/Kodhe file-spelling conventions (Auth.php, auth_lib.php, auth.php).
     * Returns array($path, $name) or array(FALSE, $file_name).
     */
    protected static function ci_find_fallback(string $module_path, string $base, string $subpath, string $file_name, string $file_ext): array
    {
        if ( ! function_exists('app_class_file_in'))
        {
            return array(FALSE, $file_name);
        }

        $base = trim(str_replace(array("\\", '/'), '/', $base), '/');

        if ($base === '')
        {
            return array(FALSE, $file_name);
        }

        // First segment of $base is the (possibly renamed) folder; the rest
        // stays part of the relative path inside it.
        $parts = explode('/', $base);
        $folder = array_shift($parts);
        $rest = implode('/', $parts);
        $relative = ($rest !== '' ? $rest.'/' : '').$subpath.$file_name;

        $hit = app_class_file_in($module_path, $folder, $relative);
        if ($hit !== null)
        {
            return array($hit, basename($hit, '.php'));
        }

        // libraries/models also probe the _lib suffix spelling
        $hit = app_class_file_in($module_path, $folder, $relative.'_lib');
        if ($hit !== null)
        {
            return array($hit, $file_name);
        }

        return array(FALSE, $file_name);
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
            $config_file = app_config_file_in($module_path, 'config.php');
            if (is_file($config_file)) {
                return $config_file;
            }
        }
        
        return null;
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
            $segments = explode('/', $module);
            array_pop($segments); // method name or stray trailing segment
            $module_name = implode('/', $segments);

            if ($module_name === '') {
                $module_name = $alias;
            }

            $class = ucfirst(basename($module));
            $path  = null;

            // Preferred path: locate the controller directly inside the
            // module cache. This is case-insensitive and never requires a
            // full router bootstrap (which depends on app()/config and
            // re-parses every route file).
            list($found_path, $found_name) = self::find($class, $module_name, 'controllers/');
            if ($found_path !== FALSE) {
                $class = $found_name;
                $path  = dirname($found_path) . '/';
            } else {
                // Fallback: run the request through the legacy-capable
                // router and resolve the directory it picked. The router's
                // $directory is an offset relative to APPPATH for module
                // routes ('../modules/blog/controllers/') but relative to
                // APPPATH.'controllers/' for plain app controllers, so try
                // both bases before giving up.
                $router  = new Router();
                $located = $router->locate(explode('/', $module));

                $loc_class = '';
                if (is_array($located) && !empty($located)) {
                    $loc_class = $located[0];
                }

                if (empty($loc_class)) {
                    log_message('debug', "Module controller not found: {$module}");
                    return FALSE;
                }

                $class = $loc_class;
                $directory = $router->fetch_directory();

                $candidates = array();
                if ($directory !== '' && strncmp($directory, '../', 3) === 0) {
                    $candidates[] = resolve_path(APPPATH, $directory);
                }
                $candidates[] = resolve_path(APPPATH, 'controllers/') . $directory;

                foreach ($candidates as $candidate_dir) {
                    $probe = $candidate_dir . ucfirst($class) . '.php';
                    if (file_exists($probe)) {
                        $path = $candidate_dir;
                        break;
                    }
                }

                if ($path === null) {
                    log_message('error', "Module controller file not found: {$module}");
                    return FALSE;
                }
            }

            $class_suffix = function_exists('config_item') ? config_item('controller_suffix') : '';
            if ($class_suffix && strpos($class, $class_suffix) === FALSE) {
                $suffixed = $path . $class . $class_suffix . '.php';
                if (file_exists($suffixed)) {
                    $class .= $class_suffix;
                }
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
            $loaded = include $location;

            // Auto-detect modern style: file returns the array directly
            // (e.g. "return ['key' => 'value'];") as well as the classic
            // CI3 style ("$route['key'] = value; ... return $route;").
            if ($type !== 'other' && is_array($loaded))
            {
                $result = $loaded;
            }
            elseif (isset($$type) && is_array($$type))
            {
                $result = $$type;
            }
            else
            {
                show_error("{$location} does not contain a valid {$type} array");
            }
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

            // Case-insensitive fallback for renamed folders/spellings
            $fallback = self::ci_find_fallback($module_path, $base, $subpath, $file_name, $file_ext);
            if ($fallback[0] !== FALSE) {
                log_message('debug', "Found ci-resolved file: {$fallback[0]}");
                return $fallback;
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
            // Try config/routes.php first, then the Routes/ folder
            // (both spellings resolved case-insensitively on Linux).
            list($path) = self::find('routes', $module, 'config/');
            if ($path) {
                self::$routes[$module] = self::load_file('routes', $path, 'route');
            } else {
                // Probe both 'Routes/' and lowercase 'routes/' folder
                // spellings so either project convention works on
                // case-sensitive filesystems (Linux).
                foreach (array('Routes/', 'routes/') as $routes_dir) {
                    list($path) = self::find('web.php', $module, $routes_dir);
                    if ($path) {
                        self::$routes[$module] = self::load_file('web', $path, 'route');
                        break;
                    }
                }
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
