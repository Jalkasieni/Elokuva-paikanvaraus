<?php
/**
*
* @package svntools
* @version $Id: auth.php 936 2014-10-21 17:20:55Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Foundation\Cache;
use ApexNet\Foundation\Kernel;

use ApexNet\Web\Auth\BasicAuthController;

/**
 * Pull in the default authentication controller
 */
class svntools_auth_controller extends BasicAuthController
{

}
