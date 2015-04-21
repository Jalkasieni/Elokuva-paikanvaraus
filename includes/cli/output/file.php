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
 * CLI output handler for flat files
 */
class cli_output_file extends cli_output
{
	protected $fp = false;

	public function open($target)
	{
		$this->fp = fopen($target, 'bc');
		return $this;
	}

	public function close()
	{
		return $this->valid() ? fclose($this->fp) : false;
	}

	public function valid()
	{
		return $this->fp !== false;
	}

	final protected function get()
	{
		trigger_error('CLI File output handler does not accept input', E_USER_WARNING);
		return false;
	}

	protected function put($out)
	{
		return fputs($this->fp, $out);
	}

	protected function put_error($error)
	{
		return $this->put($error);
	}
}