<?php
/**
*
* @package apexnet-bbclib
* @version $Id: TextNode.php 800 2014-05-27 03:43:58Z crise $
* @license http://opensource.org/licenses/mit-license.php The MIT License (MIT)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\BBCode;

/**
 * Represents a piece of text data. TextNodes never have children.
 *
 * @author jbowens
 */
class TextNode extends Node
{
	/* The value of this text node */
	protected $value;

	/**
	 * Constructs a text node from its text string
	 * 
	 * @param string $value
	 */
	public function __construct($value)
	{
		$this->value = $value;
	}

	public function accept(NodeVisitor $visitor)
	{
		$visitor->visitText($this);
		return $this;
	}

	/**
	 * Check if this element is empty
	 *
	 * @return true on empty element, false otherwise
	 */
	public function isEmpty()
	{
		return empty($this->value);
	}

	/**
	 * Append more text to this element
	 */
	public function addText($text)
	{
		$this->value .= $text;
	}

	/**
	 * Replace the content of this element
	 */
	public function setText($text)
	{
		$this->value = $text;
	}

	/**
	 * (non-PHPdoc)
	 * @see BBCode.Node::toText()
	 * 
	 * Returns the text representation of this node.
	 *
	 * @return this node represented as text
	 */
	public function toText()
	{
		return $this->value;
	}

	/**
	 * (non-PHPdoc)
	 * @see BBCode.Node::toBBCode()
	 * 
	 * Returns the bbcode representation of this node. (Just its value)
	 * 
	 * @return this node represented as bbcode
	 */
	public function toBBCode()
	{
		return $this->value;
	}

	/**
	 * (non-PHPdoc)
	 * @see BBCode.Node::toHTML()
	 * 
	 * Returns the html representation of this node. (Just its value)
	 * 
	 * @return this node represented as HTML
	 */
	public function toHTML()
	{
		return $this->value;
	}
}
