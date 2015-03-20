<?php
/**
*
* @package apexnet-bbclib
* @version $Id: ElementNode.php 800 2014-05-27 03:43:58Z crise $
* @license http://opensource.org/licenses/mit-license.php The MIT License (MIT)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\BBCode;

/**
 * Element in the parse tree, that is not document element or plain text.
 * 
 * An element within the tree. Consists of a tag name which defines the type of the
 * element and any number of Node children. It also contains a CodeDefinition matching
 * the tag name of the element.
 *
 * Known subclasses: DocumentElement
 *
 * @author jbowens
 */
class ElementNode extends Node
{
	/* The tagname of this element, for i.e. "b" in [b]bold[/b] */
	protected $tag_name;

	/* The attributes, if any, of this element node */
	protected $attributes = false;

	/* The child nodes contained within this element */
	protected $children = array();

	/* The code definition that defines this element's behavior */
	protected $code;

	/**
	 * Constructs the element node
	 */
	public function __construct($tag_name, CodeDefinition $code = null)
	{
		$this->tag_name = $tag_name;
		$this->code = $code;
	}

	public function accept(NodeVisitor $visitor)
	{
		$visitor->visitElement($this);
		return $this;
	}

	/**
	 * Gets the CodeDefinition that defines this element.
	 * 
	 * @return this element's code definition
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * Returns the tag name of this element.
	 * 
	 * @return the element's tag name
	 */
	public function getTagName()
	{
		return $this->tag_name;
	}

	/**
	 * Returns the attributes of this element.
	 *
	 * @return the attribute of this element
	 */
	public function getAttributes()
	{
		return $this->attributes;
	}

	/**
	 * Returns all the children of this element.
	 * 
	 * @return an array of this node's child nodes
	 */
	public function getChildren()
	{
		return $this->children;
	}

	/**
	 * Returns last child that is not plain text.
	 * 
	 * @return last child that is not a text node or null
	 */
	public function getLastChildElement()
	{
		$count = sizeof($this->children);
		for($i = $count - 1; $i >= 0; --$i)
		{
			if ($this->children[$i] instanceof ElementNode)
				return $this->children[$i];
		}

		return null;
	}

	/**
	 * Check if this element is empty
	 *
	 * @return true on empty element, false otherwise
	 */
	public function isEmpty()
	{
		$empty = true;
		foreach ($this->children as $child)
		{
			if (!$empty) break;
			$empty = $child->isEmpty();
		}

		return $empty;
	}

	/**
	 * (non-PHPdoc)
	 * @see BBCode.Node::toText()
	 * 
	 * Returns the element as text (not including any bbcode markup)
	 * 
	 * @return the plain text representation of this node
	 */
	public function toText()
	{
		$text = '';
		foreach ($this->children as $child)
			$text .= $child->toText();
		return $text;
	}

	/**
	 * (non-PHPdoc)
	 * @see BBCode.Node::toBBCode()
	 * 
	 * Returns the element as bbcode (with all unclosed tags closed)
	 * 
	 * @return the bbcode representation of this element
	 */
	public function toBBCode()
	{
		$text = '['.$this->tag_name;
		if ($this->attributes !== false)
		{
			foreach ($this->attributes as $name => $value)
				$text .= ($name == 'default') ? "=$value" : " $key=$value";
		}
		$text .= ']';
		foreach ($this->children as $child)
			$text .= $child->toBBCode();
		$text .= "[/{$this->tag_name}]";
		return $text;
	}

	/**
	 * (non-PHPdoc)
	 * @see BBCode.Node::toHTML()
	 * 
	 * Returns the element as html with all replacements made
	 * 
	 * @return the html representation of this node
	 */
	public function toHTML()
	{
		$content = '';
		if (!$this->code || $this->code->parse())
		{
			foreach ($this->children as $child)
				$content .= $child->toHTML();	
		}
		else
		{
			foreach ($this->children as $child)
				$content .= $child->toBBCode();
		}

		if ($this->code)
			$content = $this->code->process($this->tag_name, $this->attributes, $content);

		return $content;
	}

	/**
	 * Adds a child to this node's content. A child may be a TextNode, or another ElementNode... or anything else
	 * that may extend the abstract Node class. Additionally merges adjacent TextNodes.
	 * 
	 * @param child the node to add as a child
	 */
	public function addChild(Node $child)
	{
		$count = 0;
		if (($child instanceof TextNode) && ($count = sizeof($this->children)) > 0)
		{
			// merge adjacent TextNodes
			$last = $this->children[$count - 1];
			if ($last instanceof TextNode)
			{
				$last->addText($child->toText());
				return;
			}
		}

		$this->children[] = $child;
		$child->setParent($this);
	}

	/**
	 * Removes a child from this node's content.
	 *
	 * @param child the child node to remove
	 */
	public function removeChild(Node $child)
	{
		foreach ($this->children as $key => $value)
		{
			if($value == $child)
				unset($this->children[$key]);
		}
	}

	/**
	 * Sets the attributes of this element node.
	 * 
	 * @param attributes the attributes of this element node
	 */
	public function setAttributes($attributes)
	{
		$this->attributes =& $attributes;
	}
}
