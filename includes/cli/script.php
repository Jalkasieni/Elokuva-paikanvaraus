<?php
/**
*
* @package cli
* @copyright (c) 2015 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Foundation\Util;
use ApexNet\Foundation\Kernel\KernelRequest;

use ApexNet\Database\DBConnection;

/**
 * Base class for CLI scripts
 */
abstract class cli_script
{
	// holds environment options
	private $env = array();

	// output handler (also handles some basic input, so in retrospect naming is incorrect)
	private $output = null;

	/**
	 * Script instance factory
	 */
	public static function create(array $env_options = array(), cli_output $output = null)
	{
		return new static($env_options, $output);
	}

	protected function __construct(array $env_options, cli_output $output = null)
	{
		$this->env = $env_options;
		$this->output = $output;

		if (!$this->output)
			$this->output = cli_output::stdout();

		if (!$this->output->valid())
			$this->fatal('The provided output handler is invalid');
	}

	/**
	 * Method for main script logic
	 */
	abstract public function main(cli_options $options, DBConnection $db);

	/**
	 * Environment options management
	 */
	public function set_env($option, $value = null)
	{
		if (empty($value))
		{
			unset($this->env[$option]);
		} else $this->env[$option] = $value;
	}

	public function get_env($option)
	{
		if (!isset($this->env[$option]))
			return false;

		return $this->env[$option];
	}

	public function check_env($option)
	{
		return isset($this->env[$option]);
	}

	/**
	 * Output functions
	 */
	public function log($msg = '')
	{
		$this->output->write($msg, PHP_EOL);
		if (!$this->check_env('buffered'))
			$this->output->flush();
	}

	public function fatal($msg)
	{
		$this->output->flush();
		$this->output->error($msg, PHP_EOL);

		$this->terminate(1);
	}

	public function tail($msg)
	{
		$this->set_env('buffered', false);
		$this->log($msg);

		$this->terminate(0);
	}

	/**
	 * Input functions
	 */
	public function prompt($query, $valids = array())
	{
		if ($this->check_env('non-interactive'))
			return (!empty($valids) ? $valids[0] : '');
	
		$input = '';
		do {
			$this->output->write("$query: ");
			$this->output->flush();
			$input = trim($this->output->read());
			if (empty($input) && is_string($valids) && !empty($valids))
				$input = $valids;
		} while (empty($input) || (is_array($valids) && !empty($valids) && !in_array($input, $valids)));
		return $input;
	}

	public function prompt_file($query, $default = '')
	{
		if ($this->check_env('non-interactive'))
			return (!empty($default) ? $default : '');
	
		$file = '';
		do {
			$this->output->write("$query: ");
			$this->output->flush();
			$file = trim($this->output->read());
			if (empty($file) && !empty($default))
				$file = $default;
		} while (empty($file) || !is_file($file));
		return $file;
	}

	/**
	 * Helper: ensures correct exit code under CLI and throws a typed exception otherwise
	 */
	private function terminate($code = 0)
	{
		// If we are not running under php-cli always throw an exception to correctly yield execution to parent
		if (!Util::isCLI())
			throw new cli_result($code);

		exit($code);
	}
}
