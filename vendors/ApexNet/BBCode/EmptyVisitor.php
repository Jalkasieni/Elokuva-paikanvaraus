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
 * Visitor for removing of empty elements from the parse tree.
 *
 * This visitor is used by the the core to remove empty subtrees after
 * parsing. Some of which may have been created due to recovery from
 * incorrect nesting.
 *
 * @author crise
 * @since December 2013
 */
class EmptyVisitor extends NullVisitor
{
	public function visitText(TextNode $e)
	{
		if ($e->isEmpty())
			$e->getParent()->removeChild($e);
	}

	public function visitElement(ElementNode $e)
	{
		if ($e->isEmpty())
		{
			$e->getParent()->removeChild($e);
		}
		else
		{
			parent::visitElement($e);
		}
	}
}
