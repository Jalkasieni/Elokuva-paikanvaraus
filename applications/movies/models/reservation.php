
<?php
/**
*
* @package svntools
* @version $Id: reservation.php 1322 2015-04-03 01:52:48Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Database\DBConnection;

/**
 * Reservation model
 */
class movies_reservation_model extends web_model
{
	protected $screening;

	const STATE_FREE = 0;
	const STATE_PENDING = 1;
	const STATE_CONFIRMED = 2;

	protected $letters = " ABCDEFGHIJKLMNOPQRSTUVWXYZ";

	public static function create_schema(DBConnection $db)
	{
		$db->update("
		CREATE TABLE IF NOT EXISTS movie_reservations (
			reservation_id mediumint(8) unsigned NOT NULL AUTO_INCREMENT COMMENT 'This is used as pseudo primary key while the actual primary key is used just to prevent duplicates',
			reservation_modified int(11) unsigned NOT NULL DEFAULT 0,
			cords_seat tinyint(3) unsigned NOT NULL DEFAULT 0,
			cords_row tinyint(3) unsigned NOT NULL DEFAULT 0,
			reservation_state tinyint(1) unsigned NOT NULL DEFAULT 0,

			screening_id mediumint(8) unsigned NOT NULL,
			user_id mediumint(8) unsigned NOT NULL,

			PRIMARY KEY screening_seat (screening_id, cords_seat, cords_row),
			KEY (reservation_id),
			KEY (screening_id),
			KEY (user_id)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin");
	}

	protected function __construct(DBConnection $db)
	{
		parent::__construct($db);

		$this->screening = web_model::create('screening', $db);
	}

	function add_reservation(array $meta_data)
	{
		$size = $this->screening->get_size($meta_data['screening_id']);
		if ($meta_data['row'] > $size['rows'] || $meta_data['seat'] > $size['seats'])
			return false;

		return ($this->database->update($this->database->build_insert('movie_reservations', array(
			'reservation_modified'	=> (int) time(),
			'cords_seat'			=> (int) $meta_data['seat'],
			'cords_row'				=> (int) $meta_data['row'],
			'reservation_state'		=> self::STATE_PENDING,
			'screening_id'			=> (int) $meta_data['screening_id'],
			'user_id'				=> (int) $meta_data['user_id']
		))) == 1);
	}

	function confirm_reservations($user_id, $screening_id)
	{
		$conds = array();
		$conds[] = 'screening_id = '. (int) $screening_id;
		$conds[] = 'user_id = '. (int) $user_id;
		$conds[] = 'reservation_state = ' .  self::STATE_PENDING;

		return ($this->database->update($this->database->build_update('movie_reservations', array('reservation_modified' => time(), 'reservation_state' => self::STATE_CONFIRMED), $conds)) >= 1);
	}

	function remove_reservations($user_id, $screening_id, $only_pending = true)
	{
		$conds = array();
		$conds[] = 'screening_id = '. (int) $screening_id;
		$conds[] = 'user_id = '. (int) $user_id;
		$conds[] = ($only_pending ? 'reservation_state = ' .  self::STATE_PENDING : false);

		return ($this->database->update($this->database->build_delete('movie_reservations', $conds)) >= 1);
	}

	function remove_reservation($reservation_id, $user_id)
	{
		$conds = array();
		$conds[] = 'reservation_id = '. (int) $reservation_id;
		$conds[] = 'user_id = '. (int) $user_id;

		return ($this->database->update($this->database->build_delete('movie_reservations', $conds)) == 1);
	}

	function get_reservation_table($screening_id)
	{
		$size = $this->screening->get_size($screening_id);
		$table = array();

		for ($i = $size['rows']; $i > 0; --$i)
		{
			for ($j = 1; $j <= $size['seats']; ++$j)
			{
				$label = $this->letters[$i] . (($j < 10) ? "0$j" : "$j");
				$table[$i][$j] = array('state' => self::STATE_FREE, 'reservation_id' => 0, 'user_id' => 0, 'label' => $label);
			}
		}

		$this->database->query("
			SELECT		mr.reservation_id, mr.cords_seat AS seat, mr.cords_row AS row, reservation_state AS state, mr.user_id

			FROM		movie_reservations AS mr 
			WHERE		mr.screening_id = " . (int) $screening_id . "
			ORDER BY	mr.reservation_id DESC");

		while (($row = $this->database->fetchRow()) !== false)
		{
			$record = $table[(int)$row['row']][(int)$row['seat']];

			$record['state'] = (int) $row['state'];
			$record['reservation_id'] = (int) $row['reservation_id'];
			$record['user_id'] = (int) $row['user_id'];

			$table[(int)$row['row']][(int)$row['seat']] = $record;
		}

		$this->database->freeResult();
		return $table;
	}

	function count_user_reservations($user_id, $only_upcoming = true)
	{
		$conds = array();
		$conds[] = 'mr.user_id = ' .  (int) $user_id;
		$conds[] = ($only_upcoming ? 'ms.screening_start > ' . (int) time() : false);

		$this->database->query('
			SELECT		COUNT(mr.reservation_id) AS reservations
			FROM		movie_reservations AS mr
				LEFT JOIN 		movie_screenings AS ms ON (mr.screening_id = ms.screening_id)
			' . $this->database->build_where($conds));

		$row = $this->database->fetchRow();
		$this->database->freeResult();
		return $row['reservations'];
	}

	function get_user_reservations($user_id, $only_upcoming = true, $limit = 15, $offset = 0)
	{
		$time = (int) time();

		$conds = array();
		$conds[] = 'mr.user_id = ' .  (int) $user_id;
		$conds[] = ($only_upcoming ? "ms.screening_start > $time" : false);

		$this->database->limitQuery("
			SELECT		mi.movie_name, mro.room_name, mt.theater_name, ms.screening_start, mr.cords_seat AS seat, mr.cords_row AS row, reservation_state AS state, mr.user_id,
						(ms.screening_start > $time) AS upcoming

			FROM		movie_reservations AS mr
				LEFT JOIN 		movie_screenings AS ms ON (mr.screening_id = ms.screening_id)
				LEFT JOIN 		movie_info AS mi ON (ms.movie_id = mi.movie_id)
				LEFT JOIN		movie_rooms AS mro ON (ms.room_id = mro.room_id)
				LEFT JOIN 		movie_theaters AS mt ON (mt.theater_id = mro.theater_id)
			" . $this->database->build_where($conds), $limit, $offset);

		$reservations = array();
		while (($row = $this->database->fetchRow()) !== false)
			$reservations[] = $row;

		$this->database->freeResult();
		return $reservations;
	}
}
