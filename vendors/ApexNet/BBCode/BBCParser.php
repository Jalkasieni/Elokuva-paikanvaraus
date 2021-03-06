<?php
/**
*
* @package apexnet-bbclib
* @copyright (c) 2015 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\BBCode;

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Foundation\Facade;

/**
 * The BBCode parser Facade
 */
class BBCParser extends Facade
{
	protected static function getImplementingClass()
	{
		return '\ApexNet\BBCode\Parser';
	}
}
