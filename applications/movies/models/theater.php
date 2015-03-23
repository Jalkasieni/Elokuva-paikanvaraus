
<?php
/**
*
* @package svntools
* @version $Id: theater.php 1199 2015-03-23 21:41:57Z crise $
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
		CREATE TABLE IF NOT EXISTS movies_theaters (
			theater_id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
			theater_name varchar(255) NOT NULL,
			theater_description mediumtext NOT NULL DEFAULT '',

			PRIMARY KEY (theater_id)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin");
	}
}
