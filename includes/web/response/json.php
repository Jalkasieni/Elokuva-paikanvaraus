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
 * Web response implementation for JSON requests
 */
class web_response_json extends web_response
{
	// The JSON data to be encoded and sent
	protected $json = array();
	protected $mime_type = 'application/json';

	public function body($json_data)
	{
		if ($json_data instanceof web_response)
			$json_data = $json_data->output();

		$this->json = $json_data;
		return $this;
	}

	public function output()
	{
		// encode json and wrap in a callback if requested
		$body = $this->json;
		if (!is_string($body))
		{
			$options = 0;
			if (defined('DEBUG'))
				$options |= JSON_PRETTY_PRINT;

			$body = json_encode($body, $options);
		}

		if (isset($this->request['callback']))
		{
			$callback = preg_replace('#[^a-zA-Z0-9_\\.]++#u', '', $this->request['callback']);
			if (!empty($callback))
			{
				$this->mime_type = 'text/javascript';
				$body = "// JSONP Response \n $callback($body);";
			}
		}

		return $body;
	}

	public function send()
	{
		// set content type to json
		$this->header('Content-type', "{$this->mime_type}; charset={$this->charset}");

		parent::send();
	}
}
