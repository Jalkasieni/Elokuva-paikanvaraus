<?php
/**
*
* @package apexnet
* @version $Id: BasicAuthModel.php 1190 2015-03-20 21:46:17Z crise $
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
use ApexNet\Foundation\CoreException;
use ApexNet\Foundation\ArrayBitmask;

use ApexNet\Database\DBConnection;

use web_model;

class BasicAuthModel extends web_model implements BasicAuthModelInterface
{
	protected $permissions;

	public static function create_schema(DBConnection $db)
	{
		// slim user record
		$db->update("
		CREATE TABLE IF NOT EXISTS core_users (
			user_id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
			username varchar(255) NOT NULL,
			display_name varchar(255) NOT NULL DEFAULT '',
			email varchar(100) NOT NULL DEFAULT '',
			creation_date int(11) unsigned NOT NULL DEFAULT 0,
			password_hash varchar(255) NOT NULL,
			permission_mask mediumint(8) unsigned NOT NULL DEFAULT 0,

			PRIMARY KEY (user_id),
			UNIQUE KEY (username)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin");

		try
		{
			// default user
			$bitmask = new ArrayBitmask(Config::load('permissions'));
			$db->update($db->build_insert('core_users', array(
				'username'				=> $db->escape('root', true),
				'display_name'			=> $db->escape('System User', true),
				'email'					=> $db->escape('root@localhost', true),
				'creation_date'			=> (int) time(),
				'password_hash'			=> $db->escape(password_hash('root', PASSWORD_DEFAULT), true),
				'permission_mask'		=> (int) $bitmask->makeBitmask(array('registered', 'admin'))
			)));
		}
		catch (CoreException $e) { /* for duplicate calls */ }
	}

	public function __construct(DBConnection $db)
	{
		parent::__construct($db);

		$this->permissions = new ArrayBitmask(Config::load('permissions'));
	}

	public function setTablePrefix($prefix)
	{
		// Not used by this backend
	}

	public function getRemoteURI($action)
	{
		throw new CoreException('Attempting to resolve remote action while using local authorization.');
	}

	public function createUser($username, $password, array $meta_data)
	{
		// just make sure we always have this, browsers not sending disabled checkboxes is a pain
		$meta_data['permissions'][] = 'registered';

		if (!$this->permissions->validate($meta_data['permissions']))
			return false;

		return ($this->database->update($this->database->build_insert('core_users', array(
			'username'				=> $this->database->escape($username, true),
			'display_name'			=> $this->database->escape($meta_data['display_name'], true),
			'email'					=> $this->database->escape($meta_data['email'], true),
			'creation_date'			=> (int) time(),
			'password_hash'			=> $this->database->escape(password_hash($password, PASSWORD_DEFAULT), true),
			'permission_mask'		=> (int) $this->permissions->makeBitmask($meta_data['permissions'])
		))) == 1);
	}

	public function deleteUser($user_id)
	{
		return ($this->database->update($this->database->build_delete('core_users', 'user_id = '. (int) $user_id)) == 1);
	}

	public function updateUser($user_id, array $meta_data, $password = false)
	{
		$update_fields = array();

		if (isset($meta_data['display_name']))
			$update_fields['display_name'] = $this->database->escape($meta_data['display_name'], true);
		if (isset($meta_data['email']))
			$update_fields['email'] = $this->database->escape($meta_data['email'], true);

		if (!empty($password))
			$update_fields['password_hash'] = $this->database->escape(password_hash($password, PASSWORD_DEFAULT), true);

		if (isset($meta_data['permissions']))
		{
			// don't loose the registered flag, even if it is somewhat redundant
			$meta_data['permissions'][] = 'registered';

			if (!$this->permissions->validate($meta_data['permissions']))
				return false;

			$update_fields['permission_mask'] = (int) $this->permissions->makeBitmask($meta_data['permissions']);
		}

		return ($this->database->update($this->database->build_update('core_users', $update_fields, 'user_id = '. (int) $user_id)) == 1);
	}

	public function checkUser($username)
	{
		$this->database->query('SELECT EXISTS (SELECT * FROM core_users WHERE username = ' . $this->database->escape($username, true) . ') AS record');
		$row = $this->database->fetchRow();
		$this->database->freeResult();

		return ($row['record'] == 1);
	}

	public function countUsers()
	{
		$this->database->query('SELECT COUNT(cu.user_id) AS users FROM core_users AS cu');

		$row = $this->database->fetchRow();
		$this->database->freeResult();
		return $row['users'];
	}

	public function getUserList($limit = 15, $offset = 0)
	{
		$this->database->limitQuery('
			SELECT		cu.user_id, cu.username, cu.display_name, cu.email, cu.creation_date, cu.permission_mask AS user_permissions

			FROM		core_users AS cu
			ORDER BY	cu.user_id DESC', $limit, $offset);

		$users = array();
		while (($row = $this->database->fetchRow()) !== false)
			$users[] = $row;

		$this->database->freeResult();
		return $users;
	}

	public function retrieveUser($user_id)
	{
		$this->database->limitQuery('
			SELECT		cu.user_id, cu.username, cu.display_name, cu.email, cu.creation_date, cu.password_hash, cu.permission_mask AS user_permissions

			FROM		core_users AS cu
			WHERE		cu.user_id = ' . (int) $user_id, 1);

		$user_data = false;
		if (($row = $this->database->fetchRow()) !== false)
			$user_data = $this->parseUser($row);

		$this->database->freeResult();
		return $user_data;
	}

	public function checkLogin($username, $password)
	{
		$this->database->limitQuery('
			SELECT		cu.user_id, cu.username, cu.display_name, cu.email, cu.creation_date, cu.password_hash, cu.permission_mask AS user_permissions

			FROM		core_users AS cu
			WHERE		cu.username = '. $this->database->escape($username, true), 1);

		$user_data = false;
		if (($row = $this->database->fetchRow()) !== false)
		{
			if (password_verify($password, $row['password_hash']))
			{
				$user_data = $this->parseUser($row);

				if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT))
				{
					// This code won't run for a while
					$this->database->update($this->database->build_update('core_users', array('password_hash' => $this->database->escape(password_hash($password, PASSWORD_DEFAULT))) , 'user_id = '. (int) $row['user_id']));
				}
			}
		}

		$this->database->freeResult();
		return $user_data;
	}

	protected function parseUser($user_data)
	{
		$user_data['display_name_clean'] = $user_data['display_name'];

		$user_data['user_permissions'] = $this->permissions->makeArray($user_data['user_permissions']);
		$user_data['user_role'] = in_array('admin', (array)$user_data['user_permissions'], true) ? BasicAuth::USER_ADMIN : BasicAuth::USER_NORMAL;

		$user_data['admin']	= $user_data['user_role'] == BasicAuth::USER_ADMIN;
		$user_data['registered'] = $user_data['user_role'] != BasicAuth::USER_GUEST;

		// for theme compatibility
		$user_data['group_title'] = $user_data['admin'] ? 'Admin' : 'Member';

		return $user_data;
	}
}
