
<?php
/**
*
* @package svntools
* @version $Id: screening.php 1218 2015-03-25 14:52:40Z crise $
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


}
