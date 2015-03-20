<?php
/**
*
* @package svntools
* @version $Id: atom.php 798 2014-05-26 14:04:33Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

/**
 * Basic Atom feed generation
 */
class web_feed_atom extends web_feed
{
	const ATOM_DATE = 'Y-m-d\TH:i:sP';

	public function create_feed(array $meta_data, $link_self)
	{
		$this->mime_type = 'application/atom+xml';

		$this->current = $this->feed->appendChild(new DOMElement('feed', '', 'http://www.w3.org/2005/Atom'));
		$this->current->setAttribute('xml:lang', $meta_data['language']);

		$link = $this->current->appendChild(new DOMElement('link'));
		$link->setAttribute('href', $link_self);
		$link->setAttribute('rel', 'self');
		$link->setAttribute('type', $this->mime_type);

		$link = $this->current->appendChild(new DOMElement('link'));
		$link->setAttribute('href', $meta_data['link']);
		$link->setAttribute('rel', 'alternate');
		$link->setAttribute('type', 'text/html');

		$this->current->appendChild(new DOMElement('title'))->appendChild(new DOMCdataSection($meta_data['title']));
		$this->current->appendChild(new DOMElement('subtitle'))->appendChild(new DOMCdataSection($meta_data['description']));
		
		$this->current->appendChild(new DOMElement('updated', date(self::ATOM_DATE, time())));
		
		$author = $this->current->appendChild(new DOMElement('author'));
		$author->appendChild(new DOMElement('name', $meta_data['editor_name']));
		$author->appendChild(new DOMElement('uri', $meta_data['link']));
		$author->appendChild(new DOMElement('email', $meta_data['editor_mail']));

		$this->current->appendChild(new DOMElement('id', $this->make_guid(web_request::remove_sid($link_self))));
	}

	public function add_entry(array $entry)
	{
		$item = $this->current->appendChild(new DOMElement('entry'));

		$link = $item->appendChild(new DOMElement('link'));
		$link->setAttribute('href', $entry['link']);
		$link->setAttribute('rel', 'alternate');
		$link->setAttribute('type', 'text/html');

		$item->appendChild(new DOMElement('title'))->appendChild(new DOMCdataSection($entry['title']));

		$content = $item->appendChild(new DOMElement('content'));
		$content->appendChild(new DOMCdataSection($entry['description']));
		$content->setAttribute('type', 'html');
		$content->setAttribute('xml:base', $entry['link']);

		$item->appendChild(new DOMElement('updated', date(self::ATOM_DATE, $entry['date'])));

		$author = $item->appendChild(new DOMElement('author'));
		$author->appendChild(new DOMElement('name'))->appendChild(new DOMCdataSection($entry['author']));

		$item->appendChild(new DOMElement('id', $this->make_guid(web_request::remove_sid($entry['link']))));
	}

	protected function make_guid($base_identifier)
	{
		$key = md5($base_identifier);
		$uuid  = substr($key, 0, 8) . '-';
		$uuid .= substr($key, 8, 4) . '-';
		$uuid .= substr($key, 12, 4) . '-';
		$uuid .= substr($key, 16, 4) . '-';
		$uuid .= substr($key, 20, 12);

		return "urn:uuid:$uuid";
	}
}
