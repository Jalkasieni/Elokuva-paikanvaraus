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
 * The main top level document element returned by Parser::parse()
 * 
 * A DocumentElement object represents the root of a document tree. All documents represented by
 * this document model should have one as its root.
 *
 * @author jbowens
 */
class DocumentElement extends ElementNode
{
	/**
	 * Constructs the document element node
	 */
	public function __construct()
	{
		parent::__construct('Document');
	}

	public function accept(NodeVisitor $visitor)
	{
		$visitor->visitDocument($this);
		return $this;
	}

	public function clean($limitNesting = true)
	{
		if ($limitNesting)
			$this->accept(new NestLimitVisitor());

		$this->accept(new EmptyVisitor());
		return $this;
	}

	public function linkify(Closure $callback = null)
	{
		$this->accept(new LinkifyVisitor($callback));
		return $this;
	}

	/**
	 * (non-PHPdoc)
	 * @see BBCode.ElementNode::toText()
	 *
	 * Returns the text representation of this document
	 *
	 * @return this document's text representation
	 */
	public function toText($nl2br = false)
	{
		$text = parent::toText();
		if ($nl2br)
			$text = static::nl2br($text);
		return $text;
	}

	/**
	 * (non-PHPdoc)
	 * @see BBCode.ElementNode::toBBCode()
	 *
	 * Returns the BBCode representation of this document
	 *
	 * @return this document's bbcode representation
	 */
	public function toBBCode()
	{
		$text = '';
		foreach ($this->children as $child)
			$text .= $child->toBBCode();
		return $text;
	}

	/**
	 * (non-PHPdoc)
	 * @see BBCode.ElementNode::toHTML()
	 * 
	 * Documents don't add any html. They only exist as a container for their children, so toHTML() simply iterates through the
	 * document's children, returning their html.
	 * 
	 * @return the HTML representation of this document
	 */
	public function toHTML($nl2br = true)
	{
		$text = parent::toHTML();
		if ($nl2br)
			$text = static::nl2br($text);
		return $text;
	}

	/**
	 * Process line breaks for display purposes, either as text or HTML
	 */
	protected static function nl2br($text)
	{
		$html = '<p>'. nl2br($text, false) .'</p>';
		$html = str_replace(array("<br>\n<br>\n<br>", "<br>\n<br>"), "</p>\n<p>", $html);
		$html = str_replace(array("<br>\n<li>", "</li><br>\n", "</ul><br>\n", "</ol><br>\n"), array("\n<li>", "</li>\n", "</ul>\n", "</ol>\n"), $html);
		$html = str_replace('<p></p>', '', $html);
		return $html;
	}

	/**
	 * FOR DEBUG ONLY. This method returns the entire parse tree in a human-readable format.
	 */
	public function toString()
	{
		return print_r($this->root, true);
	}
}
