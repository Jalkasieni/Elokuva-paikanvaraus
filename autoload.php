<?php
/**
*
* @package apexnet
* @version $Id: autoload.php 827 2014-05-29 04:06:22Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Foundation\Loader\ClassLoader;
use ApexNet\Foundation\Autoloader;

{
	require(APEXNET_VENDOR_PATH . 'ApexNet/Foundation/Loader/ClassLoader.' . PHP_EXT);

	// the class loader must have at least static mapping to its own dependencies (and their dependancies) to work
	$loader = new ClassLoader(array(
		'ApexNet\Foundation\ErrorReporting'			=> APEXNET_VENDOR_PATH . 'ApexNet/Foundation/ErrorReporting.' . PHP_EXT,
		'ApexNet\Foundation\CoreException'			=> APEXNET_VENDOR_PATH . 'ApexNet/Foundation/CoreException.' . PHP_EXT,

		'ApexNet\Foundation\Facade'					=> APEXNET_VENDOR_PATH . 'ApexNet/Foundation/Facade.' . PHP_EXT,
		'ApexNet\Foundation\Cache'					=> APEXNET_VENDOR_PATH . 'ApexNet/Foundation/Cache.' . PHP_EXT,
		'ApexNet\Foundation\Config'					=> APEXNET_VENDOR_PATH . 'ApexNet/Foundation/Config.' . PHP_EXT
	));

	$loader->register();
	$loader->addPaths(array(APEXNET_VENDOR_PATH, APEXNET_OLD_PATH));

	Autoloader::bind($loader);
}
