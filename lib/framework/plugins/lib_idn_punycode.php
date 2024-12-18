<?php
// [LIB - Smart.Framework / Plugins / PunnyCode IDN Converter]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - IDN (Internationalized Domain Name) Converter - PunnyCode
// DEPENDS:
//	* SmartUnicode::
//======================================================

// PHP8

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: SmartPunycode - provides a IDN Converter using the Punycode implementation as described in RFC 3492 (http://tools.ietf.org/html/rfc3492).
 *
 * <code>
 *
 * $domain_iso 		= (string) (new SmartPunycode())->encode('jösefsson.tßst123.org'); // outputs: xn--jsefsson-n4a.xn--tst123-bta.org
 * $domain_unicode 	= (string) (new SmartPunycode())->decode('xn--jsefsson-n4a.xn--tst123-bta.org'); // outputs: jösefsson.tßst123.org
 *
 * $email_iso 		= (string) (new SmartPunycode())->encode('räksmörgås@jösefsson.tßst123.org'); // outputs: xn--rksmrgs@jsefsson-vnbx43ag.xn--tst123-bta.org
 * $email_unicode 	= (string) (new SmartPunycode())->decode('xn--rksmrgs@jsefsson-vnbx43ag.xn--tst123-bta.org'); // outputs: räksmörgås@jösefsson.tßst123.org
 *
 * </code>
 *
 * @usage 		dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 *
 * @depends 	classes: SmartUnicode
 * @version 	v.20211127
 * @package 	Plugins:ConvertersAndParsers
 *
 */
final class SmartPunycode {

	// ->

	// This class is based on: https://raw.githubusercontent.com/true/php-punycode ; License: BSD


	/**
	 * Bootstring parameter values
	 *
	 */
	private const const_BASE         = 36;
	private const const_TMIN         = 1;
	private const const_TMAX         = 26;
	private const const_SKEW         = 38;
	private const const_DAMP         = 700;
	private const const_INITIAL_BIAS = 72;
	private const const_INITIAL_N    = 128;
	private const const_PREFIX       = 'xn--';
	private const const_DELIMITER    = '-';


	/**
	 * Encode table
	 *
	 * @param array
	 */
	private const const_ENCODE_TABLE = [
		'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l',
		'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x',
		'y', 'z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
	];

	/**
	 * Decode table
	 *
	 * @param array
	 */
	private const const_DECODE_TABLE = [
		'a' =>  0, 'b' =>  1, 'c' =>  2, 'd' =>  3, 'e' =>  4, 'f' =>  5,
		'g' =>  6, 'h' =>  7, 'i' =>  8, 'j' =>  9, 'k' => 10, 'l' => 11,
		'm' => 12, 'n' => 13, 'o' => 14, 'p' => 15, 'q' => 16, 'r' => 17,
		's' => 18, 't' => 19, 'u' => 20, 'v' => 21, 'w' => 22, 'x' => 23,
		'y' => 24, 'z' => 25, '0' => 26, '1' => 27, '2' => 28, '3' => 29,
		'4' => 30, '5' => 31, '6' => 32, '7' => 33, '8' => 34, '9' => 35
	];


	/**
	 * Constructor
	 *
	 * @param string $encoding Character encoding
	 */
	public function __construct() {
		//--
		// Init
		//--
	} //END FUNCTION


	/**
	 * Encode a domain name or email address to its Punycode version
	 *
	 * @param string $input Domain name in Unicode to be encoded
	 * @return string Punycode representation in ASCII
	 */
	public function encode($input) {
		$input = (string) SmartUnicode::str_tolower((string)$input);
		$parts = (array) explode('.', $input);
		foreach($parts as &$part) {
			$part = $this->encodePart($part);
		} //end for
		return (string) implode('.', (array)$parts);
	} //END FUNCTION


	/**
	 * Decode a Punycode domain name or email address to its Unicode counterpart
	 *
	 * @param string $input Domain name in Punycode
	 * @return string Unicode domain name
	 */
	public function decode($input) {
		$input = strtolower($input);
		$parts = (array) explode('.', $input);
		foreach($parts as &$part) {
			if(strpos($part, self::const_PREFIX) !== 0) {
				continue;
			} //end if
			$part = (string) substr($part, strlen(self::const_PREFIX));
			$part = $this->decodePart($part);
		} //end foreach
		return implode('.', (array)$parts);
	} //END FUNCTION


	//======= [ PRIVATES ]


	/**
	 * Encode a part of a domain name, such as tld, to its Punycode version
	 *
	 * @param string $input Part of a domain name
	 * @return string Punycode representation of a domain part
	 */
	private function encodePart($input) {
		$codePoints = $this->listCodePoints($input);
		$n = self::const_INITIAL_N;
		$bias = self::const_INITIAL_BIAS;
		$delta = 0;
		$h = $b = count($codePoints['basic']);
		$output = '';
		foreach ($codePoints['basic'] as $code) {
			$output .= $this->codePointToChar($code);
		} //end foreach
		if ($input === $output) {
			return $output;
		} //end if
		if ($b > 0) {
			$output .= self::const_DELIMITER;
		} //end if
		$codePoints['nonBasic'] = array_unique($codePoints['nonBasic']);
		sort($codePoints['nonBasic']);
		$i = 0;
		$length = (int) SmartUnicode::str_len((string)$input);
		while ($h < $length) {
			$m = $codePoints['nonBasic'][$i++];
			$delta = $delta + ($m - $n) * ($h + 1);
			$n = $m;
			foreach ($codePoints['all'] as $c) {
				if ($c < $n || $c < self::const_INITIAL_N) {
					$delta++;
				} //end if
				if ($c === $n) {
					$q = $delta;
					for ($k = self::const_BASE;; $k += self::const_BASE) {
						$t = $this->calculateThreshold($k, $bias);
						if ($q < $t) {
							break;
						} //end if
						$code = $t + ((int)($q - $t) % (self::const_BASE - $t));
						$output .= self::const_ENCODE_TABLE[$code];
						$q = ($q - $t) / (self::const_BASE - $t);
					} //end for
					$output .= self::const_ENCODE_TABLE[(int)$q];
					$bias = $this->adapt($delta, $h + 1, ($h === $b));
					$delta = 0;
					$h++;
				} //end if
			} //end foreach
			$delta++;
			$n++;
		} //end while
		return self::const_PREFIX.$output;
	} //END FUNCTION


	/**
	 * Decode a part of domain name, such as tld
	 *
	 * @param string $input Part of a domain name
	 * @return string Unicode domain part
	 */
	private function decodePart($input) {
		$n = self::const_INITIAL_N;
		$i = 0;
		$bias = self::const_INITIAL_BIAS;
		$output = '';
		$pos = strrpos($input, self::const_DELIMITER);
		if ($pos !== false) {
			$output = substr($input, 0, $pos++);
		} else {
			$pos = 0;
		} //end if else
		$outputLength = strlen($output);
		$inputLength = strlen($input);
		while ($pos < $inputLength) {
			$oldi = $i;
			$w = 1;
			for ($k = self::const_BASE;; $k += self::const_BASE) {
				$digit = self::const_DECODE_TABLE[$input[$pos++]];
				$i = $i + ($digit * $w);
				$t = $this->calculateThreshold($k, $bias);
				if ($digit < $t) {
					break;
				} //end if
				$w = $w * (self::const_BASE - $t);
			} //end for
			$bias = $this->adapt($i - $oldi, ++$outputLength, ($oldi === 0));
			$n = $n + (int) ($i / $outputLength);
			$i = $i % ($outputLength);
			$output = (string) SmartUnicode::sub_str($output, 0, $i).(string)$this->codePointToChar($n).(string)SmartUnicode::sub_str($output, $i, $outputLength - 1);
			$i++;
		} //end while
		return $output;
	} //END FUNCTION


	/**
	 * Calculate the bias threshold to fall between TMIN and TMAX
	 *
	 * @param integer $k
	 * @param integer $bias
	 * @return integer
	 */
	private function calculateThreshold($k, $bias) {
		if ($k <= $bias + self::const_TMIN) {
			return self::const_TMIN;
		} elseif ($k >= $bias + self::const_TMAX) {
			return self::const_TMAX;
		} //end if else
		return $k - $bias;
	} //END FUNCTION


	/**
	 * Bias adaptation
	 *
	 * @param integer $delta
	 * @param integer $numPoints
	 * @param boolean $firstTime
	 * @return integer
	 */
	private function adapt($delta, $numPoints, $firstTime) {
		$delta = (int) (
			($firstTime)
				? $delta / self::const_DAMP
				: $delta / 2
			);
		$delta += (int) ($delta / $numPoints);
		$k = 0;
		while ($delta > ((self::const_BASE - self::const_TMIN) * self::const_TMAX) / 2) {
			$delta = (int) ($delta / (self::const_BASE - self::const_TMIN));
			$k = $k + self::const_BASE;
		} //end while
		$k = $k + (int) (((self::const_BASE - self::const_TMIN + 1) * $delta) / ($delta + self::const_SKEW));
		return $k;
	} //END FUNCTION


	/**
	 * List code points for a given input
	 *
	 * @param string $input
	 * @return array Multi-dimension array with basic, non-basic and aggregated code points
	 */
	private function listCodePoints($input) {
		$codePoints = array(
			'all'      => array(),
			'basic'    => array(),
			'nonBasic' => array(),
		);
		$length = (int) SmartUnicode::str_len((string)$input);
		for ($i = 0; $i < $length; $i++) {
			$char = (string) SmartUnicode::sub_str((string)$input, $i, 1);
			$code = $this->charToCodePoint($char);
			if ($code < 128) {
				$codePoints['all'][] = $codePoints['basic'][] = $code;
			} else {
				$codePoints['all'][] = $codePoints['nonBasic'][] = $code;
			}
		} //end for
		return $codePoints;
	} //END FUNCTION


	/**
	 * Convert a single or multi-byte character to its code point
	 *
	 * @param string $char
	 * @return integer
	 */
	private function charToCodePoint($char) {
		$code = ord($char[0]);
		if ($code < 128) {
			return $code;
		} elseif ($code < 224) {
			return (($code - 192) * 64) + (ord($char[1]) - 128);
		} elseif ($code < 240) {
			return (($code - 224) * 4096) + ((ord($char[1]) - 128) * 64) + (ord($char[2]) - 128);
		} else {
			return (($code - 240) * 262144) + ((ord($char[1]) - 128) * 4096) + ((ord($char[2]) - 128) * 64) + (ord($char[3]) - 128);
		} //end if else
	} //END FUNCTION


	/**
	 * Convert a code point to its single or multi-byte character
	 *
	 * @param integer $code
	 * @return string
	 */
	private function codePointToChar($code) {
		if($code <= 0x7F) {
			return chr($code);
		} elseif($code <= 0x7FF) {
			return chr(($code >> 6) + 192).chr(($code & 63) + 128);
		} elseif($code <= 0xFFFF) {
			return chr(($code >> 12) + 224).chr((($code >> 6) & 63) + 128).chr(($code & 63) + 128);
		} else {
			return chr(($code >> 18) + 240).chr((($code >> 12) & 63) + 128).chr((($code >> 6) & 63) + 128).chr(($code & 63) + 128);
		} //end if else
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//end of php code
