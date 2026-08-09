<?php

declare(strict_types=1);

namespace Kodhe\Framework\Config\Loaders;

use Kodhe\Framework\Container\Container;
use Kodhe\Framework\Http\Routing\ModernRouter;
use Kodhe\Framework\Http\Routing\Router;
use Kodhe\Framework\Support\Modules;

class FileLoader extends LegacyLoader 
{

    /**
     * List of loaded modules
     *
     * @var array
     * @access protected
     */
    protected $_ci_modules = array();

    /**
     * List of loaded controllers
     *
     * @var array
     * @access protected
     */
    protected $_ci_controllers = array();

    protected Router $router;
    protected $_module = null;

    protected Container $container;
    
    /**
     * Constructor
     *
     * Add the current module to all paths permanently
     */
    public function __construct() {
        parent::__construct();
        

        $this->container = new Container();

        foreach ($this->resolveCoreHelperPaths() as $coreHelpersPath) {
            if (!in_array($coreHelpersPath, $this->_ci_helper_paths, true)) {
                $this->_ci_helper_paths[] = $coreHelpersPath;
            }
        }
    }

    /**
     * Resolve core helper directories (src/Support/Helpers and legacy Framework/Support/Helpers).
     *
     * @return string[]
     */
    protected function resolveCoreHelperPaths(): array
    {
        $paths = [];
        $candidates = [];

        if (defined('BASEPATH') && BASEPATH) {
            $base = rtrim(BASEPATH, '/\\') . DIRECTORY_SEPARATOR;
            $candidates[] = $base . 'src' . DIRECTORY_SEPARATOR . 'Support' . DIRECTORY_SEPARATOR . 'Helpers' . DIRECTORY_SEPARATOR;
            $candidates[] = $base . 'Framework' . DIRECTORY_SEPARATOR . 'Support' . DIRECTORY_SEPARATOR . 'Helpers' . DIRECTORY_SEPARATOR;
            $candidates[] = $base . 'helpers' . DIRECTORY_SEPARATOR;
        }

        if (defined('SYSPATH') && SYSPATH) {
            $sys = rtrim(SYSPATH, '/\\') . DIRECTORY_SEPARATOR;
            $candidates[] = $sys . 'src' . DIRECTORY_SEPARATOR . 'Support' . DIRECTORY_SEPARATOR . 'Helpers' . DIRECTORY_SEPARATOR;
            $candidates[] = $sys . 'Framework' . DIRECTORY_SEPARATOR . 'Support' . DIRECTORY_SEPARATOR . 'Helpers' . DIRECTORY_SEPARATOR;
            $candidates[] = $sys . 'helpers' . DIRECTORY_SEPARATOR;
        }

        if (class_exists(Container::class, true)) {
            try {
                $ref = new \ReflectionClass(Container::class);
                $srcDir = dirname($ref->getFileName(), 2);
                $pkgRoot = dirname($srcDir);
                $candidates[] = $srcDir . DIRECTORY_SEPARATOR . 'Support' . DIRECTORY_SEPARATOR . 'Helpers' . DIRECTORY_SEPARATOR;
                $candidates[] = $pkgRoot . DIRECTORY_SEPARATOR . 'Framework' . DIRECTORY_SEPARATOR . 'Support' . DIRECTORY_SEPARATOR . 'Helpers' . DIRECTORY_SEPARATOR;
            } catch (\Throwable $e) {
                // ignore
            }
        }

        foreach ($candidates as $candidate) {
            if (is_dir($candidate) && !in_array($candidate, $paths, true)) {
                $paths[] = $candidate;
            }
        }

        return $paths;
    }

    /** Initialize the loader variables **/
    public function initialize($controller = NULL)
    {

        $router = new Router();

        /* set the module name */
        $this->_module = $router->fetch_module();
        

        parent::initialize();

        /* add this module path to the loader variables */
        $this->_add_module_paths($this->_module);
    }


    /** Add a module path loader variables **/
    public function _add_module_paths($module = '')
    {
        if (empty($module)) return;

        foreach (Modules::$locations as $location => $offset)
        {
            $module_path = rtrim($location,'/').'/'.$module.'/';
           
            /* only add a module path if it exists */
            if (is_dir($module_path) && ! in_array($module_path, $this->_ci_model_paths))
            {
                // Tambahkan lokasi libraries modul
                $moduleLibrariesPath = $module_path . 'libraries/';
                if (!in_array($moduleLibrariesPath, $this->_ci_library_paths)) {
                    array_unshift($this->_ci_library_paths, $moduleLibrariesPath);
                }

                // Tambahkan lokasi helpers modul
                $moduleHelpersPath = $module_path . 'helpers/';
                if (!in_array($moduleHelpersPath, $this->_ci_helper_paths)) {
                    array_unshift($this->_ci_helper_paths, $moduleHelpersPath);
                }

                // Tambahkan lokasi model modul
                $moduleModelsPath = $module_path . 'models/';
                if (!in_array($moduleModelsPath, $this->_ci_model_paths)) {
                    array_unshift($this->_ci_model_paths, $moduleModelsPath);
                }

                // Tambahkan lokasi views modul (sebagai associative array dengan cascade flag)
                $moduleViewsPath = $module_path . 'views/';
                if (!array_key_exists($moduleViewsPath, $this->_ci_view_paths)) {
                    $this->_ci_view_paths = [$moduleViewsPath => TRUE] + $this->_ci_view_paths;
                }
            }
        }
    }


    /**
     * Class Loader
     *
     * This function lets users load and instantiate classes.
     * It is designed to be called from a user's app controllers.
     *
     * @param    string    the name of the class
     * @param    mixed    the optional parameters
     * @param    string    an optional object name
     * @return    void
     */
    public function library($library = '', $params = NULL, $object_name = NULL) {
        if (is_array($library)) {
            $this->libraries($library, $params);
            return;
        }

        
        // Periksa apakah library menggunakan namespace
        if (strpos($library, '\\') !== FALSE) {
            // Coba autoload class dengan namespace
            if (class_exists($library)) {
                // Buat instance dan simpan ke facade
                $CI =& $this->_getInstance();
                
                // Jika object_name tidak disediakan, generate dari namespace
                if (!$object_name) {
                    $object_name = $this->getObjectNameFromNamespace($library);
                }
                
                $library_obj = isset($params) ? new $library($params) : new $library();
                $this->_bindToFacade($object_name, $library_obj);
                return;
            }
            // Class tidak ditemukan, biarkan parent handle error
        }


            // ======================================================
            // Resolve Composer / PSR-4 packages (CI3-compatible)
            // Tries several namespace patterns so $this->load->library('session')
            // works with modular packages.
            // ======================================================
            $actual_class = $this->resolveLibraryClass($library);

            if ($actual_class !== null) {
                $CI =& $this->_getInstance();

                if (!$object_name) {
                    $object_name = $this->getObjectNameFromNamespace($library);
                }

                // Prefer lowercase CI-style property: session, email, etc.
                if (!$object_name || $object_name === $library) {
                    $object_name = strtolower($library);
                    if (isset($this->_ci_varmap[$object_name])) {
                        $object_name = $this->_ci_varmap[$object_name];
                    }
                }

                $library_obj = ($params !== null) ? new $actual_class($params) : new $actual_class();
                $this->_bindToFacade($object_name, $library_obj);
                return;
            }


        // Deteksi modul untuk library tanpa namespace
        if ([$module, $class] = $this->detect_module($library)) {
            // Module already loaded
            if (in_array($module, $this->_ci_modules)) {
                return parent::library($class, $params, $object_name);
            }

            // Add module
            $this->add_module($module);

            // Let parent do the heavy work
            $void = parent::library($class, $params, $object_name);

            // Remove module
            $this->remove_module();

            return $void;
        } else {
            return parent::library($library, $params, $object_name);
        }
    }


    // Atau jika ingin lebih fleksibel, tambahkan method terpisah untuk array dengan alias:
    public function libraries($libraries = [], $params = NULL) {
        foreach ($libraries as $key => $value) {
            if (is_string($key)) {
                // Format: 'alias' => 'library'
                $this->library($key, $params, $key);
            } else {
                // Format: 'library'
                $this->library($value, $params);
            }
        }
    }




    /**
     * Driver Loader
     *
     * Loads a driver library dengan dukungan namespace Kodhe\Library\Driver.
     *
     * @param	string|string[]	$library	Driver name(s)
     * @param	array		$params		Optional parameters to pass to the driver
     * @param	string		$object_name	An optional object name to assign to
     * @return	object|bool	Object or FALSE on failure if $library is a string
     *				and $object_name is set. CI_Loader instance otherwise.
     */
    public function driver($library, $params = NULL, $object_name = NULL)
    {
        if (is_array($library)) {
            foreach ($library as $key => $value) {
                if (is_int($key)) {
                    $this->driver($value, $params);
                } else {
                    $this->driver($key, $params, $value);
                }
            }
            return $this;
        } elseif (empty($library)) {
            return FALSE;
        }

        // Cek apakah driver menggunakan namespace Kodhe\Library\Driver
        $kodhe_driver_class = 'Kodhe\\'. $this->formatClassName($library) . '\\' . $this->formatClassName($library);
        
        // Coba juga dengan casing asli jika ucfirst tidak bekerja
        $kodhe_driver_class_alt = 'Kodhe\\'. $library . '\\' . $library;

        // Cek jika class sudah ada atau dapat diload via autoloader
        if (class_exists($kodhe_driver_class) || class_exists($kodhe_driver_class_alt)) {
            // Tentukan class mana yang ada
            $actual_class = class_exists($kodhe_driver_class) ? $kodhe_driver_class : $kodhe_driver_class_alt;
            
            // Load driver via library method
            return $this->library($actual_class, $params, $object_name);
        }

        // Deteksi modul untuk driver tanpa namespace
        if ([$module, $class] = $this->detect_module($library)) {
            // Module already loaded
            if (in_array($module, $this->_ci_modules)) {
                return parent::driver($class, $params, $object_name);
            }

            // Add module
            $this->add_module($module);

            // Let parent do the heavy work
            $void = parent::driver($class, $params, $object_name);

            // Remove module
            $this->remove_module();

            return $void;
        } else {
            return parent::driver($library, $params, $object_name);
        }
    }


    /**
     * Model Loader
     *
     * This function lets users load and instantiate models.
     *
     * @param    string    the name of the class
     * @param    string    name for the model
     * @param    bool    database connection
     * @return    void
     */
    public function model($model, $name = '', $db_conn = FALSE) {
    
        // Handle array of models with namespace and alias mapping
        if (is_array($model)) {
            foreach ($model as $key => $value) {
                if (is_string($key)) {
                    if(strpos($key, '/') !== FALSE) {
                        $this->model($key, $value, $db_conn);
                    } else {
                        if (strpos($key, '\\') !== FALSE) {
                            $this->loadNamespacedModel($key, $value, $db_conn);
                        } else {
                            try {
                                $this->model($value, $key, $db_conn);
                            } catch (\Throwable $th) {
                                $this->model($key, $value, $db_conn);
                            }
                        }
                    }
                } else {
                    if(strpos($value, '/') !== FALSE) {
                        $name = explode('/', $name);
                        $name = end($name);
                        $this->model($value, $name, $db_conn);
                    } else {
                        if (strpos($value, '\\') !== FALSE) {
                            $name = is_string($key) ? $key : '';
                            $this->loadNamespacedModel($value, $name, $db_conn);                               
                        } else {
                            $this->model($value, $value, $db_conn);
                        }
                    }
                }

            }
            return;
        }
    
        // Handle single model
        if (strpos($model, '\\') !== FALSE) {
            $this->loadNamespacedModel($model, $name, $db_conn);
            return;
        }
    
        // Deteksi modul untuk model tanpa namespace
        if ([$module, $class] = $this->detect_module($model)) {
            // Module already loaded
            if (in_array($module, $this->_ci_modules)) {
                return parent::model($class, $name, $db_conn);
            }
    
            // Add module
            $this->add_module($module);
    
            // Let parent do the heavy work
            $void = parent::model($class, $name, $db_conn);
    
            // Remove module
            $this->remove_module();
    
            return $void;
        } else {
            return parent::model($model, $name, $db_conn);
        }
    }
    
    /**
     * Load legacy model (non-namespaced) with module detection
     */
    protected function loadLegacyModel($model, $alias = '', $db_conn = FALSE) {
        $class_name = $model; // default
        
        // Jika dari module, ekstrak nama class saja
        if (list($module, $class) = $this->detect_module($model)) {
            $class_name = $class; // 'blog_model_saya'
        }
        
        if (empty($alias)) {
            $alias = $class_name; // BENAR: 'blog_model_saya'
        }
        
        if (list($module, $class) = $this->detect_module($model)) {
            // Module sudah loaded
            if (in_array($module, $this->_ci_modules)) {
                return parent::model($class, $alias, $db_conn);
            }
            
            // Add module
            $this->add_module($module);
            
            // Let parent do the heavy work
            $void = parent::model($class, $alias, $db_conn);
            
            // Remove module
            $this->remove_module();
            
            return $void;
        } else {
            return parent::model($model, $alias, $db_conn);
        }
    }

    /**
     * Load namespaced model
     *
     * @param string $model Namespaced model class
     * @param string $name Alias name
     * @param bool $db_conn Database connection
     * @return void
     */
    protected function loadNamespacedModel($model, $name = '', $db_conn = FALSE)
    {
        // Coba autoload class dengan namespace
        if (class_exists($model)) {
            // Buat instance dan simpan ke facade
            $CI =& $this->_getInstance();
            
            // Jika name tidak disediakan, generate dari namespace
            if (!$name) {
                $name = $this->getObjectNameFromNamespace($model);
            }
            
            // Instantiate model dengan atau tanpa parameter database
            if ($db_conn !== FALSE) {
                $model_obj = new $model($db_conn);
            } else {
                $model_obj = new $model();
            }
            
            $this->_bindToFacade($name, $model_obj);
            return;
        }
        
        // Class tidak ditemukan, coba fallback ke parent
        parent::model($model, $name, $db_conn);
    }

    /**
     * Load View
     *
     * This function is used to load a "view" file. It has three parameters:
     *
     * 1. The name of the "view" file to be included.
     * 2. An associative array of data to be extracted for use in the view.
     * 3. TRUE/FALSE - whether to return the data or load it.
     *
     * @param    string
     * @param    array
     * @param    bool
     * @return    void
     */
    public function view($view, $vars = array(), $return = FALSE, $engine = null) {
        // Tambahkan module path ke view jika ada
        if (!empty($this->_module)) {
            $view = $this->_module . '/' . $view;
        }

        // Prefer modern ViewFactory when kodhe/view is installed
        $viewService = null;
        try {
            if (function_exists('app')) {
                $viewService = app('view');
            }
        } catch (\Throwable $e) {
            $viewService = null;
        }

        if (is_object($viewService) && method_exists($viewService, 'render')) {
            return $viewService->render($view, $vars, $return, $engine);
        }

        // Fallback to classic CI3 file-based view loader
        return parent::view($view, $vars, $return);
    }


    /**
     * Add a view path to the loader
     *
     * @param string $path View path
     * @param bool $cascade Cascade flag
     * @return void
     */
    public function add_view_path($path, $cascade = TRUE)
    {
        $path = rtrim($path, '/') . '/';
        
        if (!array_key_exists($path, $this->_ci_view_paths)) {
            $this->_ci_view_paths[$path] = $cascade;
        }
    }

    /**
     * Prepend a view path to the beginning of search paths
     *
     * @param string $path View path
     * @param bool $cascade Cascade flag
     * @return void
     */
    public function prepend_view_path($path, $cascade = TRUE)
    {
        $path = rtrim($path, '/') . '/';
        
        if (!array_key_exists($path, $this->_ci_view_paths)) {
            // Tambahkan di awal array
            $this->_ci_view_paths = [$path => $cascade] + $this->_ci_view_paths;
        } else {
            // Pindahkan ke awal jika sudah ada
            $cascade = $this->_ci_view_paths[$path];
            unset($this->_ci_view_paths[$path]);
            $this->_ci_view_paths = [$path => $cascade] + $this->_ci_view_paths;
        }
    }

    /**
     * Set primary view path (replace existing)
     *
     * @param string $path View path
     * @param bool $cascade Cascade flag
     * @return void
     */
    public function set_view_path($path, $cascade = TRUE)
    {
        $path = rtrim($path, '/') . '/';
        
        // Reset dan set sebagai satu-satunya path utama
        $this->_ci_view_paths = [$path => $cascade];
    }

    /**
     * Get all view paths
     *
     * @return array
     */
    public function get_view_paths()
    {
        return $this->_ci_view_paths;
    }

    public function legacy_view($view, $vars = array(), $return = FALSE, $layout = false) {

        // Detect module
        if ([$module, $class] = $this->detect_module($view)) {
            $view = $class;
            // Module already loaded
            if (in_array($module, $this->_ci_modules)) {
                return parent::view($view, $vars, $return);
            }
    
            // Add module
            $this->add_module($module);
    
            // Let parent do the heavy work
            $void = parent::view($view, $vars, $return, $layout);
    
            // Remove module
            $this->remove_module();
    
            return $void;
        } else {
            return parent::view($view, $vars, $return, $layout);
        }
    }
    
    /**
     * Loads a config file
     *
     * @param    string
     * @param    bool
     * @param     bool
     * @return    void
     */
    public function config($file = '', $use_sections = FALSE, $fail_gracefully = FALSE) {
        // Detect module
        if (list($module, $class) = $this->detect_module($file)) {
            // Module already loaded
            if (in_array($module, $this->_ci_modules)) {
                return parent::config($class, $use_sections, $fail_gracefully);
            }

            // Add module
            $this->add_module($module);

            // Let parent do the heavy work
            $void = parent::config($class, $use_sections, $fail_gracefully);

            // Remove module
            $this->remove_module();

            return $void;
        } else {
            parent::config($file, $use_sections, $fail_gracefully);
        }
    }

    /**
     * Load Helper
     *
     * This function loads the specified helper file.
     *
     * @param    mixed
     * @return    void
     */
    public function helper($helper = array()) {
        if (is_array($helper)) {
            foreach ($helper as $help) {
                $this->helper($help);
            }
            return;
        }

        // Detect module
        if (list($module, $class) = $this->detect_module($helper)) {
            // Module already loaded
            if (in_array($module, $this->_ci_modules)) {
                return parent::helper($class);
            }

            // Add module
            $this->add_module($module);

            // Let parent do the heavy work
            $void = parent::helper($class);

            // Remove module
            $this->remove_module();

            return $void;
        } else {
            return parent::helper($helper);
        }
    }

    /**
     * Loads a language file
     *
     * @param    array
     * @param    string
     * @return    void
     */
    public function language($file = array(), $lang = '') {
        if (is_array($file)) {
            foreach ($file as $langfile) {
                $this->language($langfile, $lang);
            }
            return;
        }

        // Detect module
        if (list($module, $class) = $this->detect_module($file)) {
            // Module already loaded
            if (in_array($module, $this->_ci_modules)) {
                return parent::language($class, $lang);
            }

            // Add module
            $this->add_module($module);

            // Let parent do the heavy work
            $void = parent::language($class, $lang);

            // Remove module
            $this->remove_module();

            return $void;
        } else {
            return parent::language($file, $lang);
        }
    }

    /**
     * Load Widget
     *
     * This function provides support to Jens Segers Template Library for loading
     * widget controllers within modules (place in module/widgets folder).
     * @author  hArpanet - 23-Jun-2014
     *
     * @param   string $widget  Must contain Module name if widget within a module
     *                          (eg. test/nav  where module name is 'test')
     * @return  array|false
     */
    public function widget($widget) {

        // Detect module
        if (list($module, $widget) = $this->detect_module($widget)) {
            // Module already loaded
            if (in_array($module, $this->_ci_modules)) {
                return array($module, $widget);
            }
            
            // Add module
            $this->add_module($module);

            // Look again now we've added new module path
            $void = $this->widget($module.'/'.$widget);

            // Remove module if widget not found within it
            if (!$void) {
                $this->remove_module();
            }

            return $void;

        } else {
            // widget not found in module
            return FALSE;
        }
    }

    /**
     * Add Module
     *
     * Allow resources to be loaded from this module path
     *
     * @param    string
     * @param     boolean
     */
    public function add_module($module, $view_cascade = TRUE)
    {
        if ($path = $this->find_module($module)) {
            // Mark module as loaded
            array_unshift($this->_ci_modules, $module);

            // Add paths using consistent method
            $this->_add_module_paths($module);
            
            // Add package path untuk kompatibilitas
            parent::add_package_path($path, $view_cascade);
        }
    }   
 

    /**
     * Remove Module
     *
     * Remove a module from the allowed module paths
     *
     * @param    type
     * @param     bool
     */
    public function remove_module($module = '', $remove_config = TRUE)
    {
        if ($module == '') {
            // Mark module as not loaded
            $removed_module = array_shift($this->_ci_modules);
            
            // Remove paths for the removed module
            if ($removed_module) {
                $this->_remove_module_paths($removed_module);
            }
        } else if (($key = array_search($module, $this->_ci_modules)) !== FALSE) {
            if ($path = $this->find_module($module)) {
                // Mark module as not loaded
                unset($this->_ci_modules[$key]);
                $this->_ci_modules = array_values($this->_ci_modules); // Re-index array
                
                // Remove paths
                $this->_remove_module_paths($module);
            }
        }
        
        // Panggil parent untuk membersihkan package path
        parent::remove_package_path('', $remove_config);
    }

    /**
     * Remove module paths from loader variables
     * 
     * @param string $module Module name
     */
    protected function _remove_module_paths($module)
    {
        foreach (Modules::$locations as $location => $offset)
        {
            $module_path = rtrim($location,'/').'/'.$module.'/';
            
            // Remove libraries path
            $moduleLibrariesPath = $module_path . 'libraries/';
            if (($key = array_search($moduleLibrariesPath, $this->_ci_library_paths)) !== FALSE) {
                unset($this->_ci_library_paths[$key]);
                $this->_ci_library_paths = array_values($this->_ci_library_paths); // Re-index
            }
            
            // Remove helpers path
            $moduleHelpersPath = $module_path . 'helpers/';
            if (($key = array_search($moduleHelpersPath, $this->_ci_helper_paths)) !== FALSE) {
                unset($this->_ci_helper_paths[$key]);
                $this->_ci_helper_paths = array_values($this->_ci_helper_paths); // Re-index
            }
            
            // Remove models path
            $moduleModelsPath = $module_path . 'models/';
            if (($key = array_search($moduleModelsPath, $this->_ci_model_paths)) !== FALSE) {
                unset($this->_ci_model_paths[$key]);
                $this->_ci_model_paths = array_values($this->_ci_model_paths); // Re-index
            }
            
            // Remove views path
            $moduleViewsPath = $module_path . 'views/';
            if (array_key_exists($moduleViewsPath, $this->_ci_view_paths)) {
                unset($this->_ci_view_paths[$moduleViewsPath]);
            }
        }
    }
    
    /**
     * Detects the module from a string. Returns the module name and class if found.
     *
     * @param    string
     * @return    array|boolean
     */
    private function detect_module($class) {
        $class = str_replace('.php', '', trim($class, '/'));
        if (($first_slash = strpos($class, '/')) !== FALSE) {
            $module = substr($class, 0, $first_slash);
            $class = substr($class, $first_slash + 1);
            
        }

        $module = $module ?? $this->_module;
        
        // Check if module exists menggunakan Modules class
        if ($this->find_module($module)) {
            return array($module, $class);
        }


        return FALSE;
    }

    /**
     * Searches a given module name. Returns the path if found, FALSE otherwise
     *
     * @param string $module
     * @return string|boolean
     */
    private function find_module($module) {
        // Gunakan Modules::path() untuk mencari module path
        $path = Modules::path($module);
        if ($path) {
            return $path;
        }

        return FALSE;
    }

    /**
     * Check if class string appears to be a valid model class
     */
    protected function isValidModelClass($class) {
        // Basic check: contains only valid namespace/class characters
        return preg_match('/^[a-zA-Z0-9_\\\\]+$/', $class) && !is_numeric($class);
    }

    /**
     * Get object name from namespace
     *
     * @param string $namespacedClass
     * @return string
     */
    protected function getObjectNameFromNamespace($namespacedClass)
    {
        $parts = explode('\\', $namespacedClass);
        $className = end($parts);
        $className = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
        if (array_key_exists($className, $this->_ci_varmap)) {
            $className = $this->_ci_varmap[$className];
        }
        // Convert CamelCase to snake_case untuk konsistensi dengan CI
        return $className;
    }
    
    protected function formatClassName($class) {
    
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $class)));
    }


    /**
     * Resolve a CI-style library name to a fully-qualified class name.
     *
     * Supports modular packages under Kodhe\Framework\* and Kodhe\*.
     * Also registers CI_* class aliases for backward compatibility.
     *
     * @param string $library e.g. "session", "email", "user_agent"
     * @return string|null FQCN or null if not found
     */
    protected function resolveLibraryClass(string $library): ?string
    {
        // Normalize: user_agent -> UserAgent, session -> Session
        $studly = $this->formatClassName($library);
        $lower = strtolower($library);

        // Special CI varmap reverse (agent -> UserAgent package)
        $packageMap = [
            'session'     => 'Kodhe\\Framework\\Session\\Session',
            'cache'       => 'Kodhe\\Framework\\Cache\\Cache',
            'email'       => 'Kodhe\\Email\\Email',
            'upload'      => 'Kodhe\\Upload\\Upload',
            'image_lib'   => 'Kodhe\\Image\\ImageLib',
            'image'       => 'Kodhe\\Image\\ImageLib',
            'encryption'  => 'Kodhe\\Encryption\\Encryption',
            'encrypt'     => 'Kodhe\\Encrypt\\Encrypt',
            'ftp'         => 'Kodhe\\Ftp\\Ftp',
            'zip'         => 'Kodhe\\Zip\\Zip',
            'cart'        => 'Kodhe\\Cart\\Cart',
            'calendar'    => 'Kodhe\\Calendar\\Calendar',
            'pagination'  => 'Kodhe\\Pagination\\Pagination',
            'parser'      => 'Kodhe\\Parser\\Parser',
            'profiler'    => 'Kodhe\\Profiler\\Profiler',
            'table'       => 'Kodhe\\Table\\Table',
            'typography'  => 'Kodhe\\Typography\\Typography',
            'javascript'  => 'Kodhe\\Javascript\\Javascript',
            'user_agent'  => 'Kodhe\\UserAgent\\UserAgent',
            'agent'       => 'Kodhe\\UserAgent\\UserAgent',
            'unit_test'   => 'Kodhe\\Test\\UnitTest',
            'form_validation' => 'Kodhe\\Framework\\Validation\\FormValidation',
            'xmlrpc'      => 'Kodhe\\Xmlrpc\\Xmlrpc',
            'xmlrpcs'     => 'Kodhe\\Xmlrpcs\\Xmlrpcs',
            'trackback'   => 'Kodhe\\Trackback\\Trackback',
            'migration'   => 'Kodhe\\Migration\\Migration',
        ];

        $candidates = [];

        if (isset($packageMap[$lower])) {
            $candidates[] = $packageMap[$lower];
        }

        // Generic patterns
        $candidates[] = 'Kodhe\\Framework\\' . $studly . '\\' . $studly;
        $candidates[] = 'Kodhe\\' . $studly . '\\' . $studly;
        $candidates[] = 'Kodhe\\Framework\\' . $studly;
        $candidates[] = 'Kodhe\\' . $studly;

        // Image special (class still named ImageLib)
        if ($lower === 'image' || $lower === 'image_lib') {
            $candidates[] = 'Kodhe\\Image\\ImageLib';
        }

        foreach ($candidates as $class) {
            if (class_exists($class)) {
                // CI3 compatibility aliases
                $ciName = 'CI_' . $studly;
                if ($lower === 'user_agent') {
                    $ciName = 'CI_User_agent';
                }
                if ($lower === 'image_lib' || $lower === 'image') {
                    $ciName = 'CI_Image_lib';
                }
                if ($lower === 'form_validation') {
                    $ciName = 'CI_Form_validation';
                }
                if ($lower === 'unit_test') {
                    $ciName = 'CI_Unit_test';
                }

                if (!class_exists($ciName, false)) {
                    class_alias($class, $ciName);
                }
                // Also alias plain Session, Email, etc. if not already defined
                if (!class_exists($studly, false) && strpos($studly, '_') === false) {
                    class_alias($class, $studly);
                }

                return $class;
            }
        }

        return null;
    }



    /**
     * Run a module controller method
     *
     * @param string $module Module/Controller/Method path
     * @return mixed
     */
    public function run_module($module)
    {
        return Modules::run($module);
    }
    
    /**
     * Load a module controller
     *
     * @param string $module Module name
     * @return mixed
     */
    public function load_module($module)
    {
        return Modules::load($module);
    }
    
    /**
     * Check if a module controller exists
     *
     * @param string $controller Controller name
     * @param string $module Module name
     * @return bool
     */
    public function module_controller_exists($controller, $module)
    {
        return Modules::controller_exists($controller, $module);
    }
    
    /**
     * Get module file path
     *
     * @param string $module Module name
     * @param string $folder Folder within module
     * @param string $file File name
     * @return string|bool
     */
    public function module_file_path($module, $folder, $file)
    {
        return Modules::file_path($module, $folder, $file);
    }
    
    /**
     * Get module config
     *
     * @param string $module Module name
     * @param bool $return_full Return full config
     * @return array
     */
    public function module_config($module, $return_full = false)
    {
        return Modules::config($module, $return_full);
    }
    
    /**
     * Get list of modules
     *
     * @param bool $exclude_core Exclude core modules
     * @return array
     */
    public function list_modules($exclude_core = false)
    {
        return Modules::list_modules($exclude_core);
    }
    
    /**
     * Register module asset
     *
     * @param string $asset Asset path
     */
    public function register_asset($asset)
    {
        Modules::register_asset($asset);
    }
    
    /**
     * Get registered assets
     *
     * @return array
     */
    public function assets()
    {
        return Modules::assets();
    }
    
}
