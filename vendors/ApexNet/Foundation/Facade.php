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
 * An abstract Facade base class
 *
 * Facades as coined by Laravel are a variation on the Singleton design pattern where an alias
 * of a class is created to contain a Singleton instance of a regular class, whose methods are
 * then made available through PHP's __callStatic magic method.
 *
 * The below implementation uses three defined methods to manage a Facade: create, bind and active.
 */
abstract class Facade
{
	// All facade instanced mapped by class name
	protected static $facades = array();

	// We don't want instances of this class, ever.
	final private function __construct() { }

	/**
	 * Should be implemented by all subclasses and return the fully qualified name of the implementing class
	 */
	protected static function getImplementingClass()
	{
		throw new CoreException('Facade ' . get_called_class() . ' does not correctly implement getImplementingClass().');
	}

	/**
	 * Initializes facade with an instance of the class set in getImplementingClass().
	 *
	 * Constructor call has no arguments, but may call a method of the new instance by the same name
	 * forwarding the original argument list.
	 */
	final public static function create()
	{
		$class_name = static::getImplementingClass();
		if (isset(static::$facades[$class_name]))
			throw new CoreException('Facade instance already exists (facade already created or bound).');

		$instance = new $class_name();
		if (is_callable(array($instance, 'create')))
			static::callMethod($instance, 'create', func_get_args());

		return (static::$facades[$class_name] = $instance);
	}

	/**
	 * Initializes facade by injecting it with a pre-existing object instance.
	 */
	final public static function bind($class_instance)
	{
		$class_name = static::getImplementingClass();
		if (isset(static::$facades[$class_name]))
			throw new CoreException('Facade instance already exists (facade already created or bound).');

		static::$facades[$class_name] = $class_instance;
	}

	/**
	 * Can be safely used to check Facade status returns true or false, may call method of the bound instance by same name.
	 */
	final public static function active()
	{
		$instance = static::getImplementation();
		if ($instance === null)
			return false;

		if (is_callable(array($instance, 'active')))
			return $instance->active();

		return true;
	}

	/**
	 * Returns a bound instance associated with the class name set in getImplementingClass() or null
	 */
	final protected static function getImplementation()
	{
		$class_name = static::getImplementingClass();
		return isset(static::$facades[$class_name]) ? static::$facades[$class_name] : null;
	}

	/**
	 * The heart of the facade implementation, if unbound attempts to bind an instance by calling create internally.
	 */
	final public static function __callStatic($method, $args)
	{
		// If the facade is unbound, call the create() method and hope for the best...
		$instance = static::getImplementation();
		if ($instance === null)
			$instance = static::create();

		// In PHP the following error tends to be fatal, which can pass custom error handlers
		if (!is_callable(array($instance, $method)))
			throw new CoreException('Attempt to call an invalid or private method on: ' . get_called_class() . ' facade.');

		return static::callMethod($instance, $method, $args);
	}

	/**
	 * Calls a named method of provided instance with provided arguments
	 */
	final protected static function callMethod($instance, $method, $args)
	{
		switch (count($args))
		{
			case 0:
				return $instance->$method();

			case 1:
				return $instance->$method($args[0]);

			case 2:
				return $instance->$method($args[0], $args[1]);

			case 3:
				return $instance->$method($args[0], $args[1], $args[2]);

			case 4:
				return $instance->$method($args[0], $args[1], $args[2], $args[3]);

			default:
				return call_user_func_array(array($instance, $method), $args);
		}
	}
}
