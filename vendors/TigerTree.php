<?php
/**
 * Class for generating Tiger and TigerTree hashes in PHP,
 *
 * This code chooses automatically from available hash extensions.
 * PHP versions prior to 5.4.0 generate Tiger hashes with incorrect byte order.
 */
class TigerTree {
	private static $tiger_hash;
	private static $tiger_mhash;
	private static $tiger_broken;

	final private function __construct() { }	

	/**
	 * Generates TTH of a string.
	 *
	 * @param string $string
	 * @return string
	 */
	public static function hashString($string, $raw = false)
	{
		// least code duplication
		return self::hashFile('data://text/plain;base64,' . base64_encode($string), $raw);
	}

	/**
	 * Generates TTH of a file.
	 *
	 * @param string $filename
	 * @return string
	 */
	public static function hashFile($filename, $raw = false)
	{
		if (is_dir($filename))
			throw new Exception("Can't hash a directory: $filename.");

		$fp = fopen($filename, "rb");
		if ($fp === false)
			throw new Exception("Error attempting to read file: $filename.");

		$i = 1;
		$hashes = array();
		while (!feof($fp))
		{
			$buf = fread($fp, 1024);
			if ($buf || ($i == 1))
			{
				$hashes[$i] = self::tigerHash("\0".$buf);
				$j = 1;
				while ($i % ($j * 2) == 0)
				{
					$hashes[$i] = self::tigerHash("\1".$hashes[$i - $j].$hashes[$i]);
					unset($hashes[$i - $j]);
					$j = round($j * 2);
				}
				++$i;
			}
		}

		$k = 1;
		while ($i > $k)
			$k = round($k * 2);

		for (; $i <= $k; ++$i)
		{
			$j = 1;
			while ($i % ($j * 2) == 0)
			{
				if (isset($hashes[$i]))
				{
					$hashes[$i] = self::tigerHash("\1".$hashes[$i - $j].$hashes[$i]);
				}
				else if (isset($hashes[$i - $j]))
				{
					$hashes[$i] = $hashes[$i - $j];
				}
				unset($hashes[$i - $j]);
				$j = round($j * 2);
			}
		}

		fclose($fp);
		return $raw ? $hashes[$i-1] : Encoder::toBase32($hashes[$i-1]);
	}

	/**
	 * Generates a Tiger hash
	 * Automatically chooses between hash() and mhash().
	 */
	public static function tigerHash($string, $raw = true)
	{
		if (!isset(self::$tiger_hash))
			 self::$tiger_hash = function_exists('hash_algos') && in_array('tiger192,3', hash_algos());
		if (!isset(self::$tiger_mhash))
			self::$tiger_mhash = function_exists('mhash');

		if (!self::$tiger_hash && !self::$tiger_mhash)
			throw new Exception(__METHOD__ . ': Neither Tiger hash function is available.');

		if (!isset(self::$tiger_broken))
			self::$tiger_broken = version_compare(PHP_VERSION, '5.4.0') < 0;

		// if we get here, we know it is either or situation
		$result = self::$tiger_hash ? hash("tiger192,3", $string, true) : mhash(MHASH_TIGER, $string);

		if (self::$tiger_broken)
			$result = self::fixTiger($result);

		return $raw ? $result : Encoder::toBase32($result);
	}

	/**
	 * Repairs tiger hash for compatibility (byte order).
	 *
	 * @url https://bugs.php.net/bug.php?id=32463 (among others)
	 * @param string $binary_hash
	 * @return string
	 */
	public static function fixTiger($binary_hash)
	{
		$data = unpack('C*', $binary_hash);
		$fixed = '';
		for ($j = 0, $size = sizeof($data); $j < $size; ++$j)
			$fixed .= pack('C', $data[($j^7) + 1]);
		return $fixed;
	}
}
