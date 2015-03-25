<?php
/**
*
* @package svntools
* @version $Id: install.php 1218 2015-03-25 14:52:40Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

// installation config
return array(
	'version'		=> '1.0',
	'models'		=> array('theater', 'movie', 'screening', 'reservation')
);
