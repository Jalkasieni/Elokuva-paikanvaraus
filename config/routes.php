<?php
/**
*
* @package apexnet
* @version $Id: routes.php 798 2014-05-26 14:04:33Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

// automatically generate routes for every controller
return web_controller::routes();
