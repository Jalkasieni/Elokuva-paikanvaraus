<?php
/**
*
* @package apexnet
* @version $Id: Autoloader.php 836 2014-05-31 19:10:05Z crise $
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

/**
 * The Autoloader Facade
 */
class Autoloader extends Facade
{
	protected static function getImplementingClass()
	{
		return '\ApexNet\Foundation\Loader\ClassLoader';
	}
}
