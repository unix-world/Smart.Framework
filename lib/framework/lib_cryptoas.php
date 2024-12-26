<?php
// [LIB - Smart.Framework / Symmetric and Asymmetric Encrypt/Decrypt Crypto]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - Symmetric and Asymmetric Crypto Support
// 		* symmetric (encrypt/decrypt):
// 			- ThreeFish 1024-bit (CBC) built-in ; requires PHP GMP extension
// 			- TwoFish 256-bit (CBC) built-in
// 			- BlowFish 448-bit (CBC) built-in
// 			- BlowFish (CFB) via OpenSSL
// 			- AES256 (CBC/CFB/OFB) via OpenSSL
// 			- Camellia256 (CBC/CFB/OFB) via OpenSSL
// 			- Idea (CBC/CFB/OFB) via OpenSSL
// 		* asymmetric (shad key): DhKx built-in
//======================================================
// NOTICE: This is unicode safe
//======================================================

// [PHP8]

//--
if(!function_exists('random_bytes')) {
	@http_response_code(500);
	die('ERROR: The PHP random_bytes Function is required for Smart.Framework / Crypto');
} //end if
//--
if(!function_exists('gmp_binomial')) { // test the newest method from GMP ; req. at least PHP 7.3 or later and a modern GMP implementation
	@http_response_code(500);
	die('ERROR: The PHP (GMP extension) gmp_init Function is required for Smart.Framework / Crypto');
} //end if
//--

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

/**
 * Class: Smart Cipher Crypto
 * Provides a built-in based feature to handle various encryption / decryption.
 *
 * <code>
 * // Usage example:
 * SmartCipherCrypto::some_method_of_this_class(...);
 * </code>
 *
 * @usage       static object: Class::method() - This class provides only STATIC methods
 *
 * @depends     classes: Smart, SmartEnvironment, SmartCryptoCiphersTwofishCBC, SmartCryptoCiphersBlowfishCBC, SmartCryptoCiphersOpenSSL, SmartCryptoCiphersHashCryptOFB, SmartDhKx
 * @version     v.20231203
 * @package     @Core:Crypto
 *
 */
final class SmartCipherCrypto {

	// ::


	private const ALGO_T3F_CBC_INTERNAL = 'threefish.cbc';
	private const ALGO_TF_CBC_INTERNAL  = 'twofish.cbc';
	private const ALGO_BF_CBC_INTERNAL  = 'blowfish.cbc';

	private const ALGO_CIPHERS_OPENSSL = [
		//--
		'openssl/blowfish/CFB',
	//	'openssl/blowfish/CBC', // disabled for logic security reasons ; this is available also on SmartJs ... ; disabling this, makes things harder for any forgey attempts
	//	'openssl/blowfish/OFB', // disabled, not enough secure
		//--
		'openssl/aes256/CFB',
		'openssl/aes256/CBC',
	//	'openssl/aes256/OFB', // disabled, not enough secure
		//--
		'openssl/camellia256/CFB',
		'openssl/camellia256/CBC',
	//	'openssl/camellia256/OFB', // disabled, not enough secure
		//--
		'openssl/idea/CFB',
		'openssl/idea/CBC',
	//	'openssl/idea/OFB', // disabled, not enough secure
		//--
	];

	private const ALGO_CIPHER_DEFAULT    = 'hash/sha3-384';
	private const ALGO_CIPHERS_HASHCRYPT = [ // {{{SYNC-HASH-ENC/DEC-HASHING}}}
		'hash/sha3-512',
		self::ALGO_CIPHER_DEFAULT, // sha3-384
		'hash/sha3-256',
		'hash/sha3-224',
		'hash/sha384',
		'hash/sha224',
	];


	//==============================================================
	/**
	 * Select and Provide the Crypto Threefish (CBC) Algo for: t3f_encrypt / t3f_decrypt
	 * Ciphers: threefish.cbc (internal)
	 *
	 * @access 		private
	 * @internal
	 *
	 * @return STRING 			The algo
	 */
	public static function t3f_algo() : string {
		//-- {{{SYNC-ASCRYPTO-SUPPORTED-T3F-ALGOS}}}
		return (string) self::ALGO_T3F_CBC_INTERNAL; // default: threefish . cbc
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Encrypts a string using the Threefish CBC 1024bit (v3 only)
	 * This is intended to be used for high-sensitive persistent data (ex: data storage)
	 * Ciphers: threefish.cbc (internal)
	 *
	 * @param STRING $data 		The plain data to be encrypted
	 * @param STRING $key 		The encryption key (secret) ; must be between 7 and 4096 bytes ; If no key is provided will use the internal key from init: SMART_FRAMEWORK_SECURITY_KEY
	 * @param BOOL $b2fpreenc 	Default is FALSE ; If set to TRUE will pre-encrypt data using BF enc (v2)
	 * @return STRING 			The encrypted data as B64S or empty string on error
	 */
	public static function t3f_encrypt(?string $data, ?string $key='', bool $b2fpreenc=false) : string {
		//--
		$cipher = (string) self::t3f_algo(); // {{{SYNC-ASCRYPTO-SUPPORTED-T3F-ALGOS}}}
		//--
		$key = (string) trim((string)$key);
		if((string)trim((string)$key) == '') {
			$key = (string) self::default_security_key();
		} //end if
		//--
		// do not trim data, preserve original ; only test with trim !
		if((string)trim((string)$data) == '') {
			return ''; // do not log when data is empty ... it may come from external output: ex: URL or Cookie ...
		} //end if
		//--
		$sig = '1kD';
		if($b2fpreenc === true) {
			$sig = 'fb2kD';
			$data = (string) self::tf_encrypt((string)$data, (string)$key, true);
			if(
				((string)trim((string)$data) == '')
				OR
				(strpos((string)$data, 'v1'.'!') === false) // allow just tf+bf v1
			) { // trim OK, expects B64s PKG + Signature
				Smart::log_warning(__METHOD__.' # ERROR: Failed to TF+BF Pre-Encrypt Data');
				return ''; // TF+BF enc failed
			} //end if
			$data = (array)  explode('!', (string)$data, 2); // remove signature
			$data = (string) trim((string)($data[1] ?? null)); // already RRot13-ed ; this is a security enhancement to avoid the first bytes that can be guessed (signature) to be fixed !
			$data = (string) Smart::dataRRot13((string)$data); // because TF+BF v1 is RRot13-ed but so is public exposed, inside T3F capsule normalize it by reverse RRot13 so would be more hard to guess
		} //end if
		//--
		return (string) '3f'.$sig.'.v1'.'!'.Smart::dataRRot13((string)self::encrypt_with_version((string)$cipher, (string)$key, (string)$data, 3));
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Decrypts a string using the  Threefish CBC 1024bit (v3 only)
	 * This is intended to be used for high-sensitive persistent data (ex: data storage)
	 * Ciphers: threefish.cbc (internal)
	 *
	 * @param STRING $data 		The plain data to be encrypted
	 * @param STRING $key 		The encryption key (secret) ; must be between 7 and 4096 bytes ; If no key is provided will use the internal key from init: SMART_FRAMEWORK_SECURITY_KEY
	 * @param BOOL $fallback2fb Default is FALSE ; If TRUE will try to fallback on TF / TF+BF / BF decrypt if signature is not recognized
	 * @return STRING 			The encrypted data as B64S or empty string on error
	 */
	public static function t3f_decrypt(?string $data, ?string $key='', bool $fallback2fb=false) : string {
		//--
		$cipher = (string) self::t3f_algo(); // {{{SYNC-ASCRYPTO-SUPPORTED-T3F-ALGOS}}}
		//--
		$key = (string) trim((string)$key);
		if((string)trim((string)$key) == '') {
			$key = (string) self::default_security_key();
		} //end if
		//--
		$data = (string) trim((string)$data);
		if((string)$data == '') {
			return ''; // do not log when data is empty ... it may come from external output: ex: URL or Cookie ...
		} //end if
		//--
		$b2fpreenc = false;
		if(
			(strpos((string)$data, '3f'.'1kD'.'.'.'v1'.'!') !== 0)
			AND
			(strpos((string)$data, '3f'.'fb2kD'.'.'.'v1'.'!') !== 0)
		) {
			if($fallback2fb === true) { // support backward compatible encrypted TF or TF+BF or BF content via this method ...
				return (string) self::tf_decrypt((string)$data, (string)$key, true); // if not T3F ... fallback and try TF+BF, TF and also BF (propagate fallback)
			} else {
				return ''; // stop here, invalid signature
			} //end if else
		} elseif(strpos((string)$data, '3f'.'fb2kD'.'.'.'v1'.'!') === 0) {
			$b2fpreenc = true;
		} //end if else
		//--
		$data = (array) explode('!', (string)$data, 2);
		$data = (string) Smart::dataRRot13((string)trim((string)($data[1] ?? null)));
		//--
		$data = (string) self::decrypt_with_version((string)$cipher, (string)$key, (string)$data, 3);
		if((string)$data == '') { // do not trim !
			return '';
		} //end if
		//--
		if($b2fpreenc === true) {
			$data = (string) Smart::dataRRot13((string)$data); // fix back RRot13 reversed from T3F Encrypt
			$data = (string) self::tf_decrypt((string)'2fb'.(22*4).'.'.'v1'.'!'.$data, (string)$key); // re-add signature
		} //end if
		//--
		return (string) $data;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Select and Provide the Crypto Twofish (CBC) Algo for: tf_encrypt / tf_decrypt
	 * Ciphers: twofish.cbc (internal)
	 *
	 * @access 		private
	 * @internal
	 *
	 * @return STRING 			The algo
	 */
	public static function tf_algo() : string {
		//-- {{{SYNC-ASCRYPTO-SUPPORTED-TF-ALGOS}}}
		return (string) self::ALGO_TF_CBC_INTERNAL; // default: twofish . cbc
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Encrypts a string using the Twofish CBC 256bit (v3 only)
	 * This is intended to be used for high-sensitive persistent data (ex: data storage)
	 * Ciphers: twofish.cbc (internal)
	 *
	 * @param STRING $data 		The plain data to be encrypted
	 * @param STRING $key 		The encryption key (secret) ; must be between 7 and 4096 bytes ; If no key is provided will use the internal key from init: SMART_FRAMEWORK_SECURITY_KEY
	 * @param BOOL $bfpreenc 	Default is FALSE ; If set to TRUE will pre-encrypt data using BF enc (v3)
	 * @return STRING 			The encrypted data as B64S or empty string on error
	 */
	public static function tf_encrypt(?string $data, ?string $key='', bool $bfpreenc=false) : string {
		//--
		$cipher = (string) self::tf_algo(); // {{{SYNC-ASCRYPTO-SUPPORTED-TF-ALGOS}}}
		//--
		$key = (string) trim((string)$key);
		if((string)trim((string)$key) == '') {
			$key = (string) self::default_security_key();
		} //end if
		//--
		// do not trim data, preserve original ; only test with trim !
		if((string)trim((string)$data) == '') {
			return ''; // do not log when data is empty ... it may come from external output: ex: URL or Cookie ...
		} //end if
		//--
		$sig = (string) (16*16);
		if($bfpreenc === true) {
			$sig = (string) 'b'.(22*4);
			$data = (string) self::bf_encrypt((string)$data, (string)$key);
			if(
				((string)trim((string)$data) == '')
				OR
				(strpos((string)$data, 'v3'.'!') === false) // allow just bf v3
			) { // trim OK, expects B64s PKG + Signature
				Smart::log_warning(__METHOD__.' # ERROR: Failed to BF Pre-Encrypt Data');
				return ''; // BF enc failed
			} //end if
			$data = (array)  explode('!', (string)$data, 2); // remove signature
			$data = (string) trim((string)($data[1] ?? null)); // already RRot13-ed ; this is a security enhancement to avoid the first bytes that can be guessed (signature) to be fixed !
			$data = (string) Smart::dataRRot13((string)$data); // because BF v3 is RRot13-ed but so is public exposed, inside TF capsule normalize it by reverse RRot13 so would be more hard to guess
		} //end if
		//--
		return (string) '2f'.$sig.'.v1'.'!'.Smart::dataRRot13((string)self::encrypt_with_version((string)$cipher, (string)$key, (string)$data, 3));
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Decrypts a string using the  Twofish CBC 256bit (v3 only)
	 * This is intended to be used for high-sensitive persistent data (ex: data storage)
	 * Ciphers: twofish.cbc (internal)
	 *
	 * @param STRING $data 		The plain data to be encrypted
	 * @param STRING $key 		The encryption key (secret) ; must be between 7 and 4096 bytes ; If no key is provided will use the internal key from init: SMART_FRAMEWORK_SECURITY_KEY
	 * @param BOOL $fallbackbf  Default is FALSE ; If TRUE will try to fallback on BF decrypt if signature is not recognized
	 * @return STRING 			The encrypted data as B64S or empty string on error
	 */
	public static function tf_decrypt(?string $data, ?string $key='', bool $fallbackbf=false) : string {
		//--
		$cipher = (string) self::tf_algo(); // {{{SYNC-ASCRYPTO-SUPPORTED-TF-ALGOS}}}
		//--
		$key = (string) trim((string)$key);
		if((string)trim((string)$key) == '') {
			$key = (string) self::default_security_key();
		} //end if
		//--
		$data = (string) trim((string)$data);
		if((string)$data == '') {
			return ''; // do not log when data is empty ... it may come from external output: ex: URL or Cookie ...
		} //end if
		//--
		$bfpreenc = false;
		if(
			(strpos((string)$data, '2f'.(16*16).'.'.'v1'.'!') !== 0)
			AND
			(strpos((string)$data, '2fb'.(22*4).'.'.'v1'.'!') !== 0)
		) {
			if($fallbackbf === true) { // support backward compatible encrypted BF content via this method ...
				return (string) self::bf_decrypt((string)$data, (string)$key); // if not TF ... fallback and try BF
			} else {
				return ''; // stop here, invalid signature
			} //end if else
		} elseif(strpos((string)$data, '2fb'.(22*4).'.'.'v1'.'!') === 0) {
			$bfpreenc = true;
		} //end if else
		//--
		$data = (array) explode('!', (string)$data, 2);
		$data = (string) Smart::dataRRot13((string)trim((string)($data[1] ?? null)));
		//--
		$data = (string) self::decrypt_with_version((string)$cipher, (string)$key, (string)$data, 3);
		if((string)$data == '') { // do not trim !
			return '';
		} //end if
		//--
		if($bfpreenc === true) {
			$data = (string) Smart::dataRRot13((string)$data); // fix back RRot13 reversed from TF Encrypt
			$data = (string) self::bf_decrypt((string)'bf'.(56*8).'.'.'v3'.'!'.$data, (string)$key); // re-add signature
		} //end if
		//--
		return (string) $data;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Select and Provide the Crypto Blowfish (CBC) Algo for: bf_encrypt / bf_decrypt
	 * Ciphers: blowfish.cbc (internal)
	 *
	 * @access 		private
	 * @internal
	 *
	 * @return STRING 			The algo
	 */
	public static function bf_algo() : string {
		//-- {{{SYNC-ASCRYPTO-SUPPORTED-BF-ALGOS}}}
		return (string) self::ALGO_BF_CBC_INTERNAL; // default: blowfish . cbc
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Encrypts a string using the Blowfish CBC 448bit (v3 only)
	 * This is intended to be used for persistent data (ex: data storage)
	 * Ciphers: blowfish.cbc (internal)
	 *
	 * @param STRING $data 		The plain data to be encrypted
	 * @param STRING $key 		The encryption key (secret) ; must be between 7 and 4096 bytes ; If no key is provided will use the internal key from init: SMART_FRAMEWORK_SECURITY_KEY
	 * @return STRING 			The encrypted data as B64S or empty string on error
	 */
	public static function bf_encrypt(?string $data, ?string $key='') : string {
		//--
		$cipher = (string) self::bf_algo(); // {{{SYNC-ASCRYPTO-SUPPORTED-BF-ALGOS}}}
		//--
		$key = (string) trim((string)$key);
		if((string)trim((string)$key) == '') {
			$key = (string) self::default_security_key();
		} //end if
		//--
		// do not trim data, preserve original ; only test with trim !
		if((string)trim((string)$data) == '') {
			return ''; // do not log when data is empty ... it may come from external output: ex: URL or Cookie ...
		} //end if
		//--
		return (string) 'bf'.(56*8).'.'.'v3'.'!'.Smart::dataRRot13((string)self::encrypt_with_version((string)$cipher, (string)$key, (string)$data, 3));
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Decrypts a string using the Blowfish CBC 448bit (v3 or v2 / v1 backward compatible)
	 * This is intended to be used for persistent data (ex: data storage)
	 * Ciphers: blowfish.cbc (internal)
	 *
	 * @param STRING $data 		The plain data to be encrypted
	 * @param STRING $key 		The encryption key (secret) ; must be between 7 and 4096 bytes ; If no key is provided will use the internal key from init: SMART_FRAMEWORK_SECURITY_KEY
	 * @return STRING 			The encrypted data as B64S or empty string on error
	 */
	public static function bf_decrypt(?string $data, ?string $key='') : string {
		//--
		$cipher = (string) self::bf_algo(); // {{{SYNC-ASCRYPTO-SUPPORTED-BF-ALGOS}}}
		//--
		$key = (string) trim((string)$key);
		if((string)trim((string)$key) == '') {
			$key = (string) self::default_security_key();
		} //end if
		//--
		$data = (string) trim((string)$data);
		if((string)$data == '') {
			return ''; // do not log when data is empty ... it may come from external output: ex: URL or Cookie ...
		} //end if
		//--
		$version = -1; // default
		if(
			(strpos((string)$data, 'bf'.(56*8).'.'.'v3'.'!') === 0) // v3
			OR
			(strpos((string)$data, 'bf'.(56*8).'.'.'v2'.'!') === 0) // v2
		) {
			$version = 2;
			$isRRot13 = false;
			if(strpos((string)$data, 'bf'.(56*8).'.'.'v3'.'!') === 0) {
				$version = 3;
				$isRRot13 = true;
			} //end if
			$data = (array) explode('!', (string)$data, 2);
			$data = (string) trim((string)($data[1] ?? null));
			if($isRRot13 === true) {
				$data = (string) Smart::dataRRot13((string)$data);
			} //end if
		} else { // v1, with signature or not
			if(strpos((string)$data, 'bf'.(48*8).'.'.'v1'.'!') === 0) { // v1
				$data = (array) explode('!', (string)$data, 2);
				$data = (string) trim((string)($data[1] ?? null));
			} //end if
			$slen = (int) strlen((string)$data);
			if(
				(((int)$slen % 2) !== 0) // must be even !
				OR
				((int)$slen !== (int)strspn((string)$data, (string)strtoupper((string)Smart::CHARSET_BASE_16)))
			) { // v1 was uppercase HEX ; {{{SYNC-ASCRYPTO-TEST-BF-V1-HEX}}}
				return ''; // stop here, malformed data, must be hex all upper as v1 implemented
			} //end if
			$version = 1;
		} //end if else
		//--
		return (string) self::decrypt_with_version((string)$cipher, (string)$key, (string)$data, (int)$version);
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Select and Provide the Crypto Algo for: encrypt / decrypt
	 * depends on constant: SMART_FRAMEWORK_SECURITY_CRYPTO
	 * Internal: 'hash/sha3-384' ; default
	 * Using OpenSSL (faster): 'openssl/{cipher}/{mode}' ; or other, hash, without OpenSSL: hash/{mode}
	 * If the constant SMART_FRAMEWORK_SECURITY_CRYPTO is set will override the default
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param STRING $cipher 	The algo to check ; If is not valid, a warning will be issues and will fallback on default algo
	 * @return STRING 			The algo
	 */
	public static function algo(?string $cipher='') {
		//--
		$cipher = (string) trim((string)$cipher);
		//--
		if((string)$cipher == '') {
			//--
			$cipher = (string) self::ALGO_CIPHER_DEFAULT; // default: internal
			//--
			if(defined('SMART_FRAMEWORK_SECURITY_CRYPTO')) {
				if((string)trim((string)SMART_FRAMEWORK_SECURITY_CRYPTO) != '') {
					$cipher = (string) trim((string)SMART_FRAMEWORK_SECURITY_CRYPTO);
				} //end if
			} //end if
			//--
		} //end if
		//--
		if( // here is not allowed to use the signed internal blowfish . cbc ... that is not intended for on-the-fly encryption because have a signature prefix and can be identifyable plus that is intended just for persistent storage
			(!in_array((string)$cipher, (array)self::ALGO_CIPHERS_HASHCRYPT))
			AND
			(!in_array((string)$cipher, (array)self::ALGO_CIPHERS_OPENSSL))
		) {
			Smart::log_warning(__METHOD__.' # ERROR: Invalid Algo set in SMART_FRAMEWORK_SECURITY_CRYPTO as: `'.$cipher.'`');
			$cipher = (string) self::ALGO_CIPHER_DEFAULT;
		} //end if
		//--
		return (string) $cipher;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Encrypts data on-the-fly using various Ciphers
	 * This is intended to be used for non-persistent data ; these ciphers may change or dissapear over time
	 *
	 * @param STRING $data 		The plain data to be encrypted
	 * @param STRING $key 		The encryption key (secret) ; must be between 7 and 4096 bytes ; If no key is provided will use the internal key from init: SMART_FRAMEWORK_SECURITY_KEY
	 * @param ENUM $cipher 		Selected cipher: hash/{mode}, blowfish.cbc, openssl/{cipher}/{mode} ; If no cipher is provided will use the default cipher
	 * @return STRING 			The encrypted data as B64S or empty string on error
	 */
	public static function encrypt(?string $data, ?string $key='', ?string $cipher='') : string {
		//--
		$key = (string) trim((string)$key);
		if((string)trim((string)$key) == '') {
			$key = (string) self::default_security_key();
		} //end if
		//--
		$cipher = (string) self::algo((string)$cipher);
		//--
		return (string) Smart::dataRRot13((string)self::encrypt_with_version((string)$cipher, (string)$key, (string)$data, 3)); // hardcoded to v3 only, this is without signature
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Decrypts data on-the-fly using various Ciphers
	 * This is intended to be used for non-persistent data ; these ciphers may change or dissapear over time
	 *
	 * @param STRING $data 		The encrypted data
	 * @param STRING $key 		The encryption key (secret) ; must be between 7 and 4096 bytes ; If no key is provided will use the internal key from init: SMART_FRAMEWORK_SECURITY_KEY
	 * @param ENUM $cipher 		Selected cipher: hash/{mode}, blowfish.cbc, openssl/cipher/mode ; ; If no cipher is provided will use the default cipher
	 * @return STRING 			The plain (decrypted) data or empty string on error
	 */
	public static function decrypt(?string $data, ?string $key='', ?string $cipher='') : string {
		//--
		$key = (string) trim((string)$key);
		if((string)trim((string)$key) == '') {
			$key = (string) self::default_security_key();
		} //end if
		//--
		$cipher = (string) self::algo((string)$cipher);
		//-- {{{SYNC-ASCRYPTO-SUPPORTED-ALGOS}}} is verified in the below method
		return (string) self::decrypt_with_version((string)$cipher, (string)$key, (string)Smart::dataRRot13((string)$data), 3); // hardcoded to v3 only, this is without signature
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Smart.DhKx Enc
	 *
	 * @access 		private
	 * @internal
	 *
	 * @return ARRAY 		Data
	 */
	public static function dhkx_enc(bool $useBigInt=true) : array {
		//--
		$arr = [
			'err'  => '?',
			'shad' => '',
			'eidz' => '',
		];
		//--
		$dh = new SmartDhKx((bool)$useBigInt);
		//--
		$basegen = (string) $dh->getBaseGen();
		//--
		$srvData = (array) $dh->getSrvData((string)$basegen);
		$cliData = (array) $dh->getCliData((string)$basegen);
		//--
		$srvShad = (string) $dh->getSrvShad((string)$srvData['sec'], (string)$cliData['pub']);
		$cliShad = (string) $dh->getCliShad((string)$cliData['sec'], (string)$srvData['pub']);
		//--
		if(
			((string)trim((string)$srvShad) == '')
			OR
			((string)trim((string)$cliShad) == '')
			OR
			((string)$srvShad !== (string)$cliShad)
		) {
			$arr['err'] = 'Shad Mismatch';
			return (array) $arr; // shad failed !
		} //end if
		//--
		$shd = 'dH';
		if($dh->useBigInt() === true) {
			$shd .= '.iHg';
		} else {
			$shd .= '.i64';
		} //end if else
		//--
		$asx = (string) bin2hex('@'.$srvData['pub']);
		//--
		$etf = (string) self::tf_encrypt(
			(string) Smart::base_from_hex_convert((string)Smart::dataRRot13((string)bin2hex('$'.$cliData['sec'])), 92),
			(string) Smart::base_from_hex_convert((string)SmartHashCrypto::hmac('sha3-224', (string)$shd.'.', (string)'&='.$asx.'#'), 92)
		);
		//--
		$ebf = (string) self::bf_encrypt(
			(string) Smart::base_from_hex_convert((string)$asx, 85),
			(string) SmartHashCrypto::pbkdf2DerivedB92Key((string)$shd.'.', (string)SmartHashCrypto::hmac('sha224', (string)$shd.'.', (string)Smart::dataRRot13((string)bin2hex((string)$shd.'.'))), 56, 14, 'sha3-384')
		);
		//--
		$arr['err'] = ''; // clear
		$arr['shad'] = (string) $srvShad;
		$arr['eidz'] = (string) $shd.'.v3!'.Smart::dataRRot13((string)Smart::b64s_enc((string)$etf, false)).'!'.Smart::dataRRot13((string)Smart::base_from_hex_convert((string)bin2hex((string)$ebf), 62));
		return (array) $arr;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Smart.DhKx Dec
	 *
	 * @access 		private
	 * @internal
	 *
	 * @return ARRAY 		Data
	 */
	public static function dhkx_dec(?string $eidz, bool $useBigInt=true) : array {
		//--
		$eidz = (string) trim((string)$eidz);
		//--
		$dh = new SmartDhKx((bool)$useBigInt);
		//--
		$err = '';
		$cliShad = '';
		if((string)$eidz == '') {
			$err = 'Empty Idz';
		} else {
			$arr = (array) self::dhkx_eidz_dec((string)$eidz, (bool)$dh->useBigInt());
			$arr['err'] = (string) trim((string)($arr['err'] ?? null));
			if((string)$arr['err'] != '') {
				$err = (string) $arr['err'];
			} else {
				$cliShad = (string) trim((string)$dh->getCliShad((string)$arr['csec'], (string)$arr['spub']));
				if((string)$cliShad == '') {
					$err = 'Empty Shad';
				} //end if
			} //end if
		} //end if
		//--
		return [
			'type' 	=> 'DhkxShadData',
			'mode' 	=> (string) $dh->getMode(),
			'shad' 	=> (string) $cliShad,
			'err' 	=> (string) $err,
		];
		//--
	} //END FUNCTION
	//==============================================================


	//======= [PRIVATES]


	//==============================================================
	/**
	 * Smart.DhKx Dec EIDZ
	 *
	 * @access 		private
	 * @internal
	 *
	 * @return ARRAY 		Data
	 */
	private static function dhkx_eidz_dec(string $eidz, bool $useBigInt) : array {
		//--
		$eidz = (string) trim((string)$eidz);
		if((string)$eidz == '') {
			return [
				'err' => 'Invalid IDZ (1)'
			];
		} //end if
		//--
		if(strpos((string)$eidz , '!') === false) {
			return [
				'err' => 'Invalid IDZ (2)'
			];
		} //end if
		//--
		$arr = (array) explode('!', (string)$eidz, 4);
		if((int)Smart::array_size($arr) != 3) {
			return [
				'err' => 'Invalid IDZ (3)'
			];
		} //end if
		//--
		$pfx = 'dH.';
		$ver = 'v3';
		$sig = '';
		$mod = '0';
		if($useBigInt === true) {
			$sig = 'iHg.';
			$mod = '1';
		} else {
			$sig = 'i64.';
			$mod = '2';
		} //end if else
		$arr[0] = (string) trim((string)$arr[0]);
		if((string)$arr[0] != (string)$pfx.$sig.$ver) {
			return [
				'err' => 'Invalid IDZ (4.'.$mod.')'
			];
		} //end if
		//--
		$arr[1] = (string) trim((string)Smart::b64s_dec((string)Smart::dataRRot13((string)$arr[1])));
		if((string)$arr[1] == '') {
			return [
				'err' => 'Invalid IDZ (5)'
			];
		} //end if
		//--
		$arr[2] = (string) trim((string)self::bf_decrypt(
			(string) trim((string)Smart::safe_hex_2_bin((string)Smart::base_to_hex_convert((string)Smart::dataRRot13((string)$arr[2]), 62), false, false)),
			(string) SmartHashCrypto::pbkdf2DerivedB92Key((string)$pfx.$sig, (string)SmartHashCrypto::hmac('sha224', (string)$pfx.$sig, (string)Smart::dataRRot13((string)bin2hex((string)$pfx.$sig))), 56, 14, 'sha3-384')
		));
		if((string)$arr[2] == '') {
			return [
				'err' => 'Invalid IDZ (6)'
			];
		} //end if
		//--
		$asx = (string) trim((string)Smart::base_to_hex_convert((string)$arr[2], 85));
		if((string)$asx == '') {
			return [
				'err' => 'Invalid IDZ (7)'
			];
		} //end if
		$arr[2] = (string) trim((string)substr((string)Smart::safe_hex_2_bin((string)$asx, false, false), 1)); // do not ignore case ; do not log notices, they are logged in base convert
		if((string)$arr[2] == '') {
			return [
				'err' => 'Invalid IDZ (8)'
			];
		} //end if
		//--
		$arr[1] = (string) trim((string)self::tf_decrypt(
			(string) $arr[1],
			(string) Smart::base_from_hex_convert((string)SmartHashCrypto::hmac('sha3-224', (string)$pfx.$sig, (string)'&='.$asx.'#'), 92)
		));
		if((string)$arr[1] == '') {
			return [
				'err' => 'Invalid IDZ (9)'
			];
		} //end if
		$arr[1] = (string) trim((string)substr((string)Smart::safe_hex_2_bin((string)Smart::dataRRot13((string)trim((string)Smart::base_to_hex_convert((string)$arr[1], 92))), false, false), 1)); // do not ignore case ; do not log notices, they are logged in base convert
		if((string)$arr[1] == '') {
			return [
				'err' => 'Invalid IDZ (10)'
			];
		} //end if
		//--
		return [
			'csec' => (string) $arr[1],
			'spub' => (string) $arr[2],
		];
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Select and Provide the Default Security Key
	 *
	 * @access 		private
	 * @internal
	 *
	 * @return STRING 			The key
	 */
	private static function default_security_key() {
		//--
		if((defined('SMART_FRAMEWORK_SECURITY_KEY')) AND ((string)trim((string)SMART_FRAMEWORK_SECURITY_KEY) != '')) {
			return (string) trim((string)SMART_FRAMEWORK_SECURITY_KEY);
		} //end if
		//--
		Smart::raise_error(__METHOD__.' SMART_FRAMEWORK_SECURITY_KEY is not defined or empty !');
		return '';
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Verifies the Cipher Algo with Version.
	 *
	 * @param ENUM $cipher 		Selected cipher: hash/{mode}, blowfish.cbc, threefish.cbc, twofish.cbc, openssl/{cipher}/{mode}
	 * @param ENUM $ver 		The version ; supported: 1, 2, 3 (current)
	 * @return STRING 			'' (empty string) if NO Error or 'ERROR: ...' if error
	 */
	private static function verify_cipher_with_version(string $cipher, int $ver) : string {
		//--
		if( // {{{SYNC-ASCRYPTO-SUPPORTED-ALGOS}}}
			((string)$cipher != (string)self::ALGO_T3F_CBC_INTERNAL) // threefish . cbc
			AND
			((string)$cipher != (string)self::ALGO_TF_CBC_INTERNAL) // twofish . cbc
			AND
			((string)$cipher != (string)self::ALGO_BF_CBC_INTERNAL) // blowfish . cbc
			AND
			((string)$cipher != (string)self::ALGO_CIPHER_DEFAULT)
			AND
			(!in_array((string)$cipher, (array)self::ALGO_CIPHERS_HASHCRYPT))
			AND
			(!in_array((string)$cipher, (array)self::ALGO_CIPHERS_OPENSSL))
		) {
			return 'ERROR: Unsupported Cipher ['.$cipher.']';
		} //end if
		//--
		if(
			((int)$ver < 1)
			OR
			((int)$ver > 3)
		) {
			return 'ERROR: Cipher[ANY] # Invalid Version: '.(int)$ver;
		} //end if
		//--
		if((string)$cipher != (string)self::ALGO_BF_CBC_INTERNAL) {
			if((int)$ver !== 3) {
				return 'ERROR: Cipher['.$cipher.'] # Invalid Version: '.(int)$ver;
			} //end if
		} //end if else
		//--
		return '';
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Encrypts a string using the selected Cipher Algo.
	 *
	 * @param ENUM $cipher 		Selected cipher: hash/{mode}, blowfish.cbc, threefish.cbc, twofish.cbc, openssl/{cipher}/{mode}
	 * @param STRING $key 		The encryption key (secret) ; must be between 7 and 4096 bytes
	 * @param STRING $data 		The plain data to be encrypted
	 * @param ENUM $ver 		The version ; supported: 1, 2, 3 (current)
	 * @return STRING 			The encrypted data as B64S or empty string on error
	 */
	private static function encrypt_with_version(?string $cipher, ?string $key, ?string $data, int $ver) : string {
		//--
		$errCipherVersion = (string) self::verify_cipher_with_version((string)$cipher, (int)$ver);
		if((string)$errCipherVersion != '') {
			Smart::log_warning(__METHOD__.' # '.$errCipherVersion);
			return '';
		} //end if
		//--
		// do not trim key, preserve original ; only test with trim !
		if((string)trim((string)$key) == '') {
			Smart::log_warning(__METHOD__.' # ERROR: Key is Empty');
			return '';
		} //end if
		//--
		// do not trim data, preserve original ; only test with trim !
		if((string)trim((string)$data) == '') {
			return ''; // do not log when data is empty ... it may come from external output: ex: URL or Cookie ...
		} //end if
		//--
		$crypto = self::crypto((string)$cipher, (string)$key, (int)$ver); // v2 or v3 only
		if(!is_object($crypto)) {
			Smart::log_warning(__METHOD__.' # ERROR: Cipher ['.$cipher.'] '.$crypto);
			return '';
		} //end if
		//--
		return (string) self::dataContainerPack(
			(string) $crypto->encrypt((string)self::dataB64WithChecksumPrepare((string)$data, (int)$ver)), // b64
			(string) $data,
			(int)    $ver
		); // B64s
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Decrypts a string using the selected Cipher Algo.
	 *
	 * @param ENUM $cipher 		Selected cipher: hash/{mode}, blowfish.cbc, threefish.cbc, twofish.cbc, openssl/cipher/mode
	 * @param STRING $key 		The encryption key (secret) ; must be between 7 and 4096 bytes
	 * @param STRING $data 		The encrypted data
	 * @param ENUM $ver 		The version ; supported: 1, 2, 3 (current)
	 * @return STRING 			The plain (decrypted) data or empty string on error
	 */
	private static function decrypt_with_version(?string $cipher, ?string $key, ?string $data, int $ver) : string {
		//--
		$errCipherVersion = (string) self::verify_cipher_with_version((string)$cipher, (int)$ver);
		if((string)$errCipherVersion != '') {
			Smart::log_warning(__METHOD__.' # '.$errCipherVersion);
			return '';
		} //end if
		//--
		// do not trim key, preserve original ; only test with trim !
		if((string)trim((string)$key) == '') {
			Smart::log_warning(__METHOD__.' # ERROR: Key is Empty');
			return '';
		} //end if
		//--
		// do not trim data, preserve original ; only test with trim !
		if((string)trim((string)$data) == '') {
			return ''; // do not log when data is empty ... it may come from external output: ex: URL or Cookie ...
		} //end if
		//--
		$crypto = self::crypto((string)$cipher, (string)$key, (int)$ver);
		if(!is_object($crypto)) {
			Smart::log_warning(__METHOD__.' # ERROR: Cipher['.$cipher.'] '.$crypto);
			return '';
		} //end if
		//--

		//--
		// START: {{{SYNC-CRYPTO-POST-UNPACK-LOGIC}}}
		//--
		$data = (string) trim((string)$data);
		if((string)$data == '') {
			return '';
		} //end if
		//--
		$originalData = (string) $data; // preserve, it is required for the final checksum verification
		//--
		$uar = (array) self::dataContainerUnpackAndVerify((string)$data, (int)$ver);
		if((string)$uar['err'] != '') {
			if(
				($uar['dbg'] !== true)
				OR
				(
					($uar['dbg'] === true)
					AND
					(SmartEnvironment::ifDebug() === true)
				)
			) {
				Smart::log_notice(__METHOD__.' # ERR-1 [v'.(int)$ver.']: '.$uar['err']);
			} //end if
			return ''; // unpack errors
		} //end if
		if((int)$uar['dlen'] <= 0) {
			Smart::log_notice(__METHOD__.' # ERR-2 [v'.(int)$ver.']: Empty Data after Unpack');
			return ''; // data is empty or invalid
		} //end if
		//--
		$data = (string) $uar['data']; // IMPORTANT ! do not TRIM ... this is RAW CRYPTO DATA
		if((string)trim((string)$data) == '') {
			Smart::log_notice(__METHOD__.' # ERR-3 [v'.(int)$ver.']: Empty Data after Unpack');
			return ''; // data is empty or invalid
		} //end if
		// empty data was checked above via $uar['data'] and $uar['dlen']
		//--
		$cksumPak = (string) trim((string)$uar['cksm']); // this is HEX (checksum), have to be trimmed !
		//-- normally it should not get so far, should have already error about this in $uar['err'], but check again
		if((int)$ver == 3) {
			if(
				((int)$uar['clen'] <= 0)
				OR
				((string)trim((string)$cksumPak) == '')
			) {
				Smart::log_notice(__METHOD__.' # ERR-4 [v'.(int)$ver.']: Empty Checksum after Unpack');
				return ''; // data is empty or invalid
			} //end if
		} //end if
		//--
		$uar = null;
		//--
		// #END: {{{SYNC-CRYPTO-POST-UNPACK-LOGIC}}}
		//--

		//--
		$data = (string) $crypto->decrypt((string)$data);
		//--
		if((string)trim((string)$data) == '') {
			Smart::log_notice(__METHOD__.' # Decrypt Failed, is Empty (v'.(int)$ver.')');
			return '';
		} //end if
		//--

		//--
		// START: {{{SYNC-CRYPTO-POST-B64DECODE-LOGIC}}}
		//--
		$bar = (array) self::dataB64WithChecksumSepareAndVerify((string)$data, (int)$ver);
		$data = null; // free mem
		//--
		if((string)$bar['err'] != '') {
			if(
				($bar['dbg'] !== true)
				OR
				(
					($bar['dbg'] === true)
					AND
					(SmartEnvironment::ifDebug() === true)
				)
			) {
				Smart::log_notice(__METHOD__.' # ERR-11 [v'.(int)$ver.']: '.$bar['err']);
			} //end if
			return ''; // decode errors
		} //end if
		if((int)$bar['dlen'] <= 0) {
			Smart::log_notice(__METHOD__.' # ERR-12 [v'.(int)$ver.']: Empty Data after Decode');
			return ''; // B64 data is empty or invalid
		} //end if
		//--
		$b64data = (string) $bar['data']; // IMPORTANT ! do not TRIM ... this is RAW CRYPTO DATA
		if((string)trim((string)$b64data) == '') {
			Smart::log_notice(__METHOD__.' # ERR-13 [v'.(int)$ver.']: Empty Data after Decode');
			return ''; // B64 data is empty or invalid
		} //end if
		// empty data was checked above via $bar['data'] and $bar['dlen']
		//--
		$testsum = (string) trim((string)$bar['cksm']); // this is HEX (checksum), have to be trimmed !
		//-- normally it should not get so far, should have already error about this in $bar['err'], but check again
		if(
			((int)$bar['clen'] <= 0)
			OR
			((string)trim((string)$testsum) == '')
		) {
			Smart::log_notice(__METHOD__.' # ERR-14 [v'.(int)$ver.']: Empty Checksum after Decode');
			return ''; // data is empty or invalid
		} //end if
		//--
		$bar = null;
		//--
		// #END: {{{SYNC-CRYPTO-POST-B64DECODE-LOGIC}}}
		//--

		//-- b64 because is not UTF-8 safe and may corrupt unicode characters
		$data = (string) base64_decode((string)$b64data);
		$b64data = null; // free mem
		//--
		if(self::verifyDecryptedData((string)$cksumPak, (string)$originalData, (string)$data, (int)$ver) !== true) {
			Smart::log_notice(__METHOD__.' # ERR-21 [v'.(int)$ver.']: Invalid Package Verification Checksum vs. Decrypted Data');
			return ''; // data is empty or invalid
		} //end if
		//--

		//--
		return (string) $data;
		//--

	} //END FUNCTION
	//==============================================================


	//==============================================================
	// Veryfies the Package vs. Plain Data Signature Checksum
	private static function verifyDecryptedData(string $hexChecksumSh3a224, string $originalCipherTextB64s, string $decryptedPlainText, int $iver) : bool {
		//--
		if((int)$iver == 1) { // v1
			//--
			// does not support this type of checksum ; should return true
			//--
		} elseif((int)$iver == 2) { // v2
			// does not support this type of checksum ; should return true
		} else { // v3
			//--
			if(
				((string)trim((string)$hexChecksumSh3a224) == '')
				OR
				((int)strlen((string)$hexChecksumSh3a224) != 56) // sh3a224, hex
				OR
				((int)strlen((string)$hexChecksumSh3a224) !== (int)strspn((string)$hexChecksumSh3a224, (string)Smart::CHARSET_BASE_16))
			) {
				return false;
			} //end if
			//--
			$ofssc = strpos((string)$originalCipherTextB64s, ';'); // !!! mixed, do not cast !!! can be FALSE or INTEGER
			if(($ofssc === false) OR (!is_int($ofssc)) OR ((int)$ofssc <= 0)) {
				return false;
			} //end if
			$cks = (string) SmartHashCrypto::sh3a224((string)substr((string)$originalCipherTextB64s, 0, (int)$ofssc).chr(0).$decryptedPlainText);
			//--
			if((string)$hexChecksumSh3a224 !== (string)$cks) {
				return false;
			} //end if
			//--
		} //end if else
		//--
		return true;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	// prepare a data holder, before encryption, B64 + Checksum ; Encrypting non-safe characters may break the algo, so B64 charset is safe ...
	// this is the plain data input container, stores data (ex: unsafe UTF-16 ... or even binary data as Base64) before encryption
	private static function dataB64WithChecksumPrepare(string $plainText, int $iver) : string {
		//--
		if((string)$plainText == '') {
			return ''; // nothing to prepare or encode ! empty data ...
		} //end if
		//--
		$plainText = (string) base64_encode((string)$plainText); // b64 because is not UTF-8 safe and may corrupt unicode characters
		//-- {{{SYNC-BLOWFISH-CHECKSUM}}}
	//	if((int)$iver == 1) { // v1
	//		$plainText .= '#CHECKSUM-SHA1#'.SmartHashCrypto::sha1((string)$plainText); // sha1, hex
	//	} elseif((int)$iver == 2) { // v2
		if((int)$iver == 2) { // v2
			$plainText .= '#CKSUM256#'.SmartHashCrypto::sha256((string)$plainText, true); // sha256, b64
		} else { // v3
			$plainText .= '#CKSUM512V3#'.Smart::base_from_hex_convert((string)SmartHashCrypto::sh3a512((string)$plainText), 62); // sh3a512, b62
		} //end if else
		//--
		return (string) trim((string)$plainText); // trim, it is safe, it is B64 data ...
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	// decode the data holder, after decryption, B64 + Checksum ; Validate, Verify Checksums and Everything ...
	private static function dataB64WithChecksumSepareAndVerify(string $b64data, int $iver) : array {
		//--
		$dec = [
			'dbg'  => false, 	// log error only on debug, if set to true
			'err'  => '??', 	// error message or empty if OK
			'dlen' => 0, 		// length of data
			'data' => '', 		// b64 data
			'clen' => 0, 		// length of checksum
			'cksm' => '', 		// checksum
			'ctyp' => '##', 	// checksum type
		];
		//--
		$b64data = (string) trim((string)$b64data); // trim padding spaces, it is known here that should be B64 data ... it is safe !
		if((string)$b64data == '') {
			$dec['err'] = 'Data Decode Failed: Empty Data, nothing to decode ... (v'.(int)$iver.')';
			return (array) $dec;
		} //end if
		//-- {{{SYNC-BLOWFISH-CHECKSUM}}}
		$separator = '########'; // init, fake one !
		if((int)$iver == 1) { // v1
			$separator = '#CHECKSUM-SHA1#';
		} elseif((int)$iver == 2) { // v2
			$separator = '#CKSUM256#';
		} else { // v3
			$separator = '#CKSUM512V3#';
		} //end if else
		//--
		$arr = [];
		if(strpos((string)$b64data, (string)$separator) !== false) {
			$arr = (array) explode((string)$separator, (string)$b64data, 2);
		} //end if
		$b64data = (string) trim((string)($arr[0] ?? ''));
		$checksum = (string) trim((string)($arr[1] ?? ''));
		if((string)trim((string)$b64data) == '') {
			$dec['err'] = 'Data Decode Failed: Empty Data, after the checksum separation by `'.$separator.'` ... (v'.(int)$iver.')';
			return (array) $dec;
		} //end if
		if(!preg_match((string)Smart::REGEX_SAFE_B64_STR, (string)$b64data)) { // {{{SYNC-BFCRYPTO-VALIDATE-B64-PAK}}}
			$dec['err'] = 'Data Decode Failed: Data contains B64 Invalid Characters (v'.(int)$iver.')';
			return (array) $dec;
		} //end if
		if((string)trim((string)$checksum) == '') {
			$dec['err'] = 'Data Decode Failed: Checksum is Empty (v'.(int)$iver.')';
			return (array) $dec;
		} //end if
		//--
		$sumver = '';
		$testsum = '';
		if((int)$iver == 1) { // v1
			$sumver = 'SHA1/Hex';
			$testsum = (string) SmartHashCrypto::sha1((string)$b64data); // sha1 hex
		} elseif((int)$iver == 2) { // v2
			$sumver = 'SHA256/B64';
			$testsum = (string) SmartHashCrypto::sha256((string)$b64data, true); // sha256 b64
		} else { // v3 ; sh3a512+b62
			$sumver = 'SHA3-512/B62';
			$testsum = (string) SmartHashCrypto::sh3a512((string)$b64data); // v3, sh3a512, HEX ; {{{SYNC-BFCRYPTO-VALIDATE-B62-SH3A512-CALCULATE}}} #1
			if(
				((int)strlen((string)trim((string)$testsum)) !== 128) // be sure is hex, 128
				OR
				((string)Smart::base_from_hex_convert((string)trim((string)$testsum), 62) !== (string)$checksum) // compare as B62
				OR
				((int)strlen((string)$checksum) !== (int)strspn((string)$checksum, (string)Smart::CHARSET_BASE_62)) // be sure is valid B62 charset
			) { // compare as B62 ; below will be re-compared as hex
				$dec['dbg'] = true; // this is debug only info ...
				$dec['err'] = 'Checksum ('.$sumver.') decoding from B62 is Invalid (v'.(int)$iver.')';
				return (array) $dec;
			} //end if
			$checksum = (string) Smart::base_to_hex_convert((string)trim((string)$checksum), 62); // v3, sh3a512, B62 to HEX ; {{{SYNC-BFCRYPTO-VALIDATE-B62-SH3A512-CALCULATE}}} #2
		} //end if else
		//--
		if(((string)trim((string)$testsum) == '') OR ((string)$testsum !== (string)$checksum)) { // {{{SYNC-VALIDATE-B62-SH3A512-CKSUM}}}
			// TODO: for recovery, in debug mode only or by setting a special constant should allow return data in special circumstances only ...
			$dec['err'] = 'Data Verification FAILED ('.$sumver.'), Invalid Checksum (v'.(int)$iver.')';
			return (array) $dec; // string is corrupted or checksum is corrupted, avoid to return !
		} //end if
		//--
		$dec['dbg']  = (bool)   (is_bool($dec['dbg']) ? $dec['dbg'] : false);
		$dec['err']  = (string) ''; // clear
		$dec['dlen'] = (int)    strlen((string)$b64data);
		$dec['data'] = (string) $b64data;
		$dec['clen'] = (int)    strlen((string)$testsum);
		$dec['cksm'] = (string) $testsum;
		$dec['ctyp'] = (string) $sumver;
		return (array) $dec;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	// prepare a data container, after encryption, B64s + Checksum ; this is required because encrypted data is RAW
	// this is the encrypted data output container, after encryption, B64 already


	private static function dataContainerPack(string $rawCipherData, string $originalPlainTextUsedJustForChecksum, int $iver) : string {
		//--
		if((string)$rawCipherData == '') { // DO NOT TRIM !
			return ''; // cannot create a package without data ...
		} //end if
		//--
	//	if((int)$iver == 1) { // v1
	//		//--
	//		return (string) strtoupper((string)bin2hex((string)$rawCipherData)); // upper HEX ; no more supported
	//		//--
	//	} elseif((int)$iver == 2) { // v2
	//	if((int)$iver == 2) { // v2
	//		//--
	//		return (string) Smart::b64s_enc((string)$rawCipherData); // B64s from B64
	//		//--
	//	} else { // v3
			//--
			if((string)$originalPlainTextUsedJustForChecksum == '') { // DO NOT TRIM !
				return ''; // cannot create a checksum without this !
			} //end if
			//--
			$pak = (string) Smart::b64s_enc((string)$rawCipherData); // b64s
			//--
			$cks = (string) SmartHashCrypto::sh3a224((string)$pak.chr(0).$originalPlainTextUsedJustForChecksum);
			$cks = (string) Smart::base_from_hex_convert((string)$cks, 62); // {{{SYNC-CRYPTO-PAK-SIGNATURE-SHA224B62}}}
			//--
			return (string) $pak.';'.$cks; // B64s + ; + B62-Checksum
			//--
	//	} //end if else
		//--
	} //END FUNCTION

	//==============================================================


	//==============================================================
	// unpack the data container, before decryption, B64s + Checksum ; Validate, Verify Checksums and Everything ...
	private static function dataContainerUnpackAndVerify(string $cipherText, int $iver) : array {
		//--
		$upk = [
			'dbg'  => false, 	// log error only on debug, if set to true
			'err'  => '??', 	// error message or empty if OK
			'dlen' => 0, 		// length of data
			'data' => '', 		// unpacked data (raw crypto data)
			'clen' => 0, 		// length of checksum
			'cksm' => '', 		// checksum
			'ctyp' => ';;', 	// checksum type
		];
		//--
		$pcheck = '';
		//--
		$cipherText = (string) trim((string)$cipherText);
		if((string)$cipherText == '') {
			$upk['err'] = ''; // clear
			return (array) $upk; // this should be no error
		} //end if
		//--
		if(
			((int)$iver != 1)
			AND
			((int)$iver != 2)
		) { // v3+ only ; not v1, not v2
			//--
			if(strpos($cipherText, ';') === false) { // {{{SYNC-CRYPTO-PAK-SIGNATURE-CHECK-SHA224B62}}}
				$upk['dbg'] = true; // this is debug only info ...
				$upk['err'] = 'Unpack: Data signature is missing';
				return (array) $upk; // signature separator ; not found
			} //end if
			$cipherText = (array) explode(';', (string)$cipherText);
			$pcheck = (string) trim((string)($cipherText[1] ?? null));
			$cipherText = (string) trim((string)($cipherText[0] ?? null));
			//--
		} //end if
		//--
		if((int)$iver == 1) { // v1
			$encmode = 'HEX';
			$cipherText = (string) strtolower((string)$cipherText);
			if((int)strlen((string)$cipherText) !== (int)strspn((string)$cipherText, (string)Smart::CHARSET_BASE_16)) {
				$upk['err'] = 'Unpack: Data v1 contains Invalid HEX Characters (v'.(int)$iver.'/'.$encmode.')';
				return (array) $upk; // data v1 is invalid
			} //end if
			$cipherText = (string) Smart::safe_hex_2_bin((string)$cipherText, false, false); // do not ignore case (was made strtolower, hex) ; skip logging (untrusted source)
		} elseif((int)$iver == 2) { // v2
			$encmode = 'B64s';
			if(!preg_match((string)Smart::REGEX_SAFE_B64S_STR, (string)$cipherText)) { // {{{SYNC-BFCRYPTO-VALIDATE-B64-PAK}}}
				$upk['err'] = 'Unpack: Data v2 contains Invalid B64S Characters (v'.(int)$iver.'/'.$encmode.')';
				return (array) $upk; // data v2 is invalid
			} //end if
			$cipherText = (string) Smart::b64s_dec((string)$cipherText); // b64s
		} else { // v3
			if((string)trim((string)$pcheck) == '') {
				$upk['err'] = 'Unpack: Data v3 Raw Checksum is Empty (v'.(int)$iver.')';
				return (array) $upk; // checksum v3 is invalid
			} //end if
			if((int)strlen((string)$pcheck) !== (int)strspn((string)$pcheck, (string)Smart::CHARSET_BASE_62)) { // {{{SYNC-CRYPTO-PAK-SIGNATURE-SHA224B62}}}
				$upk['err'] = 'Unpack: Data v3 Raw Checksum contains Invalid B62 Characters (v'.(int)$iver.')';
				return (array) $upk; // checksum v3 is invalid
			} //end if
			$pcheck = (string) trim((string)Smart::base_to_hex_convert((string)$pcheck, 62)); // this should be a sh3a224, HEX, 56 chars length
			if((string)trim((string)$pcheck) == '') {
				$upk['err'] = 'Unpack: Data v3 Checksum is Empty (v'.(int)$iver.')';
				return (array) $upk; // checksum v3 is invalid
			} //end if
			if((int)strlen((string)$pcheck) != 56) {
				$upk['err'] = 'Unpack: Data v3 Checksum Length['.(int)strlen((string)$pcheck).'] is invalid, must be 56 (v'.(int)$iver.')';
				return (array) $upk; // checksum v3 is invalid
			} //end if
			if((int)strlen((string)$pcheck) !== (int)strspn((string)$pcheck, (string)Smart::CHARSET_BASE_16)) {
				$upk['err'] = 'Unpack: Data v3 Checksum contains Invalid HEX Characters (v'.(int)$iver.')';
				return (array) $upk; // checksum v3 is invalid
			} //end if
			$encmode = 'B64s';
			if(!preg_match((string)Smart::REGEX_SAFE_B64S_STR, (string)$cipherText)) { // {{{SYNC-BFCRYPTO-VALIDATE-B64-PAK}}}
				$upk['err'] = 'Unpack: Data v3 contains Invalid B64S Characters (v'.(int)$iver.'/'.$encmode.')';
				return (array) $upk; // data v3 is invalid
			} //end if
			$cipherText = (string) Smart::b64s_dec((string)$cipherText); // b64s
		} //end if else
		//--
		if((string)trim((string)$cipherText) == '') {
			$upk['err'] = 'Unpack: Decode Failed (v'.(int)$iver.'/'.$encmode.')';
			return (array) $upk; // this should be no error
		} //end if
		//--
		$upk['dbg']  = (bool)   (is_bool($upk['dbg']) ? $upk['dbg'] : false);
		$upk['err']  = (string) ''; // clear
		$upk['dlen'] = (int)    strlen((string)$cipherText);
		$upk['data'] = (string) $cipherText;
		$upk['clen'] = (int)    strlen((string)$pcheck);
		$upk['cksm'] = (string) $pcheck;
		$upk['ctyp'] = (string) 'SHA3-224';
		return (array) $upk; // OK
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Parse cipher signature (smart)
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param ENUM $str 			cipher signature: hash/{mode}, blowfish.cbc, openssl/cipher/mode
	 * @return ARRAY 				[ 'err' => '', 'provider' => '', 'cipher' => '', 'mode' => '' ]
	 */
	private static function parseCipherProperties(string $str) {
		//--
		$str = (string) trim((string)$str);
		//--
		$err = '';
		$arr = [];
		//--
		if((string)$str != '') {
			$arr = (array) explode('/', (string)$str, 3); // example: 'openssl/aes256/CBC' ; hash/sha3-384 ; blowfish.cbc
		} else {
			$err = 'Empty Signautre';
		} //end if
		//--
		return [
			'err' 		=> (string) trim((string)$err),
			'provider' 	=> (string) strtolower((string)trim((string)($arr[0] ?? null))), // ex: 'openssl' | 'hash'   | 'blowfish.cbc'
			'cipher' 	=> (string) strtolower((string)trim((string)($arr[1] ?? null))), // ex: 'aes256'  | 'sha384' | ''
			'mode' 		=> (string) strtolower((string)trim((string)($arr[2] ?? null))), // ex: 'CBC'     | ''       | ''
		];
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Get the crypto object or an error message if could not initialize the crypto object
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param ENUM $cipher 		Selected cipher: hash/{mode}, blowfish.cbc, openssl/cipher/mode
	 * @param STRING $key 		The encryption key
	 * @param INT $smartVerKD 	Accepted versions for encrypt: 3 ; for decrypt: 3, 2, 1
	 * @return MIXED 			Crypto OBJECT / The Error Message as STRING
	 */
	private static function crypto(string $cipher, string $key, int $smartVerKD) { // mixed: string|object
		//--
		if(((int)$smartVerKD < 1) OR ((int)$smartVerKD > 3)) {
			return 'Invalid Version: '.(int)$smartVerKD;
		} //end if
		//--
		if(
			((string)$cipher != (string)self::ALGO_BF_CBC_INTERNAL) // blowfish . cbc
			AND
			((int)$smartVerKD != 3)
		) { // only blowfish . cbc cand handle other versions than current (v3)
			return 'Invalid Smart Version ['.(int)$smartVerKD.'] for Cipher: '.$cipher;
		} //end if
		//--
		$key = (string) trim((string)$key);
		if((string)$key == '') {
			return 'The Key is Empty';
		} //end if
		//--
		$cipherParseArr = (array) self::parseCipherProperties((string)$cipher);
		if((string)$cipherParseArr['err'] != '') {
			return 'Cipher Signature Parse Error: '.$cipherParseArr['err'];
		} //end if
		//--
		$kSize  = 0; // init: Key size
		$ivSize = 0; // init: iV size
		$tkSize = 0; // init: tweak size
		switch((string)$cipherParseArr['provider']) {
			case (string)self::ALGO_T3F_CBC_INTERNAL: // threefish . cbc
				//--
				if((int)$smartVerKD === 3) { // v1
					$kSize  = 1024/8; // {{{SYNC-THREEFISH-KEY}}}
					$ivSize = 1024/8; // {{{SYNC-THREEFISH-IV}}}
					$tkSize =  128/8; // {{{SYNC-THREEFISH-TWEAK}}}
				} else {
					return 'Threefish Cipher/Algo Version ['.$smartVerKD.'] :: Version is Invalid: `'.$cipher.'` # `'.$cipherParseArr['cipher'].'`';
				} //end if
				//--
				break;
			case (string)self::ALGO_TF_CBC_INTERNAL: // twofish . cbc
				//--
				if((int)$smartVerKD === 3) { // v1
					$kSize  = 256/8; // {{{SYNC-TWOFISH-KEY}}}
					$ivSize = 128/8; // {{{SYNC-TWOFISH-IV}}}
				} else {
					return 'Twofish Cipher/Algo Version ['.$smartVerKD.'] :: Version is Invalid: `'.$cipher.'` # `'.$cipherParseArr['cipher'].'`';
				} //end if
				//--
				break;
			case (string)self::ALGO_BF_CBC_INTERNAL: // blowfish . cbc
				//--
				if((int)$smartVerKD === 1) { // v1
					$kSize  = 384/8; // {{{SYNC-BLOWFISH-V1-KEY}}}
					$ivSize =  64/8; // {{{SYNC-BLOWFISH-V1-IV}}}
				} elseif(
					((int)$smartVerKD === 2) // v2
					OR
					((int)$smartVerKD === 3) // v3
				) {
					$kSize  = 448/8; // {{{SYNC-BLOWFISH-KEY}}}
					$ivSize =  64/8; // {{{SYNC-BLOWFISH-IV}}}
				} else {
					return 'Blowfish Cipher/Algo Version ['.$smartVerKD.'] :: Version is Invalid: `'.$cipher.'` # `'.$cipherParseArr['cipher'].'`';
				} //end if
				//--
				break;
			case 'openssl': // blowfish, aes256, camellia256 ; modes: CBC / CFB / OFB
				//--
				if((int)$smartVerKD != 3) { // needs current version, it does not handle versions ...
					//--
					return 'OpenSSL Cipher/Algo Version ['.$smartVerKD.'] :: Version is Invalid: `'.$cipher.'` # `'.$cipherParseArr['cipher'].'`';
					//--
				} else { // v3 only
					//--
					if(
						((string)$cipherParseArr['cipher'] == 'blowfish') // modes: CBC / CFB / OFB
					) {
						//-- openssl can only v3
						$kSize  = 448/8; // {{{SYNC-BLOWFISH-KEY}}}
						$ivSize =  64/8; // {{{SYNC-BLOWFISH-IV}}}
						//--
					} elseif(
						((string)$cipherParseArr['cipher'] == 'aes256')
						OR // modes: CBC / CFB / OFB
						((string)$cipherParseArr['cipher'] == 'camellia256')
					) {
						//--
						$kSize  = 256/8; // {{{SYNC-AES256/CAM256-KEY}}}
						$ivSize = 128/8; // {{{SYNC-AES256/CAM256-IV}}}
						//--
					} elseif(
						((string)$cipherParseArr['cipher'] == 'idea') // modes: CBC / CFB / OFB
					) {
						//--
						$kSize  = 128/8; // {{{SYNC-IDEA-CIPHER-KEY}}}
						$ivSize =  64/8; // {{{SYNC-IDEA-CIPHER-IV}}}
						//--
					} else {
						//--
						return 'OpenSSL Cipher/Algo Version ['.$smartVerKD.'] :: Cipher is Invalid: `'.$cipher.'` # `'.$cipherParseArr['cipher'].'`';
						//--
					} //end if else
					//--
				} //end if else
				//--
				break;
			case 'hash': // {{{SYNC-HASH-ENC/DEC-HASHING}}}
				//--
				if((int)$smartVerKD != 3) { // needs current version, it does not handle versions ...
					//--
					return 'Hash Cipher/Algo Version ['.$smartVerKD.'] :: Version is Invalid: `'.$cipher.'` # `'.$cipherParseArr['cipher'].'`';
					//--
				} else { // v3 only
					//--
					if(
						((int)$smartVerKD == 3) // needs current version, it does not handle versions ...
						AND
						(
							((string)$cipherParseArr['cipher'] == 'sha3-256')
							OR
							((string)$cipherParseArr['cipher'] == 'sha384')
							OR
							((string)$cipherParseArr['cipher'] == 'sha3-384')
							OR
							((string)$cipherParseArr['cipher'] == 'sha3-512')
							OR
							((string)$cipherParseArr['cipher'] == 'sha224')
							OR
							((string)$cipherParseArr['cipher'] == 'sha3-224')
							OR
							((string)$cipherParseArr['cipher'] == '') // default: sha3-224
						)
					) {
						//--
						$kSize  = -1; // key will be derived inside
						$ivSize = -1; // does not use IV
						//--
					} else {
						//--
						return 'Hash Cipher/Algo Version ['.$smartVerKD.'] :: Cipher is Invalid: `'.$cipher.'` # `'.$cipherParseArr['cipher'].'`';
						//--
					} //end if else
					//--
				} //end if else
				//--
				break;
			default:
				//--
				return 'Cipher/Algo Version ['.$smartVerKD.'] :: Invalid: `'.$cipher.'` # `'.$cipherParseArr['cipher'].'`';
				//--
		} //end switch
		//--
		$isTf = false; // init
		if(
			((string)$cipherParseArr['provider'] == (string)self::ALGO_TF_CBC_INTERNAL) // twofish . cbc
		) {
			$isTf = true; // init
		} //end if
		//--
		$arr_kiv = [ // {{{SYNC-ASCRYPTO-KEY-DERIVE-ARR-KEYS}}}
			'err' 	=> 'Un-Initialized Key Derivation, version v'.(int)$smartVerKD,
			'key' 	=> '', // init as empty
			'iv' 	=> '', // init as empty
		];
		//--
		if((string)$cipherParseArr['provider'] == 'hash') {
			//--
			$arr_kiv = [ // {{{SYNC-ASCRYPTO-KEY-DERIVE-ARR-KEYS}}}
				'err' 	=> '', 				// RESET !
				'key' 	=> (string) $key, 	// key will be derived inside
				'iv' 	=> '', 				// no IV
			];
			//--
		} else { // twofish . cbc | blowfish . cbc | openssl
			//--
			if(
				((string)$cipherParseArr['provider'] == (string)self::ALGO_BF_CBC_INTERNAL) // blowfish . cbc
				AND
				((int)$smartVerKD == 1)
			) { // v1
				//-- supports only these providers ; no support for openssl to preserve compatibility with v1
				$arr_kiv = (array) self::v1KeyDerive((string)$key);
				//--
			} elseif(
				((string)$cipherParseArr['provider'] == (string)self::ALGO_BF_CBC_INTERNAL) // blowfish . cbc
				AND
				(
					((int)$smartVerKD == 2)
					OR
					((int)$smartVerKD == 3)
				)
			) { // v2 or v3 blowfish uses only v2 KD (not v3 KD)
				//-- supports only these providers ; no support for openssl to preserve compatibility with v2
				$arr_kiv = (array) self::v2KeyDerive((string)$key);
				//-- fix: v2 is providing a a bit larger key and iv to ensure minimal 448 encoding, need this fix !
				$arr_kiv['key'] = (string) substr((string)$arr_kiv['key'], 0, 448/8); // {{{SYNC-BLOWFISH-KEY}}}
				$arr_kiv['iv'] = (string) substr((string)$arr_kiv['iv'], 0, 64/8); // {{{SYNC-BLOWFISH-IV}}}
				//-- #end fix
			} elseif(
				((string)$cipherParseArr['provider'] == (string)self::ALGO_T3F_CBC_INTERNAL) // threefish . cbc
				AND // v3 ThreeFish
				((int)$smartVerKD == 3) // v3
			) { // v3
				//-- current version, v3 extended, with Tweak: based on PBKDF2, the industry standard for key derivation ; supports: threefish
				$arr_kiv = (array) self::v3T3FKeyDerive(
					(string) $key,
					(int)    $kSize,
					(int)    $ivSize,
					(int)    $tkSize,
					(int)    $smartVerKD
				);
				//--
			} elseif((int)$smartVerKD == 3) { // v3: TwoFish or OpenSSL
				//-- current version, v3: based on PBKDF2, the industry standard for key derivation ; supports: twofish . cbc, openssl/{cipher}/{mode}
				$arr_kiv = (array) self::v3KeyDerive(
					(string) $key,
					(int)    $kSize,
					(int)    $ivSize,
					(bool)   $isTf,
					(int)    $smartVerKD
				);
				//--
				if(!is_array($arr_kiv[':settings:'])) {
					$arr_kiv[':settings:'] = [];
				} //end if
				$arr_kiv[':settings:']['current-key-size'] = (int) strlen((string)$arr_kiv['key']);
				$arr_kiv[':settings:']['current-iv-size'] = (int) strlen((string)$arr_kiv['iv']);
				//--
				$arr_kiv[':adjusted:'] = true;
				$arr_kiv[':cipher-parsed:'] = (array) $cipherParseArr;
				$arr_kiv[':cipher-signature:'] = (string) $cipher;
				//--
				if(array_key_exists('#AEAD-TAG#', (array)$arr_kiv)) { // CBC or GCM, AEAD Tag (Data Integrity Tag, Cipher Security)
					if((string)$arr_kiv['#AEAD-TAG#'] == '') { // based on Poly1305 and random_bytes crypto safe randomization
						if((string)$arr_kiv['err'] == '') {
							$arr_kiv['err'] = 'Empty AEAD Tag: '.$arr_kiv['#AEAD-TAG#'];
						} //end if
					} //end if
				} //end if
				//--
	//	Smart::log_notice(__METHOD__.' # DEBUG: '.print_r($arr_kiv,1));
				//--
			} //end if else
			//-- TEST Key and IV !
			if((string)$arr_kiv['err'] == '') {
				if(
					((string)trim((string)$arr_kiv['key']) == '')
					OR
					((int)strlen((string)trim((string)$arr_kiv['key'])) !== (int)$kSize)
					OR // test both: trim or not, key length should be both, no prefix/suffix spaces are allowed !
					((int)strlen((string)$arr_kiv['key']) !== (int)$kSize)
				) {
					$arr_kiv['err'] = 'Empty or Invalid Key Derivation Length['.(int)strlen((string)$arr_kiv['key']).'] and expects['.(int)$kSize.'], on Provider: `'.$cipherParseArr['provider'].'` ; Cipher: `'.$cipherParseArr['cipher'].'` ; Version: `'.(int)$smartVerKD.'`';
				} elseif(
					((string)trim((string)$arr_kiv['iv']) == '')
					OR
					((int)strlen((string)trim((string)$arr_kiv['iv'])) !== (int)$ivSize)
					OR // test both: trim or not, iv length should be both, no prefix/suffix spaces are allowed !
					((int)strlen((string)$arr_kiv['iv']) !== (int)$ivSize)
				) {
					$arr_kiv['err'] = 'Empty or Invalid IV Derivation Length['.(int)strlen((string)$arr_kiv['iv']).'] and expects['.(int)$ivSize.'], on Provider: `'.$cipherParseArr['provider'].'` ; Cipher: `'.$cipherParseArr['cipher'].'` ; Version: `'.(int)$smartVerKD.'`';
				} else { // OK
					//--
					$arr_kiv['err'] = ''; // RESET !
					//--
				} //end if else
			} //end if
			//--
		} //end if else
		//--
		if((string)$arr_kiv['err'] != '') {
			//--
			Smart::log_notice(__METHOD__.' # Safe Derived Key/Iv/Tweak ERR: '.$arr_kiv['err']);
			//--
			return 'ERR: '.$arr_kiv['err'];
			//--
		} //end if
		//--
		if(!array_key_exists('tk', $arr_kiv)) {
			$arr_kiv['tk'] = null; // tweak ; required just by ThreeFish
		} //end if
		//--
		$crypto = 'Not Initialized: ???';
		//--
		if((string)$cipher == (string)self::ALGO_T3F_CBC_INTERNAL) { // threefish . cbc
			//-- explicit: threefish v3 have support to be encrypted/decrypted only with the internal class SmartCryptoCiphersThreefishCBC ; OpenSSL doesn't yet support for Twofish
			if(
				((int)$smartVerKD == 3) // both: encrypt/decrypt
			) {
				$crypto = new SmartCryptoCiphersThreefishCBC((string)$arr_kiv['key'], (string)$arr_kiv['iv'], (string)$arr_kiv['tk'], 'v'.(int)$smartVerKD);
			} else {
				$crypto = 'INVALID Version for T3F Cipher/Algo ['.(int)$smartVerKD.']: `'.$cipher.'`';
			} //end if else
			//--
		} elseif((string)$cipher == (string)self::ALGO_TF_CBC_INTERNAL) { // twofish . cbc
			//-- explicit: twofish v3 have support to be encrypted/decrypted only with the internal class SmartCryptoCiphersTwofishCBC ; OpenSSL doesn't yet support for Twofish
			if(
				((int)$smartVerKD == 3) // both: encrypt/decrypt
			) {
				$crypto = new SmartCryptoCiphersTwofishCBC((string)$arr_kiv['key'], (string)$arr_kiv['iv'], 'v'.(int)$smartVerKD);
			} else {
				$crypto = 'INVALID Version for TF Cipher/Algo ['.(int)$smartVerKD.']: `'.$cipher.'`';
			} //end if else
			//--
		} elseif((string)$cipher == (string)self::ALGO_BF_CBC_INTERNAL) { // blowfish . cbc
			//-- explicit: blowfish v1 / v2 have support to only be decrypted with the internal class SmartCryptoCiphersBlowfishCBC as the class SmartCryptoCiphersOpenSSL doesn't have logic for old versions or may be incompatible ...
			if(
				((int)$smartVerKD == 1) // only decrypt
				OR // Blowfish CBC should remain at v1 / v2, will not be upgraded to v3+ ; v2 is for current exchanging also with Javascript
				((int)$smartVerKD == 2) // only decrypt
				OR
				((int)$smartVerKD == 3) // both: encrypt/decrypt
			) {
				$crypto = new SmartCryptoCiphersBlowfishCBC((string)$arr_kiv['key'], (string)$arr_kiv['iv'], 'v'.(int)$smartVerKD);
			} else {
				$crypto = 'INVALID Version for BF Cipher/Algo ['.(int)$smartVerKD.']: `'.$cipher.'`';
			} //end if else
			//--
		} elseif((int)$smartVerKD == 3) { // for on the fly, support just v3
			//--
			if((string)$cipherParseArr['provider'] == 'openssl') {
				//-- aes256, camellia256, idea, ... even blowfish (without smartBF signature)
				$crypto = new SmartCryptoCiphersOpenSSL((string)$cipher, (string)$arr_kiv['key'], (string)$arr_kiv['iv']);
				//--
			} elseif((string)substr((string)$cipher, 0, 5) == 'hash/') {
				//--
				$crypto = new SmartCryptoCiphersHashCryptOFB((string)$cipher, (string)$arr_kiv['key']); // key will be derived inside hash object, no IV ...
				//--
			} else {
				//--
				$crypto = 'INVALID Cipher/Algo: `'.$cipher.'`';
				//--
			} //end if else
			//--
		} else {
			//--
			$crypto = 'INVALID Cipher/Algo for Version ['.(int)$smartVerKD.']: `'.$cipher.'`';
			//--
		} //end if else
		//--
		return $crypto; // mixed
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Generate a Derived Key, Initialization vector iV and Algo Tweak for ThreeFish v3 only !
	 * Uses the PBKDF2 key derivation, B92 / B85 / Poly1305 + B92
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param STRING 		$key 			The encryption key
	 * @param INTEGER+ 		$kSize 			The Key Size
	 * @param INTEGER+ 		$ivSize 		The Iv Size
	 * @param INTEGER+ 		$tkSize 		The Tweak Size
	 * @param ENUM 			$smartVerKD 	Version: 3
	 * @return ARRAY 		[ 'err' => '', 'key' => '...', 'iv' => '...', 'tk' => '...' ]
	 */
	private static function v3T3FKeyDerive(?string $key, int $kSize, int $ivSize, int $tkSize, int $smartVerKD) : array {
		//--
		if((int)$smartVerKD != 3) { // v3
			return [
				'err' 	=> 'Invalid KD Version for ThreeFish v3 Key Derive: `'.(int)$smartVerKD.'`', // ERR
				'key' 	=> '',
				'iv' 	=> '',
				'tk' 	=> '',
			];
		} //end if
		//--
		if((int)$kSize !== 128) {
			return [
				'err' 	=> 'Safe Derived T3F v3 Key length must be: 128 and it is: '.(int)$kSize.' ; Iv length is: '.(int)$ivSize.' ; Tweak length is: '.(int)$tkSize, // ERR
				'key' 	=> '',
				'iv' 	=> '',
				'tk' 	=> '',
			];
		} //end if
		if((int)$ivSize !== 128) {
			return [
				'err' 	=> 'Safe Derived T3F v3 IV length must be: 128 and it is: '.(int)$ivSize.' ; Key length is: '.(int)$kSize.' ; Tweak length is: '.(int)$tkSize, // ERR
				'key' 	=> '',
				'iv' 	=> '',
				'tk' 	=> '',
			];
		} //end if
		if((int)$tkSize !== 16) {
			return [
				'err' 	=> 'Safe Derived T3F v3 Tweak length must be: 16 and it is: '.(int)$tkSize.' ; Key length is: '.(int)$kSize.' ; Iv length is: '.(int)$ivSize, // ERR
				'key' 	=> '',
				'iv' 	=> '',
				'tk' 	=> '',
			];
		} //end if
		//--
		$key = (string) trim((string)$key); // {{{SYNC-CRYPTO-KEY-TRIM}}}
		if((string)$key == '') {
			return [
				'err' 	=> 'T3F Key Derive Input Key is Empty or have an Invalid Length', // ERR
				'key' 	=> '',
				'iv' 	=> '',
				'tk' 	=> '',
			];
		} //end if
		//--
		$kSalt = (string) trim((string)SmartHashCrypto::pbkdf2PreDerivedB92Key((string)$key));
		if(
			((string)$kSalt == '')
			OR
			((int)strlen((string)$kSalt) != (int)SmartHashCrypto::DERIVE_PREKEY_LEN)
		) {
			return [
				'err' 	=> 'Derived T3F v3 Key-Salt is Empty or Length does not match', // ERR
				'key' 	=> '',
				'iv' 	=> '',
				'tk' 	=> '',
			];
		} //end if
		//--
		$iSalt = (string) trim((string)SmartHashCrypto::pbkdf2PreDerivedB92Key((string)Smart::dataRRot13((string)Smart::b64s_enc((string)$key))));
		if(
			((string)$iSalt == '')
			OR
			((int)strlen((string)$iSalt) != (int)SmartHashCrypto::DERIVE_PREKEY_LEN)
		) {
			return [
				'err' 	=> 'Derived T3F v3 Iv-Salt is Empty or Length does not match', // ERR
				'key' 	=> '',
				'iv' 	=> '',
				'tk' 	=> '',
			];
		} //end if
		//--
		$safeKey = (string) trim((string)SmartHashCrypto::pbkdf2DerivedB92Key( // B92
			(string) $key, // k
			(string) $kSalt, // s B92
			(int)    128, // l
			(int)    SmartHashCrypto::DERIVE_CENTITER_EK, // i
			(string) 'sha3-512' // a
		));
		if(
			((string)$safeKey == '')
			OR
			((int)strlen((string)$safeKey) != 128)
		) {
			return [
				'err' 	=> 'Derived T3F v3 Safe-Key is Empty or Length does not match', // ERR
				'key' 	=> '',
				'iv' 	=> '',
				'tk' 	=> '',
			];
		} //end if
		//--
		$safeIv = (string) trim((string)SmartHashCrypto::pbkdf2DerivedHexKey(
			(string) $key, // k
			(string) $iSalt, // s B92
			(int)    128 * 2, // l
			(int)    SmartHashCrypto::DERIVE_CENTITER_EV, // i
			(string) 'sha3-384' // a
		));
		if(
			((string)$safeIv == '')
			OR
			((int)strlen((string)$safeIv) != (int)(128*2))
		) {
			return [
				'err' 	=> 'Pre-Derived T3F v3 Safe-Iv is Empty or Length does not match', // ERR
				'key' 	=> '',
				'iv' 	=> '',
				'tk' 	=> '',
			];
		} //end if
		$safeIv = (string) substr((string)trim((string)Smart::base_from_hex_convert((string)$safeIv, 85)), 0, 128); // B85
		if(
			((string)$safeIv == '')
			OR
			((int)strlen((string)$safeIv) != 128)
		) {
			return [
				'err' 	=> 'Derived T3F v3 Safe-Iv is Empty or Length does not match', // ERR
				'key' 	=> '',
				'iv' 	=> '',
				'tk' 	=> '',
			];
		} //end if
		//--
		$ckSumCrc32bKeyHex 	= (string) SmartHashCrypto::crc32b((string)$key);
		$ckSumCrc32bDKeyHex = (string) SmartHashCrypto::crc32b((string)base64_encode((string)$key));
		$ckSumCrc32bKeyEnc 	= (string) Smart::base_from_hex_convert((string)$ckSumCrc32bKeyHex.$ckSumCrc32bDKeyHex, 62);
		$ckSumCrc32bDKeyEnc = (string) Smart::base_from_hex_convert((string)$ckSumCrc32bDKeyHex.$ckSumCrc32bKeyHex, 58);
		$ckSumHash 			= (string) SmartHashCrypto::sh3a512((string)$key.chr(0).SmartHashCrypto::SALT_PREFIX.' '.SmartHashCrypto::SALT_SEPARATOR.' '.SmartHashCrypto::SALT_SUFFIX.chr(0).$ckSumCrc32bKeyEnc.chr(0).$ckSumCrc32bDKeyEnc, true); // b64
		$poly1305Sum 		= (string) trim((string)SmartHashCrypto::poly1305((string)SmartHashCrypto::md5((string)$ckSumHash), (string)$key, false)); // hex
		if((string)$poly1305Sum == '') {
			return [
				'err' 	=> 'Pre-Derived (step 1) T3F v3 Safe-Tweak is Empty or Length does not match', // ERR
				'key' 	=> '',
				'iv' 	=> '',
				'tk' 	=> '',
			];
		} //end if
		$b92Tweak 			= (string) trim((string)Smart::base_from_hex_convert((string)$poly1305Sum, 92)); // B92
		if(
			((string)$b92Tweak == '')
			OR
			((int)strlen((string)$b92Tweak) < 15)
		) {
			return [ // no step2 in PHP, this is step 3 check !
				'err' 	=> 'Pre-Derived (step 3) T3F v3 Safe-Tweak is Empty or Length does not match', // ERR
				'key' 	=> '',
				'iv' 	=> '',
				'tk' 	=> '',
			];
		} //end if
		$safeTweak = (string) str_pad((string)substr((string)$b92Tweak, 0, 16), 16, '`', STR_PAD_RIGHT); // 128/8 ; pad with ` as it is only base 92
		//--
		if(
			((string)$safeTweak == '')
			OR
			((int)strlen((string)$safeTweak) != 16)
		) {
			return [
				'err' 	=> 'Derived T3F v3 Safe-Tweak is Empty or Length does not match', // ERR
				'key' 	=> '',
				'iv' 	=> '',
				'tk' 	=> '',
			];
		} //end if
		//--
		$arr_kiv = [ // {{{SYNC-ASCRYPTO-KEY-DERIVE-ARR-KEYS}}}
			'err' => (string) '', // reset
			'key' => (string) $safeKey, 	// b92
			'iv'  => (string) $safeIv, 		// b85
			'tk'  => (string) $safeTweak, 	// b92
		];
	//Smart::log_notice('V2 Derive: '.print_r($arr_kiv,1));
		//--
		return (array) $arr_kiv;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Generate a Derived Key and Initialization vector iV for v3 only !
	 * Uses the PBKDF2 key derivation, B92
	 * It also produces an AEAD Tag based on Poly1305 and random_bytes crypto safe randomization
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param STRING 		$key 			The encryption key
	 * @param INTEGER+ 		$kSize 			The Key Size
	 * @param INTEGER+ 		$ivSize 		The Iv Size
	 * @param BOOLEAN 		$isTf 			Is (specific) Twofish (v3) ; for this particular cipher extra checks are performed to ensure security
	 * @param ENUM 			$smartVerKD 	Version: 1, 2 or 3
	 * @return ARRAY 		[ 'err' => '', 'key' => '...', 'iv' => '...' ]
	 */
	private static function v3KeyDerive(?string $key, int $kSize, int $ivSize, bool $isTf, int $smartVerKD) : array { // {{{SYNC-CRYPTO-KEY-DERIVE}}}
		//--
		if((int)$smartVerKD != 3) { // v3
			return [
				'err' 	=> 'Invalid KD Version for Twofish v3 Key Derive: `'.(int)$smartVerKD.'`', // ERR
				'key' 	=> '',
				'iv' 	=> '',
			];
		} //end if
		//--
		$key = (string) trim((string)$key); // {{{SYNC-CRYPTO-KEY-TRIM}}}
		if((string)$key == '') {
			return [ // {{{SYNC-ASCRYPTO-KEY-DERIVE-ARR-KEYS}}}
				'err' 	=> 'v3 Key Derive Input Key is Empty or have an Invalid Length', // ERR
				'key' 	=> '',
				'iv' 	=> '',
			];
		} //end if
		//--
		$chkValKSize = 0;
		$chkValIVSize = 0;
		if($isTf === true) { // TF is used to store long term-data ; this check is just for TF, to ensure consistency, for the rest, do not check
			//--
			$chkValKSize = 256/8; // {{{SYNC-TWOFISH-KEY}}}
			$chkValIVSize = 128/8; // {{{SYNC-TWOFISH-KEY}}}
			//--
			if((int)$kSize !== (int)$chkValKSize) {
				return [ // {{{SYNC-ASCRYPTO-KEY-DERIVE-ARR-KEYS}}}
					'err' 	=> 'Safe Derived TF v3 Key length must be: '.(int)$chkValKSize.' and it is: '.(int)$kSize.' ; Iv length is: '.(int)$ivSize, // ERR
					'key' 	=> '',
					'iv' 	=> '',
				];
			} //end if
			if((int)$ivSize !== (int)$chkValIVSize) {
				return [ // {{{SYNC-ASCRYPTO-KEY-DERIVE-ARR-KEYS}}}
					'err' 	=> 'Safe Derived TF v3 IV length must be: '.(int)$chkValIVSize.' and it is: '.(int)$ivSize.' ; Key length is: '.(int)$kSize, // ERR
					'key' 	=> '',
					'iv' 	=> '',
				];
			} //end if
			//--
		} //end if
		//--
		$nkSize = (int) ((int)$kSize * 2);  // ensure double size ; {{{SYNC-PBKDF2-HEX-TO-B92-LENGTH-ADJUST}}}
		$nivSize = (int)((int)$ivSize * 2); // ensure double size ; {{{SYNC-PBKDF2-HEX-TO-B92-LENGTH-ADJUST}}}
		//--
		$pbkdf2PreKey = (string) trim((string)SmartHashCrypto::pbkdf2PreDerivedB92Key((string)$key));
		if(
			((string)$pbkdf2PreKey == '')
			OR
			((int)strlen((string)$pbkdf2PreKey) != (int)SmartHashCrypto::DERIVE_PREKEY_LEN)
		) {
			return [ // {{{SYNC-ASCRYPTO-KEY-DERIVE-ARR-KEYS}}}
				'err' 	=> 'Safe Derived v3 Pre-Key is Empty or Length does not match', // ERR
				'key' 	=> '',
				'iv' 	=> '',
			];
		} //end if
		//--
		$pbkdf2PreIv = (string) trim((string)SmartHashCrypto::pbkdf2PreDerivedB92Key((string)Smart::dataRRot13((string)Smart::b64s_enc((string)$key)).chr(0).$pbkdf2PreKey));
		if(
			((string)$pbkdf2PreIv == '')
			OR
			((int)strlen((string)$pbkdf2PreIv) != (int)SmartHashCrypto::DERIVE_PREKEY_LEN)
		) {
			return [ // {{{SYNC-ASCRYPTO-KEY-DERIVE-ARR-KEYS}}}
				'err' 	=> 'Safe Derived v3 Pre-IV is Empty or Length does not match', // ERR
				'key' 	=> '',
				'iv' 	=> '',
			];
		} //end if
		//--
		// PBKDF2, for Key
		$pbkdf2Key = (string) trim((string)SmartHashCrypto::pbkdf2DerivedHexKey(
			(string) $pbkdf2PreKey,  // k
			(string) '['.chr(0).$pbkdf2PreIv."\v".SmartHashCrypto::crc32b((string)"\v".$key.chr(0), true).chr(0).']', // s B92+B36
			(int)    $nkSize, // l
			(int)    SmartHashCrypto::DERIVE_CENTITER_EK, // i
			(string) 'sha3-512' // a
		)); // hex
		if(
			((string)$pbkdf2Key == '')
			OR
			((int)strlen((string)$pbkdf2Key) != (int)$nkSize)
		) {
			return [ // {{{SYNC-ASCRYPTO-KEY-DERIVE-ARR-KEYS}}}
				'err' 	=> 'Safe Derived v3 Key is Empty or Key length does not match after PBKDF2', // ERR
				'key' 	=> '',
				'iv' 	=> '',
			];
		} //end if
		//--
		// PBKDF2, for Iv
		$pbkdf2Iv = (string) trim((string)SmartHashCrypto::pbkdf2DerivedHexKey(
			(string) $pbkdf2PreIv, // k
			(string) '('.chr(0).$pbkdf2PreKey."\v".SmartHashCrypto::crc32b((string)"\v".$key.chr(0)).chr(0).')', // s + B92+Hex
			(int)    $nivSize, // l
			(int)    SmartHashCrypto::DERIVE_CENTITER_EV, // i
			(string) 'sha3-256' // a
			)); // hex
		//--
		if(
			((string)$pbkdf2Iv == '')
			OR
			((int)strlen((string)$pbkdf2Iv) != (int)$nivSize)
		) {
			return [ // {{{SYNC-ASCRYPTO-KEY-DERIVE-ARR-KEYS}}}
				'err' 	=> 'Safe Derived v3 IV is Empty or IV length does not match after PBKDF2', // ERR
				'key' 	=> '',
				'iv' 	=> '',
			];
		} //end if
		//--
		$b92Key = (string) trim((string)Smart::dataRRot13((string)substr((string)trim((string)Smart::base_from_hex_convert((string)$pbkdf2Key, 92)), 0, (int)$kSize)));
		if(
			((string)$b92Key == '')
			OR
			((int)strlen((string)$b92Key) != (int)$kSize)
		) {
			return [ // {{{SYNC-ASCRYPTO-KEY-DERIVE-ARR-KEYS}}}
				'err' 	=> 'Safe Derived v3 Key length does not match after Base Conv', // ERR
				'key' 	=> '',
				'iv' 	=> '',
			];
		} //end if
		//--
		$b85Iv  = (string) trim((string)Smart::dataRRot13((string)substr((string)trim((string)Smart::base_from_hex_convert((string)$pbkdf2Iv, 85)), 0, (int)$ivSize)));
		if(
			((string)$b85Iv == '')
			OR
			((int)strlen((string)$b85Iv) != (int)$ivSize)
		) {
			return [ // {{{SYNC-ASCRYPTO-KEY-DERIVE-ARR-KEYS}}}
				'err' 	=> 'Safe Derived v3 IV length does not match after Base Conv', // ERR
				'key' 	=> '',
				'iv' 	=> '',
			];
		} //end if
		//--
		$arr_kiv = [ // {{{SYNC-ASCRYPTO-KEY-DERIVE-ARR-KEYS}}}
			'err' 			=> '', // RESET !
			'key' 			=> (string) $b92Key, // key
			'iv' 			=> (string) $b85Iv, // iv
			':settings:' => [
				'is-tf:smart' 				=> (bool)   $isTf,
				'version-kd-smart' 			=> (bool)   $smartVerKD,
				'current-key-size' 			=> (int)    strlen((string)$b92Key),
				'chk-val:key-size' 			=> (int)    $chkValKSize,
				'chk-val:key-size-bits' 	=> (int)    ($chkValKSize * 8),
				'current-iv-size' 			=> (int)    strlen((string)$b85Iv),
				'chk-val:iv-size' 			=> (int)    $chkValIVSize,
				'chk-val:iv-size-bits'  	=> (int)    ($chkValIVSize * 8),
			],
			'#AEAD-TAG#' 	=> (string) SmartHashCrypto::poly1305(
											(string) md5((string)$b92Key.chr(0).$b85Iv), // 32 chars, hex
											(string) base64_encode((string)random_bytes(4096)),
											true
										), // internal random salt, crypto safe ...
		];
		//--
	//Smart::log_notice('V3 Derive: '.print_r($arr_kiv,1));
		//--
		return (array) $arr_kiv;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Generate a Derived Key and Initialization vector iV for v2 only
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param STRING $key 		The encryption key
	 * @return ARRAY 			[ 'err' => '', 'key' => '...', 'iv' => '...' ]
	 */
	private static function v2KeyDerive(?string $key) : array { // {{{SYNC-CRYPTO-KEY-DERIVE}}}
		//--
		$key = (string) trim((string)$key); // {{{SYNC-CRYPTO-KEY-TRIM}}}
		if((string)$key == '') {
			return [ // {{{SYNC-ASCRYPTO-KEY-DERIVE-ARR-KEYS}}}
				'err' 	=> 'v3 Key Derive Input Key is Empty or have an Invalid Length', // ERR
				'key' 	=> '',
				'iv' 	=> '',
			];
		} //end if
		//--
		$err = '';
		$derived_key = '';
		$iv = '';
		//--
		$composed_key = (string) self::v2ComposedKey((string)$key); // this is private, because v1/v2 are EOL and this method is being used just for decoding since v3
		if((string)trim((string)$composed_key) == '') {
			return [ // {{{SYNC-ASCRYPTO-KEY-DERIVE-ARR-KEYS}}}
				'err' 	=> 'v2 Key Derive Composed Key is Empty or have an Invalid Length', // ERR
				'key' 	=> '',
				'iv' 	=> '',
			];
		} //end if
		//--
		$composed_trimmed_key = (string) trim((string)$composed_key);
		$len_composed_key = (int) strlen((string)$composed_key);
		$len_composed_trimmed_key = (int) strlen((string)$composed_trimmed_key);
		if(
			((string)$composed_trimmed_key == '')
			OR
			(
				((int)$len_composed_key !== 553)
				OR
				((int)$len_composed_trimmed_key !== 553)
			)
		) { // ERR
			//--
			$err = (string) 'Safe Derived v2 Key length is invalid or does not match, normal vs. trimmed: '.(int)$len_composed_key.' vs. '.(int)$len_composed_trimmed_key.' !';
			//--
		} else { // OK
			//--
			$derived_key = (string) Smart::base_from_hex_convert((string)SmartHashCrypto::sha256((string)$composed_key), 92)."'".Smart::base_from_hex_convert((string)SmartHashCrypto::md5((string)$composed_key), 92);
			//-- IV, based on crc32 and sha1
			$iv = (string) str_pad((string)SmartHashCrypto::crc32b((string)$key, true), 8, '0', STR_PAD_LEFT);
		//	$iv .= ':'.SmartHashCrypto::sha1((string)$key, true); // no more needed, only used for BF, where iV size is 8
			$iv = (string) substr((string)$iv, 0, 8);
			//--
		} //end if else
		//--
		if((string)$err != '') {
			$derived_key = ''; // reset
			$iv = ''; // reset
		} //end if
		//--
		$arr_kiv = [ // {{{SYNC-ASCRYPTO-KEY-DERIVE-ARR-KEYS}}}
			'err' => (string) $err, // error message
			'key' => (string) $derived_key, // b92 ; safe derivation: contains a complete SHA256(B64) and the first 11 chars from SHA512(B64) from the hash of derived key
			'iv'  => (string) $iv, // b36 ; ensure the checksum of original input string
		];
	//Smart::log_notice('V2 Derive: '.print_r($arr_kiv,1));
		//--
		return (array) $arr_kiv;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the V2 Composed Key from a Key to be used in hash derived methods
	 * The purpose of this method is to provide a colission free pre-derived key from a string key (a password, an encrypt/decrypt key) to be used as the base to create a real derived key by hashing methods later
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param STRING   $key 			The key (min 7 bytes ; max 2048 bytes)
	 * @return STRING 					The safe composed key, 553 bytes (characters) ; contains only an ascii subset of: hexa[01234567890abcdef] + NullByte
	 */
	private static function v2ComposedKey(?string $key) : string { // {{{SYNC-CRYPTO-KEY-DERIVE}}}
		//--
		// This should be used as the basis for a derived key, will be 100% in theory and practice agains hash colissions (see the comments below)
		// It implements a safe mechanism that in order that a key to produce a colission must collide at the same time in all hashing mechanisms: md5, sha1, ha256 and sha512 + crc32b control
		// By enforcing the max key length to 4096 bytes actually will not have any chance to collide even in the lowest hashing such as md5 ...
		// It will return a string of 553 bytes length as: (base:key)[8(crc32b) + 1(null) + 32(md5) + 1(null) + 40(sha1) + 1(null) + 64(sha256) + 1(null) + 128(sha512) = 276] + 1(null) + (base:saltedKeyWithNullBytePrefix)[8(crc32b) + 1(null) + 32(md5) + 1(null) + 40(sha1) + 1(null) + 64(sha256) + 1(null) + 128(sha512) = 276]
		// More, it will return a fixed length (553 bytes) string with an ascii subset just of [ 01234567890abcdef + NullByte ] which already is colission free by using a max source string length of 4096 bytes and by combining many hashes as: md5, sha1, sha256, sha512 and the crc32b
		//--
		$key  = (string) trim((string)$key); // {{{SYNC-CRYPTO-KEY-TRIM}}} ; always must use a trimmed key !
		$klen = (int)    strlen((string)$key);
		//--
		if((int)$klen < (int)SmartHashCrypto::DERIVE_MIN_KLEN) { // {{{SYNC-CRYPTO-KEY-MIN}}} ; minimum acceptable secure key is 7 characters long
			if(SmartEnvironment::ifInternalDebug()) {
				if(SmartEnvironment::ifDebug()) {
					Smart::log_notice(__METHOD__.' # Key is too short: `'.$key.'`');
				} //end if
			} //end if
			return '';
		} elseif((int)$klen > (int)SmartHashCrypto::DERIVE_MAX_KLEN) { // {{{SYNC-CRYPTO-KEY-MAX}}} ; max key size is enforced to allow ZERO theoretical colissions on any of: md5, sha1, sha256 or sha512
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
		$hkey1 = (string) SmartHashCrypto::crc32b((string)$key).       $nByte.SmartHashCrypto::md5((string)$key).       $nByte.SmartHashCrypto::sha1((string)$key).       $nByte.SmartHashCrypto::sha256((string)$key).       $nByte.SmartHashCrypto::sha512((string)$key);
		$hkey2 = (string) SmartHashCrypto::crc32b((string)$salted_key).$nByte.SmartHashCrypto::md5((string)$salted_key).$nByte.SmartHashCrypto::sha1((string)$salted_key).$nByte.SmartHashCrypto::sha256((string)$salted_key).$nByte.SmartHashCrypto::sha512((string)$salted_key);
		//--
		return (string) $hkey1.$nByte.$hkey2;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Generate a Derived Key and Initialization vector iV for v1 only
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param STRING $key 		The encryption key
	 * @return ARRAY 			[ 'err' => '', 'key' => '...', 'iv' => '...' ]
	 */
	private static function v1KeyDerive(?string $key) : array { // {{{SYNC-CRYPTO-KEY-DERIVE}}}
		//--
		$key = (string) trim((string)$key); // {{{SYNC-CRYPTO-KEY-TRIM}}}
		if((string)$key == '') {
			return [ // {{{SYNC-ASCRYPTO-KEY-DERIVE-ARR-KEYS}}}
				'err' 	=> 'v1 Key Derive Input Key is Empty or have an Invalid Length', // ERR
				'key' 	=> '',
				'iv' 	=> '',
			];
		} //end if
		//--
		$arr_kiv = [ // {{{SYNC-ASCRYPTO-KEY-DERIVE-ARR-KEYS}}}
			'err' 	=> '', // none, here, at v1 ...
			'key' 	=> (string) substr((string)SmartHashCrypto::sha512((string)$key), 13, 29).strtoupper((string)substr((string)sha1((string)$key), 13, 10)).substr((string)md5((string)$key), 13, 9),
			'iv' 	=> (string) substr((string)base64_encode((string)sha1('@Smart.Framework-Crypto/BlowFish:'.$key.'#'.sha1('BlowFish-iv-SHA1'.$key).'-'.strtoupper((string)md5('BlowFish-iv-MD5'.$key)).'#')), 1, 8),
		];
		//--
	//Smart::log_notice('V1 Derive: '.print_r($arr_kiv,1));
		//--
		return (array) $arr_kiv;
		//--
	} //END FUNCTION
	//==============================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: SmartDhKx
 * Provides methods to implement a secure algorithm for Diffie-Hellman key exchange between a server and a client
 * Supports dual operation mode (Int64 or BigInt ; for using BigInt requires the PHP GMP extension)
 * It implements a slightly modified version of the DH algo to provide much more secure shared data ...
 *
 * @access 		private
 * @internal
 *
 * @usage       dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 *
 * @depends     PHP GMP extension (optional, only if uses BigInt) ; classes: Smart, SmartHashCrypto, SmartCipherCrypto, SmartCryptoCiphersBlowfishCBC
 * @version     v.20231202
 *
 */
final class SmartDhKx {

	private $useBigInt = false;
	private $size = 'default';
	private $prix = 'default';


	public function __construct(bool $useBigInt=true, ?string $prix=null, ?string $size=null) {
		//--
		if((bool)$useBigInt === true) {
			//--
			if(!function_exists('gmp_random_bits')) {
				Smart::raise_error(__METHOD__.' # PHP GMP Extension is required');
				return;
			} //end if
			//--
			$this->useBigInt = true;
			//--
		} else {
			//--
			$this->useBigInt = false;
			//--
		} //end if else
		//--
		$size = (string) trim((string)$size);
		if((string)$size != '') {
			$this->size = (string) $size;
		} //end if
		//--
		$prix = (string) trim((string)$prix);
		if((string)$prix != '') {
			$this->prix = (string) $prix;
		} //end if
		//--
	} //END FUNCTION


	public function useBigInt() : bool {
		//--
		return (bool) $this->useBigInt;
		//--
	} //END FUNCTION


	public function getMode() : string {
		//--
		$mode = '??';
		if($this->useBigInt === true) {
			$mode = 'BigInt';
		} else {
			$mode = 'Int64';
		} //end if else
		//--
		return (string) $mode;
		//--
	} //END FUNCTION


	public function getBaseGen() : string {
		//--
		return (string) $this->rng((string)$this->size);
		//--
	} //END FUNCTION


	public function getSrvData(string $basegen) : array {
		//--
		$size 	= (string) $this->size;
		$prix 	= (string) $this->prix;
		$p 		= (string) $this->prime((string)$prix);
		$ssec 	= (string) $this->rng((string)$size);
		$spub 	= (string) $this->powm((string)$basegen, (string)$ssec, (string)$p);
		//--
		return [
			'base' => (string) $basegen,
			'prix' => (string) $prix,
			'sec'  => (string) $ssec,
			'pub'  => (string) $spub,
		];
		//--
	} //END FUNCTION


	public function getSrvShad(string $ssec, string $cpub) : string {
		//--
		$prix 	= (string) $this->prix;
		$p 		= (string) $this->prime((string)$prix);
		$shad 	= (string) $this->powm((string)$cpub, (string)$ssec, (string)$p);
		//--
		return (string) $this->shadizer((string)$shad);
		//--
	} //END FUNCTION


	public function getCliData(string $basegen) : array {
		//--
		$size 	= (string) $this->size;
		$prix 	= (string) $this->prix;
		$p 		= (string) $this->prime((string)$prix);
		$csec 	= (string) $this->rng((string)$size);
		$cpub 	= (string) $this->powm((string)$basegen, (string)$csec, (string)$p);
		//--
		return [
			'base' => (string) $basegen,
			'prix' => (string) $prix,
			'sec'  => (string) $csec,
			'pub'  => (string) $cpub,
		];
		//--
	} //END FUNCTION


	public function getCliShad(string $csec, string $spub) : string {
		//--
		$prix 	= (string) $this->prix;
		$p 		= (string) $this->prime((string)$prix);
		$shad 	= (string) $this->powm((string)$spub, (string)$csec, (string)$p);
		//--
		return (string) $this->shadizer((string)$shad);
		//--
	} //END FUNCTION


	//== [PRIVATES]


	// hexfixer
	private function evenhexlen(?string $shx) {
		//--
		$shx = (string) trim((string)$shx);
		//--
		$len = (int) strlen((string)$shx);
		if((int)$len <= 0) {
			$shx = '00'; // this should not happen but anyway, it have to be fixed just in the case
		} elseif(((int)$len % 2) != 0) {
			$shx = '0'.$shx; // even zeros padding
		} //end if
		//--
		return (string) $shx;
		//--
	} //END FUNCTION


	// shaddowizer
	private function shadizer(?string $shad) : string {
		//--
		$shd = '';
		//--
		if($this->useBigInt === true) {
			$shx = (string) $this->evenhexlen((string)gmp_strval((string)$shad, 16));
			$shd = (string) Smart::base_from_hex_convert((string)$shx, 92);
		} else {
			$shx = (string) $this->evenhexlen((string)base_convert((string)$shad, 10, 16));
			$shd = (string) Smart::base_from_hex_convert((string)$shx, 85)."'".Smart::base_from_hex_convert((string)$shx, 62)."'".Smart::base_from_hex_convert((string)$shx, 92)."'".Smart::base_from_hex_convert((string)$shx, 58);
		} //end if else
		//--
		return (string) $shd;
		//--
	} //END FUNCTION


	// randomizer
	private function rng(?string $size) : string {
		//--
		if($this->useBigInt === true) {
			return (string) $this->rngBigint((string)$size);
		} else {
			return (string) $this->rngInt64((string)$size);
		} //end if else
		//--
	} //END FUNCTION


	// pwr deriv by prim
	private function powm(?string $a, ?string $b, ?string $pri) : string {
		//--
		if($this->useBigInt === true) {
			return (string) $this->powmBigint((string)$a, (string)$b, (string)$pri);
		} else {
			return (string) $this->powmInt64((string)$a, (string)$b, (string)$pri);
		} //end if else
		//--
	} //END FUNCTION


	// primes ...
	private function prime(?string $prix) : string {
		//--
		if($this->useBigInt === true) {
			return (string) $this->primeBigint((string)$prix);
		} else {
			return (string) $this->primeInt64((string)$prix);
		} //end if else
		//--
	} //END FUNCTION


	//== [SPECIFIC PRIVATES: Int64 and BigInt]


	// Int64 randomizer
	private function rngInt64(?string $size) : string {
		//--
		$size = (string) trim((string)$size);
		if(((string)$size == '') OR ((string)$size == 'default')) {
			$size = 24;
		} //end if
		//--
		switch((int)$size) {
			case 12:
			case 16:
			case 24:
				break;
			default:
				$size = 24;
				Smart::log_warning(__METHOD__.' # Invalid Size Selection, using defaults: '.(int)$size);
		} //end switch
		//--
		return (string) Smart::random_number((int)1, (int)(2 ** (int)$size));
		//--
	} //END FUNCTION


	// BigInt randomizer
	private function rngBigint(?string $size) : string {
		//--
		$size = (string) trim((string)$size);
		if(((string)$size == '') OR ((string)$size == 'default')) {
			$size = 16;
		} //end if
		//--
		switch((int)$size) {
			case 128:
			case 96:
			case 64:
			case 48:
			case 32:
			case 16:
			case 8:
				break;
			default:
				$size = 16;
				Smart::log_warning(__METHOD__.' # Invalid Size Selection, using defaults: '.(int)$size);
		} //end switch
		//--
		return (string) gmp_random_bits((int)$size * 32); // use as in js, Uint32Array
		//--
	} //END FUNCTION


	// Int64 pwr deriv by prim
	private function powmInt64(?string $a, ?string $b, ?string $pri) : string {
		//--
		$a = (int) $a;
		$b = (int) $b;
		//--
		if(strpos((string)trim((string)$pri), '0x') === 0) { // hex (if hex, expect to be prefixed with 0x)
			$pri = (int) Smart::hex_to_int10((string)trim((string)$pri));
		} else {
			$pri = (int) $pri; // int64
		} //end if else
		//--
		if((int)$b <= 0) {
			return (string) 1;
		} elseif((int)$b === 1) {
			return (string) ((int)$a % (int)$pri);
		} elseif((int)((int)$b % (int)2) === 0) {
			return (string) (
				(int) $this->powmInt64(
					(string) (int) ((int)((int)$a * (int)$a) % (int)$pri),
					(string) (int) ((int)$b / (int)2),
					(string) (int) $pri
				) % (int)$pri
			);
		} else {
			return (string) (
				(int) ((int)$this->powmInt64(
					(string) (int) ((int)((int)$a * (int)$a) % (int)$pri),
					(string) (int) ((int)$b / (int)2),
					(string) (int) $pri
				) * (int)$a) % (int)$pri
			);
		} //end if else
		//--
	} //END FUNCTION


	// Int64 pwr deriv by prim
	private function powmBigint(?string $a, ?string $b, ?string $pri) : string {
		//--
		return (string) gmp_powm((string)$a, (string)$b, (string)gmp_init((string)$pri));
		//--
	} //END FUNCTION


	// Int64 primes ...
	private const primesInt64 = [ // max js safe int is: 9007199254740992 ; of which sqrt is: ~ 94906265 (length: 8)
		72419213, 54795931, 32926051, 21801887, 77635013, 25470191, 77639819, 42010253,
		33563273, 32792339, 15923857, 67022173, 84250253, 67680727, 63438329, 52164643,
		51603269, 61444631, 58831133, 55711141, 73596863, 48905489, 61642963, 53812273,
		16600799, 79158229, 56490361, 73391389, 64351751, 14227727, 40517299, 95234563,
		42913363, 63566527, 52338703, 80146337, 37597201, 93581269, 32547497, 75587359,
		26024821, 57042743, 13862969, 46496719, 42787387, 29830469, 59912407, 75206447,
		40343341, 72357113, 23434063, 24336373, 39422399, 12866611, 11592293, 83937899,
		79746883, 37997129, 76431193, 67774627, 72107393, 31363271, 30388361, 25149569,
		54104161, 50575709, 70327973, 54960077, 92119793, 80615231, 38967139, 65609657,
		66432673, 56145097, 73864853, 70708361, 23913011, 35283481, 58352201, 57881491,
		89206109, 70619069, 96913759, 66156679, 63395257, 70022237, 93547543, 10891057,
		75492367, 86902223, 33054397, 36325571, 49119293, 64100537, 31986431, 16636237,
	]; // 0x00 .. 0x5F
	private function primeInt64(?string $prix) : string {
		//--
		$prix = (string) trim((string)$prix);
		if(((string)$prix === '') OR ((string)$prix === 'default')) {
			$prix = -1;
		} //end if
		$prix = (int) $prix;
		$px = (string) self::primesInt64[47]; // 0x2F
		if(((int)$prix >= 0) AND ((int)$prix < (int)Smart::array_size(self::primesInt64))) {
			$px = (string) self::primesInt64[(int)$prix];
		} elseif((int)$prix !== -1) {
			Smart::log_warning(__METHOD__.' # Invalid Prime Selection (Int64), using defaults: '.(string)$prix);
		} //end if
		//--
		return (string) '0x'.ltrim((string)Smart::int10_to_hex((int)$px), '0'); // preserve js compat, trim leading zeroes
		//--
	} //END FUNCTION


	private const primesBigint = [ // {{{SYNC-DHKX-HIGH-PRIMES}}}
		'h017' 		=> '0x1141317432f7b89',
		'h031' 		=> '0x6febe061005175e46c896e4079',
		'h047' 		=> '0xf3f2b0ee30050c5f6bfcb9df1b9454e77bc3503',
		'h061' 		=> '0x4771cfc3c2b8ad4561cb5437132e35e8398e8f956a2f2c94c51',
		'h097' 		=> '0x426f09b2b25aba6bbcbf9ca5edb660b91d033440916732af9ae175a84afb665a25b392361c6952119',
		'h127' 		=> '0x2c6121e6b14ecf756c083544de0e0933cac90dbeb6239905bfbec764527bbb4166ff832a2bcc3b4d6f634eddd30e40634adbbb5bfd',
		'h257' 		=> '0x279e569032f0c7256218b58ad6418aa0e9436be424ab8f1431b1f9e6b5814e0ebda0ff65ef085d7e73fee51744dec07fe08c1a1cc65855630ca983927ca277406ac42094064387d65aeaa849f9bf449e04df8cb0e99a44b004ce0efca3386f1e82c078723cd265288d9a41',
		'h232c1' 	=> '0xFFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74020BBEA63B139B22514A08798E3404DDEF9519B3CD3A431B302B0A6DF25F14374FE1356D6D51C245E485B576625E7EC6F44C42E9A63A3620FFFFFFFFFFFFFFFF',
		'h309c2' 	=> '0xFFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74020BBEA63B139B22514A08798E3404DDEF9519B3CD3A431B302B0A6DF25F14374FE1356D6D51C245E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7EDEE386BFB5A899FA5AE9F24117C4B1FE649286651ECE65381FFFFFFFFFFFFFFFF',
		'h463c5' 	=> '0xFFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74020BBEA63B139B22514A08798E3404DDEF9519B3CD3A431B302B0A6DF25F14374FE1356D6D51C245E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7EDEE386BFB5A899FA5AE9F24117C4B1FE649286651ECE45B3DC2007CB8A163BF0598DA48361C55D39A69163FA8FD24CF5F83655D23DCA3AD961C62F356208552BB9ED529077096966D670C354E4ABC9804F1746C08CA237327FFFFFFFFFFFFFFFF', // 1536-bit MODP
		'h617c14' 	=> '0xFFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74020BBEA63B139B22514A08798E3404DDEF9519B3CD3A431B302B0A6DF25F14374FE1356D6D51C245E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7EDEE386BFB5A899FA5AE9F24117C4B1FE649286651ECE45B3DC2007CB8A163BF0598DA48361C55D39A69163FA8FD24CF5F83655D23DCA3AD961C62F356208552BB9ED529077096966D670C354E4ABC9804F1746C08CA18217C32905E462E36CE3BE39E772C180E86039B2783A2EC07A28FB5C55DF06F4C52C9DE2BCBF6955817183995497CEA956AE515D2261898FA051015728E5A8AACAA68FFFFFFFFFFFFFFFF', // 2048-bit MODP (default)
		'h925c15' 	=> '0xFFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74020BBEA63B139B22514A08798E3404DDEF9519B3CD3A431B302B0A6DF25F14374FE1356D6D51C245E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7EDEE386BFB5A899FA5AE9F24117C4B1FE649286651ECE45B3DC2007CB8A163BF0598DA48361C55D39A69163FA8FD24CF5F83655D23DCA3AD961C62F356208552BB9ED529077096966D670C354E4ABC9804F1746C08CA18217C32905E462E36CE3BE39E772C180E86039B2783A2EC07A28FB5C55DF06F4C52C9DE2BCBF6955817183995497CEA956AE515D2261898FA051015728E5A8AAAC42DAD33170D04507A33A85521ABDF1CBA64ECFB850458DBEF0A8AEA71575D060C7DB3970F85A6E1E4C7ABF5AE8CDB0933D71E8C94E04A25619DCEE3D2261AD2EE6BF12FFA06D98A0864D87602733EC86A64521F2B18177B200CBBE117577A615D6C770988C0BAD946E208E24FA074E5AB3143DB5BFCE0FD108E4B82D120A93AD2CAFFFFFFFFFFFFFFFF', // 3072-bit MODP
		'h1234c16' 	=> '0xFFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74020BBEA63B139B22514A08798E3404DDEF9519B3CD3A431B302B0A6DF25F14374FE1356D6D51C245E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7EDEE386BFB5A899FA5AE9F24117C4B1FE649286651ECE45B3DC2007CB8A163BF0598DA48361C55D39A69163FA8FD24CF5F83655D23DCA3AD961C62F356208552BB9ED529077096966D670C354E4ABC9804F1746C08CA18217C32905E462E36CE3BE39E772C180E86039B2783A2EC07A28FB5C55DF06F4C52C9DE2BCBF6955817183995497CEA956AE515D2261898FA051015728E5A8AAAC42DAD33170D04507A33A85521ABDF1CBA64ECFB850458DBEF0A8AEA71575D060C7DB3970F85A6E1E4C7ABF5AE8CDB0933D71E8C94E04A25619DCEE3D2261AD2EE6BF12FFA06D98A0864D87602733EC86A64521F2B18177B200CBBE117577A615D6C770988C0BAD946E208E24FA074E5AB3143DB5BFCE0FD108E4B82D120A92108011A723C12A787E6D788719A10BDBA5B2699C327186AF4E23C1A946834B6150BDA2583E9CA2AD44CE8DBBBC2DB04DE8EF92E8EFC141FBECAA6287C59474E6BC05D99B2964FA090C3A2233BA186515BE7ED1F612970CEE2D7AFB81BDD762170481CD0069127D5B05AA993B4EA988D8FDDC186FFB7DC90A6C08F4DF435C934063199FFFFFFFFFFFFFFFF', // 4096-bit MODP
		'h1850c17' 	=> '0xFFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74020BBEA63B139B22514A08798E3404DDEF9519B3CD3A431B302B0A6DF25F14374FE1356D6D51C245E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7EDEE386BFB5A899FA5AE9F24117C4B1FE649286651ECE45B3DC2007CB8A163BF0598DA48361C55D39A69163FA8FD24CF5F83655D23DCA3AD961C62F356208552BB9ED529077096966D670C354E4ABC9804F1746C08CA18217C32905E462E36CE3BE39E772C180E86039B2783A2EC07A28FB5C55DF06F4C52C9DE2BCBF6955817183995497CEA956AE515D2261898FA051015728E5A8AAAC42DAD33170D04507A33A85521ABDF1CBA64ECFB850458DBEF0A8AEA71575D060C7DB3970F85A6E1E4C7ABF5AE8CDB0933D71E8C94E04A25619DCEE3D2261AD2EE6BF12FFA06D98A0864D87602733EC86A64521F2B18177B200CBBE117577A615D6C770988C0BAD946E208E24FA074E5AB3143DB5BFCE0FD108E4B82D120A92108011A723C12A787E6D788719A10BDBA5B2699C327186AF4E23C1A946834B6150BDA2583E9CA2AD44CE8DBBBC2DB04DE8EF92E8EFC141FBECAA6287C59474E6BC05D99B2964FA090C3A2233BA186515BE7ED1F612970CEE2D7AFB81BDD762170481CD0069127D5B05AA993B4EA988D8FDDC186FFB7DC90A6C08F4DF435C93402849236C3FAB4D27C7026C1D4DCB2602646DEC9751E763DBA37BDF8FF9406AD9E530EE5DB382F413001AEB06A53ED9027D831179727B0865A8918DA3EDBEBCF9B14ED44CE6CBACED4BB1BDB7F1447E6CC254B332051512BD7AF426FB8F401378CD2BF5983CA01C64B92ECF032EA15D1721D03F482D7CE6E74FEF6D55E702F46980C82B5A84031900B1C9E59E7C97FBEC7E8F323A97A7E36CC88BE0F1D45B7FF585AC54BD407B22B4154AACC8F6D7EBF48E1D814CC5ED20F8037E0A79715EEF29BE32806A1D58BB7C5DA76F550AA3D8A1FBFF0EB19CCB1A313D55CDA56C9EC2EF29632387FE8D76E3C0468043E8F663F4860EE12BF2D5B0B7474D6E694F91E6DCC4024FFFFFFFFFFFFFFFF', // 6144-bit MODP
		'h2467c18' 	=> '0xFFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74020BBEA63B139B22514A08798E3404DDEF9519B3CD3A431B302B0A6DF25F14374FE1356D6D51C245E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7EDEE386BFB5A899FA5AE9F24117C4B1FE649286651ECE45B3DC2007CB8A163BF0598DA48361C55D39A69163FA8FD24CF5F83655D23DCA3AD961C62F356208552BB9ED529077096966D670C354E4ABC9804F1746C08CA18217C32905E462E36CE3BE39E772C180E86039B2783A2EC07A28FB5C55DF06F4C52C9DE2BCBF6955817183995497CEA956AE515D2261898FA051015728E5A8AAAC42DAD33170D04507A33A85521ABDF1CBA64ECFB850458DBEF0A8AEA71575D060C7DB3970F85A6E1E4C7ABF5AE8CDB0933D71E8C94E04A25619DCEE3D2261AD2EE6BF12FFA06D98A0864D87602733EC86A64521F2B18177B200CBBE117577A615D6C770988C0BAD946E208E24FA074E5AB3143DB5BFCE0FD108E4B82D120A92108011A723C12A787E6D788719A10BDBA5B2699C327186AF4E23C1A946834B6150BDA2583E9CA2AD44CE8DBBBC2DB04DE8EF92E8EFC141FBECAA6287C59474E6BC05D99B2964FA090C3A2233BA186515BE7ED1F612970CEE2D7AFB81BDD762170481CD0069127D5B05AA993B4EA988D8FDDC186FFB7DC90A6C08F4DF435C93402849236C3FAB4D27C7026C1D4DCB2602646DEC9751E763DBA37BDF8FF9406AD9E530EE5DB382F413001AEB06A53ED9027D831179727B0865A8918DA3EDBEBCF9B14ED44CE6CBACED4BB1BDB7F1447E6CC254B332051512BD7AF426FB8F401378CD2BF5983CA01C64B92ECF032EA15D1721D03F482D7CE6E74FEF6D55E702F46980C82B5A84031900B1C9E59E7C97FBEC7E8F323A97A7E36CC88BE0F1D45B7FF585AC54BD407B22B4154AACC8F6D7EBF48E1D814CC5ED20F8037E0A79715EEF29BE32806A1D58BB7C5DA76F550AA3D8A1FBFF0EB19CCB1A313D55CDA56C9EC2EF29632387FE8D76E3C0468043E8F663F4860EE12BF2D5B0B7474D6E694F91E6DBE115974A3926F12FEE5E438777CB6A932DF8CD8BEC4D073B931BA3BC832B68D9DD300741FA7BF8AFC47ED2576F6936BA424663AAB639C5AE4F5683423B4742BF1C978238F16CBE39D652DE3FDB8BEFC848AD922222E04A4037C0713EB57A81A23F0C73473FC646CEA306B4BCBC8862F8385DDFA9D4B7FA2C087E879683303ED5BDD3A062B3CF5B3A278A66D2A13F83F44F82DDF310EE074AB6A364597E899A0255DC164F31CC50846851DF9AB48195DED7EA1B1D510BD7EE74D73FAF36BC31ECFA268359046F4EB879F924009438B481C6CD7889A002ED5EE382BC9190DA6FC026E479558E4475677E9AA9E3050E2765694DFC81F56E880B96E7160C980DD98EDD3DFFFFFFFFFFFFFFFFF', // 8192-bit MODP
	];
	private function primeBigint(?string $prix) : string {
		//--
		$px = null;
		$prix = trim((string)$prix);
		if((string)$prix == '') {
			$prix = 'default';
		} //end if
		switch((string)$prix) {
			case 'h017':
			case 'h031':
			case 'h047':
			case 'h061':
			case 'h097':
			case 'h127':
			case 'h257':
			case 'h232c1':
			case 'h309c2':
			case 'h463c5':
			case 'h617c14':
			case 'h925c15':
			case 'h1234c16':
			case 'h1850c17':
			case 'h2467c18':
				$px = (string) self::primesBigint[(string)$prix];
				break;
			default:
				if((string)$prix !== 'default') {
					Smart::log_warning(__METHOD__.' # Invalid Prime Selection (Bigint), using defaults: '.(string)$prix);
				} //end if
				$px = (string) self::primesBigint['h061'];
		} //end switch
		//--
		return (string) strtolower((string)$px);
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
 * Class:  Smart Crypto Ciphers :: Threefish-1024 CBC
 * (c) 2023 unix-world.org as part of PHP Smart Framework github.com/unix-world/Smart.Framework
 * Provides a built-in based feature to handle the Threefish 1024-bit CBC encryption / decryption.
 * Featuring the core Skein algorithm, is considered being a Post Quantum-Resistant Cryptographic Algorithm, having a blocksize of 128 bits, very strong, and there are very few attacks on it, and those onnly on the lower variants (Threefish-256 and Threefish-512) ; Threefish-1024 have the highest block size among known algorithms ... being almost infeasible.
 * The last version of Threefish (Threefish-1024) is quite strong. Even in weak models the best attacks hardly penetrate half of the cipher.
 *
 * Threefish is a block cipher developed by Bruce Schneier and Counterpane Labs, published in 1998.
 * Being ultra secure, it remains and will remain unbroken long time since now ... 256 bit computing is a dream yet, ... not even 128-bit computing is not yet on the market ...
 * It is more effective than AES. Threefish-1024 has a 2.9 security index, while AES-256 has only a 1.7 security index !
 *
 * This class is standard (blocksize 128 bit = 16 bytes) ; only supports CBC mode ; have 16 rounds ; only supports key size 256 bit (32 bytes), lower keys not supported for security reasons ...
 *
 * @usage       dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 * @hints       Threefish-1024 is a 1024-bit (128 bytes) block cipher, with a Key / Iv size of 128 chars length (128 bytes = 1024 bits). The CBC mode requires a initialization vector (iv). Threefish also requires a Tweak.
 * @hints       Threefish also supports 512-bit (64 bytes) and 256 bit (32 bytes) variants, but they are not implemented in this class because are not so safe and they have a slighly different, weaker algorithm
 * @hints       Threefish-256 and Threefish-512 (not implemented by this class) are up to 72 rounds and are vulnerable to some rebound attacks
 * @hints       Threefish-1024 (80 rounds) implemented in this class have no vulnerability up to present. Requires 256-bit computing to break it ... :-)) ...
 *
 * @access 		private
 * @internal
 *
 * @depends     PHP GMP extension ; classes: Smart
 * @version     v.20231203
 *
 */
final class SmartCryptoCiphersThreefishCBC {

	//------- copyright notice (please keep intact, exactly in this place ...)

	// !!!!!!!! It is mandatory to keep this copyright notice intact, with no modifications, as part of the License !!!!!!!!
	// !!!!!!! Please use this code responsible ... Threefish-1024 is an extremely strong encryption algorithm, designed to resist also on 128-bit computing !!!!!!!

	// PHP port of the Threefish 1024 Cipher (CBC mode only)
	// Threefish is a symmetric-key tweakable block cipher designed as part of the Skein hash function
	// It does not use S-boxes or other table lookups in order to avoid cache timing attacks
	// Its nonlinearity comes from alternating additions with exclusive ORs.
	// In that respect, it is similar to Salsa20, TEA, and the SHA-3 candidates CubeHash and BLAKE

	// License: BSD
	// code was ported to PHP on 2023-12-02 by unixman: Radu Ovidiu Ilies <iradu@unix-world.org> (c) 2023 unix-world.org
	// code was released as part of PHP Smart Framework github.com/unix-world/Smart.Framework

	// inspired by github.com/schultz-is/go-threefish (c) 2020 Matt Schultz <matt@schultz.is>
	// [1] http://www.skein-hash.info/sites/default/files/skein1.3.pdf
	// [2] http://www.skein-hash.info/sites/default/files/NIST_CD_102610.zip
	// Threefish and the Skein hash function were designed by:
	// (c) Bruce Schneier, Niels Ferguson, Stefan Lucks, Doug Whiting, Mihir Bellare, Tadayoshi Kohno, Jon Callas, and Jesse Walker

	// it operates only on uint64 which overflows the max int64 supported by PHP thus it requires PHP GMP extension !
	// supports only 64-bit machines ; works on words of 64 bits (unsigned Little endian integers)
	// works and tested on little endian machines
	// should work also (but not tested yet) on big endian machines ... ; export is made via GMP uwing the GMP_NATIVE_ENDIAN flag

	//------- # end copyright

	// ->

	private const BLOCK_SIZE 	= 128; 					// the Threefish 1024 block size
	private const TWEAK_SIZE 	= 16; 					// Algorithm Tweak Size ; Threefish requires also a tweak near the key and iv
	private const C240 			= '0x1bd11bdaa9fc1a22'; // Constant used to ensure that key extension cannot result in all zeroes
	private const UINT64_MAX 	= '0xffffffffffffffff'; // max 64-bit uint (uint64), as hex (it overflows int64 (aka int from PHP)
	private const ROUNDS 		= 80;
	private const MAGICS1 = [
		[ 24, 13,  8, 47,  8, 17, 22, 37 ],
		[ 38, 19, 10, 55, 49, 18, 23, 52 ],
		[ 33,  4, 51, 13, 34, 41, 59, 17 ],
		[  5, 20, 48, 41, 47, 28, 16, 25 ],
	];
	private const MAGICS2 = [
		[ 41,  9, 37, 31, 12, 47, 44, 30 ],
		[ 16, 34, 56, 51,  4, 53, 42, 41 ],
		[ 31, 44, 47, 46, 19, 42, 44, 25 ],
		[  9, 48, 35, 52, 23, 31, 37, 20 ],
	];

	private $key = '';
	private $iv = '';
	private $tweak = '';

	private $expandedTweak = [];
	private $expandedKey = [];


	//==============================================================
	/**
	 * Default Constructor.
	 */
	public function __construct(string $key, string $iv, string $tweak, string $modever='v3') {
		//--
		if(!function_exists('gmp_strval')) {
			Smart::raise_error(__METHOD__.' # PHP GMP Extension is required');
			return;
		} //end if
		//--
		$this->key 		= (string) $key;
		$this->iv 		= (string) $iv;
		$this->tweak 	= (string) $tweak;
		//--
		if((int)strlen((string)$this->key) != (int)self::BLOCK_SIZE) {
			Smart::raise_error(__METHOD__.' # Invalid Key Size, must be 128 bytes (1024 bit)');
			return; // ERR: key size
		} //end if
		if((int)strlen((string)$this->iv) != (int)self::BLOCK_SIZE) {
			Smart::raise_error(__METHOD__.' # Invalid Iv Size, must be 128 bytes (1024 bit)');
			return; // ERR: iv size
		} //end if
		if((int)strlen((string)$this->tweak) != (int)self::TWEAK_SIZE) {
			Smart::raise_error(__METHOD__.' # Invalid Tweak Size, must be 16 bytes (128 bit)');
			return; // ERR: tweak size
		} //end if
		//--
		$this->expandTweak128();
		$this->expandKey1024();
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Encrypts a string in CBC Mode using Threefish 1024 cipher algo
	 *
	 * $b64DataOrPak needs to be base64 encoded or similar because will be padded with additional spaces (CBC) until the length of the block size
	 * The idea of encoding B64 (or similar) the data is because this algorithm does not support multi-byte strings (ex: UTF-8) ; also some binary data may be broken because of internal null trimming
	 *
	 * @param string $b64DataOrPak
	 * @return string $ciphertext
	 */
	public function encrypt(?string $b64DataOrPak) : string { // CBC mode: encrypts all blocks
		//-- {{{SYNC-CRYPTO-DATA-ENCAPSULATE-B64}}}
		// expects: B64 data or B64 / HEX pak ; req. for safe padding !
		// why B64 or package data ? just because safe padding requires a capsule like B64/HEX
		// why not B64 encode here ? just because it can be a package as B64#signature and don't want to manage signatures here ...
		//--
		if((int)strlen((string)trim((string)$this->key)) != 128) {
			Smart::log_warning(__METHOD__.' # Crypto Key is Empty or Invalid');
			return '';
		} //end if
		if((int)Smart::array_size($this->expandedKey) != 21) {
			Smart::log_warning(__METHOD__.' # Crypto Key is Not Expanded');
			return '';
		} //end if
		if((int)strlen((string)trim((string)$this->iv)) != 128) {
			Smart::log_warning(__METHOD__.' # Crypto iV is Empty or Invalid');
			return '';
		} //end if
		if((int)strlen((string)trim((string)$this->tweak)) != 16) {
			Smart::log_warning(__METHOD__.' # Crypto Tweak is Empty or Invalid');
			return '';
		} //end if
		if((int)Smart::array_size($this->expandedTweak) != 3) {
			Smart::log_warning(__METHOD__.' # Crypto Tweak is Not Expanded');
			return '';
		} //end if
		//--
		if((string)$b64DataOrPak === '') {
			return '';
		} //end if
		//--
		$length = (int) strlen((string)$b64DataOrPak);
		$padlen = (int) (((int)self::BLOCK_SIZE - (int)((int)$length % (int)self::BLOCK_SIZE)) % (int)self::BLOCK_SIZE); // threefish blocksize is 128 ; {{{SYNC-ENCRYPTY-B64-PADDING}}}
		$b64DataOrPak = (string) str_pad((string)$b64DataOrPak, (int)((int)$length + (int)$padlen), ' ', STR_PAD_RIGHT); // fix by unixman ; pad with spaces, it should be B64 data
		//--
		$ciphertext = '';
		//-- CBC
		$xor = (string) $this->iv;
		for($i=0; $i<(int)$length; $i+=self::BLOCK_SIZE) {
			$block = (string) substr((string)$b64DataOrPak, (int)$i, (int)self::BLOCK_SIZE);
			$block = (string) $this->encryptBlock((string)((string)$block ^ (string)$xor));
			$xor = (string) $block;
			$ciphertext .= (string) $block;
		} //end for
		//--
		return (string) $ciphertext;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Decrypts a string in CBC Mode using Threefish 1024 cipher algo
	 *
	 * If strlen($ciphertext) is not a multiple of the block size, null bytes will be added to the end of the string until it is.
	 * If data was passed B64 or similar to the encrypt method above, after using this method data needs to be base64 decoded or similar
	 *
	 * @param string $ciphertext
	 * @return string $b64DataOrPak
	 */
	public function decrypt(?string $ciphertext) : string { // CBC mode: decrypts all blocks
		//--
		if((int)strlen((string)trim((string)$this->key)) != 128) {
			Smart::log_warning(__METHOD__.' # Crypto Key is Empty or Invalid');
			return '';
		} //end if
		if((int)Smart::array_size($this->expandedKey) != 21) {
			Smart::log_warning(__METHOD__.' # Crypto Key is Not Expanded');
			return '';
		} //end if
		if((int)strlen((string)trim((string)$this->iv)) != 128) {
			Smart::log_warning(__METHOD__.' # Crypto iV is Empty or Invalid');
			return '';
		} //end if
		if((int)strlen((string)trim((string)$this->tweak)) != 16) {
			Smart::log_warning(__METHOD__.' # Crypto Tweak is Empty or Invalid');
			return '';
		} //end if
		if((int)Smart::array_size($this->expandedTweak) != 3) {
			Smart::log_warning(__METHOD__.' # Crypto Tweak is Not Expanded');
			return '';
		} //end if
		//--
		if((string)$ciphertext === '') {
			return '';
		} //end if
		//-- {{{FIX-GOLANG-CIPHER-1ST-NULL-BLOCK-HEADER}}} ; pad with chr(0) since that's what m-crypt generic way: "The data is padded with "\0" to make sure the length of the data is n * blocksize."
		$length = (int) strlen((string)$ciphertext);
		$padlen = (int) (((int)self::BLOCK_SIZE - (int)((int)$length % (int)self::BLOCK_SIZE)) % (int)self::BLOCK_SIZE);
		$ciphertext = (string) str_pad((string)$ciphertext, (int)((int)$length + (int)$padlen), (string)chr(0), STR_PAD_RIGHT);
		//--
		$b64DataOrPak = '';
		//-- CBC
		$xor = (string) $this->iv;
		for($i=0; $i<(int)$length; $i+=(int)self::BLOCK_SIZE) {
			$block = (string) substr((string)$ciphertext, (int)$i, (int)self::BLOCK_SIZE);
			$b64DataOrPak .= (string) ((string)$this->decryptBlock((string)$block) ^ (string)$xor);
			$xor = (string) $block;
		} //end for
		//--
	//	$b64DataOrPak = (string) rtrim((string)$b64DataOrPak, ' '); // trim B64 data on right
		$b64DataOrPak = (string) trim((string)$b64DataOrPak, ' '); // trim B64 data on right + left ... why not :-) it is B64 data ...
		//--
		return (string) $b64DataOrPak;
		//--
	} //END FUNCTION
	//==============================================================


	//======== [ PRIVATES ]


	private function expandTweak128() : void { // expand the tweak
		//--
		$this->expandedTweak = []; // reset
		//--
		$src = (string) $this->tweak;
		//--
		if(strlen((string)$src) != (int)self::TWEAK_SIZE) {
			Smart::log_warning(__METHOD__.' # Tweak length must be 16 bytes (128 bit)');
			return; // ERR: Tweak Size Error
		} //end if
		//--
		$dst[0] = (array) $this->loadWord((string)substr((string)$src, 0, 8));
		$dst[1] = (array) $this->loadWord((string)substr((string)$src, 8, 8));
	//	$dst[2] = (array) ($dst[0] ^ $dst[1]); // in PHP this is not supported directly, handle as below ...
	//	$dst[2] = (array) $this->loadWord((string)$this->storeWord((array)$dst[0]) ^ (string)$this->storeWord((array)$dst[1])); // unsafe, direct ^ XOR works well just for integers (on PHP, int64 NOT on uint64) !!
		$dst[2] = (array) [ (string)$this->uint64XOr((string)$this->storeStr((array)$dst[0]), (string)$this->storeStr((array)$dst[1])) ];
		//--
		$this->expandedTweak = (array) $dst;
		//--
	} //END FUNCTION


	private function expandKey1024() : void { // expand the key
		//--
		$this->expandedKey = []; // reset
		//--
		if((int)strlen((string)$this->key) != (int)self::BLOCK_SIZE) {
			Smart::log_warning(__METHOD__.' # Key length must be equal with the Block Size, 128 bytes (1024 bit)');
			return;
		} //end if
		//--
		$t = (array) $this->expandedTweak;
		if((int)Smart::array_size($t) != 3) {
			Smart::log_warning(__METHOD__.' # Tweak must be expanded first');
			return; // invalid tweak
		} //end if
		//--
		$numWords1024 = (int) ceil((int)self::BLOCK_SIZE / 8);
		$k = (array) str_split((string)str_repeat('0', (int)((int)$numWords1024 + 1))); // init array of specific size
		$k[(int)$numWords1024] = (string) gmp_strval((string)self::C240, 10);
		for($i=0; $i<$numWords1024; $i++) {
			$k[$i] = (array)  $this->loadWord((string)substr((string)$this->key, (int)((int)$i * 8), 8));
			$k[$i] = (string) $this->storeStr((array)$k[$i]);
			$k[(int)$numWords1024] ^= (int) $k[$i];
		} //end for
		//--
		$ks = [];
		//--
		$maxS = (int) ceil((int)self::ROUNDS / 4);
		for($s=0; $s<=$maxS; $s++) {
			for($i=0; $i<$numWords1024; $i++) {
				$ks[$s][$i] = $k[($s+$i)%($numWords1024+1)];
				switch($i) {
					case $numWords1024 - 3:
						$ks[$s][$i] = (string) $this->uint64Add((string)$ks[$s][$i], (string)$this->storeStr($t[$s%3]));
						break;
					case $numWords1024 - 2:
						$ks[$s][$i] = (string) $this->uint64Add((string)$ks[$s][$i], (string)$this->storeStr($t[($s+1)%3]));
						break;
					case $numWords1024 - 1:
						$ks[$s][$i] = (string) $this->uint64Add((string)$ks[$s][$i], (string)$s);
						break;
				} //end switch
			} //end for
		} //end for
		//--
		$this->expandedKey = (array) $ks;
		//--
	} //END FUNCTION


	private function encryptBlock(string $src) : string { // encrypts a block
		//--
		if(strlen((string)$src) != (int)self::BLOCK_SIZE) {
			Smart::raise_error(__METHOD__.' # Input data must match the Block Size, 128 bytes (1024 bit)');
			return ''; // ERR: invalid data, must match the blocksize
		} //end if
		//--
		$in = [];
		//--
		$in[0]  = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src,   0, 8)));
		$in[1]  = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src,   8, 8)));
		$in[2]  = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src,  16, 8)));
		$in[3]  = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src,  24, 8)));
		$in[4]  = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src,  32, 8)));
		$in[5]  = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src,  40, 8)));
		$in[6]  = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src,  48, 8)));
		$in[7]  = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src,  56, 8)));
		$in[8]  = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src,  64, 8)));
		$in[9]  = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src,  72, 8)));
		$in[10] = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src,  80, 8)));
		$in[11] = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src,  88, 8)));
		$in[12] = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src,  96, 8)));
		$in[13] = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src, 104, 8)));
		$in[14] = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src, 112, 8)));
		$in[15] = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src, 120, 8)));
		//-- Perform encryption rounds
		for($d=0; $d<self::ROUNDS; $d+=8) {
			//-- Add round key
			$in[0]  = (string) $this->uint64Add((string)$in[0],  (string)$this->expandedKey[$d/4][0]);
			$in[1]  = (string) $this->uint64Add((string)$in[1],  (string)$this->expandedKey[$d/4][1]);
			$in[2]  = (string) $this->uint64Add((string)$in[2],  (string)$this->expandedKey[$d/4][2]);
			$in[3]  = (string) $this->uint64Add((string)$in[3],  (string)$this->expandedKey[$d/4][3]);
			$in[4]  = (string) $this->uint64Add((string)$in[4],  (string)$this->expandedKey[$d/4][4]);
			$in[5]  = (string) $this->uint64Add((string)$in[5],  (string)$this->expandedKey[$d/4][5]);
			$in[6]  = (string) $this->uint64Add((string)$in[6],  (string)$this->expandedKey[$d/4][6]);
			$in[7]  = (string) $this->uint64Add((string)$in[7],  (string)$this->expandedKey[$d/4][7]);
			$in[8]  = (string) $this->uint64Add((string)$in[8],  (string)$this->expandedKey[$d/4][8]);
			$in[9]  = (string) $this->uint64Add((string)$in[9],  (string)$this->expandedKey[$d/4][9]);
			$in[10] = (string) $this->uint64Add((string)$in[10], (string)$this->expandedKey[$d/4][10]);
			$in[11] = (string) $this->uint64Add((string)$in[11], (string)$this->expandedKey[$d/4][11]);
			$in[12] = (string) $this->uint64Add((string)$in[12], (string)$this->expandedKey[$d/4][12]);
			$in[13] = (string) $this->uint64Add((string)$in[13], (string)$this->expandedKey[$d/4][13]);
			$in[14] = (string) $this->uint64Add((string)$in[14], (string)$this->expandedKey[$d/4][14]);
			$in[15] = (string) $this->uint64Add((string)$in[15], (string)$this->expandedKey[$d/4][15]);
			//-- Four rounds of mix and permute
			for($m=0; $m<(int)Smart::array_size((array)self::MAGICS1); $m++) {
				//--
				$in[0]  = (string) $this->uint64Add((string)$in[0], (string)$in[1]);
				$in[1]  = (string) $this->blockSegmentRotate((string)$in[1], (string)$in[0],   (int)self::MAGICS1[$m][0]);
				$in[2]  = (string) $this->uint64Add((string)$in[2], (string)$in[3]);
				$in[3]  = (string) $this->blockSegmentRotate((string)$in[3], (string)$in[2],   (int)self::MAGICS1[$m][1]);
				$in[4]  = (string) $this->uint64Add((string)$in[4], (string)$in[5]);
				$in[5]  = (string) $this->blockSegmentRotate((string)$in[5], (string)$in[4],   (int)self::MAGICS1[$m][2]);
				$in[6]  = (string) $this->uint64Add((string)$in[6], (string)$in[7]);
				$in[7]  = (string) $this->blockSegmentRotate((string)$in[7], (string)$in[6],   (int)self::MAGICS1[$m][3]);
				$in[8]  = (string) $this->uint64Add((string)$in[8], (string)$in[9]);
				$in[9]  = (string) $this->blockSegmentRotate((string)$in[9], (string)$in[8],   (int)self::MAGICS1[$m][4]);
				$in[10] = (string) $this->uint64Add((string)$in[10], (string)$in[11]);
				$in[11] = (string) $this->blockSegmentRotate((string)$in[11], (string)$in[10], (int)self::MAGICS1[$m][5]);
				$in[12] = (string) $this->uint64Add((string)$in[12], (string)$in[13]);
				$in[13] = (string) $this->blockSegmentRotate((string)$in[13], (string)$in[12], (int)self::MAGICS1[$m][6]);
				$in[14] = (string) $this->uint64Add((string)$in[14], (string)$in[15]);
				$in[15] = (string) $this->blockSegmentRotate((string)$in[15], (string)$in[14], (int)self::MAGICS1[$m][7]);
				$in 	= (array)  $this->blockSegmentPermutate((array)$in, (array)$in);
				//--
			} //end for
			//-- Add round key
			$in[0]  = (string) $this->uint64Add((string)$in[0],  (string)$this->expandedKey[($d/4)+1][0]);
			$in[1]  = (string) $this->uint64Add((string)$in[1],  (string)$this->expandedKey[($d/4)+1][1]);
			$in[2]  = (string) $this->uint64Add((string)$in[2],  (string)$this->expandedKey[($d/4)+1][2]);
			$in[3]  = (string) $this->uint64Add((string)$in[3],  (string)$this->expandedKey[($d/4)+1][3]);
			$in[4]  = (string) $this->uint64Add((string)$in[4],  (string)$this->expandedKey[($d/4)+1][4]);
			$in[5]  = (string) $this->uint64Add((string)$in[5],  (string)$this->expandedKey[($d/4)+1][5]);
			$in[6]  = (string) $this->uint64Add((string)$in[6],  (string)$this->expandedKey[($d/4)+1][6]);
			$in[7]  = (string) $this->uint64Add((string)$in[7],  (string)$this->expandedKey[($d/4)+1][7]);
			$in[8]  = (string) $this->uint64Add((string)$in[8],  (string)$this->expandedKey[($d/4)+1][8]);
			$in[9]  = (string) $this->uint64Add((string)$in[9],  (string)$this->expandedKey[($d/4)+1][9]);
			$in[10] = (string) $this->uint64Add((string)$in[10], (string)$this->expandedKey[($d/4)+1][10]);
			$in[11] = (string) $this->uint64Add((string)$in[11], (string)$this->expandedKey[($d/4)+1][11]);
			$in[12] = (string) $this->uint64Add((string)$in[12], (string)$this->expandedKey[($d/4)+1][12]);
			$in[13] = (string) $this->uint64Add((string)$in[13], (string)$this->expandedKey[($d/4)+1][13]);
			$in[14] = (string) $this->uint64Add((string)$in[14], (string)$this->expandedKey[($d/4)+1][14]);
			$in[15] = (string) $this->uint64Add((string)$in[15], (string)$this->expandedKey[($d/4)+1][15]);
			//-- Four rounds of mix and permute
			for($m=0; $m<(int)Smart::array_size((array)self::MAGICS2); $m++) {
				//--
				$in[0]  = (string) $this->uint64Add((string)$in[0], (string)$in[1]);
				$in[1]  = (string) $this->blockSegmentRotate((string)$in[1], (string)$in[0],   (int)self::MAGICS2[$m][0]);
				$in[2]  = (string) $this->uint64Add((string)$in[2], (string)$in[3]);
				$in[3]  = (string) $this->blockSegmentRotate((string)$in[3], (string)$in[2],   (int)self::MAGICS2[$m][1]);
				$in[4]  = (string) $this->uint64Add((string)$in[4], (string)$in[5]);
				$in[5]  = (string) $this->blockSegmentRotate((string)$in[5], (string)$in[4],   (int)self::MAGICS2[$m][2]);
				$in[6]  = (string) $this->uint64Add((string)$in[6], (string)$in[7]);
				$in[7]  = (string) $this->blockSegmentRotate((string)$in[7], (string)$in[6],   (int)self::MAGICS2[$m][3]);
				$in[8]  = (string) $this->uint64Add((string)$in[8], (string)$in[9]);
				$in[9]  = (string) $this->blockSegmentRotate((string)$in[9], (string)$in[8],   (int)self::MAGICS2[$m][4]);
				$in[10] = (string) $this->uint64Add((string)$in[10], (string)$in[11]);
				$in[11] = (string) $this->blockSegmentRotate((string)$in[11], (string)$in[10], (int)self::MAGICS2[$m][5]);
				$in[12] = (string) $this->uint64Add((string)$in[12], (string)$in[13]);
				$in[13] = (string) $this->blockSegmentRotate((string)$in[13], (string)$in[12], (int)self::MAGICS2[$m][6]);
				$in[14] = (string) $this->uint64Add((string)$in[14], (string)$in[15]);
				$in[15] = (string) $this->blockSegmentRotate((string)$in[15], (string)$in[14], (int)self::MAGICS2[$m][7]);
				$in 	= (array)  $this->blockSegmentPermutate((array)$in, (array)$in);
				//--
			} //end for
			//--
		} //end for
		//-- Add the final round key
		$in[0]  = (string) $this->uint64Add((string)$in[0],  (string)$this->expandedKey[self::ROUNDS/4][0]);
		$in[1]  = (string) $this->uint64Add((string)$in[1],  (string)$this->expandedKey[self::ROUNDS/4][1]);
		$in[2]  = (string) $this->uint64Add((string)$in[2],  (string)$this->expandedKey[self::ROUNDS/4][2]);
		$in[3]  = (string) $this->uint64Add((string)$in[3],  (string)$this->expandedKey[self::ROUNDS/4][3]);
		$in[4]  = (string) $this->uint64Add((string)$in[4],  (string)$this->expandedKey[self::ROUNDS/4][4]);
		$in[5]  = (string) $this->uint64Add((string)$in[5],  (string)$this->expandedKey[self::ROUNDS/4][5]);
		$in[6]  = (string) $this->uint64Add((string)$in[6],  (string)$this->expandedKey[self::ROUNDS/4][6]);
		$in[7]  = (string) $this->uint64Add((string)$in[7],  (string)$this->expandedKey[self::ROUNDS/4][7]);
		$in[8]  = (string) $this->uint64Add((string)$in[8],  (string)$this->expandedKey[self::ROUNDS/4][8]);
		$in[9]  = (string) $this->uint64Add((string)$in[9],  (string)$this->expandedKey[self::ROUNDS/4][9]);
		$in[10] = (string) $this->uint64Add((string)$in[10], (string)$this->expandedKey[self::ROUNDS/4][10]);
		$in[11] = (string) $this->uint64Add((string)$in[11], (string)$this->expandedKey[self::ROUNDS/4][11]);
		$in[12] = (string) $this->uint64Add((string)$in[12], (string)$this->expandedKey[self::ROUNDS/4][12]);
		$in[13] = (string) $this->uint64Add((string)$in[13], (string)$this->expandedKey[self::ROUNDS/4][13]);
		$in[14] = (string) $this->uint64Add((string)$in[14], (string)$this->expandedKey[self::ROUNDS/4][14]);
		$in[15] = (string) $this->uint64Add((string)$in[15], (string)$this->expandedKey[self::ROUNDS/4][15]);
		//--
		return (string) $this->storeWords((array)$in);
		//--
	} //END FUNCTION


	private function decryptBlock(string $src) : string { // decrypts a block
		//--
		if(strlen((string)$src) != (int)self::BLOCK_SIZE) {
			Smart::raise_error(__METHOD__.' # Input data must match the Block Size, 128 bytes (1024 bit)');
			return ''; // ERR: invalid data, must match the blocksize
		} //end if
		//--
		$ct = [];
		//--
		$ct[0]  = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src,   0, 8)));
		$ct[1]  = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src,   8, 8)));
		$ct[2]  = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src,  16, 8)));
		$ct[3]  = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src,  24, 8)));
		$ct[4]  = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src,  32, 8)));
		$ct[5]  = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src,  40, 8)));
		$ct[6]  = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src,  48, 8)));
		$ct[7]  = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src,  56, 8)));
		$ct[8]  = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src,  64, 8)));
		$ct[9]  = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src,  72, 8)));
		$ct[10] = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src,  80, 8)));
		$ct[11] = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src,  88, 8)));
		$ct[12] = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src,  96, 8)));
		$ct[13] = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src, 104, 8)));
		$ct[14] = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src, 112, 8)));
		$ct[15] = (string) $this->storeStr((array)$this->loadWord((string)substr((string)$src, 120, 8)));
		//-- Subtract the final round key
		$ct[0]  = (string) $this->uint64Sub((string)$ct[0],  (string)$this->expandedKey[self::ROUNDS/4][0]);
		$ct[1]  = (string) $this->uint64Sub((string)$ct[1],  (string)$this->expandedKey[self::ROUNDS/4][1]);
		$ct[2]  = (string) $this->uint64Sub((string)$ct[2],  (string)$this->expandedKey[self::ROUNDS/4][2]);
		$ct[3]  = (string) $this->uint64Sub((string)$ct[3],  (string)$this->expandedKey[self::ROUNDS/4][3]);
		$ct[4]  = (string) $this->uint64Sub((string)$ct[4],  (string)$this->expandedKey[self::ROUNDS/4][4]);
		$ct[5]  = (string) $this->uint64Sub((string)$ct[5],  (string)$this->expandedKey[self::ROUNDS/4][5]);
		$ct[6]  = (string) $this->uint64Sub((string)$ct[6],  (string)$this->expandedKey[self::ROUNDS/4][6]);
		$ct[7]  = (string) $this->uint64Sub((string)$ct[7],  (string)$this->expandedKey[self::ROUNDS/4][7]);
		$ct[8]  = (string) $this->uint64Sub((string)$ct[8],  (string)$this->expandedKey[self::ROUNDS/4][8]);
		$ct[9]  = (string) $this->uint64Sub((string)$ct[9],  (string)$this->expandedKey[self::ROUNDS/4][9]);
		$ct[10] = (string) $this->uint64Sub((string)$ct[10], (string)$this->expandedKey[self::ROUNDS/4][10]);
		$ct[11] = (string) $this->uint64Sub((string)$ct[11], (string)$this->expandedKey[self::ROUNDS/4][11]);
		$ct[12] = (string) $this->uint64Sub((string)$ct[12], (string)$this->expandedKey[self::ROUNDS/4][12]);
		$ct[13] = (string) $this->uint64Sub((string)$ct[13], (string)$this->expandedKey[self::ROUNDS/4][13]);
		$ct[14] = (string) $this->uint64Sub((string)$ct[14], (string)$this->expandedKey[self::ROUNDS/4][14]);
		$ct[15] = (string) $this->uint64Sub((string)$ct[15], (string)$this->expandedKey[self::ROUNDS/4][15]);
		//-- Perform decryption rounds
		for($d=(self::ROUNDS-1); $d>=0; $d-=8) {
			//-- Four rounds of permute and unmix
			for($m=(int)((int)Smart::array_size((array)self::MAGICS2)-1); $m>=0; $m--) {
				//--
				$ct 	= (array)  $this->blockSegmentRevPermutate((array)$ct, (array)$ct);
				$ct[15] = (string) $this->blockSegmentRevRotate((string)$ct[15], (string)$ct[14], (int)self::MAGICS2[$m][7]);
				$ct[14] = (string) $this->uint64Sub((string)$ct[14], (string)$ct[15]);
				$ct[13] = (string) $this->blockSegmentRevRotate((string)$ct[13], (string)$ct[12], (int)self::MAGICS2[$m][6]);
				$ct[12] = (string) $this->uint64Sub((string)$ct[12], (string)$ct[13]);
				$ct[11] = (string) $this->blockSegmentRevRotate((string)$ct[11], (string)$ct[10], (int)self::MAGICS2[$m][5]);
				$ct[10] = (string) $this->uint64Sub((string)$ct[10], (string)$ct[11]);
				$ct[9]  = (string) $this->blockSegmentRevRotate((string)$ct[9], (string)$ct[8],   (int)self::MAGICS2[$m][4]);
				$ct[8]  = (string) $this->uint64Sub((string)$ct[8], (string)$ct[9]);
				$ct[7]  = (string) $this->blockSegmentRevRotate((string)$ct[7], (string)$ct[6],   (int)self::MAGICS2[$m][3]);
				$ct[6]  = (string) $this->uint64Sub((string)$ct[6], (string)$ct[7]);
				$ct[5]  = (string) $this->blockSegmentRevRotate((string)$ct[5], (string)$ct[4],   (int)self::MAGICS2[$m][2]);
				$ct[4]  = (string) $this->uint64Sub((string)$ct[4], (string)$ct[5]);
				$ct[3]  = (string) $this->blockSegmentRevRotate((string)$ct[3], (string)$ct[2],   (int)self::MAGICS2[$m][1]);
				$ct[2]  = (string) $this->uint64Sub((string)$ct[2], (string)$ct[3]);
				$ct[1]  = (string) $this->blockSegmentRevRotate((string)$ct[1], (string)$ct[0],   (int)self::MAGICS2[$m][0]);
				$ct[0]  = (string) $this->uint64Sub((string)$ct[0], (string)$ct[1]);
				//--
			} //end for
			//-- Subtract round key
			$ct[0]  = (string) $this->uint64Sub((string)$ct[0],  (string)$this->expandedKey[floor($d/4)][0]);
			$ct[1]  = (string) $this->uint64Sub((string)$ct[1],  (string)$this->expandedKey[floor($d/4)][1]);
			$ct[2]  = (string) $this->uint64Sub((string)$ct[2],  (string)$this->expandedKey[floor($d/4)][2]);
			$ct[3]  = (string) $this->uint64Sub((string)$ct[3],  (string)$this->expandedKey[floor($d/4)][3]);
			$ct[4]  = (string) $this->uint64Sub((string)$ct[4],  (string)$this->expandedKey[floor($d/4)][4]);
			$ct[5]  = (string) $this->uint64Sub((string)$ct[5],  (string)$this->expandedKey[floor($d/4)][5]);
			$ct[6]  = (string) $this->uint64Sub((string)$ct[6],  (string)$this->expandedKey[floor($d/4)][6]);
			$ct[7]  = (string) $this->uint64Sub((string)$ct[7],  (string)$this->expandedKey[floor($d/4)][7]);
			$ct[8]  = (string) $this->uint64Sub((string)$ct[8],  (string)$this->expandedKey[floor($d/4)][8]);
			$ct[9]  = (string) $this->uint64Sub((string)$ct[9],  (string)$this->expandedKey[floor($d/4)][9]);
			$ct[10] = (string) $this->uint64Sub((string)$ct[10], (string)$this->expandedKey[floor($d/4)][10]);
			$ct[11] = (string) $this->uint64Sub((string)$ct[11], (string)$this->expandedKey[floor($d/4)][11]);
			$ct[12] = (string) $this->uint64Sub((string)$ct[12], (string)$this->expandedKey[floor($d/4)][12]);
			$ct[13] = (string) $this->uint64Sub((string)$ct[13], (string)$this->expandedKey[floor($d/4)][13]);
			$ct[14] = (string) $this->uint64Sub((string)$ct[14], (string)$this->expandedKey[floor($d/4)][14]);
			$ct[15] = (string) $this->uint64Sub((string)$ct[15], (string)$this->expandedKey[floor($d/4)][15]);
			//-- Four rounds of permute and unmix
			for($m=(int)((int)Smart::array_size((array)self::MAGICS1)-1); $m>=0; $m--) {
				//--
				$ct 	= (array)  $this->blockSegmentRevPermutate((array)$ct, (array)$ct);
				$ct[15] = (string) $this->blockSegmentRevRotate((string)$ct[15], (string)$ct[14], (int)self::MAGICS1[$m][7]);
				$ct[14] = (string) $this->uint64Sub((string)$ct[14], (string)$ct[15]);
				$ct[13] = (string) $this->blockSegmentRevRotate((string)$ct[13], (string)$ct[12], (int)self::MAGICS1[$m][6]);
				$ct[12] = (string) $this->uint64Sub((string)$ct[12], (string)$ct[13]);
				$ct[11] = (string) $this->blockSegmentRevRotate((string)$ct[11], (string)$ct[10], (int)self::MAGICS1[$m][5]);
				$ct[10] = (string) $this->uint64Sub((string)$ct[10], (string)$ct[11]);
				$ct[9]  = (string) $this->blockSegmentRevRotate((string)$ct[9], (string)$ct[8],   (int)self::MAGICS1[$m][4]);
				$ct[8]  = (string) $this->uint64Sub((string)$ct[8], (string)$ct[9]);
				$ct[7]  = (string) $this->blockSegmentRevRotate((string)$ct[7], (string)$ct[6],   (int)self::MAGICS1[$m][3]);
				$ct[6]  = (string) $this->uint64Sub((string)$ct[6], (string)$ct[7]);
				$ct[5]  = (string) $this->blockSegmentRevRotate((string)$ct[5], (string)$ct[4],   (int)self::MAGICS1[$m][2]);
				$ct[4]  = (string) $this->uint64Sub((string)$ct[4], (string)$ct[5]);
				$ct[3]  = (string) $this->blockSegmentRevRotate((string)$ct[3], (string)$ct[2],   (int)self::MAGICS1[$m][1]);
				$ct[2]  = (string) $this->uint64Sub((string)$ct[2], (string)$ct[3]);
				$ct[1]  = (string) $this->blockSegmentRevRotate((string)$ct[1], (string)$ct[0],   (int)self::MAGICS1[$m][0]);
				$ct[0]  = (string) $this->uint64Sub((string)$ct[0], (string)$ct[1]);
				//--
			} //end for
			//-- Subtract round key
			$ct[0]  = (string) $this->uint64Sub((string)$ct[0],  (string)$this->expandedKey[(floor($d/4))-1][0]);
			$ct[1]  = (string) $this->uint64Sub((string)$ct[1],  (string)$this->expandedKey[(floor($d/4))-1][1]);
			$ct[2]  = (string) $this->uint64Sub((string)$ct[2],  (string)$this->expandedKey[(floor($d/4))-1][2]);
			$ct[3]  = (string) $this->uint64Sub((string)$ct[3],  (string)$this->expandedKey[(floor($d/4))-1][3]);
			$ct[4]  = (string) $this->uint64Sub((string)$ct[4],  (string)$this->expandedKey[(floor($d/4))-1][4]);
			$ct[5]  = (string) $this->uint64Sub((string)$ct[5],  (string)$this->expandedKey[(floor($d/4))-1][5]);
			$ct[6]  = (string) $this->uint64Sub((string)$ct[6],  (string)$this->expandedKey[(floor($d/4))-1][6]);
			$ct[7]  = (string) $this->uint64Sub((string)$ct[7],  (string)$this->expandedKey[(floor($d/4))-1][7]);
			$ct[8]  = (string) $this->uint64Sub((string)$ct[8],  (string)$this->expandedKey[(floor($d/4))-1][8]);
			$ct[9]  = (string) $this->uint64Sub((string)$ct[9],  (string)$this->expandedKey[(floor($d/4))-1][9]);
			$ct[10] = (string) $this->uint64Sub((string)$ct[10], (string)$this->expandedKey[(floor($d/4))-1][10]);
			$ct[11] = (string) $this->uint64Sub((string)$ct[11], (string)$this->expandedKey[(floor($d/4))-1][11]);
			$ct[12] = (string) $this->uint64Sub((string)$ct[12], (string)$this->expandedKey[(floor($d/4))-1][12]);
			$ct[13] = (string) $this->uint64Sub((string)$ct[13], (string)$this->expandedKey[(floor($d/4))-1][13]);
			$ct[14] = (string) $this->uint64Sub((string)$ct[14], (string)$this->expandedKey[(floor($d/4))-1][14]);
			$ct[15] = (string) $this->uint64Sub((string)$ct[15], (string)$this->expandedKey[(floor($d/4))-1][15]);
			//--
		} //end for
		//--
		return (string) $this->storeWords((array)$ct);
		//--
	} //END FUNCTION


	private function blockSegmentRotate(string $x, string $y, int $n) : string {
		//--
		return (string) $this->uint64XOr((string)$this->uint64Or((string)$this->uint64ShiftLeft((string)$x, (string)$n), (string)$this->uint64ShiftRight((string)$x, (string)(64 - (int)$n))), (string)$y);
		//--
	} //END FUNCTION


	private function blockSegmentRevRotate(string $x, string $y, int $n) : string {
		//--
		$xor = (string) $this->uint64XOr((string)$x, (string)$y);
		//--
		return (string) $this->uint64Or((string)$this->uint64ShiftLeft((string)$xor, (string)(64 - (int)$n)), (string)$this->uint64ShiftRight((string)$xor, (string)$n));
		//--
	} //END FUNCTION


	private function blockSegmentPermutate(array $x, array $y) : array {
		//--
		$x[1]  = $y[9];
		$x[3]  = $y[13];
		$x[4]  = $y[6];
		$x[5]  = $y[11];
		$x[6]  = $y[4];
		$x[7]  = $y[15];
		$x[8]  = $y[10];
		$x[9]  = $y[7];
		$x[10] = $y[12];
		$x[11] = $y[3];
		$x[12] = $y[14];
		$x[13] = $y[5];
		$x[14] = $y[8];
		$x[15] = $y[1];
		//--
		return (array) $x;
		//--
	} //END FUNCTION


	private function blockSegmentRevPermutate(array $x, array $y) : array {
		//--
		$x[1]  = $y[15];
		$x[3]  = $y[11];
		$x[4]  = $y[6];
		$x[5]  = $y[13];
		$x[6]  = $y[4];
		$x[7]  = $y[9];
		$x[8]  = $y[14];
		$x[9]  = $y[1];
		$x[10] = $y[8];
		$x[11] = $y[5];
		$x[12] = $y[10];
		$x[13] = $y[3];
		$x[14] = $y[12];
		$x[15] = $y[7];
		//--
		return (array) $x;
		//--
	} //END FUNCTION


	private function loadWord(string $src) : array {
		//--
		if((int)strlen((string)$src) !== 8) {
			Smart::raise_error(__METHOD__.' # Input data must be exactly 8 bytes (64 bit)');
			return [ 0 ]; // ERR: empty input
		} //end if
		//--
		/*
		$dst = unpack('P', (string)$src); // unpack # P: little endian order ; J: big endian byte order ; Q: machine order
		if(!is_array($dst)) {
			Smart::raise_error(__METHOD__.' # Unpack Failed');
			return [ 0 ]; // ERR: unpack failed
		} //end if
		//return (array) $dst; // needs the fix below, for negative values
		//--
		$dst = (string) trim((string)implode('', (array)array_values((array)$dst)));
		if((string)substr((string)$dst, 0, 1) == '-') { // identify negative values
			$dst = (string) $this->uint64Add((string)$dst, (string)self::UINT64_MAX); // fix negative values from int64 to uint64
			$dst = (string) $this->uint64Add((string)$dst, '1'); // fix: appear that need to add 1 more ... because negatives int64 reversed start from -1 not from zero !
		} //end if
		//--
		return [ (string)$dst ];
		*/
		//--
		return [ (string) gmp_import((string)$src, 8, GMP_LITTLE_ENDIAN) ]; // ThreeFish requires Little Endian Internally ; when using 8 bytes import/export the MSW/LSW flag does not matter ; 8 bytes MSW little/big: EFCDAB8967452301
		//--
	} //END FUNCTION


	private function storeWords(array $uint64Arr) : string {
		//--
		if((int)Smart::array_size($uint64Arr) != 16) {
			Smart::raise_error(__METHOD__.' # Input data array must have exactly 16 entries, each as uint64');
			return '';
		} //end if
		//--
		for($i=0; $i<(int)Smart::array_size($uint64Arr); $i++) {
			//--
			/*
			$uint64Arr[$i] = (string) str_pad((string)gmp_strval((string)$uint64Arr[$i], 16), 16, 0, STR_PAD_LEFT); // to reverse can use gmp_strval('0x'.$hex, 10)
			$uint64Arr[$i] = (string) hex2bin((string)$uint64Arr[$i]);
			$uint64Arr[$i] = (array)  str_split((string)$uint64Arr[$i]);
			$uint64Arr[$i] = (array)  array_reverse((array)$uint64Arr[$i]); // this is because hex2bin apparently operates in big endian mode ...
			$uint64Arr[$i] = (string) implode('', (array)$uint64Arr[$i]);
			*/
			$uint64Arr[$i] = (string) gmp_export((string)$uint64Arr[$i], 8, GMP_NATIVE_ENDIAN); // on export, use native endianness ; when using 8 bytes import/export the MSW/LSW flag does not matter ; 8 bytes MSW little/big: EFCDAB8967452301
			//--
		} //end for
		//--
		return (string) implode('', (array)$uint64Arr);
		//--
	} //END FUNCTION


	private function storeStr(array $src) : string {
		//--
		if((int)Smart::array_size($src) != 1) {
			Smart::raise_error(__METHOD__.' # Input data array must have exactly 1 entrie, as uint64');
			return ''; // ERR: empty input
		} //end if
		//--
		return (string) implode('', (array)$src);
		//--
	} //END FUNCTION


	private function uint64GetAsInt64(string $uint64) : string {
		//--
		return (string) gmp_and((string)$uint64, (string)self::UINT64_MAX);
		//--
	} //END FUNCTION


	private function uint64Add(string $a, string $b) : string {
		//--
		return (string) $this->uint64GetAsInt64((string)gmp_add((string)$a, (string)$b));
		//--
	} //END FUNCTION


	private function uint64Sub(string $a, string $b) : string {
		//--
		return (string) $this->uint64GetAsInt64((string)gmp_sub((string)$a, (string)$b));
		//--
	} //END FUNCTION


	private function uint64XOr(string $a, string $b) : string {
		//--
		return (string) $this->uint64GetAsInt64((string)gmp_xor((string)$a, (string)$b));
		//--
	} //END FUNCTION


	private function uint64Or(string $a, string $b) : string {
		//--
		return (string) $this->uint64GetAsInt64((string)gmp_or((string)$a, (string)$b));
		//--
	} //END FUNCTION


	private function uint64ShiftLeft(string $a, string $b) : string {
		//--
		return (string) $this->uint64GetAsInt64((string)$this->gmp_shiftl((string)$a, (string)$b));
		//--
	} //END FUNCTION


	private function uint64ShiftRight(string $a, string $b) : string {
		//--
		return (string) $this->uint64GetAsInt64((string)$this->gmp_shiftr((string)$a, (string)$b));
		//--
	} //END FUNCTION


	private function gmp_shiftl(string $x, string $n) : string { // gmp shift left
		//--
		return (string) gmp_mul((string)$x, (string)gmp_pow(2, (string)$n));
		//--
	} //END FUNCTION


	function gmp_shiftr(string $x, string $n) : string { // gmp shift right
		//--
		return (string) gmp_div_q((string)$x, (string)gmp_pow(2, (string)$n));
		//--
	} //END FUNCTION


} //END CLASS

//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


//--
// PHP implementation of the Two algorithm in CBC mode
// It does not require any PHP extension, it uses only the core PHP.
// Class support encryption/decryption with a secret key and iv (CBC mode only).
//
// LICENSE: MIT, authors: Jim Wigginton <terrafrost@php.net>, Hans-Juergen Petrich <petrich@tronic-media.com>
// (c) 2007 Jim Wigginton
//
// Modified from by unixman (iradu@unix-world.org), contains many fixes and optimizations
// (c) 2023-present unix-world.org
//--

/**
 * Class:  Smart Crypto Ciphers :: Twofish CBC
 * Provides a built-in based feature to handle the Twofish 256-bit CBC encryption / decryption.
 *
 * Twofish is a block cipher developed by Bruce Schneier and Counterpane Labs, published in 1998.
 * Being very secure, it remains unbroken to this day and in the foreseeable future.
 * It is as strong as AES but have the advantage being more secure by design than AES ... the only advantage AES have is being faster ...
 *
 * This class is standard (blocksize 128 bit = 16 bytes) ; only supports CBC mode ; have 16 rounds ; only supports key size 256 bit (32 bytes), lower keys not supported for security reasons ...
 *
 * @usage       dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 * @hints       Twofish is a 128-bit (16 bytes) block cipher, exactly as AES, with a Key size up to 32 chars length (32 bytes = 256 bits). The CBC mode requires a initialization vector (iv).
 *
 * @access 		private
 * @internal
 *
 * @depends     classes: Smart, SmartEnvironment
 * @version     v.20231202
 *
 */
final class SmartCryptoCiphersTwofishCBC { // algo has been adapted to work faster, supports 64-bit machines only

	// ->

	private const BLOCK_SIZE = 16; // the Twofish block size

	private $mver = ''; // Crypto Package Mode Version: `v3` (current, encrypd/decrypt)

	private $key = ''; // must be 32 bytes
	private $iv  = ''; // must be 16 bytes, as the block size

	private $q0 = []; // Q-Table 0 expander
	private $q1 = []; // Q-Table 1 expander
	private $m0 = []; // M-Table 0 expander
	private $m1 = []; // M-Table 1 expander
	private $m2 = []; // M-Table 2 expander
	private $m3 = []; // M-Table 3 expander

	private $K  = []; // the Key Schedule Array
	private $S0 = []; // the Key depended S-Table 0
	private $S1 = []; // the Key depended S-Table 1
	private $S2 = []; // the Key depended S-Table 2
	private $S3 = []; // the Key depended S-Table 3



	//==============================================================
	/**
	 * Default Constructor.
	 */
	public function __construct(string $key, string $iv, string $modever='v3') {
		//--
		$modever = (string) strtolower((string)trim((string)$modever));
		switch((string)$modever) { // {{{SYNC-CRYPTO-MODE-VERSIONS}}}
			case 'v3': // current
				break;
			default:
				Smart::log_warning(__METHOD__.' # Invalid Mode Version: `'.$modever.'` # Fallback to v3 ...');
				$modever = 'v3';
		} //end switch
		$this->mver = (string) $modever;
		//--
		$this->key = (string) $key;
		$this->iv  = (string) $iv;
		//--
		if(
			((string)trim((string)$this->key) == '')
			OR
			((int)strlen((string)$this->key) != 32)
			OR
			((int)strlen((string)trim((string)$this->key)) != 32)
		) {
			if(SmartEnvironment::ifInternalDebug()) {
				if(SmartEnvironment::ifDebug()) {
					Smart::log_notice(__METHOD__.' # Key is too short: `'.$this->key.'`');
				} //end if
			} //end if
			Smart::raise_error(__METHOD__.' # Setup Failed: Invalid KEY, must be exactly 32 bytes (256 bits)');
			return;
		} //end if
		if(
			((string)trim((string)$this->iv) == '')
			OR
			((int)strlen((string)$this->iv) != 16)
			OR
			((int)strlen((string)trim((string)$this->iv)) != 16)
		) {
			if(SmartEnvironment::ifInternalDebug()) {
				if(SmartEnvironment::ifDebug()) {
					Smart::log_notice(__METHOD__.' # Key is too short: `'.$this->iv.'`');
				} //end if
			} //end if
			Smart::raise_error(__METHOD__.' # Setup Failed: Invalid IV, must be exactly 16 bytes (128 bits)');
			return;
		} //end if
		//--
		$this->_setup();
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Encrypts a string in CBC Mode using Twofish cipher algo
	 *
	 * $b64DataOrPak needs to be base64 encoded or similar because will be padded with additional spaces (CBC) until the length of the block size
	 * The idea of encoding B64 (or similar) the data is because this algorithm does not support multi-byte strings (ex: UTF-8) ; also some binary data may be broken because of internal null trimming
	 *
	 * @param string $b64DataOrPak
	 * @return string $ciphertext
	 */
	public function encrypt(?string $b64DataOrPak) : string {
		//-- {{{SYNC-CRYPTO-DATA-ENCAPSULATE-B64}}}
		// expects: B64 data or B64 / HEX pak ; req. for safe padding !
		// why B64 or package data ? just because safe padding requires a capsule like B64/HEX
		// why not B64 encode here ? just because it can be a package as B64#signature and don't want to manage signatures here ...
		//--
		if((string)$this->mver == 'v3') { // v3
			// OK
		} else {
			return ''; // not supported
		} //end if else
		//--
		if((string)trim((string)$this->key) == '') {
			Smart::log_warning(__METHOD__.' # Crypto Key is Empty ('.$this->mver.'/-)');
			return '';
		} //end if
		if((string)trim((string)$this->iv) == '') {
			Smart::log_warning(__METHOD__.' # Crypto iV is Empty ('.$this->mver.'/-)');
			return '';
		} //end if
		//--
		if((string)$b64DataOrPak === '') {
			return '';
		} //end if
		//--
		$length = (int) strlen((string)$b64DataOrPak);
		$padlen = (int) (((int)self::BLOCK_SIZE - (int)((int)$length % (int)self::BLOCK_SIZE)) % (int)self::BLOCK_SIZE); // twofish blocksize is 16 ; {{{SYNC-ENCRYPTY-B64-PADDING}}}
		$b64DataOrPak = (string) str_pad((string)$b64DataOrPak, (int)((int)$length + (int)$padlen), ' ', STR_PAD_RIGHT); // fix by unixman ; pad with spaces, it should be B64 data
		//--
		$ciphertext = '';
		//-- CBC
		$xor = (string) $this->iv;
		for($i=0; $i<(int)$length; $i+=self::BLOCK_SIZE) {
			$block = (string) substr((string)$b64DataOrPak, (int)$i, (int)self::BLOCK_SIZE);
			$block = (string) $this->_encryptBlock((string)((string)$block ^ (string)$xor));
			$xor = (string) $block;
			$ciphertext .= (string) $block;
		} //end for
		//--
		return (string) $ciphertext;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Decrypts a string in CBC Mode using Twofish cipher algo
	 *
	 * If strlen($ciphertext) is not a multiple of the block size, null bytes will be added to the end of the string until it is.
	 * If data was passed B64 or similar to the encrypt method above, after using this method data needs to be base64 decoded or similar
	 *
	 * @param string $ciphertext
	 * @return string $b64DataOrPak
	 */
	public function decrypt(?string $ciphertext) : string {
		//--
		if((string)$this->mver == 'v3') { // v3
			// OK
		} else {
			return ''; // not supported
		} //end if else
		//--
		if((string)trim((string)$this->key) == '') {
			Smart::log_warning(__METHOD__.' # Crypto Key is Empty ('.$this->mver.'/-)');
			return '';
		} //end if
		if((string)trim((string)$this->iv) == '') {
			Smart::log_warning(__METHOD__.' # Crypto iV is Empty ('.$this->mver.'/-)');
			return '';
		} //end if
		//--
		if((string)$ciphertext === '') {
			return '';
		} //end if
		//-- {{{FIX-GOLANG-CIPHER-1ST-NULL-BLOCK-HEADER}}} ; pad with chr(0) since that's what m-crypt generic way: "The data is padded with "\0" to make sure the length of the data is n * blocksize."
		$length = (int) strlen((string)$ciphertext);
		$padlen = (int) (((int)self::BLOCK_SIZE - (int)((int)$length % (int)self::BLOCK_SIZE)) % (int)self::BLOCK_SIZE);
		$ciphertext = (string) str_pad((string)$ciphertext, (int)((int)$length + (int)$padlen), (string)chr(0), STR_PAD_RIGHT);
		//--
		$b64DataOrPak = '';
		//-- CBC
		$xor = (string) $this->iv;
		for($i=0; $i<(int)$length; $i+=(int)self::BLOCK_SIZE) {
			$block = (string) substr((string)$ciphertext, (int)$i, (int)self::BLOCK_SIZE);
			$b64DataOrPak .= (string) ((string)$this->_decryptBlock((string)$block) ^ (string)$xor);
			$xor = (string) $block;
		} //end for
		//--
	//	$b64DataOrPak = (string) rtrim((string)$b64DataOrPak, ' '); // trim B64 data on right
		$b64DataOrPak = (string) trim((string)$b64DataOrPak, ' '); // trim B64 data on right + left ... why not :-) it is B64 data ...
		//--
		return (string) $b64DataOrPak;
		//--
	} //END FUNCTION
	//==============================================================


	//===================== [PRIVATES]


	//==============================================================
	// Setup the key (expansion)
	private function _setup() : void {
		//-- Key expanding and generating the key-depended s-boxes
		$le_longs = unpack('V*', (string)$this->key); // do not cast
		if((int)Smart::array_size($le_longs) <= 0) { // unpack failed
			Smart::raise_error(__METHOD__.' # Setup Failed: UNPACK / Boxes');
			return;
		} //end if
		//--
		$key = unpack('C*', (string)$this->key); // do not cast
		if((int)Smart::array_size($key) <= 0) { // unpack failed
			Smart::raise_error(__METHOD__.' # Setup Failed: UNPACK / Key');
			return;
		} //end if
		//-- fix by unixman, this class only works on 64-bit hardware ... no need for intval
	//	$m0 = (array) array_map('intval', (array) self::M0);
	//	$m1 = (array) array_map('intval', (array) self::M1);
	//	$m2 = (array) array_map('intval', (array) self::M2);
	//	$m3 = (array) array_map('intval', (array) self::M3);
	//	$q0 = (array) array_map('intval', (array) self::Q0);
	//	$q1 = (array) array_map('intval', (array) self::Q1);
		//-- optimization for 64-bit PHP
		$m0 = (array) self::M0;
		$m1 = (array) self::M1;
		$m2 = (array) self::M2;
		$m3 = (array) self::M3;
		$q0 = (array) self::Q0;
		$q1 = (array) self::Q1;
		//-- #end fix
		$K = $S0 = $S1 = $S2 = $S3 = [];
		//--
	//	switch((int)strlen((string)$this->key)) {
		//--
		/* these keys are too short: 16 and 24 ; disable !!! strong encryption is needed ... this is why looking for Twofish !
		case 16:
		list($s7, $s6, $s5, $s4) = (array) $this->_mdsrem($le_longs[1], $le_longs[2]);
		list($s3, $s2, $s1, $s0) = (array) $this->_mdsrem($le_longs[3], $le_longs[4]);
		for($i=0, $j=1; $i<40; $i+=2, $j+=2) {
			$A = $m0[$q0[$q0[$i] ^ $key[ 9]] ^ $key[1]] ^
				 $m1[$q0[$q1[$i] ^ $key[10]] ^ $key[2]] ^
				 $m2[$q1[$q0[$i] ^ $key[11]] ^ $key[3]] ^
				 $m3[$q1[$q1[$i] ^ $key[12]] ^ $key[4]];
			$B = $m0[$q0[$q0[$j] ^ $key[13]] ^ $key[5]] ^
				 $m1[$q0[$q1[$j] ^ $key[14]] ^ $key[6]] ^
				 $m2[$q1[$q0[$j] ^ $key[15]] ^ $key[7]] ^
				 $m3[$q1[$q1[$j] ^ $key[16]] ^ $key[8]];
			$B = ($B << 8) | ($B >> 24 & 0xff);
			$A = (int)($A + $B);
			$K[] = $A;
			$A = (int)($A + $B);
			$K[] = ($A << 9 | $A >> 23 & 0x1ff);
		} //end for
		for($i=0; $i<256; ++$i) {
			$S0[$i] = $m0[$q0[$q0[$i] ^ $s4] ^ $s0];
			$S1[$i] = $m1[$q0[$q1[$i] ^ $s5] ^ $s1];
			$S2[$i] = $m2[$q1[$q0[$i] ^ $s6] ^ $s2];
			$S3[$i] = $m3[$q1[$q1[$i] ^ $s7] ^ $s3];
		} //end for
		break;
		case 24:
		list($sb, $sa, $s9, $s8) = (array) $this->_mdsrem($le_longs[1], $le_longs[2]);
		list($s7, $s6, $s5, $s4) = (array) $this->_mdsrem($le_longs[3], $le_longs[4]);
		list($s3, $s2, $s1, $s0) = (array) $this->_mdsrem($le_longs[5], $le_longs[6]);
		for($i=0, $j=1; $i<40; $i+=2, $j+=2) {
			$A = $m0[$q0[$q0[$q1[$i] ^ $key[17]] ^ $key[ 9]] ^ $key[1]] ^
				 $m1[$q0[$q1[$q1[$i] ^ $key[18]] ^ $key[10]] ^ $key[2]] ^
				 $m2[$q1[$q0[$q0[$i] ^ $key[19]] ^ $key[11]] ^ $key[3]] ^
				 $m3[$q1[$q1[$q0[$i] ^ $key[20]] ^ $key[12]] ^ $key[4]];
			$B = $m0[$q0[$q0[$q1[$j] ^ $key[21]] ^ $key[13]] ^ $key[5]] ^
				 $m1[$q0[$q1[$q1[$j] ^ $key[22]] ^ $key[14]] ^ $key[6]] ^
				 $m2[$q1[$q0[$q0[$j] ^ $key[23]] ^ $key[15]] ^ $key[7]] ^
				 $m3[$q1[$q1[$q0[$j] ^ $key[24]] ^ $key[16]] ^ $key[8]];
			$B = ($B << 8) | ($B >> 24 & 0xff);
			$A = (int)($A + $B);
			$K[] = $A;
			$A = (int)($A + $B);
			$K[] = ($A << 9 | $A >> 23 & 0x1ff);
		} //end for
		for($i=0; $i<256; ++$i) {
			$S0[$i] = $m0[$q0[$q0[$q1[$i] ^ $s8] ^ $s4] ^ $s0];
			$S1[$i] = $m1[$q0[$q1[$q1[$i] ^ $s9] ^ $s5] ^ $s1];
			$S2[$i] = $m2[$q1[$q0[$q0[$i] ^ $sa] ^ $s6] ^ $s2];
			$S3[$i] = $m3[$q1[$q1[$q0[$i] ^ $sb] ^ $s7] ^ $s3];
		} //end for
		break;
		*/
		//--
	//	default: // 32 bytes key support only !
		list($sf, $se, $sd, $sc) = (array) $this->_mdsrem($le_longs[1], $le_longs[2]);
		list($sb, $sa, $s9, $s8) = (array) $this->_mdsrem($le_longs[3], $le_longs[4]);
		list($s7, $s6, $s5, $s4) = (array) $this->_mdsrem($le_longs[5], $le_longs[6]);
		list($s3, $s2, $s1, $s0) = (array) $this->_mdsrem($le_longs[7], $le_longs[8]);
		for($i=0, $j=1; $i<40; $i+=2, $j+=2) {
			$A = $m0[$q0[$q0[$q1[$q1[$i] ^ $key[25]] ^ $key[17]] ^ $key[ 9]] ^ $key[1]] ^
				 $m1[$q0[$q1[$q1[$q0[$i] ^ $key[26]] ^ $key[18]] ^ $key[10]] ^ $key[2]] ^
				 $m2[$q1[$q0[$q0[$q0[$i] ^ $key[27]] ^ $key[19]] ^ $key[11]] ^ $key[3]] ^
				 $m3[$q1[$q1[$q0[$q1[$i] ^ $key[28]] ^ $key[20]] ^ $key[12]] ^ $key[4]];
			$B = $m0[$q0[$q0[$q1[$q1[$j] ^ $key[29]] ^ $key[21]] ^ $key[13]] ^ $key[5]] ^
				 $m1[$q0[$q1[$q1[$q0[$j] ^ $key[30]] ^ $key[22]] ^ $key[14]] ^ $key[6]] ^
				 $m2[$q1[$q0[$q0[$q0[$j] ^ $key[31]] ^ $key[23]] ^ $key[15]] ^ $key[7]] ^
				 $m3[$q1[$q1[$q0[$q1[$j] ^ $key[32]] ^ $key[24]] ^ $key[16]] ^ $key[8]];
			$B = ($B << 8) | ($B >> 24 & 0xff);
			$A = (int)($A + $B);
			$K[] = $A;
			$A = (int)($A + $B);
			$K[] = ($A << 9 | $A >> 23 & 0x1ff);
		} //end for
		for($i=0; $i<256; ++$i) {
			$S0[$i] = $m0[$q0[$q0[$q1[$q1[$i] ^ $sc] ^ $s8] ^ $s4] ^ $s0];
			$S1[$i] = $m1[$q0[$q1[$q1[$q0[$i] ^ $sd] ^ $s9] ^ $s5] ^ $s1];
			$S2[$i] = $m2[$q1[$q0[$q0[$q0[$i] ^ $se] ^ $sa] ^ $s6] ^ $s2];
			$S3[$i] = $m3[$q1[$q1[$q0[$q1[$i] ^ $sf] ^ $sb] ^ $s7] ^ $s3];
		} //end for
			//--
	//	} //end switch
		//--
		$this->K  = $K;
		$this->S0 = $S0;
		$this->S1 = $S1;
		$this->S2 = $S2;
		$this->S3 = $S3;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	// mdsrem method used by the twofish cipher algorithm
	private function _mdsrem(int $A, int $B) : array {
		//-- No gain by unrolling this loop.
		for($i=0; $i<8; ++$i) {
			//-- Get most significant coefficient.
			$t = 0xff & ($B >> 24);
			//-- Shift the others up.
			$B = ($B << 8) | (0xff & ($A >> 24));
			$A<<= 8;
			$u = $t << 1;
			//-- Subtract the modular polynomial on overflow.
			if($t & 0x80) {
				$u^= 0x14d;
			} //end if
			//-- Remove t * (a * x^2 + 1).
			$B ^= $t ^ ($u << 16);
			//-- Form u = a*t + t/a = t*(a + 1/a).
			$u^= 0x7fffffff & ($t >> 1);
			//-- Add the modular polynomial on underflow.
			if($t & 0x01) {
				$u^= 0xa6 ;
			} //end if
			//-- Remove t * (a + 1/a) * (x^3 + x).
			$B^= ($u << 24) | ($u << 8);
		} //end for
		//--
		return [
			0xff & $B >> 24,
			0xff & $B >> 16,
			0xff & $B >>  8,
			0xff & $B
		];
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	// encrypts a block
	private function _encryptBlock(string $in) : string {
		//--
		$S0 = $this->S0;
		$S1 = $this->S1;
		$S2 = $this->S2;
		$S3 = $this->S3;
		$K  = $this->K;
		//--
		$in = unpack('V4', (string)$in); // do not cast
		if((int)Smart::array_size($in) <= 0) { // unpack failed
			Smart::log_warning(__METHOD__.' # Setup Failed: UNPACK / Block');
			return '';
		} //end if
		//--
		$R0 = $K[0] ^ $in[1];
		$R1 = $K[1] ^ $in[2];
		$R2 = $K[2] ^ $in[3];
		$R3 = $K[3] ^ $in[4];
		//--
		$ki = 7;
		while($ki < 39) {
			//--
			$t0 = $S0[ $R0        & 0xff] ^
				  $S1[($R0 >>  8) & 0xff] ^
				  $S2[($R0 >> 16) & 0xff] ^
				  $S3[($R0 >> 24) & 0xff];
			$t1 = $S0[($R1 >> 24) & 0xff] ^
				  $S1[ $R1        & 0xff] ^
				  $S2[($R1 >>  8) & 0xff] ^
				  $S3[($R1 >> 16) & 0xff];
			$R2^= (int)($t0 + $t1 + $K[++$ki]);
			$R2 = ($R2 >> 1 & 0x7fffffff) | ($R2 << 31);
			$R3 = ((($R3 >> 31) & 1) | ($R3 << 1)) ^ (int)($t0 + ($t1 << 1) + $K[++$ki]);
			//--
			$t0 = $S0[ $R2        & 0xff] ^
				  $S1[($R2 >>  8) & 0xff] ^
				  $S2[($R2 >> 16) & 0xff] ^
				  $S3[($R2 >> 24) & 0xff];
			$t1 = $S0[($R3 >> 24) & 0xff] ^
				  $S1[ $R3        & 0xff] ^
				  $S2[($R3 >>  8) & 0xff] ^
				  $S3[($R3 >> 16) & 0xff];
			$R0^= (int)($t0 + $t1 + $K[++$ki]);
			$R0 = ($R0 >> 1 & 0x7fffffff) | ($R0 << 31);
			$R1 = ((($R1 >> 31) & 1) | ($R1 << 1)) ^ (int)($t0 + ($t1 << 1) + $K[++$ki]);
			//--
		} //end while
		//--
		return (string) pack('V4',
			$K[4] ^ $R2,
			$K[5] ^ $R3,
			$K[6] ^ $R0,
			$K[7] ^ $R1
		);
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	// decrypts a block
	private function _decryptBlock(string $in) : string {
		//--
		$S0 = $this->S0;
		$S1 = $this->S1;
		$S2 = $this->S2;
		$S3 = $this->S3;
		$K  = $this->K;
		//--
		$in = unpack('V4', (string)$in); // do not cast
		if((int)Smart::array_size($in) <= 0) { // unpack failed
			Smart::log_warning(__METHOD__.' # Setup Failed: UNPACK / Block');
			return '';
		} //end if
		//--
		$R0 = $K[4] ^ $in[1];
		$R1 = $K[5] ^ $in[2];
		$R2 = $K[6] ^ $in[3];
		$R3 = $K[7] ^ $in[4];
		//--
		$ki = 40;
		while($ki > 8) {
			//--
			$t0 = $S0[$R0       & 0xff] ^
				  $S1[$R0 >>  8 & 0xff] ^
				  $S2[$R0 >> 16 & 0xff] ^
				  $S3[$R0 >> 24 & 0xff];
			$t1 = $S0[$R1 >> 24 & 0xff] ^
				  $S1[$R1       & 0xff] ^
				  $S2[$R1 >>  8 & 0xff] ^
				  $S3[$R1 >> 16 & 0xff];
			$R3^= (int)($t0 + ($t1 << 1) + $K[--$ki]);
			$R3 = $R3 >> 1 & 0x7fffffff | $R3 << 31;
			$R2 = ($R2 >> 31 & 0x1 | $R2 << 1) ^ (int)($t0 + $t1 + $K[--$ki]);
			//--
			$t0 = $S0[$R2       & 0xff] ^
				  $S1[$R2 >>  8 & 0xff] ^
				  $S2[$R2 >> 16 & 0xff] ^
				  $S3[$R2 >> 24 & 0xff];
			$t1 = $S0[$R3 >> 24 & 0xff] ^
				  $S1[$R3       & 0xff] ^
				  $S2[$R3 >>  8 & 0xff] ^
				  $S3[$R3 >> 16 & 0xff];
			$R1^= (int)($t0 + ($t1 << 1) + $K[--$ki]);
			$R1 = $R1 >> 1 & 0x7fffffff | $R1 << 31;
			$R0 = ($R0 >> 31 & 0x1 | $R0 << 1) ^ (int)($t0 + $t1 + $K[--$ki]);
			//--
		} //end while
		//--
		return (string) pack('V4',
			$K[0] ^ $R2,
			$K[1] ^ $R3,
			$K[2] ^ $R0,
			$K[3] ^ $R1
		);
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	private const Q0 = [
		0xA9, 0x67, 0xB3, 0xE8, 0x04, 0xFD, 0xA3, 0x76,
		0x9A, 0x92, 0x80, 0x78, 0xE4, 0xDD, 0xD1, 0x38,
		0x0D, 0xC6, 0x35, 0x98, 0x18, 0xF7, 0xEC, 0x6C,
		0x43, 0x75, 0x37, 0x26, 0xFA, 0x13, 0x94, 0x48,
		0xF2, 0xD0, 0x8B, 0x30, 0x84, 0x54, 0xDF, 0x23,
		0x19, 0x5B, 0x3D, 0x59, 0xF3, 0xAE, 0xA2, 0x82,
		0x63, 0x01, 0x83, 0x2E, 0xD9, 0x51, 0x9B, 0x7C,
		0xA6, 0xEB, 0xA5, 0xBE, 0x16, 0x0C, 0xE3, 0x61,
		0xC0, 0x8C, 0x3A, 0xF5, 0x73, 0x2C, 0x25, 0x0B,
		0xBB, 0x4E, 0x89, 0x6B, 0x53, 0x6A, 0xB4, 0xF1,
		0xE1, 0xE6, 0xBD, 0x45, 0xE2, 0xF4, 0xB6, 0x66,
		0xCC, 0x95, 0x03, 0x56, 0xD4, 0x1C, 0x1E, 0xD7,
		0xFB, 0xC3, 0x8E, 0xB5, 0xE9, 0xCF, 0xBF, 0xBA,
		0xEA, 0x77, 0x39, 0xAF, 0x33, 0xC9, 0x62, 0x71,
		0x81, 0x79, 0x09, 0xAD, 0x24, 0xCD, 0xF9, 0xD8,
		0xE5, 0xC5, 0xB9, 0x4D, 0x44, 0x08, 0x86, 0xE7,
		0xA1, 0x1D, 0xAA, 0xED, 0x06, 0x70, 0xB2, 0xD2,
		0x41, 0x7B, 0xA0, 0x11, 0x31, 0xC2, 0x27, 0x90,
		0x20, 0xF6, 0x60, 0xFF, 0x96, 0x5C, 0xB1, 0xAB,
		0x9E, 0x9C, 0x52, 0x1B, 0x5F, 0x93, 0x0A, 0xEF,
		0x91, 0x85, 0x49, 0xEE, 0x2D, 0x4F, 0x8F, 0x3B,
		0x47, 0x87, 0x6D, 0x46, 0xD6, 0x3E, 0x69, 0x64,
		0x2A, 0xCE, 0xCB, 0x2F, 0xFC, 0x97, 0x05, 0x7A,
		0xAC, 0x7F, 0xD5, 0x1A, 0x4B, 0x0E, 0xA7, 0x5A,
		0x28, 0x14, 0x3F, 0x29, 0x88, 0x3C, 0x4C, 0x02,
		0xB8, 0xDA, 0xB0, 0x17, 0x55, 0x1F, 0x8A, 0x7D,
		0x57, 0xC7, 0x8D, 0x74, 0xB7, 0xC4, 0x9F, 0x72,
		0x7E, 0x15, 0x22, 0x12, 0x58, 0x07, 0x99, 0x34,
		0x6E, 0x50, 0xDE, 0x68, 0x65, 0xBC, 0xDB, 0xF8,
		0xC8, 0xA8, 0x2B, 0x40, 0xDC, 0xFE, 0x32, 0xA4,
		0xCA, 0x10, 0x21, 0xF0, 0xD3, 0x5D, 0x0F, 0x00,
		0x6F, 0x9D, 0x36, 0x42, 0x4A, 0x5E, 0xC1, 0xE0
	];
	//==============================================================
	private const Q1 = [
		0x75, 0xF3, 0xC6, 0xF4, 0xDB, 0x7B, 0xFB, 0xC8,
		0x4A, 0xD3, 0xE6, 0x6B, 0x45, 0x7D, 0xE8, 0x4B,
		0xD6, 0x32, 0xD8, 0xFD, 0x37, 0x71, 0xF1, 0xE1,
		0x30, 0x0F, 0xF8, 0x1B, 0x87, 0xFA, 0x06, 0x3F,
		0x5E, 0xBA, 0xAE, 0x5B, 0x8A, 0x00, 0xBC, 0x9D,
		0x6D, 0xC1, 0xB1, 0x0E, 0x80, 0x5D, 0xD2, 0xD5,
		0xA0, 0x84, 0x07, 0x14, 0xB5, 0x90, 0x2C, 0xA3,
		0xB2, 0x73, 0x4C, 0x54, 0x92, 0x74, 0x36, 0x51,
		0x38, 0xB0, 0xBD, 0x5A, 0xFC, 0x60, 0x62, 0x96,
		0x6C, 0x42, 0xF7, 0x10, 0x7C, 0x28, 0x27, 0x8C,
		0x13, 0x95, 0x9C, 0xC7, 0x24, 0x46, 0x3B, 0x70,
		0xCA, 0xE3, 0x85, 0xCB, 0x11, 0xD0, 0x93, 0xB8,
		0xA6, 0x83, 0x20, 0xFF, 0x9F, 0x77, 0xC3, 0xCC,
		0x03, 0x6F, 0x08, 0xBF, 0x40, 0xE7, 0x2B, 0xE2,
		0x79, 0x0C, 0xAA, 0x82, 0x41, 0x3A, 0xEA, 0xB9,
		0xE4, 0x9A, 0xA4, 0x97, 0x7E, 0xDA, 0x7A, 0x17,
		0x66, 0x94, 0xA1, 0x1D, 0x3D, 0xF0, 0xDE, 0xB3,
		0x0B, 0x72, 0xA7, 0x1C, 0xEF, 0xD1, 0x53, 0x3E,
		0x8F, 0x33, 0x26, 0x5F, 0xEC, 0x76, 0x2A, 0x49,
		0x81, 0x88, 0xEE, 0x21, 0xC4, 0x1A, 0xEB, 0xD9,
		0xC5, 0x39, 0x99, 0xCD, 0xAD, 0x31, 0x8B, 0x01,
		0x18, 0x23, 0xDD, 0x1F, 0x4E, 0x2D, 0xF9, 0x48,
		0x4F, 0xF2, 0x65, 0x8E, 0x78, 0x5C, 0x58, 0x19,
		0x8D, 0xE5, 0x98, 0x57, 0x67, 0x7F, 0x05, 0x64,
		0xAF, 0x63, 0xB6, 0xFE, 0xF5, 0xB7, 0x3C, 0xA5,
		0xCE, 0xE9, 0x68, 0x44, 0xE0, 0x4D, 0x43, 0x69,
		0x29, 0x2E, 0xAC, 0x15, 0x59, 0xA8, 0x0A, 0x9E,
		0x6E, 0x47, 0xDF, 0x34, 0x35, 0x6A, 0xCF, 0xDC,
		0x22, 0xC9, 0xC0, 0x9B, 0x89, 0xD4, 0xED, 0xAB,
		0x12, 0xA2, 0x0D, 0x52, 0xBB, 0x02, 0x2F, 0xA9,
		0xD7, 0x61, 0x1E, 0xB4, 0x50, 0x04, 0xF6, 0xC2,
		0x16, 0x25, 0x86, 0x56, 0x55, 0x09, 0xBE, 0x91
	];
	//==============================================================
	private const M0 = [
		0xBCBC3275, 0xECEC21F3, 0x202043C6, 0xB3B3C9F4, 0xDADA03DB, 0x02028B7B, 0xE2E22BFB, 0x9E9EFAC8,
		0xC9C9EC4A, 0xD4D409D3, 0x18186BE6, 0x1E1E9F6B, 0x98980E45, 0xB2B2387D, 0xA6A6D2E8, 0x2626B74B,
		0x3C3C57D6, 0x93938A32, 0x8282EED8, 0x525298FD, 0x7B7BD437, 0xBBBB3771, 0x5B5B97F1, 0x474783E1,
		0x24243C30, 0x5151E20F, 0xBABAC6F8, 0x4A4AF31B, 0xBFBF4887, 0x0D0D70FA, 0xB0B0B306, 0x7575DE3F,
		0xD2D2FD5E, 0x7D7D20BA, 0x666631AE, 0x3A3AA35B, 0x59591C8A, 0x00000000, 0xCDCD93BC, 0x1A1AE09D,
		0xAEAE2C6D, 0x7F7FABC1, 0x2B2BC7B1, 0xBEBEB90E, 0xE0E0A080, 0x8A8A105D, 0x3B3B52D2, 0x6464BAD5,
		0xD8D888A0, 0xE7E7A584, 0x5F5FE807, 0x1B1B1114, 0x2C2CC2B5, 0xFCFCB490, 0x3131272C, 0x808065A3,
		0x73732AB2, 0x0C0C8173, 0x79795F4C, 0x6B6B4154, 0x4B4B0292, 0x53536974, 0x94948F36, 0x83831F51,
		0x2A2A3638, 0xC4C49CB0, 0x2222C8BD, 0xD5D5F85A, 0xBDBDC3FC, 0x48487860, 0xFFFFCE62, 0x4C4C0796,
		0x4141776C, 0xC7C7E642, 0xEBEB24F7, 0x1C1C1410, 0x5D5D637C, 0x36362228, 0x6767C027, 0xE9E9AF8C,
		0x4444F913, 0x1414EA95, 0xF5F5BB9C, 0xCFCF18C7, 0x3F3F2D24, 0xC0C0E346, 0x7272DB3B, 0x54546C70,
		0x29294CCA, 0xF0F035E3, 0x0808FE85, 0xC6C617CB, 0xF3F34F11, 0x8C8CE4D0, 0xA4A45993, 0xCACA96B8,
		0x68683BA6, 0xB8B84D83, 0x38382820, 0xE5E52EFF, 0xADAD569F, 0x0B0B8477, 0xC8C81DC3, 0x9999FFCC,
		0x5858ED03, 0x19199A6F, 0x0E0E0A08, 0x95957EBF, 0x70705040, 0xF7F730E7, 0x6E6ECF2B, 0x1F1F6EE2,
		0xB5B53D79, 0x09090F0C, 0x616134AA, 0x57571682, 0x9F9F0B41, 0x9D9D803A, 0x111164EA, 0x2525CDB9,
		0xAFAFDDE4, 0x4545089A, 0xDFDF8DA4, 0xA3A35C97, 0xEAEAD57E, 0x353558DA, 0xEDEDD07A, 0x4343FC17,
		0xF8F8CB66, 0xFBFBB194, 0x3737D3A1, 0xFAFA401D, 0xC2C2683D, 0xB4B4CCF0, 0x32325DDE, 0x9C9C71B3,
		0x5656E70B, 0xE3E3DA72, 0x878760A7, 0x15151B1C, 0xF9F93AEF, 0x6363BFD1, 0x3434A953, 0x9A9A853E,
		0xB1B1428F, 0x7C7CD133, 0x88889B26, 0x3D3DA65F, 0xA1A1D7EC, 0xE4E4DF76, 0x8181942A, 0x91910149,
		0x0F0FFB81, 0xEEEEAA88, 0x161661EE, 0xD7D77321, 0x9797F5C4, 0xA5A5A81A, 0xFEFE3FEB, 0x6D6DB5D9,
		0x7878AEC5, 0xC5C56D39, 0x1D1DE599, 0x7676A4CD, 0x3E3EDCAD, 0xCBCB6731, 0xB6B6478B, 0xEFEF5B01,
		0x12121E18, 0x6060C523, 0x6A6AB0DD, 0x4D4DF61F, 0xCECEE94E, 0xDEDE7C2D, 0x55559DF9, 0x7E7E5A48,
		0x2121B24F, 0x03037AF2, 0xA0A02665, 0x5E5E198E, 0x5A5A6678, 0x65654B5C, 0x62624E58, 0xFDFD4519,
		0x0606F48D, 0x404086E5, 0xF2F2BE98, 0x3333AC57, 0x17179067, 0x05058E7F, 0xE8E85E05, 0x4F4F7D64,
		0x89896AAF, 0x10109563, 0x74742FB6, 0x0A0A75FE, 0x5C5C92F5, 0x9B9B74B7, 0x2D2D333C, 0x3030D6A5,
		0x2E2E49CE, 0x494989E9, 0x46467268, 0x77775544, 0xA8A8D8E0, 0x9696044D, 0x2828BD43, 0xA9A92969,
		0xD9D97929, 0x8686912E, 0xD1D187AC, 0xF4F44A15, 0x8D8D1559, 0xD6D682A8, 0xB9B9BC0A, 0x42420D9E,
		0xF6F6C16E, 0x2F2FB847, 0xDDDD06DF, 0x23233934, 0xCCCC6235, 0xF1F1C46A, 0xC1C112CF, 0x8585EBDC,
		0x8F8F9E22, 0x7171A1C9, 0x9090F0C0, 0xAAAA539B, 0x0101F189, 0x8B8BE1D4, 0x4E4E8CED, 0x8E8E6FAB,
		0xABABA212, 0x6F6F3EA2, 0xE6E6540D, 0xDBDBF252, 0x92927BBB, 0xB7B7B602, 0x6969CA2F, 0x3939D9A9,
		0xD3D30CD7, 0xA7A72361, 0xA2A2AD1E, 0xC3C399B4, 0x6C6C4450, 0x07070504, 0x04047FF6, 0x272746C2,
		0xACACA716, 0xD0D07625, 0x50501386, 0xDCDCF756, 0x84841A55, 0xE1E15109, 0x7A7A25BE, 0x1313EF91
	];
	//==============================================================
	private const M1 = [
		0xA9D93939, 0x67901717, 0xB3719C9C, 0xE8D2A6A6, 0x04050707, 0xFD985252, 0xA3658080, 0x76DFE4E4,
		0x9A084545, 0x92024B4B, 0x80A0E0E0, 0x78665A5A, 0xE4DDAFAF, 0xDDB06A6A, 0xD1BF6363, 0x38362A2A,
		0x0D54E6E6, 0xC6432020, 0x3562CCCC, 0x98BEF2F2, 0x181E1212, 0xF724EBEB, 0xECD7A1A1, 0x6C774141,
		0x43BD2828, 0x7532BCBC, 0x37D47B7B, 0x269B8888, 0xFA700D0D, 0x13F94444, 0x94B1FBFB, 0x485A7E7E,
		0xF27A0303, 0xD0E48C8C, 0x8B47B6B6, 0x303C2424, 0x84A5E7E7, 0x54416B6B, 0xDF06DDDD, 0x23C56060,
		0x1945FDFD, 0x5BA33A3A, 0x3D68C2C2, 0x59158D8D, 0xF321ECEC, 0xAE316666, 0xA23E6F6F, 0x82165757,
		0x63951010, 0x015BEFEF, 0x834DB8B8, 0x2E918686, 0xD9B56D6D, 0x511F8383, 0x9B53AAAA, 0x7C635D5D,
		0xA63B6868, 0xEB3FFEFE, 0xA5D63030, 0xBE257A7A, 0x16A7ACAC, 0x0C0F0909, 0xE335F0F0, 0x6123A7A7,
		0xC0F09090, 0x8CAFE9E9, 0x3A809D9D, 0xF5925C5C, 0x73810C0C, 0x2C273131, 0x2576D0D0, 0x0BE75656,
		0xBB7B9292, 0x4EE9CECE, 0x89F10101, 0x6B9F1E1E, 0x53A93434, 0x6AC4F1F1, 0xB499C3C3, 0xF1975B5B,
		0xE1834747, 0xE66B1818, 0xBDC82222, 0x450E9898, 0xE26E1F1F, 0xF4C9B3B3, 0xB62F7474, 0x66CBF8F8,
		0xCCFF9999, 0x95EA1414, 0x03ED5858, 0x56F7DCDC, 0xD4E18B8B, 0x1C1B1515, 0x1EADA2A2, 0xD70CD3D3,
		0xFB2BE2E2, 0xC31DC8C8, 0x8E195E5E, 0xB5C22C2C, 0xE9894949, 0xCF12C1C1, 0xBF7E9595, 0xBA207D7D,
		0xEA641111, 0x77840B0B, 0x396DC5C5, 0xAF6A8989, 0x33D17C7C, 0xC9A17171, 0x62CEFFFF, 0x7137BBBB,
		0x81FB0F0F, 0x793DB5B5, 0x0951E1E1, 0xADDC3E3E, 0x242D3F3F, 0xCDA47676, 0xF99D5555, 0xD8EE8282,
		0xE5864040, 0xC5AE7878, 0xB9CD2525, 0x4D049696, 0x44557777, 0x080A0E0E, 0x86135050, 0xE730F7F7,
		0xA1D33737, 0x1D40FAFA, 0xAA346161, 0xED8C4E4E, 0x06B3B0B0, 0x706C5454, 0xB22A7373, 0xD2523B3B,
		0x410B9F9F, 0x7B8B0202, 0xA088D8D8, 0x114FF3F3, 0x3167CBCB, 0xC2462727, 0x27C06767, 0x90B4FCFC,
		0x20283838, 0xF67F0404, 0x60784848, 0xFF2EE5E5, 0x96074C4C, 0x5C4B6565, 0xB1C72B2B, 0xAB6F8E8E,
		0x9E0D4242, 0x9CBBF5F5, 0x52F2DBDB, 0x1BF34A4A, 0x5FA63D3D, 0x9359A4A4, 0x0ABCB9B9, 0xEF3AF9F9,
		0x91EF1313, 0x85FE0808, 0x49019191, 0xEE611616, 0x2D7CDEDE, 0x4FB22121, 0x8F42B1B1, 0x3BDB7272,
		0x47B82F2F, 0x8748BFBF, 0x6D2CAEAE, 0x46E3C0C0, 0xD6573C3C, 0x3E859A9A, 0x6929A9A9, 0x647D4F4F,
		0x2A948181, 0xCE492E2E, 0xCB17C6C6, 0x2FCA6969, 0xFCC3BDBD, 0x975CA3A3, 0x055EE8E8, 0x7AD0EDED,
		0xAC87D1D1, 0x7F8E0505, 0xD5BA6464, 0x1AA8A5A5, 0x4BB72626, 0x0EB9BEBE, 0xA7608787, 0x5AF8D5D5,
		0x28223636, 0x14111B1B, 0x3FDE7575, 0x2979D9D9, 0x88AAEEEE, 0x3C332D2D, 0x4C5F7979, 0x02B6B7B7,
		0xB896CACA, 0xDA583535, 0xB09CC4C4, 0x17FC4343, 0x551A8484, 0x1FF64D4D, 0x8A1C5959, 0x7D38B2B2,
		0x57AC3333, 0xC718CFCF, 0x8DF40606, 0x74695353, 0xB7749B9B, 0xC4F59797, 0x9F56ADAD, 0x72DAE3E3,
		0x7ED5EAEA, 0x154AF4F4, 0x229E8F8F, 0x12A2ABAB, 0x584E6262, 0x07E85F5F, 0x99E51D1D, 0x34392323,
		0x6EC1F6F6, 0x50446C6C, 0xDE5D3232, 0x68724646, 0x6526A0A0, 0xBC93CDCD, 0xDB03DADA, 0xF8C6BABA,
		0xC8FA9E9E, 0xA882D6D6, 0x2BCF6E6E, 0x40507070, 0xDCEB8585, 0xFE750A0A, 0x328A9393, 0xA48DDFDF,
		0xCA4C2929, 0x10141C1C, 0x2173D7D7, 0xF0CCB4B4, 0xD309D4D4, 0x5D108A8A, 0x0FE25151, 0x00000000,
		0x6F9A1919, 0x9DE01A1A, 0x368F9494, 0x42E6C7C7, 0x4AECC9C9, 0x5EFDD2D2, 0xC1AB7F7F, 0xE0D8A8A8
	];
	//==============================================================
	private const M2 = [
		0xBC75BC32, 0xECF3EC21, 0x20C62043, 0xB3F4B3C9, 0xDADBDA03, 0x027B028B, 0xE2FBE22B, 0x9EC89EFA,
		0xC94AC9EC, 0xD4D3D409, 0x18E6186B, 0x1E6B1E9F, 0x9845980E, 0xB27DB238, 0xA6E8A6D2, 0x264B26B7,
		0x3CD63C57, 0x9332938A, 0x82D882EE, 0x52FD5298, 0x7B377BD4, 0xBB71BB37, 0x5BF15B97, 0x47E14783,
		0x2430243C, 0x510F51E2, 0xBAF8BAC6, 0x4A1B4AF3, 0xBF87BF48, 0x0DFA0D70, 0xB006B0B3, 0x753F75DE,
		0xD25ED2FD, 0x7DBA7D20, 0x66AE6631, 0x3A5B3AA3, 0x598A591C, 0x00000000, 0xCDBCCD93, 0x1A9D1AE0,
		0xAE6DAE2C, 0x7FC17FAB, 0x2BB12BC7, 0xBE0EBEB9, 0xE080E0A0, 0x8A5D8A10, 0x3BD23B52, 0x64D564BA,
		0xD8A0D888, 0xE784E7A5, 0x5F075FE8, 0x1B141B11, 0x2CB52CC2, 0xFC90FCB4, 0x312C3127, 0x80A38065,
		0x73B2732A, 0x0C730C81, 0x794C795F, 0x6B546B41, 0x4B924B02, 0x53745369, 0x9436948F, 0x8351831F,
		0x2A382A36, 0xC4B0C49C, 0x22BD22C8, 0xD55AD5F8, 0xBDFCBDC3, 0x48604878, 0xFF62FFCE, 0x4C964C07,
		0x416C4177, 0xC742C7E6, 0xEBF7EB24, 0x1C101C14, 0x5D7C5D63, 0x36283622, 0x672767C0, 0xE98CE9AF,
		0x441344F9, 0x149514EA, 0xF59CF5BB, 0xCFC7CF18, 0x3F243F2D, 0xC046C0E3, 0x723B72DB, 0x5470546C,
		0x29CA294C, 0xF0E3F035, 0x088508FE, 0xC6CBC617, 0xF311F34F, 0x8CD08CE4, 0xA493A459, 0xCAB8CA96,
		0x68A6683B, 0xB883B84D, 0x38203828, 0xE5FFE52E, 0xAD9FAD56, 0x0B770B84, 0xC8C3C81D, 0x99CC99FF,
		0x580358ED, 0x196F199A, 0x0E080E0A, 0x95BF957E, 0x70407050, 0xF7E7F730, 0x6E2B6ECF, 0x1FE21F6E,
		0xB579B53D, 0x090C090F, 0x61AA6134, 0x57825716, 0x9F419F0B, 0x9D3A9D80, 0x11EA1164, 0x25B925CD,
		0xAFE4AFDD, 0x459A4508, 0xDFA4DF8D, 0xA397A35C, 0xEA7EEAD5, 0x35DA3558, 0xED7AEDD0, 0x431743FC,
		0xF866F8CB, 0xFB94FBB1, 0x37A137D3, 0xFA1DFA40, 0xC23DC268, 0xB4F0B4CC, 0x32DE325D, 0x9CB39C71,
		0x560B56E7, 0xE372E3DA, 0x87A78760, 0x151C151B, 0xF9EFF93A, 0x63D163BF, 0x345334A9, 0x9A3E9A85,
		0xB18FB142, 0x7C337CD1, 0x8826889B, 0x3D5F3DA6, 0xA1ECA1D7, 0xE476E4DF, 0x812A8194, 0x91499101,
		0x0F810FFB, 0xEE88EEAA, 0x16EE1661, 0xD721D773, 0x97C497F5, 0xA51AA5A8, 0xFEEBFE3F, 0x6DD96DB5,
		0x78C578AE, 0xC539C56D, 0x1D991DE5, 0x76CD76A4, 0x3EAD3EDC, 0xCB31CB67, 0xB68BB647, 0xEF01EF5B,
		0x1218121E, 0x602360C5, 0x6ADD6AB0, 0x4D1F4DF6, 0xCE4ECEE9, 0xDE2DDE7C, 0x55F9559D, 0x7E487E5A,
		0x214F21B2, 0x03F2037A, 0xA065A026, 0x5E8E5E19, 0x5A785A66, 0x655C654B, 0x6258624E, 0xFD19FD45,
		0x068D06F4, 0x40E54086, 0xF298F2BE, 0x335733AC, 0x17671790, 0x057F058E, 0xE805E85E, 0x4F644F7D,
		0x89AF896A, 0x10631095, 0x74B6742F, 0x0AFE0A75, 0x5CF55C92, 0x9BB79B74, 0x2D3C2D33, 0x30A530D6,
		0x2ECE2E49, 0x49E94989, 0x46684672, 0x77447755, 0xA8E0A8D8, 0x964D9604, 0x284328BD, 0xA969A929,
		0xD929D979, 0x862E8691, 0xD1ACD187, 0xF415F44A, 0x8D598D15, 0xD6A8D682, 0xB90AB9BC, 0x429E420D,
		0xF66EF6C1, 0x2F472FB8, 0xDDDFDD06, 0x23342339, 0xCC35CC62, 0xF16AF1C4, 0xC1CFC112, 0x85DC85EB,
		0x8F228F9E, 0x71C971A1, 0x90C090F0, 0xAA9BAA53, 0x018901F1, 0x8BD48BE1, 0x4EED4E8C, 0x8EAB8E6F,
		0xAB12ABA2, 0x6FA26F3E, 0xE60DE654, 0xDB52DBF2, 0x92BB927B, 0xB702B7B6, 0x692F69CA, 0x39A939D9,
		0xD3D7D30C, 0xA761A723, 0xA21EA2AD, 0xC3B4C399, 0x6C506C44, 0x07040705, 0x04F6047F, 0x27C22746,
		0xAC16ACA7, 0xD025D076, 0x50865013, 0xDC56DCF7, 0x8455841A, 0xE109E151, 0x7ABE7A25, 0x139113EF
	];
	//==============================================================
	private const M3 = [
		0xD939A9D9, 0x90176790, 0x719CB371, 0xD2A6E8D2, 0x05070405, 0x9852FD98, 0x6580A365, 0xDFE476DF,
		0x08459A08, 0x024B9202, 0xA0E080A0, 0x665A7866, 0xDDAFE4DD, 0xB06ADDB0, 0xBF63D1BF, 0x362A3836,
		0x54E60D54, 0x4320C643, 0x62CC3562, 0xBEF298BE, 0x1E12181E, 0x24EBF724, 0xD7A1ECD7, 0x77416C77,
		0xBD2843BD, 0x32BC7532, 0xD47B37D4, 0x9B88269B, 0x700DFA70, 0xF94413F9, 0xB1FB94B1, 0x5A7E485A,
		0x7A03F27A, 0xE48CD0E4, 0x47B68B47, 0x3C24303C, 0xA5E784A5, 0x416B5441, 0x06DDDF06, 0xC56023C5,
		0x45FD1945, 0xA33A5BA3, 0x68C23D68, 0x158D5915, 0x21ECF321, 0x3166AE31, 0x3E6FA23E, 0x16578216,
		0x95106395, 0x5BEF015B, 0x4DB8834D, 0x91862E91, 0xB56DD9B5, 0x1F83511F, 0x53AA9B53, 0x635D7C63,
		0x3B68A63B, 0x3FFEEB3F, 0xD630A5D6, 0x257ABE25, 0xA7AC16A7, 0x0F090C0F, 0x35F0E335, 0x23A76123,
		0xF090C0F0, 0xAFE98CAF, 0x809D3A80, 0x925CF592, 0x810C7381, 0x27312C27, 0x76D02576, 0xE7560BE7,
		0x7B92BB7B, 0xE9CE4EE9, 0xF10189F1, 0x9F1E6B9F, 0xA93453A9, 0xC4F16AC4, 0x99C3B499, 0x975BF197,
		0x8347E183, 0x6B18E66B, 0xC822BDC8, 0x0E98450E, 0x6E1FE26E, 0xC9B3F4C9, 0x2F74B62F, 0xCBF866CB,
		0xFF99CCFF, 0xEA1495EA, 0xED5803ED, 0xF7DC56F7, 0xE18BD4E1, 0x1B151C1B, 0xADA21EAD, 0x0CD3D70C,
		0x2BE2FB2B, 0x1DC8C31D, 0x195E8E19, 0xC22CB5C2, 0x8949E989, 0x12C1CF12, 0x7E95BF7E, 0x207DBA20,
		0x6411EA64, 0x840B7784, 0x6DC5396D, 0x6A89AF6A, 0xD17C33D1, 0xA171C9A1, 0xCEFF62CE, 0x37BB7137,
		0xFB0F81FB, 0x3DB5793D, 0x51E10951, 0xDC3EADDC, 0x2D3F242D, 0xA476CDA4, 0x9D55F99D, 0xEE82D8EE,
		0x8640E586, 0xAE78C5AE, 0xCD25B9CD, 0x04964D04, 0x55774455, 0x0A0E080A, 0x13508613, 0x30F7E730,
		0xD337A1D3, 0x40FA1D40, 0x3461AA34, 0x8C4EED8C, 0xB3B006B3, 0x6C54706C, 0x2A73B22A, 0x523BD252,
		0x0B9F410B, 0x8B027B8B, 0x88D8A088, 0x4FF3114F, 0x67CB3167, 0x4627C246, 0xC06727C0, 0xB4FC90B4,
		0x28382028, 0x7F04F67F, 0x78486078, 0x2EE5FF2E, 0x074C9607, 0x4B655C4B, 0xC72BB1C7, 0x6F8EAB6F,
		0x0D429E0D, 0xBBF59CBB, 0xF2DB52F2, 0xF34A1BF3, 0xA63D5FA6, 0x59A49359, 0xBCB90ABC, 0x3AF9EF3A,
		0xEF1391EF, 0xFE0885FE, 0x01914901, 0x6116EE61, 0x7CDE2D7C, 0xB2214FB2, 0x42B18F42, 0xDB723BDB,
		0xB82F47B8, 0x48BF8748, 0x2CAE6D2C, 0xE3C046E3, 0x573CD657, 0x859A3E85, 0x29A96929, 0x7D4F647D,
		0x94812A94, 0x492ECE49, 0x17C6CB17, 0xCA692FCA, 0xC3BDFCC3, 0x5CA3975C, 0x5EE8055E, 0xD0ED7AD0,
		0x87D1AC87, 0x8E057F8E, 0xBA64D5BA, 0xA8A51AA8, 0xB7264BB7, 0xB9BE0EB9, 0x6087A760, 0xF8D55AF8,
		0x22362822, 0x111B1411, 0xDE753FDE, 0x79D92979, 0xAAEE88AA, 0x332D3C33, 0x5F794C5F, 0xB6B702B6,
		0x96CAB896, 0x5835DA58, 0x9CC4B09C, 0xFC4317FC, 0x1A84551A, 0xF64D1FF6, 0x1C598A1C, 0x38B27D38,
		0xAC3357AC, 0x18CFC718, 0xF4068DF4, 0x69537469, 0x749BB774, 0xF597C4F5, 0x56AD9F56, 0xDAE372DA,
		0xD5EA7ED5, 0x4AF4154A, 0x9E8F229E, 0xA2AB12A2, 0x4E62584E, 0xE85F07E8, 0xE51D99E5, 0x39233439,
		0xC1F66EC1, 0x446C5044, 0x5D32DE5D, 0x72466872, 0x26A06526, 0x93CDBC93, 0x03DADB03, 0xC6BAF8C6,
		0xFA9EC8FA, 0x82D6A882, 0xCF6E2BCF, 0x50704050, 0xEB85DCEB, 0x750AFE75, 0x8A93328A, 0x8DDFA48D,
		0x4C29CA4C, 0x141C1014, 0x73D72173, 0xCCB4F0CC, 0x09D4D309, 0x108A5D10, 0xE2510FE2, 0x00000000,
		0x9A196F9A, 0xE01A9DE0, 0x8F94368F, 0xE6C742E6, 0xECC94AEC, 0xFDD25EFD, 0xAB7FC1AB, 0xD8A8E0D8
	];
	//==============================================================


} //END CLASS

/*** Sample Usage ; These are only sample Key/Iv (use your own) ; must use base64 encode/decode just like in the below example to preserve binary or unicode data !!
$tf = new SmartCryptoCiphersTwofishCBC('a-32-bytes-secret-key-abcdefghij', 'iv:2345;78654Rf/'); // the key must be 32 bytes (characters) / 256 bits ; the iv must be 16 bytes (characters) / 128 bits
$encrypted = (string) base64_encode((string)$tf->encrypt(base64_encode('this is some example plain text')));
$plaintext = (string) base64_decode((string)$tf->decrypt(base64_decode((string)$encrypted)));
echo 'plain text: '.$plaintext; // it should be: 'this is some example plain text'
die();
*/

//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

//--
// PHP implementation of the Blowfish algorithm in CBC mode
// It does not require any PHP extension, it uses only the core PHP.
// Class support encryption/decryption with a secret key and iv (CBC mode only).
//
// LICENSE: BSD, authors: Matthew Fonda <mfonda@php.net>, Philippe Jausions <jausions@php.net>
// (c) 2005-2008 Matthew Fonda
//
// Modified from by unixman (iradu@unix-world.org), contains many fixes and optimizations
// (c) 2015-present unix-world.org
//--

/**
 * Class:  Smart Crypto Ciphers :: Blowfish CBC
 * Provides a built-in based feature to handle the Blowfish 448-bit CBC encryption / decryption.
 *
 * This class is standard (blocksize 64 bit = 8 bytes) ; only supports CBC mode ; only supports key sizes as 448 bit (56 bytes) or 384 bit (48 bytes), lower keys not supported for security reasons ...
 *
 * @usage       dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 * @hints       Blowfish is a 64-bit (8 bytes) block cipher. Max Key is up to 56 chars length (56 bytes = 448 bits). The CBC mode requires a initialization vector (iv).
 *
 * @access 		private
 * @internal
 *
 * @depends     classes: Smart, SmartHashCrypto
 * @version     v.20231117
 *
 */
final class SmartCryptoCiphersBlowfishCBC {

	// ->

	private const BLOCK_SIZE = 8; 	// the Blowfish block size

	private $_P 	= []; 	// P-Array contains 18 32-bit subkeys
	private $_S 	= []; 	// Array of four S-Blocks each containing 256 32-bit entries

	private $_iv 	= null; // Initialization vector
	private $_key 	= '';	// the key

	private $_mver 	= null; // Crypto Package Mode Version: `v2` (current, encrypd/decrypt) ; or `v1` (deprecated, just decrypt)


	//==============================================================
	/**
	 * Constructor
	 * Initializes the blowfish cipher object and the secret key and vector
	 *
	 * @param string $key
	 * @access public
	 */
	public function __construct(string $key, string $iv, string $modever='v2') {
		//-- versions
		$modever = (string) strtolower((string)trim((string)$modever));
		switch((string)$modever) { // {{{SYNC-CRYPTO-MODE-VERSIONS}}}
			case 'v3': // current
				$modever = 'v2'; // BF v3 is just virtual, use the same v2 algo inside BF class
				break;
			case 'v2':
			case 'v1': // deprecated, just decode
				break;
			default:
				Smart::log_warning(__METHOD__.' # Invalid Mode Version: `'.$modever.'` # Fallback to v2 ...');
				$modever = 'v2';
		} //end switch
		$this->_mver = (string) $modever;
		//-- Blowfish uses 64-bit blocks, a variable key size (ranging from 32 to 448 bits = 4 to 56 bytes[characters]) and an initialization vector (IV, 64bit = 8 bytes[characters])
		if((string)$this->_mver == 'v1') { // v1 ; will only allow decrypt to support
			if(
				((string)trim((string)$key) == '')
				OR
				((int)strlen((string)$key) !== 48)
				OR
				((int)strlen((string)trim((string)$key)) !== 48)
			) {
				if(SmartEnvironment::ifInternalDebug()) {
					if(SmartEnvironment::ifDebug()) {
						Smart::log_notice(__METHOD__.' # Key (48) is too short: `'.$key.'`');
					} //end if
				} //end if
				Smart::raise_error(__METHOD__.' # Invalid (48) Key, must be exactly 48 bytes (384 bits)');
				return;
			} //end if
		} else { // v2
			if(
				((string)trim((string)$key) == '')
				OR
				((int)strlen((string)$key) !== 56)
				OR
				((int)strlen((string)trim((string)$key)
			) !== 56)) {
				if(SmartEnvironment::ifInternalDebug()) {
					if(SmartEnvironment::ifDebug()) {
						Smart::log_notice(__METHOD__.' # Key (56) is too short: `'.$key.'`');
					} //end if
				} //end if
				Smart::raise_error(__METHOD__.' # Invalid Key (56), must be exactly 56 bytes (448 bits)');
				return;
			} //end if
		} //end if
		if(
			((string)trim((string)$iv) == '')
			OR
			((int)strlen((string)$iv) !== (int)self::BLOCK_SIZE)
			OR
			((int)strlen((string)trim((string)$iv)) !== 8)
		) {
			Smart::raise_error(__METHOD__.' # Invalid iV, must be exactly 8 bytes (64 bits)');
			if(SmartEnvironment::ifInternalDebug()) {
				if(SmartEnvironment::ifDebug()) {
					Smart::log_notice(__METHOD__.' # iV (8) is too short: `'.$iv.'`');
				} //end if
			} //end if
			return;
		} //end if
		//--
		$this->_key = (string) $key; 	// Blowfish448 key: 448 bits = 56 bytes ; {{{SYNC-BLOWFISH-KEY}}} ; for v1 (which supports decrypt only) must be 384 bits = 48 bytes
		$this->_iv 	= (string) $iv; 	// Blowfish448 iv:   64 bits =  8 bytes ; {{{SYNC-BLOWFISH-IV}}}
		//--
		$this->init();
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Encrypts a string in CBC Mode using Blowfish cipher algo
	 *
	 * $b64DataOrPak needs to be base64 encoded or similar because will be padded with additional spaces (CBC) until the length of the block size
	 * The idea of encoding B64 (or similar) the data is because this algorithm does not support multi-byte strings (ex: UTF-8) ; also some binary data may be broken because of internal null trimming
	 *
	 * @param string $b64DataOrPak
	 * @return string Returns cipher text
	 */
	public function encrypt(?string $b64DataOrPak) : string {

		//-- {{{SYNC-CRYPTO-DATA-ENCAPSULATE-B64}}}
		// expects: B64 data or B64 / HEX pak ; req. for safe padding !
		// why B64 or package data ? just because safe padding requires a capsule like B64/HEX
		// why not B64 encode here ? just because it can be a package as B64#signature and don't want to manage signatures here ...
		//--

		//--
		if((string)$this->_mver == 'v2') { // v2
			// OK ; this will remain as is at v2 for use with Javascript
		} else { // v1 is no more supported for encrypt ; v3 will be used for twfish and new generation of ciphers only
			return ''; // not supported
		} //end if else
		//--

		//--
		if((string)trim((string)$this->_key) == '') {
			Smart::log_warning(__METHOD__.' # Crypto Key is Empty ('.$this->_mver.'/-)');
			return '';
		} //end if
		if((string)trim((string)$this->_iv) == '') {
			Smart::log_warning(__METHOD__.' # Crypto iV is Empty ('.$this->_mver.'/-)');
			return '';
		} //end if
		//-- disallow encrypt with a key lower than 56 bytes (448 bit) since v2 !!
		if((int)strlen((string)trim((string)$this->_key)) !== 56) { // {{{SYNC-BLOWFISH-KEY}}}
			Smart::log_warning(__METHOD__.' # Invalid Key, must be exactly 56 bytes (448 bit) ('.$this->_mver.'/-)'); // for encrypt, enforce this
			return '';
		} //end if
		//-- disallow encrypt with a iv lower than 8 bytes (64 bit) since v2 !!
		if((int)strlen((string)trim((string)$this->_iv)) !== 8) { // {{{SYNC-BLOWFISH-IV}}}
			Smart::log_warning(__METHOD__.' # Invalid iV, must be exactly 8 bytes (64 bit) ('.$this->_mver.'/-)'); // for encrypt, enforce this
			return '';
		} //end if
		//--

		//--
		if((string)$b64DataOrPak == '') { // do not test with trim, can be various data !
			return '';
		} //end if
		//--

		//--
		//== ALGO: START
		$rawCryptoData = ''; // init
		//-- Blowfish is a 64-bit block cipher. This means that the data must be provided in units that are a multiple of 8 bytes
		$padding = (int) ((int)ceil((int)strlen((string)$b64DataOrPak) / (int)self::BLOCK_SIZE) * (int)self::BLOCK_SIZE); // blowfish blocksize is 8 ; {{{SYNC-ENCRYPTY-B64-PADDING}}}
		$b64DataOrPak = (string) str_pad((string)$b64DataOrPak, (int)$padding, ' ', STR_PAD_RIGHT); // unixman (pad with spaces), safe for B64/HEX or package ; padding with NULL is not safe and if it would be no package and last character is null trimming will break data ...
		//--
		//==
		//--
		$len = (int) strlen((string)$b64DataOrPak);
	//	$b64DataOrPak .= (string) str_repeat((string)chr(0), ((int)self::BLOCK_SIZE - ((int)$len % (int)self::BLOCK_SIZE)) % (int)self::BLOCK_SIZE); // fix, by unixman: no need to pad again with NULL, padded above with spaces ...
		$arx = unpack('N2', (string)substr((string)$b64DataOrPak, 0, 8) ^ (string)$this->_iv); // do not cast !
		if((int)Smart::array_size($arx) <= 0) {
			Smart::log_warning(__METHOD__.' # Setup Failed: UNPACK / Data');
			return '';
		} //end if
		if(!array_key_exists(0, $arx)) {
			$arx[0] = null; // fix for PHP8 ; array may have elements: 1, 2 but key 0 is missing ...
		} //end if
		list($kk, $Xl, $Xr) = $arx; // FIX to be compatible with the upcoming PHP 7
		$arx = null;
		list($Xl, $Xr) = (array) $this->_encipher($Xl, $Xr);
		$rawCryptoData .= (string) pack('N2', $Xl, $Xr);
		for($i=8; $i<$len; $i+=8) {
			$arx = unpack('N2', (string)substr((string)$b64DataOrPak, (int)$i, 8) ^ substr((string)$rawCryptoData, (int)$i - 8, 8)); // do not cast !
			if((int)Smart::array_size($arx) <= 0) {
				Smart::log_warning(__METHOD__.' # Setup Failed: UNPACK / Data ['.(int)$i.']');
				return '';
			} //end if
			if(!array_key_exists(0, $arx)) {
				$arx[0] = null; // fix for PHP8 ; array may have elements: 1, 2 but key 0 is missing ...
			} //end if
			list($kk, $Xl, $Xr) = $arx; // FIX to be compatible with the upcoming PHP 7
			$arx = null;
			list($Xl, $Xr) = (array) $this->_encipher($Xl, $Xr);
			$rawCryptoData .= (string) pack('N2', $Xl, $Xr);
		} //end for
		$b64DataOrPak = null; // free mem
		//--
		// DO NOT TRIM $rawCryptoData ! IT IS RAW CRYPTO DATA
		//== ALGO: #END
		//--

		//--
		return (string) $rawCryptoData; // raw encrypted data ; DO NOT TRIM !!
		//--

	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Decrypts a string in CBC Mode using Blowfish cipher algo
	 *
	 * If strlen($ciphertext) is not a multiple of the block size, null bytes will be added to the end of the string until it is.
	 * If data was passed B64 or similar to the encrypt method above, after using this method data needs to be base64 decoded or similar
	 *
	 * @param string $cipherText
	 * @return string Returns plain text
	 */
	public function decrypt(?string $cipherText) : string {

		//--
		if(
			((string)$this->_mver == 'v1') // v1
			OR
			((string)$this->_mver == 'v2') // v2
		) {
			// OK
		} else {
			return ''; // not supported
		} //end if else
		//--

		//--
		if((string)trim((string)$this->_key) == '') {
			Smart::log_warning(__METHOD__.' # Crypto Key is Empty ('.$this->_mver.'/-)');
			return '';
		} //end if
		if((string)trim((string)$this->_iv) == '') {
			Smart::log_warning(__METHOD__.' # Crypto iV is Empty ('.$this->_mver.'/-)');
			return '';
		} //end if
		//--

		//--
		if((string)$cipherText == '') { // do not test with trim, may be binary data !
			return '';
		} //end if
		//--

		//--
		$plainText = ''; // init, clear
		//--
		//== START: DECRYPT ALGO BF.CBC
		//--
		$len = (int) strlen((string)$cipherText);
		$cipherText .= (string) str_repeat((string)chr(0), (8 - ((int)$len % 8)) % 8);
		$arx = unpack('N2', substr((string)$cipherText, 0, 8)); // do not cast !
		if((int)Smart::array_size($arx) <= 0) {
			Smart::log_warning(__METHOD__.' # Setup Failed: UNPACK / Data');
			return '';
		} //end if
		if(!array_key_exists(0, $arx)) {
			$arx[0] = null; // fix for PHP8 ; array may have elements: 1, 2 but key 0 is missing ...
		} //end if
		list($kk, $Xl, $Xr) = $arx; // FIX to be compatible with the upcoming PHP 7
		$arx = null;
		list($Xl, $Xr) = (array) $this->_decipher($Xl, $Xr);
		//--
		$plainText .= (string) ((string)pack('N2', $Xl, $Xr) ^ (string)$this->_iv);
		for($i = 8; $i < $len; $i += 8) {
			$arx = unpack('N2', (string)substr((string)$cipherText, (int)$i, 8)); // do not cast !
			if((int)Smart::array_size($arx) <= 0) {
				Smart::log_warning(__METHOD__.' # Setup Failed: UNPACK / Data ['.(int)$i.']');
				return '';
			} //end if
			if(!array_key_exists(0, $arx)) {
				$arx[0] = null; // fix for PHP8 ; array may have elements: 1, 2 but key 0 is missing ...
			} //end if
			list($kk, $Xl, $Xr) = $arx; // FIX to be compatible with the upcoming PHP 7
			$arx = null;
			list($Xl, $Xr) = (array) $this->_decipher($Xl, $Xr);
			$plainText .= (string) ((string)pack('N2', $Xl, $Xr) ^ (string)substr((string)$cipherText, (int)$i - 8, 8));
		} //end for
		//--
		//== #END: DECRYPT ALGO BF.CBC
		//--
		$plainText = (string) trim((string)$plainText); // expects B64 data or B64 pak ; {{{SYNC-CRYPTO-DECRYPT-TRIM-B64}}}
		//--
		// why not B64 decode here ? just because it can be a package as B64#signature and don't want to manage signatures here ...
		// pf B64 decoded and have a trailing #signature, that is lost ... can't be verified later
		//--

		//--
		return (string) $plainText; // B64 data or B64 pak
		//--

	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Sets the secret key
	 * The key must be non-zero, and less than or equal to
	 * 56 characters in length.
	 *
	 * @param string $key
	 * @return bool  Returns true on success
	 * @access public
	 */
	private function init() : bool {
		//--
		$key = (string) $this->_key;
		$len = (int) strlen((string)$key);
		//--
		$this->_init();
		//--
		$k = 0;
		$data = 0;
		$datal = 0;
		$datar = 0;
		//--
		for($i=0; $i<18; $i++) {
			$data = 0;
			for($j=4; $j>0; $j--) {
				$data = $data << 8 | ord($key[$k]); // fix for PHP 7.4
				$k = ($k+1) % $len;
			} //end for
			$this->_P[$i] ^= $data;
		} //end for
		//--
		for($i=0; $i<=16; $i+=2) {
			list($datal, $datar) = (array) $this->_encipher($datal, $datar);
			$this->_P[$i] = $datal;
			$this->_P[$i+1] = $datar;
		} //end for
		//--
		for($i=0; $i<256; $i+=2) {
			list($datal, $datar) = (array) $this->_encipher($datal, $datar);
			$this->_S[0][$i] = $datal;
			$this->_S[0][$i+1] = $datar;
		} //end for
		//--
		for($i=0; $i<256; $i+=2) {
			list($datal, $datar) = (array) $this->_encipher($datal, $datar);
			$this->_S[1][$i] = $datal;
			$this->_S[1][$i+1] = $datar;
		} //end for
		//--
		for($i=0; $i<256; $i+=2) {
			list($datal, $datar) = (array) $this->_encipher($datal, $datar);
			$this->_S[2][$i] = $datal;
			$this->_S[2][$i+1] = $datar;
		} //end for
		//--
		for($i=0; $i<256; $i+=2) {
			list($datal, $datar) = (array) $this->_encipher($datal, $datar);
			$this->_S[3][$i] = $datal;
			$this->_S[3][$i+1] = $datar;
		} //end for
		//--
		return true;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Enciphers a single 64 bit block
	 *
	 * @param int &$Xl
	 * @param int &$Xr
	 * @access private
	 */
	private function _encipher(int $Xl, int $Xr) : array {
		//--
		for($i = 0; $i < 16; $i++) {
			$temp = $Xl ^ $this->_P[$i];
			$Xl = ((($this->_S[0][($temp>>24) & 255] + $this->_S[1][($temp>>16) & 255]) ^ $this->_S[2][($temp>>8) & 255]) + $this->_S[3][$temp & 255]) ^ $Xr;
			$Xr = $temp;
		} //end for
		//--
		$Xr = $Xl   ^ $this->_P[16];
		$Xl = $temp ^ $this->_P[17];
		//--
		return [ $Xl, $Xr ];
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Deciphers a single 64 bit block
	 *
	 * @param int &$Xl
	 * @param int &$Xr
	 * @access private
	 */
	private function _decipher(int $Xl, int $Xr) : array {
		//--
		for($i = 17; $i > 1; $i--) {
			$temp = $Xl ^ $this->_P[$i];
			$Xl = ((($this->_S[0][($temp>>24) & 255] + $this->_S[1][($temp>>16) & 255]) ^ $this->_S[2][($temp>>8) & 255]) + $this->_S[3][$temp & 255]) ^ $Xr;
			$Xr = $temp;
		} //end for
		//--
		$Xr = $Xl   ^ $this->_P[1];
		$Xl = $temp ^ $this->_P[0];
		//--
		return [ $Xl, $Xr ];
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Initializes the blowfish cipher object
	 *
	 * @access private
	 */
	private function _init() : void {
		//--
		$this->_P = (array) self::BLOWFISH_BOXES_P;
		//--
		$this->_S = (array) self::BLOWFISH_BOXES_S;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	private const BLOWFISH_BOXES_P = [
		0x243F6A88, 0x85A308D3, 0x13198A2E, 0x03707344, 0xA4093822, 0x299F31D0,
		0x082EFA98, 0xEC4E6C89, 0x452821E6, 0x38D01377, 0xBE5466CF, 0x34E90C6C,
		0xC0AC29B7, 0xC97C50DD, 0x3F84D5B5, 0xB5470917, 0x9216D5D9, 0x8979FB1B
	];
	//==============================================================
	private const BLOWFISH_BOXES_S = [
		[
			0xD1310BA6, 0x98DFB5AC, 0x2FFD72DB, 0xD01ADFB7, 0xB8E1AFED, 0x6A267E96, 0xBA7C9045, 0xF12C7F99,
			0x24A19947, 0xB3916CF7, 0x0801F2E2, 0x858EFC16, 0x636920D8, 0x71574E69, 0xA458FEA3, 0xF4933D7E,
			0x0D95748F, 0x728EB658, 0x718BCD58, 0x82154AEE, 0x7B54A41D, 0xC25A59B5, 0x9C30D539, 0x2AF26013,
			0xC5D1B023, 0x286085F0, 0xCA417918, 0xB8DB38EF, 0x8E79DCB0, 0x603A180E, 0x6C9E0E8B, 0xB01E8A3E,
			0xD71577C1, 0xBD314B27, 0x78AF2FDA, 0x55605C60, 0xE65525F3, 0xAA55AB94, 0x57489862, 0x63E81440,
			0x55CA396A, 0x2AAB10B6, 0xB4CC5C34, 0x1141E8CE, 0xA15486AF, 0x7C72E993, 0xB3EE1411, 0x636FBC2A,
			0x2BA9C55D, 0x741831F6, 0xCE5C3E16, 0x9B87931E, 0xAFD6BA33, 0x6C24CF5C, 0x7A325381, 0x28958677,
			0x3B8F4898, 0x6B4BB9AF, 0xC4BFE81B, 0x66282193, 0x61D809CC, 0xFB21A991, 0x487CAC60, 0x5DEC8032,
			0xEF845D5D, 0xE98575B1, 0xDC262302, 0xEB651B88, 0x23893E81, 0xD396ACC5, 0x0F6D6FF3, 0x83F44239,
			0x2E0B4482, 0xA4842004, 0x69C8F04A, 0x9E1F9B5E, 0x21C66842, 0xF6E96C9A, 0x670C9C61, 0xABD388F0,
			0x6A51A0D2, 0xD8542F68, 0x960FA728, 0xAB5133A3, 0x6EEF0B6C, 0x137A3BE4, 0xBA3BF050, 0x7EFB2A98,
			0xA1F1651D, 0x39AF0176, 0x66CA593E, 0x82430E88, 0x8CEE8619, 0x456F9FB4, 0x7D84A5C3, 0x3B8B5EBE,
			0xE06F75D8, 0x85C12073, 0x401A449F, 0x56C16AA6, 0x4ED3AA62, 0x363F7706, 0x1BFEDF72, 0x429B023D,
			0x37D0D724, 0xD00A1248, 0xDB0FEAD3, 0x49F1C09B, 0x075372C9, 0x80991B7B, 0x25D479D8, 0xF6E8DEF7,
			0xE3FE501A, 0xB6794C3B, 0x976CE0BD, 0x04C006BA, 0xC1A94FB6, 0x409F60C4, 0x5E5C9EC2, 0x196A2463,
			0x68FB6FAF, 0x3E6C53B5, 0x1339B2EB, 0x3B52EC6F, 0x6DFC511F, 0x9B30952C, 0xCC814544, 0xAF5EBD09,
			0xBEE3D004, 0xDE334AFD, 0x660F2807, 0x192E4BB3, 0xC0CBA857, 0x45C8740F, 0xD20B5F39, 0xB9D3FBDB,
			0x5579C0BD, 0x1A60320A, 0xD6A100C6, 0x402C7279, 0x679F25FE, 0xFB1FA3CC, 0x8EA5E9F8, 0xDB3222F8,
			0x3C7516DF, 0xFD616B15, 0x2F501EC8, 0xAD0552AB, 0x323DB5FA, 0xFD238760, 0x53317B48, 0x3E00DF82,
			0x9E5C57BB, 0xCA6F8CA0, 0x1A87562E, 0xDF1769DB, 0xD542A8F6, 0x287EFFC3, 0xAC6732C6, 0x8C4F5573,
			0x695B27B0, 0xBBCA58C8, 0xE1FFA35D, 0xB8F011A0, 0x10FA3D98, 0xFD2183B8, 0x4AFCB56C, 0x2DD1D35B,
			0x9A53E479, 0xB6F84565, 0xD28E49BC, 0x4BFB9790, 0xE1DDF2DA, 0xA4CB7E33, 0x62FB1341, 0xCEE4C6E8,
			0xEF20CADA, 0x36774C01, 0xD07E9EFE, 0x2BF11FB4, 0x95DBDA4D, 0xAE909198, 0xEAAD8E71, 0x6B93D5A0,
			0xD08ED1D0, 0xAFC725E0, 0x8E3C5B2F, 0x8E7594B7, 0x8FF6E2FB, 0xF2122B64, 0x8888B812, 0x900DF01C,
			0x4FAD5EA0, 0x688FC31C, 0xD1CFF191, 0xB3A8C1AD, 0x2F2F2218, 0xBE0E1777, 0xEA752DFE, 0x8B021FA1,
			0xE5A0CC0F, 0xB56F74E8, 0x18ACF3D6, 0xCE89E299, 0xB4A84FE0, 0xFD13E0B7, 0x7CC43B81, 0xD2ADA8D9,
			0x165FA266, 0x80957705, 0x93CC7314, 0x211A1477, 0xE6AD2065, 0x77B5FA86, 0xC75442F5, 0xFB9D35CF,
			0xEBCDAF0C, 0x7B3E89A0, 0xD6411BD3, 0xAE1E7E49, 0x00250E2D, 0x2071B35E, 0x226800BB, 0x57B8E0AF,
			0x2464369B, 0xF009B91E, 0x5563911D, 0x59DFA6AA, 0x78C14389, 0xD95A537F, 0x207D5BA2, 0x02E5B9C5,
			0x83260376, 0x6295CFA9, 0x11C81968, 0x4E734A41, 0xB3472DCA, 0x7B14A94A, 0x1B510052, 0x9A532915,
			0xD60F573F, 0xBC9BC6E4, 0x2B60A476, 0x81E67400, 0x08BA6FB5, 0x571BE91F, 0xF296EC6B, 0x2A0DD915,
			0xB6636521, 0xE7B9F9B6, 0xFF34052E, 0xC5855664, 0x53B02D5D, 0xA99F8FA1, 0x08BA4799, 0x6E85076A
		],
		[
			0x4B7A70E9, 0xB5B32944, 0xDB75092E, 0xC4192623, 0xAD6EA6B0, 0x49A7DF7D, 0x9CEE60B8, 0x8FEDB266,
			0xECAA8C71, 0x699A17FF, 0x5664526C, 0xC2B19EE1, 0x193602A5, 0x75094C29, 0xA0591340, 0xE4183A3E,
			0x3F54989A, 0x5B429D65, 0x6B8FE4D6, 0x99F73FD6, 0xA1D29C07, 0xEFE830F5, 0x4D2D38E6, 0xF0255DC1,
			0x4CDD2086, 0x8470EB26, 0x6382E9C6, 0x021ECC5E, 0x09686B3F, 0x3EBAEFC9, 0x3C971814, 0x6B6A70A1,
			0x687F3584, 0x52A0E286, 0xB79C5305, 0xAA500737, 0x3E07841C, 0x7FDEAE5C, 0x8E7D44EC, 0x5716F2B8,
			0xB03ADA37, 0xF0500C0D, 0xF01C1F04, 0x0200B3FF, 0xAE0CF51A, 0x3CB574B2, 0x25837A58, 0xDC0921BD,
			0xD19113F9, 0x7CA92FF6, 0x94324773, 0x22F54701, 0x3AE5E581, 0x37C2DADC, 0xC8B57634, 0x9AF3DDA7,
			0xA9446146, 0x0FD0030E, 0xECC8C73E, 0xA4751E41, 0xE238CD99, 0x3BEA0E2F, 0x3280BBA1, 0x183EB331,
			0x4E548B38, 0x4F6DB908, 0x6F420D03, 0xF60A04BF, 0x2CB81290, 0x24977C79, 0x5679B072, 0xBCAF89AF,
			0xDE9A771F, 0xD9930810, 0xB38BAE12, 0xDCCF3F2E, 0x5512721F, 0x2E6B7124, 0x501ADDE6, 0x9F84CD87,
			0x7A584718, 0x7408DA17, 0xBC9F9ABC, 0xE94B7D8C, 0xEC7AEC3A, 0xDB851DFA, 0x63094366, 0xC464C3D2,
			0xEF1C1847, 0x3215D908, 0xDD433B37, 0x24C2BA16, 0x12A14D43, 0x2A65C451, 0x50940002, 0x133AE4DD,
			0x71DFF89E, 0x10314E55, 0x81AC77D6, 0x5F11199B, 0x043556F1, 0xD7A3C76B, 0x3C11183B, 0x5924A509,
			0xF28FE6ED, 0x97F1FBFA, 0x9EBABF2C, 0x1E153C6E, 0x86E34570, 0xEAE96FB1, 0x860E5E0A, 0x5A3E2AB3,
			0x771FE71C, 0x4E3D06FA, 0x2965DCB9, 0x99E71D0F, 0x803E89D6, 0x5266C825, 0x2E4CC978, 0x9C10B36A,
			0xC6150EBA, 0x94E2EA78, 0xA5FC3C53, 0x1E0A2DF4, 0xF2F74EA7, 0x361D2B3D, 0x1939260F, 0x19C27960,
			0x5223A708, 0xF71312B6, 0xEBADFE6E, 0xEAC31F66, 0xE3BC4595, 0xA67BC883, 0xB17F37D1, 0x018CFF28,
			0xC332DDEF, 0xBE6C5AA5, 0x65582185, 0x68AB9802, 0xEECEA50F, 0xDB2F953B, 0x2AEF7DAD, 0x5B6E2F84,
			0x1521B628, 0x29076170, 0xECDD4775, 0x619F1510, 0x13CCA830, 0xEB61BD96, 0x0334FE1E, 0xAA0363CF,
			0xB5735C90, 0x4C70A239, 0xD59E9E0B, 0xCBAADE14, 0xEECC86BC, 0x60622CA7, 0x9CAB5CAB, 0xB2F3846E,
			0x648B1EAF, 0x19BDF0CA, 0xA02369B9, 0x655ABB50, 0x40685A32, 0x3C2AB4B3, 0x319EE9D5, 0xC021B8F7,
			0x9B540B19, 0x875FA099, 0x95F7997E, 0x623D7DA8, 0xF837889A, 0x97E32D77, 0x11ED935F, 0x16681281,
			0x0E358829, 0xC7E61FD6, 0x96DEDFA1, 0x7858BA99, 0x57F584A5, 0x1B227263, 0x9B83C3FF, 0x1AC24696,
			0xCDB30AEB, 0x532E3054, 0x8FD948E4, 0x6DBC3128, 0x58EBF2EF, 0x34C6FFEA, 0xFE28ED61, 0xEE7C3C73,
			0x5D4A14D9, 0xE864B7E3, 0x42105D14, 0x203E13E0, 0x45EEE2B6, 0xA3AAABEA, 0xDB6C4F15, 0xFACB4FD0,
			0xC742F442, 0xEF6ABBB5, 0x654F3B1D, 0x41CD2105, 0xD81E799E, 0x86854DC7, 0xE44B476A, 0x3D816250,
			0xCF62A1F2, 0x5B8D2646, 0xFC8883A0, 0xC1C7B6A3, 0x7F1524C3, 0x69CB7492, 0x47848A0B, 0x5692B285,
			0x095BBF00, 0xAD19489D, 0x1462B174, 0x23820E00, 0x58428D2A, 0x0C55F5EA, 0x1DADF43E, 0x233F7061,
			0x3372F092, 0x8D937E41, 0xD65FECF1, 0x6C223BDB, 0x7CDE3759, 0xCBEE7460, 0x4085F2A7, 0xCE77326E,
			0xA6078084, 0x19F8509E, 0xE8EFD855, 0x61D99735, 0xA969A7AA, 0xC50C06C2, 0x5A04ABFC, 0x800BCADC,
			0x9E447A2E, 0xC3453484, 0xFDD56705, 0x0E1E9EC9, 0xDB73DBD3, 0x105588CD, 0x675FDA79, 0xE3674340,
			0xC5C43465, 0x713E38D8, 0x3D28F89E, 0xF16DFF20, 0x153E21E7, 0x8FB03D4A, 0xE6E39F2B, 0xDB83ADF7
		],
		[
			0xE93D5A68, 0x948140F7, 0xF64C261C, 0x94692934, 0x411520F7, 0x7602D4F7, 0xBCF46B2E, 0xD4A20068,
			0xD4082471, 0x3320F46A, 0x43B7D4B7, 0x500061AF, 0x1E39F62E, 0x97244546, 0x14214F74, 0xBF8B8840,
			0x4D95FC1D, 0x96B591AF, 0x70F4DDD3, 0x66A02F45, 0xBFBC09EC, 0x03BD9785, 0x7FAC6DD0, 0x31CB8504,
			0x96EB27B3, 0x55FD3941, 0xDA2547E6, 0xABCA0A9A, 0x28507825, 0x530429F4, 0x0A2C86DA, 0xE9B66DFB,
			0x68DC1462, 0xD7486900, 0x680EC0A4, 0x27A18DEE, 0x4F3FFEA2, 0xE887AD8C, 0xB58CE006, 0x7AF4D6B6,
			0xAACE1E7C, 0xD3375FEC, 0xCE78A399, 0x406B2A42, 0x20FE9E35, 0xD9F385B9, 0xEE39D7AB, 0x3B124E8B,
			0x1DC9FAF7, 0x4B6D1856, 0x26A36631, 0xEAE397B2, 0x3A6EFA74, 0xDD5B4332, 0x6841E7F7, 0xCA7820FB,
			0xFB0AF54E, 0xD8FEB397, 0x454056AC, 0xBA489527, 0x55533A3A, 0x20838D87, 0xFE6BA9B7, 0xD096954B,
			0x55A867BC, 0xA1159A58, 0xCCA92963, 0x99E1DB33, 0xA62A4A56, 0x3F3125F9, 0x5EF47E1C, 0x9029317C,
			0xFDF8E802, 0x04272F70, 0x80BB155C, 0x05282CE3, 0x95C11548, 0xE4C66D22, 0x48C1133F, 0xC70F86DC,
			0x07F9C9EE, 0x41041F0F, 0x404779A4, 0x5D886E17, 0x325F51EB, 0xD59BC0D1, 0xF2BCC18F, 0x41113564,
			0x257B7834, 0x602A9C60, 0xDFF8E8A3, 0x1F636C1B, 0x0E12B4C2, 0x02E1329E, 0xAF664FD1, 0xCAD18115,
			0x6B2395E0, 0x333E92E1, 0x3B240B62, 0xEEBEB922, 0x85B2A20E, 0xE6BA0D99, 0xDE720C8C, 0x2DA2F728,
			0xD0127845, 0x95B794FD, 0x647D0862, 0xE7CCF5F0, 0x5449A36F, 0x877D48FA, 0xC39DFD27, 0xF33E8D1E,
			0x0A476341, 0x992EFF74, 0x3A6F6EAB, 0xF4F8FD37, 0xA812DC60, 0xA1EBDDF8, 0x991BE14C, 0xDB6E6B0D,
			0xC67B5510, 0x6D672C37, 0x2765D43B, 0xDCD0E804, 0xF1290DC7, 0xCC00FFA3, 0xB5390F92, 0x690FED0B,
			0x667B9FFB, 0xCEDB7D9C, 0xA091CF0B, 0xD9155EA3, 0xBB132F88, 0x515BAD24, 0x7B9479BF, 0x763BD6EB,
			0x37392EB3, 0xCC115979, 0x8026E297, 0xF42E312D, 0x6842ADA7, 0xC66A2B3B, 0x12754CCC, 0x782EF11C,
			0x6A124237, 0xB79251E7, 0x06A1BBE6, 0x4BFB6350, 0x1A6B1018, 0x11CAEDFA, 0x3D25BDD8, 0xE2E1C3C9,
			0x44421659, 0x0A121386, 0xD90CEC6E, 0xD5ABEA2A, 0x64AF674E, 0xDA86A85F, 0xBEBFE988, 0x64E4C3FE,
			0x9DBC8057, 0xF0F7C086, 0x60787BF8, 0x6003604D, 0xD1FD8346, 0xF6381FB0, 0x7745AE04, 0xD736FCCC,
			0x83426B33, 0xF01EAB71, 0xB0804187, 0x3C005E5F, 0x77A057BE, 0xBDE8AE24, 0x55464299, 0xBF582E61,
			0x4E58F48F, 0xF2DDFDA2, 0xF474EF38, 0x8789BDC2, 0x5366F9C3, 0xC8B38E74, 0xB475F255, 0x46FCD9B9,
			0x7AEB2661, 0x8B1DDF84, 0x846A0E79, 0x915F95E2, 0x466E598E, 0x20B45770, 0x8CD55591, 0xC902DE4C,
			0xB90BACE1, 0xBB8205D0, 0x11A86248, 0x7574A99E, 0xB77F19B6, 0xE0A9DC09, 0x662D09A1, 0xC4324633,
			0xE85A1F02, 0x09F0BE8C, 0x4A99A025, 0x1D6EFE10, 0x1AB93D1D, 0x0BA5A4DF, 0xA186F20F, 0x2868F169,
			0xDCB7DA83, 0x573906FE, 0xA1E2CE9B, 0x4FCD7F52, 0x50115E01, 0xA70683FA, 0xA002B5C4, 0x0DE6D027,
			0x9AF88C27, 0x773F8641, 0xC3604C06, 0x61A806B5, 0xF0177A28, 0xC0F586E0, 0x006058AA, 0x30DC7D62,
			0x11E69ED7, 0x2338EA63, 0x53C2DD94, 0xC2C21634, 0xBBCBEE56, 0x90BCB6DE, 0xEBFC7DA1, 0xCE591D76,
			0x6F05E409, 0x4B7C0188, 0x39720A3D, 0x7C927C24, 0x86E3725F, 0x724D9DB9, 0x1AC15BB4, 0xD39EB8FC,
			0xED545578, 0x08FCA5B5, 0xD83D7CD3, 0x4DAD0FC4, 0x1E50EF5E, 0xB161E6F8, 0xA28514D9, 0x6C51133C,
			0x6FD5C7E7, 0x56E14EC4, 0x362ABFCE, 0xDDC6C837, 0xD79A3234, 0x92638212, 0x670EFA8E, 0x406000E0
		],
		[
			0x3A39CE37, 0xD3FAF5CF, 0xABC27737, 0x5AC52D1B, 0x5CB0679E, 0x4FA33742, 0xD3822740, 0x99BC9BBE,
			0xD5118E9D, 0xBF0F7315, 0xD62D1C7E, 0xC700C47B, 0xB78C1B6B, 0x21A19045, 0xB26EB1BE, 0x6A366EB4,
			0x5748AB2F, 0xBC946E79, 0xC6A376D2, 0x6549C2C8, 0x530FF8EE, 0x468DDE7D, 0xD5730A1D, 0x4CD04DC6,
			0x2939BBDB, 0xA9BA4650, 0xAC9526E8, 0xBE5EE304, 0xA1FAD5F0, 0x6A2D519A, 0x63EF8CE2, 0x9A86EE22,
			0xC089C2B8, 0x43242EF6, 0xA51E03AA, 0x9CF2D0A4, 0x83C061BA, 0x9BE96A4D, 0x8FE51550, 0xBA645BD6,
			0x2826A2F9, 0xA73A3AE1, 0x4BA99586, 0xEF5562E9, 0xC72FEFD3, 0xF752F7DA, 0x3F046F69, 0x77FA0A59,
			0x80E4A915, 0x87B08601, 0x9B09E6AD, 0x3B3EE593, 0xE990FD5A, 0x9E34D797, 0x2CF0B7D9, 0x022B8B51,
			0x96D5AC3A, 0x017DA67D, 0xD1CF3ED6, 0x7C7D2D28, 0x1F9F25CF, 0xADF2B89B, 0x5AD6B472, 0x5A88F54C,
			0xE029AC71, 0xE019A5E6, 0x47B0ACFD, 0xED93FA9B, 0xE8D3C48D, 0x283B57CC, 0xF8D56629, 0x79132E28,
			0x785F0191, 0xED756055, 0xF7960E44, 0xE3D35E8C, 0x15056DD4, 0x88F46DBA, 0x03A16125, 0x0564F0BD,
			0xC3EB9E15, 0x3C9057A2, 0x97271AEC, 0xA93A072A, 0x1B3F6D9B, 0x1E6321F5, 0xF59C66FB, 0x26DCF319,
			0x7533D928, 0xB155FDF5, 0x03563482, 0x8ABA3CBB, 0x28517711, 0xC20AD9F8, 0xABCC5167, 0xCCAD925F,
			0x4DE81751, 0x3830DC8E, 0x379D5862, 0x9320F991, 0xEA7A90C2, 0xFB3E7BCE, 0x5121CE64, 0x774FBE32,
			0xA8B6E37E, 0xC3293D46, 0x48DE5369, 0x6413E680, 0xA2AE0810, 0xDD6DB224, 0x69852DFD, 0x09072166,
			0xB39A460A, 0x6445C0DD, 0x586CDECF, 0x1C20C8AE, 0x5BBEF7DD, 0x1B588D40, 0xCCD2017F, 0x6BB4E3BB,
			0xDDA26A7E, 0x3A59FF45, 0x3E350A44, 0xBCB4CDD5, 0x72EACEA8, 0xFA6484BB, 0x8D6612AE, 0xBF3C6F47,
			0xD29BE463, 0x542F5D9E, 0xAEC2771B, 0xF64E6370, 0x740E0D8D, 0xE75B1357, 0xF8721671, 0xAF537D5D,
			0x4040CB08, 0x4EB4E2CC, 0x34D2466A, 0x0115AF84, 0xE1B00428, 0x95983A1D, 0x06B89FB4, 0xCE6EA048,
			0x6F3F3B82, 0x3520AB82, 0x011A1D4B, 0x277227F8, 0x611560B1, 0xE7933FDC, 0xBB3A792B, 0x344525BD,
			0xA08839E1, 0x51CE794B, 0x2F32C9B7, 0xA01FBAC9, 0xE01CC87E, 0xBCC7D1F6, 0xCF0111C3, 0xA1E8AAC7,
			0x1A908749, 0xD44FBD9A, 0xD0DADECB, 0xD50ADA38, 0x0339C32A, 0xC6913667, 0x8DF9317C, 0xE0B12B4F,
			0xF79E59B7, 0x43F5BB3A, 0xF2D519FF, 0x27D9459C, 0xBF97222C, 0x15E6FC2A, 0x0F91FC71, 0x9B941525,
			0xFAE59361, 0xCEB69CEB, 0xC2A86459, 0x12BAA8D1, 0xB6C1075E, 0xE3056A0C, 0x10D25065, 0xCB03A442,
			0xE0EC6E0E, 0x1698DB3B, 0x4C98A0BE, 0x3278E964, 0x9F1F9532, 0xE0D392DF, 0xD3A0342B, 0x8971F21E,
			0x1B0A7441, 0x4BA3348C, 0xC5BE7120, 0xC37632D8, 0xDF359F8D, 0x9B992F2E, 0xE60B6F47, 0x0FE3F11D,
			0xE54CDA54, 0x1EDAD891, 0xCE6279CF, 0xCD3E7E6F, 0x1618B166, 0xFD2C1D05, 0x848FD2C5, 0xF6FB2299,
			0xF523F357, 0xA6327623, 0x93A83531, 0x56CCCD02, 0xACF08162, 0x5A75EBB5, 0x6E163697, 0x88D273CC,
			0xDE966292, 0x81B949D0, 0x4C50901B, 0x71C65614, 0xE6C6C7BD, 0x327A140A, 0x45E1D006, 0xC3F27B9A,
			0xC9AA53FD, 0x62A80F00, 0xBB25BFE2, 0x35BDD2F6, 0x71126905, 0xB2040222, 0xB6CBCF7C, 0xCD769C2B,
			0x53113EC0, 0x1640E3D3, 0x38ABBD60, 0x2547ADF0, 0xBA38209C, 0xF746CE76, 0x77AFA1C5, 0x20756060,
			0x85CBFE4E, 0x8AE88DD8, 0x7AAAF9B0, 0x4CF9AA7E, 0x1948C25C, 0x02FB8A8C, 0x01C36AE4, 0xD6EBE1F9,
			0x90D4F869, 0xA65CDEA0, 0x3F09252D, 0xC208E69F, 0xB74E6132, 0xCE77E25B, 0x578FDFE3, 0x3AC372E6
		]
	];
	//==============================================================


} //END CLASS

/*** Sample Usage ; These are only sample Key/Iv (use your own) ; must use base64 encode/decode just like in the below example to preserve binary or unicode data !!
$bf = new SmartCryptoCiphersBlowfishCBC('a-56-bytes-secret-key-abcdefghijklmnopqrstuvwxyz-1234567', 'iv:2345;'); // the key must be 56 bytes (characters) / 448 bits ; the iv must be 8 bytes (characters) / 64 bits
$encrypted = (string) base64_encode((string)$bf->encrypt(base64_encode('this is some example plain text')));
$plaintext = (string) base64_decode((string)$bf->decrypt(base64_decode((string)$encrypted)));
echo 'plain text: '.$plaintext; // it should be: 'this is some example plain text'
*/


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

// provide the (WEAK but FAST :: symetrical :: HASH) cryptography support // (ENCRYPT + DECRYPT)
// v.1.2.1 (unixworld)
// Simple but secure encryption based on hash functions
// Basically this algorithm provides a block cipher in OFB mode (output feedback mode)
// requires sha1 function in PHP
// based on :: Quadracom's class v.1.0

/**
 * Class Smart Crypto Ciphers :: HashCrypt OFB
 * This class uses a dynamic generated initialization vector based on some random data, it is not intended or safe for long-term storage purposes, just for on-the-fly encryption
 *
 * @usage       dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 *
 * @access 		private
 * @internal
 *
 * @depends     classes: Smart, SmartEnvironment, SmartHashCrypto
 * @version     v.20231117
 *
 */
final class SmartCryptoCiphersHashCryptOFB {

	// ->


	//========================================
	// @ PRIVATE
	private $hash_key 		= null;		// @var	string :: Hashed value of the user provided encryption key
	// @ PRIVATE
	private $hash_length 	= 0;		// @var	int :: String length of hashed values using the current algorithm
	// @PRIVATE
	private $mode 			= null;		// @var enum :: sha224 (default), sha3-224, sha3-256, sha384, sha3-384, sha3-512 (length extension attack safe only algos)
	//========================================


	//==============================================================
	 // Constructor method
	 // Used to set key for encryption and decryption (must be between 7 and 4096 bytes)
	 // @param	string	$key	Your secret key used for encryption and decryption
	 // @return mixed
	public function __construct(string $mode, string $key) {

		//--
		$key = (string) trim((string)$key);
		if((string)$key == '') { // fix for empty key
			Smart::raise_error(__METHOD__.' # Empty Key');
			return;
		} //end if
		//--
		$klen = (int) strlen((string)$key);
		if((int)$klen < 7) { // {{{SYNC-CRYPTO-KEY-MIN}}} ; minimum acceptable secure key is 7 characters long
			if(SmartEnvironment::ifInternalDebug()) {
				if(SmartEnvironment::ifDebug()) {
					Smart::log_notice(__METHOD__.' # Key is too short: `'.$key.'`');
				} //end if
			} //end if
			Smart::raise_error(__METHOD__.' # Key Size is lower than 7 bytes ('.(int)$klen.') which is not safe against brute force attacks !');
			return;
		} elseif((int)$klen > 4096) { // {{{SYNC-CRYPTO-KEY-MAX}}} ; max key size is enforced to allow ZERO theoretical colissions on any of: md5, sha1, sha256 or sha512
			if(SmartEnvironment::ifInternalDebug()) {
				if(SmartEnvironment::ifDebug()) {
					Smart::log_notice(__METHOD__.' # Key is too long: `'.$key.'`');
				} //end if
			} //end if
			Smart::raise_error(__METHOD__.' # Key Size is higher than 4096 bytes ('.(int)$klen.') which is not safe against collisions !');
			return;
		} //end if
		//--

		//-- for the case: hash/sha224
		$mode = (string) trim((string)$mode);
		if((string)substr((string)$mode, 0, 5) != 'hash/') {
			Smart::raise_error(__METHOD__.' # Invalid Mode: '.$mode);
			return;
		} //end if
		//--
		$cfgcrypto = (string) $mode;
		$mode = '';
		$arr = (array) explode('/', (string)$cfgcrypto, 2);
		$cfgcrypto = null;
		$mode = (string) trim((string)($arr[1] ?? ''));
		$arr = null;
		//--

		//--
		switch((string)$mode) { // enhancement by unixman ; {{{SYNC-HASH-ENC/DEC-HASHING}}}
		//	case 'md5': // UNSAFE+++ (collisions)
		//	case 'sha1': // UNSAFE++ (collisions)
		//	case 'sha256': // UNSAFE+ (collisions, attack length)
			case 'sha3-256': // OK
			case 'sha384': // OK
			case 'sha3-384': // OK
			case 'sha3-512': // OK
		//	case 'sha512': // UNSAFE (attack length)
			case 'sha3-224': // OK
				break;
			case 'sha224': // OK
			default:
				Smart::log_warning(__METHOD__.' # ERROR: Invalid mode: `'.$mode.'` ; FallBack to SHA224');
				$mode = 'sha224';
		} //end switch
		//--
		$this->mode = (string) $mode;
		//--

		//-- Instead of using the key directly we compress it using a hash function
		$this->hash_key = (string) $this->_hash((string)$key);
		//--

		//-- Remember length of hashvalues for later use
		$this->hash_length = (int) strlen((string)$this->hash_key);
		//--

	} //END FUNCTION
	//==============================================================


	//==============================================================
	// [PUBLIC]
	 // Method used for encryption
	 // @param	string	$string	Message to be encrypted
	 // @return string	Encrypted message
	public function encrypt(?string $b64DataOrPak) : string {

		//-- {{{SYNC-CRYPTO-DATA-ENCAPSULATE-B64}}}
		// expects: B64 data or B64 / HEX pak ; req. for safe padding !
		// why B64 or package data ? just because safe padding requires a capsule like B64/HEX
		// why not B64 encode here ? just because it can be a package as B64#signature and don't want to manage signatures here ...
		//--

		//--
		if((string)trim((string)$this->mode) == '') {
			Smart::log_warning(__METHOD__.' # Crypto Mode is Not Selected');
			return '';
		} //end if
		if((string)trim((string)$this->hash_key) == '') {
			Smart::log_warning(__METHOD__.' # Crypto Key is Empty');
			return '';
		} //end if
		if((int)$this->hash_length <= 0) {
			Smart::log_warning(__METHOD__.' # Crypto Key is Negative');
			return '';
		} //end if
		//--

		//--
		if((string)$b64DataOrPak == '') { // do not test with trim, can be various data !
			return '';
		} //end if
		//--

		//--
		$rawCryptoData = (string) $this->_encipher((string)$b64DataOrPak);
		$b64DataOrPak = null; // free mem
		//--

		//--
		return (string) $rawCryptoData;
		//--

	} //END FUNCTION
	//==============================================================


	//==============================================================
	// [PUBLIC]
	 // Method used for decryption
	 // @param	string	$string	Message to be decrypted
	 // @return string	Decrypted message
	public function decrypt(?string $string) : string {

		//--
		if((string)trim((string)$this->mode) == '') {
			Smart::log_warning(__METHOD__.' # Crypto Mode is Not Selected');
			return '';
		} //end if
		if((string)trim((string)$this->hash_key) == '') {
			Smart::log_warning(__METHOD__.' # Crypto Key is Empty');
			return '';
		} //end if
		if((int)$this->hash_length <= 0) {
			Smart::log_warning(__METHOD__.' # Crypto Key is Negative');
			return '';
		} //end if
		//--

		//--
		if((string)$string == '') { // do not test with trim, may be binary data !
			return '';
		} //end if
		//--

		//--
		$string = (string) $this->_decipher((string)$string);
		//--
		$string = (string) trim((string)$string); // expects B64 data or B64 pak ; {{{SYNC-CRYPTO-DECRYPT-TRIM-B64}}}
		//--
		// why not B64 decode here ? just because it can be a package as B64#signature and don't want to manage signatures here ...
		// pf B64 decoded and have a trailing #signature, that is lost ... can't be verified later
		//--

		//--
		return (string) $string; // B64 data or B64 pak
		//--

	} //END FUNCTION
	//==============================================================


	//==============================================================
	//============================================================== PRIVATES
	//==============================================================


	private function _encipher(string $str) : string {
		//-- test
		if((string)$str == '') {
			return '';
		} //end if
		//-- Clear output
		$out = '';
		//--
		//== start algo
		//-- gen IV
		$iv = $this->_generate_iv();
		//-- First block of output is ($this->hash_hey XOR IV)
		for($c=0; $c<(int)$this->hash_length; $c++) {
			$out .= (string) chr((int)ord((string)$iv[(int)$c]) ^ (int)ord((string)$this->hash_key[(int)$c]));
		} //end for
		//-- Use IV as first key
		$key = (string) $iv;
		$c = 0;
		//-- Go through input string
		while((int)$c < (int)strlen((string)$str)) {
			//-- If we have used all characters of the current key we switch to a new one
			if(((int)$c != 0) AND ((int)((int)$c % (int)$this->hash_length) == 0)) {
				//-- New key is (Last block of plaintext XOR current Key)
				$key = (string) $this->_hash((string)$key.substr((string)$str, (int)((int)$c - (int)$this->hash_length), (int)$this->hash_length));
				//--
			} //end if
			//-- Generate output by xor-ing input and key character for character
			$out .= (string) chr((int)ord((string)$key[(int)((int)$c % (int)$this->hash_length)]) ^ (int)ord((string)$str[(int)$c]));
			$c++;
		} //end while
		//--
		//== #end algo
		//--
		return (string) $out;
		//--
	} //END FUNCTION


	private function _decipher(string $str) : string {
		//-- test
		if((string)$str == '') {
			return '';
		} //end if
		//--
		$out = '';
		//--
		//== start algo
		//-- Extract encrypted IV from input
		$xiv = (string) substr((string)$str, 0, (int)$this->hash_length);
		//-- Extract encrypted message from input
		$str = (string) substr((string)$str, (int)$this->hash_length, (int)((int)strlen((string)$str) - (int)$this->hash_length));
		//--
		$iv = '';
		//-- Regenerate IV by xor-ing encrypted IV from block 1 and $this->hashed_key :: Mathematics: (IV XOR KeY) XOR Key = IV
		for($c=0; $c<$this->hash_length; $c++) {
			$iv .= (string) chr((int)ord((string)$xiv[(int)$c]) ^ (int)ord((string)$this->hash_key[(int)$c]));
		} //end for
		//-- Use IV as key for decrypting the first block cyphertext
		$key = $iv;
		//--
		$c = 0;
		//-- Loop through the whole input string
		while((int)$c < (int)strlen((string)$str)) {
			//-- If we have used all characters of the current key we switch to a new one
			if(((int)$c != 0) and ((int)$c % (int)$this->hash_length == 0)) {
				//-- New key is (Last block of recovered plaintext XOR current Key)
				$key = $this->_hash((string)$key.substr((string)$out, (int)((int)$c - (int)$this->hash_length), (int)$this->hash_length));
				//--
			} //end if
			//-- Generate output by xor-ing input and key character for character
			$out .= (string) chr((int)ord((string)$key[(int)((int)$c % (int)$this->hash_length)]) ^ ord((string)$str[(int)$c]));
			//--
			$c++;
			//--
		} //end while
		//--
		//== #end algo
		//--
		return (string) $out;
		//--
	} //END FUNCTION


	//==============================================================
	 // Hashfunction used for encryption
	 // This class hashes any given string using the best available hash algorithm.
	 // Default is using sha1, but it is not the best recommended ...
	 // @access	private
	 // @param	string	$string	Message to hashed
	 // @return string	Hash value of input message
	private function _hash(?string $string) : string {

		//--
		// force use sha1() encryption (unixman)
		//$result = sha1($string);
		//$out ='';
		// Convert hexadecimal hash value to binary string
		//for($c=0;$c<strlen($result);$c+=2) {
		//	$out .= chr(hexdec($result[$c].$result[$c+1]));
		//} //end for
		//return (string) $out;
		//--

		//--
		$result = '';
		//--

		//--
		switch((string)$this->mode) { // enhancement by unixman ; {{{SYNC-HASH-ENC/DEC-HASHING}}}
		//	case 'md5': // UNSAFE+++ (collisions)
		//		$result = (string) SmartHashCrypto::md5((string)$string);
		//		break;
		//	case 'sha1': // UNSAFE++ (collisions)
		//		$result = (string) SmartHashCrypto::sha1((string)$string);
		//		break;
		//	case 'sha256': // UNSAFE+ (collisions, attack length)
		//		$result = (string) SmartHashCrypto::sha256((string)$string);
		//		break;
			case 'sha3-256': // OK
				$result = (string) SmartHashCrypto::sh3a256((string)$string);
				break;
			case 'sha384': // OK
				$result = (string) SmartHashCrypto::sha384((string)$string);
				break;
			case 'sha3-384': // OK
				$result = (string) SmartHashCrypto::sh3a384((string)$string);
				break;
			case 'sha3-512': // OK
				$result = (string) SmartHashCrypto::sh3a512((string)$string);
				break;
		//	case 'sha512': // UNSAFE (attack length)
		//		$result = (string) SmartHashCrypto::sha512((string)$string);
		//		break;
			case 'sha224': // OK
				$result = (string) SmartHashCrypto::sha224((string)$string);
				break;
			case 'sha3-224': // OK
			default:
				$result = (string) SmartHashCrypto::sh3a224((string)$string);
		} //end switch
		//--

		//-- convert hexadecimal hash value to binary string ;
	//	return (string) Smart::safe_hex_2_bin((string)$result, true, true); // ignore case, log if invalid
		return (string) hex2bin((string)$result); // this come form HEX SHA hashing, if there is something must WARN !
		//--

	} //END FUNCTION
	//==============================================================


	//==============================================================
	 // Generate a random string to initialize encryption
	 // This method will return a random binary string IV ( = initialization vector).
	 // The randomness of this string is one of the crucial points of this algorithm as it
	 // is the basis of encryption. The encrypted IV will be added to the encrypted message
	 // to make decryption possible. The transmitted IV will be encoded using the user provided key.
	 // @todo	Add more random sources.
	 // @access	private
	 // @see function	hash encryption
	 // @return string	Binary pseudo random string
	private function _generate_iv() {

		/*
		// Initialize pseudo random generator
		// seed rand: (double)microtime()*1000000 // no more needed

		// Collect very random data.
		// Add as many "pseudo" random sources as you can find.
		// Possible sources: Memory usage, diskusage, file and directory content...
		$iv =  (string) Smart::random_number(); // this first and last must be not guess !

		$iv .= (string) Smart::uuid_10_str();
		$iv .= (string) Smart::uuid_10_num();
		$iv .= (string) Smart::uuid_10_seq();
		$iv .= (string) Smart::uuid_13_seq();

		$iv .= (string) implode("\r", (array)$_SERVER);
		$iv .= (string) implode("\r", (array)$_COOKIE);

		$iv .= (string) Smart::uuid_36();
		$iv .= (string) Smart::uuid_45();
		$iv .= (string) Smart::unique_entropy(); // this first and last must be not guess !
		*/

		$iv = (string) random_bytes(4096); // binary
		$iv = (string) base64_encode((string)$iv); // safe character set, from binary

		return (string) $this->_hash((string)$iv);

	} //END FUNCTION
	//==============================================================


	//------------------------------------
	//-- # EXAMPLE USAGE:
	// $crypt = new SmartCryptoCiphersHashCryptOFB('the secret ...');
	// $enc_text = $crypt->encrypt('text to be encrypted');
	// $dec_text = $crypt->decrypt($enc_text);
	//-- # WARNING: !!! The $encrypted WILL BE ALWAYS (ALMOST) DIFFERENT !!!
	//------------------------------------


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

/**
 * Class: Smart Crypto Ciphers :: OpenSSL (Provider)
 * Provides an OpenSSL based encryption / decryption.
 *
 * @usage       dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 *
 * @access 		private
 * @internal
 *
 * @depends     extensions: PHP OpenSSL ; classes: Smart, SmartHashCrypto
 * @version     v.20231117
 *
 */
final class SmartCryptoCiphersOpenSSL {

	// ->


	//==============================================================
	//-- @ PRIVATE
	private $crypto_cipher 	= null;		// Crypto Cipher (ex: BF-CBC)
	private $crypto_key 	= null;		// Crypto Key
	private $crypto_iv 		= null;		// Crypto IV (initialization vector)
	private $crypto_opts 	= null; 	// Crypto Options (OpenSSL options: OPENSSL_RAW_DATA OPENSSL_ZERO_PADDING)
	private $crypto_pfix 	= null; 	// Crypto Fix: Pre Encrypt Padding
	private $crypto_algo 	= null; 	// Crypto Algo
	//--
	//==============================================================


	//==============================================================
	/**
	 * Constructor
	 * Initializes the blowfish cipher object, and gives a sets
	 * the secret key
	 *
	 * @param string $key
	 * @param enum $runmode		ex: openssl/blowfish/CBC
	 * @access public
	 */
	public function __construct(string $runmode, string $key, string $iv) {
		//--
		if((!function_exists('openssl_encrypt')) OR (!function_exists('openssl_decrypt'))) {
			Smart::raise_error(__METHOD__.' # requires the PHP OpenSSL Extension with Encrypt/Decrypt support ! If is not available use the alternative Encryption Mode in Configuration INITS !', 'PHP OpenSSL Extension Encrypt/Decrypt support is missing');
			return;
		} //end if
		//--
		$runmode = (string) trim((string)$runmode);
		//--
		$tmp_mode_crypto 	= (array) explode('/', (string)$runmode, 3); // Example: 'openssl/blowfish/CBC'
		if(!array_key_exists(0, $tmp_mode_crypto)) {
			$tmp_mode_crypto[0] = null;
		} //end if
		if(!array_key_exists(1, $tmp_mode_crypto)) {
			$tmp_mode_crypto[1] = null;
		} //end if
		if(!array_key_exists(2, $tmp_mode_crypto)) {
			$tmp_mode_crypto[2] = null;
		} //end if
		$tmp_expl_check 	= (string) trim((string)strtolower((string)$tmp_mode_crypto[0]));
		$tmp_expl_algo 		= (string) trim((string)strtolower((string)$tmp_mode_crypto[1]));
		$tmp_expl_method 	= (string) trim((string)strtoupper((string)$tmp_mode_crypto[2]));
		//--
		if((string)$tmp_expl_check != 'openssl') {
			Smart::raise_error(__METHOD__.' # Invalid Run Mode: '.$runmode);
			return;
		} //end if
		//--
		$this->crypto_algo = (string) $tmp_expl_algo;
		//--
		$len_key = 0;
		$len_iv = 0;
		switch((string)$tmp_expl_algo) { // cipher
			//--
			case 'blowfish': // 448 ; currently this is the only-one compatible with the Symmetric Crypto JS Api ; chipher (Blowfish is a 64-bit (8 bytes) block cipher)
				//-- Blowfish uses 64-bit blocks, a variable size key (ranging from 32 to 448 bits = 4 to 56 bytes[characters]) and an initialization vector (IV, 64bit = 8 bytes[characters])
				$len_key = 56; // Blowfish448 # key: 448 bits = 56 bytes ; {{{SYNC-BLOWFISH-KEY}}}
				$len_iv  =  8; // Blowfish448 # iv:   64 bits =  8 bytes ; {{{SYNC-BLOWFISH-IV}}}
				//--
				break;
			case 'aes256': // 256
			case 'camellia256': // 256
				//-- AES/CAM key ; sizes: 128, 192 or 256 bits = max 32 chars ; AES/CAM iv ; block size: 128 bits = 16 chars
				$len_key = 32; // AES256 # key: 256 bits = 32 bytes ; {{{SYNC-AES256/CAM256-KEY}}}
				$len_iv  = 16; // AES256 # iv:  128 bits = 16 bytes ; {{{SYNC-AES256/CAM256-IV}}}
				//--
				break;
			case 'idea':
				//-- IDEA ; sizes: key size is 128 bit ; iv is 64 bit
				$len_key = 16; // IDEA # key: 128 bits = 16 bytes ; {{{SYNC-IDEA-CIPHER-KEY}}}
				$len_iv  =  8; // IDEA # iv:   64 bits =  8 bytes ; {{{SYNC-IDEA-CIPHER-IV}}}
				//--
				break;
			default:
				Smart::raise_error(__METHOD__.' # Invalid Cipher: '.$tmp_expl_algo);
				return;
		} //end if
		//--
		if(((int)strlen((string)$key) !== (int)$len_key) OR ((int)strlen((string)trim((string)$key)) !== (int)$len_key)) {
			if(SmartEnvironment::ifInternalDebug()) {
				if(SmartEnvironment::ifDebug()) {
					Smart::log_notice(__METHOD__.' # Key ('.(int)$len_key.') is too short: `'.$key.'`');
				} //end if
			} //end if
			Smart::raise_error(__METHOD__.' # Invalid Key, must be exactly '.(int)$len_key.' bytes ('.(int)((int)$len_key * 8).' bits)');
			return;
		} //end if
		if(((int)strlen((string)$iv) !== (int)$len_iv) OR ((int)strlen((string)trim((string)$iv)) !== (int)$len_iv)) {
			if(SmartEnvironment::ifInternalDebug()) {
				if(SmartEnvironment::ifDebug()) {
					Smart::log_notice(__METHOD__.' # iV ('.(int)$len_iv.') is too short: `'.$iv.'`');
				} //end if
			} //end if
			Smart::raise_error(__METHOD__.' # Invalid iV, must be exactly '.(int)$len_iv.' bytes ('.(int)((int)$len_iv * 8).' bits)');
			return;
		} //end if
		//--
		$this->crypto_opts = 0;
		$this->crypto_pfix = true;
		//--
		switch((string)$tmp_expl_algo) { // cipher
			//--
			case 'blowfish': // 448 only ; currently this is the only-one compatible with the Symmetric Crypto JS Api ; chipher (Blowfish is a 64-bit (8 bytes) block cipher)
				//--
				$this->crypto_opts = OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING; // preserve compatibility with the PHP and JS classes and also mcrypt
				//--
				switch((string)$tmp_expl_method) { // for blowfish accept only: CBC, CFB or OFB
					case 'CBC':
						$this->crypto_cipher = 'BF-CBC'; // Blowfish CBC
						break;
					case 'OFB':
						$this->crypto_cipher = 'BF-OFB'; // Blowfish OFB
						break;
					case 'CFB':
					default:
						$this->crypto_cipher = 'BF-CFB'; // Blowfish CFB (default mode)
				} //end switch
				//--
				$this->crypto_key = (string) $key; // Blowfish key: 448 bits = 56 bytes ; {{{SYNC-BLOWFISH-KEY}}} ; {{{SYNC-BLOWFISH-CFB-OFB-KEY}}}
				$this->crypto_iv = (string) $iv; // Blowfish iv: 64 bits = 8 bytes ; {{{SYNC-BLOWFISH-IV}}}
				//--
				break;
			case 'aes256':
				//--
				$this->crypto_opts = OPENSSL_RAW_DATA; // don't use OPENSSL_ZERO_PADDING
				//--
				switch((string)$tmp_expl_method) { // for AES256 accept only: CBC, CFB or OFB
					case 'CBC':
						$this->crypto_cipher = 'AES-256-CBC'; // AES-256 CBC
						break;
					case 'OFB':
						$this->crypto_cipher = 'AES-256-OFB'; // AES-256 OFB
						break;
					case 'CFB':
					default:
						$this->crypto_cipher = 'AES-256-CFB'; // AES-256 CFB (default mode)
				} //end switch
				//--
				$this->crypto_key = (string) $key; // AES key: 256 bits = 32 bytes ; {{{SYNC-AES256/CAM256-KEY}}}
				$this->crypto_iv = (string) $iv; // AES iv: 128 bits = 16 bytes ; {{{SYNC-AES256/CAM256-IV}}}
				//--
				break;
			case 'camellia256':
				//--
				$this->crypto_opts = OPENSSL_RAW_DATA; // don't use OPENSSL_ZERO_PADDING
				//--
				switch((string)$tmp_expl_method) { // for Camellia256 accept only: CBC, CFB or OFB
					case 'CBC':
						$this->crypto_cipher = 'CAMELLIA-256-CBC'; // CAMELLIA-256 CBC
						break;
					case 'OFB':
						$this->crypto_cipher = 'CAMELLIA-256-OFB'; // CAMELLIA-256 OFB
						break;
					case 'CFB':
					default:
						$this->crypto_cipher = 'CAMELLIA-256-CFB'; // CAMELLIA-256 CFB (default mode)
				} //end switch
				//--
				$this->crypto_key = (string) $key; // Camellia key: 256 bits = 32 bytes ; {{{SYNC-AES256/CAM256-KEY}}}
				$this->crypto_iv = (string) $iv; // Camellia iv: 128 bits = 16 bytes ; {{{SYNC-AES256/CAM256-IV}}}
				//--
				break;
			case 'idea':
				//--
				$this->crypto_opts = OPENSSL_RAW_DATA; // don't use OPENSSL_ZERO_PADDING
				$this->crypto_pfix = false;
				//--
				switch((string)$tmp_expl_method) { // for IDEA accept only: CBC, CFB or OFB
					case 'CBC':
						$this->crypto_cipher = 'IDEA-CBC'; // IDEA
						break;
					case 'OFB':
						$this->crypto_cipher = 'IDEA-OFB'; // IDEA OFB
						break;
					case 'CFB':
					default:
						$this->crypto_cipher = 'IDEA-CFB'; // IDEA CFB (default mode)
				} //end switch
				//--
				$this->crypto_key = (string) $key; // idea key: 128 bits = 16 bytes ; {{{SYNC-IDEA-CIPHER-KEY}}}
				$this->crypto_iv = (string) $iv;   // idea  iv:  64 bits =  8 bytes ; {{{SYNC-IDEA-CIPHER-KEY}}}
				//--
				break;
			default:
				Smart::raise_error(__METHOD__.' # Invalid Cipher to set: '.$tmp_expl_algo);
				return;
		} //end if
		//--
	//	Smart::log_notice(__CLASS__.' @ Cipher: '.$this->crypto_cipher);
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Encrypts a string in CBC Mode or ECB Mode
	 *
	 * @param string $plainText
	 * @return string Returns cipher text
	 * @access public
	 */
	public function encrypt(?string $b64DataOrPak) : string {

		//-- {{{SYNC-CRYPTO-DATA-ENCAPSULATE-B64}}}
		// expects: B64 data or B64 / HEX pak ; req. for safe padding !
		// why B64 or package data ? just because safe padding requires a capsule like B64/HEX
		// why not B64 encode here ? just because it can be a package as B64#signature and don't want to manage signatures here ...
		//--

		//--
		if((string)trim((string)$this->crypto_key) == '') {
			Smart::log_warning(__METHOD__.' # Crypto Key is Empty');
			return '';
		} //end if
		if((string)trim((string)$this->crypto_iv) == '') {
			Smart::log_warning(__METHOD__.' # Crypto iV is Empty');
			return '';
		} //end if
		if((string)trim((string)$this->crypto_cipher) == '') {
			Smart::log_warning(__METHOD__.' # Crypto Cipher is Empty');
			return '';
		} //end if
		//--

		//--
		if((string)$b64DataOrPak == '') { // do not test with trim, can be various data !
			return '';
		} //end if
		//--

		//--
		//== ALGO: START
		//--
		if($this->crypto_pfix !== false) {
			//==
			//-- fix padding depending on cipher blocksize, if required
			$blocksize = (int) strlen((string)$this->crypto_iv);
			$padding = 0;
			if((int)$blocksize > 0) {
				$padding = (int) ((int)ceil((int)strlen((string)$b64DataOrPak) / (int)$blocksize) * (int)$blocksize); // {{{SYNC-BLOWFISH-PADDING}}}
			} //end if
			//-- unixman: fix: add spaces as padding as we have it as b64 encoded and will not modify the original
			$b64DataOrPak = (string) str_pad((string)$b64DataOrPak, (int)$padding, ' ', STR_PAD_RIGHT); // unixman (pad with spaces), safe for B64/HEX or package ; padding with NULL is not safe and if it would be no package and last character is null trimming will break data ...
			//==
			//--
		} //end if
		//--
		$rawCryptoData = (string) openssl_encrypt(
			(string) $b64DataOrPak, 		// B64 Data or Pak
			(string) $this->crypto_cipher, 	// algo
			(string) $this->crypto_key, 	// secret
			(int)    $this->crypto_opts, 	// options
			(string) $this->crypto_iv 		// init vector (nonce)
		);
		$b64DataOrPak = null; // free mem
		// DO NOT TRIM $cipherText ! IT IS RAW CRYPTO DATA
		//==
		//--
		//== ALGO #END
		//--

		//--
		return (string) $rawCryptoData; // RAW Encrypted Data ; DO NOT TRIM !!
		//--

	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Decrypts an encrypted string in CBC Mode or ECB Mode
	 *
	 * @param string $cipherText
	 * @return string Returns plain text
	 * @access public
	 */
	public function decrypt(?string $cipherText) : string {

		//--
		if((string)trim((string)$this->crypto_key) == '') {
			Smart::log_warning(__METHOD__.' # Crypto Key is Empty');
			return '';
		} //end if
		if((string)trim((string)$this->crypto_iv) == '') {
			Smart::log_warning(__METHOD__.' # Crypto iV is Empty');
			return '';
		} //end if
		if((string)trim((string)$this->crypto_cipher) == '') {
			Smart::log_warning(__METHOD__.' # Crypto Cipher is Empty');
			return '';
		} //end if
		//--

		//--
		if((string)$cipherText == '') { // do not test with trim, may be binary data !
			return '';
		} //end if
		//--

		//--
		$cipherText = (string) openssl_decrypt(
			(string) $cipherText, 			// encrypted data
			(string) $this->crypto_cipher, 	// algo
			(string) $this->crypto_key, 	// secret
			(int)    $this->crypto_opts, 	// options
			(string) $this->crypto_iv   	// init vector (nonce)
		);
		//--
		$cipherText = (string) trim((string)$cipherText); // expects B64 data or B64 pak ; {{{SYNC-CRYPTO-DECRYPT-TRIM-B64}}}
		//--
		// why not B64 decode here ? just because it can be a package as B64#signature and don't want to manage signatures here ...
		// pf B64 decoded and have a trailing #signature, that is lost ... can't be verified later
		//--

		//--
		return (string) $cipherText; // B64 data or B64 pak
		//--

	} //END FUNCTION
	//==============================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
