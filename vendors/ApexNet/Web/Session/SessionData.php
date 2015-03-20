<?php
/**
*
* @package apexnet
* @version $Id: SessionData.php 950 2014-11-23 19:04:35Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\Web\Session;

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Foundation\CoreException;

use ApexNet\Database\DBConnection;

use ArrayAccess;

/**
 * Base session class
 */
class SessionData implements ArrayAccess
{
	private $session_id = false;
	private $session_name = false;
	private $strict = false;

	public function setup(array $config, DBConnection $store)
	{
		// alter session config
		ini_set('session.cache_limiter', '');
		ini_set('session.use_cookies', 1);
		ini_set('session.use_trans_sid', 0);
		ini_set('session.hash_function', 1);
		ini_set('session.hash_bits_per_character', 5);
		ini_set('session.entropy_file', '/dev/urandom');
		ini_set('session.entropy_length', 64);

		if (isset($config['session_timeout']))
			ini_set('session.gc_maxlifetime', $config['session_timeout']);

		// set safer, but more restrictive config
		$this->strict = isset($config['strict']);
		ini_set('session.use_only_cookies', $this->strict ? 1 : 0);

		// set up the handler
		$handler_class = "ApexNet\\Web\\Session\\SessionStore{$config['session_handler']}";
		session_set_save_handler(new $handler_class($store), true);

		session_name($config['session_name']);

		// update cookie params, as needed...
		$params = session_get_cookie_params();
		if (isset($config['session_domain']))
			$params['domain'] = $config['session_domain'];

		// session id is rotated at least once every five minutes for all logged in users (as well as always on access change)
		session_set_cookie_params(ini_get('session.gc_maxlifetime'), $params['path'], $params['domain'], $params['secure'], $this->strict);

		return $this;
	}

	public function start()
	{
		if ($this->session_id !== false)
			throw new CoreException('User session started twice.');

		session_start();

		$this->session_id = session_id();
		$this->session_name = session_name();
	}

	public function destroy()
	{
		// clear session cookie
		$params = session_get_cookie_params();
		setcookie($this->session_name, '', time() - 42000,
			$params['path'], $params['domain'],
			$params['secure'], $params['httponly']
		);

		// clear session global
		session_unset();

		session_destroy();
		$this->session_id = false;
	}

	public function regenerate()
	{
		if (session_regenerate_id(true))
		{
			$this->session_id = session_id();
			$this->offsetSet('sid_rotated', time());
		}
	}

	public function rotate($force = false)
	{
		// session id is rotated every five minutes only, unless forced
		if (!$force && $this->offsetExists('sid_rotated') && time() - $this->offsetGet('sid_rotated') <= 300)
			return false;

		return $this->regenerate();
	}

	public function strict()
	{
		return $this->strict;
	}

	public function id()
	{
		return $this->session_id;
	}
	
	public function name()
	{
		return $this->session_name;
	}

	/**
	 * ArrayAccess implementation
	 */
	public function offsetExists($offset) { return isset($_SESSION[$offset]); }
	public function offsetGet($offset) { return $_SESSION[$offset]; }

	public function offsetSet($offset, $value) { $_SESSION[$offset] = $value; }
	public function offsetUnset($offset) { unset($_SESSION[$offset]); }
}
