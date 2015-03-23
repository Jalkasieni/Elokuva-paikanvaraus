
<?php
/**
*
* @package svntools
* @version $Id: movie.php 1199 2015-03-23 21:41:57Z crise $
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
	}
}
