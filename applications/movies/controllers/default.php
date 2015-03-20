<?php
/**
*
* @package svntools
* @version $Id: default.php 1178 2015-03-20 17:41:15Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

/**
 * Basic empty controller
 */
class movies_default_controller extends web_controller
{
	protected $model;

	public function prepare(web_request $request)
	{
		// Begin session if any
		$this->user->load($request);
		$this->model = $this->model('default');
	}

	public function do_index(web_request $request)
	{
		$tpl_vars = array();
		return web_response::template($request, 'default_index', $this->user->pack($tpl_vars));
	}
}
