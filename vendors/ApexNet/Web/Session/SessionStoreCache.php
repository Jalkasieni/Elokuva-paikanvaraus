<?php
/**
*
* @package apexnet
* @version $Id: SessionStoreCache.php 945 2014-10-21 20:46:13Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\Web\Session;

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Foundation\Cache;
use ApexNet\Foundation\CoreException;

use SessionHandlerInterface;

/**
 * Session handler for the chosen cache backend
 */
class SessionStoreCache implements SessionHandlerInterface
{
	private $prefix;
	private $ttl = 0;

	public function __construct($store)
	{
		if (!Cache::active())
			throw new CoreException('The chosen storage method for sessions is not available');

		$this->ttl = ini_get('session.gc_maxlifetime');
	}

	/**
	 * Functions for session_set_save_handler
	 */
	public function open($save_path, $sess_name)
	{
		$this->prefix = "session/$sess_name/";
		return true;
	}

	public function close()
	{
		return true;
	}

	public function read($sid)
	{
		return Cache::get($this->prefix . $sid);
	}

	public function write($sid, $data)
	{
		return Cache::put($this->prefix . $sid, $data, $this->ttl);
	}

	public function destroy($sid)
	{
		return Cache::delete($this->prefix . $sid);
	}

	public function gc($max_ttl)
	{
		return true;
	}
}
