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

/**
 * CLI null output handler (output is only accessible at runtime)
 */
class cli_output_memory extends cli_output
{
	public function open($target)
	{
		return $this;
	}

	public function close()
	{
		return true;
	}

	public function valid()
	{
		return true;
	}

	protected function get()
	{
		trigger_error('CLI memory (null) output handler does not accept input', E_USER_WARNING);
		return false;
	}

	protected function put($out)
	{
		// We don't actually want cli_output::flush() to empty the buffer
		return false;
	}

	protected function put_error($error)
	{
		// Force the error into the output buffer
		return $this->write($error);
	}
}