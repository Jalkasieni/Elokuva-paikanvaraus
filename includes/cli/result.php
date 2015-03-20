<?php
/**
*
* @package cli
* @version $Id: result.php 798 2014-05-26 14:04:33Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

/**
 * Exception thrown when running CLI scripts in foreign environment
 */
class cli_result extends Exception
{
	public function __construct($code, Exception $previous = null)
	{
		$message = 'Script execution ' . ($code == 0) ? 'finished successfully.' : 'failed with errors!';
		parent::__construct($message, $code, $previous);
	}
}
