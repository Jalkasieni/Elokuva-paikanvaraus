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
 * Defines an interface for a visitor to traverse the node graph.
 *
 * @author jbowens
 * @since January 2013
 */
interface NodeVisitor
{
	public function visitDocument(DocumentElement $e);

	public function visitText(TextNode $e);

	public function visitElement(ElementNode $e);
}
