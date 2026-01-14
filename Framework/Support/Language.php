<?php namespace Kodhe\Framework\Support;

use Kodhe\Framework\Support\Modules;

class Language 
{

	/**
	 * List of translations
	 *
	 * @var	array
	 */
	public $language =	array();

	/**
	 * List of loaded language files
	 *
	 * @var	array
	 */
	public $is_loaded =	array();

	/**
	 * Class constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		log_message('info', 'Language Class Initialized');
	}


	public function load($langfile, $lang = '', $return = FALSE, $add_suffix = TRUE, $alt_path = '', $_module = '')	
	{
		if (is_array($langfile)) 
		{
			foreach($langfile as $_lang) $this->load($_lang);
			return $this->language;
		}

		if(!app()->has('session')) {
			app('load')->library('session');
		}

		// Ambil bahasa dari session atau gunakan default dari config
		if(app()->session->userdata('language')){
			$deft_lang = app()->session->userdata('language');
		} else {
			$deft_lang = app()->config->item('language');
		}
		

		
		$idiom = ($lang == '') ? $deft_lang : $lang;
	
		if (in_array($langfile.'_lang.php', $this->is_loaded, TRUE))
			return $this->language;

		$_module OR $_module = app()->router->fetch_module();

			$path = '';
			list($module, $lang_file) = $this->detect_module($langfile.'_lang.php');
			$path_file = $this->find_module($_module);
			$langfile = $path_file. 'language/'.$idiom.'/' . $lang_file.'.php';
		//[$path, $_langfile] = Modules::find($langfile.'_lang', $_module, '/language/'.$idiom.'/');

		if ($path === FALSE) 
		{
			if ($lang = $this->_load($langfile, $lang, $return, $add_suffix, $alt_path)) return $lang;
		
		} 
		else 
		{
			if(file_exists($path)){
				//include $path;
				//if ($return) return $lang;
				//$this->language = array_merge($this->language, $lang);
				//$this->is_loaded[] = $langfile.'_lang.php';
				//unset($lang);
			}

		}
		
		return $this->language;
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
            
            
            // Check if module exists menggunakan Modules class
            if ($this->find_module($module)) {
                return array($module, $class);
            }
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


	public function line($line, $log_errors = TRUE)
	{
		$value = isset($this->language[$line]) ? $this->language[$line] : FALSE;

		// Because killer robots like unicorns!
		if ($value === FALSE && $log_errors === TRUE)
		{
			log_message('error', 'Could not find the language line "'.$line.'"');
		}

		return $value;
	}

	protected function _load($langfile, $idiom = '', $return = FALSE, $add_suffix = TRUE, $alt_path = '')
	{
		if (is_array($langfile))
		{
			foreach ($langfile as $value)
			{
				$this->load($value, $idiom, $return, $add_suffix, $alt_path);
			}

			return;
		}

		$langfile = str_replace('.php', '', $langfile);

		if ($add_suffix === TRUE)
		{
			$langfile = preg_replace('/_lang$/', '', $langfile).'_lang';
		}

		$langfile .= '.php';

		if (empty($idiom) OR ! preg_match('/^[a-z_-]+$/i', $idiom))
		{
			$config =& get_config();
			$idiom = empty($config['language']) ? 'english' : $config['language'];
		}

		if ($return === FALSE && isset($this->is_loaded[$langfile]) && $this->is_loaded[$langfile] === $idiom)
		{
			return;
		}

		// Load the base file, so any others found can override it
		$basepath = BASEPATH.'/Resources/language/'.$idiom.'/'.$langfile;
		if (($found = file_exists($basepath)) === TRUE)
		{
			include($basepath);
		}

		// Do we have an alternative path to look in?
		if ($alt_path !== '')
		{
			$alt_path .= 'language/'.$idiom.'/'.$langfile;
			if (file_exists($alt_path))
			{
				include($alt_path);
				$found = TRUE;
			}
		}
		else
		{
			foreach (get_instance()->load->get_package_paths(TRUE) as $package_path)
			{
				$package_path .= 'language/'.$idiom.'/'.$langfile;
				if ($basepath !== $package_path && file_exists($package_path))
				{
					include($package_path);
					$found = TRUE;
					break;
				}
			}
		}

		if ($found !== TRUE)
		{
			show_error('Unable to load the requested language file: language/'.$idiom.'/'.$langfile);
		}

		if ( ! isset($lang) OR ! is_array($lang))
		{
			log_message('error', 'Language file contains no data: language/'.$idiom.'/'.$langfile);

			if ($return === TRUE)
			{
				return array();
			}
			return;
		}

		if ($return === TRUE)
		{
			return $lang;
		}

		$this->is_loaded[$langfile] = $idiom;
		$this->language = array_merge($this->language, $lang);

		log_message('info', 'Language file loaded: language/'.$idiom.'/'.$langfile);
		return TRUE;
	}


}