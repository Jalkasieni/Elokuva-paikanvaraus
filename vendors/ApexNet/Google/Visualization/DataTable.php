<?php
/**
*
* @package google-apis
* @version $Id: DataTable.php 800 2014-05-27 03:43:58Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\Google\Visualization;

use Exception;

/**
 * Implements a class for generating google.visualization.DataTable json objects
 *
 * Implemented API follows google's documented JS counterpart closely with some
 * minor differences. This class only supports insertions and implements a
 * symmetric N x M DataTable where additional columns can only be added when there
 * are no rows (ie. the DataTable is empty).
 *
 * @see https://developers.google.com/chart/interactive/docs/reference#DataTable
 */
class DataTable
{
	protected $columns = array();
	protected $rows = array();

	protected $column_count = 0;
	protected $row_count = 0;

	public function __construct(array $array_data = null)
	{
		if (!empty($array_data))
			$this->fromArray($array_data);
	}

	public function addColumn($type, $label = null, $id = null)
	{
		$this->columns[] = $this->makeColumn($type, $label, $id);
		return ($this->column_count += 1);
	}

	public function insertColumn($index, $type, $label = null, $id = null)
	{
		if ($index >= $this->column_count)
			throw new Exception('Google DataTable: invalid position, index out of bounds.');

		array_splice($this->columns, $index, 0, array($this->makeColumn($type, $label, $id)));
		return ($this->column_count += 1);
	}

	public function setColumnLabel($index, $label, $id = null)
	{
		if ($index >= $this->column_count)
			throw new Exception('Google DataTable: invalid position, index out of bounds.');

		$this->columns[$index]['label'] = $label;

		if (!empty($id))
			$this->columns[$index]['id'] = (string) $id;
	}

	public function addRow(array $row)
	{
		$this->rows[] = $this->makeRow($row);
		return ($this->row_count += 1);
	}

	public function addRows(array $rows)
	{
		foreach ($rows as $row)
			$this->rows[] = $this->makeRow($row);

		return ($this->row_count += sizeof($rows));
	}

	public function insertRows($index, array $rows)
	{
		if ($index >= $this->row_count)
			throw new Exception('Google DataTable: invalid position, index out of bounds.');

		for ($i = 0, $count = sizeof($rows); $i != $count; ++$i)
			$rows[$i] = $this->makeRow($row);

		array_splice($this->rows, $index, 0, $rows);
		return ($this->row_count += sizeof($rows));
	}

	public function setCell($row, $col, $value, $formatted_value = null)
	{
		if ($row >= $this->row_count || $col >= $this->column_count)
			throw new Exception('Google DataTable: invalid position, cell out of bounds.');

		if ($value instanceof DataDate)
			$value = $value->toJSON($this->getDataType($col));

		$this->rows[$row]['c'][$col]['v'] = $value;

		if (!empty($formatted_value))
			$this->rows[$row]['c'][$col]['f'] = $formatted_value;
	}

	public function setFormattedValue($row, $col, $formatted_value)
	{
		if ($row >= $this->row_count || $col >= $this->column_count)
			throw new Exception('Google DataTable: invalid position, cell out of bounds.');

		$this->rows[$row]['c'][$col]['f'] = $formatted_value;
	}

	public function setValue($row, $col, $value)
	{
		$this->setCell($row, $col, $value);
	}

	public function fromArray(array $array_data, $columns_first = true)
	{
		if ($columns_first)
		{
			$this->columns = array();
			$this->column_count = 0;

			foreach (array_shift($array_data) as $column)
			{
				$this->columns[] = $this->makeColumn(DataType::STRING, $label, null);
				$this->column_count += 1;
			}
		}

		$this->rows = array();
		$this->row_count = 0;

		foreach ($array_data as $row)
		{
			$this->rows[] = $this->makeRow($row);
			$this->row_count += 1;
		}
	}

	public function toArray()
	{
		return array('cols' => $this->columns, 'rows' => $this->rows);
	}

	public function toJSON()
	{
		$options = JSON_NUMERIC_CHECK;
		if (defined('DEBUG'))
			$options |= JSON_PRETTY_PRINT;

		return json_encode($this->toArray(), $options);
	}

	protected function makeColumn($type, $label, $id)
	{
		if ($this->row_count != 0)
			throw new Exception('Google DataTable: adding a column after populating rows is illegal.');

		$column =  array('type' => $type);

		if (!empty($label))
			$column['label'] = (string) $label;

		if (!empty($id))
			$column['id'] = (string) $id;

		return $column;
	}

	protected function makeRow(array $row)
	{
		if ($this->column_count < sizeof($row))
			throw new Exception('Google DataTable: adding a row with more values than columns is illegal.');

		for ($i = 0, $count = sizeof($row); $i != $count; ++$i)
		{
			if (is_array($row[$i]))
			{
				if (!isset($row[$i]['v']))
					throw new Exception('Google DataTable: row contains a cell without a value.');

				$value = $row[$i]['v'];
				if ($value instanceof DataDate)
					$row[$i]['v'] = $value->toJSON($this->getDataType($i));
			}
			else
			{
				$value = $row[$i];
				if ($value instanceof DataDate)
					$value = $value->toJSON($this->getDataType($i));
				$row[$i] = array('v' => $value);
			}
		}

		return array('c' => array_pad($row, $this->column_count, array('v' => null)));
	}

	protected function getDataType($col)
	{
		return $this->columns[$col]['type'];
	}
}
