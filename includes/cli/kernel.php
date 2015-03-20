<?php
/**
*
* @package cli
* @version $Id: script.php 802 2014-05-27 03:51:43Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Foundation\Util;
use ApexNet\Foundation\ErrorReporting;
use ApexNet\Foundation\CoreException;
use ApexNet\Foundation\Kernel\KernelBase;
use ApexNet\Foundation\Kernel\KernelRequest;

/**
 * Kernel implementation for the CLI frontend (maintenance scripts)
 */
class cli_kernel extends KernelBase
{
	// Holds the instance of the script class
	protected $script;

	public function create($app_name, $use_database = true, cli_script $script = null)
	{
		// PHP's lack of proper function overloading is fun (default argument is needed to avoid E_STRICT warning)
		if ($script == null)
			throw new CoreException('CLI Kernel requires a script instance to operate on.');

		$this->script = $script;

		parent::create($app_name, $use_database);
	}

	/**
	 * Make sure default timezone is set, throw if not actually running in CLI
	 */
	public function __construct()
	{
		// Not running in CLI throw before we touch error handlers
		if (!Util::isCLI())
			throw new CoreException('CLI Kernel invoked outside of CLI environment');

		parent::__construct();

		// set default timezone (php-cli doesn't have this set)
		ini_set('date.timezone', 'UTC');
	}

	public function run(KernelRequest $request)
	{
		return $this->script->main($request, $this->database);
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
		$error_format = ErrorReporting::formatException($exception, false);

		try
		{
			// If we have no script, we have to do it manually...
			if (!isset($this->script))
			{
				fputs(STDERR, $error_format . PHP_EOL);
				exit(1);
			}

			// fatal(...) out through the script
			$this->script->fatal($error_format);
		}
		catch (Exception $e)
		{
			$original_error = "Original Error: $error_format";
			$error_format = sprintf('%s thrown within the exception handler. Message: %s on line %d.', get_class($e), $e->getMessage(), $e->getLine()) . PHP_EOL;
			$error_format .= PHP_EOL . $original_error;

			fputs(STDERR, $error_format);
			exit(1);
		}
	}
}
