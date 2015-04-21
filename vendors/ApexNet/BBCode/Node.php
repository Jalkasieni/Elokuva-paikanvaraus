<?php
/**
*
* @package apexnet-bbclib
* @license http://opensource.org/licenses/mit-license.php The MIT License (MIT)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\BBCode;

/**
 * A node within the document tree.
 *
 * Known subclasses: TextNode, ElementNode
 *
 * @author jbowens
 */
abstract class Node
{
	/* Pointer to the parent node of this node */
	protected $parent;

	/**
	 * Returns this node's immediate parent.
	 * 
	 * @return the node's parent
	 */
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * Determines if this node has a parent.
	 * 
	 * @return true if this node has a parent, false otherwise
	 */
	public function hasParent()
	{
		return $this->parent != null;
	}

	/**
	* Accepts a NodeVisitor
	*
	* @param nodeVisitor the NodeVisitor traversing the graph
	*/
	abstract function accept(NodeVisitor $visitor);

	/**
	 * Determine if this node is empty
	 *
	 * @return true on empty element, false otherwise
	 */
	abstract function isEmpty();

	/**
	 * Returns this node as text (without any bbcode markup)
	 * 
	 * @return the plain text representation of this node
	 */
	abstract function toText();

	/**
	 * Returns this node as bbcode
	 * 
	 * @return the bbcode representation of this node
	 */
	abstract function toBBCode();

	/**
	 * Returns this node as HTML
	 * 
	 * @return the html representation of this node
	 */
	abstract function toHTML();

	/**
	 * Sets this node's parent to be the given node.
	 * 
	 * @param parent the node to set as this node's parent
	 */
	public function setParent(Node $parent)
	{
		$this->parent = $parent;
	}
}
