<?php
/**
*
* @package apexnet
* @copyright (c) 2015 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\Web\Auth;

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

/**
 * User authentication model interface
 */
interface BasicAuthModelInterface
{
	function setTablePrefix($prefix);
	function getRemoteURI($action);

	function retrieveUser($user_id);
	function checkLogin($username, $password);
}
