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
 * This class represents a BBCode Definition.
 *
 * @author jbowens
 */
class CodeDefinition
{
	/* NOTE: THIS PROPERTY SHOULD ALWAYS BE LOWERCASE */
	protected $tag_name;

	/* Callback (closure) supplied by the provider of this definition */
	protected $callback;

	/* Whether or not to parse elements of this definition's contents */
	protected $parse;

	/* How many of this element type may be nested within each other */
	protected $limit;

	/**
	 * Constructs a new CodeDefinition.
	 *
	 * @param string $tag_name   	the tag name of the code (for example the b in [b])
	 * @param closure $callback   	PHP closure (aka lambda function)
	 * @param boolean $parseContent whether or not to parse the content within these elements
	 * @param integer $limit		an optional limit of the number of elements of this kind that can be nested within
	 *								each other before the parser stops parsing them.
	 */
	public function __construct($tag_name, Closure $callback, $parseContent = true, $limit = -1)
	{
		$this->tag_name = strtolower($tag_name);
		$this->callback = $callback;
		$this->parse = $parseContent;
		$this->limit = $limit;
	}

	/**
	 * Process input from an ElementNode.
	 *
	 * @param	string	$tag_name	lowercase tag name of the should match $this->tag_name
	 * @param	array	$attributes	the attributes from the current element
	 * @param	string	$content	the current content inside this BBCode
	 * 
	 * @return the HTML according to this definitions handling
	 */
	public function process($tag_name, $attributes, $content)
	{
		$callback = $this->callback;
		if (($html = $callback($tag_name, $attributes, $content)) !== false)
			return $html;

		// if we are still here return as BBCode
		return $content;
	}

	/**
	 * Returns the tag name of this code definition
	 *
	 * @return this definition's associated tag name
	 */
	public function name()
	{
		return $this->tag_name;
	}

	/**
	 * Returns whether or not this CodeDefnition parses elements contained within it, or just treats its children as text.
	 *
	 * @return true if this CodeDefinition parses elements contained within itself
	 */
	public function parse()
	{
		return $this->parse;
	}
	
	/**
	 * Get the nesting limit for this type of BBCode.
	 */
	public function limit()
	{
		return $this->limit;
	}
}
