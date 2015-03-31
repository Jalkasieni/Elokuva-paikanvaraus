
<?php
/**
*
* @package svntools
* @version $Id: screening.php 1282 2015-03-31 21:18:44Z crise $
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
 * Screening model
 */
class movies_screening_model extends web_model
{
	public static function create_schema(DBConnection $db)
	{
		$db->update("
		CREATE TABLE IF NOT EXISTS movie_screenings (
			screening_id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
			screening_start int(11) unsigned NOT NULL DEFAULT 0,
			screening_end int(11) unsigned NOT NULL DEFAULT 0,

			movie_id mediumint(8) unsigned NOT NULL,
			room_id mediumint(8) unsigned NOT NULL,

			PRIMARY KEY (screening_id),
			KEY (movie_id),
			KEY (room_id)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin");
	}
	
	public function add_screening($movie_id, array $meta_data)
	{
		return ($this->database->update($this->database->build_insert('movie_screenings', array(
			'movie_id'				=> (int) $movie_id,
			'screening_start'		=> (int) $meta_data['start'],
			'screening_end'			=> (int) $meta_data['end'],
			'room_id'				=> (int) $meta_data['room_id'],
		))) == 1);
	}

	public function update_screening($movie_id, $screening_id, array $meta_data)
	{
		$update_fields = array();

		if (isset($meta_data['start']))
			$update_fields['screening_start'] = (int) $meta_data['start'];
		if (isset($meta_data['end']))
			$update_fields['screening_end'] = (int) $meta_data['end'];
		if (isset($meta_data['room_id']))
			$update_fields['room_id'] = (int) $meta_data['room_id'];

		return ($this->database->update($this->database->build_update('movie_screenings', $update_fields, 'screening_id = '. (int) $screening_id . ' AND movie_id = '. (int) $movie_id)) == 1);
	}

	public function remove_screening($movie_id, $screening_id)
	{
		$conds = array();
		$conds[] = 'screening_id = '. (int) $screening_id;
		$conds[] = 'movie_id = '. (int) $movie_id;
		$conds[] = 'NOT EXISTS (SELECT * FROM movie_reservations WHERE screening_id = ' . (int) $screening_id . ')';

		return ($this->database->update($this->database->build_delete('movie_screenings', $conds)) == 1);
	}

	public function count_screenings($movie_id, $theater_id = 0, $only_upcoming = true)
	{
		$conds = array();
		$conds[] = 'ms.movie_id = ' .  (int) $movie_id;
		$conds[] = (($theater_id != 0) ? 'mr.theater_id = ' . (int) $theater_id : false);
		$conds[] = ($only_upcoming ? 'ms.screening_start > ' . (int) time() : false);

		$this->database->query('
			SELECT		COUNT(ms.screening_id) AS screenings 
			FROM		movie_screenings AS ms 
				LEFT JOIN		movie_rooms AS mr ON (mr.room_id = ms.room_id) 
			' . $this->database->build_where($conds));

		$row = $this->database->fetchRow();
		$this->database->freeResult();
		return $row['screenings'];
	}

	public function get_screenings($movie_id, $theater_id = 0, $only_upcoming = true, $limit = 15, $offset = 0)
	{
		$time = (int) time();
		$this->database->limitQuery("
			SELECT			ms.screening_id, ms.screening_start AS start, ms.screening_end AS end, ms.movie_id, mr.theater_id, mt.theater_name, ms.room_id, mr.room_name,
							(ms.screening_start > $time) AS upcoming, mr.room_seats AS seats, mr.room_rows AS rows, COUNT(mre.reservation_id) AS reserved_seats
			FROM			movie_screenings AS ms 
				LEFT JOIN		movie_rooms AS mr ON (ms.room_id = mr.room_id)
				LEFT JOIN		movie_theaters AS mt ON (mr.theater_id = mt.theater_id)
				LEFT JOIN		movie_reservations AS mre ON (ms.screening_id = mre.screening_id)
			WHERE			ms.movie_id = " . (int) $movie_id . ($only_upcoming ? " AND ms.screening_start > $time" : '') . (($theater_id != 0) ? ' AND mr.theater_id = ' . (int) $theater_id : '') . "
			GROUP BY		ms.screening_id
			ORDER BY		ms.screening_start ASC", $limit, $offset);

		$screenings = array();
		while (($row = $this->database->fetchRow()) !== false)
			$screenings[] = $row;

		$this->database->freeResult();
		return $screenings;
	}

	public function get_screening($screening_id)
	{
		$time = (int) time();
		$this->database->query("
			SELECT			ms.screening_id, ms.screening_start AS start, ms.screening_end AS end, ms.movie_id, mr.theater_id, mt.theater_name, ms.room_id, mr.room_name,
							(ms.screening_start > $time) AS upcoming, mr.room_seats AS seats, mr.room_rows AS rows, COUNT(mre.reservation_id) AS reserved_seats
			FROM			movie_screenings AS ms 
				LEFT JOIN		movie_rooms AS mr ON (ms.room_id = mr.room_id)
				LEFT JOIN		movie_theaters AS mt ON (mr.theater_id = mt.theater_id)
				LEFT JOIN		movie_reservations AS mre ON (ms.screening_id = mre.screening_id)
			WHERE			ms.screening_id = " . (int) $screening_id . "
			GROUP BY		ms.screening_id");

		$row = $this->database->fetchRow();
		$this->database->freeResult();
		return $row;
	}

	public function count_screenings_theater($theater_id, $movie_id = 0, $only_upcoming = true)
	{
		$conds = array();
		$conds[] = 'mr.theater_id = ' . (int) $theater_id;
		$conds[] = (($movie_id != 0) ? 'ms.movie_id = ' . (int) $movie_id : false);
		$conds[] = ($only_upcoming ? ' ms.screening_start > ' . (int) time() : false);

		$this->database->query('
			SELECT			COUNT(ms.screening_id) AS screenings
			FROM			movie_screenings AS ms
				LEFT JOIN 		movie_rooms AS mr ON (mr.room_id = ms.room_id)
			' . $this->database->build_where($conds));

		$row = $this->database->fetchRow();
		$this->database->freeResult();
		return $row['screenings'];
	}

	public function get_screenings_theater($theater_id, $movie_id = 0, $only_upcoming = true, $limit = 15, $offset = 0)
	{
		$time = (int) time();
		$this->database->limitQuery("
			SELECT			ms.screening_id, ms.screening_start AS start, ms.screening_end AS end, mr.theater_id, mi.movie_id, mi.movie_name, ms.room_id, mr.room_name,
							(ms.screening_start > $time) AS upcoming, mr.room_seats AS seats, mr.room_rows AS rows, COUNT(mre.reservation_id) AS reserved_seats
			FROM			movie_screenings AS ms 
				LEFT JOIN		movie_rooms AS mr ON (ms.room_id = mr.room_id)
				LEFT JOIN 		movie_info AS mi ON (ms.movie_id = mi.movie_id)
				LEFT JOIN		movie_reservations as mre ON (ms.screening_id = mre.screening_id)
			WHERE			mr.theater_id = " . (int) $theater_id . ($only_upcoming ? " AND ms.screening_start > $time" : '') . (($movie_id != 0) ? ' AND ms.movie_id = ' . (int) $movie_id : ''). "
			GROUP BY		ms.screening_id
			ORDER BY		ms.screening_start ASC", $limit, $offset);

		$screenings = array();
		while (($row = $this->database->fetchRow()) !== false)
			$screenings[] = $row;

		$this->database->freeResult();
		return $screenings;
	}

	public function get_screening_theater($screening_id)
	{
		$time = (int) time();
		$this->database->query("
			SELECT			ms.screening_id, ms.screening_start AS start, ms.screening_end AS end, mr.theater_id, mi.movie_id, mi.movie_name, ms.room_id, mr.room_name,
							(ms.screening_start > $time) AS upcoming, mr.room_seats AS seats, mr.room_rows AS rows, COUNT(mre.reservation_id) AS reserved_seats
			FROM			movie_screenings AS ms
				LEFT JOIN		movie_rooms AS mr ON (ms.room_id = mr.room_id)
				LEFT JOIN 		movie_info AS mi ON (ms.movie_id = mi.movie_id)
				LEFT JOIN		movie_reservations AS mre ON (ms.screening_id = mre.screening_id)
			WHERE			ms.screening_id = " . (int) $screening_id . "
			GROUP BY		ms.screening_id");

		$row = $this->database->fetchRow();
		$this->database->freeResult();
		return $row;
	}
	
	public function get_size($screening_id)
	{
		$this->database->query("
			SELECT			mr.room_seats AS seats, mr.room_rows AS rows
			FROM			movie_rooms AS mr
				LEFT JOIN		movie_screenings AS ms ON (mr.room_id = ms.room_id)
			WHERE			ms.screening_id = " .(int) $screening_id);
			
		$row = $this->database->fetchRow();
		$this->database->freeResult();

		return $row;
	}
}
