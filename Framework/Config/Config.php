<?php namespace Kodhe\Framework\Config;

use Kodhe\Framework\Support\Modules;

class Config implements \ArrayAccess
{	
	public $config = array();
	public $is_loaded =	array();
	public $_config_paths =	array(APPPATH);

	public function __construct()
	{
		$this->config =& get_config();

		// Set the base_url automatically if none was provided
		if (empty($this->config['base_url']))
		{
			if (isset($_SERVER['SERVER_ADDR']))
			{
				if (strpos($_SERVER['SERVER_ADDR'], ':') !== FALSE)
				{
					$server_addr = '['.$_SERVER['SERVER_ADDR'].']';
				}
				else
				{
					$server_addr = $_SERVER['SERVER_ADDR'];
				}

				$base_url = (is_https() ? 'https' : 'http').'://'.$server_addr
					.substr($_SERVER['SCRIPT_NAME'], 0, strpos($_SERVER['SCRIPT_NAME'], basename($_SERVER['SCRIPT_FILENAME'])));
			}
			else
			{
				$base_url = 'http://localhost/';
			}

			$this->set_item('base_url', $base_url);
		}

		log_message('info', 'Config Class Initialized');
	}

	public function load($file = '', $use_sections = FALSE, $fail_gracefully = FALSE, $_module = ''): mixed
	{
		if (in_array($file, $this->is_loaded, TRUE)) return $this->item($file);

		$_module OR $_module = kodhe()->router->fetch_module();
		list($path, $file) = Modules::find($file, $_module, 'config/');
		
		if ($path === FALSE)
		{
			$this->_load($file, $use_sections, $fail_gracefully);					
			return $this->item($file);
		}  
		
		if ($config = Modules::load_file($file, $path, 'config'))
		{
			/* reference to the config array */
			$current_config =& $this->config;

			if ($use_sections === TRUE)	
			{
				if (isset($current_config[$file])) 
				{
					$current_config[$file] = array_merge($current_config[$file], $config);
				} 
				else 
				{
					$current_config[$file] = $config;
				}
				
			} 
			else 
			{
				$current_config = array_merge($current_config, $config);
			}

			$this->is_loaded[] = $file;
			unset($config);
			return $this->item($file);
		}
		
		return NULL;
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch a config file item
	 *
	 * @param	string	$item	Config item name
	 * @param	string	$index	Index name
	 * @return	mixed|null	The configuration item or NULL if the item doesn't exist
	 */
	public function item($item, $index = ''): mixed
	{
		if ($index == '')
		{
			return isset($this->config[$item]) ? $this->config[$item] : NULL;
		}

		return isset($this->config[$index], $this->config[$index][$item]) ? $this->config[$index][$item] : NULL;
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch a config file item with slash appended (if not empty)
	 *
	 * @param	string		$item	Config item name
	 * @return	string|null	The configuration item or NULL if the item doesn't exist
	 */
	public function slash_item($item): ?string
	{
		if ( ! isset($this->config[$item]))
		{
			return NULL;
		}
		elseif (trim($this->config[$item]) === '')
		{
			return '';
		}

		return rtrim($this->config[$item], '/').'/';
	}

	// --------------------------------------------------------------------

	/**
	 * Site URL
	 *
	 * Returns base_url . index_page [. uri_string]
	 *
	 * @uses	CI_Config::_uri_string()
	 *
	 * @param	string|string[]	$uri	URI string or an array of segments
	 * @param	string	$protocol
	 * @return	string
	 */
	public function site_url($uri = '', $protocol = NULL): string
	{
		$base_url = $this->slash_item('base_url');

		if (isset($protocol))
		{
			// For protocol-relative links
			if ($protocol === '')
			{
				$base_url = substr($base_url, strpos($base_url, '//'));
			}
			else
			{
				$base_url = $protocol.substr($base_url, strpos($base_url, '://'));
			}
		}

		if (empty($uri))
		{
			return $base_url.$this->item('index_page');
		}

		$uri = $this->_uri_string($uri);

		if ($this->item('enable_query_strings') === FALSE)
		{
			$suffix = isset($this->config['url_suffix']) ? $this->config['url_suffix'] : '';

			if ($suffix !== '')
			{
				if (($offset = strpos($uri, '?')) !== FALSE)
				{
					$uri = substr($uri, 0, $offset).$suffix.substr($uri, $offset);
				}
				else
				{
					$uri .= $suffix;
				}
			}

			return $base_url.$this->slash_item('index_page').$uri;
		}
		elseif (strpos($uri, '?') === FALSE)
		{
			$uri = '?'.$uri;
		}

		return $base_url.$this->item('index_page').$uri;
	}

	// -------------------------------------------------------------

	/**
	 * Base URL
	 *
	 * Returns base_url [. uri_string]
	 *
	 * @uses	CI_Config::_uri_string()
	 *
	 * @param	string|string[]	$uri	URI string or an array of segments
	 * @param	string	$protocol
	 * @return	string
	 */
	public function base_url($uri = '', $protocol = NULL): string
	{
		$base_url = $this->slash_item('base_url');

		if (isset($protocol))
		{
			// For protocol-relative links
			if ($protocol === '')
			{
				$base_url = substr($base_url, strpos($base_url, '//'));
			}
			else
			{
				$base_url = $protocol.substr($base_url, strpos($base_url, '://'));
			}
		}

		return $base_url.$this->_uri_string($uri);
	}

	// -------------------------------------------------------------

	/**
	 * Build URI string
	 *
	 * @used-by	CI_Config::site_url()
	 * @used-by	CI_Config::base_url()
	 *
	 * @param	string|string[]	$uri	URI string or an array of segments
	 * @return	string
	 */
	protected function _uri_string($uri): string
	{
		if ($this->item('enable_query_strings') === FALSE)
		{
			is_array($uri) && $uri = implode('/', $uri);
			return ltrim($uri, '/');
		}
		elseif (is_array($uri))
		{
			return http_build_query($uri);
		}

		return $uri;
	}

	// --------------------------------------------------------------------

	/**
	 * System URL
	 *
	 * @deprecated	3.0.0	Encourages insecure practices
	 * @return	string
	 */
	public function system_url(): string
	{
		$x = explode('/', preg_replace('|/*(.+?)/*$|', '\\1', BASEPATH));
		return $this->slash_item('base_url').end($x).'/';
	}

	// --------------------------------------------------------------------

	/**
	 * Set a config file item
	 *
	 * @param	string	$item	Config item key
	 * @param	mixed	$value	Config item value
	 * @return	void
	 */
	public function set_item($item, $value): void
	{
		$this->config[$item] = $value;
	}

    /**
     * Assign to Config
     *
     * This function is called by the front controller (boot.php)
     * after the Config class is instantiated.  It permits config items
     * to be assigned or overriden by variables contained in the index.php file
     *
     * @access  private
     * @param   array
     * @return  void
     */
    public function _assign_to_config($items = array()): void
    {
        if (is_array($items)) {
            foreach ($items as $key => $val) {
                $this->set_item($key, $val);
            }
        }
    }

	/**
	 * Check if a config item exists
	 *
	 * @param	string	$item	Config item name
	 * @return	bool
	 */
	public function has_item($item): bool
	{
		return isset($this->config[$item]);
	}

	/**
	 * Remove a config item
	 *
	 * @param	string	$item	Config item name
	 * @return	void
	 */
	public function remove_item($item): void
	{
		unset($this->config[$item]);
	}

	/**
	 * Get all config items
	 *
	 * @return	array
	 */
	public function get_all(): array
	{
		return $this->config;
	}

	private function _load($file = '', $use_sections = FALSE, $fail_gracefully = FALSE): bool
	{
		$file = ($file === '') ? 'config' : str_replace('.php', '', $file);
		
		// Security: Prevent directory traversal
		if (strpos($file, '..') !== false || preg_match('/\.\.(\/|\\\\)/', $file)) {
			if ($fail_gracefully === TRUE) {
				return FALSE;
			}
			show_error('Invalid configuration file name: Directory traversal detected.');
		}
		
		// Check if already loaded (using normalized file name)
		if (in_array($file, $this->is_loaded, TRUE)) {
			return TRUE;
		}

		$loaded = FALSE;
	
		foreach ($this->_config_paths as $path)
		{
			foreach (array($file, ENVIRONMENT.DIRECTORY_SEPARATOR.$file) as $location)
			{
				$file_path = $path.'config/'.$location.'.php';
				
				if (in_array($file_path, $this->is_loaded, TRUE))
				{
					return TRUE;
				}
	
				if ( ! file_exists($file_path))
				{
					continue;
				}
	
				// Inisialisasi $config sebagai array kosong
				$config = array();
				
				// Include file with error handling
				try {
					// First try to get the return value
					$config = include $file_path;
					
					// If include returns 1 (successful include but no return), try old method
					if ($config === 1) {
						// Old style config file (uses $config array)
						$config = array();
						include $file_path;
					}
					
					// Ensure $config is an array
					if (!is_array($config)) {
						$config = array();
					}
					
				} catch (\ParseError $e) {
					if ($fail_gracefully === TRUE) {
						log_message('error', 'Parse error in config file: ' . $file_path . ' - ' . $e->getMessage());
						continue;
					}
					show_error('Syntax error in configuration file ' . $file_path . ': ' . $e->getMessage());
				} catch (\Throwable $e) {
					if ($fail_gracefully === TRUE) {
						log_message('error', 'Error loading config file: ' . $file_path . ' - ' . $e->getMessage());
						continue;
					}
					show_error('Error loading configuration file ' . $file_path . ': ' . $e->getMessage());
				}
	
				if (empty($config) && $fail_gracefully === TRUE)
				{
					return FALSE;
				}
				elseif (empty($config))
				{
					show_error('Your '.$file_path.' file does not appear to contain a valid configuration array.');
				}
	
				if ($use_sections === TRUE)
				{
					$this->config[$file] = isset($this->config[$file])
						? array_merge($this->config[$file], $config)
						: $config;
				}
				else
				{
					$this->config = array_merge($this->config, $config);
				}
	
				$this->is_loaded[] = $file_path;
				$loaded = TRUE;
				log_message('debug', 'Config file loaded: '.$file_path);
				
				// Break inner loop if file found
				break 2;
			}
		}
	
		if ($loaded === TRUE)
		{
			return TRUE;
		}
		elseif ($fail_gracefully === TRUE)
		{
			return FALSE;
		}
	
		show_error('The configuration file '.$file.'.php does not exist.');
	}

	// ArrayAccess implementation
	public function offsetExists($offset): bool
	{
		return $this->has_item($offset);
	}
	
	public function offsetGet($offset): mixed
	{
		return $this->item($offset);
	}
	
	public function offsetSet($offset, $value): void
	{
		$this->set_item($offset, $value);
	}
	
	public function offsetUnset($offset): void
	{
		$this->remove_item($offset);
	}

	// Magic methods for convenient access
	public function __get($name): mixed
	{
		return $this->item($name);
	}
	
	public function __set($name, $value): void
	{
		$this->set_item($name, $value);
	}
	
	public function __isset($name): bool
	{
		return $this->has_item($name);
	}
	
	public function __unset($name): void
	{
		$this->remove_item($name);
	}
}