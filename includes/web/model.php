<?php
/**
*
* @package apexnet
* @version $Id: model.php 950 2014-11-23 19:04:35Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Foundation\Kernel;

use ApexNet\Database\DBConnection;

abstract class web_model
{
	// The database connection
	protected $database;

	/**
	 * Check existence of a model
	 */
	public static function check($model)
	{
		$model = strtr($model, '_\\', '//');
		return is_file(APEXNET_APP_MODELS . "$model." . PHP_EXT);
	}

	/**
	 * Factory for models
	 */
	public static function create($model, DBConnection $db)
	{
		$class = Kernel::getActiveApp() . "_{$model}_model";
		return new $class($db);
	}

	protected function __construct(DBConnection $db)
	{
		$this->database = $db;
	}

	public static function create_schema(DBConnection $db) { /* nothing by default */ }
}
