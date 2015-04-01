<?php
/**
*
* @package svntools
* @version $Id: reservations.php 1301 2015-04-01 16:25:47Z crise $
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
	protected $screening;

	public function prepare(web_request $request)
	{
		// Begin session if any
		$this->user->load($request);
		$this->model = $this->model('reservation');
		$this->screening = $this->model('screening');

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
			'reserve_seat' => array(
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
		$upcoming = $request->variable('upcoming', true, web_request::REQUEST);
		$offset = $response->paginate(self::RESERVATIONS_LIMIT, $this->model->count_user_reservations((int) $this->user['user_id'], $upcoming), 'reservations');
	
		return $response->body('reservations_index', $this->user->pack(array(
			'reservations'	=> $this->model->get_user_reservations((int) $this->user['user_id'], $upcoming, self::RESERVATIONS_LIMIT, $offset)
		)));
	}

	function do_create(web_request $request)
	{
		$screening_id = $request->variable('screening_id', 0, web_request::REQUEST);
		if ($screening_id < 1)
			return web_response::redirect($request, '/movies/', 302);

		return web_response::page($request, 'reservations_create', $this->user->pack(array(
			'screening'					=> $this->screening->get_screening_theater($screening_id),
			'reservation_table'			=> $this->model->get_reservation_table($screening_id)
		)));
	}

	function do_reserve_seat(web_request $request)
	{
		$meta_data = array(
			'screening_id'		=> $request->variable('screening_id', 0, web_request::REQUEST),
			'user_id'			=> $request->variable('user_id', (int) $this->user['user_id'], web_request::POST),
			'seat'				=> $request->variable('seat', 0, web_request::POST),
			'row'				=> $request->variable('row', 0, web_request::POST)
		);

		if ($meta_data['screening_id'] < 1 || $meta_data['seat'] < 1 || $meta_data['row'] < 1)
			return web_response::error($request, 400);

		if ($meta_data['user_id'] < 1 || (!$this->user->admin() && $this->user['user_id'] != $meta_data['user_id']))
			return web_response::error($request, 400);

		if ($this->model->add_reservation($meta_data))
			return web_response::json($request, json_encode($this->model->get_reservation_table($meta_data['screening_id'])));

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
		$reservation_id = $request->variable('reservation_id', 0, web_request::REQUEST);
		$user_id = $request->variable('user_id', (int) $this->user['user_id'], web_request::REQUEST);
		$redirect = (($this->user->admin() && $this->user['user_id'] != $user_id) ? "/reservations/?user_id=$user_id" : '/reservations/');

		if ($reservation_id < 1 || $user_id < 1 || (!$this->user->admin() && $this->user['user_id'] != $user_id))
			return web_response::redirect($request, $redirect, 302);

		if ($this->model->remove_reservation($reservation_id, $user_id))
			return web_response::redirect($request, $redirect, 200, 'Reservation removed successfully.');

		return web_response::redirect($request, $redirect, 302);
	}
}
