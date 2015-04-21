<?php
/**
*
* @package apexnet-bbclib
* @license http://opensource.org/licenses/mit-license.php The MIT License (MIT)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\BBCode;

use Closure;

/**
 * Main BBCode parser
 *
 * Parser is the main parser class that constructs and stores the parse tree. Through this class
 * new bbcode definitions can be added, and documents may be parsed and converted to html/bbcode/plaintext, etc.
 *
 * @author jbowens
 */
class Parser
{
	// The list of bbcodes to be used by the parser.
	protected $codes = array();

	// Callback for additional URL validation
	protected $url_callback = null;

	/**
	 * Constructs an instance of the BBCode parser
	 */
	public function __construct() { }

	/**
	 * Adds a bbcode definition
	 *
	 * @param CodeDefinition $code	the CodeDefinition of the bbcode to add
	 */
	public function addCode(CodeDefinition $code)
	{
		$this->codes[$code->name()] = $code;
		return $this;
	}

	/**
	 * Convert a plain text string to a DocumentElement 
	 *
	 * @param string		$text		text string
	 *
	 * @return instance of DocumentElement
	 */
	public function textDocument($text)
	{
		$document = new DocumentElement();
		$document->addChild(new TextNode($text));
		return $document;
	}

	/**
	 * Constructs the parse tree from a string of bbcode markup.
	 * 
	 * @param string	$str   the bbcode markup to parse
	 *
	 * @return DocumentElement that represent $str
	 */
	public function parse($str)
	{
		$in_tag = false;
		$current = $root = new DocumentElement();
		$tokenizer = new Tokenizer($str);

		// tokens are either "[", "]" or a string that contains neither a opening bracket nor a closing bracket
		while (($token = $tokenizer->current()) != null)
		{
			if ($in_tag)
			{
				// explode by = in case there's an attribute
				$pieces = explode('=', $token, 2);
			
				$tag_name = strtolower($pieces[0]);
				$closing = false;

				// check if it's a closing tag
				if ($tag_name[0] == '/')
				{
					$tag_name = substr($tag_name, 1);
					$closing = true;
				}			

				if (isset($this->codes[$tag_name]) && $tokenizer->next() == "]" )
				{
					if ($closing)
					{
						$in_tag = false;

						// closing an element... move to this element's parent
						if ($tag_name != $current->getTagName())
						{
							$opened = $this->findParent($current, $tag_name);
							if ($opened != null && $opened->hasParent())
							{
								$current = $opened->getParent();

								while (($opened = $opened->getLastChildElement()) !== null)
								{
									$e = new ElementNode($opened->getTagName(), $opened->getCode());
									$e->setAttributes($opened->getAttributes());
									$current->addChild($e);
									$current = $e;
								}
							} else $current->addChild(new TextNode("[$token]"));
						}
						else
						{
							$current = $current->getParent();							
						}

						$tokenizer->advance(2);
						continue;
					} else {
						// new element
						$e = new ElementNode($tag_name, $this->codes[$tag_name]);

						if (isset($pieces[1]))
						{
							// build an associative array of valid (syntax) attributes
							$pieces = explode(' ', $pieces[1]);
							$attribs = array('default' => array_shift($pieces));
							$key = false;
							foreach ($pieces as $piece)
							{
								$piece = explode('=', $piece, 2);
								if (!isset($piece[1]))
								{
									if($key) $attribs[$key] .= $piece[0];
									continue;
								}
								$key = $piece[0];
								$attribs[$key] = $piece[1];
							}
							$e->setAttributes($attribs);
						}

						$current->addChild($e);
						$current = $e;
						$in_tag = false;

						$tokenizer->advance(2);
						continue;
					}
				}

				// the opening bracket that sent us in here was just text, so is this token
				$current->addChild(new TextNode("[$token"));
				$in_tag = false;

				$tokenizer->advance();
				continue;
			}

			if ($token == "[")
			{
				// assume that all brackets start an element until proven wrong
				$in_tag = true;
			} else $current->addChild(new TextNode($token));

			$tokenizer->advance();	
		}

		return $root;
	}

	/**
	 * Set a callback to add additional processing to links genrated from BBCode.
	 *
	 * @param $url_callback	Closure	PHP closure of type function (&$url) -> boolean, or null to disable
	 */
	public function setUrlCallback(Closure $url_callback = null)
	{
		$this->url_callback = $url_callback;
	}

	/**
	 * Get the current a url processing callback.
	 *
	 * @return PHP closure of type function (&$url) -> boolean, or null
	 */
	public function getUrlCallback()
	{
		return $this->url_callback;
	}

	/**
	 * Linkify the text in $text and return a string.
	 * Shorthand for using Parser::textDocument()->linkify()->toText()
	 */
	public function linkifyText($text, $nl2br = false, Closure $url_callback = null)
	{
		if (!$url_callback)
			$url_callback = $this->url_callback;

		// Here toText() and toHTML() are interchangeable, because the document only consists a TextNode.
		return $this->textDocument($text)->linkify($url_callback)->toText($nl2br);
	}

	/**
	 * Prepare BBCode for storage from external input, outputs BBCode or HTML (preview)
	 */
	public function parseStringStorage($text, $limit_nesting = true, $html = false, $convert_links = true, $encoding = 'UTF-8')
	{
		$text = htmlspecialchars($text, ENT_COMPAT, $encoding);
		$document = $this->parse($text)->clean($limit_nesting);
		if ($html && $convert_links)
			$document->linkify($this->url_callback);

		return $html ? $document->toHTML() : $document->toBBCode();
	}

	/**
	 * Display BBCode that has passed thorough Parser::parseStringStorage()
	 */
	public function parseStoredString($text, $convert_links = true)
	{
		$document = $this->parse($text);
		if ($convert_links)
			$document->linkify($this->url_callback);

		return $document->toHTML();
	}

	/**
	 * Remove all BBCode from a string that has passed thorough Parser::parseStringStorage()
	 */
	public function stripTags($text, $display = false, $convert_links = false)
	{
		$document = $this->parse($text);
		if ($display && $convert_links)
			$document->linkify($this->url_callback);

		$text = $document->toText($display);
		if (!$display)
			$text = htmlspecialchars_decode($text, ENT_COMPAT);
		return $text;
	}

	/**
	 * Traverses the parse tree upwards, going from parent to parent, until it finds a parent who has the given tag name.
	 * Returns the parent with the matching tag name if it exists, otherwise returns null.
	 *
	 * @param tag_name the tag name to search for
	 *
	 * @return the closest parent with the given tag name
	 */
	protected function findParent(ElementNode $start, $tag_name)
	{
		while ($start->getTagName() != $tag_name && $start->hasParent())
			$start = $start->getParent();

		return ($start->getTagName() == $tag_name) ? $start : null;
	}

	/**
	 * Adds a set of default, standard bbcode definitions commonly used across the web. 
	 */
	public function loadDefaultCodes()
	{
		// Basic text formatting
		$this->addCode(new CodeDefinition("b", function ($name, $attribs, $content) {
			return "<strong>$content</strong>";
		}));
		$this->addCode(new CodeDefinition("i", function ($name, $attribs, $content) {
			return "<em>$content</em>";
		}));
		$this->addCode(new CodeDefinition("u", function ($name, $attribs, $content) {
			return "<span style=\"text-decoration: underline\">$content</span>";
		}));
		$this->addCode(new CodeDefinition("s", function ($name, $attribs, $content) {
			return "<span style=\"text-decoration: line-through\">$content</span>";
		}));
		$this->addCode(new CodeDefinition("sub", function ($name, $attribs, $content) {
			return "<sub>$content</sub>";
		}));
		$this->addCode(new CodeDefinition("sup", function ($name, $attribs, $content) {
			return "<sup>$content</sup>";
		}));

		// Color
		$this->addCode(new CodeDefinition("color", function ($name, $attribs, $content) {
			if (!$attribs || empty($attribs['default']) || !preg_match("/^(#[a-fA-F0-9]{3,6}|[A-Za-z]+)$/", $attribs['default']))
				return false;

			return '<span style="color: '. $attribs['default'] ."\">$content</span>";
		}));

		// Lists
		$this->addCode(new CodeDefinition("ul", function ($name, $attribs, $content) {
			return "<ul>$content</ul>";
		}));
		$this->addCode(new CodeDefinition("ol", function ($name, $attribs, $content) {
			return "<ul>$content</ul>";
		}));
		$this->addCode(new CodeDefinition("li", function ($name, $attribs, $content) {
			return "<li>$content</li>";
		}));

		// Code & Quote
		$this->addCode(new CodeDefinition("code", function ($name, $attribs, $content) {
			return "</p><code>$content</code><p>";
		}, false));
		$this->addCode(new CodeDefinition("quote", function ($name, $attribs, $content) {
			$cite = '';
			if ($attribs && !empty($attribs['default']))
				$cite = "<cite>{$attribs['default']}:</cite>";
			return "</p><blockquote><p>{$cite}{$content}</p></blockquote><p>";
		}, true, 1));

		// Heading
		$this->addCode(new CodeDefinition('heading', function ($name, $attribs, $content) {
			$size = 5;
			if ($attribs && !empty($attribs['default']) && ctype_digit((string)$attribs['default']))
				$size = min((int)$attribs['default'], 7);
			return "</p><h$size>$content</h$size><p>";
		}));		

		// Text alignment
		$this->addCode(new CodeDefinition("left", function ($name, $attribs, $content) {
			return "</p><p style=\"display: block; text-align: left\">$content</p><p>";
		}));
		$this->addCode(new CodeDefinition("center", function ($name, $attribs, $content) {
			return "</p><p style=\"display: block; text-align: center\">$content</p><p>";
		}));
		$this->addCode(new CodeDefinition("right", function ($name, $attribs, $content) {
			return "</p><p style=\"display: block; text-align: right\">$content</p><p>";
		}));

		// Links
		$this->addCode(new CodeDefinition("url", function ($name, $attribs, $content) {
			if (!$attribs || empty($attribs['default']))
				$attribs['default'] = $content;

			$callback = $this->url_callback;
			if ($callback && !$callback($attribs['default']))
				return false;

			if (strncmp($attribs['default'], 'http', 4) != 0 && strncmp($attribs['default'], 'https', 5) != 0)
				return false;

			if (filter_var($attribs['default'], FILTER_VALIDATE_URL) === false)
				return false;

			return "<a href=\"{$attribs['default']}\">$content</a>";
		}));

		// Image
		$this->addCode(new CodeDefinition("img", function ($name, $attribs, $content) {
			if (!$attribs || empty($attribs['default']))
				$attribs['default'] = 'User posted image';

			if (strncmp($content, 'http', 4) != 0 && strncmp($content, 'https', 5) != 0)
				return false;

			if (filter_var($content, FILTER_VALIDATE_URL) === false)
				return false;

			return "<img src=\"{$content}\" alt=\"{$attribs['default']}\" />";
		}));
	}
}
