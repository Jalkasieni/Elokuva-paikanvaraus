<?php
/**
*
* @package google-apis
* @version $Id: DataDate.php 800 2014-05-27 03:43:58Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\Google\Visualization;

/**
 * Helper class to deal with the date types for DataTable
 *
 * JSON formats, note all months are zero based:
 *	DataType::DATE & DataType::DATETIME: "Date(year, month, day[,hour, minute, second[, millisecond]])" (string)
 *	DataType::TIMEOFDAY: [hour, minute, second[, millisecond]] (array)
 */
class DataDate
{
	protected $data = array();

	public function __construct($timestamp = null)
	{
		$this->data = array(1970, 0, 1);

		if (!empty($timestamp))
		{
			if (is_string($timestamp))
				$timestamp = (int) strtotime($timestamp);

			$this->data = json_decode(date('[Y, n, j, G, i, s]', $timestamp), true);
			$this->data[1] -= 1;
		}
	}

	public function toJSON($type = DataType::DATETIME)
	{
		$relevant = $this->data;
		switch($type)
		{
			case DataType::TIMEOFDAY:
				return array_slice($relevant, 3);
			case DataType::DATE:
				$relevant = array_slice($relevant, 0, 3);
			default:
				return 'Date(' . implode(', ', $relevant) . ')';
		}
	}
}
