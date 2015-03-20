<?php
/**
*
* @package apexnet
* @version $Id: xml.php 798 2014-05-26 14:04:33Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

/**
 * Web response implementation for XML responses
 */
class web_response_xml extends web_response
{
	// The DOM document, if any
	protected $xml = null;
	protected $mime_type = 'application/xml';

	public function body($xml_data, $mime_type = false, $options = 0)
	{
		if ($xml_data instanceof DOMDocument)
		{
			// the trivial case
			$this->xml = $xml_data;
		}
		else
		{
			// try to get a string representation and load it
			if ($xml_data instanceof SimpleXmlElement)
				$xml_data = $xml_data->asXML();

			if ($xml_data instanceof web_response)
				$xml_data = $xml_data->output();

			if (!is_string($xml_data) || empty($xml_data))
				throw new Exception('Trying to generate an invalid or empty XML response.');

			$this->xml = new DOMDocument();
			$this->xml->preserveWhiteSpace = false;
			$this->xml->formatOutput = true;

			if (is_file($xml_data))
			{
				$this->xml->load(realpath($xml_data), $options);
			}
			else
			{
				$this->xml->loadXML($xml_data, $options);
			}
		}

		// use the determined charset, so we can blame the user if it goes wrong, and provided mime type
		$this->charset = strtolower($this->xml->encoding);
		if ($mime_type)
			$this->mime_type = $mime_type;

		return $this;
	}

	public function output()
	{
		return $this->xml ? $this->xml->saveXML() : '';
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
