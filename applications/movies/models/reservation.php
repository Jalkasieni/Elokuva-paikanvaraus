
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
 * Reservation model
 */
class movies_reservation_model extends web_model
{
	public static function create_schema(DBConnection $db)
	{
		$db->update("
		CREATE TABLE IF NOT EXISTS movie_reservations (
			reservation_id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
			cords_seat tinyint(3) unsigned NOT NULL DEFAULT 0,
			cords_row tinyint(3) unsigned NOT NULL DEFAULT 0,
			reservation_state tinyint(1) unsigned NOT NULL DEFAULT 0,

			screening_id mediumint(8) unsigned NOT NULL,
			user_id mediumint(8) unsigned NOT NULL,

			PRIMARY KEY (reservation_id),
			KEY (screening_id),
			KEY (user_id)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin");
	}


}
