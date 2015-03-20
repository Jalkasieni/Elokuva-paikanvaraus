<?php
/**
*
* @package cli
* @version $Id: options.php 843 2014-06-01 14:32:24Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Foundation\Kernel\KernelRequest;

/**
 * CLI environment options
 *
 * Processes command line arguments and optional environment options for CLI scripts.
 */
class cli_options implements KernelRequest, ArrayAccess
{
	// the arguments passed to the script
	private $options = array();

	/**
	 * CLI options factory
	 *
	 * @param array $args typically php's $argv as is
	 */
	public static function create(array $args)
	{
		return new static($args);
	}

	protected function __construct(array $args)
	{
		// Collect the arguments into an associative array 
		//	Note: somewhat similar to php's getopt() but always guaranteed to behave the same way
		for ($i = 0, $count = sizeof($args); $i < $count; ++$i)
		{
			$arg = $args[$i];
			if ($arg[0] !== '-')
				continue;

			if (($posEqual = strpos($arg, '=')) !== false)
			{
				$param = substr($arg, 0, $posEqual);
				$value = substr($arg, $posEqual + 1);
				if($value !== false)
				{
					$this->options[$param] = $value;
				} else $this->options[$param] = true;
			}
			else
			{
				$value = $args[$i + 1];
				if ($value[0] != '-')
				{
					$i += 1;
					$this->options[$arg] = $value;
				} else $this->options[$arg] = true;
			}
		}

		$this->options['args'] =& $args;
	}

	public function get_script()
	{
		return $this->options['args'][0];
	}

	public function check_option($opt)
	{
		if (is_int($opt))
			return isset($this->options['args'][$opt]);

		return isset($this->options[(sizeof($opt) == 1 ? '-' : '--') . $opt]);	
	}
	
	public function get_option($opt, $default = false)
	{
		if (is_int($opt))
		{
			if (!isset($this->options['args'][$opt]))
				return $default;
			return $this->options['args'][$opt];
		}

		$opt = (sizeof($opt) == 1 ? '-' : '--') . $opt;
		if (!isset($this->options[$opt]))
			return $default;
		return $this->options[$opt];
	}

	public function cmp_option($opt, $value)
	{
		return $this->get_option($opt) == $value;
	}

	/**
	 * ArrayAccess implementation
	 */
	public function offsetExists($offset)
	{
		if (is_int($offset))
			return isset($this->options['args'][$offset]);

		return isset($this->options[$offset]);
	}
	 
	public function offsetGet($offset)
	{
		if (is_int($offset))
			return $this->options['args'][$offset];

		return $this->options[$offset];
	}

	final public function offsetSet($offset, $value) { trigger_error('CLI Options: Attempt to modify a const object.', E_USER_WARNING); }
	final public function offsetUnset($offset) { trigger_error('CLI Options: Attempt to modify a const object', E_USER_WARNING); }
}
