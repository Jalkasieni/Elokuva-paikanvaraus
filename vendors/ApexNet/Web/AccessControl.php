<?php
/**
*
* @package apexnet
* @copyright (c) 2015 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\Web;

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Web\Auth\BasicAuth;

use web_request;
use web_response;

/**
 * AccessControl helper
 */
class AccessControl
{
	// Holds the action permission mask pairs for this controller
	protected $acl = array();

	// Holds active users information
	protected $user;

	public function __construct(BasicAuth $user)
	{
		$this->user = $user;
	}

	public function assign(array $access_list)
	{
		$this->acl = $access_list;
	}

	public function add($action, $rule)
	{
		$this->acl[$action] = $rule;
	}

	public function clear()
	{
		$this->acl = array();
	}

	public function valid()
	{
		return !empty($this->acl);
	}

	public function check(web_request $request)
	{
		$config = $this->rules($request);
		if ($config === false)
			return true;

		if (!isset($config['error_message']))
			$config['error_message'] = false;

		if (!isset($config['http_realm']))
			$config['http_realm'] = false;

		// avoid redirect error codes for the time being
		if (!isset($config['error_code']) || ($config['error_code'] >= 300 && $config['error_code'] < 400))
			$config['error_code'] = 401;

		if (!$this->user->registered())
		{
			if ($request->ajax() || in_array('admin', (array)$config['permissions'], true))
				return web_response::error($request, 401, 'Unauthenticated users do not have permission to access this page.');

			$response = $this->authenticate($request, $config['http_realm'], false);
			if ($response instanceof web_response)
				return $response;
		}

		if ($this->user->banned() && !$this->user->admin() && (!isset($config['allow_banned']) || !$config['allow_banned']))
			return web_response::error($request, 401, 'You are not currently authorized to view this page because your account has been banned or temporarily suspended, please try again later.');

		if (in_array('admin', (array)$config['permissions'], true) && !$this->user->admin())
		{
			if ($request->ajax() || !$this->user['admin'])
				return web_response::error($request, 401, 'You are not authorized to view this page because your account does not have the required privileges.');

			$response = $this->authenticate($request, $config['http_realm'], true);
			if ($response instanceof web_response)
				return $response;
		}

		if (!$this->user->checkPermission($config['permissions']))
			return web_response::error($request, $config['error_code'], $config['error_message']);

		return true;
	}

	protected function rules(web_request $request)
	{
		$rule = $request->route('action');

		if (isset($this->acl[$rule]))
			return $this->acl[$rule];

		if (isset($this->acl['*']))
			return $this->acl['*'];

		return false;
	}

	protected function authenticate(web_request $request, $http_realm = false, $admin = false)
	{
		if (empty($http_realm))
			return web_response::login($request, $this->user, false, $admin);

		// HTTP Authorization based authentication over https only
		if (!$request->secure())
			return web_response::error($request, 403, 'This resource requires authentication over a secure connection and unsecure access is forbidden.');

		return $this->user->httpLogin($request, $http_realm, $admin);
	}
}
