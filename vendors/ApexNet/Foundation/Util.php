<?php
/**
*
* @package apexnet
* @copyright (c) 2015 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\Foundation;

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

/**
 * Static class for common auxiliary functions
 */
class Util
{
	// private constructor so no instances can be created
	final private function __construct() { }

	/**
	 * Checks if we are running from command line
	 */
	public static function isCLI()
	{
		return (PHP_SAPI == 'cli' || (substr(PHP_SAPI, 0, 3) == 'cgi' && @$_SERVER['REMOTE_ADDR'] == ''));
	}

	/**
	 * Execution statistics
	 */
	public static function time($format = true)
	{
		// return time used for response generation
		$current_time = microtime(true) - APEXNET_TIME_START;
		return $format ? sprintf("%.3f", $current_time) : $current_time;
	}

	public static function memory($format = true, $base_usage = false)
	{
		$current_memory = memory_get_usage();
		$current_memory -= ($base_usage !== false ? $base_usage : APEXNET_MEMORY_START);
		
		if ($format)
		{
			$current_memory = ($current_memory >= 1048576) ? round((round($current_memory / 1048576 * 100) / 100), 2) . ' MiB' : 
				(($current_memory >= 1024) ? round((round($current_memory / 1024 * 100) / 100), 2) . ' KiB' : $current_memory . ' Bytes');
		}

		// return memory used for response generation
		return $current_memory;
	}
}
