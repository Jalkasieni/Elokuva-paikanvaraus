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

/**
 * Basic RSS 2.0 feed generation
 */
class web_feed_rss extends web_feed
{
	const RSS_DATE = 'D, d M Y H:i:s O';

	public function create_feed(array $meta_data, $link_self)
	{
		$this->mime_type = 'application/rss+xml';

		$this->current = $this->feed->appendChild(new DOMElement('rss'));
		$this->current->setAttribute('version', '2.0');
		$this->current->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:atom', 'http://www.w3.org/2005/Atom');
		$this->current->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:dc', 'http://purl.org/dc/elements/1.1/');
		$this->current->setAttribute('xml:lang', $meta_data['language']);

		$this->current = $this->current->appendChild(new DOMElement('channel'));

		$link = $this->current->appendChild(new DOMElement('atom:link', '', 'http://www.w3.org/2005/Atom'));
		$link->setAttribute('href', $link_self);
		$link->setAttribute('rel', 'self');
		$link->setAttribute('type', $this->mime_type);

		$this->current->appendChild(new DOMElement('language', $meta_data['language']));
		$this->current->appendChild(new DOMElement('lastBuildDate', date(self::RSS_DATE, time())));

		$this->current->appendChild(new DOMElement('title'))->appendChild(new DOMCdataSection($meta_data['title']));
		$this->current->appendChild(new DOMElement('description'))->appendChild(new DOMCdataSection($meta_data['description']));

		$this->current->appendChild(new DOMElement('link', $meta_data['link']));
		$this->current->appendChild(new DOMElement('ttl', $meta_data['ttl']));

		$this->current->appendChild(new DOMElement('managingEditor', "{$meta_data['editor_mail']} ({$meta_data['editor_name']})"));
		$this->current->appendChild(new DOMElement('copyright', $meta_data['copyright']));
	}

	public function add_entry(array $entry)
	{
		$item = $this->current->appendChild(new DOMElement('item'));

		$item->appendChild(new DOMElement('title'))->appendChild(new DOMCdataSection($entry['title']));
		$item->appendChild(new DOMElement('description'))->appendChild(new DOMCdataSection($entry['description']));
		$item->appendChild(new DOMElement('link', $entry['link']));

		$item->appendChild(new DOMElement('pubDate', date(self::RSS_DATE, $entry['date'])));
		$item->appendChild(new DOMElement('dc:creator', '', 'http://purl.org/dc/elements/1.1/'))->appendChild(new DOMCdataSection($entry['author']));

		$item->appendChild(new DOMElement('guid', web_request::remove_sid($entry['link'])));
	}
}
