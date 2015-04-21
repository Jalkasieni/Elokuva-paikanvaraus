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

use ApexNet\Foundation\CoreException;

/**
 * Singleton design pattern (NOTE: currently unused, may be removed)
 *
 * Usage:
 *	$obj = Class::create(...);
 */
trait Singleton
{
	// Singleton instance
	protected static $instance;

	final private function __construct(array $args)
	{
		// Allow instance to pass arguments when constructing the singleton
		if (is_callable(array($this, 'init')))
			call_user_func_array(array($this, 'init'), $args);
	}

	final public static function instance()
	{
		if (!isset(static::$instance))
			throw new CoreException('Singleton instance not created (missing call to '. __CLASS__ .'::create()).');

		return static::$instance;
	}

	final private function __clone() { }

	/**
	 * Create the singleton, error if already created.
	 */
	public static function create()
	{
		if (isset(static::$instance))
			throw new CoreException('Singleton instance already exists (multiple calls to '. __CLASS__ .'::create()).');

		return (static::$instance = new static(func_get_args()));
	}
}
