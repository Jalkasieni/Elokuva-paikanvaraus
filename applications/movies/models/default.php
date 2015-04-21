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

/**
 * Basic empty model
 */
class movies_default_model extends web_model
{
	public static function create_schema(DBConnection $db)
	{
		// Create your database tables and seed them with data inside this method
	}
}
