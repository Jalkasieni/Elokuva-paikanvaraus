<?php
/**
*
* @package svntools
* @version $Id: screenings.php 1246 2015-03-28 06:49:59Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

/**
 * Basic screening controller
 */
class screenings_screenings_controller extends web_controller
{
	// How many entries per page
	const SCREENINGS_LIMIT = 15;

	protected $model;

	public function prepare(web_request $request)
	{
		// Begin session if any
		$this->user->load($request);
		$this->model = $this->model('screenings');

		// access restrictions
		$this->acl->assign(array(
			'admin' => array(
				'permissions'	=> 'admin'
			),
			'add_screening' => array(
				'permissions'	=> 'admin'
			),
			'update_screening' => array(
				'permissions'	=> 'admin'
			),
			'remove_screening' => array(
				'permissions'	=> 'admin'
			)
		));

	}

	public function do_index(web_request $request)
	{
		$response = web_response::create($request);
		$theater = $request->variable('theater', 0, web_request::REQUEST);
		$tpl_data = array('theater' => $theater);

		$offset = $response->paginate(self::SCREENINGS_LIMIT, $this->model->count_screenings_user($theater, null, true), 'screenings');
		$tpl_data['screenings'] = $this->model->get_screenings_user($theater, null, true, self::SCREENINGS_LIMIT, $offset);

		return $response->body('screenings_index', $this->user->pack($tpl_data));
	}
	
	public function do_frontpage(web_request $request)
	{
		$response = web_response::create($request);
		$theater = $request->variable('theater', 0, web_request::REQUEST);

		$offset = $response->paginate(self::SCREENINGS_LIMIT, $this->model->count_screenings_user($theater, null, true), 'screenings');
		$tpl_data = $this->model->get_screenings_user($theater, null, true, self::SCREENINGS_LIMIT, $offset);

		return $response->body('screenings_frontpage', $this->user->pack($tpl_data));
	}

	public function do_admin(web_request $request)
	{
		$response = web_response::create($request);
		$upcoming = $request->variable('upcoming', true , web_request::REQUEST);
		$offset = $response->paginate(self::SCREENINGS_LIMIT, $this->model->count_screenings_user($theater, null, $upcoming), 'screenings');

		return $response->body('screenings_admin', $this->user->pack(array(
			'screenings'		=> $this->model->get_screenings_user($theater, null, $upcoming, self::SCREENINGS_LIMIT, $offset)
		)));
	}

	public function do_add_screening(web_request $request)
	{
		$form_data = array(
			'start'			=> $request->variable('start', '', web_request::POST),
			'end'			=> $request->variable('end', '', web_request::POST),
			'movie_id'		=> $request->variable('movie_id', '', web_request::POST),
			'room_id'		=> $request->variable('room_id', '', web_request::POST),
		);

		if ($request->is_set('submit') && !empty($form_data['start']) && !empty($form_data['end']))
		{
			if ($this->model->add_screening($form_data))
				return web_response::redirect($request, '/screenings/admin', 200, 'Screening added successfully.');
		}

		return web_response::page($request, 'screenings_admin_editor', $this->user->pack(array(
			'editor_action'	=> 'add',
			'form'			=> $form_data
		)));
	}

	public function do_update_screening(web_request $request)
	{
		$screening_id = $request->variable('screening_id', 0, web_request::REQUEST);

		if ($screening_id < 1)
			return web_response::redirect($request, '/screenings/admin', 302);

		$current = array('start' => '', 'end' => '', 'movie_id' => '', 'room_id' => '');
		if (!$request->is_set('submit'))
		{
			$screening = $this->model->get_screening($screening_id);
			if ($screening !== false)
				$current = $screening;
		}

		$form_data = array(
			'screening_id'		=> (int) $screening_id,
			'start'				=> $request->variable('start', $current['start'], web_request::POST),
			'end'				=> $request->variable('end', $current['end'], web_request::POST),
			'movie_id'			=> $request->variable('movie_id', $current['movie_id'], web_request::POST),
			'room_id'			=> $request->variable('room_id', $current['room_id'], web_request::POST),
		);

		if ($request->is_set('submit') && !empty($form_data['start']) && !empty($form_data['end']))
		{
			if ($this->model->update_screening($screening_id, $form_data))
				return web_response::redirect($request, '/screenings/admin', 200, 'Screening updated successfully.');
		}

		return web_response::page($request, 'screenings_admin_editor', $this->user->pack(array(
			'editor_action'	=> 'update',
			'form'			=> $form_data
		)));
	}

	public function do_remove_screening(web_request $request)
	{
		$screening_id = $request->variable('screening_id', 0, web_request::REQUEST);

		if ($screening_id < 1)
			return web_response::redirect($request, '/screenings/admin', 302);

		if ($this->model->remove_screening($screening_id))
			return web_response::redirect($request, '/screenings/admin', 200, 'Screening removed successfully.');

		return web_response::redirect($request, '/screenings/admin', 302);
	}
}
