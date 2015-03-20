<?php
/**
*
* @package google-apis
* @version $Id: DataSource.php 800 2014-05-27 03:43:58Z crise $
* @copyright (c) 2014 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace ApexNet\Google\Visualization;

use Exception;

/**
 * Implements the bare essentials of the data source protocol, as defined by Google
 */
class DataSource
{
	const SAME_ORIGIN_HEADER = 'X-DataSource-Auth';

	protected $response = array();
	protected $params = array();

	protected $version = '6.0';
	protected $handler = 'google.visualization.Query.setResponse';

	protected $table = false;

	public function __construct($tqx, $handler = null, $version = null)
	{
		if(!empty($handler))
			$this->handler = $handler;

		if(!empty($version))
			$this->version = $version;

		$this->response = array(
			'version'	=> $this->version,
			'status'	=> 'ok'
		);

		if (!empty($tqx))
		{
			$tqx = explode(';', $tqx);
			foreach($tqx as $fragment) {
				list($name, $val) = explode(':', $fragment, 2);
				$this->params[$name] = $val;
			}

			if (isset($this->params['reqId']))
				$this->response['reqId'] = $this->params['reqId'];

			if (isset($this->params['responseHandler']))
			{
				$safe_handler = preg_replace('#[^a-zA-Z0-9_\\.]++#u', '', $this->params['responseHandler']);
				if (!empty($safe_handler))
					$this->handler = $safe_handler;
			}

			if (isset($this->params['out']) && $this->params['out'] !== 'json')
				$this->addError('not_supported', 'Data source does not support requested output format');
		}
	}

	public function addError($reason, $message)
	{
		if (!isset($this->response['errors']))
		{
			$this->response['status'] = 'error';
			$this->response['errors'] = array();

			// if we encountered an error don't send a table
			$this->table = false;
		}

		$this->response['errors'][] = array(
			'reason'	=> $reason,
			'message'	=> $message
		);
	}

	public function addWarning($reason, $message)
	{
		if (!isset($this->response['warnings']))
		{
			$this->response['status'] = 'warning';
			$this->response['warnings'] = array();
		}

		$this->response['warnings'][] = array(
			'reason'	=> $reason,
			'message'	=> $message
		);
	}

	public function setDataTable(DataTable $table)
	{
		if (!isset($this->response['errors']))
			$this->table = $table;
	}

	public function getMimeType()
	{
		return 'text/javascript';
	}

	public function toJSON()
	{
		$full_response = $this->response;
		if ($this->table !== false)
			$full_response['table'] = $this->table->toArray();

		$options = 0;
		if (defined('DEBUG'))
			$options |= JSON_PRETTY_PRINT;

		return "// Data table response \n {$this->handler}(". json_encode($full_response, $options) .');';
	}

	final public function __call($method, $args)
	{
		if (isset($this->response['errors']))
			return false;

		if ($this->table == false)
			$this->setDataTable(new DataTable());

		if (!is_callable(array($this->table, $method)))
			return false;

		try
		{
			switch (count($args))
			{
				case 0:
					return $this->table->$method();

				case 1:
					return $this->table->$method($args[0]);

				case 2:
					return $this->table->$method($args[0], $args[1]);

				case 3:
					return $this->table->$method($args[0], $args[1], $args[2]);

				case 4:
					return $this->table->$method($args[0], $args[1], $args[2], $args[3]);

				default:
					return call_user_func_array(array($this->table, $method), $args);
			}
		}
		catch (Exception $e)
		{
			$this->addError('internal_error', $e->getMessage());
		}

		return false;
	}
}
