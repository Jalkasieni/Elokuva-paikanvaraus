<?php
/**
*
* @package apexnet
* @version $Id: bootstrap.php 1126 2015-03-19 02:44:34Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Foundation\Autoloader;
use ApexNet\Foundation\Cache;
use ApexNet\Foundation\Config;

// php environment constants
define('APEXNET_MEMORY_START', memory_get_usage());
define('APEXNET_TIME_START', microtime(true));

// core framework paths
if (!defined('APEXNET_ROOT_PATH')) define('APEXNET_ROOT_PATH', __DIR__ . '/');
if (!defined('APEXNET_VENDOR_PATH')) define('APEXNET_VENDOR_PATH', APEXNET_ROOT_PATH . 'vendors/');

if (!defined('APEXNET_APPS_ROOT')) define('APEXNET_APPS_ROOT', APEXNET_ROOT_PATH . 'applications/');
if (!defined('APEXNET_CACHE_PATH')) define('APEXNET_CACHE_PATH', APEXNET_ROOT_PATH . 'cache/');
if (!defined('APEXNET_CONFIG_PATH')) define('APEXNET_CONFIG_PATH', APEXNET_ROOT_PATH . 'config/');

// deprecated include path
define('APEXNET_OLD_PATH', APEXNET_ROOT_PATH . 'includes/');

// require the necessary files for the framework that can't be autoloaded
require(APEXNET_ROOT_PATH . 'autoload.' . PHP_EXT);
require(APEXNET_VENDOR_PATH . 'password.' . PHP_EXT);

// Makes sure Cache Facade is loaded and active if supported
Cache::load();
