<?php
/**
*
* @package db
* @copyright (c) 2015 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\Database;

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;


interface DBDriverInterface
{
	function connect($host, $port, $user, $password, $persistent);
	function database($name, $user, $password);

	function name();
	function version();
	function connected();
	function error();

	function escape($input);
	function query($sql);
	function fetchRow($result_id, $assoc);
	function freeResult($result_id);

	function affectedRows();

	function beginTransaction();
	function commitTransaction();
	function rollbackTransaction();

	function close();
}
