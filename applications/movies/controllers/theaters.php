<?php
/**
*
* @package svntools
* @version $Id: theaters.php 1228 2015-03-25 17:19:26Z crise $
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
	// How many entries per page
	const THEATERS_LIMIT = 15;

	protected $model;

	public function prepare(web_request $request)
	{
		// Begin session if any
		$this->user->load($request);
		$this->model = $this->model('theater');

		// access restrictions
		$this->acl->assign(array(
			'admin' => array(
				'permissions'	=> 'admin'
			),
			'add_teather' => array(
				'permissions'	=> 'admin'
			),
			'update_teather' => array(
				'permissions'	=> 'admin'
			),
			'remove_teather' => array(
				'permissions'	=> 'admin'
			),
			'manage_rooms' => array(
				'permissions'	=> 'admin'
			)
		));

	}

	public function do_index(web_request $request)
	{
		$tpl_vars = array();
		return web_response::page($request, 'theaters_index', $this->user->pack($tpl_vars));
	}

	public function do_admin(web_request $request)
	{
		$response = web_response::create($request);
		$offset = $response->paginate(self::THEATERS_LIMIT, $this->model->count_theaters(), 'theaters');

		return $response->body('theaters_admin', $this->user->pack(array(
			'theaters'		=> $this->model->get_theaters(true, self::THEATERS_LIMIT, $offset)
		)));
	}

	public function do_add_theater(web_request $request)
	{
		$form_data = array(
			'name'			=> $request->variable('name', '', web_request::POST),
			'description'	=> $request->variable('description', '', web_request::POST),
		);

		if ($request->is_set('submit') && !empty($form_data['name']))
		{
			if ($this->model->add_theater($form_data))
				return web_response::redirect($request, '/theaters/admin', 200, 'Theater added successfully.');
		}

		return web_response::page($request, 'theaters_admin_editor', $this->user->pack(array(
			'editor_action'	=> 'add',
			'form'			=> $form_data
		)));
	}
	public function do_manage_rooms(web_request $request)
	{	
		$theater_id = $request->variable('theater_id', 0, web_request::REQUEST);
		$room_id = $request->variable('room_id', 0, web_request::REQUEST);
		$action = $request->variable('action', '', web_request::REQUEST);
		
		if ($theater_id < 1)
			return web_response::redirect($request, '/theaters/admin', 302);

		if ($action == 'add')
		{
			$form_data = array(
				'name'	=> $request->variable('name', '', web_request::POST),
				'seats'	=> $request->variable('poster_url', '', web_request::POST),
				'rows'	=> $request->variable('description', '', web_request::POST),
			);
			if ($this->model->add_room($theater_id, $form_data))
				return web_response::redirect($request, '/theaters/admin', 200, 'Room added succesfully');
		}
		else if(($action == 'update') && !($room_id < 1))
		{
			$form_data = array(
				'name'	=> $request->variable('name', '', web_request::POST),
				'seats'	=> $request->variable('poster_url', '', web_request::POST),
				'rows'	=> $request->variable('description', '', web_request::POST),
			);
			if($this->model->update_room($room_id,$theater_id, $form_data))
				return web_response::redirect($request, '/theaters/admin', 200, 'Room updated successfully.');
		}
		else if (($action == 'remove') && !($room_id < 1))
		{
			if ($this->model->remove_room($room_id))
				return web_response::redirect($request, '/theaters/admin', 200, 'Room removed successfully.');
		}

		return web_response::redirect($request, '/theaters/admin', 302);
	}
}
