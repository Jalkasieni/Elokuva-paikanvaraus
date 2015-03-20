<?php
/**
*
* @package apexnet
* @version $Id: XmlRPC.php 1122 2015-02-03 04:36:06Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\Web;

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use SimpleXMLElement;

class XmlRPC
{
	public static function makeRequest($address, $method, array $arguments)
	{
		// create a stream HTTP resource for our POST
		$xml_rpc = stream_context_create(array(
			'http' => array(
				'method' => 'POST',
				'header' => "Content-Type: text/xml\r\n" .
							"User-Agent: ApexNet/XML-RPC Backend\r\n",
				'content' => self::encodeRequest($method, $arguments)
			)
		));

		// HTTP response
		$raw_data = file_get_contents($address, false, $xml_rpc);

		return self::decodeResponse(new SimpleXMLElement($raw_data));
	}

	public static function encodeRequest($method, array $arguments)
	{
		// Header & Method
		$result = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\r\n";
		$result .= "<methodCall>\r\n";
		$result .= "<methodName>{$method}</methodName>\r\n";

		// Arguments
		$result .= "<params>\r\n";

		foreach ($arguments as $param)
			$result .= "<param>\r\n" . self::encodeValue($param) . "</param>\r\n";

		$result .= "</params>\r\n";

		// Footer
		$result .= "</methodCall>\r\n";

		return $result;
	}

	public static function decodeRequest(SimpleXMLElement $xml)
	{
		$method = (string) $xml->methodName;
		$params = array();

		if (isset($xml->params))
		{
			if (isset($xml->params->param))
			{
				foreach ($xml->params->param as $param)
				{
					if (isset($param->value))
						$params[] = self::decodeValue($param->value);
				}
			}
		}

		return array($method, $params);
	}

	public static function encodeResponse($method_result)
	{
		// Header
		$result = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\r\n";
		$result .= "<methodResponse>\r\n";

		// Result value
		$result .= "<params>\r\n";
		$result .= "<param>\r\n" . self::encodeValue($method_result) . "</param>\r\n";
		$result .= "</params>\r\n";

		// Footer
		$result .= "</methodResponse>\r\n";

		return $result;
	}

	public static function decodeResponse(SimpleXMLElement $xml)
	{
		// TODO: add proper fault support, when we need it...
		if (isset($xml->fault))
			return null;

		// We only have a single return value
		if (isset($xml->params) && isset($xml->params->param->value))
			return self::decodeValue($xml->params->param->value);

		return $params;
	}

	/**
	 * Encode helpers, encode PHP variables into XML-RPC format
	 */
	protected static function encodeValue($var)
	{
		$result = false; // if we fail return false

		switch (gettype($var))
		{
			case 'boolean':
				$result = '<value><boolean>'. ($var ? 1 : 0) . "</boolean></value>\r\n";
				break;

			case 'double':
				$result = "<value><double>{$var}</double></value>\r\n";
				break;

			case 'integer':
				$result = "<value><int>{$var}</int></value>\r\n";
				break;

			case 'string':
				$result = "<value><string><![CDATA[{$var}]]></string></value>\r\n";
				break;

			case 'array':
				$result = "<value>" . self::encodeArray($var) . "</value>\r\n";
				break;
			
			default:
				$result = false;
				break;
		}

		return $result;
	}

	protected static function encodeArray(array $array)
	{
		$result = false;
		if (empty($array))
			return '<array><data /></array>';

		$arr_keys = array_keys($array);
		if (!is_int($arr_keys[0]))
		{
			$result = "<struct>\r\n";

			foreach ($array as $key => $value)
			{
				$result .= "<member>\r\n";
				$result .= "<name><![CDATA[{$key}]]></name>\r\n";
				$result .= self::encodeValue($value);
				$result .= "</member>\r\n";
			}

			$result .= "</struct>\r\n";
		}
		else
		{
			$result = "<array>\r\n";
			$result .= "<data>\r\n";

			foreach ($array as $value)
				$result .= self::encodeValue($value);

			$result .= "</data>\r\n";
			$result .= "</array>\r\n";
		}

		return $result;
	}

	/**
	 * Decode helpers, decodes XML-RPC nodes into PHP variables
	 */
	protected static function decodeValue(SimpleXMLElement $value)
	{
		$result = null; // if we fail return null

		// No type declared => string
		if ($value->count() == 0)
			return (string) $value;

		$value = $value->children();
		switch ($value[0]->getName())
		{
			case 'boolean':
				$result = boolval((string) $value[0]);
				break;

			case 'double':
				$result = floatval((string) $value[0]);
				break;

			case 'int':
			case 'i4':
				$result = intval((string) $value[0]);
				break;

			case 'string':
				$result = (string) $value[0];
				break;

			case 'dateTime.iso8601':
				$result = strtotime((string) $value[0]);
				break;

			case 'base64':
				$result = base64_decode((string) $value[0]);
				break;

			case 'array':
			case 'struct':
				$result = self::decodeArray($value[0]);
				break;

			default:
				$result = $value[0]->getName();
				break;
		}

		return $result;
	}

	protected static function decodeArray(SimpleXMLElement $node)
	{
		// Are we a struct? (ie. associative array)
		if (isset($node->member))
		{
			$result = array();
			foreach ($node->member as $member)
			{
				if (isset($member->name) && isset($member->value))
					$result[(string) $member->name] = self::decodeValue($member->value);
			}

			return $result;
		}
		else if (isset($node->data))
		{
			$result = array();
			if (isset($node->data->value))
			{
				foreach ($node->data->value as $value)
					$result[] = self::decodeValue($value);
			}

			return $result;
		}
	}
}
