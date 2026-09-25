<?php
if ( ! function_exists('load_class'))
{
	/**
	 * Class registry
	 *
	 * This function acts as a singleton. If the requested class does not
	 * exist it is instantiated and set to a static variable. If it has
	 * previously been instantiated the variable is returned.
	 *
	 * @param	string	the class name being requested
	 * @param	string	the directory where the class should be found
	 * @param	mixed	an optional argument to pass to the class constructor
	 * @return	object
	 */
		function &load_class($class, $directory = null, $param = NULL)
		{
			static $_classes = array();
			
			// Does the class exist? If so, we're done...
			if (isset($_classes[$class]))
			{
				return $_classes[$class];
			}
	
			// Check for namespace in class name
			if (strpos($class, '\\') !== false) {
				
				// Try to autoload the namespaced class
				spl_autoload_call($class);
				
				// If class doesn't exist after autoload, return false or handle error
				if (!class_exists($class, false)) {
					return false; // or throw exception
				}
				
				// Instantiate the class
				$_classes[$class] = isset($param) ? new $class($param) : new $class();
				
				// Create alias if needed (excluding specific directories)
				if (!empty($directory)) {
					$directory_lower = strtolower($directory);
					
					// Only create alias for non-standard directories
					$excluded_directories = ['libraries', 'core', 'helpers', 'drivers'];
					if (!in_array($directory_lower, $excluded_directories, true)) {
						if (!class_exists($directory, false)) {
							
							class_alias($class, $directory);
						}
					}
				}
					
				// Track loaded class
				is_loaded($class);
				return $_classes[$class];
			}

			// A CI_<class> may already be defined (e.g. by an autoloader or a
			// previous require) without having been registered here yet.
			// Instantiate it instead of returning an empty value from the
			// static slot (previous behavior silently broke callers).
			if (class_exists('CI_'.$class, false)) {
				$_name = 'CI_'.$class;
				$_classes[$class] = isset($param) ? new $_name($param) : new $_name();
				is_loaded($class);
				return $_classes[$class];
			}
	
			// ======================================================
			// NEW CODE: Handle Kodhe\Libraries namespace
			// ======================================================
			$kodhe_namespace_class = 'Kodhe\\' . ucfirst($class) . '\\' . ucfirst($class);
			// Check if the Kodhe namespaced class exists
			if (class_exists($kodhe_namespace_class, false)) {
				// Class already loaded
				$_classes[$class] = isset($param) 
					? new $kodhe_namespace_class($param) 
					: new $kodhe_namespace_class();
				
				// Track loaded class
				is_loaded($class);
				return $_classes[$class];
			}
			
			// Try to autoload the Kodhe namespaced class
			spl_autoload_call($kodhe_namespace_class);
			
			if (class_exists($kodhe_namespace_class, false)) {
				// Instantiate the Kodhe namespaced class
				$_classes[$class] = isset($param) 
					? new $kodhe_namespace_class($param) 
					: new $kodhe_namespace_class();
				
				// Track loaded class
				is_loaded($class);
				return $_classes[$class];
			}
			// ======================================================
			// END NEW CODE
			// ======================================================
	
			$name = FALSE;
		
			// Look for the class first in the local application/libraries folder
			// then in the native system/libraries folder
			foreach (array(APPPATH, BASEPATH.'Core/Support/Legacy') as $path)
			{
				// Gunakan resolve_path untuk mencari direktori dengan kemungkinan casing yang berbeda
				$resolved_path = resolve_path($path, $directory);
				
				if (file_exists($resolved_path . '/' . $class . '.php'))
				{
					$name = 'CI_'.$class;
	
					if (class_exists($name, FALSE) === FALSE)
					{
						require_once($resolved_path . '/' . $class . '.php');
					}
	
					break;
				}
			}
	
			// Is the request a class extension? If so we load it too
			// Gunakan resolve_path untuk application directory juga
			$app_resolved_path = resolve_path(APPPATH, $directory);
			
			if (file_exists($app_resolved_path . '/' . config_item('subclass_prefix') . $class . '.php'))
			{
				$name = config_item('subclass_prefix') . $class;
	
				if (class_exists($name, FALSE) === FALSE)
				{
					require_once($app_resolved_path . '/' . $name . '.php');
				}
			}
	
			// Did we find the class?
			if ($name === FALSE)
			{
				// Note: We use exit() rather than show_error() in order to avoid a
				// self-referencing loop with the Exceptions class
				set_status_header(503);
				echo 'Unable to locate the specified class: '.$class.'.php';
				exit(5); // EXIT_UNK_CLASS
			}
	
			// Keep track of what we just loaded
			is_loaded($class);
	
			$_classes[$class] = isset($param)
				? new $name($param)
				: new $name();
			return $_classes[$class];
	}
}

if (!function_exists('session')) {
	/**
	 * Retrieve session data in CI3 (compatible with Blade)
	 *
	 * @param string|null $key The session key to retrieve
	 * @return mixed Session value or null if not found
	 */
	function session($key = null) {
		$ci =& kodhe();
		if ($key) {
			return $ci->session->userdata($key);
		}
		return $ci->session->all_userdata();
	}
}


if (!function_exists('resolve_path')) {
/**
 * Resolve directory path dengan berbagai kemungkinan casing.
 *
 * Proyek CodeIgniter 3 memakai nama folder huruf kecil (config/,
 * libraries/, core/, helpers/, views/, cache/, ...), tetapi proyek
 * Kodhe-style boleh menamainya ulang (Config/, Libraries/, Core/,
 * Helpers/, Views/, Cache/, dst). Pada filesystem case-sensitive
 * (Linux) lookup hardcoded bisa meleset dan file/folder seolah hilang.
 *
 * Strategi (paling akurat -> fallback anggun):
 *   1. exact-case match (nol overhead untuk proyek CI3 standar),
 *   2. scan per-segmen case-insensitive lewat app_path_in() bila
 *      helper tersedia (menangani UPPERCASE, PascalCase, mixed),
 *   3. varian lowercase/ucfirst bila helper belum ter-load,
 *   4. default: kembalikan input apa adanya sehingga pemeriksaan
 *      file_exists()/is_dir() caller gagal anggun seperti semula.
 *
 * @param string      $basePath  Basis direktori (mis. APPPATH)
 * @param string|null $directory Sub-folder relatif (mis. 'config')
 * @return string Path dengan trailing DIRECTORY_SEPARATOR
 */
function resolve_path(string $basePath = '', ?string $directory = ''): string {
$had_directory = ! empty($directory);
		$directory = is_string($directory) ? trim(str_replace(chr(92), '/', $directory), '/') : '';
$basePath  = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

// Tanpa direktori: tidak ada yang perlu di-resolve.
if ($directory === '') {
return $had_directory ? $basePath . DIRECTORY_SEPARATOR : $basePath;
}

// 1) Exact hit tercepat.
if (is_dir($basePath . $directory)) {
return $basePath . $directory . DIRECTORY_SEPARATOR;
}

// 2) Resolusi case-insensitive penuh per segmen.
if ( ! function_exists('app_path_in')) {
$__app_path_helper = __DIR__ . '/app_path.php';
if (is_file($__app_path_helper)) {
require_once $__app_path_helper; // idempoten; definisi dibungkus function_exists
}
}

if (function_exists('app_path_in')) {
$resolved = app_path_in(rtrim($basePath, DIRECTORY_SEPARATOR), $directory);

if (is_dir($resolved)) {
return rtrim($resolved, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
}
}

// 3) Fallback heuristik lama (lowercase & ucfirst) bila scan nihil.
foreach (array_unique([$basePath . strtolower($directory), $basePath . ucfirst(strtolower($directory))]) as $path) {
if (is_dir($path)) {
return rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
}
}

// 4) Default: persis seperti perilaku lama terhadap input.
return $basePath . $directory . DIRECTORY_SEPARATOR;
}
}

if (!function_exists('csrf_token')) {
	/**
	 * Get CSRF token
	 */
	function csrf_token()
	{
		$ci =& get_instance();
		if (isset($ci->session)) {
			return $ci->session->userdata('csrf_token');
		}
		
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}
		return $_SESSION['csrf_token'] ?? '';
	}
}

if (!function_exists('csrf_field')) {
	/**
	 * Generate CSRF hidden input field
	 */
	function csrf_field()
	{
		$token = csrf_token();
		if ($token) {
			return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
		}
		return '';
	}
}

if (!function_exists('csrf_meta')) {
	/**
	 * Generate CSRF meta tag for JavaScript
	 */
	function csrf_meta()
	{
		$token = csrf_token();
		if ($token) {
			return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
		}
		return '';
	}
}


// Di file helper (misal: MY_helper.php atau buat helper khusus)

if (!function_exists('active_module')) {
    /**
     * Get or set active module
     */
    function active_module(?string $moduleName = null): ?string
    {
        
        if ($moduleName !== null) {
            kodhe()->router->set_module($moduleName);
        }
        
        return kodhe()->router->fetch_module();
    }
}

// Helper function wrapper
if (!function_exists('service')) {
    function service(string $name, string $prefix = null)
    {
        return Kodhe\Framework\Container\ServiceHelper::get($name, $prefix);
    }
}
