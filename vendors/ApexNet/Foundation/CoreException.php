<?php
/**
*
* @package apexnet
* @copyright (c) 2015 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\Foundation;

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use Exception;

/**
 * Base Exception class for all classes depending on the Foundation library
 */
class CoreException  extends Exception
{
	public function getExceptionAsString()
	{
		return ErrorReporting::formatException($this, false);
	}

	public function getExceptionAsHTML()
	{
		return ErrorReporting::formatException($this, true);
	}

	public function __toString()
	{
		return ErrorReporting::formatException($this, false);
	}
}
