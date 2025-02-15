<?php
// [LIB - Smart.Framework / Archive: Snappy]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - Compress/Uncompress Support
//======================================================


//--
// gzdeflate / gzinflate (rfc1951) have no checksum for data integrity by default ; if sha1 checksums are integrated separately, it can be better than other zlib algorithms
//--
if((!function_exists('gzdeflate')) OR (!function_exists('gzinflate'))) {
	@http_response_code(500);
	die('ERROR: The PHP ZLIB Extension (gzdeflate/gzinflate) is required for Smart.Framework / Lib Utils');
} //end if
//--
// gzencode / gzdecode (rfc1952) is the gzip compatible algorithm which uses CRC32 minimal checksums (a bit safer and faster than ADLER32)
//--
if((!function_exists('gzencode')) OR (!function_exists('gzdecode'))) {
	@http_response_code(500);
	die('ERROR: The PHP ZLIB Extension (gzencode/gzdecode) is required for Smart.Framework / Lib Utils');
} //end if
//--


// [PHP8]


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

/**
 * Class: SmartZLib - Provides ZLib Deflate/Inflate methods with extra checksum.
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @access 		PUBLIC
 * @depends 	classes: Smart
 * @version 	v.20250214
 * @package 	@Core
 *
 */
final class SmartZLib {

	// ::


	//================================================================
	// Archive data (string) to B64/Zlib-Raw/Hex (v3 only)
	public static function dataArchive(?string $data) : string {
		//-- if empty data, return empty string
		if((string)$data == '') {
			return '';
		} //end if
		//-- checksum of original data
		$chksum = (string) SmartHashCrypto::sh3a384((string)$data, true); // B64
		//-- prepare data and add checksum
		$data = (string) trim((string)bin2hex((string)$data)).'#CKSUM384V3#'.$chksum; // use lower hex for data and for checksum ; compression will be better using a more restricted charset and not upper letters combined with lower letters
		$out = gzdeflate((string)$data, -1, ZLIB_ENCODING_RAW); // don't make it string, may return false ; -1 = default compression of the zlib library is used which is 6
		//-- check for possible deflate errors
		if(($out === false) OR ((string)$out == '')) {
			Smart::log_warning(__METHOD__.' # ZLib Deflate ERROR');
			return '';
		} //end if
		$len_data = (int) strlen((string)$data);
		$len_arch = (int) strlen((string)$out);
		if(((int)$len_data > 0) AND ((int)$len_arch > 0)) {
			$ratio = (float) ((int)$len_data / (int)$len_arch); // division by zero is checked above as $out not to be empty!
		} else {
			$ratio = 0;
		} //end if
		if((float)$ratio <= 0) { // check for empty input / output !
			Smart::log_warning(__METHOD__.' #  ZLib Data Ratio is zero');
			return '';
		} //end if
		if((float)$ratio > 32768) { // check for this bug in ZLib {{{SYNC-GZ-ARCHIVE-ERR-CHECK}}}
			Smart::log_warning(__METHOD__.' # ZLib Data Ratio is higher than 32768');
			return '';
		} //end if
		//--
		$data = null; // free mem
		//-- add signature
		$out = (string) trim((string)Smart::b64_enc((string)$out))."\n".'[SFZ.20231031/B64.ZLibRaw.hex]'; // v3
		$out .= "\n".'('.self::data_cksgn_archive((string)$out).')'; // v3+ signature
		//-- test unarchive
		$unarch_checksum = (string) SmartHashCrypto::sh3a384((string)self::dataUnarchive((string)$out), true); // B64
		if((string)$chksum !== (string)$unarch_checksum) { // check: if there is a serious bug with ZLib or PHP we can't tolerate, so test decompress here !!
			Smart::log_warning(__METHOD__.' # Data Encode Check Failed');
			return '';
		} //end if
		//-- if all test pass, return archived data
		return (string) $out;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// Unarchive data (string) from B64/Zlib-Raw/Hex (v2 and v1)
	public static function dataUnarchive(?string $data) : string {
		//--
		$data = (string) trim((string)$data);
		//--
		if((string)$data == '') {
			return '';
		} //end if
		//--
		$out = ''; // initialize output
		//-- pre-process
		$arr = array();
		$arr = (array) explode("\n", (string)$data, 4); // let it be 4 not 3 ; if there is some garbage on a new line after signature ; also v3 have an extra checksum ... just let it there ...
		$data = null; // free mem
		$arr[0] = (string) trim((string)($arr[0] ?? '')); // is the data packet
		$arr[1] = (string) trim((string)($arr[1] ?? '')); // signature
		$arr[2] = (string) trim((string)($arr[2] ?? '')); // package checksum (just on v3)
		//-- check signature
		if((string)$arr[1] == '') {
			Smart::log_warning(__METHOD__.' # Empty Package Signature');
			return '';
		} //end if
		$versionDetected = 0;
		if((string)$arr[1] == '[SFZ.20231031/B64.ZLibRaw.hex]') { // v3
			$versionDetected = 3;
		} elseif((string)$arr[1] == 'SFZ.20210818/B64.ZLibRaw.hex') { // v2
			$versionDetected = 2;
		} elseif((string)$arr[1] == 'PHP.SF.151129/B64.ZLibRaw.HEX') { // v1
			$versionDetected = 1;
		} //end if else
		if((int)$versionDetected <= 0) { // signature is different, try to decode but log the error
			Smart::log_warning(__METHOD__.' # Invalid Package Signature: `'.$arr[1].'`');
			return '';
		} //end if
		//-- verify package checksum (v3+ only)
		if((int)$versionDetected == 3) {
			//--
			if(
				((string)$arr[2] == '')
				OR
				(strpos((string)$arr[2], '(') !== 0)
				OR
				(substr((string)$arr[2], -1, 1) !== ')')
			) {
				Smart::log_warning(__METHOD__.' # Empty or Malformed Package CheckSign');
				return '';
			} //end if
			//--
			$cksgn = (string) '('.self::data_cksgn_archive((string)$arr[0]."\n".$arr[1]).')';
			if((string)$cksgn !== (string)$arr[2]) {
				Smart::log_warning(__METHOD__.' # Invalid Package CheckSign, signature does not match, archived data is unsafe !');
				return '';
			} //end if
			//--
		} //end if
		//-- decode it (at least try)
		if((string)$arr[0] == '') {
			Smart::log_warning(__METHOD__.' # Invalid Package Format @ v.'.$versionDetected);
			return '';
		} //end if
		$out = Smart::b64_dec((string)$arr[0]); // NON-STRICT ! don't make it string, may return null
		if(($out === null) OR ((string)trim((string)$out) == '')) { // use trim, the deflated string can't contain only spaces, expect having hex data + checksum
			Smart::log_warning(__METHOD__.' # Invalid B64 Data @ v.'.$versionDetected);
			return '';
		} //end if
		$out = gzinflate((string)$out);
		if(($out === false) OR ((string)trim((string)$out) == '')) {
			Smart::log_warning(__METHOD__.' # Invalid Zlib GzInflate Data @ v.'.$versionDetected);
			return '';
		} //end if
		//-- post-process
		$versionCksumSeparator = '#CKSUM384V3#'; // v3
		if((int)$versionDetected == 2) {
			$versionCksumSeparator = '#CKSUM256#'; // v2
		} elseif((int)$versionDetected == 1) {
			$versionCksumSeparator = '#CHECKSUM-SHA1#'; // v1
		} //end if
		if(((string)trim((string)$versionCksumSeparator) == '') OR (strpos((string)$out, (string)$versionCksumSeparator) === false)) {
			Smart::log_warning(__METHOD__.' # Invalid Packet, no Checksum. This can occur if decompression failed or an invalid packet has been assigned @ v.'.$versionDetected);
			return '';
		} //end if
		//--
		$arr = array();
		$arr = (array) explode((string)$versionCksumSeparator, (string)$out, 2); // should be 2 ... otherwise is invalid
		$out = '';
		$arr[0] = (string) trim((string)($arr[0] ?? null));
		$arr[1] = (string) trim((string)($arr[1] ?? null));
		//--
		if((int)$versionDetected == 1) {
			$arr[0] = (string) strtolower((string)$arr[0]); // on v1 must be done this conversion, the v1 was exporting upper letter hex
		} //end if
		//--
		if((int)strlen((string)$arr[0]) !== (int)strspn((string)$arr[0], (string)Smart::CHARSET_BASE_16)) {
			Smart::log_warning(__METHOD__.' # Invalid HEX Charset v.'.$versionDetected);
			return '';
		} //end if
		//--
		$arr[0] = (string) Smart::safe_hex_2_bin((string)$arr[0], true, false);
		if((string)$arr[0] == '') { // no trim here ... (the real string may contain only some spaces)
			Smart::log_warning(__METHOD__.' # Invalid HEX Data v.'.$versionDetected);
			return '';
		} //end if
		//--
		$arr[1] = (string) trim((string)$arr[1]); // the checksum
		$is_checksum_ok = false;
		if((int)$versionDetected == 1) { // v1, sha1, HEX
			if((string)SmartHashCrypto::sha1((string)$arr[0]) == (string)$arr[1]) {
				$is_checksum_ok = true;
			} //end if
		} elseif((int)$versionDetected == 2) { // v2, sha256, HEX
			if((string)SmartHashCrypto::sha256((string)$arr[0]) == (string)$arr[1]) {
				$is_checksum_ok = true;
			} //end if
		} else { // v3, sha3-384, B64
			if((string)SmartHashCrypto::sh3a384((string)$arr[0], true) == (string)$arr[1]) {
				$is_checksum_ok = true;
			} //end if
		} //end if else
		if($is_checksum_ok !== true) {
			Smart::log_warning(__METHOD__.' # Invalid Packet, Checksum FAILED. A checksum was found but is invalid: `'.$arr[1].'` on v.'.$versionDetected);
			return '';
		} //end if
		//--
		return (string) $arr[0];
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function data_cksgn_archive(string $pak) : string {
		//--
		$len = (string) (int) strlen((string)$pak);
		//--
		$crc32b  = (string) SmartHashCrypto::crc32b((string)$pak, true); // b36
		$sh3a512 = (string) SmartHashCrypto::sh3a512((string)$pak."\v".$len, true); // b64
		$sh3a384 = (string) SmartHashCrypto::sh3a384((string)$sh3a512.chr(0).$pak, true); // b64
		$sh3a256 = (string) SmartHashCrypto::sh3a256((string)$pak.chr(0).$sh3a384, true); // b64
		$sh3a224 = (string) SmartHashCrypto::sh3a224((string)$sh3a512.chr(0).$pak.chr(0).$crc32b.chr(0).$sh3a256.chr(0).$sh3a384, true); // b64
		//--
		$hmacSh3a224 = (string) SmartHashCrypto::hmac('SHA3-224', (string)$len."\v".$pak, (string)$sh3a224); // hex
		//--
		return (string) Smart::base_from_hex_convert((string)$hmacSh3a224, 62); // b62
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

/**
 * Class: SmartGZip - Provides GZip compatible Compress/Uncompress methods with extra checksum.
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @access 		PUBLIC
 * @depends 	classes: Smart
 * @version 	v.20250214
 * @package 	@Core
 *
 */
final class SmartGZip {

	// ::


	final public static function compress(?string $raw_data) : string {
		//--
		if((string)$raw_data == '') {
			return '';
		} //end if
		//-- crc
		$crc32b = (string) SmartHashCrypto::crc32b((string)$raw_data, true); // b36
		//-- compress
		$data = gzencode((string)$raw_data, -1, FORCE_GZIP); // don't make it string, may return false ; -1 = default compression of the zlib library is used which is 6
		//-- check for possible zlib-pack errors
		if(($data === false) OR ((string)$data == '')) {
			Smart::log_warning(__METHOD__.' # GZ-Encode ERROR');
			return '';
		} //end if
		$len_data = (int) strlen((string)$raw_data);
		$raw_data = null; // free mem
		$len_arch = (int) strlen((string)$data);
		if(((int)$len_data > 0) AND ((int)$len_arch > 0)) {
			$ratio = (float) ((int)$len_data / (int)$len_arch); // division by zero is checked above as $out not to be empty!
		} else {
			$ratio = 0;
		} //end if
		if((float)$ratio <= 0) { // check for empty input / output !
			Smart::log_warning(__METHOD__.' # Data Ratio is zero');
			return '';
		} //end if
		if((float)$ratio > 32768) { // check for this bug in ZLib {{{SYNC-GZ-ARCHIVE-ERR-CHECK}}}
			Smart::log_warning(__METHOD__.' # Data Ratio is higher than 32768');
			return '';
		} //end if
		//--
		return (string) Smart::b64_enc((string)$data).'#'.$crc32b;
		//--
	} //END FUNCTION


	final public static function uncompress(?string $y_arch) : ?string {
		//--
		$y_arch = (string) trim((string)$y_arch);
		if((string)$y_arch == '') {
			return null; // no data to unarchive, return empty string
		} //end if
		//--
		$arr = (array) explode('#', (string)$y_arch, 2);
		$y_arch = null; // free mem
		$data = (string) trim((string)($arr[0] ?? null));
		$crc32b = (string) trim((string)($arr[1] ?? null));
		//--
		if(
			((string)$data == '') // empty arch
			OR
			((string)$crc32b == '') // no crc32b
		) {
			return null; // no data to unarchive or no checksum
		} //end if
		//--
		$data = Smart::b64_dec((string)$data, true); // STRICT ! don't make it string, may return null
		if(($data === null) OR ((string)trim((string)$data) == '')) { // use trim, the deflated string can't contain only spaces
			Smart::log_warning('SmartPersistentCache / Cache Variable Decompress :: Empty Data after B64-Decode ! ...');
			return null; // something went wrong after b64 decoding ...
		} //end if
		//--
		$data = gzdecode((string)$data); // don't make it string, may return false
		if(($data === false) OR ((string)trim((string)$data) == '')) { // use trim, the string before unseryalize can't contain only spaces
			Smart::log_warning('SmartPersistentCache / Cache Variable Decompress :: Empty Data after Zlib GZ-Decode ! ...');
			return null;
		} //end if
		//--
		if((string)$crc32b !== (string)SmartHashCrypto::crc32b((string)$data, true)) { // b36
			return null; // crc32b does not match
		} //end if
		//--
		return (string) $data;
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//======================================================
// Snappy PHP code is based on the following Project:
// github.com/flow-php/snappy
// License: BSD # (c) 2020-present Flow PHP
//======================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

/**
 * Class: SmartSnappy - Provides Snappy Compress/Uncompress methods.
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @access 		PUBLIC
 * @depends 	classes: Smart
 * @version 	v.20250214
 * @package 	@Core
 *
 */
final class SmartSnappy {

	// ::

	public static function compress(string $plainText) : string {
		//--
		if((string)$plainText == '') {
			return '';
		} //end if
		//--
		$snappy = new SnappyCompressor((string)$plainText);
		//-- b64u
		return (string) 'sy1!'.';'.Smart::b64s_enc((string)pack('C*', ...(array)$snappy->compressToBuffer()), false).';'.SmartHashCrypto::crc32b((string)$plainText, true); // b64
		//--
	} //END FUNCTION


	public static function uncompress(string $b64Arch) : string {
		//--
		if((string)$b64Arch == '') {
			return '';
		} //end if
		//--
		$b64Arch = (string) trim((string)$b64Arch);
		if((string)$b64Arch == '') {
			return '';
		} //end if
		//--
		$arr = (array) explode(';', (string)$b64Arch, 3);
		$b64Arch = null; // free mem
		if((string)trim((string)($arr[0] ?? null)) !== 'sy1!') {
			Smart::log_warning(__METHOD__.' # Snappy Package: Invalid Signature');
			return ''; // invalid prefix
		} //end if
		$arr[1] = (string) \trim((string)($arr[1] ?? null));
		if((string)$arr[1] == '') {
			Smart::log_warning(__METHOD__.' # Snappy Package: Empty B64 Data');
			return ''; // invalid core
		} //end if
		$arr[2] = (string) \trim((string)($arr[2] ?? null));
		if(((string)$arr[2] == '') || ((int)strlen((string)$arr[2]) < 6) || ((int)strlen((string)$arr[2]) > 10)) {
			Smart::log_warning(__METHOD__.' # Snappy Package: Empty CRC');
			return ''; // invalid crc
		} //end if
		//--
		$data = (string) $arr[1];
		$crc = (string) $arr[2];
		$arr = null; // free mem
		//--
		$data = (string) Smart::b64s_dec((string)$data, true); // strict
		if((string)$data == '') {
			Smart::log_warning(__METHOD__.' # Snappy Package: Empty Arch Data');
			return ''; // invalid core
		} //end if
		$snappy = new SnappyDecompressor((string)$data);
		$data = (string) pack('C*', ...(array)$snappy->uncompressToBuffer());
		$snappy = null; // free mem
		if((string)$data == '') {
			Smart::log_warning(__METHOD__.' # Snappy Package: Empty Data');
			return ''; // invalid core
		} //end if
		//--
		if((string)SmartHashCrypto::crc32b((string)$data, true) !== (string)$crc) {
			Smart::log_warning(__METHOD__.' # Snappy Package: Data CRC Failed');
			return '';
		} //end if
		//--
		return (string) $data;
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

/**
 * Class: SnappyUtils - Provides Snappy Utility methods.
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @access 		private
 * @internal
 *
 * @depends 	classes: -
 * @version 	v.20250214
 * @package 	@Core
 *
 */
final class SnappyUtils {

	// ::

	public static function copyBytes(array $fromArray, int $fromPos, array &$toArray, int $toPos, int $length) : void {
		//--
		for($i=0; $i<$length; $i++) {
			$toArray[(int)((int)$toPos + (int)$i)] = (int) $fromArray[(int)((int)$fromPos + (int)$i)];
		} //end for
		//--
	} //END FUNCTION


} //END FUNCTION


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

/**
 * Class: SnappyCompressor - Provides Snappy Compress methods.
 *
 * @usage  		dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 *
 * @access 		private
 * @internal
 *
 * @depends 	classes: Smart
 * @version 	v.20250214
 * @package 	@Core
 *
 */
final class SnappyCompressor {

	// ->

	private const BLOCK_LOG = 16;
	private const BLOCK_SIZE = 1 << self::BLOCK_LOG;
	private const MAX_HASH_TABLE_BITS = 14;

	private array $arrData = [];
	private int   $lenArrData = 0;
	private array $hashTables = [];


	public function __construct(string $plainText) {
		//--
		$this->arrData = (array) array_values((array)unpack('C*', (string)$plainText) ?: []);
		//--
		$this->lenArrData = (int) Smart::array_size($this->arrData);
		//--
	} //END FUNCTION


	public function compressToBuffer() : array {
		//--
		$outBuffer = [];
		//--
		$pos = 0;
		$outPos = 0;
		//--
		$outPos = (int) $this->putVarInt((int)$this->lenArrData, $outBuffer, (int)$outPos);
		//--
		while((int)$pos < (int)$this->lenArrData) {
			$fragmentSize = (int) min((int)$this->lenArrData - (int)$pos, (int)self::BLOCK_SIZE);
			$outPos = $this->compressFragment((array)$this->arrData, (int)$pos, (int)$fragmentSize, $outBuffer, (int)$outPos);
			$pos += (int) $fragmentSize;
		} //end while
		//--
		return (array) $outBuffer;
		//--
	} //END FUNCTION


	public function maxCompressedLength() : int {
		//--
		$sourceLen = (int) Smart::array_size($this->arrData);
		//--
		return (int) (32 + (int)$sourceLen + (int)floor((int)$sourceLen / 6));
		//--
	} //END FUNCTION


	private function compressFragment(array $input, int $ip, int $inputSize, array &$output, int $op) : int {
		//--
		$hashTableBits = 1;
		while(((1 << (int)$hashTableBits) <= (int)$inputSize) && ((int)$hashTableBits <= (int)self::MAX_HASH_TABLE_BITS)) {
			$hashTableBits++;
		} //end while
		$hashTableBits--;
		//--
		$hashFuncShift = (int) (32 - (int)$hashTableBits);
		//--
		if(!isset($this->hashTables[(int)$hashTableBits])) {
			$this->hashTables[(int)$hashTableBits] = (array) array_fill(0, 1 << (int)$hashTableBits, 0);
		} //end if
		//--
		$hashTable = [];
		foreach($this->hashTables[(int)$hashTableBits] as $key => $value) {
			$hashTable[(int)$key] = 0;
		} //end foreach
		//--
		$ipEnd    = (int) ((int)$ip + (int)$inputSize);
		$baseIp   = (int) $ip;
		$nextEmit = (int) $ip;
		//--
		$inputMargin = 15;
		//--
		if((int)$inputSize >= (int)$inputMargin) {
			//--
			$ipLimit = (int) ((int)$ipEnd - (int)$inputMargin);
			$ip++;
			$nextHash = (int) $this->hashFunc((int)$this->load32((array)$input, (int)$ip), (int)$hashFuncShift);
			$candidate = 0;
			//--
			while(true) {
				//--
				$skip = 32;
				$nextIp = (int) $ip;
				//--
				do {
					//--
					$ip = (int) $nextIp;
					$hash = (int) $nextHash;
					$bytesBetweenHashLookups = (int) ((int)$skip / 32);
					$skip++;
					$nextIp = $ip + $bytesBetweenHashLookups;
					//--
					if((int)$ip > (int)$ipLimit) {
						break 2;
					} //end if
					//--
					$nextHash = (int) $this->hashFunc((int)$this->load32((array)$input, (int)$nextIp), (int)$hashFuncShift);
					$candidate = (int) ((int)$baseIp + (int)$hashTable[(int)$hash]);
					$hashTable[(int)$hash] = (int) ((int)$ip - (int)$baseIp);
					//--
				} while(!$this->equals32((array)$input, (int)$ip, (int)$candidate));
				//--
				$op = (int) $this->emitLiteral($input, (int)$nextEmit, (int)((int)$ip - (int)$nextEmit), $output, (int)$op);
				//--
				do {
					//--
					$base = (int) $ip;
					$matched = 4;
					//--
					while(((int)((int)$ip + (int)$matched) < (int)$ipEnd) && ($input[(int)((int)$ip + (int)$matched)] === $input[(int)((int)$candidate + (int)$matched)])) {
						$matched++;
					} //end while
					//--
					$ip += (int) $matched;
					$offset = (int) ((int)$base - (int)$candidate);
					$op = (int) $this->emitCopy($output, (int)$op, (int)$offset, (int)$matched);
					$nextEmit = (int) $ip;
					//--
					if((int)$ip >= (int)$ipLimit) {
						break 2;
					} //end if
					//--
					$prevHash = (int) $this->hashFunc((int)$this->load32((array)$input, (int)((int)$ip - 1)), (int)$hashFuncShift);
					$hashTable[(int)$prevHash] = (int) ((int)$ip - 1 - (int)$baseIp);
					$curHash = (int) $this->hashFunc((int)$this->load32((array)$input, (int)$ip), (int)$hashFuncShift);
					$candidate = (int) ((int)$baseIp + (int)$hashTable[(int)$curHash]);
					$hashTable[(int)$curHash] = (int) ((int)$ip - (int)$baseIp);
					//--
				} while($this->equals32((array)$input, (int)$ip, (int)$candidate));
				//--
				$ip++;
				$nextHash = (int) $this->hashFunc((int)$this->load32((array)$input, (int)$ip), (int)$hashFuncShift);
				//--
			} //end while
			//--
		} //end if
		//--
		if((int)$nextEmit < (int)$ipEnd) {
			return (int) $this->emitLiteral($input, (int)$nextEmit, (int)((int)$ipEnd - (int)$nextEmit), $output, (int)$op);
		} //end if
		//--
		return (int) $op;
		//--
	} //END FUNCTION


	private function emitCopy(array &$output, int $op, int $offset, int $len) : int {
		//--
		while((int)$len >= 68) {
			$op = (int) $this->emitCopyLessThan64($output, (int)$op, (int)$offset, 64);
			$len -= 64;
		} //end while
		//--
		if((int)$len > 64) {
			$op = (int) $this->emitCopyLessThan64($output, (int)$op, (int)$offset, 60);
			$len -= 60;
		} //end if
		//--
		return (int) $this->emitCopyLessThan64($output, (int)$op, (int)$offset, (int)$len);
		//--
	} //END FUNCTION


	private function emitCopyLessThan64(array &$output, int $op, int $offset, int $len) : int {
		//--
		if(((int)$len < 12) && ((int)$offset < 2048)) {
			//--
			$output[(int)$op] = 1 + (((int)$len - 4) << 2) + (((int)$offset >> 8) << 5);
			$output[(int)((int)$op + 1)] = (int)$offset & 0xFF;
			//--
			return (int) ((int)$op + 2);
			//--
		} //end if
		//--
		$output[(int)$op] = 2 + (((int)$len - 1) << 2);
		$output[(int)((int)$op + 1)] = (int)$offset & 0xFF;
		$output[(int)((int)$op + 2)] = (int)$offset >> 8;
		//--
		return (int) ((int)$op + 3);
		//--
	} //END FUNCTION


	private function emitLiteral(array &$input, int $ip, int $len, array &$output, int $op) : int {
		//--
		if((int)$len <= 60) {
			$output[(int)$op] = (int) ((int)$len - 1) << 2;
			$op++;
		} elseif((int)$len < 256) {
			$output[(int)$op] = 60 << 2;
			$output[(int)((int)$op + 1)] = (int) ((int)$len - 1);
			$op += 2;
		} else {
			$output[(int)$op] = 61 << 2;
			$output[(int)((int)$op + 1)] = (int) ((int)$len - 1) & 0xFF;
			$output[(int)((int)$op + 2)] = (int) ((int)$len - 1) >> 8;
			$op += 3;
		} //end if else
		//--
		SnappyUtils::copyBytes($input, (int)$ip, $output, (int)$op, (int)$len);
		//--
		return (int) ((int)$op + (int)$len);
		//--
	} //END FUNCTION


	private function equals32(array $array, int $pos1, int $pos2) : bool {
		//--
		return (bool) (
			$array[(int)$pos1] === $array[(int)$pos2]
			&&
			$array[(int)((int)$pos1 + 1)] === $array[(int)((int)$pos2 + 1)]
			&&
			$array[(int)((int)$pos1 + 2)] === $array[(int)((int)$pos2 + 2)]
			&&
			$array[(int)((int)$pos1 + 3)] === $array[(int)((int)$pos2 + 3)]
		);
		//--
	} //END FUNCTION


	private function hashFunc(int $key, int $hashFuncShift) : int {
		//--
		$multiplied = (int) ((int)$key * 0x1E35A7BD);
		//--
		return ((int)$multiplied >> (int)$hashFuncShift) & ((1 << (32 - (int)$hashFuncShift)) - 1); // emulate unsigned right shift in PHP
		//--
	} //END FUNCTION


	private function load32(array $array, int $pos) : int {
		//--
		if(!isset($array[(int)$pos])) {
			return 0;
		} //end if
		//--
		if(!isset($array[(int)((int)$pos + 1)])) {
			return (int) $array[(int)$pos];
		} //end if
		//--
		if(!isset($array[(int)((int)$pos + 2)])) {
			return (int) ((int)$array[(int)$pos] + (int)((int)$array[(int)((int)$pos + 1)] << 8));
		} //end if
		//--
		if(!isset($array[(int)((int)$pos + 3)])) {
			return (int) ((int)$array[$pos] + (int)((int)$array[(int)((int)$pos + 1)] << 8) + (int)((int)$array[(int)((int)$pos + 2)] << 16));
		} //end if
		//--
		return (int) ((int)$array[(int)$pos] + (int)((int)$array[(int)((int)$pos + 1)] << 8) + (int)((int)$array[(int)((int)$pos + 2)] << 16) + (int)((int)$array[(int)((int)$pos + 3)] << 24));
		//--
	} //END FUNCTION


	private function putVarInt(int $value, array &$output, int $op) : int {
		//--
		do {
			$output[(int)$op] = (int) ((int)$value & 0x7F);
			$value = (int) ((int)$value >> 7);
			if((int)$value > 0) {
				$output[(int)$op] += 0x80;
			} //end if
			$op++;
		} while((int)$value > 0);
		//--
		return (int) $op;
		//--
	} //END FUNCTION
	//--


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

/**
 * Class: SnappyCompressor - Provides Snappy Compress methods.
 *
 * @usage  		dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 *
 * @access 		private
 * @internal
 *
 * @depends 	classes: Smart
 * @version 	v.20250214
 * @package 	@Core
 *
 */
final class SnappyDecompressor {

	// ->

	private const WORD_MASK = [ 0, 0xFF, 0xFFFF, 0xFFFFFF, 0xFFFFFFFF ];

	private array $arrData = [];
	private int   $lenArrData = 0;
	private int   $pos = 0;


	public function __construct(string $compressedText) {
		//--
		$this->arrData = (array) array_values((array)unpack('C*', (string)$compressedText) ?: []);
		//--
		$this->lenArrData = (int) Smart::array_size($this->arrData);
		//--
	} //END FUNCTION


	public function readUncompressedLength() : int {
		//--
		$result = 0;
		$shift = 0;
		//--
		while(((int)$shift < 32) && ((int)$this->pos < (int)$this->lenArrData)) {
			$c = (int) $this->arrData[$this->pos];
			$this->pos++;
			$val = (int) ((int)$c & 0x7F);
			if(((int)$val << (int)$shift >> (int)$shift) !== $val) {
				return -1;
			} //end if
			$result |= (int)$val << (int)$shift;
			if((int)$c < 128) {
				return (int) $result;
			} //end if
			$shift += 7;
		} //end while
		//--
		return -1;
		//--
	} //END FUNCTION


	public function uncompressToBuffer() : array {
		//--
		$outBuffer = [];
		//--
		$outBuffer = (array) array_fill(0, (int)$this->readUncompressedLength(), 0);
		$pos = (int) $this->pos;
		$outPos = 0;
		$len = 0;
		$offset = 0;
		//--
		while((int)$pos < (int)Smart::array_size($this->arrData)) {
			//--
			$c = (int) $this->arrData[(int)$pos];
			$pos++;
			//--
			if(((int)$c & 0x3) === 0) {
				//--
				$len = (int) (((int)$c >> 2) + 1); // literal
				//--
				if((int)$len > 60) {
					if((int)((int)$pos + 3) >= (int)$this->lenArrData) {
						return [];
					} //end if
					$smallLen = (int) ((int)$len - 60);
					$len = (int)((int)$this->arrData[(int)$pos] + (int)((int)$this->arrData[(int)((int)$pos + 1)] << 8) + (int)((int)$this->arrData[(int)((int)$pos + 2)] << 16) + (int)((int)$this->arrData[(int)((int)$pos + 3)] << 24));
					$len = (int) ((int)((int)$len & (int)self::WORD_MASK[(int)$smallLen]) + 1);
					$pos += (int) $smallLen;
				} //end if
				//--
				if((int)((int)$pos + (int)$len) > (int)$this->lenArrData) {
					return [];
				} //end if
				//--
				SnappyUtils::copyBytes($this->arrData, (int)$pos, $outBuffer, (int)$outPos, (int)$len);
				//--
				$pos += (int) $len;
				$outPos += (int) $len;
				//--
			} else {
				//--
				switch((int)((int)$c & 0x3)) {
					case 1:
						$len = (int) ((((int)$c >> 2) & 0x7) + 4);
						$offset = (int) ((int)$this->arrData[(int)$pos] + (int)((int)((int)$c >> 5) << 8));
						$pos++;
						break;
					case 2:
						if((int)((int)$pos + 1) >= (int)$this->lenArrData) {
							return [];
						} //end if
						$len = (int) (((int)$c >> 2) + 1);
						$offset = (int) ((int)$this->arrData[(int)$pos] + (int)((int)$this->arrData[(int)((int)$pos + 1)] << 8));
						$pos += 2;
						break;
					case 3:
						if((int)((int)$pos + 3) >= (int)$this->lenArrData) {
							return [];
						} //end if
						$len = (int) ((int)((int)$c >> 2) + 1);
						$offset = (int) ((int)$this->arrData[(int)$pos] + (int)((int)$this->arrData[(int)((int)$pos + 1)] << 8) + (int)((int)$this->arrData[(int)((int)$pos + 2)] << 16) + (int)((int)$this->arrData[(int)((int)$pos + 3)] << 24));
						$pos += 4;
						break;
					default:
						// n/a
				} //end switch
				//--
				if($offset === 0 || ((int)$offset > (int)$outPos)) {
					return [];
				} //end if
				//--
				$this->selfCopyBytes($outBuffer, (int)$outPos, (int)$offset, (int)$len);
				//--
				$outPos += (int) $len;
				//--
			} //end if else
			//--
		} //end while
		//--
		return (array) $outBuffer;
		//--
	} //END FUNCTION


	private function selfCopyBytes(array &$array, int $pos, int $offset, int $length) : void {
		//--
		for($i=0; $i<$length; $i++) {
			$array[(int)((int)$pos + (int)$i)] = (int) $array[(int)((int)$pos - (int)$offset + (int)$i)];
		} //end for
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
