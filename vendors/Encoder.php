<?php
/**
 * Base32 encoding and decoding
 *
 * The methods in this class are ported/based on the public domain code
 * from bitzi bitcollider.
 *
 * @author Markus Willman, markuwil <at> gmail <dot> com
 */
class Encoder {
	private static $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
	private static $base32Table = array(
		-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,
		-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,
		-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,
		-1,-1,26,27,28,29,30,31,-1,-1,-1,-1,-1,-1,-1,-1,
		-1, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9,10,11,12,13,14,
		15,16,17,18,19,20,21,22,23,24,25,-1,-1,-1,-1,-1,
		-1, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9,10,11,12,13,14,
		15,16,17,18,19,20,21,22,23,24,25,-1,-1,-1,-1,-1,
		-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,
		-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,
		-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,
		-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,
		-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,
		-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,
		-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,
		-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1
	);

	final private function __construct() { }

	/**
	 * Encode input in base32.
	 *
	 * @param string $input
	 * @return string
	 */
	public static function toBase32($input) {
		// Get a binary representation of $input
		$data = unpack('C*', $input);
		$len = sizeof($data);

		$word = 0;
		$output = '';

		for($i = 1, $index = 0; $i <= $len;) {
			/* Is the current word going to span a byte boundary? */
			if($index > 3) {
				$word = (int)($data[$i] & (0xFF >> $index));
				$index = ($index + 5) % 8;
				$word <<= $index;
				if (($i + 1) <= $len)
					$word |= $data[$i + 1] >> (8 - $index);
				$i++;
			} else {
				$word = (int)($data[$i] >> (8 - ($index + 5))) & 0x1F;
				$index = ($index + 5) % 8;
				if($index == 0)
					$i++;
			}

			$output .= self::$base32Chars[$word];
		}

		return $output;
	}

	/**
	 * Decode input from base32.
	 *
	 * @param string $input
	 * @param bool $raw Output raw binary string
	 * @return string Binary string, if $raw is true hex otherwise
	 */
	public static function fromBase32($input, $raw = false) {
		$input = unpack('C*', $input);
		$len = sizeof($input);

		$data = array(1 => 0);
		$output = '';

		for($i = 1, $index = 0, $offset = 1; $i <= $len; ++$i) {
			// Skip what we don't recognise
			$tmp = self::$base32Table[$input[$i]];

			if($tmp == -1)
				continue;

			if($index <= 3) {
				$index = ($index + 5) % 8;
				if($index == 0) {
					$data[$offset] |= $tmp;
					$output .= pack('C', $data[$offset]);
					$offset++;
					$data[$offset] = 0;
				} else {
					$data[$offset] |= $tmp << (8 - $index);
				}
			} else {
				$index = ($index + 5) % 8;
				$data[$offset] |= ($tmp >> $index);
				$output .= pack('C', $data[$offset]);
				$offset++;
				$data[$offset] = $tmp << (8 - $index);
			}
		}

		return $raw ? $output : bin2hex($output);
	}
}
