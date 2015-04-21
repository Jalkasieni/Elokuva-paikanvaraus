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

// extension to a (probable) mime-type mapping array
return array(
	// Image files
	'bmp'	=> array('content_type' => 'image/bmp', 'download' => false),
	'gif'	=> array('content_type' => 'image/gif', 'download' => false),
	'jpeg'	=> array('content_type' => 'image/jpeg', 'download' => false),
	'jpg'	=> array('content_type' => 'image/jpeg', 'download' => false),
	'jpe'	=> array('content_type' => 'image/jpeg', 'download' => false),
	'png'	=> array('content_type' => 'image/png', 'download' => false),
	'tiff'	=> array('content_type' => 'image/tiff', 'download' => false),
	'tif'	=> array('content_type' => 'image/tiff', 'download' => false),
	'svg'	=> array('content_type' => 'image/svg+xml', 'download' => false),

	// Text formats
	'eml'	=> array('content_type' => 'message/rfc822', 'download' => false),
	'css'	=> array('content_type' => 'text/css', 'download' => false),
	'html'	=> array('content_type' => 'text/html', 'download' => false),
	'htm'	=> array('content_type' => 'text/html', 'download' => false),
	'shtml'	=> array('content_type' => 'text/html', 'download' => false),
	'txt'	=> array('content_type' => 'text/plain', 'download' => false),
	'text'	=> array('content_type' => 'text/plain', 'download' => false),
	'log'	=> array('content_type' => 'text/plain', 'download' => false),
	'rtx'	=> array('content_type' => 'text/richtext', 'download' => false),
	'xml'	=> array('content_type' => 'text/xml', 'download' => false),
	'xsl'	=> array('content_type' => 'text/xml', 'download' => false),
	'json'	=> array('content_type' => 'application/json', 'download' => false),
	'xhtml'	=> array('content_type' => 'application/xhtml+xml', 'download' => false),
	'xht'	=> array('content_type' => 'application/xhtml+xml', 'download' => false),
	'js'	=> array('content_type' => 'application/x-javascript', 'download' => false),
	'ai'	=> array('content_type' => 'application/postscript', 'download' => false),
	'eps'	=> array('content_type' => 'application/postscript', 'download' => false),
	'ps'	=> array('content_type' => 'application/postscript', 'download' => false),
	'csv'	=> array('content_type' => 'text/x-comma-separated-values', 'download' => true),
	'rtf'	=> array('content_type' => 'text/rtf', 'download' => true),

	// PHP source files
	'php'	=> array('content_type' => 'application/x-httpd-php', 'download' => true),
	'php4'	=> array('content_type' => 'application/x-httpd-php', 'download' => true),
	'php3'	=> array('content_type' => 'application/x-httpd-php', 'download' => true),
	'phtml'	=> array('content_type' => 'application/x-httpd-php', 'download' => true),
	'phps'	=> array('content_type' => 'application/x-httpd-php-source', 'download' => true),

	// Compressed files
	'tar'	=> array('content_type' => 'application/x-tar', 'download' => true),
	'tgz'	=> array('content_type' => 'application/x-tar', 'download' => true),
	'zip'	=> array('content_type' => 'application/x-zip', 'download' => true),
	'gtar'	=> array('content_type' => 'application/x-gtar', 'download' => true),
	'gz'	=> array('content_type' => 'application/x-gzip', 'download' => true),
	'7z'	=> array('content_type' => 'application/x-7z-compressed', 'download' => true),

	// Audio files
	'mid'	=> array('content_type' => 'audio/midi', 'download' => true),
	'midi'	=> array('content_type' => 'audio/midi', 'download' => true),
	'mpga'	=> array('content_type' => 'audio/mpeg', 'download' => true),
	'mp2'	=> array('content_type' => 'audio/mpeg', 'download' => true),
	'mp3'	=> array('content_type' => 'audio/mpeg', 'download' => true),
	'aif'	=> array('content_type' => 'audio/x-aiff', 'download' => true),
	'aiff'	=> array('content_type' => 'audio/x-aiff', 'download' => true),
	'aifc'	=> array('content_type' => 'audio/x-aiff', 'download' => true),
	'ram'	=> array('content_type' => 'audio/x-pn-realaudio', 'download' => true),
	'rm'	=> array('content_type' => 'audio/x-pn-realaudio', 'download' => true),
	'rpm'	=> array('content_type' => 'audio/x-pn-realaudio-plugin', 'download' => true),
	'ra'	=> array('content_type' => 'audio/x-realaudio', 'download' => true),
	'wav'	=> array('content_type' => 'audio/x-wav', 'download' => true),

	// Video files
	'rv'	=> array('content_type' => 'video/vnd.rn-realvideo', 'download' => true),
	'mpeg'	=> array('content_type' => 'video/mpeg', 'download' => true),
	'mpg'	=> array('content_type' => 'video/mpeg', 'download' => true),
	'mpe'	=> array('content_type' => 'video/mpeg', 'download' => true),
	'qt'	=> array('content_type' => 'video/quicktime', 'download' => true),
	'mov'	=> array('content_type' => 'video/quicktime', 'download' => true),
	'avi'	=> array('content_type' => 'video/x-msvideo', 'download' => true),
	'movie'	=> array('content_type' => 'video/x-sgi-movie', 'download' => true),

	// Office document formats
	'doc'	=> array('content_type' => 'application/msword', 'download' => true),
	'docx'	=> array('content_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'download' => true),
	'xlsx'	=> array('content_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'download' => true),
	'xl'	=> array('content_type' => 'application/excel', 'download' => true),
	'xls'	=> array('content_type' => 'application/excel', 'download' => true),
	'ppt'	=> array('content_type' => 'application/powerpoint', 'download' => true),

	// Other random or proprietary file formats
	'hqx'	=> array('content_type' => 'application/mac-binhex40', 'download' => true),
	'cpt'	=> array('content_type' => 'application/mac-compactpro', 'download' => true),
	'psd'	=> array('content_type' => 'application/x-photoshop', 'download' => true),
	'oda'	=> array('content_type' => 'application/oda', 'download' => true),
	'pdf'	=> array('content_type' => 'application/pdf', 'download' => true),
	'smi'	=> array('content_type' => 'application/smil', 'download' => true),
	'smil'	=> array('content_type' => 'application/smil', 'download' => true),
	'mif'	=> array('content_type' => 'application/vnd.mif', 'download' => true),
	'wbxml'	=> array('content_type' => 'application/wbxml', 'download' => true),
	'wmlc'	=> array('content_type' => 'application/wmlc', 'download' => true),
	'dcr'	=> array('content_type' => 'application/x-director', 'download' => true),
	'dir'	=> array('content_type' => 'application/x-director', 'download' => true),
	'dxr'	=> array('content_type' => 'application/x-director', 'download' => true),
	'dvi'	=> array('content_type' => 'application/x-dvi', 'download' => true),
	'swf'	=> array('content_type' => 'application/x-shockwave-flash', 'download' => true),
	'sit'	=> array('content_type' => 'application/x-stuffit', 'download' => true)
);
