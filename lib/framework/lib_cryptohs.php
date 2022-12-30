<?php
// [LIB - Smart.Framework / A-Symmetric Crypto and Hashing]
// (c) 2006-2022 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - Hash Crypto Support
//======================================================
// NOTICE: This is unicode safe
//======================================================

// [PHP8]

//--
if(!defined('SMART_FRAMEWORK_SECURITY_KEY')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_SECURITY_KEY');
} //end if
if((string)trim((string)SMART_FRAMEWORK_SECURITY_KEY) == '') {
	die('Empty INIT constant value for SMART_FRAMEWORK_SECURITY_KEY');
} //end if
//--

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
 * Class: SmartHashCrypto - provide various hashes for a string: salted password, sha512, sha256, sha1, md5.
 *  Hints:
 *  - hashing passwords: is better to prepend the secret, the input is unknown to attackers so these kind of hashes are safe against length attacks ; they have to be protected against colissions ... where more different inputs can generate the same hash !
 *  - hashing checksum: they MUST append the secret to the text to real protect against length attacks where both the input and the hash are public # https://en.wikipedia.org/wiki/Length_extension_attack
 *
 * <code>
 * // Usage example:
 * SmartHashCrypto::some_method_of_this_class(...);
 * </code>
 *
 * @usage       static object: Class::method() - This class provides only STATIC methods
 *
 * @access      PUBLIC
 * @depends     PHP hash_algos() / hash() ; classes: Smart, SmartEnvironment ; constants: SMART_FRAMEWORK_SECURITY_KEY
 * @version     v.20221223
 * @package     @Core:Crypto
 *
 */
final class SmartHashCrypto {

	// ::

	public const PASSWORD_HASH_LENGTH = 128; // fixed length, if lower then padded {{{SYNC-AUTHADM-PASS-PADD}}}

	private const SALT_PREFIX = 'Smart Framework';
	private const SALT_SEPARATOR = '#';
	private const SALT_SUFFIX = 'スマート フレームワーク';

	private const PASSWORD_PREFIX_VERSION = 'sfpass.v2!';

	private static $cache = [];


	//==============================================================
	/**
	 * Create a safe checksum of data
	 * It will append the salt to the end of data to avoid the length extension attack # https://en.wikipedia.org/wiki/Length_extension_attack
	 *
	 * @param STRING $y_data 			The data to be hashed
	 * @param STRING $y_custom_salt 	The salt (will be trimmed from whitespaces) ; If the salt is empty will use the SMART_FRAMEWORK_SECURITY_KEY as the checksum must use a mandatory salt appended to the data to prevent the length extension attack
	 * @return STRING 					The checksum hash as B62 using the hex SHA256 as data + 'salt' suffix (append) ; ~ 43 bytes length
	 */
	public static function checksum(?string $y_data, ?string $y_custom_salt=null) : string {
		//--
		$y_custom_salt = (string) trim((string)$y_custom_salt);
		if((string)$y_custom_salt == '') {
			if(defined('SMART_FRAMEWORK_SECURITY_KEY')) {
				$y_custom_salt = (string) SMART_FRAMEWORK_SECURITY_KEY;
			} else {
				$y_custom_salt = (string) self::SALT_PREFIX.' '.self::SALT_SEPARATOR.' '.self::SALT_SUFFIX;
			} //end if
		} //end if
		//--
		$hexstr = (string) self::sha256((string)$y_data.'#'.$y_custom_salt);
		//--
		return (string) Smart::base_from_hex_convert((string)$hexstr, 62);
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Encrypt (one way) a password by creating a safe password hash
	 * By default will use the v2 which is more colission resistant, by using a new algorithm to derive the password
	 * NOTICE: it uses a custom salt + an internally hard-coded salt to avoid rainbow attack
	 *
	 * @param STRING $y_pass 			The password
	 * @param STRING $y_custom_salt 	The salt (default is empty)
	 * @return STRING 					The password hash: for v1 will return a 128 bytes SHA512 (hex) ; for v2 will return a 128 bytes padded with * on right, composed from 98 bytes hash with a prefix and the SHA512 (base64, 88 bytes)
	 */
	public static function password(string $y_pass, string $y_custom_salt='') : string { // {{{SYNC-HASH-PASSWORD}}}
		//--
		// the password salt must not be too complex related to the password itself # http://stackoverflow.com/questions/5482437/md5-hashing-using-password-as-salt
		// nn extraordinary good salt + a weak password may increase the risk of colissions
		// just sensible salt + strong password = safer
		// for passwords the best is to pre-pend the salt: http://stackoverflow.com/questions/4171859/password-salts-prepending-vs-appending
		// for checksuming is better to append the salt to avoid the length extension attack # https://en.wikipedia.org/wiki/Length_extension_attack
		// ex: azA-Z09 pass, prepend needs 26^6 permutations while append 26^10, so append adds more complexity
		// SHA512 is high complexity: O(2^n/2) # http://stackoverflow.com/questions/6776050/how-long-to-brute-force-a-salted-sha-512-hash-salt-provided
		//--
		if(((int)strlen((string)$y_pass) > 2048) OR ((int)strlen((string)$y_custom_salt) > 2048)) { // {{{SYNC-CRYPTO-KEY-MAX}}} divided by 2 as it is composed of both
			Smart::raise_error(__METHOD__.' # Internal Error: Password or Salt is too long !');
			return '';
		} //end if
		//--
		$prefix = (string) self::PASSWORD_PREFIX_VERSION;
		//--
		$salt = (string) self::sha512((string)$y_custom_salt.' '.self::SALT_PREFIX.' '.self::SALT_SEPARATOR.' '.self::SALT_SUFFIX); // req. padding if salt is empty, will use a fixed salt
		//--
		$pass = (string) self::safecomposedkey((string)str_pad((string)trim((string)$y_pass), 7, '|', STR_PAD_RIGHT)); // req. padding, if an empty password is sent by auth login tries to avoid fatal error
		$pass = (string) self::sha512((string)self::sha256((string)$salt).' '.$pass.' '.$salt, true);
		//--
		$prefix = (string) trim((string)$prefix);
		$pass = (string) trim((string)$pass);
		$minpasslen = (int) ceil(128 / 2 * 1.33); // hex / 2 * 1.33 as b64
		if((int)strlen((string)$pass) < (int)$minpasslen) {
			Smart::raise_error(__METHOD__.' # Internal Error: Password hash must be at least: '.(int)$minpasslen.' bytes !');
			return '';
		} //end if
		$pass = (string) $prefix.str_pad((string)$pass, ((int)self::PASSWORD_HASH_LENGTH - (int)strlen((string)$prefix)), '*'); // {{{SYNC-AUTHADM-PASS-PADD}}}
		if((int)strlen((string)$pass) !== (int)self::PASSWORD_HASH_LENGTH) {
			Smart::raise_error(__METHOD__.' # Internal Error: Password hash length must be '.(int)self::PASSWORD_HASH_LENGTH.' bytes !');
			return '';
		} //end if
		//--
		return (string) $pass;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Check a password hash provided by SmartHashCrypto::password()
	 * It must use the same salt as it was used when password was hashed ; if not using version detect the same version must be used as used when hashed
	 *
	 * @param STRING $y_hash 			The password hash to be checked
	 * @param STRING $y_pass 			The password
	 * @param STRING $y_custom_salt 	The salt (default is empty)
	 * @return BOOL 					Will return TRUE if password match or FALSE if not
	 */
	public static function checkpassword(string $y_hash, string $y_pass, string $y_custom_salt='') : bool {
		//--
		$y_hash = (string) trim((string)$y_hash);
		if((int)strlen((string)$y_hash) !== (int)self::PASSWORD_HASH_LENGTH) {
			return false;
		} //end if
		//--
		if(strpos((string)$y_hash, (string)self::PASSWORD_PREFIX_VERSION) !== 0) {
			return false;
		} //end if
		if(!preg_match('/^[a-zA-Z0-9\+\/\=]+$/', (string)rtrim((string)substr((string)$y_hash, (int)strlen((string)self::PASSWORD_PREFIX_VERSION)),'*'))) { // b64, except signature and padding
			return false;
		} //end if
		//--
		$hash = (string) self::password((string)$y_pass, (string)$y_custom_salt);
		if(((string)trim((string)$hash) == '') OR ((int)strlen((string)$hash) !== (int)self::PASSWORD_HASH_LENGTH)) { // {{{SYNC-AUTHADM-PASS-PADD}}}
			return false;
		} //end if
		if((string)$hash === (string)$y_hash) {
			return true;
		} else {
			return false;
		} //end if else
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the Safe Composed Key from a Key to be used in hash derived methods
	 * The purpose of this method is to provide a colission free pre-derived key from a string key (a password, an encrypt/decrypt key) to be used as the base to create a real derived key by hashing methods later
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param STRING $key 				The key (min 7 bytes ; max 4096 bytes)
	 * @return STRING 					The safe composed key, 553 bytes (characters) ; contains only an ascii subset of: hexa[01234567890abcdef] + NullByte
	 */
	public static function safecomposedkey(?string $key) : string {
		//--
		// This should be used as the basis for a derived key, will be 100% in theory and practice agains hash colissions (see the comments below)
		// It implements a safe mechanism that in order that a key to produce a colission must collide at the same time in all hashing mechanisms: md5, sha1, ha256 and sha512 + crc32b control
		// By enforcing the max key length to 4096 bytes actually will not have any chance to collide even in the lowest hashing such as md5 ...
		// It will return a string of 553 bytes length as: (base:key)[8(crc32b) + 1(null) + 32(md5) + 1(null) + 40(sha1) + 1(null) + 64(sha256) + 1(null) + 128(sha512) = 276] + 1(null) + (base:saltedKeyWithNullBytePrefix)[8(crc32b) + 1(null) + 32(md5) + 1(null) + 40(sha1) + 1(null) + 64(sha256) + 1(null) + 128(sha512) = 276]
		// More, it will return a fixed length (553 bytes) string with an ascii subset just of [ 01234567890abcdef + NullByte ] which already is colission free by using a max source string length of 4096 bytes and by combining many hashes as: md5, sha1, sha256, sha512 and the crc32b
		//--
		$original_key = (string) $key;
		$key = (string) trim((string)$key); // {{{SYNC-CRYPTO-KEY-TRIM}}}
		if((string)$original_key !== (string)$key) {
			if(SmartEnvironment::ifInternalDebug()) {
				if(SmartEnvironment::ifDebug()) {
					Smart::log_notice(__METHOD__.' # Key is invalid, must not contain trailing spaces: `'.$key.'`');
				} //end if
			} //end if
			Smart::raise_error(__METHOD__.' # Key is invalid, must not contain trailing spaces !');
			return '';
		} //end if
		//--
		$klen = (int) strlen((string)$key);
		//--
		if((int)$klen < 7) { // {{{SYNC-CRYPTO-KEY-MIN}}} ; minimum acceptable secure key is 7 characters long
			if(SmartEnvironment::ifInternalDebug()) {
				if(SmartEnvironment::ifDebug()) {
					Smart::log_notice(__METHOD__.' # Key is too short: `'.$key.'`');
				} //end if
			} //end if
			Smart::raise_error(__METHOD__.' # Key Size is lower than 7 bytes ('.(int)$klen.') which is not safe against brute force attacks !');
			return '';
		} elseif((int)$klen > 4096) { // {{{SYNC-CRYPTO-KEY-MAX}}} ; max key size is enforced to allow ZERO theoretical colissions on any of: md5, sha1, sha256 or sha512
			//-- as a precaution, use the lowest supported value which is 4096 (as the md5 supports) ; under this value all the hashes are safe against colissions (in theory)
			// MD5     produces 128 bits which is 16 bytes, not characters, each byte has 256 possible values ; theoretical safe max colission free is: 16*256 =  4096 bytes
			// SHA-1   produces 160 bits which is 20 bytes, not characters, each byte has 256 possible values ; theoretical safe max colission free is: 20*256 =  5120 bytes
			// SHA-256 produces 256 bits which is 32 bytes, not characters, each byte has 256 possible values ; theoretical safe max colission free is: 32*256 =  8192 bytes
			// SHA-512 produces 512 bits which is 64 bytes, not characters, each byte has 256 possible values ; theoretical safe max colission free is: 64*256 = 16384 bytes
			//-- anyway, as a more precaution, combine all hashes thus a key should produce a colission at the same time in all: md5, sha1, sha256 and sha512 ... which in theory, event with bad implementations of the hashing functions this is excluded !
			if(SmartEnvironment::ifInternalDebug()) {
				if(SmartEnvironment::ifDebug()) {
					Smart::log_notice(__METHOD__.' # Key is too long: `'.$key.'`');
				} //end if
			} //end if
			Smart::raise_error(__METHOD__.' # Key Size is higher than 4096 bytes ('.(int)$klen.') which is not safe against collisions !');
			return '';
		} //end if
		//--
		// Security concept: be safe against collisions, the idea is to concatenate more algorithms on the exactly same input !!
		// https://security.stackexchange.com/questions/169711/when-hashing-do-longer-messages-have-a-higher-chance-of-collisions
		// just sensible salt + strong password = unbreakable ; using a minimal salt, prepended, the NULL byte ; a complex salt may be used later in combination with derived keys
		// the best is to pre-pend the salt: http://stackoverflow.com/questions/4171859/password-salts-prepending-vs-appending
		//--
		$nByte = (string) chr(0);
		$salted_key = (string) $nByte.$key; // adding a not so complex fixed salt as suffix
		//--
		// https://stackoverflow.com/questions/1323013/what-are-the-chances-that-two-messages-have-the-same-md5-digest-and-the-same-sha
		// use just hex here and the null byte, with fixed lengths to reduce the chance of collisions for the next step (with not so complex fixed length strings, chances of colissions are infinite lower) ; this will generate a predictible concatenated hash using multiple algorithms ; actually the chances to find a colission for a string between 1..1024 characters that will produce a colission of all 4 hashing algorithms at the same time is ZERO in theory and in practice ... and in the well known universe using well known mathematics !
		//--
		$hkey1 = (string) self::crc32b((string)$key).       $nByte.self::md5((string)$key).       $nByte.self::sha1((string)$key).       $nByte.self::sha256((string)$key).       $nByte.self::sha512((string)$key);
		$hkey2 = (string) self::crc32b((string)$salted_key).$nByte.self::md5((string)$salted_key).$nByte.self::sha1((string)$salted_key).$nByte.self::sha256((string)$salted_key).$nByte.self::sha512((string)$salted_key);
		$composed_key = (string) $hkey1.$nByte.$hkey2;
		//--
		return (string) $composed_key;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the SHA512 hash of a string
	 *
	 * @param STRING $y_str 			String to be hashed
	 * @param BOOLEAN $y_base64 		If set to TRUE will use Base64 Encoding instead of Hexa Encoding
	 * @return STRING 					The hash: 128 chars length (hex) or 88 chars length (b64)
	 */
	public static function sha512(?string $y_str, bool $y_base64=false) : string { // execution cost: 0.35
		//--
		if(!self::algo_check('sha512')) {
			Smart::raise_error(__METHOD__.' # ERROR: Smart.Framework Crypto Hash requires SHA512 Hash/Algo');
			return '';
		} //end if
		//--
		if($y_base64 === true) {
			return (string) base64_encode((string)hash('sha512', (string)$y_str, true));
		} else {
			return (string) hash('sha512', (string)$y_str, false);
		} //end if else
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the SHA256 hash of a string
	 *
	 * @param STRING $y_str 			String to be hashed
	 * @param BOOLEAN $y_base64 		If set to TRUE will use Base64 Encoding instead of Hexa Encoding
	 * @return STRING 					The hash: 64 chars length (hex) or 44 chars length (b64)
	 */
	public static function sha256(?string $y_str, bool $y_base64=false) : string { // execution cost: 0.21
		//--
		if(!self::algo_check('sha256')) {
			Smart::raise_error(__METHOD__.' # ERROR: Smart.Framework Crypto Hash requires SHA256 Hash/Algo');
			return '';
		} //end if
		//--
		if($y_base64 === true) {
			return (string) base64_encode((string)hash('sha256', (string)$y_str, true));
		} else {
			return (string) hash('sha256', (string)$y_str, false);
		} //end if else
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the SHA1 hash of a string
	 *
	 * @param STRING $y_str 			String to be hashed
	 * @param BOOLEAN $y_base64 		If set to TRUE will use Base64 Encoding instead of Hexa Encoding
	 * @return STRING 					The hash: 40 chars length (hex) or 28 chars length (b64)
	 */
	public static function sha1(?string $y_str, bool $y_base64=false) : string { // execution cost: 0.14
		//--
		if(!function_exists('sha1')) {
			Smart::raise_error(__METHOD__.' # ERROR: Smart.Framework Crypto Hash requires SHA1 support');
			return '';
		} //end if
		//--
		if($y_base64 === true) {
			//--
			return (string) base64_encode((string)hex2bin((string)sha1((string)$y_str)));
			//--
		} else {
			//--
			return (string) sha1((string)$y_str);
			//--
		} //end if else
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the MD5 hash of a string
	 *
	 * @param STRING $y_str 			String to be hashed
	 * @param BOOLEAN $y_base64 		If set to TRUE will use Base64 Encoding instead of Hexa Encoding
	 * @return STRING 					The hash: 32 chars length (hex) or 24 chars length (b64)
	 */
	public static function md5(?string $y_str, bool $y_base64=false) : string { // execution cost: 0.13
		//--
		if(!function_exists('md5')) {
			Smart::raise_error(__METHOD__.' # ERROR: Smart.Framework Crypto Hash requires MD5 support');
			return '';
		} //end if
		//--
		if($y_base64 === true) {
			//--
			return (string) base64_encode((string)hex2bin((string)md5((string)$y_str)));
			//--
		} else {
			//--
			return (string) md5((string)$y_str);
			//--
		} //end if else
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the CRC32B hash of a string in base16 by default or base36 optional (better than CRC32, portable between 32-bit and 64-bit platforms, unsigned)
	 *
	 * @param STRING $y_str 			String to be hashed
	 * @param BOOLEAN $y_base36 		If set to TRUE will use Base36 Encoding instead of Hexa Encoding
	 * @return STRING 					The hash: 8 chars length (hex) or 7 chars length (b36)
	 */
	public static function crc32b(?string $y_str, bool $y_base36=false) : string { // execution cost: 0.21
		//--
		if(!self::algo_check('crc32b')) {
			Smart::raise_error(__METHOD__.' # ERROR: Smart.Framework Crypto Hash requires CRC32B Hash/Algo');
			return '';
		} //end if
		//--
		$hash = (string) hash('crc32b', (string)$y_str, false);
		if($y_base36 === true) {
		//	$hash = (string) str_pad((string)base_convert((string)$hash, 16, 36), 7, '0', STR_PAD_LEFT); // 10x faster but unsafe on some platforms ...
			$hash = (string) str_pad((string)Smart::base_from_hex_convert((string)$hash, 36), 7, '0', STR_PAD_LEFT);
		} //end if
		//--
		return (string) $hash;
		//--
	} //END FUNCTION
	//==============================================================


	//##### PRIVATES


	//==============================================================
	private static function algo_check(string $y_algo) : bool {
		//--
		if(in_array((string)$y_algo, (array)self::algos())) {
			return true;
		} else {
			return false;
		} //end if else
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	private static function algos() : array {
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
		if(SmartEnvironment::ifInternalDebug()) {
			if(SmartEnvironment::ifDebug()) {
				SmartEnvironment::setDebugMsg('extra', '***SMART-CLASSES:INTERNAL-CACHE***', [
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
