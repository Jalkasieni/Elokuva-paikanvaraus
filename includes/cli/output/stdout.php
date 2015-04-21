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

/**
 * CLI output handler for php standard IO streams
 */
class cli_output_stdout extends cli_output
{
	protected $file = null;

	public function open($target)
	{
		$this->file = cli_output::file($target);

		// STDOUT & STDIN are always open
		return $this;
	}

	public function close()
	{
		// While the standard IO streams can be closed, it is a special case that we do not need
		return (!$this->file || $this->file->close());
	}

	public function valid()
	{
		// Direct access to standard IO outside of a real CLI can behave unexpectedly
		return Util::isCLI() && (!$this->file || $this->file->valid());
	}

	protected function get()
	{
		return fgets(STDIN);
	}

	protected function put($out)
	{
		if ($this->file)
			$this->file->put($out);

		return fputs(STDOUT, $out);
	}

	protected function put_error($error)
	{
		if ($this->file)
			$this->file->put_error($error);

		return fputs(STDERR, $error);
	}
}