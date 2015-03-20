<?php
/**
*
* @package apexnet
* @version $Id: session.php 1176 2015-03-20 13:16:24Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

// session settings
return array(
	'auth'				=> 'basic',					// user authentication model (must implement at least auth_model)
	'strict'			=> true,					// strict mode for sessions (only with cookies, and only over http)

	'session_handler'	=> 'MySQL',					// session storage handler (MySQL or Cache, case-sensitive)
	'session_name'		=> 'apexnet_sid',			// session identifier (cookie name)
	'session_domain'	=> '.apexdc.net',			// session cookie domain
	'session_timeout'	=> 1440,					// the number of seconds session will remain valid

	// Either a fully qualified database config array or database name and/or table prefix
	'users'				=> array(
		'database'			=> 'apexdc_backend',		// Name of the database to use
		'table_prefix'		=> ''						// Prefix used for tables (optional)
	)
);
