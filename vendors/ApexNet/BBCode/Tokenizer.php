<?php
/**
*
* @package apexnet-bbclib
* @version $Id: Tokenizer.php 800 2014-05-27 03:43:58Z crise $
* @license http://opensource.org/licenses/mit-license.php The MIT License (MIT)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\BBCode;

/**
 * Tokenizes a piece of text for the main parser.
 * 
 * The Tokenizer is used when constructing the parse tree. Before parsing begins, it separates the string into
 * left and right brackets ("[", "]") and string to make parsing easier.
 *
 * @author jbowens
 */
class Tokenizer
{
	protected $tokens = array();
	protected $pos = 0;

	/**
	 * Tokenizes the inputted string
	 * 
	 * @param string $str   the string to tokenize
	 */
	public function __construct($str)
	{
		$this->tokens = preg_split('#([\[\]])#', $str, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
	}

	/**
	 * Returns the array of all the tokens.
	 * 
	 * @return all tokens
	 */
	public function tokens()
	{
		return $this->tokens;
	}

    /**
     * Returns true if there is a current token.
     *
     * @return true if there is a current token
     */
	public function valid()
	{
		return isset($this->tokens[$this->pos]);
	}

	/**
	 * Returns the current token.
	 * 
	 * @return the current token
	 */
	public function current()
	{
		return isset($this->tokens[$this->pos]) ? $this->tokens[$this->pos] : null;
	}

	/**
	 * Returns the next token.
	 * 
	 * @return the next token
	 */
	public function next()
	{
		return isset($this->tokens[$this->pos + 1]) ? $this->tokens[$this->pos + 1] : null;
	}

	/**
	 * Moves the pointer back to the first token.
	 */
	public function restart()
	{
		$this->pos = 0;
	}

	/**
	 * Advances the token pointer to the next token.
	 */
	public function advance($step = 1)
	{
		$this->pos += $step;
	}
}
