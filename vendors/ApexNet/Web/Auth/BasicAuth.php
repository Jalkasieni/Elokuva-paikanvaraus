<?php
/**
*
* @package apexnet
* @copyright (c) 2015 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\Web\Auth;

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Foundation\Config;
use ApexNet\Foundation\CoreException;
use ApexNet\Foundation\ArrayBitmask;

use ApexNet\Web\Session;
use ApexNet\Web\Session\SessionData;

use ApexNet\Database\DBConnection;

use ArrayAccess;

use web_request;
use web_response;
use web_model;

/**
 * User authentication base class
 */
class BasicAuth implements ArrayAccess
{
	// User role constants
	const USER_BANNED = -1;
	const USER_GUEST = 0;
	const USER_NORMAL = 1;
	const USER_ADMIN = 2;

	// The applications authentication model and permission bitmask
	protected $model;
	protected $permissions;

	// The user's data
	protected $data = array();
	protected $role = self::USER_GUEST;

	// Secure login server
	protected $auth_server = false;

	// Total number of allowed login attempts
	protected $retry_limit = 3;

	// The internal session
	private $session;

	/**
	 * Authentication factory
	 *
	 * @return instance of this container class with SessionData
	 */
	public static function create(array $config, DBConnection $store, SessionData $session = null)
	{
		if ($session === null)
			$session = Session::setup($config, $store);

		return new static($session, $config, $store);
	}

	protected function __construct(SessionData $session, array $config, DBConnection $db = null)
	{
		$this->session = $session;

		if (isset($config['auth_server']))
			$this->auth_server = $config['auth_server'];

		if (isset($config['auth_retries']))
			$this->retry_limit = $config['auth_retries'];

		// load in permission flags
		$permission_names = isset($config['permissions']) ? $config['permissions'] : Config::load('permissions');
		$this->permissions = new ArrayBitmask($permission_names);

		// create the model used to retrieve user data
		$this->model = self::model($config, $db);
	}

	/**
	 * Load session data
	 */
	public function load(web_request $request)
	{
		$this->session->start();

		// Do an aggressive token check against session hijacking
		$token = sha1($request->header('User-Agent') .'.'. $request->server('REMOTE_ADDR'));
		if (isset($this->session['token']) && $token != $this->session['token'])
		{
			// Retain old password retry counter, so that token check can not be used as a bypass.
			$retries = $this->session['retries'];
			$this->logout();

			$this->session['token'] = $token;
			$this->session['retries'] = $retries;

			$this->session['error'] = 'Session origin could not be sufficiently verified.';
			return $this;
		}

		if (isset($this->session['user_id']) && $this->session['user_id'] != 0)
		{
			$user_data = $this->model->retrieveUser($this->session['user_id']);
			if ($user_data === false)
			{
				$this->logout();

				$this->session['error'] = "User associated with this session doesn't exist.";
				return $this;
			}
			
			$this->setUser($user_data);

			// rotate session id for logged in users, force if users access has changed
			$this->session->rotate($this->role != $this->session['role']);

			if ($this->role != $this->session['role'])
			{
				// update the role constant
				$this->session['role'] = $this->role;

				// update the admin flag
				if ($this->session['is_admin'])
					$this->session['is_admin'] = ($this->role == self::USER_ADMIN);
			}

			return $this;
		}

		$this->setUser();

		// Unauthenticated session, ensure initial data
		$this->session['user_id'] = 0;
		$this->session['is_admin'] = false;
		$this->session['role'] = $this->role;

		if (!isset($this->session['token']))
			$this->session['token'] = $token;
		if (!isset($this->session['retries']))
			$this->session['retries'] = 0;

		return $this;
	}

	/**
	 * Return correct login link based user state
	 */
	public function loginLink($admin = false)
	{
		$url = $this->registered() ? ($admin ? '/auth/login_admin' : '/auth/logout') :  '/auth/login';
		if ($this->auth_server)
			$url = "https://{$this->auth_server}$url";
		return $url;
	}

	/**
	 * HTTP logins using the authorization mechanism, blocks on errors
	 */
	public function httpLogin(web_request $request, $realm, $admin = false)
	{
		$username = $request->server('PHP_AUTH_USER', $request->server('HTTP_AUTHORIZATION'));
		$password = $request->server('PHP_AUTH_PW');

		// fall back to HTTP_AUTHORIZATION if we are missing the PHP_ variant
		if (!empty($username) && empty($password) && strpos($username, 'Basic ') === 0)
		{
			list($username, $password) = explode(':', base64_decode(substr($username, 6)), 2);
		}

		if (!empty($username) && !empty($password))
		{
			if ($this->login($username, $password, $admin))
				return true;
		}

		$response = web_response::error($request, 401);
		if ($this->session['retries'] < $this->retry_limit)
			$response->header('WWW-Authenticate: Basic realm="' . $realm . '"');

		return $response;
	}

	/**
	 * Low level authentication api
	 */
	public function login($username, $password, $admin = false)
	{
		// Don't permit admin logins without a valid user session
		if ($admin && (!$this->registered() || $this->data['username'] !== $username))
		{
			$this->session['error'] = 'Unauthenticated or mismatching admin logins are not permitted.';
			return false;
		}

		// Check for hammering, if hit fails logins automatically for the duration of this session
		if ($this->session['retries'] >= $this->retry_limit)
		{
			$this->session['error'] = 'Maximum allowed password attempts exceeded for this session, try again later.';
			return false;
		}

		$user_data = $this->model->checkLogin($username, $password);
		if ($user_data === false)
		{
			// Increase the counter for incorrect password
			$this->session['retries'] += 1;

			$this->session['error'] = 'Incorrect username and/or password provided.';
			return false;
		}

		$this->setUser($user_data);

		$this->session->regenerate();
		$this->session['role'] = $this->role;

		$this->session['user_id'] = $this->data['user_id'];
		$this->session['retries'] = 0;

		if ($admin && $this->role == self::USER_ADMIN)
			$this->session['is_admin'] = true;

		// Log in successful
		unset($this->session['error']);
		return true;
	}

	public function logout()
	{
		$token = $this->session['token'];
		$this->session->destroy();

		$this->setUser();

		// Start a new (empty) session
		$this->session->start();
		$this->session->regenerate();

		// Set unauthenticated session (similar to BasicAuth::load)
		$this->session['user_id'] = 0;
		$this->session['is_admin'] = false;
		$this->session['role'] = $this->role;

		$this->session['token'] = $token;
		$this->session['retries'] = 0;
	}

	/**
	 * Map user data to an array
	 */
	public function pack(array $vars = array())
	{
		// Check that the data we have matches the session
		if ($this->session['user_id'] != $this->data['user_id'])
			throw new CoreException('Loaded user does not match session user.');

		if (!isset($vars['login_link']))
			$vars['login_link'] = $this->loginLink();

		if (!isset($vars['login_admin']) && $this->data['admin'] && !$this->session['is_admin'])
			$vars['login_admin'] = $this->loginLink(true);

		$vars['user'] =& $this->data;

		$vars['session'] = array(
			'name'		=> Session::name(), 
			'id'		=> Session::id(),
			'admin'		=> $this->session['is_admin']
		);

		if (isset($this->session['error']))
		{
			// Add and pop the error message.
			$vars['session']['error'] = $this->session['error'];
			unset($this->session['error']);
		}

		return $vars;
	}

	public function setUser(array $user_data = null)
	{
		$this->permissions->clear();

		if (is_null($user_data))
		{
			$user_data = array(
				'user_id'				=> 0,
				'username'				=> 'guest',
				'display_name'			=> 'Guest',
				'display_name_clean'	=> 'Guest',

				'admin'					=> false,
				'registered'			=> false,

				'user_role'				=> self::USER_GUEST,
				'user_permissions'		=> array()
			);
		}

		$this->role = $user_data['user_role'];
		$this->permissions->addAll($user_data['user_permissions']);

		// trim duplicate data
		unset($user_data['user_role'], $user_data['user_permissions']);
	
		$this->data = $user_data;
	}

	public static function model(array $config, DBConnection $db = null)
	{
		// Load config, if not present
		$db_config = isset($config['users']) ? $config['users'] : Config::load('users');

		// Set up explicit table prefix
		$tbl_prefix = $db_config['database'] . '.';
		if (isset($db_config['table_prefix']))
			$tbl_prefix .= $db_config['table_prefix'];

		// Create separate database connection if needed
		if (isset($db_config['host']) && ($db === null || strcmp($db->info('host'), $db_config['host']) != 0))
			$db = DBConnection::create($db_config, true);

		$model = null;
		if (isset($config['auth']) && web_model::check($config['auth']))
			$model = web_model::create($config['auth'], $db);

		if ($model == null)
			$model = new BasicAuthModel($db);

		$model->setTablePrefix($tbl_prefix);

		return $model;
	}

	public function local()
	{
		return ($this->model instanceof BasicAuthModel);
	}

	/**
	 * Access control and permissions
	 */
	public function banned() { return $this->role == self::USER_BANNED; }
	public function registered() { return $this->data['registered']; }
	public function admin() { return $this->session['is_admin'] && $this->data['admin']; }

	public function checkPermission($permission_flags, $require_all = false)
	{
		if ($this->role < self::USER_GUEST)
			return false;

		if (is_string($permission_flags))
			return $this->permissions->hasFlag($permission_flags);

		return $require_all ? $this->permissions->hasAll($permission_flags) : $this->permissions->hasAny($permission_flags);
	}

	/**
	 * ArrayAccess implementation
	 */
	public function offsetExists($offset) { return isset($this->data[$offset]); }
	public function offsetGet($offset) { return $this->data[$offset]; }

	public function offsetSet($offset, $value) { $this->data[$offset] = $value; }
	public function offsetUnset($offset) { unset($this->data[$offset]); }

	/**
	 * Allow direct access to additional methods provided by the used model
	 */
	final public function __call($method, $args)
	{
		// In PHP the following error tends to be fatal, which can pass custom error handlers
		if (!is_callable(array($this->model, $method)))
			throw new CoreException('Attempt to call an invalid or private method of the authentication backend.');

		switch (count($args))
		{
			case 0:
				return $this->model->$method();

			case 1:
				return $this->model->$method($args[0]);

			case 2:
				return $this->model->$method($args[0], $args[1]);

			case 3:
				return $this->model->$method($args[0], $args[1], $args[2]);

			case 4:
				return $this->model->$method($args[0], $args[1], $args[2], $args[3]);

			default:
				return call_user_func_array(array($this->model, $method), $args);
		}
	}
}
