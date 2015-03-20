<?php
/**
*
* @package svntools
* @version $Id: index.php 839 2014-05-31 19:58:53Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
define('IN_APEXNET', true);
define('APEXNET_ROOT_PATH', realpath('../../../') . '/');
define('PHP_EXT', substr(strrchr(__FILE__, '.'), 1));

define('DEBUG', true);

require(APEXNET_ROOT_PATH . 'bootstrap.' . PHP_EXT);

use ApexNet\Foundation\Kernel;

// process the request and send a response
Kernel::create('movies');
Kernel::run(web_request::create());
