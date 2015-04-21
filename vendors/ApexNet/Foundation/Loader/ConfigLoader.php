<?php
/**
*
* @package apexnet
* @copyright (c) 2015 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\Foundation\Loader;

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Foundation\CoreException;
use ApexNet\Foundation\Kernel;

/**
 * Class for loading config files
 */
class ConfigLoader
{
	// holds templates for runtime application paths
	protected $paths;

	public function __construct()
	{
		// php has poor support for complex initializer expressions (even 5.6 can't handle arrays)
		$this->paths = array(
			'cache'			=> APEXNET_CACHE_PATH . '<app_name>/',
			'config'		=> APEXNET_APPS_ROOT . '<app_name>/config/',
			'controller'	=> APEXNET_APPS_ROOT . '<app_name>/controllers/',
			'model'			=> APEXNET_APPS_ROOT . '<app_name>/models/',
			'root'			=> APEXNET_APPS_ROOT . '<app_name>/',
			'script'		=> APEXNET_APPS_ROOT . '<app_name>/scripts/',
			'view'			=> APEXNET_APPS_ROOT . '<app_name>/views/',
			'template'		=> APEXNET_APPS_ROOT . '<app_name>/templates/'
		);
	}

	/**
	 * Retrieves an application specific path at runtime
	 */
	public function path($area, $app_name = false)
	{
		if (!isset($this->paths[$area]))
			throw new CoreException("Could not resolve path for '$area'.");

		if (!$app_name)
			$app_name = Kernel::getActiveApp();

		return str_replace('<app_name>', $app_name, $this->paths[$area]);
	}

	/**
	 * Load config file(s)
	 */
	public function load($area = null, $app_name = false)
	{
		if (!$app_name)
			$app_name = Kernel::getActiveApp();

		if (is_string($area))
			return $this->loadFile($area, $app_name);

		if (is_array($area))
		{
			$config = array();
			foreach ($area as $key)
				$config[$key] = $this->loadFile($key, $app_name);
			return $config;
		}

		throw new CoreException('Invalid argument for config::load().');
	}

	protected function loadFile($area, $app_name)
	{
		// this function makes use of the ability to use the return statement inside an included file
		// and validates the file based on it (include returns 1 on files without return statement).
		$config_var = null;

		$config_file = $this->path('config', $app_name) . "$area." . PHP_EXT;
		if (is_file($config_file))
			$config_var = require($config_file);

		$config_file = APEXNET_CONFIG_PATH . "$area." . PHP_EXT;
		if ($config_var == null && is_file($config_file))
			$config_var = require($config_file);

		// throw on an unexpected value
		if ($config_var == null || (!is_array($config_var) && !is_bool($config_var)))
			throw new CoreException("Unable to load config file '$area' for application '$app_name'.");

		return $config_var;
	}
}
