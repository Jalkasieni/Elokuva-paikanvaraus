<?php
/**
*
* @package apexnet
* @version $Id: BasicAuthController.php 1183 2015-03-20 18:51:03Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\Web\Auth;

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Foundation\Config;
use ApexNet\Database\DBConnection;
use ApexNet\Web\AccessControl;

use web_controller;
use web_request;
use web_response;

abstract class BasicAuthController extends web_controller
{
	const USERS_LIMIT = 25;

	public function __construct(BasicAuth $user, DBConnection $db, AccessControl $acl)
	{
		parent::__construct($user, $db, $acl);
	}

	public function run(web_request $request, $action = APEXNET_DEFAULT_ACTION)
	{
		// Begin session if any
		$this->user->load($request);

		// Complain in debug mode if we are still not HTTPS
		if (defined('DEBUG') && !$request->secure())
			trigger_error('Unable to log user in securely.', E_USER_WARNING);

		$redirect = $request->variable('redirect', '', web_request::REQUEST);
		if (empty($redirect))
			$redirect = $request->base_url();

		if ($action == 'login' || $action == 'login_admin')
		{
			$username = $request->variable('username', '', web_request::POST);
			$password = $request->variable('password', '', web_request::POST);

			if (empty($username) || empty($password))
				return web_response::login($request, $this->user, $redirect, $action == 'login_admin');

			if ($this->user->login($username, $password, $action == 'login_admin'))
				return web_response::redirect($request, $redirect, 200, 'User logged in successfully');

			// redirect to the intended location regardless, should result in login prompt with error information
			return web_response::redirect($request, $redirect, 401, false, 0);
		}
		else if ($action == 'logout')
		{
			$this->user->logout();
			return web_response::redirect($request, $redirect, 200, 'User logged out successfully');
		}

		return parent::run($request, $action);
	}

	public function prepare(web_request $request)
	{
		// default access restrictions
		$this->acl->assign(array(
			'admin' => array(
				// remote user databases should have their own individual authorization
				'permissions'	=> $this->user->local() ? 'admin' : 'registered'
			),
			'delete' => array(
				'permissions'	=> 'admin'
			),
			'update' => array(
				'permissions'	=> 'registered'
			)
		));
	}

	public function do_register(web_request $request)
	{
		if (!$this->user->local())
			return web_response::redirect($request, $this->user->getRemoteURI('register'), 302);

		if ($this->user->registered() && !$this->user->admin())
			return web_response::redirect($request, '/', 200, 'You are already logged in as a registered user.');

		$form_data = array(
			'username'			=> $request->variable('username', '', web_request::POST),
			'password'			=> $request->variable('password', '', web_request::POST),

			'display_name'		=> $request->variable('display_name', '', web_request::POST),
			'email'				=> $request->variable('email', '', web_request::POST),
			'permissions'		=> $this->user->admin() ? $request->variable('permissions',  array('registered'), web_request::POST) : array('registered'),

			'password_confirm'	=> $request->variable('password_confirm', '', web_request::POST),
		);

		if ($request->is_set('submit') && !empty($form_data['username']) && !empty($form_data['password']))
		{
			$errors = array();
			if ($this->validate('register', $form_data, $errors) && $this->user->createUser($form_data['username'], $form_data['password'], $form_data))
				return web_response::redirect($request, '/', 200, 'User account created successfully.');

			$form_data['errors'] = $errors;

			// PHP __call can't deal with references so this is the only serverside option
			if (isset($form_data['permissions']))
				$form_data['permissions'][] = 'registered';
		}

		return web_response::page($request, 'user_editor', $this->user->pack(array(
			'editor_action'		=> 'register',
			'form'				=> $form_data,
			'permission_list'	=> Config::load('permissions')
		)));
	}

	public function do_update(web_request $request)
	{
		if (!$this->user->local())
			return web_response::redirect($request, $this->user->getRemoteURI('update'), 302);

		$user_id = $request->variable('user_id', (int) $this->user['user_id'], web_request::REQUEST);
		$redirect = ($this->user->admin() ? '/auth/admin' : '/');

		if ($user_id < 1 || (!$this->user->admin() && $this->user['user_id'] != $user_id))
			return web_response::redirect($request, $redirect, 302);

		$current = $this->user->retrieveUser($user_id);
		if ($current === false)
			return web_response::redirect($request, $redirect, 302);

		$form_data = array(
			'user_id'			=> $user_id,
			'username'			=> $current['username'],

			'display_name'		=> $request->variable('display_name', $current['display_name'], web_request::POST),
			'email'				=> $request->variable('email', $current['email'], web_request::POST),
			'permissions'		=> $request->variable('permissions', $current['user_permissions'], web_request::POST),

			'password_old'		=> $request->variable('password_old', '', web_request::POST),
			'password'			=> $request->variable('password', '', web_request::POST),
			'password_confirm'	=> $request->variable('password_confirm', '', web_request::POST),
		);

		if (!$this->user->admin())
			unset($form_data['permissions']);

		if ($request->is_set('submit'))
		{
			if (!$this->user->admin() && (empty($form_data['password_old']) || !password_verify($form_data['password_old'], $current['password_hash'])))
				$form_data['password_old'] = false;

			$errors = array();
			if ($this->validate('update', $form_data, $errors) && $this->user->updateUser($user_id, $form_data, $form_data['password']))
				return web_response::redirect($request, $redirect, 200, 'User account update successfully.');

			$form_data['errors'] = $errors;

			// PHP __call can't deal with references so this is the only serverside option
			if (isset($form_data['permissions']))
				$form_data['permissions'][] = 'registered';
		}

		return web_response::page($request, 'user_editor', $this->user->pack(array(
			'editor_action'		=> 'update',
			'form'				=> $form_data,
			'permission_list'	=> Config::load('permissions')
		)));
	}

	public function do_delete(web_request $request)
	{
		if (!$this->user->local())
			return web_response::error($request, 403);

		$user_id = $request->variable('user_id', (int) $this->user['user_id'], web_request::REQUEST);
		$redirect = ($this->user->admin() ? '/auth/admin' : '/');

		if ($user_id < 1 || (!$this->user->admin() && $this->user['user_id'] != $user_id))
			return web_response::redirect($request, $redirect, 302);

		if ($this->user->deleteUser($user_id))
			return web_response::redirect($request, $redirect, 200, 'User account removed successfully.');

		return web_response::redirect($request, $redirect, 302);
	}

	public function do_admin(web_request $request)
	{
		if (!$this->user->local())
			return web_response::redirect($request, $this->user->getRemoteURI('admin'), 302);

		$response = web_response::create($request);
		$offset = $response->paginate(self::USERS_LIMIT, $this->user->countUsers(), 'users');

		return $response->body('user_admin', $this->user->pack(array(
			'users'		=> $this->user->getUserList(self::USERS_LIMIT, $offset)
		)));
	}

	public function ajax_check_user(web_request $request)
	{
		if (!$this->user->local())
			return web_response::error($request, 403);

		$username = $request->variable('username', '', web_request::GET);
		if (empty($username))
			return web_response::error($request, 400);

		$result = !$this->user->checkUser($username);
		$json_data = array();

		$json_data['result']		= $result;
		$json_data['short_text']	= $result ? 'available' : 'unavailable';
		$json_data['long_text']		= "The user name $username is {$json_data['short_text']}";

		return web_response::json($request, json_encode($json_data));
	}

	protected function validate($action, $form_data, array &$errors)
	{
		if ($action == 'register' || $action == 'update')
		{
			if ($action == 'register' && $this->user->checkUser($form_data['username']))
				$errors[] = "The user name {$form_data['username']} is already taken.";

			if (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL))
				$errors[] = 'You did not provide a valid e-mail address.';

			if (!empty($form_data['password']))
			{
				if ($action == 'update' && $form_data['password_old'] === false)
					$errors[] = 'Old password is incorrect.';

				if (strlen($form_data['password']) < 8)
					$errors[] = 'Your password is too short.';

				if (strcmp($form_data['password'], $form_data['password_confirm']) != 0)
					$errors[] = 'The provided passwords did not match.';			
			}

			return empty($errors);
		}

		return false;
	}
}
