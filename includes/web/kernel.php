<?php
/**
*
* @package apexnet
* @version $Id: kernel.php 1183 2015-03-20 18:51:03Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Foundation\ErrorReporting;
use ApexNet\Foundation\Kernel\KernelBase;
use ApexNet\Foundation\Kernel\KernelRequest;
use ApexNet\Foundation\Config;

use ApexNet\BBCode\BBCParser;

use ApexNet\Web\Auth\BasicAuth;
use ApexNet\Web\Auth\BasicAuthController;
use ApexNet\Web\AccessControl;

/**
 * Kernel implementation for web frontends (HTTP Response)
 */
class web_kernel extends KernelBase
{
	public function run(KernelRequest $request)
	{
		$controller = APEXNET_DEFAULT_CONTROLLER;
		$action = APEXNET_DEFAULT_ACTION;

		// We really want a database
		if (!$this->database)
			throw new Exception('Web kernel always requires a database connection.');

		// Initialize session
		$user = BasicAuth::create(Config::load('session'), $this->database);

		// Initialize the BBCode parser
		BBCParser::setUrlCallback(function (&$url) use ($request) {
			$base_url = $request->base_url();
			if ($url[0] == '/')
				$url = $base_url . $url;

			if (strpos($url, $base_url) !== false)
				$url = $request->append_sid($url);

			return true;
		});

		BBCParser::loadDefaultCodes();

		if (!$request->resolve($controller, $action, Config::load('routes')))
			return web_response::error($request, 404)->send();

		// check for controller
		if (!web_controller::check($controller))
		{
			if ($controller !== 'auth')
				return web_response::error($request, 404)->send();

			$controller = new BasicAuthController($user, $this->database, new AccessControl($user));
		}
		else
		{
			$controller = web_controller::create($controller, $user, $this->database);
		}

		$response = $controller->run($request, $action);

		if (!$response instanceof web_response)
			throw new Exception('Not all control paths return a response (Missing return statement in controller?).');

		return $response->send();
	}

	/**
	 * PHP Error handler
	 */
	public function errorHandler($errno, $message, $file, $line)
	{
		// Guard against error loop
		if (defined('APEXNET_FATAL_ERROR'))
			return false;

		// Do not display notices if we suppress them via @
		if (error_reporting() == 0 && $errno != E_USER_ERROR && $errno != E_USER_WARNING && $errno != E_USER_NOTICE)
			return true;

		// Check the error reporting level and return if the error level does not match
		if (($errno & ((defined('DEBUG')) ? (E_ALL | E_STRICT) : error_reporting())) == 0)
			return true;

		switch ($errno)
		{
			case E_NOTICE:
			case E_WARNING:
			case E_USER_NOTICE:
			case E_USER_WARNING:
			case E_DEPRECATED:
			case E_STRICT:
				print(ErrorReporting::formatError($errno, $message, $file, $line, true));
				return true;
			break;
		}

		throw new ErrorException($message, $errno, 0, $file, $line);
	}

	/**
	 * PHP Exception handler
	 */
	public function exceptionHandler(Exception $exception)
	{
		// Let's prevent error loops
		define('APEXNET_FATAL_ERROR', true);

		// Pretty errors, although we'd rather there be none...
		$error_format = ErrorReporting::formatException($exception, true);

		try
		{
			// Send as '503 - Service Unavailable'
			$response = web_response::error(web_request::create(), 503, $error_format);
			$response->send();
		}
		catch (Exception $e)
		{
			$original_error = "Original Error: $error_format";
			$error_format = sprintf('%s thrown within the exception handler. Message: %s on line %d. <br />', get_class($e), $e->getMessage(), $e->getLine()) . PHP_EOL;
			$error_format .= "<br /> $original_error";

			exit($error_format);
		}
	}
}
