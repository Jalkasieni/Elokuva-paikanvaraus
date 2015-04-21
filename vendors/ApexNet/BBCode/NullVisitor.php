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
 * Defines an abstract Null visitor to traverse the node graph.
 *
 * @author crise
 * @since December 2013
 */
abstract class NullVisitor implements NodeVisitor
{
	public function visitDocument(DocumentElement $e)
	{
		foreach($e->getChildren() as $child)
			$child->accept($this);	
	}

	public function visitText(TextNode $e)
	{
		// Nothing to do here, we have no children
	}

	public function visitElement(ElementNode $e)
	{
		foreach ($e->getChildren() as $child)
			$child->accept($this);
	}
}
