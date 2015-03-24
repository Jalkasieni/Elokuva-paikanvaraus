
<?php
/**
*
* @package svntools
* @version $Id: movie.php 1201 2015-03-24 01:55:10Z crise $
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
 * Movie model
 */
class movies_movie_model extends web_model
{
	public static function create_schema(DBConnection $db)
	{
		$db->update("
		CREATE TABLE IF NOT EXISTS movies_info (
			movie_id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
			movie_name varchar(255) NOT NULL,
			movie_poster varchar(255) NOT NULL DEFAULT '',
			movie_description mediumtext NOT NULL DEFAULT '',
			movie_date int(11) unsigned NOT NULL DEFAULT 0,

			PRIMARY KEY (movie_id)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin");

		$db->update("
		CREATE TABLE IF NOT EXISTS movies_screenings (
			screening_id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
			screening_start int(11) unsigned NOT NULL DEFAULT 0,
			screening_end int(11) unsigned NOT NULL DEFAULT 0,

			movie_id mediumint(8) unsigned NOT NULL,
			room_id mediumint(8) unsigned NOT NULL,

			PRIMARY KEY (screening_id),
			KEY (movie_id),
			KEY (room_id)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin");

		$db->update("
		CREATE TABLE IF NOT EXISTS movies_reservations (
			cords_seat tinyint(3) unsigned NOT NULL,
			cords_row tinyint(3) unsigned NOT NULL,
			reservation_state tinyint(1) unsigned NOT NULL DEFAULT 0,

			screening_id mediumint(8) unsigned NOT NULL,
			user_id mediumint(8) unsigned NOT NULL,

			PRIMARY KEY reservation_key (screening_id, cords_seat, cords_row),
			KEY transaction_key (screening_id, user_id)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin");
	}


}
