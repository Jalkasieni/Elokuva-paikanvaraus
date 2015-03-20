<?php
/**
*
* @package apexnet
* @version $Id: KernelBase.php 1130 2015-03-19 06:37:14Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\Foundation\Kernel;

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Foundation\Autoloader;
use ApexNet\Foundation\CoreException;
use ApexNet\Foundation\Config;
use ApexNet\Foundation\Kernel\KernelRequest;

use ApexNet\Database\DBConnection;

use ApexNet\Web\Session\SessionStoreMySQL;
use ApexNet\Web\Auth\BasicAuthModel;

use Exception;
use FilesystemIterator;

/**
 * Abstract base class for kernel implementations
 */
abstract class KernelBase
{
	// currently active application, set in run()
	protected $app_name;

	// the database connection handle
	protected $database;

	public function __construct()
	{
		// set error handlers
		set_error_handler(array($this, 'errorHandler'));
		set_exception_handler(array($this, 'exceptionHandler'));
	}

	abstract public function run(KernelRequest $request);

	/**
	 * PHP Error handlers
	 */
	abstract public function errorHandler($errno, $message, $file, $line);
	abstract public function exceptionHandler(Exception $exception);

	/**
	 * Create database tables (install helper)
	 */
	public function createSchemas($app = false)
	{
		if (!$app)
		{
			// Common database tables
			if ($this->database->info('name') == 'MySQL')
				SessionStoreMySQL::createSchema($this->database);

			BasicAuthModel::create_schema($this->database);

			return true;
		}
		else
		{
			// Tables for specific application
			$install = Config::load('install', $app);
			if (!empty($install))
			{
				Autoloader::addApplication($app);

				foreach ($install['models'] as $model)
				{
					$class = "{$app}_{$model}_model";
					$class::create_schema($this->database);
				}
				return true;
			}
		}

		return false;
	}

	/**
	 * Must be called before run() is invoked
	 */
	public function create($app_name, $use_database = true)
	{
		$this->app_name = $app_name;

		// load application specific constants
		Config::load('constants', $app_name);

		// default application state constants, if not defined above
		if (!defined('APEXNET_DEFAULT_CONTROLLER'))
			define('APEXNET_DEFAULT_CONTROLLER', 'default');
		if (!defined('APEXNET_DEFAULT_ACTION'))
			define('APEXNET_DEFAULT_ACTION', 'index');

		// application path constants (will resolve into paths relative to base paths)
		define('APEXNET_APP_PATH', Config::path('root', $app_name));
		define('APEXNET_APP_CACHE', Config::path('cache', $app_name));
		define('APEXNET_APP_CONFIG', Config::path('config', $app_name));
		define('APEXNET_APP_CONTROLLERS', Config::path('controller', $app_name));
		define('APEXNET_APP_MODELS', Config::path('model', $app_name));
		define('APEXNET_APP_VIEWS', Config::path('view', $app_name));
		define('APEXNET_APP_TEMPLATES', Config::path('template', $app_name));
		define('APEXNET_APP_SCRIPTS', Config::path('script', $app_name));

		// Add the application for auto loading
		Autoloader::addApplication($app_name);

		// Config::load depends on Kernel::getActiveApp(), so this can't be in the constructor or it is recursion city
		if (!isset($this->database))
			$this->database = DBConnection::create(Config::load('db', $app_name), $use_database);
	}

	/**
	 * Get a name of the currently active application
	 */
	public function getActiveApp()
	{
		if (empty($this->app_name))
			throw new CoreException('Kernel not correctly initialized, call Kernel::init(...)');

		return $this->app_name;
	}

	/**
	 * Get a list of existing applications
	 */
	public function getAllApplications()
	{
		static $apps = array();
		if (empty($apps))
		{
			foreach (new FilesystemIterator(APEXNET_APPS_ROOT, FilesystemIterator::SKIP_DOTS | FilesystemIterator::NEW_CURRENT_AND_KEY) as $name => $fi)
			{
				if ($name[0] == '.' || !$fi->isDir())
					continue;

				$apps[] = $name;
			}
		}
	
		return $apps;
	}
}
