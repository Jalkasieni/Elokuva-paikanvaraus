<?php
/**
*
* @package svntools
* @version $Id: reservations.php 1293 2015-04-01 13:07:15Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

/**
 * Basic reservations controller
 */
class movies_reservations_controller extends web_controller
{
	const RESERVATIONS_LIMIT = 15;

	protected $model;
	protected $movie;

	public function prepare(web_request $request)
	{
		// Begin session if any
		$this->user->load($request);
		$this->model = $this->model('reservation');
		$this->movie = $this->model('movie');

		// access restrictions
		$this->acl->assign(array(
			'admin' => array(
				'permissions'	=> 'admin'
			),
			'index' => array(
				'permissions'	=> 'registered'
			),
			'create' => array(
				'permissions'	=> 'registered'
			),
			'remove' => array(
				'permissions'	=> 'registered'
			)
		));
	}

	public function do_index(web_request $request)
	{
		$response = web_response::create($request);
		$upcoming = $request->variable('upcoming', true , web_request::REQUEST);
		$offset = $response->paginate(self::RESERVATIONS_LIMIT, $this->model->count_user_reservations((int) $this->user['user_id'], $upcoming), 'reservations');
	
		return $response->body('reservations_index', $this->user->pack(array(
			'reservations'	=> $this->model->get_user_reservations((int) $this->user['user_id'], $upcoming, self::RESERVATIONS_LIMIT, $offset)
		)));
	}

	function do_create(web_request $request)
	{
		return web_response::error($request, 400);
	}

	function do_update_table(web_request $request)
	{
		$screening_id = $request->variable('screening_id', 0, web_request::REQUEST);
		if ($screening_id < 1)
			return web_response::error($request, 400);

		return web_response::json($request, json_encode($this->model->get_reservation_table($screening_id)));
	}

	function do_remove(web_request $request)
	{
		return web_response::error($request, 400);
	}
}