<?php
/**
*
* @package apexnet
* @version $Id: EngineAPC.php 983 2015-01-10 16:16:56Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\Foundation\Cache;

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

/**
 * APC (Advaned PHP Cache) cache engine
 */
class EngineAPC
{
	private $prefix = 'apexnet/';
	private $ttl = 0;

	public function __construct()
	{
		$this->ttl = ini_get('apc.user_ttl');
	}

	public function load($resource = false)
	{
		return $this->active();
	}

	public function active()
	{
		return extension_loaded('apc') || extension_loaded('apcu');
	}

	public function get($key, $default = '')
	{
		$exists = false;
		$value = apc_fetch($this->prefix . $key, $exists);
		return ($exists ? $value : $default);
	}

	public function put($key, $value, $ttl = false)
	{
		if ($ttl === false)
			$ttl = $this->ttl;

		 return apc_store($this->prefix . $key, $value, $ttl);
	}

	public function delete($key)
	{
		return apc_delete($this->prefix . $key);
	}
}
