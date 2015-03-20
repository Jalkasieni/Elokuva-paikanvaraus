<?php
/**
*
* @package apexnet
* @version $Id: Session.php 1027 2015-01-31 05:17:07Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\Web;

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Foundation\Facade;

/**
 * The Session Facade
 */
class Session extends Facade
{
	protected static function getImplementingClass()
	{
		return '\ApexNet\Web\Session\SessionData';
	}
}
