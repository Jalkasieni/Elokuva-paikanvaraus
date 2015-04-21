<?php
/**
*
* @package apexnet-bbclib
* @copyright (c) 2010 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\BBCode;

use Closure;

/**
 * Visitor for handling "magic urls" in TextNode's contents.
 *
 * This visitor is used by the the core to 'linkify' URLs automatically
 * the code is similar functionality in phpBB (version 3).
 *
 * @author crise
 * @since December 2013
 */
class LinkifyVisitor extends NullVisitor
{
	protected static $magic_url_match;

	// Optional callback (closure) supplied to process URLs
	protected $callback;

	public function __construct(Closure $callback = null)
	{
		if (!is_array(static::$magic_url_match))
		{
			// matches a xxxx://aaaaa.bbb.cccc. ...
			$preg_expression = "[a-z][a-z\d+]*:/{2}(?:(?:[a-z0-9\-._~!$&'(*+,;=:@|]+|%[\dA-F]{2})+|[0-9.]+|\[[a-z0-9.]+:[a-z0-9.]+:[a-z0-9.:]+\])(?::\d*)?(?:/(?:[a-z0-9\-._~!$&'(*+,;=:@|]+|%[\dA-F]{2})*)*(?:\?(?:[a-z0-9\-._~!$&'(*+,;=:@/?|]+|%[\dA-F]{2})*)?(?:\#(?:[a-z0-9\-._~!$&'(*+,;=:@/?|]+|%[\dA-F]{2})*)?";
			static::$magic_url_match[] = "#(^|[\\n\\t (>.])($preg_expression)#i";

			// matches a "www.xxxx.yyyy[/zzzz]" kinda lazy URL thing
			$preg_expression = "www\.(?:[a-z0-9\-._~!$&'(*+,;=:@|]+|%[\dA-F]{2})+(?::\d*)?(?:/(?:[a-z0-9\-._~!$&'(*+,;=:@|]+|%[\dA-F]{2})*)*(?:\?(?:[a-z0-9\-._~!$&'(*+,;=:@/?|]+|%[\dA-F]{2})*)?(?:\#(?:[a-z0-9\-._~!$&'(*+,;=:@/?|]+|%[\dA-F]{2})*)?";
			static::$magic_url_match[] = "#(^|[\\n\\t (>])($preg_expression)#i";
		}

		$this->callback = $callback;
	}

	public function visitText(TextNode $e)
	{
		$parent = $e->getParent();
		if ($parent->getTagName() != 'img' && $parent->getTagName() != 'url')
			$e->setText(preg_replace_callback(static::$magic_url_match, array($this, 'linkifyCallback'), $e->toText()));
	}

	protected function linkifyCallback($matches)
	{
		$url = htmlspecialchars_decode($matches[2]);
		$append	= '';
	
		// make sure no HTML entities were matched
		$chars = array('<', '>', '"');
		$split = false;

		foreach ($chars as $char)
		{
			$next_split = strpos($url, $char);
			if ($next_split !== false)
			{
				$split = ($split !== false) ? min($split, $next_split) : $next_split;
			}
		}

		if ($split !== false)
		{
			// an HTML entity was found, so the URL has to end before it
			$append	= substr($url, $split);
			$url = substr($url, 0, $split);
		}

		// if the last character of the url is a punctuation mark, exclude it from the url
		$last_char = $url[strlen($url) - 1];

		switch ($last_char)
		{
			case '.':
			case '?':
			case '!':
			case ':':
			case ',':
				$append = $last_char . $append;
				$url = substr($url, 0, -1);
			break;

			// set last_char to empty here, so the variable can be used later to
			// check whether a character was removed
			default:
				$last_char = '';
			break;
		}

		$short_url = (strlen($url) > 55) ? substr($url, 0, 39) . ' ... ' . substr($url, -10) : $url;

		if (strpos($url, '://') === false)
			$url = "http://$url";

		if ($this->callback)
		{
			// Allow deep integration of something like conditional session id for this visitor
			$callback = $this->callback;
			if (!$callback($url))
				return "{$matches[1]}$url$append";
		}

		$url = htmlspecialchars($url);
		$short_url = htmlspecialchars($short_url);
		$append = htmlspecialchars($append);

		return "{$matches[1]}<a rel=\"nofollow\" href=\"$url\" class=\"auto-link\">$short_url</a>$append";
	}
}
