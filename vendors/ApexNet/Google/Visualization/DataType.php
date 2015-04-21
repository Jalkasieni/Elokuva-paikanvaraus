<?php
/**
*
* @package google-apis
* @copyright (c) 2015 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\Google\Visualization;

/**
 * Defines constants for the data types used by DataTable
 */
final class DataType
{
	private function __construct() { }

    const STRING = 'string';
    const NUMBER = 'number';
    const BOOL = 'boolean';

	// Date and time formats, see DataDate
    const DATE = 'date';
    const DATETIME = 'datetime';
    const TIMEOFDAY = 'timeofday';
}
