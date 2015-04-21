<?php
/**
*
* @package db
* @copyright (c) 2015 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\Database;

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Database\DBDriverInterface;
use ApexNet\Foundation\CoreException;

/**
 * Database connectivity base class
 */
class DBConnection
{
	// Underlying database driver
	protected $driver = false;

	// Current connection info
	protected $info = array();
	
	// The SQL query currently being processed
	protected $current_query = false;
	
	// Number of SQL queries made
	protected $query_count = 0;

	// Transaction variables
	protected $transaction = false;
	protected $transactions = 0;

	// Transaction constants
	const TRANSACTION_BEGIN = 0;
	const TRANSACTION_COMMIT = 1;
	const TRANSACTION_ROLLBACK = 2;

	/**
	* Wildcards for matching any (%) or exactly one (_) character within LIKE expressions
	*/
	public $any_char;
	public $one_char;

	/**
	 * Constructor
	 */
	protected function __construct(DBDriverInterface $driver)
	{
		$this->any_char = chr(0) . '%';
		$this->one_char = chr(0) . '_';

		$this->driver = $driver;
		$this->info['name'] = $driver->name();
		$this->info['dbms'] = $this->info['name'];
	}

	public function __destruct() {
		if ($this->connected())
			$this->close();
	}

	/**
	 * Database connection factory
	 */
	public static function create(array $db_info, $connect = true, DBDriverInterface $driver = null)
	{
		$dbc = false;
		if ($driver == null && isset($db_info['dbms']))
		{
			$driver_class = "ApexNet\\Database\\Drivers\\{$db_info['dbms']}Driver";
			$dbc = new static(new $driver_class());
		}
		else if ($driver != null)
		{
			$dbc = new static($driver);
		}
		else
		{
			throw new CoreException(__METHOD__ .' called with invalid or missing info.');
		}

		if ($dbc && $connect)
		{	
			$host = isset($db_info['host']) ? $db_info['host'] : 'localhost';
			$port = isset($db_info['port']) ? $db_info['port'] : null;
			$user = isset($db_info['user']) ? $db_info['user'] : 'root';
			$password = isset($db_info['password']) ? $db_info['password'] : '';

			$dbc->connect($host, $port, $user, $password);

			if (isset($db_info['database']))
				$dbc->database($db_info['database']);
		}

		return $dbc;
	}

	/**
	 * State functions
	 */
	public function connected() { return $this->driver->connected(); }
	public function info($field) { return isset($this->info[$field]) ? $this->info[$field] : ''; }

	/**
	 * Connection data - preformatted
	 */
	public function version() { return $this->connected() ? $this->info['name'] .' '. $this->info['version'] : ''; }
	public function user() { return $this->connected() ? $this->info['user'] .'@'. $this->info['host'] : ''; }
	public function queries() { return $this->query_count; }

	/**
	 * Public facing functions
	 */
	public function connect($host, $port, $user, $password, $persistent = true)
	{
		if ($this->driver->connect($host, $port, $user, $password, $persistent) == false)
			throw new CoreException($this->driver->error());

		// store the current connections info
		$this->info['version'] = $this->driver->version();

		$this->info['host'] = $host;
		$this->info['port'] = $port;
		$this->info['user'] = $user;
	}

	public function database($name, $user = '', $password = '')
	{
		if (!$this->connected())
			throw new CoreException('Database connection not established.');

		// commit any pending transaction
		if ($this->transaction)
		{
			$this->driver->commitTransaction();
			$this->transaction = false;
			$this->transactions = 0;
		}

		if (!$this->driver->database($name, $user, $password))
			throw new CoreException($this->driver->error());

		// update  the connection info
		$this->info['database'] = $name;
		if(!empty($user))
			$this->info['user'] = $user;
	}

	public function escape($input, $quote = true, $wildcards = false)
	{
		if (!is_string($input))
			return is_bool($input) ? (int)$input : $input;

		if ($wildcards)
		{
			$input = str_replace(array('_', '%'), array("\_", "\%"), $input);
			$input = str_replace(array(chr(0) . "\_", chr(0) . "\%"), array('_', '%'), $input);
		}

		$input = $this->driver->escape($input);
		return $quote ? "'{$input}'" : $input;
	}

	public function update($sql)
	{
		if($this->query($sql) === true)
		{
			$this->current_query = false;
			return $this->driver->affectedRows();
		}

		throw new CoreException(__METHOD__ . ' used for a non-update operation.');
	}

	public function query($sql)
	{
		if (!$this->connected())
			throw new CoreException('Database connection not established.');

		if (($this->current_query = $this->driver->query($sql)) === false)
			throw new CoreException($this->driver->error());

		++$this->query_count;
		return $this->current_query;
	}

	public function limitQuery($sql, $limit, $offset = 0, $store = true)
	{
		return $this->query($this->build_limit($sql, $limit, $offset), $store);
	}

	public function fetchRow($assoc = true, $result_id = false)
	{
		if ($result_id === false)
			$result_id = $this->current_query;

		if ($result_id === false)
			throw new CoreException($this->driver->error());

		return $this->driver->fetchRow($result_id, $assoc);
	}

	public function freeResult($result_id = false)
	{
		if ($result_id === false)
			$result_id = $this->current_query;

		if ($result_id === false)
			throw new CoreException($this->driver->error());

		$this->current_query = $this->driver->freeResult($result_id);
	}

	public function transaction($state = self::TRANSACTION_BEGIN)
	{
		if (!$this->connected())
			throw new CoreException('Database connection not established.');

		switch ($state)
		{
			case self::TRANSACTION_BEGIN:
				// nested transactions are included in the existing one
				if ($this->transaction)
				{
					++$this->transactions;
					return true;
				}
				$result = $this->transaction = $this->driver->beginTransaction();
			break;

			case self::TRANSACTION_COMMIT:
				if ($this->transaction && $this->transactions > 0)
				{
					--$this->transactions;
					return true;
				} else if (!$this->transaction)
					return false;

				$result = $this->driver->commitTransaction();
				$this->transaction = false;
				$this->transactions = 0;
			break;

			case self::TRANSACTION_ROLLBACK:
				$result = $this->driver->rollbackTransaction();
				$this->transaction = false;
				$this->transactions = 0;
			break;
		}

		return $result;
	}

	public function close()
	{
		if (!$this->connected())
			throw new CoreException('Database connection not established.');

		// we always use a single transaction (if any)
		if ($this->transaction)
		{
			$this->driver->commitTransaction();
			$this->transaction = false;
			$this->transactions = 0;
		}

		if ($this->current_query)
			$this->freeResult();

		$this->driver->close();

		// reset current info
		$this->info = array();
	}

	/**
	 * Query build helpers
	 */
	public function build_limit($sql, $limit, $offset = 0)
	{
		// This syntax is supported by MySQL and PostgreSQL, others will need to overload
		if ($limit > 0)
			$sql .= ' LIMIT ' . (int) $limit . ($offset > 0 ? ' OFFSET ' . (int) $offset : '');
		return $sql;
	}
	
	public function build_where($conds, $operator = 'AND')
	{
		if (!is_array($conds))
			return "WHERE $conds";
	
		$conds = array_filter($conds, function ($v) { return (!empty($v) && is_string($v)); });
		return (!empty($conds)) ? 'WHERE ' . implode(" $operator ", $conds) : '';
	}

	public function build_insert($table, array $data_array)
	{
		return "INSERT INTO $table (" . implode(', ', array_keys($data_array)) . ') VALUES (' . implode(', ', $data_array) . ')' . PHP_EOL;
	}

	public function build_multi_insert($table, array $data_array)
	{
		$columns = array_shift($data_array);
		for ($i = 0, $count = sizeof($data_array); $i < $count; ++$i)
			$data_array[$i] = '(' . implode(', ', $data_array[$i]) . ')';

		return "INSERT INTO $table (" . implode(', ', $columns) . ') VALUES ' . PHP_EOL . implode(', ' . PHP_EOL, $data_array);
	}

	public function build_update($table, array $data_array, $where)
	{
		$columns = array();
		foreach ($data_array as $key => $value)
			$columns[] = "$key = $value";

		return "UPDATE $table SET " . implode(', ', $columns) . ' ' . $this->build_where($where);
	}

	public function build_delete($table, $where = null)
	{
		// deleting a whole table?
		if ($where == null)
			return "DELETE $table";

		return "DELETE FROM $table " . $this->build_where($where);
	}
}
