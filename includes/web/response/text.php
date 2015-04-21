<?php
/**
*
* @package apexnet
* @copyright (c) 2015 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

/**
 * Web response implementation for text/plain
 */
class web_response_text extends web_response
{
	// The text response
	protected $text = null;
	protected $mime_type = 'text/plain';

	public function body($text_data, $mime_type = false)
	{
		$this->text = $text_data;
		if (is_file(strval(str_replace("\0", "", $this->text))))
			$this->text = file_get_contents($this->text);

		if ($mime_type)
			$this->mime_type = $mime_type;

		return $this;
	}

	public function output()
	{
		return $this->text ? $this->text : '';
	}

	public function send()
	{
		// get the output here so we can enforce Content-length from server
		$output = $this->output();

		// set content type and length
		$this->header('Content-type', "{$this->mime_type}; charset={$this->charset}");
		$this->header('Content-length', strlen($output));

		$this->send_headers();
		exit($output);
	}
}
