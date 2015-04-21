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
 * Visitor for enforcing nesting limits from CodeDefinition.
 *
 * This visitor is used by the the core to enforce nesting limits after
 * parsing. It traverses the parse graph depth first, removing any subtrees
 * that are nested deeper than an element's code definition allows.
 *
 * @author jbowens
 * @since May 2013
 */
class NestLimitVisitor extends NullVisitor
{
	// A map from tag name to current depth.
	protected $depth = array();

	public function visitElement(ElementNode $e)
	{
		$tag_name = $e->getTagName();
		$limit = $e->getCode()->limit();
		
		/* Update the current depth for this tag name. */
		if (!isset($this->depth[$tag_name]))
			$this->depth[$tag_name] = 0;

		$this->depth[$tag_name]++;

		if ($limit != -1 && $limit < $this->depth[$tag_name])
		{
			$e->getParent()->removeChild($e);
		}
		else
		{
			parent::visitElement($e);
		}

		$this->depth[$tag_name]--;
	}
}
