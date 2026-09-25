<?php

declare(strict_types=1);

if ( ! defined('BASEPATH') && ! defined('STDIN')) exit('No direct script access allowed');

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
