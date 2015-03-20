<?php
/**
*
* @package cli
* @version $Id: output.php 798 2014-05-26 14:04:33Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

/**
 * Base class for CLI output handlers
 */
abstract class cli_output
{
	// Internal output buffer
	private $output = '';

	/**
	 * Factories for specific output handlers
	 */
	public static function stdout($file = null)
	{
		$stdout = self::create('stdout');
		if ($file)
			$stdout->open($file);

		return $stdout;
	}

	public static function file($file)
	{
		return self::create('file')->open($file);
	}

	public static function memory()
	{
		return self::create('memory');
	}

	/**
	 * Output handler factory
	 */
	public static function create($type)
	{
		$class = "cli_output_$type";
		return new $class();
	}

	protected function __construct() { }

	public function read()
	{
		$read = $this->get();
		if ($read === false || empty($read))
			return false;

		return self::normalize($read);
	}

	public function write($out, $eol = '')
	{
		$this->output .= self::normalize($out) . $eol;
		return true;
	}

	public function flush()
	{
		if (!empty($this->output))
		{
			if ($this->put($this->output) !== false)
			{
				$this->output = '';
				return true;
			}
		}

		return false;
	}

	public function error($error, $eol = '')
	{
		return $this->put_error(self::normalize($error) . $eol);
	}

	public function contents()
	{
		return $this->output;
	}

	/**
	 * Functions (abstract) dependant on the output method
	 */
	abstract public function open($target);
	abstract public function close();
	abstract public function valid();

	abstract protected function get();
	abstract protected function put($out);
	abstract protected function put_error($error);

	/**
	 * Normalization helper, unifies line endings and converts arrays (TODO: encoding?)
	 */
	private static function normalize($output)
	{
		if (is_array($output))
		{
			foreach ($output as $i => $line)
				$output[$i] = self::normalize($line);

			$output = implode(PHP_EOL, $output);
		}
		else
		{
			// Normalize line-endings to PHP_EOL (in terms of performance unix is favoured slightly) 
			$output = str_replace(array("\r\n", "\r", "\0"), array("\n", "\n", ''), $output);
			if (PHP_EOL != "\n")
				$output = str_replace("\n", PHP_EOL, $output);
		}

		return $output;
	}
}
