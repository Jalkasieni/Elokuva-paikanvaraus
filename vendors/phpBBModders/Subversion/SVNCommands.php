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

use phpBBModders\Subversion\SVNBase;

use DOMDocument;

/**
 * the svn commands for SVNBase
 *
 * @todo svnMerge
 * @todo svnMergeinfo
 */
class SVNCommands extends SVNBase
{
	/**
	 * svn add
	 * add a file to the repository
	 *
	 * @param string $path path of the file to add, relative to svn root
	 * @param mixed $depth set to 0 if you don't want recursion
	 * @return array result
	 */
	public function svnAdd($path, $depth = false)
	{
		$options = array();

		if ($depth !== false)
		{
			$options['depth'] = $depth;
		}

		return $this->exec('add', $this->local_copy_path . $path, $options);
	}

	/**
	 * svn blame
	 * get revision number, author and changes from a file
	 *
	 * @param string $path path of file, relative to svn root -- must be a file
	 * @param mixed $revision
	 * @param boolean $on_server
	 * @return array result
	 */
	public function svnBlame($path, $revision = false, $on_server = false, $verbose = false, $xml = false, $incremental = false)
	{
		$options = array();

		if ($revision)
		{
			$options['revision'] = $this->buildRevision($revision);
		}

		if ($verbose)
		{
			$options['verbose'] = null;
		}

		if ($xml)
		{
			if ($incremental)
			{
				$options['incremental'] = null;
			}

			$options['xml'] = null;
		}

		return $this->exec('blame', $this->onServer($on_server) . $path, $options, $on_server);
	}

	/**
	 * svn cat
	 * get contents of a file
	 *
	 * @param string $path path of file, relative to svn root
	 * @param mixed $revision
	 * @param boolean $on_server
	 * @return array result
	 */
	public function svnCat($path, $revision = false, $on_server = false)
	{
		$options = array();

		if ($revision)
		{
			$options['revision'] = $this->buildRevision($revision);
		}

		return $this->exec('cat', $this->onServer($on_server) . $path, $options, $on_server);
	}

	/**
	 * svn changelist
	 * associate (or deassociate) local paths with a changelist
	 *
	 * @param mixed $path path to file, relative to svn root - can also be an array of paths
	 * @param mixed $changelist name of the changelist
	 * @param boolean $remove remove from the changelist
	 * @return array result
	 */
	public function svnChangelist($path = '', $changelist = false, $remove = false)
	{
		$options = array();

		if ($remove)
		{
			$options['remove'] = null;
		}

		if (!is_array($path))
		{
			$path = array($path);
		}

		foreach ($path as $key => $value)
		{
			$path[$key] = $this->local_copy_path . $value;
		}

		if ($changelist !== false)
		{
			array_unshift($path, $changelist);
		}

		return $this->exec('changelist', $path, $options);
	}

	/**
	 * svn checkout
	 * checkout to local
	 *
	 * @param string $path path to where we want to checkout, relative to svn root
	 * @param mixed $revision
	 * @return array result
	 */
	public function svnCheckout($path = '', $revision = false)
	{
		$options = array();

		if ($revision)
		{
			$options['revision'] = $this->buildRevision($revision);
		}

		return $this->exec('co', array($this->svn_repository . $path, $this->local_copy_path . $path), $options, true);
	}

	/**
	 * svn cleanup
	 * recursively clean up local
	 *
	 * @param string $path path to clean up, relative to svn root
	 * @return array result
	 */
	public function svnCleanup($path = '')
	{
		return $this->exec('cleanup', $this->local_copy_path . $path);
	}

	/**
	 * svn commit
	 * checkin changes to server
	 *
	 * @param string $path
	 * @param mixed $message
	 * @param mixed $encoding
	 * @param mixed $depth
	 * @return array result
	 */
	public function svnCommit($path = '', $message = false, $encoding = false, $depth = false, $changelist = false)
	{
		$options = array();

		if ($message)
		{
			$options['message'] = $message;
			if ($encoding)
			{
				$options['encoding'] = $encoding;
			}
		}

		if ($depth !== false)
		{
			$options['depth'] = $depth;
		}

		if ($changelist !== false)
		{
			$options['changelist'] = $changelist;
		}

		return $this->exec('ci', array($this->svn_repository . $path, $this->local_copy_path . $path), $options, true);
	}

	/**
	 * svn copy
	 * copy a file
	 *
	 * @param string $path_old path of source file, relative to svn root
	 * @param string $path_new path of destination file, relative to svn root
	 * @param mixed $revision
	 * @param boolean $on_server_old
	 * @param boolean $on_server_new
	 * @param mixed $message
	 * @param mixed $encoding
	 * @return array result
	 */
	public function svnCopy($path_old, $path_new, $revision = false, $on_server_old = false, $on_server_new = false, $message = false, $encoding = false)
	{
		$options = array();

		if ($revision)
		{
			$options['revision'] = $this->buildRevision($revision);
		}

		if ($on_server_new && $message)
		{
			$options['message'] = $message;
			if ($encoding)
			{
				$options['encoding'] = $encoding;
			}
		}

		return $this->exec('copy', array($this->onServer($on_server_old) . $path_old, $this->onServer($on_server_new) . $path_new), $options, $on_server_old || $on_server_new);
	}

	/**
	 * svn delete
	 * delete a file from the repository
	 *
	 * @param string $path path of file to delete, relative to svn root
	 * @param boolean $on_server
	 * @param mixed $log_message
	 * @param mixed $encoding
	 * @return array result
	 */
	public function svnDelete($path, $on_server = false, $log_message = false, $encoding = false)
	{
		$options = array();

		if ($on_server && $log_message)
		{
			$options['message'] = $log_message;
			if ($encoding)
			{
				$options['encoding'] = $encoding;
			}
		}

		return $this->exec('delete', $this->onServer($on_server) . $path, $options, $on_server);
	}

	/**
	 * svn diff
	 * find differences between files
	 * compare local to local:		svn_diff($path, $rev_old[, $rev_new])
	 * compare server to server:	svn_diff($path, $rev_old, $rev_new, true, true)
	 * compare local to server:		svn_diff($path, $rev_old, $rev_new, false, true)
	 *
	 * @param string $path path relative to svn root
	 * @param mixed $rev_old
	 * @param mixed $rev_new
	 * @param boolean $on_server_old
	 * @param boolean $on_server_new
	 * @param boolean $xml
	 * @return array result
	 */
	public function svnDiff($path = '', $rev_old = false, $rev_new = false, $on_server_old = false, $on_server_new = false, $xml = false)
	{
		$arguments = $options = array();
		$on_server_custom = false;

		if ($on_server_new !== $on_server_old)
		{
			// comparing local to remote or vice versa
			$arguments = array($this->local_copy_path . $path . ($rev_old ? "@{$this->buildRevision($rev_old)}" : ''), $this->svn_repository . $path . ($rev_new ? "@{$this->buildRevision($rev_new)}" : ''));
		}
		else
		{
			// comparing local to local or remote to remote
			if ($rev_old && $rev_new)
			{
				// compare $rev_old against $rev_new
				$arguments = array($this->onServer($on_server_old) . $path);
				$options['revision'] = "{$this->buildRevision($rev_old)}:{$this->buildRevision($rev_new)}";
			}
			else if ($rev_old)
			{
				// compare $rev_old to working copy
				$arguments = array($this->onServer($on_server_old) . $path);
				$options['revision'] = $this->buildRevision($rev_old);
			}
			else
			{
				// compare latest to working copy, for that we need username & password
				$arguments = array($this->onServer($on_server_old) . $path);
				$on_server_custom = true;
			}
		}

		// pass it to diff
		//$options['diff-cmd'] = $this->local_bin_path . 'diff';

		// xml support only in svn 1.5.0+
		if ($xml && version_compare($this->svn_version, '1.5.0', '>='))
		{
			$options['xml'] = null;
			$options['summarize'] = null;
		}

		return $this->exec('diff', $arguments, $options, $on_server_old || $on_server_new || $on_server_custom);
	}

	/**
	 * svn export
	 * export the repository to local
	 *
	 * @param string $path path relative to local_site_dir
	 * @param string $export_to path we want to export to, not relative to anything
	 * @param mixed $revision
	 * @param boolean $on_server
	 * @return array result
	 */
	public function svnExport($path = '', $export_to = '', $revision = false, $on_server = false)
	{
		$options = array();

		if ($revision)
		{
			$options['revision'] = $this->buildRevision($revision);
		}

		return $this->exec('export', array($this->onServer($on_server) . $path, $export_to), $options, $on_server);
	}

	/**
	 * svn help
	 *
	 * @param string $argument
	 * @return array result
	 */
	public function svnHelp($argument = '', $version = false, $quiet = false)
	{
		$options = array();

		if ($version)
		{
			$options['version'] = null;
		}

		if ($quiet)
		{
			$options['quiet'] = null;
		}

		return $this->exec('help', $argument, $options);
	}

	/**
	 * svn import
	 * import to a repo from local
	 *
	 * @param string $path path relative to svn root
	 * @param string $import_from path not relative to anything
	 * @param mixed $message
	 * @param mixed $encoding
	 * @return array result
	 */
	public function svnImport($path = '', $import_from = '', $message = false, $encoding = false)
	{
		$options = array();

		if ($message)
		{
			$options['message'] = $message;
			if ($encoding)
			{
				$options['encoding'] = $encoding;
			}
		}

		return $this->exec('import', array($import_from, $this->svn_repository . $path), $options, true);
	}

	/**
	 * svn info
	 * get svn info from a path
	 *
	 * @param string $path path relative to svn root
	 * @param mixed $revision
	 * @param boolean $on_server
	 * @param boolean $xml
	 * @return array result
	 */
	public function svnInfo($path = '', $revision = false, $on_server = false, $xml = false)
	{
		$options = array();

		if ($revision)
		{
			$options['revision'] = $this->buildRevision($revision);
		}

		if ($xml)
		{
			$options['xml'] = null;
		}

		return $this->exec('info', $this->onServer($on_server) . $path, $options, $on_server);
	}

	/**
	 * svn list
	 * the same as ls
	 *
	 * @param string $path path relative to svn root
	 * @param mixed $revision
	 * @param boolean $on_server
	 * @param boolean $verbose returns more details if set to true
	 * @param boolean $xml
	 * @return array result
	 */
	public function svnList($path = '', $revision = false, $on_server = false, $verbose = false, $xml = false)
	{
		$options = array();

		if ($verbose)
		{
			$options['verbose'] = null;
		}

		if ($revision)
		{
			$options['revision'] = $this->buildRevision($revision);
		}

		if ($xml)
		{
			$options['xml'] = null;
		}

		return $this->exec('list', $this->onServer($on_server) . $path, $options, true);
	}

	/**
	 * svn lock
	 * lock a path
	 *
	 * @param string $path path relative to svn root
	 * @param boolean $on_server
	 * @param boolean $force force locking - overwrite existing locks
	 * @param mixed $message
	 * @param mixed $encoding
	 * @return array result
	 */
	public function svnLock($path = '', $on_server = false, $force = false, $message = false, $encoding = false)
	{
		$options = array();

		if ($on_server && $message)
		{
			$options['message'] = $message;
			if ($encoding)
			{
				$options['encoding'] = $encoding;
			}
		}

		if ($force)
		{
			$options['force'] = null;
		}

		return $this->exec('lock', $this->onServer($on_server) . $path, $options, $on_server);
	}

	/**
	 * svn log
	 * get svn log message
	 *
	 * @param string $path path relative to svn root
	 * @param mixed $revision
	 * @param boolean $on_server
	 * @param boolean $verbose returns more info when true
	 * @param boolean $xml
	 * @return array result
	 */
	public function svnLog($path = '', $revision = false, $on_server = false, $verbose = false, $incremental = false, $limit = false, $with_all_revprops = false, $xml = false)
	{
		$options = array();

		if ($revision)
		{
			$options['revision'] = $this->buildRevision($revision);
		}

		if ($verbose)
		{
			$options['verbose'] = null;
		}

		if ($incremental)
		{
			$options['incremental'] = null;
		}

		if ($limit)
		{
			$options['limit'] = (int) $limit;
		}

		if ($with_all_revprops)
		{
			$options['with-all-revprops'] = null;
		}

		if ($xml)
		{
			$options['xml'] = null;
		}

		return $this->exec('log', $this->onServer($on_server) . $path, $options, true);
	}

	/**
	 * svn log array
	 * get svn log message(s) as an associative array
	 *
	 * @param string $path path relative to svn root
	 * @param mixed $revision
	 * @param boolean $on_server
	 * @param boolean $verbose returns more info when true
	 * @param mixed $limit top limit for log entries fetched
	 * @return array result
	 */
	public function svnLogArray($path = '', $revision = false, $on_server = false, $verbose = false, $limit = false)
	{
		$revisions = array();
		$result = $this->svnLog($path, $revision, $on_server, $verbose, false, $limit, true, true);

		if (!is_array($result) || sizeof($result) <= 3)
			return $revisions;

		// Build a associative array from the xml
		$xml = new DOMDocument();
		$xml->formatOutput = true;
		$xml->preserveWhiteSpace = false;

		if (@$xml->loadXML(implode("\n", $result)))
		{
			unset($result);
			$entries = $xml->getElementsByTagName('logentry');

			foreach ($entries as $entry)
			{
				$revision = array();
				$revision['revision'] = (int)$entry->getAttribute('revision');

				if ($entry->childNodes->length) 
				{
					foreach ($entry->childNodes as $i)
					{
						if ($i->nodeName == 'paths') {
							$revision[$i->nodeName] = array();
							$dirlist = array();
							foreach ($i->childNodes as $j)
							{
								$revision[$i->nodeName][$j->getAttribute('action')][] = $j->nodeValue;
								$dirlist[] = ($j->getAttribute('kind') == 'dir') ? $j->nodeValue :
									substr($j->nodeValue, 0, (int)strrpos($j->nodeValue, '/'));
							}

							$dirlist = array_unique($dirlist);
							sort($dirlist);
							if (sizeof($dirlist) > 2 && !in_array('/', $dirlist))
							{
								$common = explode('/', array_shift($dirlist));
								foreach ($dirlist as $dir)
								{
									$parts = explode('/', $dir);
									for ($i = 0, $len = sizeof($common); $i < $len; ++$i) {
										if ($i == sizeof($parts) || $common[$i] != $parts[$i]) 
										{
											array_splice($common, $i);
											break;
										}
									}
								}
								$common = implode('/', $common);
								if (!empty($common))
								{
									$newdirs = array();
									foreach ($dirlist as $dir)
										$newdirs[] = ($dir == $common) ? '.' : substr($dir, strlen($common) + 1);

									$revision['dirs'] = array('base' => $common, 'dirs' => $newdirs);
								}
							} if(!isset($revision['dirs']))
								$revision['dirs'] = $dirlist;
						} else if ($i->nodeName == 'date') {
							$revision[$i->nodeName] = self::convertTime($i->nodeValue);
						} else $revision[$i->nodeName] = $i->nodeValue;
					}
				}

				$revisions[$revision['revision']] = $revision;
			}
		}
	
		return $revisions;
	}

	public function svnMerge()
	{
		/**
		 * @todo code
		 */
	}

	/**
	 * svn mkdir
	 * create a new dir in svn repo
	 *
	 * @param string $path path relative to svn root
	 * @param boolean $on_server
	 * @param mixed $message
	 * @param mixed $encoding
	 * @return array result
	 */
	public function svnMkdir($path = '', $on_server = false, $message = false, $encoding = false)
	{
		$options = array();

		if ($on_server && $message)
		{
			$options['message'] = $message;
			if ($encoding)
			{
				$options['encoding'] = $encoding;
			}
		}

		return $this->exec('mkdir', $this->onServer($on_server) . $path, $options, $on_server);
	}

	/**
	 * svn move
	 * move path within repo
	 *
	 * @param string $path path relative to svn root
	 * @param string $move_to move to path, relative to svn root
	 * @param mixed $revision
	 * @param boolean $on_server
	 * @param mixed $message
	 * @param mixed $encoding
	 * @return array result
	 */
	public function svnMove($path = '', $move_to = '', $revision = false, $on_server = false, $message = false, $encoding = false)
	{
		$options = array();

		if ($revision)
		{
			$options['revision'] = $this->buildRevision($revision);
		}

		if ($on_server && $message)
		{
			$options['message'] = $message;
			if ($encoding)
			{
				$options['encoding'] = $encoding;
			}
		}

		return $this->exec('move', array($this->onServer($on_server) . $path, $this->onServer($on_server) . $move_to), $options, $on_server);
	}

	/**
	 * svn propdel
	 * delete a property
	 *
	 * @param string $prop_name property name
	 * @param string $path path relative to svn root
	 * @param mixed $revision
	 * @param boolean $on_server
	 * @return array result
	 */
	public function svnPropdel($prop_name, $path = '', $revision = false, $on_server = false)
	{
		$options = array();

		if ($revision)
		{
			$options['revision'] = $this->buildRevision($revision);
		}

		return $this->exec('propdel', array($prop_name, $this->onServer($on_server) . $path), $options, $on_server);
	}

	/**
	 * svn propedit
	 * edit a property
	 *
	 * @param string $prop_name property name
	 * @param string $path path relative to svn root
	 * @param mixed $revision
	 * @param boolean $on_server
	 * @param mixed $message
	 * @param mixed $encoding
	 * @return array result
	 */
	public function svnPropedit($prop_name, $path = '', $revision = false, $on_server = false, $message = false, $encoding = false)
	{
		$options = array();

		if ($revision)
		{
			$options['revision'] = $this->buildRevision($revision);
		}

		if ($on_server && $message)
		{
			$options['message'] = $message;
			if ($encoding)
			{
				$options['encoding'] = $encoding;
			}
		}

		return $this->exec('propedit', array($prop_name, $this->onServer($on_server) . $path), $options, $on_server);
	}

	/**
	 * svn propget
	 * get a property
	 *
	 * @param string $prop_name property name
	 * @param string $path path relative to svn root
	 * @param mixed $revision
	 * @param boolean $on_server
	 * @param mixed $message
	 * @param mixed $encoding
	 * @return array result
	 */
	public function svnPropget($prop_name, $path = '', $revision = false, $on_server = false, $message = false, $encoding = false)
	{
		$options = array();

		if ($revision)
		{
			$options['revision'] = $this->buildRevision($revision);
		}

		if ($on_server && $message)
		{
			$options['message'] = $message;
			if ($encoding)
			{
				$options['encoding'] = $encoding;
			}
		}

		return $this->exec('propget', array($prop_name, $this->onServer($on_server) . $path), $options, $on_server);
	}

	/**
	 * svn proplist
	 * list properties of a path
	 *
	 * @param string $path path relative to svn root
	 * @param mixed $revision
	 * @param boolean $on_server
	 * @param boolean $verbose when set to true the func returns more info
	 * @return array result
	 */
	public function svnProplist($path = '', $revision = false, $on_server = false, $verbose = false)
	{
		$options = array();

		if ($revision)
		{
			$options['revision'] = $this->buildRevision($revision);
		}

		if ($verbose)
		{
			$options['verbose'] = null;
		}

		return $this->exec('proplist', $this->onServer($on_server) . $path, $options, $on_server);
	}

	/**
	 * svn propset
	 * set a property
	 *
	 * @param string $prop_name property name
	 * @param string $prop_value property value
	 * @param string $path path relative to local_site_root
	 * @param mixed $revision
	 * @param boolean $on_server
	 * @param mixed $message
	 * @param mixed $encoding
	 * @return array result
	 */
	public function svnPropset($prop_name, $prop_value, $path = '', $revision = false, $on_server = false, $message = false, $encoding = false)
	{
		$options = array();

		if ($revision)
		{
			$options['revision'] = $this->buildRevision($revision);
		}

		if ($on_server && $message)
		{
			$options['message'] = $message;
			if ($encoding)
			{
				$options['encoding'] = $encoding;
			}
		}

		return $this->exec('propset', array($prop_name, $prop_value, $this->onServer($on_server) . $path), $options, $on_server);
	}

	/**
	 * svn propset log
	 * edit a log message
	 *
	 * @param mixed $revision
	 * @param mixed $message
	 * @param boolean $on_server
	 * @return array result
	 */
	public function svnPropsetLog($revision, $message, $on_server = false)
	{
		return $this->exec('propset', array($this->properties['log'], $message, $this->onServer($on_server)), array('revprop' => null, 'revision' => $this->buildRevision($revision)), $on_server);
	}

	/**
	 * svn resolved
	 * remove "conflicted" state on path
	 *
	 * @param string $path path relative to svn root
	 * @return array result
	 */
	public function svnResolved($path = '')
	{
		return $this->exec('resolved', $this->local_copy_path . $path);
	}

	/**
	 * svn revert
	 * revert local changes to current rev
	 *
	 * @param string $path path relative to svn root
	 * @return array result
	 */
	public function svnRevert($path = '')
	{
		return $this->exec('revert', $this->local_copy_path . $path);
	}

	/**
	 * svn status
	 * info about local files
	 *
	 * @param string $path path relative to svn root
	 * @param boolean $show_updates shows outdated files
	 * @param boolean $no_ignore includes svn:ignore files
	 * @param boolean $verbose when set to true func returns more info
	 * @param boolean $xml
	 * @return array result
	 */
	public function svnStatus($path = '', $show_updates = false, $no_ignore = false, $verbose = false, $xml = false)
	{
		$options = array();

		if ($show_updates)
		{
			$options['show-updates'] = null;
		}

		if ($no_ignore)
		{
			$options['no-ignore'] = null;
		}

		if ($verbose)
		{
			$options['verbose'] = null;
		}

		if ($xml)
		{
			$options['xml'] = null;
		}

		return $this->exec('status', $this->local_copy_path . $path, $options);
	}

	/**
	 * svn switch
	 * update working copy to a different url
	 *
	 * @param string $path path relative to svn root
	 * @param mixed $revision
	 * @param boolean $on_server
	 * @param mixed $relocate use if you want to change the repo url of your working copy
	 * @return array result
	 */
	public function svnSwitch($path = '', $revision = false, $on_server = false, $relocate = false)
	{
		$options = $arg = array();

		if ($revision && !$relocate)
		{
			$options['revision'] = $this->buildRevision($revision);
		}

		if ($relocate)
		{
			$options['relocate'] = null;
			$on_server = false;

			$arg = array($this->local_copy_path, $relocate, $path);
		}
		else
		{
			$arg = $this->onServer($on_server) . $path;
		}

		return $this->exec('switch', $arg, $options, $on_server);
	}

	/**
	 * svn unlock
	 * unlock a path
	 *
	 * @param string $path path relative to svn root
	 * @param boolean $on_server
	 * @return array result
	 */
	public function svnUnlock($path = '', $on_server = false)
	{
		return $this->exec('unlock', $this->onServer($on_server) . $path, array(), $on_server);
	}

	/**
	 * svn update
	 * update local copy to $revision
	 *
	 * @param string $path path relative to svn root
	 * @param mixed $revision
	 * @return array result
	 */
	public function svnUpdate($path = '', $revision = false)
	{
		$options = array();

		if ($revision)
		{
			$options['revision'] = $this->buildRevision($revision);
		}

		return $this->exec('update', $this->local_copy_path . $path, $options, true);
	}
}
