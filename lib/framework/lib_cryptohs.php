<?php
// [LIB - Smart.Framework / Hashing and Checksuming related Crypto]
// (c) 2006-present unix-world.org - all rights reserved
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
if((!function_exists('hash_algos')) OR (!function_exists('hash_hmac_algos'))) {
	@http_response_code(500);
	die('PHP Extension Hash / Hmac is not available');
} //end if
//--

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

/**
 * Class: SmartHashCrypto - provide various hashes for a string: salted password, sha512, sha384, sha256, sha1, md5.
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
 * @depends     PHP hash_algos() / hash() ; classes: Smart, SmartEnvironment ; constants: SMART_FRAMEWORK_SECURITY_KEY, SMART_SOFTWARE_NAMESPACE
 * @version     v.20250107
 * @package     @Core:Crypto
 *
 */
final class SmartHashCrypto {

	// ::

	public const PASSWORD_PLAIN_MIN_LENGTH = 7;
	public const PASSWORD_PLAIN_MAX_LENGTH = 55;
	public const PASSWORD_HASH_LENGTH = 128; // fixed length ; {{{SYNC-AUTHADM-PASS-LENGTH}}} ; if lower then padd to right with * ; {{{SYNC-AUTHADM-PASS-PADD}}}
	public const PASSWORD_PREFIX_VERSION = '$fPv3.7!'; // {{{SYNC-AUTHADM-PASS-PREFIX}}}

	public const DERIVE_MIN_KLEN = 3; // this can be a valid auth id, of which min size is 3
	public const DERIVE_MAX_KLEN = 4096;
	public const DERIVE_PREKEY_LEN = 80;
	public const DERIVE_CENTITER_TK = 88;
	public const DERIVE_CENTITER_EK = 87;
	public const DERIVE_CENTITER_EV = 78;
	public const DERIVE_CENTITER_PW = 77;

	public const SALT_PREFIX = 'Smart Framework';
	public const SALT_SEPARATOR = '#';
	public const SALT_SUFFIX = 'スマート フレームワーク';

	private static $cache = [];


	//==============================================================
	/**
	 * Create a safe checksum of data
	 * It will append the salt to the end of data to avoid the length extension attack # https://en.wikipedia.org/wiki/Length_extension_attack
	 * Protected by SHA3-384 that has 128-bit resistance against the length extension attacks since the attacker needs to guess the 128-bit to perform the attack, due to the truncation
	 * Now includes also a Poly1305 custom derivation ... adds 10x more resistence against length extension attacks ; increases exponential chances for rainbow attacks
	 *
	 * @param STRING $str 				The data to be hashed
	 * @param STRING $custom_salt 		A custom salt (will be trimmed from whitespaces) ; This is optional ; if not provided will use an internal salt derived from SMART_FRAMEWORK_SECURITY_KEY
	 * @return STRING 					The checksum hash as B62 using the final hex SHA3-384 over extremely complex derivations of the string/salt combination ; ~ 65 bytes length
	 */
	public static function checksum(?string $str, ?string $custom_salt=null) : string { // {{{SYNC-HASH-SAFE-CHECKSUM}}}
		//-- r.20231204
		// this have to be extremely fast, it is a checksum not a 2-way encryption or a password, thus not using PBKDF2 derivation
		// more, it is secured against length attacks with a combination of SHA3-384 / SHA384 and a core of SHA3-512 as derivations ; double prefixed with high complexity strings: B64 prefix 88 chars ; B92 suffix, variable
		// time execution ~ 0.07s .. 0.08s
		//--
		$custom_salt = (string) trim((string)$custom_salt);
		if((string)$custom_salt == '') {
			$custom_salt = (string) self::SALT_PREFIX.' '.self::SALT_SEPARATOR.' '.self::SALT_SUFFIX;
			if((defined('SMART_SOFTWARE_NAMESPACE')) AND ((string)trim((string)SMART_SOFTWARE_NAMESPACE) != '')) {
				$custom_salt .= (string) ' '.SMART_SOFTWARE_NAMESPACE;
			} else {
				Smart::raise_error(__METHOD__.' SMART_SOFTWARE_NAMESPACE is not defined or empty !');
				return '';
			} //end if else
			if((defined('SMART_FRAMEWORK_SECURITY_KEY')) AND ((string)trim((string)SMART_FRAMEWORK_SECURITY_KEY) != '')) {
				$custom_salt .= (string) ' '.SMART_FRAMEWORK_SECURITY_KEY;
			} else {
				Smart::raise_error(__METHOD__.' SMART_FRAMEWORK_SECURITY_KEY is not defined or empty !');
				return '';
			} //end if else
		} //end if
		//--
		$nByte = (string) chr(0);
		//--
		$antiAtkLen = (string) self::sha384((string)$str.$nByte.$custom_salt);
		$antiAtkB64Len = (string) base64_encode((string)hex2bin((string)$antiAtkLen));
		//--
		$cSalt = (string) self::crc32b((string)$antiAtkLen, true); // B36
		//--
		$oSalt = (string) self::hmac('SHA3-384', (string)$custom_salt, (string)$nByte.$str.$nByte.$antiAtkLen); // Hex
		$pSalt = (string) Smart::dataRRot13((string)Smart::b64s_enc((string)hex2bin((string)$oSalt))); // B64s
		$rSalt = (string) self::sh3a256((string)$cSalt.$nByte.self::sh3a224((string)$pSalt.$nByte.$antiAtkB64Len, true).$nByte.self::sha512((string)$str, true).$nByte.self::md5((string)$str, true).$nByte.self::sha1((string)$str, true).$nByte.self::sha224((string)$str, true).$nByte.self::sha256((string)$str, true).$nByte.self::sha384((string)$str, true).$nByte.strrev((string)$antiAtkLen)); // Hex
		//--
		$tSalt = (string) Smart::base_from_hex_convert((string)$antiAtkLen, 32); // B32
		$vSalt = (string) Smart::base_from_hex_convert((string)$rSalt, 58); // B58
		$wSalt = (string) Smart::base_from_hex_convert((string)$oSalt.$antiAtkLen, 85); // B85
		$xSalt = (string) Smart::base_from_hex_convert((string)strrev((string)$oSalt).$antiAtkLen, 92); // B92
		$ySalt = (string) self::sh3a512((string)$custom_salt.$nByte.$str.$nByte.$tSalt.$nByte.$xSalt, true); // B64 of B92
		$polyKey = (string) strrev((string)substr((string)$xSalt, 17, 32)); // B64 (part)
		$polyData = (string) $custom_salt.$nByte.$str.$nByte.$wSalt; // ascii
		$zSalt = (string) self::poly1305((string)$polyKey, (string)$polyData, true); // B64
		//--
		$hexstr = (string) self::sh3a384((string)$ySalt."\v".$str."\t".$vSalt."\n".$custom_salt."\r".$xSalt.$nByte.$zSalt); // SHA3-384 Hex of B64 derived salt (88 characters) + data + B92 derived salt (variable length ~ 72 characters)
		//--
		return (string) Smart::dataRRot13((string)Smart::base_from_hex_convert((string)$hexstr, 62)); // B62
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Creates a password one-way hash, using a salted and strong PBKDF2 derivation with two hashing algorithms: SHA3-512 (anti collisions) and SHA2-384 (anti length attack)
	 * This is safe also against rainbow attacks, and more, does not uses HEX but Base92+ which provides extra layer of security by using 92+1 alpha characters not only 16 as Hex ...
	 * VERSION 3
	 *
	 * @param STRING $plainpass 			The plain password ; min 7 ; max 55
	 * @param STRING $salt 					The salt ; min 3 ; max 4096 ; this salt will be derived using an internal algorithm to provide a much safe salt even if this is leaved empty ; recommended is to provide here a salt value ...
	 * @return STRING 						The password hash: a 128 bytes (Base92+) derived using PBKDF2 (the most secure key derivation algorithm), hashed using SHA3-512 (safe against collisions) and SHA3-384 (safe against length attacks)
	 */
	public static function password(string $plainpass, string $salt) : string { // {{{SYNC-HASH-PASSWORD}}}
		//-- r.20231204
		// V2 was a bit unsecure..., was deprecated a long time, now is no more supported !
		// V3 is the current version: 20231028, using PBKDF2 + derivations, SHA3-512 and SHA3-384
		//--
		// the password salt must not be too complex related to the password itself # http://stackoverflow.com/questions/5482437/md5-hashing-using-password-as-salt
		// an extraordinary good salt + a weak password may increase the risk of colissions
		// just sensible salt + strong password = safer
		// to achieve this, the salt is derived, to make it safe and even more unpredictable
		// for passwords the best is to pre-pend the salt: http://stackoverflow.com/questions/4171859/password-salts-prepending-vs-appending
		// for checksuming is better to append the salt to avoid the length extension attack # https://en.wikipedia.org/wiki/Length_extension_attack
		// ex: azA-Z09 pass, prepend needs 26^6 permutations while append 26^10, so append adds more complexity
		//--
		if((string)trim((string)$plainpass) == '') {
			Smart::log_notice(__METHOD__.' # Password is Empty !'); // this is ok, in prod env notices are not logged
			return ''; // avoid hash an empty password
		} //end if
		if((string)trim((string)$salt) == '') {
			Smart::log_notice(__METHOD__.' # Salt is Empty !'); // this is ok, in prod env notices are not logged
			return ''; // avoid hash an empty password
		} //end if
		//--
		if(
			((int)SmartUnicode::str_len((string)$plainpass) < (int)self::PASSWORD_PLAIN_MIN_LENGTH)
			OR
			((int)SmartUnicode::str_len((string)$plainpass) > (int)self::PASSWORD_PLAIN_MAX_LENGTH)
		) { // {{{SYNC-PASS-HASH-SHA512-PLUS-SALT-SAFE}}} ; sync with auth validate password: max pass allowed length is 55 !
			Smart::log_notice(__METHOD__.' # Password is too long or too short: '.(int)SmartUnicode::str_len((string)$plainpass)); // this is ok, in prod env notices are not logged
			return ''; // too short or too long
		} //end if
		//--
		if(
			((int)strlen((string)$salt) < (int)self::DERIVE_MIN_KLEN)
			OR
			((int)strlen((string)$salt) > (int)self::DERIVE_MAX_KLEN)
		) { // {{{SYNC-CRYPTO-KEY-MAX}}} divided by 2 as it is composed of both
			Smart::log_notice(__METHOD__.' # Salt is too long or too short: '.(int)strlen((string)$salt)); // this is ok, in prod env notices are not logged
			return ''; // too short or too long
		} //end if
		//--
		$nByte = (string) chr(0);
		//-- {{{SYNC-PBKDF2-PRE-DERIVATION}}} ; adds more complexity ; B64 charset
		$key = (string) trim((string)self::pbkdf2PreDerivedB92Key((string)$plainpass.$nByte.$salt)); // B92+, 80 chars ; see comments on pbkdf2 Pre Derived Key, why ...
		if((int)strlen((string)$key) !== (int)self::DERIVE_PREKEY_LEN) {
			Smart::log_warning(__METHOD__.' # Pre Derived Key length is invalid !'); // this is ok, in prod env notices are not logged
			return ''; // invalid length
		} //end if
		$pbkdf2Salt = (string) trim((string)self::pbkdf2PreDerivedB92Key((string)$salt.$nByte.$salt));
		if((int)strlen((string)$pbkdf2Salt) !== (int)self::DERIVE_PREKEY_LEN) {
			Smart::log_warning(__METHOD__.' # Pre Derived Salt length is invalid !'); // this is ok, in prod env notices are not logged
			return ''; // invalid length
		} //end if
		$reqLen = 34; // be sure it is an even number ; must fit max len for B92 + Padding
		$sSalt = (string) trim((string)self::pbkdf2DerivedB92Key((string)$key, (string)$pbkdf2Salt, (int)$reqLen, (int)self::DERIVE_CENTITER_PW, 'sha3-384')); // B92
		if((int)strlen((string)$sSalt) !== (int)$reqLen) {
			Smart::log_warning(__METHOD__.' # Derived Key length is invalid !'); // this is ok, in prod env notices are not logged
			return ''; // invalid length
		} //end if
		$fSalt = (string) substr((string)str_pad((string)$sSalt, 22, "'", STR_PAD_LEFT), 0, 22); // fixed length sale: 22 chars (from ~ 21..22), with a more wider character set: B92
		//--
		$chksPass = (string) self::crc32b((string)$plainpass, true); // 7 chars
		$pddPass = (string) str_pad((string)$plainpass, (int)self::PASSWORD_PLAIN_MAX_LENGTH, "\v", STR_PAD_RIGHT); // fixed length: 55
		$chksPPass = (string) self::crc32b((string)$pddPass, true); // 7 chars
		$hashData = (string) $fSalt."\n".$pddPass."\r"."\t".$chksPass; // MUST BE FIXED LEN ! It is 87 a PRIME Number ! To avoid colissions ; SHA3-512 collisions safe max string is 256 bit (32 bytes only) !!!
		$hashPass = (string) self::sh3a512((string)$hashData); // hex, 128
		$hashPass = (string) str_pad((string)Smart::base_from_hex_convert((string)$hashPass, 92), 80, "'", STR_PAD_RIGHT); // 79..80 chars ; fixed length: 80
		//--
		$antiAtkLen = (string) str_pad((string)Smart::base_from_hex_convert((string)self::sh3a224((string)$fSalt.$nByte.$plainpass.$nByte.$chksPPass), 92), 36, "'", STR_PAD_RIGHT); // 35..36 chars ; fixed length: 36
		//--
		$hash = (string) self::PASSWORD_PREFIX_VERSION."'".$hashPass."'".$antiAtkLen;
		$hash = (string) str_pad((string)$hash, 127, "'", STR_PAD_RIGHT);
		$hash = (string) substr((string)$hash.'!', 0, 128); // terminator character
		//--
		if(
			((string)trim((string)$hash) == '')
			OR
			((string)trim((string)$hash) != (string)$hash)
			OR
			((int)strlen((string)$hash) !== (int)self::PASSWORD_HASH_LENGTH)
		) {
			Smart::log_warning(__METHOD__.' # Internal Error: Password Hash :: Length must be '.(int)self::PASSWORD_HASH_LENGTH.' bytes !');
			return '';
		} //end if
		//--
		if(self::validatepasshashformat((string)$hash) !== true) { // {{{SYNC-AUTH-HASHPASS-FORMAT}}} ; also checks {{{SYNC-AUTHADM-PASS-LENGTH}}}
			Smart::log_warning(__METHOD__.' # Internal Error: Password Hash have an Invalid Format !');
			return '';
		} //end if
		//--
		return (string) $hash;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Check (verify) a password hash provided by SmartHashCrypto::password() have a valid format
	 * IMPORTANT: this method just validate the password hash format and DOES NOT CHECK if the Pass Hash is Valid against the provided plain password !
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param STRING $passhash 			The password hash of which format needs to be validated by certain criteria
	 * @return BOOL 					Will return TRUE if password format is valid or FALSE if not
	 */
	public static function validatepasshashformat(string $passhash) : bool { // {{{SYNC-HASH-PASSWORD}}} ; {{{SYNC-AUTH-HASHPASS-FORMAT}}}
		//--
		if((string)trim((string)$passhash) == '') {
			return false;
		} //end if
		if((string)trim((string)$passhash) != (string)$passhash) {
			return false;
		} //end if
		//--
		if((int)strlen((string)$passhash) !== (int)self::PASSWORD_HASH_LENGTH) { // {{{SYNC-AUTHADM-PASS-LENGTH}}}
			return false;
		} //end if
		//--
		if(strpos((string)$passhash, (string)self::PASSWORD_PREFIX_VERSION) !== 0) { // {{{SYNC-AUTHADM-PASS-PREFIX}}}
			return false;
		} //end if
		//--
		if((int)strlen((string)$passhash) !== (int)strspn((string)$passhash, (string)Smart::CHARSET_BASE_92."'")) { // a kind of alowed charset verification
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Check (verify) a plain text password hashed with SmartHashCrypto::password() matches the salted hash
	 * It must use the same salt as it was used when password was hashed, otherwise will not be validated
	 *
	 * @param STRING $plainpass 		The plain password
	 * @param STRING $passhash 			The password hash to be checked
	 * @param STRING $salt 				The salt (default is empty) ; this salt will be composed using an internal algorithm to provide a much safe salt even if this is leaved empty ; recommended is to provide here a salt value ...
	 * @return BOOL 					Will return TRUE if password match or FALSE if not
	 */
	public static function checkpassword(string $plainpass, string $passhash, string $salt) : bool { // {{{SYNC-HASH-PASSWORD}}}
		//--
		if((string)trim((string)$plainpass) == '') {
			return false;
		} //end if
		//--
		$passhash = (string) trim((string)$passhash); // for check, this is OK, to trim !
		if((string)$passhash == '') {
			return false;
		} //end if
		//--
		if((string)trim((string)$salt) == '') {
			return false;
		} //end if
		//--
		if(self::validatepasshashformat((string)$passhash) !== true) { // {{{SYNC-AUTH-HASHPASS-FORMAT}}} ;  also checks {{{SYNC-AUTHADM-PASS-LENGTH}}}
			return false;
		} //end if
		//--
		$hash = (string) self::password((string)$plainpass, (string)$salt);
		if(((string)trim((string)$hash) == '') OR ((int)strlen((string)$hash) !== (int)self::PASSWORD_HASH_LENGTH)) { // {{{SYNC-AUTHADM-PASS-PADD}}}
			return false;
		} //end if
		if((string)$hash === (string)$passhash) {
			return true;
		} else {
			return false;
		} //end if else
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the Safe Pre-Derived B92 Key from a Key to be used in hash derived methods ; v3
	 * The purpose of this method is to provide a colission free pre-derived key from a string key (a password, an encrypt/decrypt key) to be used as the base to create a real derived key by hashing methods later
	 * It adds more entropy to a key, by combining SHA3-512/HEX with SHA3-384/B64 and finalizing in a B92 character set
	 * On the other hand, adds more safety to PBKDF2 which operates on a fixed length key, 80 characters, B92+
	 * It is intended to be used with PBKDF2 (Password-Based Key Derivation Function, version 2)
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param STRING 		$key 				The key (min 7 bytes ; max 4096 bytes)
	 * @return STRING 							The safe derived key, B92
	 */
	public static function pbkdf2PreDerivedB92Key(string $key) : string {
		//--
		// IMPORTANT: hash_pbkdf2 becomes extremely slow on long strings
		// for a large key as 4096 bytes key would take 30 seconds on regular hardware with a reasonable number of iterations
		// for a 255 characters string may 1 second ... this is too much for a web based application, thus this method provide fixes this issue ... by adding more entropy into a fixed 80 length key which takes 0.05 seconds
		// so reduce to a fixed size: 80 characters, unpredictable, B92+, using dual SHA3: 512 + 384 !
		//--
		$key  = (string) trim((string)$key); // {{{SYNC-CRYPTO-KEY-TRIM}}} ; always must use a trimmed key !
		$klen = (int)    strlen((string)$key);
		//--
		if((int)$klen < (int)self::DERIVE_MIN_KLEN) { // {{{SYNC-CRYPTO-KEY-MIN}}} ; allow 3 ... minimum acceptable secure key is 7 characters long
			Smart::log_warning(__METHOD__.' # The Key is too short');
			return ''; //  must be empty string, on err
		} elseif((int)$klen > (int)self::DERIVE_MAX_KLEN) {
			Smart::log_warning(__METHOD__.' # The Key is too long');
			return ''; //  must be empty string, on err
		} //end if else
		//--
		$b64 = (string) self::sh3a384((string)$key, true); // 64 chars fixed length, B64
		$hex = (string) self::sh3a512((string)$key."\v".self::crc32b((string)$key, true)."\v".Smart::dataRRot13((string)$b64)); // 128 chars fixed length, HEX
		$b92 = (string) Smart::base_from_hex_convert((string)$hex, 92); // variable length, 78..80 (mostly 79) characters, B92
		//--
		$preKey = (string) trim((string)Smart::dataRRot13((string)substr((string)str_pad((string)trim((string)$b92), (int)self::DERIVE_PREKEY_LEN, "'", STR_PAD_RIGHT), 0, (int)self::DERIVE_PREKEY_LEN))); // 80 chars fixed length, B92+
		//--
		if(
			((string)trim((string)$preKey) == '') // avoid being empty
			OR
			((string)trim((string)$preKey, "'") == '') // avoid being all '
			OR
			((int)strlen((string)$preKey) != (int)self::DERIVE_PREKEY_LEN)
		) {
			Smart::raise_error(__METHOD__.' # The B92 PBKDF2 Pre-Derived Key is empty or does not match the expected size ; required size is: '.(int)self::DERIVE_PREKEY_LEN.' bytes ; but the actual size is: '.(int)strlen((string)$preKey).' bytes');
			return ''; //  must be empty string, on err
		} //end if
		//--
		return (string) $preKey;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the PBKDF2 Derived Key (B92)
	 * The purpose of this method is to provide a colission free derived key from a string key (a password, an encrypt/decrypt key) to be used as the base to create a real derived key by hashing methods later
	 * It rely on PBKDF2 (Password-Based Key Derivation Function, version 2)
	 *
	 * @hint 	 	It uses the Safe Derived HEX Key to generate a HEX Key as ($len * 2) for enough length, then convert it to Base92 and the returned key lenth will be as required by method $len parameter
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param STRING 		$key 				The key (min 7 bytes ; max 4096 bytes)
	 * @param STRING 		$salt 				The salt ; it should not be empty
	 * @param INTEGER+ 		$len 				The derived key length
	 * @param INTEGER+ 		$iterations 		The number of iterations ; between 1 and 5000
	 * @param STRING 		$algo 				The hash algo ; *optional* ; default is sha3-512
	 * @return STRING 							The safe derived key, HEX
	 */
	public static function pbkdf2DerivedB92Key(string $key, string $salt, int $len, int $iterations, string $algo='sha3-512') : string { // {{{SYNC-CRYPTO-KEY-DERIVE}}}
		//--
		$hlen = (int) (2 * (int)$len); // ensure double size ; {{{SYNC-PBKDF2-HEX-TO-B92-LENGTH-ADJUST}}} ; should have enough length to ensure the same size because Base92 length shrinks after conversion from HEX (Base16)
		//--
		$hex = (string) self::pbkdf2DerivedHexKey((string)$key, (string)$salt, (int)$hlen, (int)$iterations, (string)$algo);
		$b92 = (string) Smart::base_from_hex_convert((string)$hex, 92); // variable length, depends on hex length
		//--
		$derived_key = (string) trim((string)substr((string)str_pad((string)$b92, (int)$len, "'", STR_PAD_RIGHT), 0, (int)$len)); // fixed length, as required, B92+
		//--
		if(
			((string)trim((string)$derived_key) == '') // avoid being empty
			OR
			((string)trim((string)$derived_key, "'") == '') // avoid being all '
			OR
			((int)strlen((string)$derived_key) != (int)$len)
		) {
			Smart::raise_error(__METHOD__.' # The B92 PBKDF2 Derived Key is empty or does not match the expected size ; required size is: '.(int)$len.' bytes ; but the actual size is: '.(int)strlen((string)$derived_key).' bytes');
			return ''; //  must be empty string, on err
		} //end if
		//--
		return (string) $derived_key;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the PBKDF2 Derived Key (Hex)
	 * The purpose of this method is to provide a colission free derived key from a string key (a password, an encrypt/decrypt key) to be used as the base to create a real derived key by hashing methods later
	 * It rely on PBKDF2 (Password-Based Key Derivation Function, version 2)
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param STRING 		$key 				The key (min 7 bytes ; max 4096 bytes)
	 * @param STRING 		$salt 				The salt ; it should not be empty
	 * @param INTEGER+ 		$len 				The derived key length
	 * @param INTEGER+ 		$iterations 		The number of iterations ; between 1 and 5000
	 * @param STRING 		$algo 				The hash algo ; *optional* ; default is sha3-512
	 * @return STRING 							The safe derived key, HEX
	 */
	public static function pbkdf2DerivedHexKey(string $key, string $salt, int $len, int $iterations, string $algo='sha3-512') : string { // {{{SYNC-CRYPTO-KEY-DERIVE}}}
		//--
		if((int)strlen((string)$key) < (int)self::DERIVE_MIN_KLEN) { // {{{SYNC-CRYPTO-KEY-MIN}}} ; allow 3 ... minimum acceptable secure key is 7 characters long
			Smart::log_warning(__METHOD__.' # The Key is too short');
			return ''; //  must be empty string, on err
		} elseif((int)strlen((string)$key) > (int)self::DERIVE_MAX_KLEN) {
			Smart::log_warning(__METHOD__.' # The Key is too long');
			return ''; //  must be empty string, on err
		} //end if else
		//--
		if((int)strlen((string)$salt) < (int)self::DERIVE_MIN_KLEN) {
			Smart::log_warning(__METHOD__.' # The Salt is too short');
			return ''; //  must be empty string, on err
		} elseif((int)strlen((string)$salt) > (int)self::DERIVE_MAX_KLEN) {
			Smart::log_warning(__METHOD__.' # The Salt is too long');
			return ''; //  must be empty string, on err
		} //end if else
		//--
		if((int)$len <= 0) {
			Smart::log_warning(__METHOD__.' # The length parameter is zero or negative');
			return ''; //  must be empty string, on err
		} //end if
		//--
		if($iterations < 1) {
			Smart::log_warning(__METHOD__.' # The Number of iterations is too low: '.(int)$iterations);
			$iterations = 1;
		} elseif((int)$iterations > 5000) {
			Smart::log_warning(__METHOD__.' # The Number of iterations is too large: '.(int)$iterations);
			$iterations = 5000;
		} //end if
		//--
		$derived_key = '';
		try { // starting from PHP 8.0, throws a ValueError exception on error ; previous false was returned and an E_WARNING message was emitted
			$derived_key = (string) hash_pbkdf2(
				(string) $algo, 		// algo
				(string) $key, 			// key
				(string) $salt, 		// salt
				(int)    $iterations, 	// iterations
				(int)    $len, 			// length
				(bool)   false 			// binary
			);
		} catch(Exception $e) { // this is fatal exception: ValueError !
			Smart::raise_error(__METHOD__.' # The hash_pbkdf2() Failed with ERROR: `'.$e->getMessage().'`');
			return ''; //  must be empty string, on err
		} //end try catch
		//--
		if(
			((string)trim((string)$derived_key) == '') // avoid being empty
			OR
			((string)trim((string)$derived_key, '0') == '') // avoid being all zeroes
			OR
			((int)strlen((string)$derived_key) != (int)$len)
		) {
			Smart::raise_error(__METHOD__.' # The HEX PBKDF2 Derived Key is empty or does not match the expected size ; required size is: '.(int)$len.' bytes ; but the actual size is: '.(int)strlen((string)$derived_key).' bytes');
			return ''; //  must be empty string, on err
		} //end if
		//--
		return (string) $derived_key;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the SHA3-512 hash of a string
	 *
	 * Length Extension Attack Resistance: 	1024 bits (128 bytes)
	 * Collision Attack Resistance: 		 256 bits ( 32 bytes)
	 *
	 * @param STRING $str 				String to be hashed
	 * @param BOOLEAN $b64 				If set to TRUE will use Base64 Encoding instead of Hex Encoding
	 * @return STRING 					The hash: 128 chars length (hex) or 88 chars length (b64) ; 4*ceil(64Chars/3)=87..88
	 */
	public static function sh3a512(?string $str, bool $b64=false) : string { // execution cost: 0.35 * 2
		//--
		if(!self::algo_check('sha3-512')) {
			Smart::raise_error(__METHOD__.' # ERROR: Smart.Framework Crypto Hash requires SHA3-512 Hash/Algo');
			return '';
		} //end if
		//--
		$hash = (string) hash('sha3-512', (string)$str, (bool)$b64);
		if($b64 === true) {
			$hash = (string) base64_encode((string)$hash);
		} //end if
		//--
		return (string) $hash;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the SHA512 (SHA2-512) hash of a string
	 *
	 * Length Extension Attack Resistance: 	  0 bits ( 0 bytes) ; This algo has ZERO resistance, do not use it for checksums that may be modified together with the content !
	 * Collision Attack Resistance: 		256 bits (32 bytes)
	 *
	 * @param STRING $str 				String to be hashed
	 * @param BOOLEAN $b64 				If set to TRUE will use Base64 Encoding instead of Hex Encoding
	 * @return STRING 					The hash: 128 chars length (hex) or 88 chars length (b64) ; 4*ceil(64Chars/3)=87..88
	 */
	public static function sha512(?string $str, bool $b64=false) : string { // execution cost: 0.35
		//--
		if(!self::algo_check('sha512')) {
			Smart::raise_error(__METHOD__.' # ERROR: Smart.Framework Crypto Hash requires SHA512 Hash/Algo');
			return '';
		} //end if
		//--
		$hash = (string) hash('sha512', (string)$str, (bool)$b64);
		if($b64 === true) {
			$hash = (string) base64_encode((string)$hash);
		} //end if
		//--
		return (string) $hash;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the SHA3-384 hash of a string
	 *
	 * Length Extension Attack Resistance: 	768 bits (96 bytes)
	 * Collision Attack Resistance: 		192 bits (24 bytes)
	 *
	 * @param STRING $str 				String to be hashed
	 * @param BOOLEAN $b64 				If set to TRUE will use Base64 Encoding instead of Hex Encoding
	 * @return STRING 					The hash: 96 chars length (hex) or 64 chars length (b64)
	 */
	public static function sh3a384(?string $str, bool $b64=false) : string { // execution cost: 0.21 * 2
		//--
		if(!self::algo_check('sha3-384')) {
			Smart::raise_error(__METHOD__.' # ERROR: Smart.Framework Crypto Hash requires SHA3-384 Hash/Algo');
			return '';
		} //end if
		//--
		$hash = (string) hash('sha3-384', (string)$str, (bool)$b64);
		if($b64 === true) {
			$hash = (string) base64_encode((string)$hash);
		} //end if
		//--
		return (string) $hash;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the SHA384 (SHA2-384) hash of a string
	 *
	 * It is roughly 50% faster than SHA256 (SHA2-256) on 64-bit machines
	 * It also has resistances to length extension attack (due to truncation) ; notice that by example SHA2-512 or SHA2-256 does not have this resistance !
	 *
	 * Length Extension Attack Resistance: 	128 bits (16 bytes)
	 * Collision Attack Resistance: 		192 bits (24 bytes)
	 *
	 * @param STRING $str 				String to be hashed
	 * @param BOOLEAN $b64 				If set to TRUE will use Base64 Encoding instead of Hex Encoding
	 * @return STRING 					The hash: 96 chars length (hex) or 64 chars length (b64)
	 */
	public static function sha384(?string $str, bool $b64=false) : string { // execution cost: 0.21
		//--
		if(!self::algo_check('sha384')) {
			Smart::raise_error(__METHOD__.' # ERROR: Smart.Framework Crypto Hash requires SHA384 Hash/Algo');
			return '';
		} //end if
		//--
		$hash = (string) hash('sha384', (string)$str, (bool)$b64);
		if($b64 === true) {
			$hash = (string) base64_encode((string)$hash);
		} //end if
		//--
		return (string) $hash;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the SHA3-256 hash of a string
	 *
	 * Length Extension Attack Resistance: 	512 bits (64 bytes)
	 * Collision Attack Resistance: 		128 bits (16 bytes)
	 *
	 * @param STRING $str 				String to be hashed
	 * @param BOOLEAN $b64 				If set to TRUE will use Base64 Encoding instead of Hex Encoding
	 * @return STRING 					The hash: 64 chars length (hex) or 44 chars length (b64)
	 */
	public static function sh3a256(?string $str, bool $b64=false) : string { // execution cost: 0.20 * 2
		//--
		if(!self::algo_check('sha3-256')) {
			Smart::raise_error(__METHOD__.' # ERROR: Smart.Framework Crypto Hash requires SHA3-256 Hash/Algo');
			return '';
		} //end if
		//--
		$hash = (string) hash('sha3-256', (string)$str, (bool)$b64);
		if($b64 === true) {
			$hash = (string) base64_encode((string)$hash);
		} //end if
		//--
		return (string) $hash;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the SHA256 (SHA2-256) hash of a string
	 *
	 * Length Extension Attack Resistance: 	  0 bits ( 0 bytes) ; This algo has ZERO resistance, do not use it for checksums that may be modified together with the content !
	 * Collision Attack Resistance: 		128 bits (16 bytes)
	 *
	 * @param STRING $str 				String to be hashed
	 * @param BOOLEAN $b64 				If set to TRUE will use Base64 Encoding instead of Hex Encoding
	 * @return STRING 					The hash: 64 chars length (hex) or 44 chars length (b64)
	 */
	public static function sha256(?string $str, bool $b64=false) : string { // execution cost: 0.20
		//--
		if(!self::algo_check('sha256')) {
			Smart::raise_error(__METHOD__.' # ERROR: Smart.Framework Crypto Hash requires SHA256 Hash/Algo');
			return '';
		} //end if
		//--
		$hash = (string) hash('sha256', (string)$str, (bool)$b64);
		if($b64 === true) {
			$hash = (string) base64_encode((string)$hash);
		} //end if
		//--
		return (string) $hash;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the SHA3-224 hash of a string
	 *
	 * Length Extension Attack Resistance: 	448 bits (56 bytes)
	 * Collision Attack Resistance: 		112 bits (14 bytes)
	 *
	 * @param STRING $str 				String to be hashed
	 * @param BOOLEAN $b64 				If set to TRUE will use Base64 Encoding instead of Hex Encoding
	 * @return STRING 					The hash: 56 chars length (hex) or 40 chars length (b64)
	 */
	public static function sh3a224(?string $str, bool $b64=false) : string { // execution cost: 0.18 * 2
		//--
		if(!self::algo_check('sha3-224')) {
			Smart::raise_error(__METHOD__.' # ERROR: Smart.Framework Crypto Hash requires SHA3-224 Hash/Algo');
			return '';
		} //end if
		//--
		$hash = (string) hash('sha3-224', (string)$str, (bool)$b64);
		if($b64 === true) {
			$hash = (string) base64_encode((string)$hash);
		} //end if
		//--
		return (string) $hash;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the SHA224 (SHA2-224) hash of a string
	 *
	 * Length Extension Attack Resistance: 	  0 bits ( 0 bytes) ; This algo has ZERO resistance, do not use it for checksums that may be modified together with the content !
	 * Collision Attack Resistance: 		128 bits (16 bytes)
	 *
	 * @param STRING $str 				String to be hashed
	 * @param BOOLEAN $b64 				If set to TRUE will use Base64 Encoding instead of Hex Encoding
	 * @return STRING 					The hash: 56 chars length (hex) or 40 chars length (b64)
	 */
	public static function sha224(?string $str, bool $b64=false) : string { // execution cost: 0.18
		//--
		if(!self::algo_check('sha224')) {
			Smart::raise_error(__METHOD__.' # ERROR: Smart.Framework Crypto Hash requires SHA224 Hash/Algo');
			return '';
		} //end if
		//--
		$hash = (string) hash('sha224', (string)$str, (bool)$b64);
		if($b64 === true) {
			$hash = (string) base64_encode((string)$hash);
		} //end if
		//--
		return (string) $hash;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the SHA1 (SHA-1) hash of a string
	 *
	 * @param STRING $str 				String to be hashed
	 * @param BOOLEAN $b64 				If set to TRUE will use Base64 Encoding instead of Hex Encoding
	 * @return STRING 					The hash: 40 chars length (hex) or 28 chars length (b64)
	 */
	public static function sha1(?string $str, bool $b64=false) : string { // execution cost: 0.14
		//--
		if(!self::algo_check('sha1')) {
			Smart::raise_error(__METHOD__.' # ERROR: Smart.Framework Crypto Hash requires SHA1 Hash/Algo');
			return '';
		} //end if
		//--
		$hash = (string) hash('sha1', (string)$str, (bool)$b64);
		if($b64 === true) {
			$hash = (string) base64_encode((string)$hash);
		} //end if
		//--
		return (string) $hash;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the MD5 hash of a string
	 *
	 * @param STRING $str 				String to be hashed
	 * @param BOOLEAN $b64 				If set to TRUE will use Base64 Encoding instead of Hex Encoding
	 * @return STRING 					The hash: 32 chars length (hex) or 24 chars length (b64)
	 */
	public static function md5(?string $str, bool $b64=false) : string { // execution cost: 0.13
		//--
		if(!self::algo_check('md5')) {
			Smart::raise_error(__METHOD__.' # ERROR: Smart.Framework Crypto Hash requires MD5 Hash/Algo');
			return '';
		} //end if
		//--
		$hash = (string) hash('md5', (string)$str, (bool)$b64);
		if($b64 === true) {
			$hash = (string) base64_encode((string)$hash);
		} //end if
		//--
		return (string) $hash;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the HMAC hash of a string with support for various algorithms
	 * @param ENUM $algo 				The hashing algo: md5, sha1, sha224, sha256, sha384, sha512, sha3-224, sha3-256, sha3-384, sha3-512
	 * @param STRING $key 				The secret key
	 * @param STRING $str 				The public message to be hashed
	 * @param BOOLEAN $b64 				If set to TRUE will use Base64 Encoding instead of Hex Encoding
	 * @return STRING 					The Hmac Hash of the string, salted by key (Hex or B64) ; length vary depending on the algorithm
	 */
	public static function hmac(?string $algo, ?string $key, ?string $str, bool $b64=false) : string { // execution cost: 0.21 .. 0.35 * 2 (depending upon the hashing algorithm)
		//--
		$algo = (string) strtolower((string)trim((string)$algo));
		//--
		if((string)$algo == '') {
			Smart::raise_error(__METHOD__.' # ERROR: Smart.Framework Crypto Hmac Hash :: Algo parameter is Empty');
			return '';
		} //end if
		//--
		if(!self::hmac_algo_check((string)$algo)) {
			Smart::raise_error(__METHOD__.' # ERROR: Smart.Framework Crypto Hmac Hash requires `'.$algo.'` Hmac/Hash/Algo');
			return '';
		} //end if
		//--
		$hash = (string) hash_hmac((string)$algo, (string)$str, (string)$key, (bool)$b64);
		if($b64 === true) {
			$hash = (string) base64_encode((string)$hash);
		} //end if
		//--
		return (string) $hash;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the CRC32B polynomial sum of a string ; can use base16/hex (default) or base36 optional
	 * CRC32B is better than CRC32, being portable between 32-bit and 64-bit platforms, is unsigned
	 *
	 * @param STRING $str 				The data
	 * @param BOOLEAN $b36 				If set to TRUE will use Base36 Encoding (0-9,A-Z) instead of default Hex (Base16) Encoding
	 * @return STRING 					The hash: 8 chars length (hex, max is FFFFFFFF) or 7 chars length (b36, max is 1Z141Z3, based on max hex)
	 */
	public static function crc32b(?string $str, bool $b36=false) : string { // execution cost: 0.21
		//--
		if(!self::algo_check('crc32b')) {
			Smart::raise_error(__METHOD__.' # ERROR: Smart.Framework Crypto Hash requires CRC32B Hash/Algo');
			return '';
		} //end if
		//--
		$hash = (string) hash('crc32b', (string)$str, false);
		//--
		if($b36 === true) {
			//-- $hash = (string) str_pad((string)base_convert((string)$hash, 16, 36), 7, '0', STR_PAD_LEFT); // 10x faster but unsafe on 32-bit platforms ...
			$hash = (string) str_pad((string)Smart::base_from_hex_convert((string)$hash, 36), 7, '0', STR_PAD_LEFT); // ensure fixed length: 7
			//--
		} else {
			//--
			$hash = (string) str_pad((string)$hash, 8, '0', STR_PAD_LEFT); // ensure fixed length: 8
			//--
		} //end if
		//--
		return (string) $hash;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the Poly1305 hash (polynomial 1305 sum) of a string, derived from the given secret key when the object was constructed
	 * @param STRING $key 				The secret key
	 * @param STRING $str 				The public message to be hashed
	 * @param BOOLEAN $b64 				If set to TRUE will use Base64 Encoding instead of Hex Encoding
	 * @return STRING 					The Poly1305 hash sum: 32 bytes length (hex) or 16 bytes (raw binary crypto data)
	 */
	public static function poly1305(?string $key, ?string $str, bool $b64=false) : string { // execution cost: 0.21
		//--
		if((int)strlen((string)$key) != 32) {
			Smart::raise_error(__METHOD__.' # Key length is invalid, must be 32 bytes !');
			return '';
		} //end if
		//--
		$sum = (string) (new SmartHashPoly1305((string)$key))->getSum((string)$str);
		//--
		if($b64 === true) {
			$sum = (string) base64_encode((string)$sum);
		} else {
			$sum = (string) bin2hex((string)$sum);
		} //end if else
		//--
		return (string) $sum;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	public static function ed25519_sign(string $message, ?string $secret) : array {
		//--
		$data = [
			'error' 		=> '?',
			'provider' 		=> 'LibSodium',
			'algo' 			=> 'Ed25519',
			'private-key' 	=> '',
			'public-key' 	=> '',
			'signature' 	=> '',
			'encoding' 		=> 'Base64'
		];
		//--
		if(!function_exists('sodium_crypto_sign_seed_keypair')) {
			$data['error'] = 'PHP Sodium Extension is missing';
			return (array) $data;
		} //end if
		//--
		if((string)$message == '') {
			$data['error'] = 'Message is Empty';
			return (array) $data;
		} //end if
		//--
		$keyPair = '';
		if($secret === null) {
			try {
				$keyPair = (string) sodium_crypto_sign_keypair();
			} catch(Exception $e) {
				$data['error'] = 'Random KeyPair Generation Failed: # '.$e->getMessage();
				return (array) $data;
			} //end try catch
		} else if((int)strlen((string)$secret) == 32) {
			try {
				$keyPair = (string) sodium_crypto_sign_seed_keypair((string)$secret);
			} catch(Exception $e) {
				$data['error'] = 'Secret Based KeyPair Generation Failed: # '.$e->getMessage();
				return (array) $data;
			} //end try catch
		} else {
			$data['error'] = 'Secret must be exact 32 bytes';
			return (array) $data;
		} //end if
		if((string)$keyPair == '') {
			$data['error'] = 'Key Pair generation Failed: Empty';
			return (array) $data;
		} //end if
		//--
		$privateKey = '';
		try {
			$privateKey = (string) sodium_crypto_sign_secretkey((string)$keyPair);
		} catch(Exception $e) {
			$data['error'] = 'Private Key Generation Failed: # '.$e->getMessage();
			return (array) $data;
		} //end try catch
		if((string)$privateKey == '') {
			$data['error'] = 'Private Key generation Failed: Empty';
			return (array) $data;
		} //end if
		//--
		$publicKey = '';
		try {
			$publicKey  = (string) sodium_crypto_sign_publickey_from_secretkey((string)$privateKey);
		} catch(Exception $ex) {
			try {
				$publicKey = (string) sodium_crypto_sign_publickey((string)$keyPair);
			} catch(Exception $e) {
				$data['error'] = 'Public Key Generation Failed: # '.$e->getMessage();
				return (array) $data;
			} //end try catch
		} //end try catch
		if((string)$publicKey == '') {
			$data['error'] = 'Public Key generation Failed: Empty';
			return (array) $data;
		} //end if
		//--
		$signature = '';
		try {
			$signature = (string) sodium_crypto_sign_detached((string)$message, (string)$privateKey);
		} catch(Exception $e) {
			$data['error'] = 'Signature Generation Failed: # '.$e->getMessage();
			return (array) $data;
		} //end try catch
		if((string)$signature == '') {
			$data['error'] = 'Signature generation Failed: Empty';
			return (array) $data;
		} //end if
		//--
		$data['error'] 			= ''; // clear
		$data['private-key'] 	= (string) base64_encode((string)$privateKey);
		$data['public-key'] 	= (string) base64_encode((string)$publicKey);
		$data['signature'] 		= (string) base64_encode((string)$signature);
		return (array) $data;
		//--
	} //END FUNCTION
	//==============================================================


	//##### PRIVATES


	//==============================================================
	private static function algo_check(string $algo) : bool {
		//--
		if(in_array((string)$algo, (array)self::algos())) {
			return true;
		} //end if
		//--
		return false;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	private static function algos() : array {
		//--
		if((!array_key_exists('algos', (array)self::$cache)) OR (!is_array(self::$cache['algos']))) {
			self::$cache['algos'] = (array) hash_algos();
		} //end if else
		//--
		return (array) self::$cache['algos'];
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	private static function hmac_algo_check(string $algo) : bool {
		//--
		if(in_array((string)$algo, (array)self::hmac_algos())) {
			return true;
		} // end if
		//--
		return false;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	private static function hmac_algos() : array {
		//--
		if((!array_key_exists('algos-hmac', (array)self::$cache)) OR (!is_array(self::$cache['algos-hmac']))) {
			self::$cache['algos-hmac'] = (array) hash_hmac_algos();
		} //end if else
		//--
		return (array) self::$cache['algos-hmac'];
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


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

// Copyright: (c) 2014 Leigh T # github.com/lt/php-poly1305
// License: MIT

// (c) 2023 unix-world.org
// License: BSD
// This is a modified and improved version of the original code

/**
 * Class: SmartHashPoly1305 - implements Poly1305, a universal polynomial hashing (sum) method
 *
 * The Poly1305 hash sum for a string, based on the secret key and, is always 16 bytes (128 bit).
 * This implements a very strong cryptography algorithm used in many secret key derivations.
 *
 * It takes a 32-byte one-time key and a message and produces a 16-byte tag
 * Another purpose can be to share a secret key between sender and recipient, similar to the way that a one-time pad can be used to conceal the content of a single message using a secret key shared between sender and recipient
 *
 * Important: this class is non-reusable, because it acts as a context container, will be locked after getting the hash.
 * After getting a Poly1305 hash (sum), it must be destructed. If need for another Poly1305 hash, create a new instance ...
 *
 * THIS CLASS IS FOR PRIVATE USE.
 * @access 		private
 * @internal
 *
 * @usage       dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 * @hints       The Poly1305 Algorithm Poly1305 is a one-way secure hashing method, as a one-time authenticator, or exchanging secret keys
 *
 * @access      PUBLIC
 * @depends     classes: Smart
 * @version     v.20231114
 * @package     @Core:Crypto
 *
 */
final class SmartHashPoly1305 {

	// ->

	//--
	private $r;
	private $s;
	private $h;
	private $buffer;
	private $hibit;
	//--
	private $lck; // getSum lock
	private $ini; // init lock
	private $upd; // update lock
	private $mac; // finish lock
	//--


	//==============================================================
	/**
	 * Constructor
	 * Initializes the Poly1305 object
	 *
	 * @param STRING $key 				The secret key
	 *
	 * @access public
	 */
	public function __construct(string $key) {
		//--
		if(
			((string)trim((string)$key) == '')
			OR
			((int)strlen((string)trim((string)$key)) != 32)
			OR
			((int)strlen((string)$key) != 32)
		) {
			Smart::raise_error(__METHOD__.' # ERROR: The Poly1305 Key must be 32 bytes, exactly !');
			return;
		} //end if
		//--
		$this->lck = false;
		$this->ini = false;
		$this->upd = false;
		$this->mac = null;
		//--
		$this->init((string)$key);
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the Poly1305 hash (polynomial 1305 sum) of a string, derived from the given secret key when the object was constructed
	 * @param STRING $str 				The public message to be hashed
	 * @return STRING 					The Poly1305 hash sum: 32 bytes length (hex) or 16 bytes (raw binary crypto data)
	 */
	public function getSum(string $str) : string {
		//--
		// do not trim $str !
		// should return a sum also for an empty string
		//--
		if($this->lck === true) {
			Smart::log_notice(__METHOD__.' # This Poly1305 Instance already Freezed, create a new Instance ...');
			return '';
		} //end if
		$this->lck = true;
		//--
		$this->update((string)$str);
		$this->finish();
		//--
		$sum = (string) $this->mac; // do not trim, it is binary raw data !
		//--
		$len = (int) strlen((string)$sum);
		if((int)$len !== 16) {
			Smart::raise_error(__METHOD__.' # ERROR: Poly1305 Hash Sum (raw) must have 16 bytes length (128 bit) but have: '.(int)$len.' bytes');
			return '';
		} //end if
		//--
		return (string) $sum;
		//--
	} //END FUNCTION
	//==============================================================


	//======== [PRIVATES]


	//==============================================================
	private function init(string $key) : void {
		//--
		if($this->ini === true) {
			//--
			Smart::log_notice(__METHOD__.' # Already Initialized ...');
			return;
			//--
		} //end if
		$this->ini = true;
		//--
		if((int)strlen((string)$key) !== 32) {
			//--
			Smart::raise_error(__METHOD__.' # ERROR: Key must be a 256-bit string');
			return;
			//--
		} //end if
		//--
		$words = unpack('v8', (string)$key);
		if((int)Smart::array_size($words) !== 8) {
			Smart::raise_error(__METHOD__.' # ERROR: Init Unpack Failed (1)');
			return;
		} //end if
		//--
		$this->r = [
			( $words[1]        | ($words[2] << 16))                     & 0x3ffffff,
			(($words[2] >> 10) | ($words[3] <<  6) | ($words[4] << 22)) & 0x3ffff03,
			(($words[4] >>  4) | ($words[5] << 12))                     & 0x3ffc0ff,
			(($words[5] >> 14) | ($words[6] <<  2) | ($words[7] << 18)) & 0x3f03fff,
			(($words[7] >>  8) | ($words[8] <<  8))                     & 0x00fffff,
		];
		//--
		$words = unpack('@16/v8', (string)$key);
		if((int)Smart::array_size($words) <= 0) {
			Smart::raise_error(__METHOD__.' # ERROR: Init Unpack Failed (1)');
			return;
		} //end if
		$this->s = [
			( $words[1]        | ($words[2] << 16))                     & 0x3ffffff,
			(($words[2] >> 10) | ($words[3] <<  6) | ($words[4] << 22)) & 0x3ffffff,
			(($words[4] >>  4) | ($words[5] << 12))                     & 0x3ffffff,
			(($words[5] >> 14) | ($words[6] <<  2) | ($words[7] << 18)) & 0x3ffffff,
			(($words[7] >>  8) | ($words[8] <<  8))                     & 0x0ffffff,
		];
		//--
		$this->h = [ 0, 0, 0, 0, 0 ];
		$this->buffer = '';
		$this->hibit = 0x1000000;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	private function update(string $str) : void {
		//--
		if($this->upd === true) {
			Smart::log_notice(__METHOD__.' # Already Updated ...');
			return;
		} //end if
		$this->upd = true;
		//--
		if($this->ini !== true) {
			Smart::log_notice(__METHOD__.' # Not yet Initialised ...');
			return;
		} //end if
		//--
		if((string)$this->buffer != '') {
			$str = (string) $this->buffer.$str;
			$this->buffer = '';
		} //end if
		//--
		$offset = 0;
		//--
		$hibit = $this->hibit;
		list($r0, $r1, $r2, $r3, $r4) = $this->r;
		//--
		$s1 = 5 * $r1;
		$s2 = 5 * $r2;
		$s3 = 5 * $r3;
		$s4 = 5 * $r4;
		//--
		list($h0, $h1, $h2, $h3, $h4) = $this->h;
		//--
		$msgLen = (int) strlen((string)$str);
		$blocks = (int) ($msgLen >> 4);
		//--
		while($blocks--) {
			//--
			$words = unpack('@'.(int)$offset.'/v8', (string)$str);
			if((int)Smart::array_size($words) !== 8) {
				Smart::raise_error(__METHOD__.' # ERROR: Init Unpack Failed');
				return;
			} //end if
			//--
			$h0 += ( $words[1]        | ($words[2] << 16))                     & 0x3ffffff;
			$h1 += (($words[2] >> 10) | ($words[3] <<  6) | ($words[4] << 22)) & 0x3ffffff;
			$h2 += (($words[4] >>  4) | ($words[5] << 12))                     & 0x3ffffff;
			$h3 += (($words[5] >> 14) | ($words[6] <<  2) | ($words[7] << 18)) & 0x3ffffff;
			$h4 += (($words[7] >>  8) | ($words[8] <<  8))                     | $hibit;
			//--
			$hr0 = ($h0 * $r0) + ($h1 * $s4) + ($h2 * $s3) + ($h3 * $s2) + ($h4 * $s1);
			$hr1 = ($h0 * $r1) + ($h1 * $r0) + ($h2 * $s4) + ($h3 * $s3) + ($h4 * $s2);
			$hr2 = ($h0 * $r2) + ($h1 * $r1) + ($h2 * $r0) + ($h3 * $s4) + ($h4 * $s3);
			$hr3 = ($h0 * $r3) + ($h1 * $r2) + ($h2 * $r1) + ($h3 * $r0) + ($h4 * $s4);
			$hr4 = ($h0 * $r4) + ($h1 * $r3) + ($h2 * $r2) + ($h3 * $r1) + ($h4 * $r0);
			//--
						$c = $hr0 >> 26; $h0 = $hr0 & 0x3ffffff;
			$hr1 += $c; $c = $hr1 >> 26; $h1 = $hr1 & 0x3ffffff;
			$hr2 += $c; $c = $hr2 >> 26; $h2 = $hr2 & 0x3ffffff;
			$hr3 += $c; $c = $hr3 >> 26; $h3 = $hr3 & 0x3ffffff;
			$hr4 += $c; $c = $hr4 >> 26; $h4 = $hr4 & 0x3ffffff;
			$h0 += 5 * $c; $c = $h0 >> 26; $h0 &= 0x3ffffff;
			$h1 += $c;
			//--
			$offset += 16;
			//--
		} //end while
		//--
		$this->h = [ $h0, $h1, $h2, $h3, $h4 ];
		//--
		if((int)$offset < (int)$msgLen) {
			$this->buffer = (string) substr((string)$str, (int)$offset);
		} //end if
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	private function finish() : void {
		//--
		if($this->mac !== null) {
			Smart::log_notice(__METHOD__.' # Already Finalized ...');
			return;
		} //end if
		$this->upd = true;
		//--
		if($this->ini !== true) {
			Smart::log_notice(__METHOD__.' # Not yet Initialised ...');
			return;
		} //end if
		//--
		if($this->upd !== true) {
			Smart::log_notice(__METHOD__.' # Not yet Updated ...');
			return;
		} //end if
		//--
		if($this->buffer) {
			$this->hibit = 0;
			$this->upd = false; // update reset required, to be able to run again update ...
			$this->update("\1".str_repeat("\0", 15 - (int)strlen((string)$this->buffer)));
		} //end if
		//--
		list($h0, $h1, $h2, $h3, $h4) = $this->h;
		//--
				   $c = $h1 >> 26; $h1 &= 0x3ffffff;
		$h2 += $c; $c = $h2 >> 26; $h2 &= 0x3ffffff;
		$h3 += $c; $c = $h3 >> 26; $h3 &= 0x3ffffff;
		$h4 += $c; $c = $h4 >> 26; $h4 &= 0x3ffffff;
		$h0 += 5 * $c; $c = $h0 >> 26; $h0 &= 0x3ffffff;
		$h1 += $c;
		//--
		$g0 = $h0  + 5; $c = $g0 >> 26; $g0 &= 0x3ffffff;
		$g1 = $h1 + $c; $c = $g1 >> 26; $g1 &= 0x3ffffff;
		$g2 = $h2 + $c; $c = $g2 >> 26; $g2 &= 0x3ffffff;
		$g3 = $h3 + $c; $c = $g3 >> 26; $g3 &= 0x3ffffff;
		$g4 = ($h4 + $c - (1 << 26)) & 0xffffffff;
		//--
		$mask = ($g4 >> 31) - 1;
		$g0 &= $mask;
		$g1 &= $mask;
		$g2 &= $mask;
		$g3 &= $mask;
		$g4 &= $mask;
		$mask = ~$mask & 0xffffffff;
		$h0 = ($h0 & $mask) | $g0;
		$h1 = ($h1 & $mask) | $g1;
		$h2 = ($h2 & $mask) | $g2;
		$h3 = ($h3 & $mask) | $g3;
		$h4 = ($h4 & $mask) | $g4;
		//--
		list($s0, $s1, $s2, $s3, $s4) = $this->s;
		//--
		$c = $h0 + $s0;              $h0 = $c & 0x3ffffff;
		$c = $h1 + $s1 + ($c >> 26); $h1 = $c & 0x3ffffff;
		$c = $h2 + $s2 + ($c >> 26); $h2 = $c & 0x3ffffff;
		$c = $h3 + $s3 + ($c >> 26); $h3 = $c & 0x3ffffff;
		$c = $h4 + $s4 + ($c >> 26); $h4 = $c & 0x0ffffff;
		//--
		$this->mac = (string) pack('v8',
			 $h0,
			(($h0 >> 16) | ($h1 << 10)),
			 ($h1 >>  6),
			(($h1 >> 22) | ($h2 <<  4)),
			(($h2 >> 12) | ($h3 << 14)),
			 ($h3 >>  2),
			(($h3 >> 18) | ($h4 <<  8)),
			( $h4 >>  8)
		);
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
 * Class Smart.Framework CSRF
 * A Safety Handler against Cross-Site Request Forgery (CSRF)
 *
 * <code>
 * // Usage example:
 * SmartCsrf::some_method_of_this_class(...);
 * </code>
 *
 * @usage 		static object: Class::method() - This class provides only STATIC methods
 * @hints 		This is generally intended for advanced usage !
 *
 * @depends 	Smart, SmartHashCrypto
 *
 * @version 	v.20231114
 * @package 	Application
 *
 */
final class SmartCsrf {

	// ::


	//==============================================================
	// this is intended to store in a private session ; if need to be exposed in a cookie (public) it have to be encrypted, ex: Blowfish !
	public static function newPrivateKey() : string {
		//--
		return (string) Smart::uuid_35(); // case sensitive
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	// returns a public key hash that can be public exposed, it is just a one-way hash, safe
	public static function getPublicKey(string $privKey, string $secret) : string {
		//--
		$privKey = (string) trim((string)$privKey); // the unencrypted private key
		if((string)$privKey == '') {
			return '';
		} //end if
		//--
		if((string)trim((string)$secret) == '') { // a secret
			return '';
		} //end if
		//--
		return SmartHashCrypto::checksum((string)$secret.chr(0).$privKey); // B62, SHA3-384
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	// will check if the keys match
	public static function verifyKeys(string $pubKey, string $privKey, string $secret) : bool {
		//--
		$pubKey = (string) trim((string)$pubKey);
		$privKey = (string) trim((string)$privKey);
		//--
		$ok = false;
		//--
		if(
			((string)$pubKey != '')
			AND
			((string)$privKey != '')
			AND
			((string)trim((string)$secret) != '')
		) {
			//--
			if((string)$pubKey === (string)self::getPublicKey((string)$privKey, (string)$secret)) {
				$ok = true;
			} //end if
			//--
		} //end if
		//--
		return (bool) $ok;
		//--
	} //END FUNCTION
	//==============================================================



} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
