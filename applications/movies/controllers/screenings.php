<?php
/**
*
* @package svntools
* @version $Id: screenings.php 1313 2015-04-01 21:45:25Z crise $
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
class movies_screenings_controller extends web_controller
{
	// How many entries per page
	const SCREENINGS_LIMIT = 15;

	protected $model;
	protected $movie;

	public function prepare(web_request $request)
	{
		// Begin session if any
		$this->user->load($request);
		$this->model = $this->model('screening');
		$this->movie = $this->model('movie');
		$this->theater = $this->model('theater');

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
		$theater_id = $request->variable('theater_id', 0, web_request::REQUEST);
		$movie_id = $request->variable('movie_id', 0, web_request::REQUEST);
		$upcoming = $request->variable('upcoming', true , web_request::REQUEST);

		if ($theater_id < 1)
			return web_response::redirect($request, '/theaters', 302);

		$offset = $response->paginate(self::SCREENINGS_LIMIT, $this->model->count_screenings_theater($theater_id, $movie_id, $upcoming), 'screenings');

		return $response->body('screenings_index', $this->user->pack(array(
			'theater'		=> $this->theater->get_theater($theater_id, true),
			'screenings'	=> $this->model->get_screenings_theater($theater_id, $movie_id, $upcoming, self::SCREENINGS_LIMIT, $offset)
		)));
	}

	public function do_movie(web_request $request)
	{
		$response = web_response::create($request);
		$movie_id = $request->variable('movie_id', 0, web_request::REQUEST);
		$upcoming = $request->variable('upcoming', true, web_request::REQUEST);
		$theater_id = $request->variable('theater_id', 0, web_request::REQUEST);

		if ($movie_id < 1)
			return web_response::redirect($request, '/movies', 302);

		$offset = $response->paginate(self::SCREENINGS_LIMIT, $this->model->count_screenings($movie_id, $theater_id, $upcoming), 'screenings');

		return $response->body('screenings_movie', $this->user->pack(array(
			'movie'				=> $this->movie->get_movie($movie_id, true),
			'screenings'		=> $this->model->get_screenings($movie_id, $theater_id, $upcoming, self::SCREENINGS_LIMIT, $offset)
		)));
	}

	public function do_admin(web_request $request)
	{
		$response = web_response::create($request);
		$movie_id = $request->variable('movie_id', 0, web_request::REQUEST);
		$upcoming = $request->variable('upcoming', true, web_request::REQUEST);

		if ($movie_id < 1)
			return web_response::redirect($request, '/movies/admin', 302);

		$offset = $response->paginate(self::SCREENINGS_LIMIT, $this->model->count_screenings($movie_id, 0, $upcoming), 'screenings');

		return $response->body('screenings_admin', $this->user->pack(array(
			'movie'				=> $this->movie->get_movie($movie_id, false),
			'screenings'		=> $this->model->get_screenings($movie_id, 0, $upcoming, self::SCREENINGS_LIMIT, $offset)
		)));
	}

	public function do_add_screening(web_request $request)
	{
		$movie_id = $request->variable('movie_id', 0, web_request::REQUEST);
		if ($movie_id < 1)
			return web_response::redirect($request, '/movies/admin', 302);

		$form_data = array(
			'movie_id'		=> (int) $movie_id,
			'theater_id'	=> $request->variable('theater_id', 0, web_request::POST),
			'start'			=> strtotime($request->variable('start', date('Y-m-d\TH:i:s', ceil((time() + 60 * 60) / 300) * 300), web_request::POST)),
			'end'			=> strtotime($request->variable('end', date('Y-m-d\TH:i:s', ceil((time() + 180 * 60) / 300) * 300), web_request::POST)),
			'room_id'		=> $request->variable('room_id', 0, web_request::POST),
		);

		if ($request->is_set('submit') && $form_data['start'] != 0 && $form_data['end'] != 0)
		{
			if ($this->model->add_screening($movie_id, $form_data))
				return web_response::redirect($request,  "/screenings/admin?movie_id=$movie_id", 200, 'Screening added successfully.');
		}

		$tpl_data = array(
			'editor_action'		=> 'add',
			'form'				=> $form_data,
			'theater_list'		=> $this->theater->get_theater_list()
		);

		if ($form_data['theater_id'] > 0)
			$tpl_data['room_list'] = $this->theater->get_room_list($form_data['theater_id']);

		return web_response::page($request, 'screenings_admin_editor', $this->user->pack($tpl_data));
	}

	public function do_update_screening(web_request $request)
	{
		$movie_id = $request->variable('movie_id', 0, web_request::REQUEST);
		$screening_id = $request->variable('screening_id', 0, web_request::REQUEST);

		if ($screening_id < 1)
			return web_response::redirect($request, "/screenings/admin?movie_id=$movie_id", 302);

		$current = array('start' => ceil((time() + 60 * 60) / 300) * 300, 'end' => ceil((time() + 180 * 60) / 300) * 300, 'movie_id' => 0, 'theater_id' => 0, 'room_id' => 0);
		if (!$request->is_set('submit'))
		{
			$screening = $this->model->get_screening($screening_id);
			if ($screening !== false)
				$current = $screening;
		}

		$form_data = array(
			'movie_id'			=> (int) $movie_id,
			'screening_id'		=> (int) $screening_id,
			'theater_id'		=> $request->variable('theater_id', $current['theater_id'], web_request::POST),
			'start'				=> strtotime($request->variable('start', date('Y-m-d\TH:i:s', $current['start']), web_request::POST)),
			'end'				=> strtotime($request->variable('end', date('Y-m-d\TH:i:s', $current['end']), web_request::POST)),
			'room_id'			=> $request->variable('room_id', $current['room_id'], web_request::POST),
		);

		if ($request->is_set('submit') && $form_data['start'] != 0 && $form_data['end'] != 0)
		{
			if ($this->model->update_screening($movie_id, $screening_id, $form_data))
				return web_response::redirect($request,  "/screenings/admin?movie_id=$movie_id", 200, 'Screening updated successfully.');
		}

		$tpl_data = array(
			'editor_action'		=> 'update',
			'form'				=> $form_data,
			'theater_list'		=> $this->theater->get_theater_list()
		);

		if ($form_data['theater_id'] > 0)
			$tpl_data['room_list'] = $this->theater->get_room_list($form_data['theater_id']);

		return web_response::page($request, 'screenings_admin_editor', $this->user->pack($tpl_data));
	}

	public function do_remove_screening(web_request $request)
	{
		$movie_id = $request->variable('movie_id', 0, web_request::REQUEST);
		$screening_id = $request->variable('screening_id', 0, web_request::REQUEST);

		if ($screening_id < 1)
			return web_response::redirect($request,  "/screenings/admin?movie_id=$movie_id", 302);

		if ($this->model->remove_screening($movie_id, $screening_id))
			return web_response::redirect($request, "/screenings/admin?movie_id=$movie_id", 200, 'Screening removed successfully.');

		return web_response::redirect($request, "/screenings/admin?movie_id=$movie_id", 302);
	}

	public function ajax_load_rooms(web_request $request)
	{
		$theater_id = $request->variable('theater_id', 0, web_request::REQUEST);
		if ($theater_id < 1)
			return web_response::error($request, 400);

		return web_response::json($request, json_encode($this->theater->get_room_list($theater_id)));
	}
}
