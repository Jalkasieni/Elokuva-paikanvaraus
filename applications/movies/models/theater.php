
<?php
/**
*
* @package svntools
* @version $Id: theater.php 1203 2015-03-25 03:26:39Z crise $
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


}
