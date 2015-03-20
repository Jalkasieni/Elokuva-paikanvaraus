<?php
/**
*
* @package apexnet
* @version $Id: util.php 799 2014-05-26 14:50:08Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\Foundation;

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use Exception;

/**
 * Static class for error reporting functions
 */
class ErrorReporting
{
	// private constructor so no instances can be created
	final private function __construct() { }

	/**
	 * Format PHP errors and warnings
	 */
	public static function formatError($errno, $message, $file, $line, $html)
	{
		$file = static::filterPaths($file);
		$message = static::filterPaths($message);

		$error_name = 'Error';
		switch ($errno)
		{
			case E_NOTICE:
			case E_USER_NOTICE:
				$error_name = 'Notice';
				break;
			case E_WARNING:
			case E_USER_WARNING:
			case E_DEPRECATED:
			case E_STRICT:
				$error_name = 'Warning';
				break;
		}

		$error_format = '[ApexNet Debug] %s: in file %s, on line %d: %s' . PHP_EOL;
		if ($html)
		{
			$error_format = '<strong>[ApexNet Debug] %s:</strong> in file <strong>%s</strong>, on line <strong>%d</strong>: <strong>%s</strong><br />';
			$file = htmlspecialchars($file);
			$message = htmlspecialchars($message);
		}

		return sprintf($error_format . PHP_EOL, $error_name, $file, $line, $message);
	}

	/**
	 * Format any exception that extends the standard PHP Exception class
	 */
	public static function formatException(Exception $exception, $html)
	{
		$message = static::filterPaths($exception->getMessage());
		$file = static::filterPaths($exception->getFile());
		$error_format = '';

		if (!$html)
		{
			$error_format = 'MESSAGE: %s' . PHP_EOL . 'FILE: %s' . PHP_EOL . 'LINE: %d' . PHP_EOL;
			$error_format = sprintf($error_format, $message, $file, $exception->getLine());

			if (defined('DEBUG'))
				$error_format .= PHP_EOL  . 'BACKTRACE:' . PHP_EOL . static::formatTrace($exception->getTrace(), false);
		}
		else
		{
			$error_format = '<strong>MESSAGE:</strong> <em>%s</em> <br /> <strong>FILE:</strong> <em>%s</em> <br /> <strong>LINE:</strong> <em>%d</em>' . PHP_EOL;
			$error_format = sprintf($error_format, $message, $file, $exception->getLine());

			if (defined('DEBUG'))
				$error_format .= '<br /><br /> BACKTRACE: <br />' . static::formatTrace($exception->getTrace(), true) . PHP_EOL;
		}

		return $error_format;
	}

	/**
	 * Format exception stack traces
	 */
	public static function formatTrace(array $trace, $html)
	{
		$output = '';
		foreach ($trace as $frame)
		{
			// Strip the current directory from path
			$frame['file'] = empty($frame['file']) ? '(not given by php)' : static::filterPaths($frame['file']);
			$frame['line'] = empty($frame['line']) ? '(not given by php)' : $frame['line'];

			// Skip error and exception handlers
			if (in_array($frame['function'], array('errorHandler', 'exceptionHandler')))
				continue;

			// Only show function arguments for include etc. otherwise use the type
			foreach ($frame['args'] as $key => $argument)
			{
				if ($key == 0 && in_array($frame['function'], array('include', 'require', 'include_once', 'require_once')))
				{
					$frame['args'][$key] = '\''. static::filterPaths($argument) .'\'';
					continue;
				}
				$frame['args'][$key] = is_object($argument) ? get_class($argument) : gettype($argument);
			}

			$frame['class'] = (!isset($frame['class'])) ? '' : $frame['class'];
			$frame['type'] = (!isset($frame['type'])) ? '' : $frame['type'];

			$output .= PHP_EOL;
			$output .= 'FILE: ' . $frame['file'] . PHP_EOL;
			$output .= 'LINE: ' . $frame['line'] . PHP_EOL;

			$output .= 'CALL: ' . $frame['class'] . $frame['type'] . $frame['function'];
			$output .= '(' . implode(', ', $frame['args']) . ')' . PHP_EOL;
		}

		if (!$html)
			return $output;

		$output = nl2br(htmlspecialchars($output));
		return '<div style="font-family: monospace">'. $output .'</div>';
	}

	/**
	 * Helper: filter server paths from output
	 */
	 protected static function filterPaths($message)
	 {
		// Don't depend on constants defined by Kernel::init()
		return str_replace(APEXNET_ROOT_PATH, '[ROOT]/', $message);
	 }
}
