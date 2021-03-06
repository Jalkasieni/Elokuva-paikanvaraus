<?php
/**
*
* @package demo-movies
* @copyright (c) 2015 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Foundation\Config;
use ApexNet\Foundation\ArrayBitmask;

/**
 * Basic movies controller
 */
class movies_movies_controller extends web_controller
{
	// How many entries per page
	const MOVIES_LIMIT = 15;

	protected $model;

	public function prepare(web_request $request)
	{
		// Begin session if any
		$this->user->load($request);
		$this->model = $this->model('movie');

		// access restrictions
		$this->acl->assign(array(
			'admin' => array(
				'permissions'	=> 'admin'
			),
			'add_movie' => array(
				'permissions'	=> 'admin'
			),
			'update_movie' => array(
				'permissions'	=> 'admin'
			),
			'remove_movie' => array(
				'permissions'	=> 'admin'
			)
		));
	}

	public function do_index(web_request $request)
	{
		$response = web_response::create($request);
		$query = $request->variable('q', '', web_request::REQUEST);
		$tpl_data = array('query' => $query);

		if (empty($query))
		{
			$offset = $response->paginate(self::MOVIES_LIMIT, $this->model->count_movies(array('active')), 'movies');
			$tpl_data['movies'] = $this->model->get_movies(array('active'), true, self::MOVIES_LIMIT, $offset);
		}
		else
		{
			$offset = $response->paginate(self::MOVIES_LIMIT, $this->model->count_search($query), 'movies');
			$tpl_data['movies'] = $this->model->search_movies($query, true, self::MOVIES_LIMIT, $offset);
		}

		return $response->body('movies_index', $this->user->pack($tpl_data));
	}

	public function do_admin(web_request $request)
	{
		$response = web_response::create($request);
		$offset = $response->paginate(self::MOVIES_LIMIT, $this->model->count_all_movies(), 'movies');

		return $response->body('movies_admin', $this->user->pack(array(
			'movies'		=> $this->model->get_all_movies(true, self::MOVIES_LIMIT, $offset),
			'options_list'	=> Config::load('movie_options')
		)));
	}

	public function do_add_movie(web_request $request)
	{
		$form_data = array(
			'name'			=> $request->variable('name', '', web_request::POST),
			'poster_url'	=> $request->variable('poster_url', '', web_request::POST),
			'description'	=> $request->variable('description', '', web_request::POST),
			'options'		=> $request->variable('options', array(), web_request::POST),
		);

		if ($request->is_set('submit') && !empty($form_data['name']))
		{
			$errors = array();
			if (!empty($form_data['poster_url']) && !filter_var($form_data['poster_url'], FILTER_VALIDATE_URL))
				$errors[] = 'You did not provide a valid URL';

			if (empty($errors) && $this->model->add_movie($form_data))
				return web_response::redirect($request, '/movies/admin', 200, 'Movie added successfully.');

			$form_data['errors'] = $errors;
		}

		return web_response::page($request, 'movies_admin_editor', $this->user->pack(array(
			'editor_action'	=> 'add',
			'form'			=> $form_data,
			'options_list'	=> Config::load('movie_options')
		)));
	}

	public function do_update_movie(web_request $request)
	{
		$movie_id = $request->variable('movie_id', 0, web_request::REQUEST);

		if ($movie_id < 1)
			return web_response::redirect($request, '/movies/admin', 302);

		$current = array('name' => '', 'poster_url' => '', 'description' => '', 'options' => array());
		if (!$request->is_set('submit'))
		{
			$movie = $this->model->get_movie($movie_id, false);
			if ($movie !== false)
				$current = $movie;
		}

		$form_data = array(
			'movie_id'		=> (int) $movie_id,
			'name'			=> $request->variable('name', $current['name'], web_request::POST),
			'poster_url'	=> $request->variable('poster_url', $current['poster_url'], web_request::POST),
			'description'	=> $request->variable('description', $current['description'], web_request::POST),
			'options'		=> $request->variable('options', $current['options'], web_request::POST),
		);

		if ($request->is_set('submit') && !empty($form_data['name']))
		{
			$errors = array();
			if (!empty($form_data['poster_url']) && !filter_var($form_data['poster_url'], FILTER_VALIDATE_URL))
				$errors[] = 'You did not provide a valid URL';

			if (empty($errors) && $this->model->update_movie($movie_id, $form_data))
				return web_response::redirect($request, '/movies/admin', 200, 'Movie updated successfully.');

			$form_data['errors'] = $errors;
		}

		return web_response::page($request, 'movies_admin_editor', $this->user->pack(array(
			'editor_action'	=> 'update',
			'form'			=> $form_data,
			'options_list'	=> Config::load('movie_options')
		)));
	}

	public function do_remove_movie(web_request $request)
	{
		$movie_id = $request->variable('movie_id', 0, web_request::REQUEST);

		if ($movie_id < 1)
			return web_response::redirect($request, '/movies/admin', 302);

		if ($this->model->remove_movie($movie_id))
			return web_response::redirect($request, '/movies/admin', 200, 'Movie removed successfully.');

		return web_response::redirect($request, '/movies/admin', 302);
	}
}
