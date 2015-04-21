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
 * Implements a dynamic bitmask, and related opreations, based on an associative array of flag names
 */
class ArrayBitmask
{
	protected $flags = array();
	protected $bitmask = 0;

	public function __construct(array $flag_names, $start_bits = 0)
	{
		$max_bits = (PHP_INT_MAX > 2147483647 ? 64 : 32);
		if (sizeof($flag_names) > $max_bits)
			throw new CoreException('ArrayBitmask: too many flags for a bitmask');

		$this->bitmask = $start_bits;
		foreach ($flag_names as $index => $flag_name)
			$this->flags[$flag_name] = (int) pow(2, $index);
	}

	public function hasFlag($flag_name)
	{
		$flag = $this->getFlag($flag_name);
		return ($this->bitmask & $flag) == $flag;
	}

	public function hasAny(array $flag_names)
	{
		$flags = $this->makeBitmask($flag_names);
		return ($this->bitmask & $flags) != 0;
	}

	public function hasAll(array $flag_names)
	{
		$flags = $this->makeBitmask($flag_names);
		return ($this->bitmask & $flags) == $flags;
	}

	public function addFlag($flag_name)
	{
		if (!$this->hasFlag($flag_name))
			$this->bitmask |= $this->flags[$flag_name];
	}

	public function addAll(array $flag_names)
	{
		$flags = $this->makeBitmask($flag_names);
		$this->bitmask |= $flags;
	}

	public function removeFlag($flag_name)
	{
		if ($this->hasFlag($flag_name))
			$this->bitmask &= ~ $this->flags[$flag_name];
	}

	public function getFlag($flag_name)
	{
		if (!isset($this->flags[$flag_name]))
			throw new CoreException("ArrayBitmask: Invalid flag '$flag_name'");

		return $this->flags[$flag_name];
	}
	
	public function getNames()
	{
		return array_keys($this->flags);
	}

	public function getBitmask()
	{
		return $this->bitmask;
	}

	public function getArray()
	{
		return $this->makeArray($this->bitmask);
	}

	public function clear()
	{
		$this->bitmask = 0;
	}

	public function validate(array &$flag_names)
	{
		foreach ($flag_names as $key => $flag_name)
		{
			if (empty($flag_name) || !isset($this->flags[$flag_name]))
				unset($flag_names[$key]);
		}

		return !empty($flag_names);
	}

	public function makeBitmask(array $flag_names)
	{
		$flag_set = 0;
		foreach ($flag_names as $flag_name)
		{
			$flag = $this->getFlag($flag_name);			
			$flag_set |= $flag;
		}

		return $flag_set;
	}

	public function makeArray($bitmask)
	{
		$result = array();
		foreach ($this->flags as $name => $flag)
		{
			if (($bitmask & $flag) == $flag)
				$result[] = $name;
		}

		return $result;
	}
}
