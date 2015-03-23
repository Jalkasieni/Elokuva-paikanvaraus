<?php
/**
*
* @package svntools
* @version $Id: theaters.php 1198 2015-03-23 21:29:00Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

/**
 * Basic theater controller
 */
class movies_theaters_controller extends web_controller
{
	protected $model;

	public function prepare(web_request $request)
	{
		// Begin session if any
		$this->user->load($request);
		$this->model = $this->model('theater');
	}

	public function do_index(web_request $request)
	{
		$tpl_vars = array();
		return web_response::page($request, 'theaters_index', $this->user->pack($tpl_vars));
	}
}
