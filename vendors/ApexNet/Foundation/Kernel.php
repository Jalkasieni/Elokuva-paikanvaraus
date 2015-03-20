<?php
/**
*
* @package apexnet
* @version $Id: Kernel.php 838 2014-05-31 19:46:56Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\Foundation;

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Foundation\Facade;
use ApexNet\Foundation\Util;

/**
 * The Kernel Facade
 */
class Kernel extends Facade
{
	protected static function getImplementingClass()
	{
		// return different kernel class based on environment
		static $class_name = false;
		if (!$class_name)
			$class_name = Util::isCLI() ? '\cli_kernel' : '\web_kernel';

		return $class_name;
	}
}
