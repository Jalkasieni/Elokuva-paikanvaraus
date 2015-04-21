<?php
/**
*
* @package apexnet
* @copyright (c) 2015 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
 * @ignore
 * Note: CLI scripts are located in a sub directory of the usual directory
 */
define('IN_APEXNET', true);
define('APEXNET_ROOT_PATH', dirname(__DIR__) . '/');
define('PHP_EXT', substr(strrchr(__FILE__, '.'), 1));

require(APEXNET_ROOT_PATH . 'bootstrap.' . PHP_EXT);

use ApexNet\Foundation\Kernel;
use ApexNet\Foundation\Util;
use ApexNet\Foundation\Config;

use ApexNet\Database\DBConnection;

class install extends cli_script
{
	/**
	 * main(...)
	 */
	public function main(cli_options $options, DBConnection $db)
	{
		// Set environment options
		if ($options->check_option('s'))
			$this->set_env('non-interactive');

		if ($options->check_option('b'))
			$this->set_env('buffered');

		// Print header
		$this->log(sprintf('Connected: %s (%s) <install>', $db->user(), $db->version()));
		$this->log('-----------------------------------------------------');

		// Process arguments
		$target = $options->get_option(1, 'all');
		if ($target == 'all' || $target == 'core')
		{
			Kernel::createSchemas();
			$this->log('Created common database tables');
		}

		if ($target == 'all')
		{
			$apps = Kernel::getAllApplications();
			foreach ($apps as $app)
			{
				if (Kernel::createSchemas($app))
					$this->log("Created schemas for application: $app");
			}
		}
		else
		{
			if (Kernel::createSchemas($target))
				$this->log("Created schemas for application: $target");
		}

		$this->tail('Operation Complete (' . Util::time() . 's, ' . Util::memory() . ')');
	}
}

// run the script
Kernel::create('core', true, install::create());
Kernel::run(cli_options::create((array)$argv));
