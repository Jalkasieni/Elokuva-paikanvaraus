<?php
/**
*
* @package apexnet
* @version $Id: ClassLoader.php 832 2014-05-29 06:43:51Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\Foundation\Loader;

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Foundation\Cache;
use ApexNet\Foundation\Config;
use ApexNet\Foundation\CoreException;
use ApexNet\Foundation\Kernel;

class ClassLoader
{
	// Store path info
	protected $locations = array();
	protected $class_map = array();

	// Hold the registration state of the class loader
	protected $registered = false;

	/**
	 * Create the class loader, with optional static class mapping
	 */
	public function __construct(array $class_map = null)
	{
		if (!empty($class_map))
			$this->class_map = $class_map;
	}

	/**
	 * Installs this class loader on the SPL autoload stack.
	 */
	public function register($prepend = false)
	{
		spl_autoload_register(array($this, 'loadClass'), false, $prepend);
		$this->registerd = true;
	}

	/**
	 * Uninstalls this class loader from the SPL autoloader stack.
	 */
	public function unregister()
	{
		spl_autoload_unregister(array($this, 'loadClass'));
		$this->registered = false;
	}

	/**
	 * Functions reporting on the status of the class loader
	 */
	public function isCached()
	{
		return Cache::active();
	}

	public function isRegistered()
	{
		return $this->registered;
	}

	/**
	 * Set an array of static class name to file path mappings
	 */
	 public function setClassMap(array $class_map)
	 {
		$this->class_map = $class_map;
	 }

	/**
	 * Add standard application paths
	 */
	public function addApplication($app_name)
	{
		if (!Config::active())
			throw new CoreException('Unable to resolve application paths.');

		$this->addPaths(Config::path('controller', $app_name), $app_name, 'controller');
		$this->addPaths(Config::path('model', $app_name), $app_name, 'model');
		$this->addPaths(Config::path('script', $app_name), $app_name, 'script');
	}

	/**
	 * Add location for the autoloader to look through
	 */
	public function addPaths($paths, $prefix = false, $suffix = false)
	{
		$path_key = $prefix . '.' . $suffix;
		if (!$prefix && !$suffix)
		{
			foreach ((array)$paths as $path)
				$this->locations[] = $path;
		}
		else if (isset($this->locations[$prefix . '.' . $suffix]))
		{
			$this->locations[$path_key]['paths'] = array_merge(
				$this->locations[$path_key]['paths'],
				(array)$paths
			);
		}
		else
		{
			$this->locations[$path_key] = array(
				'prefix' => $prefix,
				'suffix' => $suffix,
				'paths' => $paths
			);
		}
	}

	/**
	 * Calls findFile() and loads the class file
	 */
	public function loadClass($class)
	{
		// check mapped classes
		if (isset($this->class_map[$class]) && is_file($this->class_map[$class]))
		{
			require($this->class_map[$class]);
		}
		else if (!empty($this->locations))
		{
			$file = false;
			if (Cache::active())
			{
				if (($file = Cache::get("autoloader/$class", false)) === false)
				{
					$file = $this->findFile($class);
					Cache::put("autoloader/$class", $file);
				}
			} else $file = $this->findFile($class);

			if ($file)
				require($file);
		}
	}

	/**
	 * Find the expected class name
	 */
	protected function findFile($class_name)
	{
		// prepare the set of lookup paths
		$lookup_paths = array();
		$class_file =  false;

		foreach ($this->locations as $location)
		{
			if (is_array($location))
			{
				$prefix = $location['prefix'];
				$suffix = $location['suffix'];

				if ($prefix)
				{
					if (strpos($class_name, $prefix) !== 0)
						continue;

					$name_clean = substr($class_name, strlen($prefix) + 1);

					if ($suffix)
					{
						$suffix_len = strlen($suffix);
						if (strpos($class_name, $suffix, strlen($class_name) - $suffix_len) === false)
							continue;

						$name_clean = substr($name_clean, 0, strlen($name_clean) - ($suffix_len + 1));
					}

					if (!$class_file || strlen($name_clean) < strlen($class_file))
						$class_file = strtr($name_clean, '_\\', '//');
				}

				// make sure the more specific paths have precedence
				$lookup_paths = array_merge((array)$location['paths'], $lookup_paths);
			}
			else
			{
				$lookup_paths[] = $location;
			}
		}

		// we don't have a file name yet, class name did not match anything special
		if (!$class_file)
			$class_file = strtr($class_name, '_\\', '//');

		// look through the paths that match the initial class name
		foreach ($lookup_paths as $dir)
		{
			$class_path = $dir . $class_file . '.' . PHP_EXT;
			if (is_file($class_path))
				return $class_path;
		}

		// ... still here? give up.
		return false;
	}
}
