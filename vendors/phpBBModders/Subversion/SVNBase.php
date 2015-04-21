<?php
/**
*
* @package svn
* @copyright (c) 2008 phpbbmodders
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
* History:
*	- 2010-10-11: inital revision from phpbbmodders_lib (functions_svn.php)
*	- 2012-05-13: added svn_log_array for a more php like interface
*	- 2012-05-18: refactor for autoloader support
*	- 2014-10-22: update for namespaces and change to camelCase function and class names
*/

namespace phpBBModders\Subversion;

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

/**
 * command-line svn class (for svn 1.5)
 * thanks to highway of life for the original code
 * thanks to this svn documentation: http://svnbook.red-bean.com/nightly/en/index.html
 */
class SVNBase
{
	// The name of the svn executable including path if needed.
	protected $svn_bin = 'svn';

	// our paths
	protected $local_copy_path;
	protected $local_bin_path;
	protected $local_config_path;
	protected $svn_repository;

	// svn login details
	protected $username;
	protected $password;

	// global svn options, used for things like ignore-externals
	protected $global_options = array();

	// contains the version of subversion binaries
	public $svn_version;

	// svn properties
	public $properties = array(
		// reserved properties
		'date'			=> 'svn:date',
		'orig_date'		=> 'svn:original-date',
		'author'		=> 'svn:author',
		'log'			=> 'svn:log',

		// other properties
		'executable'	=> 'svn:executable',
		'mime_type'		=> 'svn:mime-type',
		'ignore'		=> 'svn:ignore',
		'keywords'		=> 'svn:keywords',	// Id, Date, Rev, Author, URL
		'eol_style'		=> 'svn:eol-style',	// native, CRLF, LF, CR
		'externals'		=> 'svn:externals',
	);

	public static function create(array $config)
	{
		$config['svn_bin'] = isset($config['svn_bin']) ? $config['svn_bin'] : false;
		return new static($config['bin_path'], $config['config_path'], $config['svn_bin']);
	}

	/**
	 * constructor
	 * build it all, paths should end with /
	 *
	 * @param string $bin_path path to the svn binaries dir
	 * @param string $config_path path to the svn configuration
	 * @param string $svn_bin binary file for SVN
	 */
	protected function __construct($bin_path, $config_path, $svn_bin = false)
	{
		$this->local_bin_path		= $this->appendSlash((string) $bin_path);
		$this->local_config_path	= $this->appendSlash((string) $config_path);

		if ($svn_bin)
			$this->svn_bin = (string) $svn_bin;

		$this->svn_version = implode(PHP_EOL, $this->exec(false, false, array('version' => null, 'quiet' => null)));
	}

	/**
	 * set the repository to operate on, paths should end with /
	 * 
	 * @param string $repository_url path to the svn repo
	 * @param string $username username for svn server
	 * @param string $password password for svn server
	 * @param string $working_copy path to the local svn copy
	 */
	 public function setRepository($repository_url, $username = '', $password = '', $working_copy = '')
	 {
		$this->svn_repository = $this->appendSlash((string) $repository_url);
		$this->local_copy_path = $this->appendSlash((string) $working_copy);

		$this->username	= (string) $username;
		$this->password	= (string) $password;		
	 }

	/**
	 * append a slash to a path, if there isn't one already
	 *
	 * @param string $path
	 * @return string slash appended path
	 */
	protected function appendSlash($path)
	{
		return (strlen($path) && substr($path, -1) !== '/') ? $path . '/' : $path;
	}

	/**
	 * escape a shell arguemnt, wrapper for escapeshellarg()
	 *
	 * @param mixed $arg
	 * @return string escaped arg
	 */
	protected function escapeArg($arg)
	{
		return escapeshellarg(addslashes($arg));
	}

	/**
	 * execute an svn command, this is the main function
	 *
	 * @param string $_svn_command
	 * @param mixed $_svn_arg svn argument(s), can either be bool(false), string or array
	 * @param array $_svn_options svn options
	 * @param boolean $inc_user_pass when set to true, user and pass are included as args, only needed for remote actions
	 * @return array result
	 */
	protected function exec($_svn_command = false, $_svn_arg = false, $_svn_options = array(), $inc_user_pass = false)
	{
		// add some svn options
		if ($this->local_config_path)
		{
			$_svn_options['config-dir'] = $this->local_config_path;
		}

		if ($inc_user_pass)
		{
			if ($this->username)
			{
				$_svn_options['username'] = $this->username;
			}

			if ($this->password)
			{
				$_svn_options['password'] = $this->password;
			}
		}

		// add the global options
		$_svn_options = array_merge($_svn_options, $this->global_options);

		// build the svn command
		$svn_command = $this->buildCommand($_svn_command, $_svn_arg, $_svn_options);

		// exec and return
		$result = array();
		exec($svn_command, $result);
		return $result;
	}

	/**
	 * build the main svn command
	 *
	 * @param string $command the svn command
	 * @param mixed $argument the svn argument(s)
	 * @param array $options the svn options
	 * @return string svn shell command
	 */
	protected function buildCommand($command, $argument, $options)
	{
		if ($argument)
		{
			if (!is_array($argument))
			{
				$argument = array($argument);
			}
			$argument = array_diff($argument, array('')); // remove empty values
			$argument = implode(' ', array_map(array($this, 'escapeArg'), $argument));
		}

		$svn_command = $this->local_bin_path . $this->svn_bin . ($command ? ' ' . $this->escapeArg($command) : '') . ($argument ? ' ' . $argument : '');
		foreach ($options as $key => $option)
		{
			// nothing if $option is null
			// implode if $option is an array
			$svn_command .= ' --' . $key . ($option !== null ? ' ' . (!is_array($option) ? $this->escapeArg($option) : implode(' ', array_map(array($this, 'escapeArg'), $option))) : '');
		}
		$svn_command .= ' 2>&1'; // this is needed

		return $svn_command;
	}

	/**
	 * build the revision
	 *
	 * @param mixed $data
	 * @param string $mode one of these: nun, date, head, base, committed, prev
	 * @return mixed build revision
	 */
	public function buildRevision($data, $mode = 'num')
	{
		if (is_array($data))
		{
			if (sizeof($data) < 2)
			{
				$data = $data[0];
			}
			else
			{
				// use array values so it doesn't mess up with list()
				list($data, $mode) = array_values($data);
			}
		}

		switch ($mode)
		{
			case 'num':
				// revision id (or range)
				return (strpos($data, ':') >= 1) ? $data : (int) $data;
				break;
			case 'date':
				// unix timestamp, convert it to date
				// reference: http://svnbook.red-bean.com/nightly/en/svn.tour.revs.specifiers.html#svn.tour.revs.dates
				return '{"' . gmdate('Y-m-d H:i', (int) $data) . '"}';
				break;
			case 'head':
			case 'base':
			case 'committed':
			case 'prev':
				/**
				 * special modes
				 *
				 * head:		latest in repository
				 * base:		base rev of item's working copy
				 * committed:	last commit at or before BASE
				 * prev:		revision just before COMMITTED
				 */
				return strtoupper($mode);
				break;
			default:
				if (in_array($data, array('head', 'base', 'committed', 'prev'), true))
				{
					// see if head, base, committed or prev is supplied as data
					return strtoupper($data);
				}
				else
				{
					// no valid mode given -- default to num
					// slight recursion :P
					return $this->buildRevision($data, 'num');
				}
				break;
		}
	}

	/**
	 * used to destinguish between local/remote
	 *
	 * @param boolean $on_server do we want to access the svn server
	 * @return string svn root path
	 */
	protected function onServer($on_server)
	{
		return ($on_server ? $this->svn_repository : $this->local_copy_path);
	}

	/**
	 * Set a global option
	 *
	 * @param string $option
	 * @param mixed $value
	 */
	public function setGlobalOption($option, $value = null)
	{
		$this->global_options[$option] = $value;
	}

	/**
	 * Unset global option
	 *
	 * @param string $option
	 */
	public function unsetGlobalOption($option)
	{
		if (array_key_exists($option, $this->global_options))
		{
			unset($this->global_options[$option]);
		}
	}

	/**
	 * convert unix timestamps to the format used by SVN or vice versa
	 * format taken from svn's libsvn_subr/time.c
	 *
	 * @todo find out what happened to those six numbers and the T
	 *
	 * @param int|string $time_input
	 * @param boolean $svn_to_unix
	 * @return int|string converted timestamp
	 */
	public static function convertTime($time_input, $svn_to_unix = true)
	{
		if ($svn_to_unix)
		{
			$date = strtotime($time_input);
			if ($date <= 0 && preg_match('#(\d{4})-(\d{2})-(\d{2})[T| ](\d{2}):(\d{2}):(\d{2})(?:\.\d{6})?Z#', $time_input, $matches))
			{
				$matches[2] = substr('00'.$matches[2], -2);
				$matches[3] = substr('00'.$matches[3], -2);
				$matches[4] = substr('00'.$matches[4], -2);
				$matches[5] = substr('00'.$matches[5], -2);
				$matches[6] = substr('00'.$matches[6], -2);
				$date = strtotime($matches[1].'-'.$matches[2].'-'.$matches[3].' '.$matches[4].':'.$matches[5].':'.$matches[6].' GMT');
			}

			return ($date <= 0) ? false : $date;
		}
		else
		{
			// can't use getdate() because it uses the timezone settings
			list($year, $month, $day, $hours, $minutes, $seconds) = array_map('intval', explode(' ', gmdate('Y n j G i s', $time_input)));
			return sprintf('%04d-%02d-%02dT%02d:%02d:%02dZ', $year, $month, $day, $hours, $minutes, $seconds);
		}
	}
}
