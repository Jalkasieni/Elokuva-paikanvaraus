<?php
/**
*
* @package demo-movies
* @copyright (c) 2015 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Database\DBConnection;
use ApexNet\BBCode\BBCParser;

/**
 * Theater model
 */
class movies_theater_model extends web_model
{
	public static function create_schema(DBConnection $db)
	{
		$db->update("
		CREATE TABLE IF NOT EXISTS movie_theaters (
			theater_id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
			theater_name varchar(255) NOT NULL,
			theater_description mediumtext NOT NULL DEFAULT '',

			PRIMARY KEY (theater_id)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin");

		$db->update("
		CREATE TABLE IF NOT EXISTS movie_rooms (
			room_id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
			room_name varchar(255) NOT NULL,
			room_seats tinyint(3) unsigned NOT NULL,
			room_rows tinyint(3) unsigned NOT NULL,

			theater_id mediumint(8) unsigned NOT NULL,

			PRIMARY KEY (room_id),
			KEY (theater_id)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin");
	}

	public function add_theater(array $meta_data)
	{
		return ($this->database->update($this->database->build_insert('movie_theaters', array(
			'theater_name'			=> $this->database->escape($meta_data['name'], true),
			'theater_description'	=> $this->database->escape(BBCParser::parseStringStorage($meta_data['description']), true),
		))) == 1);
	}

	public function update_theater($theater_id, array $meta_data)
	{
		$update_fields = array();

		if (isset($meta_data['name']))
			$update_fields['theater_name'] = $this->database->escape($meta_data['name'], true);
		if (isset($meta_data['description']))
			$update_fields['theater_description'] = $this->database->escape(BBCParser::parseStringStorage($meta_data['description']), true);

		return ($this->database->update($this->database->build_update('movie_theaters', $update_fields, 'theater_id = '. (int) $theater_id)) == 1);
	}

	public function remove_theater($theater_id)
	{
		$conds = array();
		$conds[] = 'theater_id = '. (int) $theater_id;
		$conds[] = 'NOT EXISTS (SELECT * FROM movie_rooms WHERE theater_id = ' . (int) $theater_id . ')';

		return ($this->database->update($this->database->build_delete('movie_theaters', $conds)) == 1);
	}

	public function count_theaters()
	{
		$this->database->query('SELECT COUNT(mt.theater_id) AS theaters FROM movie_theaters AS mt');

		$row = $this->database->fetchRow();
		$this->database->freeResult();
		return $row['theaters'];
	}

	public function get_theaters($parse_bbc = true, $limit = 15, $offset = 0)
	{
		$this->database->limitQuery("
			SELECT		mt.theater_id, mt.theater_name AS name, mt.theater_description AS description,
						NOT EXISTS (SELECT * FROM movie_rooms WHERE theater_id = mt.theater_id) AS fresh

			FROM		movie_theaters AS mt 
			ORDER BY	mt.theater_id DESC", $limit, $offset);

		$theaters = array();
		while (($row = $this->database->fetchRow()) !== false)
		{
			if ($parse_bbc)
				$row['description'] = BBCParser::parseStoredString($row['description']);

			$theaters[] = $row;
		}

		$this->database->freeResult();
		return $theaters;
	}

	public function get_theater($theater_id, $parse_bbc = true)
	{
		$this->database->limitQuery("
			SELECT		mt.theater_id, mt.theater_name AS name, mt.theater_description AS description,
						NOT EXISTS (SELECT * FROM movie_rooms WHERE theater_id = mt.theater_id) AS fresh

			FROM		movie_theaters AS mt 
			WHERE		mt.theater_id = ". (int) $theater_id, 1);

		$theater = false;
		if (($theater = $this->database->fetchRow()) !== false)
		{
			if ($parse_bbc)
				$theater['description'] = BBCParser::parseStoredString($theater['description']);
		}

		$this->database->freeResult();
		return $theater;
	}

	public function get_theater_list()
	{
		$this->database->query("
			SELECT		mt.theater_id, mt.theater_name

			FROM		movie_theaters AS mt 
			ORDER BY	mt.theater_id DESC");

		$theaters = array();
		while (($row = $this->database->fetchRow()) !== false)
			$theaters[(int)$row['theater_id']] = $row['theater_name'];

		$this->database->freeResult();
		return $theaters;
	}

	public function add_room($theater_id, array $meta_data)
	{
		return ($this->database->update($this->database->build_insert('movie_rooms', array(
			'theater_id'			=> (int) $theater_id,
			'room_name'				=> $this->database->escape($meta_data['name'], true),
			'room_seats'			=> (int) $meta_data['seats'],
			'room_rows'				=> (int) $meta_data['rows'],
		))) == 1);
	}

	public function update_room($theater_id, $room_id, array $meta_data)
	{
		$update_fields = array();

		if (isset($meta_data['name']))
			$update_fields['room_name'] = $this->database->escape($meta_data['name'], true);
		if (isset($meta_data['seats']))
			$update_fields['room_seats'] = (int) $meta_data['seats'];
		if (isset($meta_data['rows']))
			$update_fields['room_rows'] = (int) $meta_data['rows'];

		return ($this->database->update($this->database->build_update('movie_rooms', $update_fields, 'room_id = '. (int) $room_id .' AND theater_id = '. (int) $theater_id)) == 1);
	}

	public function remove_room($theater_id, $room_id)
	{
		$conds = array();
		$conds[] = 'room_id = '. (int) $room_id;
		$conds[] = 'theater_id = '. (int) $theater_id;
		$conds[] = 'NOT EXISTS (SELECT * FROM movie_screenings WHERE room_id = ' . (int) $room_id . ')';

		return ($this->database->update($this->database->build_delete('movie_rooms', $conds)) == 1);
	}

	public function count_rooms($theater_id, $only_inactive = true)
	{
		$conds = array();
		$conds[] = 'mr.theater_id = ' . (int) $theater_id;
		$conds[] = ($only_inactive ? 'NOT EXISTS (SELECT * FROM movie_screenings WHERE room_id = mr.room_id)' : false);

		$this->database->query('SELECT COUNT(mr.room_id) AS rooms FROM movie_rooms AS mr ' . $this->database->build_where($conds));

		$row = $this->database->fetchRow();
		$this->database->freeResult();
		return $row['rooms'];
	}

	public function get_rooms($theater_id, $only_inactive = true, $limit = 15, $offset = 0)
	{
		$this->database->limitQuery("
			SELECT		mr.room_id, mr.room_name AS name, mr.room_seats AS seats, mr.room_rows AS rows,
						EXISTS (SELECT * FROM movie_screenings WHERE room_id = mr.room_id) AS active

			FROM		movie_rooms AS mr 
			WHERE		mr.theater_id = " . (int) $theater_id . ($only_inactive ? ' HAVING active = 0' : '') . "
			ORDER BY	mr.room_id DESC", $limit, $offset);

		$rooms = array();
		while (($row = $this->database->fetchRow()) !== false)
			$rooms[] = $row;

		$this->database->freeResult();
		return $rooms;
	}

	public function get_room($theater_id, $room_id)
	{
		$this->database->limitQuery('
			SELECT		mr.room_id, mr.room_name AS name, mr.room_seats AS seats, mr.room_rows AS rows,
						EXISTS (SELECT * FROM movie_screenings WHERE room_id = mr.room_id) AS active

			FROM		movie_rooms AS mr 
			WHERE		mr.room_id = '. (int) $room_id .' AND theater_id = '. (int) $theater_id, 1);

		$room = $this->database->fetchRow();

		$this->database->freeResult();
		return $room;
	}

	public function get_room_list($theater_id)
	{
		$this->database->query("
			SELECT		mr.room_id, mr.room_name

			FROM		movie_rooms AS mr 
			WHERE		mr.theater_id = " . (int) $theater_id . "
			ORDER BY	mr.room_id DESC");

		$rooms = array();
		while (($row = $this->database->fetchRow()) !== false)
			$rooms[(int)$row['room_id']] = $row['room_name'];

		$this->database->freeResult();
		return $rooms;
	}
}
