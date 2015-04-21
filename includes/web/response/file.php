<?php
/**
*
* @package apexnet
* @copyright (c) 2015 Markus Willman, markuwil <at> gmail <dot> com / www.apexdc.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_APEXNET')) exit;

use ApexNet\Foundation\Config;

/**
 * Web response implementation for Simple binary files
 */
class web_response_file extends web_response
{
	// SplFileInfo instance for the file
	protected $file;

	// These will hold settings for this file
	protected $mime_type = 'application/octet-stream';
	protected $download = true;

	// Path or a fragment that the server will use to send the file (if supported, we don't actually send content)
	protected $serve_from;

	public function body($file, $force_download = false, $serve_from = false)
	{
		$this->serve_from = $serve_from;
		if (!($file instanceof SplFileInfo))
			$file = new SplFileInfo($file);

		$this->file = $file;
		if (!$this->file->isReadable() || $this->file->isDir())
			throw new Exception('File: "' . $this->file->getPathname() . '" not readable.');

		// load config
		$settings = Config::load('mime');
		$file_ext = $this->file->getExtension();
		if (isset($settings[$file_ext]))
		{
			$settings = $settings[$file_ext];
			$this->mime_type = $settings['content_type'];
			$this->download = ($force_download || $settings['download']);
		}

		// remove any presets to avoid rogue headers
		$this->clear_headers();
		return $this;
	}

	public function output()
	{
		return false;
	}

	public function send()
	{
		$file_size = $this->file->getSize();
		$offset = 0;
		$length = -1;

		$this->header('Accept-Ranges', 'bytes');
		$this->header('Cache-Control', 'public');
		$this->header('Last-Modified', gmdate('D, d M Y H:i:s', $this->file->getMTime()) .' GMT');
		$this->header('Content-Type', $this->mime_type);
		$this->header('Content-Transfer-Encoding', 'binary');
		$this->header('Content-Length', $file_size);
		if ($this->download)
			$this->header('Content-Disposition', 'attachment; filename="'. $this->file->getBasename() .'"');

		if (!empty($this->serve_from))
		{
			if (defined('NGINX_USE_X_ACCEL_REDIRECT'))
			{
				$this->header('X-Accel-Redirect', $this->serve_from);
				$this->send_headers();
				exit();
			}
		}

		if ($this->request->has_header('Range') && !$this->request->has_header('If-Range'))
		{
			list($start, $end) = explode('-', substr($this->request->header('Range'), 6), 2) + array(0);
			$end = ('' === $end) ? $file_size - 1 : (int) $end;

			if ('' === $start)
			{
				$start = $file_size - $end;
				$end = $file_size - 1;
			}
			else
			{
				$start = (int) $start;
			}

			if ($start <= $end)
			{
				if ($start < 0 || $end > $file_size - 1)
				{
					$this->status_code = 416;
				}
				else if ($start !== 0 || $end !== $file_size - 1)
				{
					$length = $end < $file_size ? $end - $start + 1 : -1;
					$offset = $start;

					$this->status_code = 206;
					$this->header('Content-Range', sprintf('bytes %s-%s/%s', $start, $end, $file_size));
					$this->header('Content-Length', $end - $start + 1);
				}
			}
		}

		$this->send_headers();

		$out = fopen('php://output', 'wb');
		$file = fopen($this->file->getPathname(), 'rb');

		stream_copy_to_stream($file, $out, $length, $offset);

		fclose($out);
		fclose($file);
		exit();
	}
}
