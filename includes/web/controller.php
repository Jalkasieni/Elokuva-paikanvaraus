<?php
/**
*
* @package apexnet
* @version $Id: controller.php 981 2014-12-15 23:13:12Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Foundation\Kernel;

use ApexNet\Web\AccessControl;
use ApexNet\Web\Auth\BasicAuth;

use ApexNet\Database\DBConnection;

abstract class web_controller
{
	// Controller delegates this to models through web_controller::model()
	private $database;

	// User authentication information, if any
	protected $user;

	// Access control helper
	protected $acl;

	public static function routes($current = null)
	{
		$routes = array();
		$root = APEXNET_APP_CONTROLLERS;

		if (is_null($current))
			$current = $root;

		foreach (new FilesystemIterator($current, FilesystemIterator::SKIP_DOTS | FilesystemIterator::NEW_CURRENT_AND_KEY) as $name => $fi)
		{
			$name = $fi->getFileName();
			if ($name[0] == '.' || !$fi->isReadable())
				continue;

			if ($fi->isDir())
			{
				$routes = array_merge($routes, self::routes($fi->getRealPath()));
			}
			else
			{
				$route = str_replace(array($root, '.' . PHP_EXT), '', $fi->getRealPath());
				$route = '/' . str_replace('\\', '/', $route) . '(/<action>(.<type>))';

				$routes[$route] = array(
					'defaults' => array(
						'controller'	=> str_replace('.' .PHP_EXT, '', $name),
						'action'		=> APEXNET_DEFAULT_ACTION,
						'type'			=> ''
					)
				);
			}
		}

		return $routes;
	}

	/**
	 * Check existence of a controller
	 */
	public static function check($controller)
	{
		$controller = strtr($controller, '_\\', '//');
		return is_file(APEXNET_APP_CONTROLLERS . "$controller." . PHP_EXT);
	}

	/**
	 * Factory for controllers
	 */
	public static function create($controller, BasicAuth $user, DBConnection $db)
	{
		$class = Kernel::getActiveApp() . "_{$controller}_controller";
		return new $class($user, $db, new AccessControl($user));
	}

	protected function __construct(BasicAuth $user, DBConnection $db, AccessControl $acl)
	{
		$this->database = $db;
		$this->user = $user;
		$this->acl = $acl;
	}

	public function run(web_request $request, $action = APEXNET_DEFAULT_ACTION)
	{
		$callback = false;
		if ($request->ajax() && is_callable(array($this, "ajax_$action")))
		{
			// this request is valid as ajax and only ajax
			$callback = "ajax_$action";
		}
		else if (is_callable(array($this, "do_$action")))
		{
			// this request may or may not be ajax, but is valid regardless
			$callback = "do_$action";
		}
		else
		{
			// no suitable callback function, invalid request
			return web_response::error($request, 404);
		}

		if (is_callable(array($this, 'prepare')))
		{
			$response = $this->prepare($request);
			if ($response instanceof web_response)
				return $response;
		}

		if ($this->acl->valid())
		{
			$response = $this->acl->check($request);
			if ($response instanceof web_response)
				return $response;
		}

		return $this->$callback($request);
	}

	/**
	 * Load a model
	 */
	final public function model($model)
	{
		if (!web_model::check($model))
			throw new Exception("Model '$model' loaded but not defined.");
	
		return web_model::create($model, $this->database);
	}

	/**
	 * Delegate a request to another controller
	 */
	final public function delegate($controller, web_request $request, $action = APEXNET_DEFAULT_ACTION)
	{
		if (!web_controller::check($controller))
			throw new Exception("Delegating requests to missing controller '$controller'.");

		return web_controller::create($controller, $this->user, $this->database)->run($request, $action);
	}
}
