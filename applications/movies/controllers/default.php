<?php
/**
*
* @package svntools
* @version $Id: default.php 1214 2015-03-25 11:00:58Z crise $
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
	const FEATURED_LIMIT = 5;

	protected $model;
	protected $movies;

	public function prepare(web_request $request)
	{
		// Begin session if any
		$this->user->load($request);
		$this->model = $this->model('default');
		$this->movies = $this->model('movies');
	}

	public function do_index(web_request $request)
	{
		$response = web_response::create($request);
	
		return $response->body('default_index', $this->user->pack(array(
			'featured'	=> $this->movies->get_movies(array('active', 'featured'), true, self::FEATURED_LIMIT)
		)));
	}
}
