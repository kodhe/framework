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

			if (class_exists('CI_'.$class, false)) {
				return $_classes[$class];
			}
	
			// ======================================================
			// NEW CODE: Handle Kodhe\Libraries namespace
			// ======================================================
			$kodhe_namespace_class = 'Kodhe\\Library\\' . ucfirst($class) . '\\' . ucfirst($class);
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
	 * Resolve directory path dengan berbagai kemungkinan casing
	 */
	function resolve_path(string $basePath = '', ?string $directory = ''): string {
		$basePath = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		
		// Jika directory null atau kosong, return basePath saja
		if (empty($directory)) {
			return $basePath;
		}
		
		// Prioritaskan direktori yang benar-benar ada
		$variations = [
			$basePath . $directory,                     // original
			$basePath . strtolower($directory),         // lowercase
			$basePath . ucfirst(strtolower($directory)), // ucfirst
		];
		
		// Hapus duplikat
		$variations = array_unique($variations);
		
		foreach ($variations as $path) {
			if (is_dir($path)) {
				return $path . DIRECTORY_SEPARATOR;
			}
		}
		
		// Default: return lowercase (konsisten dengan CI3 style)
		return $variations[1] ?? $variations[0]. DIRECTORY_SEPARATOR;
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
		
		session_start();
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