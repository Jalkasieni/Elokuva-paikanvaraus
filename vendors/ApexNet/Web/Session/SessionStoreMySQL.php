<?php
/**
*
* @package apexnet
* @version $Id: SessionStoreMySQL.php 950 2014-11-23 19:04:35Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\Web\Session;

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Database\DBConnection;

use Exception;
use SessionHandlerInterface;

/**
 * MySQL session handler
 */
class SessionStoreMySQL implements SessionHandlerInterface
{
	private $time = array();
	private $database;
	private $lock;

	public function __construct(DBConnection $store)
	{
		$this->database = $store;
		$this->time['gc'] = ini_get('session.gc_maxlifetime');
	}

	public static function createSchema(DBConnection $db)
	{
		$db->update("
		CREATE TABLE IF NOT EXISTS core_sessions (
			sid varchar(32) NOT NULL,
			access int(11) unsigned NOT NULL DEFAULT 0,
			data text,
			PRIMARY KEY (sid)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin");
	}

	/**
	 * Functions for session_set_save_handler
	 */
	public function open($save_path, $sess_name)
	{
		$this->time['access'] = time();

		try
		{
			// Always purge old sessions, instead of relying on built in garbage collection
			// results in mysql and cache handlers behaving identically
			$old = $this->database->escape($this->time['access'] - $this->time['gc'], false);
			$this->database->update("DELETE FROM core_sessions WHERE access < $old");
		}
		catch (Exception $e)
		{
			// TODO: log errors, this does not fail opening a session.
		}		

		return true;
	}

	public function close()
	{
		return true;
	}

	public function read($sid)
	{
		try
		{
			// Get lock for this request, to mitigate potential issues with ajax
			$this->lock = $this->database->escape('apexnet.session.' . $sid, true);
			$this->database->query("SELECT GET_LOCK({$this->lock}, 10)");
		
			// Did we actually get the lock?
			$lock = $this->database->fetchRow(false);

			$this->database->freeResult();
			if ($lock[0] != 1)
				return ''; // TODO: no lock, proceed with read only session?

			// Retrieve data, if any...
			$this->database->limitQuery('SELECT s.data FROM core_sessions AS s
				WHERE s.sid = ' . $this->database->escape($sid, true), 1);
			$row = $this->database->fetchRow();

			$this->database->freeResult();
			if ($row !== false)
				return $row['data'];
		}
		catch (Exception $e)
		{
			// TODO: log errors
		}

		return '';
	}

	public function write($sid, $data)
	{
		try
		{
			$data = $this->database->escape($data, false);
			$this->database->update('INSERT INTO core_sessions (sid, access, data) VALUES 
				('. $this->database->escape($sid, true) .", {$this->time['access']}, '{$data}')
			ON DUPLICATE KEY UPDATE access = {$this->time['access']}, data = '{$data}'");

			$this->database->query("DO RELEASE_LOCK({$this->lock})");
		}
		catch (Exception $e)
		{
			// TODO: log errors
			return false;		
		}

		return true;
	}

	public function destroy($sid)
	{
		try
		{
			$this->database->update('DELETE FROM core_sessions WHERE sid = ' . $this->database->escape($sid, true));

			$this->database->query("DO RELEASE_LOCK({$this->lock})");
		}
		catch (Exception $e)
		{
			// TODO: log errors
			return false;
		}

		return true;
	}

	public function gc($max_ttl)
	{
		return true;
	}
}
