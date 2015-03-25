
<?php
/**
*
* @package svntools
* @version $Id: movie.php 1218 2015-03-25 14:52:40Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Foundation\Config;
use ApexNet\Foundation\ArrayBitmask;
use ApexNet\Database\DBConnection;
use ApexNet\BBCode\BBCParser;

/**
 * Movie model
 */
class movies_movie_model extends web_model
{
	protected $options;

	public static function create_schema(DBConnection $db)
	{
		$db->update("
		CREATE TABLE IF NOT EXISTS movie_info (
			movie_id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
			movie_name varchar(255) NOT NULL,
			movie_poster varchar(255) NOT NULL DEFAULT '',
			movie_description mediumtext NOT NULL DEFAULT '',
			movie_updated int(11) unsigned NOT NULL DEFAULT 0,
			movie_options bigint(16) NOT NULL DEFAULT 0,

			PRIMARY KEY (movie_id)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin");
	}

	protected function __construct(DBConnection $db)
	{
		parent::__construct($db);

		$this->options = new ArrayBitmask(Config::load('movie_options'));
	}

	public function add_movie(array $meta_data)
	{
		$this->options->validate($meta_data['options']);

		return ($this->database->update($this->database->build_insert('movie_info', array(
			'movie_name'			=> $this->database->escape($meta_data['name'], true),
			'movie_poster'			=> $this->database->escape($meta_data['poster_url'], true),
			'movie_description'		=> $this->database->escape(BBCParser::parseStringStorage($meta_data['description']), true),
			'movie_updated'			=> (int) time(),
			'movie_options'			=> (int) $this->options->makeBitmask($meta_data['options'])
		))) == 1);
	}

	public function update_movie($movie_id, array $meta_data)
	{
		$update_fields = array('movie_updated' => (int) time());

		if (isset($meta_data['name']))
			$update_fields['movie_name'] = $this->database->escape($meta_data['name'], true);
		if (isset($meta_data['poster_url']))
			$update_fields['movie_poster'] = $this->database->escape($meta_data['poster_url'], true);
		if (isset($meta_data['description']))
			$update_fields['movie_description'] = $this->database->escape(BBCParser::parseStringStorage($meta_data['description']), true);

		if (isset($meta_data['options']))
		{
			$this->options->validate($meta_data['options']);
			$update_fields['movie_options'] = (int) $this->options->makeBitmask($meta_data['options']);
		}

		return ($this->database->update($this->database->build_update('movie_info', $update_fields, 'movie_id = '. (int) $movie_id)) == 1);
	}

	public function remove_movie($movie_id)
	{
		return ($this->database->update($this->database->build_delete('movie_info', 'movie_id = '. (int) $movie_id)) == 1);
	}

	public function count_movies(array $options = array('active'))
	{
		$conds = array();
		if (!empty($options))
		{
			$options = (int) $this->options->makeBitmask($options);
			$conds[] = "(mi.movie_options & $options) = $options";
		}

		$this->database->query('SELECT COUNT(mi.movie_id) AS movies FROM movie_info AS mi ' . $this->database->build_where($conds));

		$row = $this->database->fetchRow();
		$this->database->freeResult();
		return $row['movies'];
	}

	public function get_movies(array $options = array('active'), $parse_bbc = true, $limit = 15, $offset = 0)
	{
		$conds = array();
		if (!empty($options))
		{
			$options = (int) $this->options->makeBitmask($options);
			$conds[] = "(mi.movie_options & $options) = $options";
		}

		$this->database->limitQuery("
			SELECT		mi.movie_id, mi.movie_name AS name, mi.movie_poster AS poster_url, mi.movie_description AS description,
						mi.movie_updated AS modified_date, mi.movie_options AS options

			FROM		movie_info AS mi " . $this->database->build_where($conds) . "
			ORDER BY	mi.movie_id DESC", $limit, $offset);

		$movies = array();
		while (($row = $this->database->fetchRow()) !== false)
		{
			if ($parse_bbc)
				$row['description'] = BBCParser::parseStoredString($row['description']);

			$row['options'] = $this->options->makeArray($row['options']);
			$movies[] = $row;
		}

		$this->database->freeResult();
		return $movies;
	}

	public function count_all_movies()
	{
		return $this->count_movies(array());
	}

	public function get_all_movies($parse_bbc = true, $limit = 15, $offset = 0)
	{
		return $this->get_movies(array(), $parse_bbc, $limit, $offset);
	}

	public function get_movie($movie_id, $parse_bbc = true)
	{
		$this->database->limitQuery("
			SELECT		mi.movie_id, mi.movie_name AS name, mi.movie_poster AS poster_url, mi.movie_description AS description,
						mi.movie_updated AS modified_date, mi.movie_options AS options

			FROM		movie_info AS mi
			WHERE		mi.movie_id = " . (int) $movie_id, 1);

		$movie = $this->database->fetchRow();
		if ($movie !== false)
		{
			if ($parse_bbc)
				$row['description'] = BBCParser::parseStoredString($row['description']);

			$movie['options'] = $this->options->makeArray($movie['options']);
		}

		$this->database->freeResult();
		return $movie;
	}

	public function search_movies($query, $parse_bbc = true, $limit = 15, $offset = 0)
	{
		$query = $this->database->any_char . str_replace('*', $db->any_char, $query) . $this->database->any_char;

		$conds = array();
		$conds[] = '(mi.movie_options  & '. (int) $this->options->makeBitmask(array('active')) .') <> 0';
		$conds[] = 'mi.movie_name LIKE ' . $this->database->escape($query, true, true);

		$this->database->limitQuery("
			SELECT		mi.movie_id, mi.movie_name AS name, mi.movie_poster AS poster_url, mi.movie_description AS description,
						mi.movie_updated AS modified_date, mi.movie_options AS options

			FROM		movie_info AS mi " . $this->database->build_where($conds) . "
			ORDER BY	mi.movie_id DESC", $limit, $offset);

		$movies = array();
		while (($row = $this->database->fetchRow()) !== false)
		{
			if ($parse_bbc)
				$row['description'] = BBCParser::parseStoredString($row['description']);

			$row['options'] = $this->options->makeArray($row['options']);
			$movies[] = $row;
		}

		$this->database->freeResult();
		return $movies;
	}

	public function count_search($query)
	{
		$query = $this->database->any_char . str_replace('*', $db->any_char, $query) . $this->database->any_char;

		$conds = array();
		$conds[] = '(mi.movie_options  & '. (int) $this->options->makeBitmask(array('active')) .') <> 0';
		$conds[] = 'mi.movie_name LIKE ' . $this->database->escape($query, true, true);

		$this->database->query('SELECT COUNT(mi.movie_id) AS movies FROM movie_info AS mi ' . $this->database->build_where($conds));

		$row = $this->database->fetchRow();
		$this->database->freeResult();
		return $row['movies'];
	}
}
