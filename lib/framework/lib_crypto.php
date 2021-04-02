<?php
// [LIB - Smart.Framework / Symmetric Crypto and Hashing]
// (c) 2006-2020 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.7.2')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - Crypto Support: Hash Crypto (asymmetric, encrypt only as hash)
//======================================================
// NOTICE: This is unicode safe
//======================================================

// [PHP8]

//--
if(!function_exists('hash_algos')) {
	@http_response_code(500);
	die('PHP Extension Hash is not available');
} //end if
//--

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

/**
 * Class: SmartHashCrypto - provide various hashes for a string: salted password, sha512, sha384, sha256, sha1, md5.
 *
 * <code>
 * // Usage example:
 * SmartHashCrypto::some_method_of_this_class(...);
 * </code>
 *
 * @usage       static object: Class::method() - This class provides only STATIC methods
 *
 * @access      PUBLIC
 * @depends     PHP hash_algos() / hash() ; classes: Smart, SmartFrameworkRuntime, SmartFrameworkRegistry
 * @version     v.20210330
 * @package     @Core:Crypto
 *
 */
final class SmartHashCrypto {

	// ::

	private static $cache = array();


	//==============================================================
	/**
	 * Encrypt (one way) a password :: this may depend on *OPTIONAL* extra salt $y_custom_salt
	 *
	 * @param STRING $y_pass
	 * @return STRING, 128 chars length
	 */
	public static function password($y_pass, $y_custom_salt='') { // {{{SYNC-HASH-PASSWORD}}}
		//-- v.151216
		// Password Salt must not be very complex :: http://stackoverflow.com/questions/5482437/md5-hashing-using-password-as-salt
		// extraordinary good salt + weak password = breakable in seconds
		// just sensible salt + strong password = unbreakable
		// the best is to pre-pend the salt: http://stackoverflow.com/questions/4171859/password-salts-prepending-vs-appending
		// ex: azA-Z09 pass, prepend needs 26^6 permutations while append 26^10, so append adds more complexity
		// SHA512 is high complexity: O(2^n/2) # http://stackoverflow.com/questions/6776050/how-long-to-brute-force-a-salted-sha-512-hash-salt-provided
		//--
		if((string)$y_custom_salt != '') {
			$y_custom_salt = (string) md5((string)'$1'.$y_custom_salt.'$2'.$y_pass);
		} //end if
		//--
		return self::sha512((string)$y_custom_salt.'@ Smart Framework :'.$y_pass.': スマート フレームワーク # 170115%!Password.512/($Auth)*'.strtoupper((string)sha1((string)$y_pass.'&$'.$y_custom_salt)).'^#[?]');
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the SHA512 hash of a string
	 *
	 * @param STRING $y_str
	 * @return STRING, 128 chars length
	 */
	public static function sha512($y_str) {
		//--
		if(!self::algo_check('sha512')) {
			Smart::raise_error('ERROR: Smart.Framework Crypto Hash requires SHA512 Hash/Algo', 'SHA512 Hash/Algo is missing');
			return '';
		} //end if
		//--
		return (string) hash('sha512', (string)$y_str, false); // execution cost: 0.35
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the SHA384 hash of a string
	 *
	 * @param STRING $y_str
	 * @return STRING, 96 chars length
	 */
	public static function sha384($y_str) {
		//--
		if(!self::algo_check('sha384')) {
			Smart::raise_error('ERROR: Smart.Framework Crypto Hash requires SHA384 Hash/Algo', 'SHA384 Hash/Algo is missing');
			return '';
		} //end if
		//--
		return (string) hash('sha384', (string)$y_str, false); // execution cost: 0.34
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the SHA256 hash of a string
	 *
	 * @param STRING $y_str
	 * @return STRING, 64 chars length
	 */
	public static function sha256($y_str) {
		//--
		if(!self::algo_check('sha256')) {
			Smart::raise_error('ERROR: Smart.Framework Crypto Hash requires SHA256 Hash/Algo', 'SHA256 Hash/Algo is missing');
			return '';
		} //end if
		//--
		return (string) hash('sha256', (string)$y_str, false); // execution cost: 0.21
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the SHA1 hash of a string
	 *
	 * @param STRING $y_str
	 * @return STRING, 40 chars length
	 */
	public static function sha1($y_str) {
		//--
		if(!function_exists('sha1')) {
			Smart::raise_error('ERROR: Smart.Framework Crypto Hash requires SHA1 support', 'SHA1 support is missing');
			return '';
		} //end if
		//--
		return (string) sha1((string)$y_str); // execution cost: 0.14
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the MD5 hash of a string
	 *
	 * @param STRING $y_str
	 * @return STRING, 32 chars length
	 */
	public static function md5($y_str) {
		//--
		if(!function_exists('md5')) {
			Smart::raise_error('ERROR: Smart.Framework Crypto Hash requires MD5 support', 'MD5 support is missing');
			return '';
		} //end if
		//--
		return (string) md5((string)$y_str); // execution cost: 0.13
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the CRC32B hash of a string in base16 by default or base36 optional (better than CRC32, portable between 32-bit and 64-bit platforms, unsigned)
	 *
	 * @param STRING $y_str
	 * @param BOOLEAN $y_base36
	 * @return STRING, 8 chars length
	 */
	public static function crc32b($y_str, $y_base36=false) {
		//--
		if(!self::algo_check('sha512')) {
			Smart::raise_error('ERROR: Smart.Framework Crypto Hash requires CRC32B Hash/Algo', 'CRC32B Hash/Algo is missing');
			return '';
		} //end if
		//--
		$hash = (string) hash('crc32b', (string)$y_str, false); // execution cost: 0.21
		if($y_base36 === true) {
			$hash = (string) base_convert((string)$hash, 16, 36);
		} //end if
		//--
		return (string) $hash;
		//--
	} //END FUNCTION
	//==============================================================


	//##### PRIVATES


	//==============================================================
	private static function algo_check($y_algo) {
		//--
		if(in_array($y_algo, (array)self::algos())) {
			$out = 1;
		} else {
			$out = 0;
		} //end if else
		//--
		return $out;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	private static function algos() {
		//--
		if((!array_key_exists('algos', self::$cache)) OR (!is_array(self::$cache['algos']))) {
			self::$cache['algos'] = (array) hash_algos();
		} //end if else
		//--
		return (array) self::$cache['algos'];
		//--
	} //END FUNCTION
	//==============================================================


	//##### DEBUG ONLY


	//==============================================================
	/**
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function registerInternalCacheToDebugLog() {
		//--
		if(SmartFrameworkRuntime::ifInternalDebug()) {
			if(SmartFrameworkRuntime::ifDebug()) {
				SmartFrameworkRegistry::setDebugMsg('extra', '***SMART-CLASSES:INTERNAL-CACHE***', [
					'title' => 'SmartHashCrypto // Internal Cache',
					'data' => 'Dump:'."\n".print_r(self::$cache,1)
				]);
			} //end if
		} //end if
		//--
	} //END FUNCTION
	//==============================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
