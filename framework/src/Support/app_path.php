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
