<?php

declare(strict_types=1);

if ( ! defined('BASEPATH') && ! defined('STDIN') && ! function_exists('is_cli')) exit('No direct script access allowed');

if ( ! function_exists('app_config_folder'))
{
	/**
	 * Resolve the real name of the application config folder.
	 *
	 * CodeIgniter 3 ships with a lowercase `application/config/` folder,
	 * but projects may rename it to `Config` (Kodhe style). On
	 * case-sensitive filesystems (Linux) a hardcoded lowercase lookup
	 * silently fails and config files appear to be "missing".
	 *
	 * The actual on-disk name is detected once via scandir() and cached.
	 * Falls back to 'config' when the folder does not exist at all so
	 * behavior stays identical to plain CI3 projects.
	 *
	 * @return string Folder name relative to APPPATH (no slashes)
	 */
	function app_config_folder(): string
	{
		static $folder = null;

		if ($folder !== null)
		{
			return $folder;
		}

		if (defined('APPPATH') && is_dir(APPPATH))
		{
			$entries = (is_dir(APPPATH) ? scandir(APPPATH) : array()) ?: array();

			foreach ($entries as $entry)
			{
				if (strcasecmp($entry, 'config') === 0 && is_dir(APPPATH.$entry))
				{
					$folder = $entry;
					return $folder;
				}
			}
		}

		$folder = 'config';
		return $folder;
	}
}

if ( ! function_exists('app_folder'))
{
/**
 * Resolve the real on-disk name of a first-level application folder
 * under APPPATH, case-insensitively (e.g. 'controllers' -> 'Controllers').
 *
 * Falls back to the requested lowercase spelling when nothing matching
 * exists on disk, so plain CI3 projects are unaffected.
 *
 * @paramstring$name Folder name without slashes
 * @returnstring Actual folder name as stored on disk
 */
function app_folder(string $name): string
{
static $cache = array();

$key = strtolower($name);

if (isset($cache[$key]))
{
return $cache[$key];
}

$found = $name;

if (defined('APPPATH') && is_dir(APPPATH))
{
foreach (((is_dir(APPPATH) ? scandir(APPPATH) : array()) ?: array()) as $entry)
{
if ($entry !== '.' && $entry !== '..'
&& strcasecmp($entry, $name) === 0
&& is_dir(APPPATH.$entry))
{
$found = $entry;

if ($entry === $name)
{
break; // exact win
}
}
}
}

$cache[$key] = $found;
return $found;
}
}

if ( ! function_exists('app_folder_in'))
{
        /**
         * Resolve a sub-folder name inside an arbitrary base path, keeping
         * the trailing slash. Returns "$base$name/" using the real on-disk
         * spelling when it exists, otherwise falls back to "$base$name/".
         *
         * @param       string  $base   Base directory (with or without trailing slash)
         * @param       string  $name   Sub-folder name to look up case-insensitively
         * @return      string  Resolved absolute folder path WITH trailing slash
         */
        function app_folder_in(string $base, string $name): string
        {
                $base = rtrim($base, '/\\').DIRECTORY_SEPARATOR;

                static $cache = array();
                $key = $base.'|'.$name;
                if (isset($cache[$key]))
                {
                        return $cache[$key];
                }

                $found = $name;
                if (is_dir($base))
                {
                        foreach ((scandir($base) ?: array()) as $entry)
                        {
                                if ($entry !== '.' && $entry !== '..'
                                        && strcasecmp($entry, $name) === 0
                                        && is_dir($base.$entry))
                                {
                                        $found = $entry;
                                        if ($entry === $name)
                                        {
                                                break;
                                        }
                                }
                        }
                }

                return $cache[$key] = $base.$found.DIRECTORY_SEPARATOR;
        }
}

if ( ! function_exists('app_file_in'))
{
        /**
         * Resolve a file path inside APPPATH case-insensitively, per segment.
         *
         * Lets legacy CI3 calls keep working when project folders are renamed
         * to PascalCase (Models/, Helpers/, Views/, Libraries/, errors/ ...),
         * e.g. app_file_in(APPPATH.'models/welcome_model.php') will find
         * app/Models/Welcome_model.php on Linux.
         *
         * Falls back to the original path when nothing matches so plain
         * lowercase CI3 projects are completely unaffected.
         *
         * @param       string  $relative_path  Path relative to APPPATH (may include subfolders)
         * @return      string  Absolute resolved path (existing file preferred, else original)
         */
        function app_file_in(string $relative_path): string
        {
                if ( ! defined('APPPATH'))
                {
                        return $relative_path;
                }

                static $cache = array();
                if (isset($cache[$relative_path]))
                {
                        return $cache[$relative_path];
                }

                $result = app_path_in(rtrim(APPPATH, '/\\'), $relative_path);

                return $cache[$relative_path] = $result;
        }
}

if ( ! function_exists('app_realpath'))
{
        /**
         * Case-insensitive resolution of an ABSOLUTE path (any base folder).
         *
         * Walks each directory segment of the given absolute path and swaps
         * it for the real on-disk spelling when a case-insensitive sibling
         * exists. Works outside APPPATH too (e.g. VIEWPATH pointing at a
         * renamed "Views" folder, or vendor paths). Returns the original
         * path untouched when every segment already resolves exactly.
         *
         * @param       string  $absolute_path
         * @return      string
         */
        function app_realpath(string $absolute_path): string
        {
                if ($absolute_path === '' || file_exists($absolute_path))
                {
                        return $absolute_path;
                }

                static $cache = array();
                if (isset($cache[$absolute_path]))
                {
                        return $cache[$absolute_path];
                }

                $normalized = str_replace('\\', '/', $absolute_path);
                $is_dir_suffix = (substr($normalized, -1) === '/');
                $segments = array_values(array_filter(explode('/', $normalized), function ($s) { return $s !== ''; }));

                // Drive letter (Windows) or leading slash (POSIX)
                if (preg_match('#^[A-Za-z]:#', $segments[0] ?? '', $m))
                {
                        $current = $segments[0].'/';
                        array_shift($segments);
                }
                elseif (substr($normalized, 0, 1) === '/')
                {
                        $current = '/';
                }
                else
                {
                        return $cache[$absolute_path] = $absolute_path; // relative: not our job
                }

                $count = count($segments);
                foreach ($segments as $i => $segment)
                {
                        $candidate = rtrim($current, '/').'/'.$segment;

                        if (file_exists($candidate) || ! is_dir($current))
                        {
                                $current = $candidate;
                                continue;
                        }

                        // try case-insensitive match among siblings of $current
                        $entries = scandir($current) ?: array();
                        $found = null;
                        foreach ($entries as $entry)
                        {
                                if ($entry !== '.' && $entry !== '..' && strcasecmp($entry, $segment) === 0)
                                {
                                        $found = $entry;
                                        if ($entry === $segment)
                                        {
                                                break;
                                        }
                                }
                        }

                        $current = rtrim($current, '/').'/'.($found !== null ? $found : $segment);
                }

                if ($is_dir_suffix && substr($current, -1) !== '/')
                {
                        $current .= '/';
                }

                return $cache[$absolute_path] = $current;
        }
}

if ( ! function_exists('app_view_file'))
{
        /**
         * Locate a view/template file under the (possibly renamed) views
         * folder. Accepts names with or without the .php extension and
         * subfolders like 'errors/html/error_php'.
         *
         * @param       string  $relative_path  View path relative to the views folder
         * @return      string|null  Resolved existing file or NULL
         */
        function app_view_file(string $relative_path): ?string
        {
                if ( ! defined('APPPATH'))
                {
                        return null;
                }

                $base = rtrim(APPPATH, '/\\').DIRECTORY_SEPARATOR.app_folder('views').DIRECTORY_SEPARATOR;
                $candidates = array($relative_path);
                if (strtolower(substr($relative_path, -4)) !== '.php')
                {
                        $candidates[] = $relative_path.'.php';
                }

                foreach ($candidates as $c)
                {
                        $resolved = app_path_in($base, $c);
                        if (is_file($resolved))
                        {
                                return $resolved;
                        }
                }

                return null;
        }
}

if ( ! function_exists('app_config_file'))
{
	/**
	 * Build a full path to a file inside the (case-insensitively resolved)
	 * application config folder.
	 *
	 * When the exact-cased path does not exist, we attempt a case-insensitive
	 * match against the real folder listing, so references like
	 * app_config_file('routes.php') keep working whether the project uses
	 * config/, Config/, CONFIG/ ... and even for env subfolders
	 * (e.g. app_config_file(ENVIRONMENT.'/routes.php')).
	 *
	 * @param	string	$relative_path	Path relative to the config folder
	 * @return	string
	 */
	function app_config_file(string $relative_path): string
	{
		$base = APPPATH.app_config_folder().'/';

		if ($relative_path === '' || file_exists($base.$relative_path))
		{
			return $relative_path === '' ? rtrim($base, '/ ') : $base.$relative_path;
		}

		// Case-insensitive fallback: walk each segment through the real dir listing.
		$path = rtrim($base, '/');

		foreach (explode('/', str_replace('\\', '/', $relative_path)) as $segment)
		{
			if ($segment === '' || $segment === '.')
			{
				continue;
			}

			$candidate = $path.'/'.$segment;

			if (file_exists($candidate))
			{
				$path = $candidate;
				continue;
			}

			$matched = null;
			foreach (((is_dir($path) ? scandir($path) : array()) ?: array()) as $entry)
			{
				if ($entry === '.' OR $entry === '..')
				{
					continue;
				}

				if (strcasecmp($entry, $segment) === 0)
				{
					$matched = $entry;

					if ($entry === $segment)
					{
						break; // exact win
					}
				}
			}

			if ($matched === null)
			{
				// Nothing on disk: return the naive path so callers'
				// file_exists()/is_file() checks fail gracefully as before.
				return $base.$relative_path;
			}

			$path .= '/'.$matched;
		}

		return $path;
	}
}

if ( ! function_exists('app_config_file_in'))
{
/**
 * Resolve a config file path inside an arbitrary base path.
 *
 * Works like app_config_file() but accepts any search path (APPPATH,
 * package paths, module paths ...). The literal lowercase 'config/'
 * form is returned when the caller's path already ends with it, or
 * when nothing case-insensitively matching exists on disk.
 *
 * @paramstring$base_pathBase directory (e.g. APPPATH)
 * @paramstring$relative_pathFile relative to the config folder
 * @returnstring
 */
function app_config_file_in(string $base_path, string $relative_path): string
{
$base = rtrim(str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, $base_path), DIRECTORY_SEPARATOR);

// Caller already embedded a (lowercase) config/ segment in the base path
if (strcasecmp(basename($base), 'config') === 0)
{
return $base.DIRECTORY_SEPARATOR.ltrim(str_replace('/', DIRECTORY_SEPARATOR, $relative_path), DIRECTORY_SEPARATOR);
}

$folder = null;
foreach ((is_dir($base) ? scandir($base) : array()) ?: array() as $entry)
{
if (strcasecmp($entry, 'config') === 0 && is_dir($base.DIRECTORY_SEPARATOR.$entry))
{
$folder = $entry;
break;
}
}

if ($folder === null)
{
$folder = 'config'; // graceful default: caller's file_exists() will fail as before
}

$candidate = $base.DIRECTORY_SEPARATOR.$folder.DIRECTORY_SEPARATOR.ltrim(str_replace('/', DIRECTORY_SEPARATOR, $relative_path), DIRECTORY_SEPARATOR);

if (file_exists($candidate) OR strcasecmp($folder, 'config') === 0)
{
return $candidate;
}

// Fallback to the legacy lowercase spelling if it happens to exist
$legacy = $base.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.ltrim(str_replace('/', DIRECTORY_SEPARATOR, $relative_path), DIRECTORY_SEPARATOR);

return file_exists($legacy) ? $legacy : $candidate;
}
}

if ( ! function_exists('app_path_in'))
{
	/**
	 * Case-insensitive path resolver for arbitrary application paths.
	 *
	 * CodeIgniter 3 projects use lowercase folder names (controllers/,
	 * models/, libraries/, ...) but many Kodhe-style projects rename them
	 * to PascalCase (Controllers/, Models/, ...). On case-sensitive
	 * filesystems a hardcoded lowercase lookup silently fails, so the
	 * legacy loader/router can no longer find files or folders.
	 *
	 * This helper walks every segment of the requested relative path
	 * through the real on-disk directory listing:
	 *   - exact-case match always wins (zero overhead for plain CI3 apps),
	 *   - otherwise the first case-insensitive match is used,
	 *   - when nothing matches, the naive lowercased path is returned so
	 *     callers' file_exists()/is_dir() checks fail gracefully as before.
	 *
	 * Directories are cached per base path to keep repeated lookups cheap.
	 *
	 * @param	string	$base_path		 Base directory (e.g. APPPATH.'controllers')
	 * @param	string	$relative_path Path relative to the base directory
	 *                                     (file name, sub-folder, or nested path)
	 * @return	string Absolute resolved path (may not exist on disk)
	 */
	function app_path_in(string $base_path, string $relative_path): string
	{
		$base = rtrim(str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, $base_path), DIRECTORY_SEPARATOR);
		$relative_path = trim((string) $relative_path, '/\\');

		if ($relative_path === '')
		{
			return $base;
		}

		static $dir_cache = array();

		$list_dir = static function (string $dir) use (&$dir_cache)
		{
			if ( ! isset($dir_cache[$dir]))
			{
				$entries = (is_dir($dir) ? scandir($dir) : array()) ?: array();
				$map = array();

				foreach ($entries as $entry)
				{
					if ($entry !== '.' && $entry !== '..')
					{
						// Later entries never override an existing key; exact
						// and near-exact spellings remain reachable via the
						// fast-path check below anyway.
						$map[strtolower($entry)] = isset($map[strtolower($entry)]) ? $map[strtolower($entry)] : $entry;
					}
				}

				$dir_cache[$dir] = $map;
			}

			return $dir_cache[$dir];
		};

		$path = $base;

		foreach (explode('/', str_replace('\\', '/', $relative_path)) as $segment)
		{
			if ($segment === '' || $segment === '.')
			{
				continue;
			}

			$candidate = $path.DIRECTORY_SEPARATOR.$segment;

			if (file_exists($candidate))
			{
				$path = $candidate; // exact win, no scan needed
				continue;
			}

			$map = $list_dir($path);
			$key = strtolower($segment);

			if (isset($map[$key]))
			{
				$path .= DIRECTORY_SEPARATOR.$map[$key];
				continue;
			}

			// Nothing on disk: return the naive path so callers behave exactly
			// like plain CI3 (404 / "unable to load" instead of fatal errors).
			return $base.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative_path);
		}

		return $path;
	}
}

if ( ! function_exists('app_class_file_in'))
{
	/**
	 * Locate a class file (library/model/driver) inside an arbitrary base
	 * path, trying every common naming convention used across CI3 and
	 * Kodhe-style projects, case-insensitively.
	 *
	 * Conventions probed, in order of likelihood:
	 *   Libraries/Auth.php          (Kodhe style: PascalCase, no suffix)
	 *   Libraries/AuthLib.php       (PascalCase + Lib suffix)
	 *   Libraries/Auth_lib.php      (CI3 MY_*-style lib suffix)
	 *   Libraries/auth_lib.php      (lowercase + suffix)
	 *   Libraries/auth.php          (plain lowercase)
	 *   Libraries/Pdf.php           (all-lowercase names collapse safely)
	 * plus the caller-provided spelling first when it differs.
	 *
	 * "CamelCase/Lib" variants are derived by stripping underscores and
	 * capitalising each remaining word boundary, so both 'auth_lib' and
	 * 'AuthLib' inputs find either spelling on disk.
	 *
	 * Sub-directories inside the folder are resolved case-insensitively
	 * too (e.g. 'sub/Pdf' -> 'Sub/Pdf.php').
	 *
	 * @param	string	$base_path	Base directory (e.g. APPPATH)
	 * @param	string	$folder		Folder name ('libraries', 'models', ...)
	 * @param	string	$class		Class name WITHOUT .php (may include subdir)
	 * @return	string|null	Absolute existing file path or NULL
	 */
	function app_class_file_in(string $base_path, string $folder, string $class): ?string
	{
		if ( ! function_exists('app_class_name_variants'))
		{
			// Defensive bootstrap: plain-function file not autoloaded yet.
			$__app_path = __DIR__.'/app_path.php';
			is_file($__app_path) && require_once $__app_path;
		}

		$base = rtrim(str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, $base_path), DIRECTORY_SEPARATOR);
		$class = str_replace('.php', '', trim($class, '/\\'));

		if ($class === '')
		{
			return null;
		}

		// Split optional sub-directory from the class/file name
		$subdir = '';
		if (($slash = strrpos($class, '/')) !== FALSE)
		{
			$subdir = substr($class, 0, $slash + 1);
			$name = substr($class, $slash + 1);
		}
		else
		{
			$name = $class;
		}

		$candidates = array();
		foreach (app_class_name_variants($name) as $variant)
		{
			$candidates[] = $folder.'/'.$subdir.$variant.'.php';
		}

		foreach ($candidates as $candidate)
		{
			$resolved = app_path_in($base, $candidate);
			if (is_file($resolved))
			{
				return $resolved;
			}
		}

		return null;
	}
}

if ( ! function_exists('app_class_name_variants'))
{
	/**
	 * Every plausible on-disk spelling for a class/file name, ordered by
	 * likelihood. Handles the "_lib"-suffix family AND its PascalCase
	 * equivalents (auth_lib <-> AuthLib <-> authLib), because projects may
	 * rename files to full CamelCase with capitalised words.
	 *
	 * Examples:
	 *   'auth'     -> Auth, AuthLib, auth_lib, auth
	 *   'AuthLib'  -> AuthLib, Auth, auth_lib, authlib, auth
	 *   'auth_lib' -> AuthLib, Auth, auth_lib, authlib, auth
	 *   'pdf'      -> Pdf, pdf (no bogus "Pdf_lib" probe added blindly;
	 *                  callers that need MY_*_lib pass the suffixed name)
	 *
	 * @param	string	$name File/class name WITHOUT extension
	 * @return	array	List of unique spellings (no extension)
	 */
	function app_class_name_variants(string $name): array
	{
		$name = str_replace('.php', '', trim($name));

		if ($name === '')
		{
			return array();
		}

		// Strip a trailing _lib / Lib suffix (case-insensitive) to get the stem
		$stem = $name;
		$has_lib = FALSE;
		if (preg_match('/^(.*?)[_]?lib$/i', $name, $m) && $m[1] !== '')
		{
			$stem = $m[1];
			$has_lib = TRUE;
		}

		// Camelise the stem: split on underscores/hyphens, ucfirst every word,
		// then implode. 'auth'->'Auth', 'my_auth'->'MyAuth'.
		$camel_stem = str_replace(' ', '', ucwords(str_replace(array('_', '-'), ' ', strtolower($stem))));

		$variants = array(
			$camel_stem,				 // Auth.php / MyAuth.php (Kodhe PascalCase)
			$camel_stem.'Lib',			 // AuthLib.php (PascalCase + Lib)
			strtolower($stem).'_lib',	 // auth_lib.php (CI3 legacy suffix)
			strtolower($camel_stem).'_lib', // myauth_lib style fallback
			str_replace(' ', '', ucwords(str_replace(array('_', '-'), ' ', $stem))).'_lib', // Auth_lib.php
			strtolower($stem),			 // auth.php (plain lowercase)
			$stem,					 // caller's exact spelling
			$name,					 // original input spelling
		);

		// When the input itself was PascalCase-with-Lib, also probe the
		// underscore-split form (AuthLib -> auth_lib already covered; but
		// Auth_Lib input should keep working verbatim).
		if ($has_lib)
		{
			$variants[] = $camel_stem.'_lib';
		}

		$variants = array_values(array_unique(array_filter($variants, static function ($v)
		{
			return $v !== '' && $v !== null;
		})));

		return $variants;
	}
}

if ( ! function_exists('app_controller_file'))
{
	/**
	 * Locate a controller file under APPPATH's controllers folder using a
	 * case-insensitive search over every folder/file segment, including the
	 * optional "_Controller" suffix convention.
	 *
	 * Handles combinations like:
	 *   APPPATH.'controllers/admin/Welcome_controller.php'
	 *   APPPATH.'Controllers/Admin/WelcomeController.php'
	 *   APPPATH.'Controllers/Admin/Welcome.php'
	 *
	 * @param	string	$relative Controller path relative to the controllers
	 *                              folder, WITHOUT extension (e.g. 'admin/welcome')
	 * @param	string	$suffix	 Optional controller suffix (e.g. '_Controller')
	 * @return	string Absolute path to the matched file, or the naive
	 *                  lowercase path when nothing exists on disk.
	 */
	function app_controller_file(string $relative, string $suffix = ''): string
	{
		$relative = str_replace('.php', '', trim($relative, '/\\'));
		$segments = explode('/', str_replace('\\', '/', $relative));
		$file = (string) array_pop($segments);
		$subdir = empty($segments) ? '' : implode('/', $segments).'/';

		// Candidate spellings, most-likely first (CI3 convention: ucfirst).
		$variants = array(ucfirst($file), $file, strtolower($file));

		$candidates = array();

		foreach ($variants as $v)
		{
			$candidates[] = $v;

			if ($suffix !== '')
			{
				// Try the suffix in its raw and ucfirst spelling so a
				// '_controller' config still matches WelcomeController.php.
				$candidates[] = $v.$suffix;
				$candidates[] = $v.ucfirst(ltrim($suffix, '_'));
			}
		}

		$candidates = array_values(array_unique($candidates));

		$base = defined('APPPATH') ? APPPATH.app_folder('controllers').'/' : 'controllers/';

		foreach ($candidates as $candidate)
		{
			$resolved = app_path_in(rtrim($base, '/\\'), $subdir.$candidate.'.php');

			if (is_file($resolved))
			{
				return $resolved;
			}
		}

		// Graceful default: naive lowercase path (file_exists() will fail as before).
		return $base.$subdir.strtolower($file).(($suffix !== '') ? $suffix : '').'.php';
	}
}
