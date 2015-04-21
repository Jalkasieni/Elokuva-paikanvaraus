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

use ApexNet\Foundation\Cache;
use ApexNet\Foundation\Kernel;
use ApexNet\Foundation\Kernel\KernelRequest;

use ApexNet\Google\Visualization\DataSource as GDataSource;

use ApexNet\Web\Session;

/**
 * Class handling arguments for HTTP requests
 *
 * Inspired by phpBB's original phpbb_request class and its handling of PHP super globals, with changes:
 *	- do not support URL array syntax, they should be avoided
 *	- allow direct array type access to REQUEST variables (cast to strings) through ArrayAccess
 *	- implements our request routing scheme (@todo refactor into a router class)
 *	- does not overwrite the actual array objects in $GLOBALS for interoperability
 *	- does not escape html entities in variables (I found that more often than not I was reversing the process, besides Twig has great output escaping)
 *
 * Original phpbb_request class, and its methods:
 *	@see https://github.com/phpbb/phpbb/blob/feature/ascraeus-experiment/phpBB/includes/core/request.php
 *	@author naderman
 *	@copyright (c) 2010 phpBB Group
 */
class web_request implements KernelRequest, ArrayAccess
{
	const POST = 0;
	const GET = 1;
	const REQUEST = 2;
	const SERVER = 3;
	const COOKIE = 4;
	const FILES = 5;

	private $input = array();

	// URI cache
	private $base_url = false;
	private $request_url = false;

	// routing information for this request
	private $route = false;

	public static function create(array $input = array())
	{
		return new static($input);
	}

	protected function __construct(array $input)
	{
		$this->input = $input;

		if (empty($this->input))
			$this->load_globals();
	}

	protected function load_globals()
	{
		// store globals
		$this->input[self::POST] =& $_POST;
		$this->input[self::GET] =& $_GET;
		$this->input[self::SERVER] =& $_SERVER;
		$this->input[self::COOKIE] =& $_COOKIE;
		$this->input[self::FILES] =& $_FILES;

		// build REQUEST array (GET has preference in case of equal keys)
		$this->input[self::REQUEST] = $this->input[self::GET] + $this->input[self::POST];
	}

	public function is_set($name, $global = self::REQUEST)
	{
		return isset($this->input[$global][$name]);
	}

	public function variable($name, $default, $global = self::REQUEST, $multibyte = false)
	{
		if (!isset($this->input[$global][$name]))
			return $default;

		$value = $this->input[$global][$name];
		self::typecast($value, gettype($default), $multibyte);

		return $value;
	}

	public function server($name, $default = '')
	{
		// Support reverse proxies, technically nginx with HttpRealipModule doesn't need this.
		if (defined('IP_HEADER') && $name == 'REMOTE_ADDR')
			return $this->header(IP_HEADER, $default);

		return $this->variable($name, $default, self::SERVER, true);
	}

	public function route($key = false)
	{
		if ($key && is_string($key))
			return isset($this->route[$key]) ? $this->route[$key] : false;

		return $this->route;
	}

	public function has_header($header_name)
	{
		$name = 'HTTP_' . str_replace('-', '_', strtoupper($header_name));
		return $this->is_set($name, self::SERVER);
	}

	public function header($header_name, $default = '')
	{
		$name = 'HTTP_' . str_replace('-', '_', strtoupper($header_name));
		return $this->server($name, $default);
	}

	public function secure()
	{
		return $this->server('HTTPS') != '';
	}

	public function ajax()
	{
		// Google Visualization API does not set X-Requested-With
		return $this->has_header(GDataSource::SAME_ORIGIN_HEADER) || $this->header('X-Requested-With') == 'XMLHttpRequest';
	}

	/**
	 * Resolve the controller for handling this request
	 */
	public function resolve(&$controller, &$action, array $rules)
	{
		if (!isset($rules['/']))
			$rules['/'] = array('defaults' => array());

		$path_info = rtrim($this->server('PATH_INFO'), '/');
		if (empty($path_info))
			$path_info = '/';

		if (isset($rules[$path_info]))
		{
			$this->route =& $rules[$path_info]['defaults'];
			if (isset($this->route['controller']))
				$controller = $this->route['controller'];
			if (isset($this->route['action']))
				$action = $this->route['action'];
		}
		else
		{
			foreach ($rules as $pattern => $route)
			{
				if (!isset($route['regex']))
					$route['regex'] = null;

				if (!self::build_route_regex($pattern, $route['regex']))
					continue;

				$matches = array();
				if (preg_match($pattern, $path_info, $matches))
				{
					$this->route =& $route['defaults'];
					foreach ($this->route as $key => $value)
					{
						if (isset($matches[$key]))
						{
							self::typecast($matches[$key], gettype($value), true);
							$this->route[$key] = $matches[$key];
						}
					}

					if (!empty($this->route['controller']))
						$controller = $this->route['controller'];
					if (!empty($this->route['action']))
						$action = $this->route['action'];

					// and we are done, or are we?
					break;
				}
			}
		}

		if (is_array($this->route))
		{
			// lower case the retrieved values
			$controller = $this->route['controller'] =  strtolower($controller);
			$action = $this->route['action'] = strtolower($action);
			return true;
		}

		return false;
	}

	/**
	 * Functions for URL generation
	 */
	public function request_url()
	{
		if (!$this->request_url)
		{
			$this->request_url = $this->base_url();

			// path info
			$this->request_url .= $this->is_set('ORIG_PATH_INFO', self::SERVER) ? $this->server('ORIG_PATH_INFO') : $this->server('PATH_INFO');

			// query string
			$query_string = preg_replace('/(&)?'. Session::name() .'=[a-z0-9]*/', '', $this->server('QUERY_STRING'));
			if (!empty($query_string))
			{
				if ($query_string[0] == '&')
					$query_string = substr($query_string, 1);			
				$this->request_url .= "?$query_string";
			}
		}

		return $this->request_url;
	}

	public function base_url()
	{
		if (!$this->base_url)
		{
			// protocol & host
			$secure = $this->secure();
			$this->base_url = ($secure ? 'https://' : 'http://') . $this->header('Host', $this->server('SERVER_NAME'));

			// port
			$port = $this->server('SERVER_PORT', 0);
			if ($port && ((!$secure && $port != 80) || ($secure && $port != 443)))
				$this->base_url .= (strpos($this->base_url, ':') === false) ? ':' . $port : '';

			// script path
			$script_name = str_replace(array('\\', '//'), '/', $this->server('SCRIPT_NAME'));
			$this->base_url .= str_replace('index.php', '', $script_name);

			// remove ending slash
			$this->base_url = rtrim($this->base_url, '/');
		}

		return $this->base_url;
	}

	/**
	 * Functions related to cookieless session support
	 */
	public function append_sid($resource, $params = false, $force = false)
	{
		// support relative paths and urls without protocol
		$url = ($resource[0] == '/') ? $this->base_url() . $resource : $resource;
		$url = (strpos($url, '://') === false) ? ($this->secure() ? 'https://' : 'http://') . $url : $url;
		$url = rtrim($url, '/');

		// detect the delimiter to use for appending $params or sid
		$url_delim = (strpos($url, '?') === false) ? '?' : '&';

		// check empty params
		if ($params === '')
			$params = false;

		// get anchor
		$anchor = '';
		if (strpos($url, '#') !== false)
		{
			list($url, $anchor) = explode('#', $url, 2);
			$anchor = '#' . $anchor;
		}
		else if ($params && strpos($params, '#') !== false)
		{
			list($params, $anchor) = explode('#', $params, 2);
			$anchor = '#' . $anchor;
		} 

		$sid = false;
		if ($force || (!Session::strict() && !$this->is_set(Session::name(), self::COOKIE)))
			$sid = Session::id() ? Session::name() . '=' . session::id() : false;

		// do not append sid twice
		if ($sid && strpos($url, $sid) !== false)
			$sid = false;
		
		if (!$sid && !$anchor)
			return $url . ($params !== false ? $url_delim . $params : '');

		if ($params === false)
			return (!$sid) ? $url . $anchor : $url . $url_delim . $sid . $anchor;

		return $url . $url_delim . $params . ((!$sid) ? '' : "&$sid") . $anchor;
	}

	public function reapply_sid($url)
	{
		// remove previously added sid
		$url = self::remove_sid($url);

		return $this->append_sid($url);
	}

	public static function remove_sid($url)
	{
		$session_name = session::name();
		if (strpos($url, "$session_name=") !== false)
		{
			list($url, $query_string) = explode('?', $url, 2);
			$query_string = preg_replace("/(&)?$session_name=[a-z0-9]*/", '', $query_string);
			if (!empty($query_string))
			{
				if ($query_string[0] == '&')
					$query_string[0] = '?';
				$url .= $query_string;
			}
		}

		return $url;
	}

	/**
	 * ArrayAccess implementation
	 */
	public function offsetExists($offset)
	{
		return isset($this->input[self::REQUEST][$offset]);
	}
	 
	public function offsetGet($offset)
	{
		return $this->variable($offset, '', self::REQUEST, true);
	}

	public function offsetSet($offset, $value) { trigger_error('Request: Attempt to modify a const object.', E_USER_WARNING); }
	public function offsetUnset($offset) { trigger_error('Request: Attempt to modify a const object', E_USER_WARNING); }

	/**
	 * Typecast helper, makes sure user input is of correct type and (possibly) encoding 
	 */
	private static function typecast(&$value, $type, $multibyte = false)
	{
		settype($value, $type);
		if ($type == 'string')
		{
			$value = trim(str_replace(array("\r\n", "\r", "\0"), array("\n", "\n", ''), $value));
			if ($multibyte)
			{
				if (!Normalizer::isNormalized($value))
					$value = (string) Normalizer::normalize($value);
			}

			if (!empty($value))
			{
				if ($multibyte)
				{
					if (!preg_match('/^./u', $value))
						$value = '';
				} else $value = preg_replace('/[\x80-\xFF]/', '?', $value);
			}
		}
	}

	/**
	 * Regex helper, builds actual regex from our routing patterns
	 */
	private static function build_route_regex(&$pattern, array $regex = null)
	{
		$pattern = rtrim($pattern, '/');
		if (empty($pattern))
			return false;

		$cache_key = Kernel::getActiveApp() . '/route/' . md5($pattern);
		if (Cache::active() && ($cached = Cache::get($cache_key, false)) !== false)
		{
			$pattern = $cached;
			return true;
		}

		$pattern = preg_replace('#[.\\+*?[^\\]${}=!|]#', '\\\\$0', $pattern);

		if (strpos($pattern, '(') !== false)
		{
			// optional parts
			$pattern = str_replace(array('(', ')'), array('(?:', ')?'), $pattern);
		}

		$pattern = str_replace(array('<', '>'), array('(?P<', '>[^/.,;?\n]++)'), $pattern);

		if ($regex)
		{
			$search = $replace = array();
			foreach ($regex as $key => $value)
			{
				$search[] = "<$key>[^/.,;?\\n]++";
				$replace[] = "<$key>$value";
			}

			// replace with the user-specified regex
			$pattern = str_replace($search, $replace, $pattern);
		}

		$pattern = '#^'. $pattern .'$#uD';
		if (Cache::active()) Cache::put($cache_key, $pattern);
		return true;
	}
}
