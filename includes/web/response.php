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

use ApexNet\Web\Auth\BasicAuth;

/**
 * Base class for typed HTTP responses
 */
abstract class web_response
{
	// HTTP request
	protected $request;

	// The character encoding of the response
	protected $charset;

	// HTTP status code for the response
	protected $status_code;
	
	// Custom HTTP headers from controller
	private $headers = array();

	// HTTP status codes and messages (from HTTP specification)
	protected static $messages = array(
		// Informational 1xx
		100 => 'Continue',
		101 => 'Switching Protocols',

		// Success 2xx
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',

		// Redirection 3xx
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found', // 1.1
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		// 306 is deprecated but reserved
		307 => 'Temporary Redirect',

		// Client Error 4xx
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',

		// Server Error 5xx
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		509 => 'Bandwidth Limit Exceeded'
	);

	/**
	 * Factories for specialized responses
	 */
	public static function redirect(web_request $request, $url, $status_code = 200, $message = false, $time = 5)
	{
		$response = self::create($request, $status_code);

		$url = $request->reapply_sid($url);
		$response->header('Refresh', "$time; url=$url");
		if ($time == 0 || $status_code == 302 || $status_code == 301)
			$response->header('Location', $url);
	
		// page variables
		$page_vars = array(
			'status_code'		=> $status_code,
			'status_message'	=> self::$messages[$status_code],
			'redirect_url'		=> $url,
			'redirect_time'		=> $time
		);

		if ($message)
			$page_vars['message'] = $message;

		return $response->body('redirect', $page_vars);
	}

	public static function error(web_request $request, $status_code, $message = false)
	{
		if ($status_code < 400)
			throw new Exception('Provided HTTP status code is not an error code');

		// page variables
		$page_vars = array(
			'status_code'		=> $status_code,
			'status_message'	=> self::$messages[$status_code]
		);

		if ($message)
			$page_vars['message'] = $message;

		return self::create($request, $status_code)->body('error', $page_vars);
	}

	public static function login(web_request $request, BasicAuth $user, $redirect = false, $admin = false)
	{
		// make sure https is in effect
		$redirect = $redirect ? $redirect : $request->request_url();
		$login_link = $user->loginLink($admin);

		// make sure https is in effect
		if (!$request->secure() && strncmp($login_link, 'https://', 8) == 0)
		{
			$redirect = $request->append_sid($login_link, 'redirect=' . urlencode($redirect), true);
			return web_response::redirect($request, $redirect, 302, false, 0);
		}

		return self::create($request, 401)->body('user_login', $user->pack(array(
			'login_realm'	=> $admin ? 'admin'	: 'user',
			'login_action'	=> $login_link,
			'redirect_url'	=> $redirect
		)));
	}

	/**
	 * Shortcuts for typed responses
	 */
	public static function page(web_request $request, $template, $page_vars = array(), $status_code = 200)
	{
		return self::create($request, $status_code)->body($template, $page_vars);
	}

	public static function json(web_request $request, $json_data, $status_code = 200)
	{
		return self::create($request, $status_code, 'json')->body($json_data);
	}

	public static function xml(web_request $request, $xml_data, $mime_type = false, $status_code = 200)
	{
		return self::create($request, $status_code, 'xml')->body($xml_data, $mime_type);
	}

	public static function text(web_request $request, $text_data, $mime_type = false, $status_code = 200)
	{
		return self::create($request, $status_code, 'text')->body($text_data, $mime_type);
	}

	public static function file(web_request $request, $file, $force_download = false, $serve_from = false, $status_code = 200)
	{
		return self::create($request, $status_code, 'file')->body($file, $force_download, $serve_from);
	}

	/**
	 * Response factory
	 */
	public static function create(web_request $request, $status_code = 200, $type = 'template', $charset = 'utf-8')
	{
		if (!isset(self::$messages[$status_code]))
			throw new Exception('Invalid HTTP status code provided');

		$class = "web_response_$type";
		return new $class($request, $status_code, $charset);
	}

	protected function __construct(web_request $request, $status_code, $charset)
	{
		$this->request = $request;
		$this->charset = $charset;
		$this->status_code = $status_code;

		$this->headers = array(
			'Content-Type' 	=> array("text/html; charset=$charset"),
			'Cache-Control'	=> array('private, no-cache="set-cookie"'),
			'Expires'		=> array('0'),
			'Pragma'		=> array('no-cache')
		);
	}

	public function header($name, $value, $replace =  true)
	{
		if ($replace && isset($this->headers[$name]))
		{
			$this->headers[$name] = array($value);
			return;
		}

		$this->headers[$name][] = $value;
	}

	private function send_http_status($code)
	{
		$message = self::$messages[$code];
		if (substr(strtolower(php_sapi_name()), 0, 3) === 'cgi')
		{
			header("Status: $code $message", true, $code);
		}
		else
		{
			$version = $this->request->server('SERVER_PROTOCOL', 'HTTP/1.0');
			header("$version $code $message", true, $code);
		}
	}

	protected function clear_headers()
	{
		if (headers_sent())
			return false;

		$this->headers = array();
		return true;
	}

	protected function send_headers()
	{
		if (headers_sent())
			return false;

		$this->send_http_status($this->status_code);
		foreach ($this->headers as $name => $values)
		{
			foreach ($values as $key => $value)
				header("$name: $value", $key == 0);
		}

		return true;
	}

	public function send()
	{
		$this->send_headers();
		exit($this->output());
	}

	/**
	 * Functions (abstract) dependant on the response type
	 */
	abstract public function body($content);
	abstract public function output();
}
