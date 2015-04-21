<?php
/**
*
* @package apexnet
* @copyright (c) 2015 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Foundation\Util;

abstract class web_feed
{
	// The associated request
	private $request;

	// DOMDocument and its current top level element
	protected $feed;
	protected $current;

	// The mime type to use for this feed
	protected $mime_type = false;

	/**
	 * Check for support of a particular feed type
	 */
	public static function support($type)
	{
		return (!empty($type) && class_exists("web_feed_{$type}"));
	}

	/**
	 * Factory for feeds
	 */
	public static function create(web_request $request, $type = 'rss', array $meta_data = array())
	{
		$class = "web_feed_{$type}";
		return new $class($request, $meta_data);
	}

	protected function __construct(web_request $request, array $meta_data)
	{
		$this->request = $request;

		$this->feed = $this->current = new DOMDocument('1.0', 'utf-8');
		$this->feed->formatOutput = true;

		if (!empty($meta_data))
		{
			$meta_data['link'] = $this->request->append_sid(isset($meta_data['link']) ? $meta_data['link'] : $this->request->base_url());

			$this->create_feed($meta_data, $this->request->request_url());
		}
	}

	public function response()
	{
		if (defined('DEBUG'))
			$this->feed->appendChild(new DOMComment(' Generation Time: ' . Util::time() . 's | Memory: ' . Util::memory() . ' '));
	
		if (!$this->mime_type)
		{
			$this->mime_type = 'application/xml';
			if (defined('DEBUG'))
				trigger_error('Serving a feed as generic xml', E_USER_WARNING);
		}

		return web_response::xml($this->request, $this->feed, $this->mime_type);
	}

	abstract public function create_feed(array $meta_data, $link_self);
	abstract public function add_entry(array $entry);
}
