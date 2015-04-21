<?php
/**
*
* @package db
* @copyright (c) 2015 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\Database\Drivers;

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Database\DBDriverInterface;

/**
 * Database driver for the MySQL
 */
class MySQLDriver implements DBDriverInterface
{
	// Last error message
	protected $error = '';

	// Current database connection handle
	protected $link = false;

	public function connect($host, $port, $user, $password, $persistent)
	{
		if ($this->link !== false)
		{
			$this->error = 'Driver is already connected.';
			return false;
		}

		$this->link = mysqli_connect($persistent ? "p:$host" : $host, $user, $password, '', $port ? (int)$port : 3306);
		if ($this->link === false)
		{
			$this->error = sprintf('Database error (%d): %s', mysqli_connect_errno(), mysqli_connect_error());
			return false;
		}

		return $this->link;
	}

	public function database($name, $user, $password)
	{
		if (!(empty($user) ? mysqli_select_db($this->link, $name) : mysqli_change_user($user, $password, $name)))
		{
			$this->error = sprintf('Database error (%d): %s', mysqli_errno($this->link), mysqli_error($this->link));
			return false;
		}

		return true;
	}

	public function name()
	{
		return 'MySQL';
	}

	public function version()
	{
		return $this->link ? mysqli_get_server_info($this->link) : '';
	}

	public function connected()
	{
		return $this->link !== false;
	}

	public function error()
	{
		return $this->error;
	}

	public function escape($input)
	{
		return mysqli_real_escape_string($this->link, $input);
	}

	public function query($sql)
	{
		$query = mysqli_query($this->link, $sql);
		if ($query === false)
			$this->error = sprintf('Database error (%d): %s', mysqli_errno($this->link), mysqli_error($this->link));

		return $query;
	}

	public function fetchRow($result_id, $assoc)
	{
		if (($row = mysqli_fetch_array($result_id, $assoc ? MYSQLI_ASSOC : MYSQLI_NUM)) !== null)
			return $row;

		return false;
	}

	public function freeResult($result_id)
	{
		if ($result_id !== true)
			mysqli_free_result($result_id);

		return false;
	}

	public function affectedRows()
	{
		return mysqli_affected_rows($this->link);
	}

	public function beginTransaction()
	{
		return mysqli_autocommit($this->link, false);
	}

	public function commitTransaction()
	{
		$result = mysqli_commit($this->link);
		mysqli_autocommit($this->link, true);
		return $result;
	}

	public function rollbackTransaction()
	{
		$result = mysqli_rollback($this->link);
		mysqli_autocommit($this->link, true);
		return $result;
	}

	public function close()
	{
		mysqli_close($this->link);

		// reset the state
		$this->link = false;
		$this->error = '';
	}
}
