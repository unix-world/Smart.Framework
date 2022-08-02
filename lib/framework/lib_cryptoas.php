<?php
// [LIB - Smart.Framework / Symmetric and Asymmetric Crypto]
// (c) 2006-2022 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - Symmetric and Asymmetric Crypto Support
// 		* symmetric (encrypt/decrypt): BlowFish (CBC) built-in / BlowFish/AES256/Camellia256 (CBC/CFB/OFB via OpenSSL)
// 		* asymmetric (shad key): DhKx built-in
//======================================================
// NOTICE: This is unicode safe
//======================================================

// [PHP8] r.20210903


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

/**
 * Class: SmartCipherCrypto
 * Provides a built-in based feature to handle the Blowfish (CBC) encryption / decryption.
 * This provides an advanced crypto handler for Blowfish CBC algorithm.
 *
 * <code>
 * // Usage example:
 * SmartCipherCrypto::some_method_of_this_class(...);
 * </code>
 *
 * @usage       static object: Class::method() - This class provides only STATIC methods
 * @hints       Blowfish is a 64-bit (8 bytes) block cipher. Max Key is up to 56 chars length (56 bytes = 448 bits). The CBC mode requires a initialization vector (iv).
 *
 * @depends     classes: SmartFrameworkRegistry, Smart, SmartCryptoCipherBlowfishCBC, SmartCryptoOpenSSLCipher, SmartCryptoCipherHash
 * @version     v.20210825
 * @package     @Core:Crypto
 *
 */
final class SmartCipherCrypto {

	// ::

	//==============================================================
	/**
	 * Encrypts a string using the selected Cipher Algo.
	 *
	 * @param ENUM $cipher 		Selected cipher: hash/{mode}, blowfish.cbc, openssl/cipher/mode
	 * @param STRING $key 		The encryption key (must be between 7 and 4096 bytes)
	 * @param STRING $data 		The plain data to be encrypted
	 * @return STRING 			The encrypted data as B64S or empty string on error
	 */
	public static function encrypt(?string $cipher, ?string $key, ?string $data) {
		//--
		$crypto = self::crypto((string)$cipher, (string)$key);
		if(!is_object($crypto)) {
			Smart::log_warning(__METHOD__.' # ERROR: '.$crypto);
			return '';
		} //end if
		//--
		if((string)trim((string)$data) == '') {
			return '';
		} //end if
		//--
		return (string) $crypto->encrypt((string)$data);
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Decrypts a string using the selected Cipher Algo.
	 *
	 * @param ENUM $cipher 		Selected cipher: hash/{mode}, blowfish.cbc, openssl/cipher/mode
	 * @param STRING $key 		The encryption key (must be between 7 and 4096 bytes)
	 * @param STRING $data 		The encrypted data
	 * @return STRING 			The plain (decrypted) data or empty string on error
	 */
	public static function decrypt(?string $cipher, ?string $key, ?string $data) {
		//--
		$crypto = self::crypto((string)$cipher, (string)$key);
		if(!is_object($crypto)) {
			Smart::log_warning(__METHOD__.' # ERROR: '.$crypto);
			return '';
		} //end if
		//--
		if((string)trim((string)$data) == '') {
			return '';
		} //end if
		//--
		return (string) $crypto->decrypt((string)$data);
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Encrypts a string using the Blowfish CBC 448bit (v2) or the Blowfish CBC 384bit (v1)
	 * This is intended to be used for persistent data (ex: data storage)
	 * It will be always supported even if the openssl blowfish support will dissapear the built-in support will be available
	 *
	 * @access 		private		Use the alias method from lib utils !
	 * @internal
	 *
	 * @param STRING $key 		The encryption key (must be between 7 and 4096 bytes)
	 * @param STRING $data 		The plain data to be encrypted
	 * @param ENUM $cipher 		Selected cipher: blowfish.cbc (default) | openssl/blowfish/CBC
	 * @return STRING 			The encrypted data as B64S or empty string on error
	 */
	public static function blowfish_encrypt(?string $key, ?string $data, ?string $cipher=null) {
		//--
		if((string)$cipher !== 'openssl/blowfish/CBC') {
			$cipher = 'blowfish.cbc'; // fall back to internal
		} //end if
		//--
		if((string)$data == '') { // do not trim, preserve original data !
			return '';
		} //end if
		//--
		return (string) 'bf'.(56*8).'.'.'v2'.'!'.self::encrypt((string)$cipher, (string)$key, (string)$data);
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Decrypts a string using the Blowfish CBC 448bit (v2)
	 * This is intended to be used for persistent data (ex: data storage)
	 * It will be always supported even if the openssl blowfish support will dissapear the built-in support will be available
	 *
	 * @access 		private		Use the alias method from lib utils !
	 * @internal
	 *
	 * @param STRING $key 		The encryption key (must be between 7 and 4096 bytes)
	 * @param STRING $data 		The plain data to be encrypted
	 * @param ENUM $cipher 		Selected cipher: blowfish.cbc (default) | openssl/blowfish/CBC
	 * @return STRING 			The encrypted data as B64S or empty string on error
	 */
	public static function blowfish_decrypt(?string $key, ?string $data, ?string $cipher=null) {
		//--
		if((string)$cipher !== 'openssl/blowfish/CBC') {
			$cipher = 'blowfish.cbc'; // fall back to internal
		} //end if
		//--
		$data = (string) trim((string)$data);
		if((string)$data == '') {
			return '';
		} //end if
		//--
		$version = 2;
		if(strpos((string)$data, 'bf'.(56*8).'.'.'v2'.'!') !== 0) {
			if(strpos((string)$data, 'bf'.(48*8).'.'.'v1'.'!') === 0) {
				$data = (array) explode('!', (string)$data, 2);
				$data = (string) trim((string)($data[1] ?? null));
			} //end if
			if(!preg_match('/^['.preg_quote((string)strtoupper((string)Smart::CHARSET_BASE_16)).']+$/', (string)$data)) {
				return ''; // stop here, malformed data, must be hex all upper as v1 implemented
			} //end if
			$version = 1;
		} else { // v2
			$data = (array) explode('!', (string)$data, 2);
			$data = (string) trim((string)($data[1] ?? null));
		} //end if else
		//--
		if($version == 1) { // v1 (only support decrypt)
			return (string) (new SmartCryptoCipherBlowfishCBC( // blowfish 384 CBC
				(string) (string) substr((string)SmartHashCrypto::sha512((string)$key), 13, 29).strtoupper((string)substr((string)sha1((string)$key), 13, 10)).substr((string)md5((string)$key), 13, 9),
				(string) substr((string)base64_encode((string)sha1('@Smart.Framework-Crypto/BlowFish:'.$key.'#'.sha1('BlowFish-iv-SHA1'.$key).'-'.strtoupper((string)md5('BlowFish-iv-MD5'.$key)).'#')), 1, 8),
				false // use 384bit
			))->decrypt((string)$data, false, false);
		} else { // v2
			return (string) self::decrypt((string)$cipher, (string)$key, (string)$data);
		} //end if else
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Generate a Derived Key and Initialization vector iV
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param STRING $key 		The encryption key
	 * @return ARRAY 			[ key => '...', 'iv' => '...' ]
	 */
	private static function keyderive(?string $key) { // {{{SYNC-CRYPTO-KEY-DERIVE}}}
		//--
		$key = (string) trim((string)$key); // {{{SYNC-CRYPTO-KEY-TRIM}}}
		//--
		$composed_key = (string) SmartHashCrypto::safecomposedkey((string)$key);
		$len_composed_key = (int) strlen((string)$composed_key);
		$len_composed_trimmed_key = (int) strlen((string)trim((string)$composed_key));
		if(((int)$len_composed_key !== 553) OR ((int)$len_composed_trimmed_key !== 553)) {
			if(SmartFrameworkRegistry::ifInternalDebug()) {
				if(SmartFrameworkRegistry::ifDebug()) {
					Smart::log_notice(__METHOD__.' # Safe Composed Key is invalid: `'.$key.'`');
				} //end if
			} //end if
			Smart::raise_error(__METHOD__.' # Safe Composed Key is invalid ('.(int)$len_composed_trimmed_key.'/'.(int)$len_composed_trimmed_key.') !');
			return array();
		} //end if
		//--
		$derived_key = (string) Smart::base_from_hex_convert((string)SmartHashCrypto::sha256((string)$composed_key), 92)."'".Smart::base_from_hex_convert((string)SmartHashCrypto::md5((string)$composed_key), 92);
		//-- IV, based on crc32 and sha1
		$iv = (string) str_pad((string)SmartHashCrypto::crc32b((string)$key, true), 8, '0', STR_PAD_LEFT);
		$iv .= ':'.SmartHashCrypto::sha1((string)$key, true);
		//-- chances are zero in practice to have a key colission by ensuring 2 different (salted and not salted) input to produce a simultan colission in all 5 algos: CRC32 / MD5 / SHA1 / SHA256 / SHA512 at once !!!
		return (array) [
			'key' => (string) $derived_key, // safe derivation: contains a complete SHA256(B64) and the first 11 chars from SHA512(B64) from the hash of composed key
			'iv'  => (string) $iv, // ensure the checksum of original input string
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
	 * @return MIXED 			Crypto OBJECT / The Error Message as STRING
	 */
	private static function crypto(string $cipher, string $key) {
		//--
		$key = (string) trim((string)$key);
		if((string)$key == '') {
			return 'The Key is Empty';
		} //end if
		//--
		if((string)substr((string)$cipher, 0, 5) == 'hash/') {
			$arr_kiv = [ 'key' => (string) $key, 'iv' => '' ]; // key will be derived inside hash object, no IV ...
		} else {
			$arr_kiv = (array) self::keyderive((string)$key);
		} //end if else
		if((int)Smart::array_size($arr_kiv) <= 0) {
			return 'Empty Key Derivation ...'; // will log warnings from keyderive ...
		} //end if
		//--
		$crypto = '???';
		if(((string)$cipher == 'blowfish') OR ((string)$cipher == 'blowfish.cbc')) { // use the built-in blowfish CBC
			$arr_kiv['key'] = (string) substr((string)$arr_kiv['key'], 0, 448/8); // {{{SYNC-BLOWFISH-KEY}}}
			$arr_kiv['iv'] = (string) substr((string)$arr_kiv['iv'], 0, 64/8); // {{{SYNC-BLOWFISH-IV}}}
			$crypto = new SmartCryptoCipherBlowfishCBC((string)$arr_kiv['key'], (string)$arr_kiv['iv']); // blowfish448
		} elseif((string)substr((string)$cipher, 0, 8) == 'openssl/') {
			if(strpos((string)$cipher, '/blowfish/') !== false) { // blowfish448
				$arr_kiv['key'] = (string) substr((string)$arr_kiv['key'], 0, 448/8); // {{{SYNC-BLOWFISH-KEY}}}
				$arr_kiv['iv'] = (string) substr((string)$arr_kiv['iv'], 0, 64/8); // {{{SYNC-BLOWFISH-IV}}}
			} else { // camellia256 / aes256
				$arr_kiv['key'] = (string) substr((string)$arr_kiv['key'], 0, 256/8); // {{{SYNC-BLOWFISH-KEY}}}
				$arr_kiv['iv'] = (string) substr((string)$arr_kiv['iv'], 0, 128/8); // {{{SYNC-BLOWFISH-IV}}}
			} //end if else
			$crypto = new SmartCryptoOpenSSLCipher((string)$cipher, (string)$arr_kiv['key'], (string)$arr_kiv['iv']);
		} elseif((string)substr((string)$cipher, 0, 5) == 'hash/') {
			$crypto = new SmartCryptoCipherHash((string)$cipher, (string)$arr_kiv['key']); // key will be derived inside hash object, no IV ...
		} else {
			$crypto = 'INVALID Cipher/Algo';
		} //end if else
		//--
		return $crypto; // mixed
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
 * Class: SmartCryptoOpenSSLCipher
 * Provides an OpenSSL based encryption / decryption.
 *
 * @usage       dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 *
 * @access 		private
 * @internal
 *
 * @depends     extensions: PHP OpenSSL ; classes: Smart, SmartHashCrypto
 * @version     v.20210825
 *
 */
final class SmartCryptoOpenSSLCipher {

	// ->


	//==============================================================
	//-- @ PRIVATE
	private $crypto_cipher 	= null;		// Crypto Cipher (ex: BF-CBC)
	private $crypto_key 	= null;		// Crypto Key
	private $crypto_iv 		= null;		// Crypto IV (initialization vector)
	private $crypto_opts 	= null; 	// Crypto Options (OpenSSL options: OPENSSL_RAW_DATA OPENSSL_ZERO_PADDING)
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
			Smart::raise_error(__METHOD__.' # Invalid Run Mode: '.$tmp_mode_crypto);
			return;
		} //end if
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
			case 'camellia256': // 256
				//-- Camellia key ; sizes: 128, 192 or 256 bits = max 32 chars ; Camellia iv ; block size: 128 bits = 16 chars
				$len_key = 32; // Camellia256 # key: 256 bits = 32 bytes
				$len_iv  = 16; // Camellia256 # iv:  128 bits = 16 bytes
				//--
				break;
			case 'aes256': // 256
				//-- AES key ; sizes: 128, 192 or 256 bits = max 32 chars ; AES iv ; block size: 128 bits = 16 chars
				$len_key = 32; // AES256 # key: 256 bits = 32 bytes
				$len_iv  = 16; // AES256 # iv:  128 bits = 16 bytes
				//--
				break;
			default:
				Smart::raise_error(__METHOD__.' # Invalid Cipher: '.$tmp_expl_algo);
				return;
		} //end if
		//--
		if(((int)strlen((string)$key) !== (int)$len_key) OR ((int)strlen((string)trim((string)$key)) !== (int)$len_key)) {
			Smart::raise_error(__METHOD__.' # Invalid Key, must be exactly '.(int)$len_key.' bytes ('.(int)((int)$len_key * 8).' bits)');
			return;
		} //end if
		if(((int)strlen((string)$iv) !== (int)$len_iv) OR ((int)strlen((string)trim((string)$iv)) !== (int)$len_iv)) {
			Smart::raise_error(__METHOD__.' # Invalid iV, must be exactly '.(int)$len_iv.' bytes ('.(int)((int)$len_iv * 8).' bits)');
			return;
		} //end if
		//--
		$this->crypto_opts = 0;
		//--
		switch((string)$tmp_expl_algo) { // cipher
			//--
			case 'blowfish': // 448 ; currently this is the only-one compatible with the Symmetric Crypto JS Api ; chipher (Blowfish is a 64-bit (8 bytes) block cipher)
				//--
				$this->crypto_opts = OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING; // preserve compatibility with the PHP and JS classes and also mcrypt
				//--
				switch((string)$tmp_expl_method) { // for blowfish accept only: CBC, CFB or OFB
					case 'OFB':
						$this->crypto_cipher = 'BF-OFB'; // Blowfish OFB
						break;
					case 'CFB':
						$this->crypto_cipher = 'BF-CFB'; // Blowfish CFB
						break;
					case 'CBC':
					default:
						$this->crypto_cipher = 'BF-CBC'; // Blowfish CBC (default mode)
				} //end switch
				//--
				$this->crypto_key = (string) $key; // Blowfish key: 448 bits = 56 bytes ; {{{SYNC-BLOWFISH-KEY}}}
				$this->crypto_iv = (string) $iv; // Blowfish iv: 64 bits = 8 bytes ; {{{SYNC-BLOWFISH-IV}}}
				//--
				break;
			case 'camellia256':
				//--
				$this->crypto_opts = OPENSSL_RAW_DATA; // don't use OPENSSL_ZERO_PADDING
				//--
				switch((string)$tmp_expl_method) { // for Camellia256 accept only: CBC, CFB or OFB
					case 'OFB':
						$this->crypto_cipher = 'CAMELLIA-256-OFB'; // CAMELLIA-256 OFB
						break;
					case 'CFB':
						$this->crypto_cipher = 'CAMELLIA-256-CFB'; // CAMELLIA-256 CFB
						break;
					case 'CBC':
					default:
						$this->crypto_cipher = 'CAMELLIA-256-CBC'; // CAMELLIA-256 CBC (default mode)
				} //end switch
				//--
				$this->crypto_key = (string) $key; // Camellia key: 256 bits = 32 bytes
				$this->crypto_iv = (string) $iv; // Camellia iv: 128 bits = 16 bytes
				//--
				break;
			case 'aes256':
				//--
				$this->crypto_opts = OPENSSL_RAW_DATA; // don't use OPENSSL_ZERO_PADDING
				//--
				switch((string)$tmp_expl_method) { // for AES256 accept only: CBC, CFB or OFB
					case 'OFB':
						$this->crypto_cipher = 'AES-256-OFB'; // AES-256 OFB
						break;
					case 'CFB':
						$this->crypto_cipher = 'AES-256-CFB'; // AES-256 CFB
						break;
					case 'CBC':
					default:
						$this->crypto_cipher = 'AES-256-CBC'; // AES-256 CBC (default mode)
				} //end switch
				//--
				$this->crypto_key = (string) $key; // AES key: 256 bits = 32 bytes
				$this->crypto_iv = (string) $iv; // AES iv: 128 bits = 16 bytes
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
	public function encrypt($plainText) {
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
		if((string)$plainText == '') {
			return '';
		} //end if
		//-- base64 :: because is not UTF-8 safe and may corrupt unicode characters
		$plainText = (string) base64_encode((string)$plainText);
		//-- {{{SYNC-BLOWFISH-CHECKSUM}}}
		$plainText .= '#CKSUM256#'.SmartHashCrypto::sha256((string)$plainText, true);
		//--
		//== {{{SYNC-BLOWFISH-PADDING}}}
		//-- Blowfish is a 64-bit block cipher. This means that the data must be provided in units that are a multiple of 8 bytes
		$padding = 8 - (int)(strlen($plainText) & 7);
		//-- unixman: fix: add spaces as padding as we have it as b64 encoded and will not modify the original
		for($i=0; $i<$padding; $i++) {
			$plainText .= ' '; // unixman (pad with spaces)
		} //end for
		//==
		//--
	//	return (string) strtoupper((string)bin2hex((string)openssl_encrypt((string)$plainText, (string)$this->crypto_cipher, (string)$this->crypto_key, $this->crypto_opts, (string)$this->crypto_iv)));
		return (string) Smart::b64s_enc((string)openssl_encrypt((string)$plainText, (string)$this->crypto_cipher, (string)$this->crypto_key, $this->crypto_opts, (string)$this->crypto_iv));
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
	public function decrypt($cipherText) {
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
		$cipherText = (string) trim((string)$cipherText);
		if((string)$cipherText == '') {
			return '';
		} //end if
		//--
	//	$cipherText = (string) hex2bin((string)strtolower((string)$cipherText));
		$cipherText = (string) Smart::b64s_dec((string)$cipherText);
		if((string)$cipherText == '') {
			Smart::log_notice(__METHOD__.' # Decode Failed');
			return '';
		} //end if
		//-- {{{SYNC-BLOWFISH-PADDING-TRIM}}} :: trim padding spaces
		$plainText = (string) trim((string)openssl_decrypt((string)$cipherText, (string)$this->crypto_cipher, (string)$this->crypto_key, $this->crypto_opts, (string)$this->crypto_iv));
		if((string)$plainText == '') {
			Smart::log_notice(__METHOD__.' # Decrypt ('.$this->crypto_cipher.') Failed');
			return '';
		} //end if
		//-- {{{SYNC-BLOWFISH-CHECKSUM}}}
		$arr = (array) explode('#CKSUM256#', (string)$plainText, 2);
		$plainText = (string) trim((string)($arr[0] ?? ''));
		$checksum = (string) trim((string)($arr[1] ?? ''));
		if((string)$plainText == '') {
			Smart::log_notice(__METHOD__.' # Decrypt: Data is Empty');
			return ''; // no checksum
		} //end if
		if((string)$checksum == '') {
			Smart::log_notice(__METHOD__.' # Decrypt: Checksum is Empty');
			return ''; // no checksum
		} //end if
		if((string)SmartHashCrypto::sha256((string)$plainText, true) != (string)$checksum) {
			Smart::log_notice(__METHOD__.' # Decrypt: Checksum Failed');
			return ''; // string is corrupted, avoid to return
		} //end if
		//-- base64 :: because is not UTF-8 safe and may corrupt unicode characters
		return (string) base64_decode((string)$plainText);
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

//--
// PHP implementation of the Blowfish algorithm in CBC mode
// It does not require any PHP extension, it uses only the core PHP.
// Class support encryption/decryption with or without a secret key.
//
// LICENSE: BSD, authors: Matthew Fonda <mfonda@php.net>, Philippe Jausions <jausions@php.net>
// (c) 2005-2008 Matthew Fonda
//
// Modified from the v.1.1.0 by unixman (iradu@unix-world.org), contains many fixes and make it unicode safe
// (c) 2015 unix-world.org
//--

/**
 * Class: SmartCryptoCipherBlowfishCBC
 * Provides a built-in based feature to handle the Blowfish 448-bit CBC encryption / decryption.
 *
 * It is recommended to use instead the SmartUtils::crypto_blowfish_encrypt() and SmartUtils::crypto_blowfish_decrypt() which are detecting from inits if to use the OpenSSL Blowfish (faster) or the built-in Blowfish (compatible with all platforms) classes of Blowfish CBC.
 *
 * @usage       dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 * @hints       Blowfish is a 64-bit (8 bytes) block cipher. Max Key is up to 56 chars length (56 bytes = 448 bits). The CBC mode requires a initialization vector (iv).
 *
 * @access 		private
 * @internal
 *
 * @depends     classes: Smart, SmartHashCrypto
 * @version     v.20210903
 *
 */
final class SmartCryptoCipherBlowfishCBC {

	// ->


	//==============================================================
	//--
	private $_P 	= []; 		// P-Array contains 18 32-bit subkeys
	private $_S 	= []; 		// Array of four S-Blocks each containing 256 32-bit entries
	//--
	private $_iv 	= null;		// Initialization vector
	private $_key 	= '';		// the key
	//--
	//==============================================================


	//==============================================================
	/**
	 * Constructor
	 * Initializes the blowfish cipher object, and gives a sets
	 * the secret key
	 *
	 * @param string $key
	 * @access public
	 */
	public function __construct(string $key, string $iv, bool $enforce448=true) {
		//-- Blowfish uses 64-bit blocks, a variable size key (ranging from 32 to 448 bits = 4 to 56 bytes[characters]) and an initialization vector (IV, 64bit = 8 bytes[characters])
		if($enforce448 === false) { // will only allow decrypt to support v1
			if(((int)strlen((string)$key) !== 48) OR ((int)strlen((string)trim((string)$key)) !== 48)) {
				Smart::raise_error(__METHOD__.' # Invalid (v1) Key, must be exactly 48 bytes (384 bits)');
				return;
			} //end if
		} else {
			if(((int)strlen((string)$key) !== 56) OR ((int)strlen((string)trim((string)$key)) !== 56)) {
				Smart::raise_error(__METHOD__.' # Invalid Key, must be exactly 56 bytes (448 bits)');
				return;
			} //end if
		} //end if
		if(((int)strlen((string)$iv) !== 8) OR ((int)strlen((string)trim((string)$iv)) !== 8)) {
			Smart::raise_error(__METHOD__.' # Invalid iV, must be exactly 8 bytes (64 bits)');
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
	 * Encrypts a string in CBC Mode
	 *
	 * @param string $plainText
	 * @param string $b64 (if FALSE will use v1 HEX, otherwise v2 B64s) ; option currently disabled it is always TRUE to enforce use v2 !
	 * @param string $cksum256 (if FALSE will use v1 SHA1, otherwise v2 SHA256) ; option currently disabled it is always TRUE to enforce use v2 !
	 * @return string Returns cipher text
	 * @access public
	 */
	public function encrypt($plainText, $b64=true, $cksum256=true) {
		//--
		$b64 = true; 		// disable v1 encrypt (only decrypt should be available) ; use v2 !
		$cksum256 = true; 	// disable v1 encrypt (only decrypt should be available) ; use v2 !
		//--
		if((string)trim((string)$this->_key) == '') {
			Smart::log_warning(__METHOD__.' # Crypto Key is Empty');
			return '';
		} //end if
		if((string)trim((string)$this->_iv) == '') {
			Smart::log_warning(__METHOD__.' # Crypto iV is Empty');
			return '';
		} //end if
		//-- disallow encrypt with a key lower than 56 bytes (448 bit) since v2 !!
		if((int)strlen((string)trim((string)$this->_key)) !== 56) {
			Smart::log_warning(__METHOD__.' # Invalid Key, must be exactly 56 bytes (448 bits)'); // for encrypt, enforce this
			return '';
		} //end if
		//--
		if((string)$plainText == '') {
			return '';
		} //end if
		//--
		$plainText = (string) base64_encode((string)$plainText); // b64 because is not UTF-8 safe and may corrupt unicode characters
		//-- {{{SYNC-BLOWFISH-CHECKSUM}}}
		if($cksum256 === false) { // v1
			$plainText .= '#CHECKSUM-SHA1#'.SmartHashCrypto::sha1((string)$plainText); // sha1, hex
		} else { // v2
			$plainText .= '#CKSUM256#'.SmartHashCrypto::sha256((string)$plainText, true); // sha256, b64
		} //end if else
		//--
		//== {{{SYNC-BLOWFISH-PADDING}}}
		//-- Blowfish is a 64-bit block cipher. This means that the data must be provided in units that are a multiple of 8 bytes
		$padding = 8 - (strlen($plainText) & 7);
		//-- unixman: fix: add spaces as padding as we have it as b64 encoded and will not modify the original
		for($i=0; $i<$padding; $i++) {
			$plainText .= ' '; // unixman (pad with spaces)
		} //end for
		//==
		//--
		$cipherText = '';
		$len = (int) strlen((string)$plainText);
		$plainText .= (string) str_repeat(chr(0), (8 - ($len % 8)) % 8);
		$arx = unpack('N2', substr($plainText, 0, 8) ^ $this->_iv);
		if(!is_array($arx)) {
			$arx = []; // fix for PHP8
		} //end if
		if(!array_key_exists(0, $arx)) {
			$arx[0] = null; // fix for PHP8
		} //end if
		list($kk, $Xl, $Xr) = $arx; // FIX to be compatible with the upcoming PHP 7
		$arx = null;
		$this->_encipher($Xl, $Xr);
		$cipherText .= pack('N2', $Xl, $Xr);
		for($i = 8; $i < $len; $i += 8) {
			$arx = unpack('N2', substr($plainText, $i, 8) ^ substr($cipherText, $i - 8, 8));
			if(!is_array($arx)) {
				$arx = []; // fix for PHP8
			} //end if
			if(!array_key_exists(0, $arx)) {
				$arx[0] = null; // fix for PHP8
			} //end if
			list($kk, $Xl, $Xr) = $arx; // FIX to be compatible with the upcoming PHP 7
			$arx = null;
			$this->_encipher($Xl, $Xr);
			$cipherText .= pack('N2', $Xl, $Xr);
		} //end for
		//--
		if($b64 === false) { // v1
			return (string) strtoupper((string)bin2hex((string)$cipherText)); // upper hex
		} else { // v2
			return (string) Smart::b64s_enc((string)$cipherText); // b64s
		} //end if else
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Decrypts an encrypted string in CBC Mode
	 *
	 * @param string $cipherText
	 * @param string $b64 (if FALSE will use v1 HEX, otherwise v2 B64s)
	 * @param string $cksum256 (if FALSE will use v1 SHA1, otherwise v2 SHA256)
	 * @return string Returns plain text
	 * @access public
	 */
	public function decrypt($cipherText, $b64=true, $cksum256=true) {
		//--
		if((string)trim((string)$this->_key) == '') {
			Smart::log_warning(__METHOD__.' # Crypto Key is Empty');
			return '';
		} //end if
		if((string)trim((string)$this->_iv) == '') {
			Smart::log_warning(__METHOD__.' # Crypto iV is Empty');
			return '';
		} //end if
		//--
		$cipherText = (string) trim((string)$cipherText);
		if((string)$cipherText == '') {
			return '';
		} //end if
		//--
		if($b64 === false) { // v1
			$encmode = 'HEX';
			$cipherText = (string) @hex2bin((string)strtolower((string)$cipherText)); // upper hex
		} else { // v2
			$encmode = 'B64s';
			$cipherText = (string) Smart::b64s_dec((string)$cipherText); // b64s
		} //end if else
		if((string)$cipherText == '') {
			Smart::log_notice(__METHOD__.' # Decode ('.$encmode.') Failed');
			return '';
		} //end if
		//--
		$len = (int) strlen((string)$cipherText);
		$cipherText .= (string) str_repeat(chr(0), (8 - ($len % 8)) % 8);
		$arx = unpack('N2', substr($cipherText, 0, 8));
		if(!is_array($arx)) {
			$arx = []; // fix for PHP8
		} //end if
		if(!array_key_exists(0, $arx)) {
			$arx[0] = null; // fix for PHP8
		} //end if
		list($kk, $Xl, $Xr) = $arx; // FIX to be compatible with the upcoming PHP 7
		$arx = null;
		$this->_decipher($Xl, $Xr);
		//--
		$plainText = '';
		//--
		$plainText .= (string) (pack('N2', $Xl, $Xr) ^ $this->_iv);
		for($i = 8; $i < $len; $i += 8) {
			$arx = unpack('N2', substr($cipherText, $i, 8));
			if(!is_array($arx)) {
				$arx = []; // fix for PHP8
			} //end if
			if(!array_key_exists(0, $arx)) {
				$arx[0] = null; // fix for PHP8
			} //end if
			list($kk, $Xl, $Xr) = $arx; // FIX to be compatible with the upcoming PHP 7
			$arx = null;
			$this->_decipher($Xl, $Xr);
			$plainText .= (pack('N2', $Xl, $Xr) ^ substr($cipherText, $i - 8, 8));
		} //end for
		//-- {{{SYNC-BLOWFISH-PADDING-TRIM}}} :: trim padding spaces
		$plainText = (string) trim((string)$plainText);
		if((string)$plainText == '') {
			Smart::log_notice(__METHOD__.' # Decrypt Failed');
			return '';
		} //end if
		//-- {{{SYNC-BLOWFISH-CHECKSUM}}}
		if($cksum256 === false) { // v1
			$separator = '#CHECKSUM-SHA1#';
		} else { // v2
			$separator = '#CKSUM256#';
		} //end if else
		$arr = (array) explode((string)$separator, (string)$plainText, 2);
		$plainText = (string) trim((string)($arr[0] ?? ''));
		$checksum = (string) trim((string)($arr[1] ?? ''));
		if((string)$plainText == '') {
			Smart::log_notice(__METHOD__.' # Decrypt: Data is Empty');
			return ''; // no checksum
		} //end if
		if((string)$checksum == '') {
			Smart::log_notice(__METHOD__.' # Decrypt: Checksum is Empty');
			return ''; // no checksum
		} //end if
		if($cksum256 === false) { // v1
			$version = 'Sha1';
			$testsum = (string) SmartHashCrypto::sha1((string)$plainText); // sha1 hex
		} else { // v2
			$version = 'Sha256B64';
			$testsum = (string) SmartHashCrypto::sha256((string)$plainText, true); // sha256 b64
		} //end if else
		if((string)$testsum != (string)$checksum) {
			Smart::log_notice(__METHOD__.' # Checksum ('.$version.') Failed');
			return ''; // string is corrupted, avoid to return
		} //end if
		//-- b64 because is not UTF-8 safe and may corrupt unicode characters
		return (string) base64_decode((string)$plainText);
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
	private function init() {
		//--
		$key = $this->_key;
		$len = strlen($key);
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
			$this->_encipher($datal, $datar);
			$this->_P[$i] = $datal;
			$this->_P[$i+1] = $datar;
		} //end for
		//--
		for($i=0; $i<256; $i+=2) {
			$this->_encipher($datal, $datar);
			$this->_S[0][$i] = $datal;
			$this->_S[0][$i+1] = $datar;
		} //end for
		//--
		for($i=0; $i<256; $i+=2) {
			$this->_encipher($datal, $datar);
			$this->_S[1][$i] = $datal;
			$this->_S[1][$i+1] = $datar;
		} //end for
		//--
		for($i=0; $i<256; $i+=2) {
			$this->_encipher($datal, $datar);
			$this->_S[2][$i] = $datal;
			$this->_S[2][$i+1] = $datar;
		} //end for
		//--
		for($i=0; $i<256; $i+=2) {
			$this->_encipher($datal, $datar);
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
	private function _encipher(&$Xl, &$Xr) {
		//--
		for($i = 0; $i < 16; $i++) {
			$temp = $Xl ^ $this->_P[$i];
			$Xl = ((($this->_S[0][($temp>>24) & 255] + $this->_S[1][($temp>>16) & 255]) ^ $this->_S[2][($temp>>8) & 255]) + $this->_S[3][$temp & 255]) ^ $Xr;
			$Xr = $temp;
		} //end for
		//--
		$Xr = $Xl ^ $this->_P[16];
		$Xl = $temp ^ $this->_P[17];
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
	private function _decipher(&$Xl, &$Xr) {
		//--
		for($i = 17; $i > 1; $i--) {
			$temp = $Xl ^ $this->_P[$i];
			$Xl = ((($this->_S[0][($temp>>24) & 255] + $this->_S[1][($temp>>16) & 255]) ^ $this->_S[2][($temp>>8) & 255]) + $this->_S[3][$temp & 255]) ^ $Xr;
			$Xr = $temp;
		} //end for
		//--
		$Xr = $Xl ^ $this->_P[1];
		$Xl = $temp ^ $this->_P[0];
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Initializes the blowfish cipher object
	 *
	 * @access private
	 */
	private function _init() {
		//--
		$this->_P = (array) self::BLOWFISH_BOXES_P;
		//--
		$this->_S = (array) self::BLOWFISH_BOXES_S;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	private const BLOWFISH_BOXES_P = [
		0x243F6A88, 0x85A308D3, 0x13198A2E, 0x03707344,
		0xA4093822, 0x299F31D0, 0x082EFA98, 0xEC4E6C89,
		0x452821E6, 0x38D01377, 0xBE5466CF, 0x34E90C6C,
		0xC0AC29B7, 0xC97C50DD, 0x3F84D5B5, 0xB5470917,
		0x9216D5D9, 0x8979FB1B
	];
	//==============================================================
	private const BLOWFISH_BOXES_S = [
		[
			0xD1310BA6, 0x98DFB5AC, 0x2FFD72DB, 0xD01ADFB7,
			0xB8E1AFED, 0x6A267E96, 0xBA7C9045, 0xF12C7F99,
			0x24A19947, 0xB3916CF7, 0x0801F2E2, 0x858EFC16,
			0x636920D8, 0x71574E69, 0xA458FEA3, 0xF4933D7E,
			0x0D95748F, 0x728EB658, 0x718BCD58, 0x82154AEE,
			0x7B54A41D, 0xC25A59B5, 0x9C30D539, 0x2AF26013,
			0xC5D1B023, 0x286085F0, 0xCA417918, 0xB8DB38EF,
			0x8E79DCB0, 0x603A180E, 0x6C9E0E8B, 0xB01E8A3E,
			0xD71577C1, 0xBD314B27, 0x78AF2FDA, 0x55605C60,
			0xE65525F3, 0xAA55AB94, 0x57489862, 0x63E81440,
			0x55CA396A, 0x2AAB10B6, 0xB4CC5C34, 0x1141E8CE,
			0xA15486AF, 0x7C72E993, 0xB3EE1411, 0x636FBC2A,
			0x2BA9C55D, 0x741831F6, 0xCE5C3E16, 0x9B87931E,
			0xAFD6BA33, 0x6C24CF5C, 0x7A325381, 0x28958677,
			0x3B8F4898, 0x6B4BB9AF, 0xC4BFE81B, 0x66282193,
			0x61D809CC, 0xFB21A991, 0x487CAC60, 0x5DEC8032,
			0xEF845D5D, 0xE98575B1, 0xDC262302, 0xEB651B88,
			0x23893E81, 0xD396ACC5, 0x0F6D6FF3, 0x83F44239,
			0x2E0B4482, 0xA4842004, 0x69C8F04A, 0x9E1F9B5E,
			0x21C66842, 0xF6E96C9A, 0x670C9C61, 0xABD388F0,
			0x6A51A0D2, 0xD8542F68, 0x960FA728, 0xAB5133A3,
			0x6EEF0B6C, 0x137A3BE4, 0xBA3BF050, 0x7EFB2A98,
			0xA1F1651D, 0x39AF0176, 0x66CA593E, 0x82430E88,
			0x8CEE8619, 0x456F9FB4, 0x7D84A5C3, 0x3B8B5EBE,
			0xE06F75D8, 0x85C12073, 0x401A449F, 0x56C16AA6,
			0x4ED3AA62, 0x363F7706, 0x1BFEDF72, 0x429B023D,
			0x37D0D724, 0xD00A1248, 0xDB0FEAD3, 0x49F1C09B,
			0x075372C9, 0x80991B7B, 0x25D479D8, 0xF6E8DEF7,
			0xE3FE501A, 0xB6794C3B, 0x976CE0BD, 0x04C006BA,
			0xC1A94FB6, 0x409F60C4, 0x5E5C9EC2, 0x196A2463,
			0x68FB6FAF, 0x3E6C53B5, 0x1339B2EB, 0x3B52EC6F,
			0x6DFC511F, 0x9B30952C, 0xCC814544, 0xAF5EBD09,
			0xBEE3D004, 0xDE334AFD, 0x660F2807, 0x192E4BB3,
			0xC0CBA857, 0x45C8740F, 0xD20B5F39, 0xB9D3FBDB,
			0x5579C0BD, 0x1A60320A, 0xD6A100C6, 0x402C7279,
			0x679F25FE, 0xFB1FA3CC, 0x8EA5E9F8, 0xDB3222F8,
			0x3C7516DF, 0xFD616B15, 0x2F501EC8, 0xAD0552AB,
			0x323DB5FA, 0xFD238760, 0x53317B48, 0x3E00DF82,
			0x9E5C57BB, 0xCA6F8CA0, 0x1A87562E, 0xDF1769DB,
			0xD542A8F6, 0x287EFFC3, 0xAC6732C6, 0x8C4F5573,
			0x695B27B0, 0xBBCA58C8, 0xE1FFA35D, 0xB8F011A0,
			0x10FA3D98, 0xFD2183B8, 0x4AFCB56C, 0x2DD1D35B,
			0x9A53E479, 0xB6F84565, 0xD28E49BC, 0x4BFB9790,
			0xE1DDF2DA, 0xA4CB7E33, 0x62FB1341, 0xCEE4C6E8,
			0xEF20CADA, 0x36774C01, 0xD07E9EFE, 0x2BF11FB4,
			0x95DBDA4D, 0xAE909198, 0xEAAD8E71, 0x6B93D5A0,
			0xD08ED1D0, 0xAFC725E0, 0x8E3C5B2F, 0x8E7594B7,
			0x8FF6E2FB, 0xF2122B64, 0x8888B812, 0x900DF01C,
			0x4FAD5EA0, 0x688FC31C, 0xD1CFF191, 0xB3A8C1AD,
			0x2F2F2218, 0xBE0E1777, 0xEA752DFE, 0x8B021FA1,
			0xE5A0CC0F, 0xB56F74E8, 0x18ACF3D6, 0xCE89E299,
			0xB4A84FE0, 0xFD13E0B7, 0x7CC43B81, 0xD2ADA8D9,
			0x165FA266, 0x80957705, 0x93CC7314, 0x211A1477,
			0xE6AD2065, 0x77B5FA86, 0xC75442F5, 0xFB9D35CF,
			0xEBCDAF0C, 0x7B3E89A0, 0xD6411BD3, 0xAE1E7E49,
			0x00250E2D, 0x2071B35E, 0x226800BB, 0x57B8E0AF,
			0x2464369B, 0xF009B91E, 0x5563911D, 0x59DFA6AA,
			0x78C14389, 0xD95A537F, 0x207D5BA2, 0x02E5B9C5,
			0x83260376, 0x6295CFA9, 0x11C81968, 0x4E734A41,
			0xB3472DCA, 0x7B14A94A, 0x1B510052, 0x9A532915,
			0xD60F573F, 0xBC9BC6E4, 0x2B60A476, 0x81E67400,
			0x08BA6FB5, 0x571BE91F, 0xF296EC6B, 0x2A0DD915,
			0xB6636521, 0xE7B9F9B6, 0xFF34052E, 0xC5855664,
			0x53B02D5D, 0xA99F8FA1, 0x08BA4799, 0x6E85076A
		],
		[
			0x4B7A70E9, 0xB5B32944, 0xDB75092E, 0xC4192623,
			0xAD6EA6B0, 0x49A7DF7D, 0x9CEE60B8, 0x8FEDB266,
			0xECAA8C71, 0x699A17FF, 0x5664526C, 0xC2B19EE1,
			0x193602A5, 0x75094C29, 0xA0591340, 0xE4183A3E,
			0x3F54989A, 0x5B429D65, 0x6B8FE4D6, 0x99F73FD6,
			0xA1D29C07, 0xEFE830F5, 0x4D2D38E6, 0xF0255DC1,
			0x4CDD2086, 0x8470EB26, 0x6382E9C6, 0x021ECC5E,
			0x09686B3F, 0x3EBAEFC9, 0x3C971814, 0x6B6A70A1,
			0x687F3584, 0x52A0E286, 0xB79C5305, 0xAA500737,
			0x3E07841C, 0x7FDEAE5C, 0x8E7D44EC, 0x5716F2B8,
			0xB03ADA37, 0xF0500C0D, 0xF01C1F04, 0x0200B3FF,
			0xAE0CF51A, 0x3CB574B2, 0x25837A58, 0xDC0921BD,
			0xD19113F9, 0x7CA92FF6, 0x94324773, 0x22F54701,
			0x3AE5E581, 0x37C2DADC, 0xC8B57634, 0x9AF3DDA7,
			0xA9446146, 0x0FD0030E, 0xECC8C73E, 0xA4751E41,
			0xE238CD99, 0x3BEA0E2F, 0x3280BBA1, 0x183EB331,
			0x4E548B38, 0x4F6DB908, 0x6F420D03, 0xF60A04BF,
			0x2CB81290, 0x24977C79, 0x5679B072, 0xBCAF89AF,
			0xDE9A771F, 0xD9930810, 0xB38BAE12, 0xDCCF3F2E,
			0x5512721F, 0x2E6B7124, 0x501ADDE6, 0x9F84CD87,
			0x7A584718, 0x7408DA17, 0xBC9F9ABC, 0xE94B7D8C,
			0xEC7AEC3A, 0xDB851DFA, 0x63094366, 0xC464C3D2,
			0xEF1C1847, 0x3215D908, 0xDD433B37, 0x24C2BA16,
			0x12A14D43, 0x2A65C451, 0x50940002, 0x133AE4DD,
			0x71DFF89E, 0x10314E55, 0x81AC77D6, 0x5F11199B,
			0x043556F1, 0xD7A3C76B, 0x3C11183B, 0x5924A509,
			0xF28FE6ED, 0x97F1FBFA, 0x9EBABF2C, 0x1E153C6E,
			0x86E34570, 0xEAE96FB1, 0x860E5E0A, 0x5A3E2AB3,
			0x771FE71C, 0x4E3D06FA, 0x2965DCB9, 0x99E71D0F,
			0x803E89D6, 0x5266C825, 0x2E4CC978, 0x9C10B36A,
			0xC6150EBA, 0x94E2EA78, 0xA5FC3C53, 0x1E0A2DF4,
			0xF2F74EA7, 0x361D2B3D, 0x1939260F, 0x19C27960,
			0x5223A708, 0xF71312B6, 0xEBADFE6E, 0xEAC31F66,
			0xE3BC4595, 0xA67BC883, 0xB17F37D1, 0x018CFF28,
			0xC332DDEF, 0xBE6C5AA5, 0x65582185, 0x68AB9802,
			0xEECEA50F, 0xDB2F953B, 0x2AEF7DAD, 0x5B6E2F84,
			0x1521B628, 0x29076170, 0xECDD4775, 0x619F1510,
			0x13CCA830, 0xEB61BD96, 0x0334FE1E, 0xAA0363CF,
			0xB5735C90, 0x4C70A239, 0xD59E9E0B, 0xCBAADE14,
			0xEECC86BC, 0x60622CA7, 0x9CAB5CAB, 0xB2F3846E,
			0x648B1EAF, 0x19BDF0CA, 0xA02369B9, 0x655ABB50,
			0x40685A32, 0x3C2AB4B3, 0x319EE9D5, 0xC021B8F7,
			0x9B540B19, 0x875FA099, 0x95F7997E, 0x623D7DA8,
			0xF837889A, 0x97E32D77, 0x11ED935F, 0x16681281,
			0x0E358829, 0xC7E61FD6, 0x96DEDFA1, 0x7858BA99,
			0x57F584A5, 0x1B227263, 0x9B83C3FF, 0x1AC24696,
			0xCDB30AEB, 0x532E3054, 0x8FD948E4, 0x6DBC3128,
			0x58EBF2EF, 0x34C6FFEA, 0xFE28ED61, 0xEE7C3C73,
			0x5D4A14D9, 0xE864B7E3, 0x42105D14, 0x203E13E0,
			0x45EEE2B6, 0xA3AAABEA, 0xDB6C4F15, 0xFACB4FD0,
			0xC742F442, 0xEF6ABBB5, 0x654F3B1D, 0x41CD2105,
			0xD81E799E, 0x86854DC7, 0xE44B476A, 0x3D816250,
			0xCF62A1F2, 0x5B8D2646, 0xFC8883A0, 0xC1C7B6A3,
			0x7F1524C3, 0x69CB7492, 0x47848A0B, 0x5692B285,
			0x095BBF00, 0xAD19489D, 0x1462B174, 0x23820E00,
			0x58428D2A, 0x0C55F5EA, 0x1DADF43E, 0x233F7061,
			0x3372F092, 0x8D937E41, 0xD65FECF1, 0x6C223BDB,
			0x7CDE3759, 0xCBEE7460, 0x4085F2A7, 0xCE77326E,
			0xA6078084, 0x19F8509E, 0xE8EFD855, 0x61D99735,
			0xA969A7AA, 0xC50C06C2, 0x5A04ABFC, 0x800BCADC,
			0x9E447A2E, 0xC3453484, 0xFDD56705, 0x0E1E9EC9,
			0xDB73DBD3, 0x105588CD, 0x675FDA79, 0xE3674340,
			0xC5C43465, 0x713E38D8, 0x3D28F89E, 0xF16DFF20,
			0x153E21E7, 0x8FB03D4A, 0xE6E39F2B, 0xDB83ADF7
		],
		[
			0xE93D5A68, 0x948140F7, 0xF64C261C, 0x94692934,
			0x411520F7, 0x7602D4F7, 0xBCF46B2E, 0xD4A20068,
			0xD4082471, 0x3320F46A, 0x43B7D4B7, 0x500061AF,
			0x1E39F62E, 0x97244546, 0x14214F74, 0xBF8B8840,
			0x4D95FC1D, 0x96B591AF, 0x70F4DDD3, 0x66A02F45,
			0xBFBC09EC, 0x03BD9785, 0x7FAC6DD0, 0x31CB8504,
			0x96EB27B3, 0x55FD3941, 0xDA2547E6, 0xABCA0A9A,
			0x28507825, 0x530429F4, 0x0A2C86DA, 0xE9B66DFB,
			0x68DC1462, 0xD7486900, 0x680EC0A4, 0x27A18DEE,
			0x4F3FFEA2, 0xE887AD8C, 0xB58CE006, 0x7AF4D6B6,
			0xAACE1E7C, 0xD3375FEC, 0xCE78A399, 0x406B2A42,
			0x20FE9E35, 0xD9F385B9, 0xEE39D7AB, 0x3B124E8B,
			0x1DC9FAF7, 0x4B6D1856, 0x26A36631, 0xEAE397B2,
			0x3A6EFA74, 0xDD5B4332, 0x6841E7F7, 0xCA7820FB,
			0xFB0AF54E, 0xD8FEB397, 0x454056AC, 0xBA489527,
			0x55533A3A, 0x20838D87, 0xFE6BA9B7, 0xD096954B,
			0x55A867BC, 0xA1159A58, 0xCCA92963, 0x99E1DB33,
			0xA62A4A56, 0x3F3125F9, 0x5EF47E1C, 0x9029317C,
			0xFDF8E802, 0x04272F70, 0x80BB155C, 0x05282CE3,
			0x95C11548, 0xE4C66D22, 0x48C1133F, 0xC70F86DC,
			0x07F9C9EE, 0x41041F0F, 0x404779A4, 0x5D886E17,
			0x325F51EB, 0xD59BC0D1, 0xF2BCC18F, 0x41113564,
			0x257B7834, 0x602A9C60, 0xDFF8E8A3, 0x1F636C1B,
			0x0E12B4C2, 0x02E1329E, 0xAF664FD1, 0xCAD18115,
			0x6B2395E0, 0x333E92E1, 0x3B240B62, 0xEEBEB922,
			0x85B2A20E, 0xE6BA0D99, 0xDE720C8C, 0x2DA2F728,
			0xD0127845, 0x95B794FD, 0x647D0862, 0xE7CCF5F0,
			0x5449A36F, 0x877D48FA, 0xC39DFD27, 0xF33E8D1E,
			0x0A476341, 0x992EFF74, 0x3A6F6EAB, 0xF4F8FD37,
			0xA812DC60, 0xA1EBDDF8, 0x991BE14C, 0xDB6E6B0D,
			0xC67B5510, 0x6D672C37, 0x2765D43B, 0xDCD0E804,
			0xF1290DC7, 0xCC00FFA3, 0xB5390F92, 0x690FED0B,
			0x667B9FFB, 0xCEDB7D9C, 0xA091CF0B, 0xD9155EA3,
			0xBB132F88, 0x515BAD24, 0x7B9479BF, 0x763BD6EB,
			0x37392EB3, 0xCC115979, 0x8026E297, 0xF42E312D,
			0x6842ADA7, 0xC66A2B3B, 0x12754CCC, 0x782EF11C,
			0x6A124237, 0xB79251E7, 0x06A1BBE6, 0x4BFB6350,
			0x1A6B1018, 0x11CAEDFA, 0x3D25BDD8, 0xE2E1C3C9,
			0x44421659, 0x0A121386, 0xD90CEC6E, 0xD5ABEA2A,
			0x64AF674E, 0xDA86A85F, 0xBEBFE988, 0x64E4C3FE,
			0x9DBC8057, 0xF0F7C086, 0x60787BF8, 0x6003604D,
			0xD1FD8346, 0xF6381FB0, 0x7745AE04, 0xD736FCCC,
			0x83426B33, 0xF01EAB71, 0xB0804187, 0x3C005E5F,
			0x77A057BE, 0xBDE8AE24, 0x55464299, 0xBF582E61,
			0x4E58F48F, 0xF2DDFDA2, 0xF474EF38, 0x8789BDC2,
			0x5366F9C3, 0xC8B38E74, 0xB475F255, 0x46FCD9B9,
			0x7AEB2661, 0x8B1DDF84, 0x846A0E79, 0x915F95E2,
			0x466E598E, 0x20B45770, 0x8CD55591, 0xC902DE4C,
			0xB90BACE1, 0xBB8205D0, 0x11A86248, 0x7574A99E,
			0xB77F19B6, 0xE0A9DC09, 0x662D09A1, 0xC4324633,
			0xE85A1F02, 0x09F0BE8C, 0x4A99A025, 0x1D6EFE10,
			0x1AB93D1D, 0x0BA5A4DF, 0xA186F20F, 0x2868F169,
			0xDCB7DA83, 0x573906FE, 0xA1E2CE9B, 0x4FCD7F52,
			0x50115E01, 0xA70683FA, 0xA002B5C4, 0x0DE6D027,
			0x9AF88C27, 0x773F8641, 0xC3604C06, 0x61A806B5,
			0xF0177A28, 0xC0F586E0, 0x006058AA, 0x30DC7D62,
			0x11E69ED7, 0x2338EA63, 0x53C2DD94, 0xC2C21634,
			0xBBCBEE56, 0x90BCB6DE, 0xEBFC7DA1, 0xCE591D76,
			0x6F05E409, 0x4B7C0188, 0x39720A3D, 0x7C927C24,
			0x86E3725F, 0x724D9DB9, 0x1AC15BB4, 0xD39EB8FC,
			0xED545578, 0x08FCA5B5, 0xD83D7CD3, 0x4DAD0FC4,
			0x1E50EF5E, 0xB161E6F8, 0xA28514D9, 0x6C51133C,
			0x6FD5C7E7, 0x56E14EC4, 0x362ABFCE, 0xDDC6C837,
			0xD79A3234, 0x92638212, 0x670EFA8E, 0x406000E0
		],
		[
			0x3A39CE37, 0xD3FAF5CF, 0xABC27737, 0x5AC52D1B,
			0x5CB0679E, 0x4FA33742, 0xD3822740, 0x99BC9BBE,
			0xD5118E9D, 0xBF0F7315, 0xD62D1C7E, 0xC700C47B,
			0xB78C1B6B, 0x21A19045, 0xB26EB1BE, 0x6A366EB4,
			0x5748AB2F, 0xBC946E79, 0xC6A376D2, 0x6549C2C8,
			0x530FF8EE, 0x468DDE7D, 0xD5730A1D, 0x4CD04DC6,
			0x2939BBDB, 0xA9BA4650, 0xAC9526E8, 0xBE5EE304,
			0xA1FAD5F0, 0x6A2D519A, 0x63EF8CE2, 0x9A86EE22,
			0xC089C2B8, 0x43242EF6, 0xA51E03AA, 0x9CF2D0A4,
			0x83C061BA, 0x9BE96A4D, 0x8FE51550, 0xBA645BD6,
			0x2826A2F9, 0xA73A3AE1, 0x4BA99586, 0xEF5562E9,
			0xC72FEFD3, 0xF752F7DA, 0x3F046F69, 0x77FA0A59,
			0x80E4A915, 0x87B08601, 0x9B09E6AD, 0x3B3EE593,
			0xE990FD5A, 0x9E34D797, 0x2CF0B7D9, 0x022B8B51,
			0x96D5AC3A, 0x017DA67D, 0xD1CF3ED6, 0x7C7D2D28,
			0x1F9F25CF, 0xADF2B89B, 0x5AD6B472, 0x5A88F54C,
			0xE029AC71, 0xE019A5E6, 0x47B0ACFD, 0xED93FA9B,
			0xE8D3C48D, 0x283B57CC, 0xF8D56629, 0x79132E28,
			0x785F0191, 0xED756055, 0xF7960E44, 0xE3D35E8C,
			0x15056DD4, 0x88F46DBA, 0x03A16125, 0x0564F0BD,
			0xC3EB9E15, 0x3C9057A2, 0x97271AEC, 0xA93A072A,
			0x1B3F6D9B, 0x1E6321F5, 0xF59C66FB, 0x26DCF319,
			0x7533D928, 0xB155FDF5, 0x03563482, 0x8ABA3CBB,
			0x28517711, 0xC20AD9F8, 0xABCC5167, 0xCCAD925F,
			0x4DE81751, 0x3830DC8E, 0x379D5862, 0x9320F991,
			0xEA7A90C2, 0xFB3E7BCE, 0x5121CE64, 0x774FBE32,
			0xA8B6E37E, 0xC3293D46, 0x48DE5369, 0x6413E680,
			0xA2AE0810, 0xDD6DB224, 0x69852DFD, 0x09072166,
			0xB39A460A, 0x6445C0DD, 0x586CDECF, 0x1C20C8AE,
			0x5BBEF7DD, 0x1B588D40, 0xCCD2017F, 0x6BB4E3BB,
			0xDDA26A7E, 0x3A59FF45, 0x3E350A44, 0xBCB4CDD5,
			0x72EACEA8, 0xFA6484BB, 0x8D6612AE, 0xBF3C6F47,
			0xD29BE463, 0x542F5D9E, 0xAEC2771B, 0xF64E6370,
			0x740E0D8D, 0xE75B1357, 0xF8721671, 0xAF537D5D,
			0x4040CB08, 0x4EB4E2CC, 0x34D2466A, 0x0115AF84,
			0xE1B00428, 0x95983A1D, 0x06B89FB4, 0xCE6EA048,
			0x6F3F3B82, 0x3520AB82, 0x011A1D4B, 0x277227F8,
			0x611560B1, 0xE7933FDC, 0xBB3A792B, 0x344525BD,
			0xA08839E1, 0x51CE794B, 0x2F32C9B7, 0xA01FBAC9,
			0xE01CC87E, 0xBCC7D1F6, 0xCF0111C3, 0xA1E8AAC7,
			0x1A908749, 0xD44FBD9A, 0xD0DADECB, 0xD50ADA38,
			0x0339C32A, 0xC6913667, 0x8DF9317C, 0xE0B12B4F,
			0xF79E59B7, 0x43F5BB3A, 0xF2D519FF, 0x27D9459C,
			0xBF97222C, 0x15E6FC2A, 0x0F91FC71, 0x9B941525,
			0xFAE59361, 0xCEB69CEB, 0xC2A86459, 0x12BAA8D1,
			0xB6C1075E, 0xE3056A0C, 0x10D25065, 0xCB03A442,
			0xE0EC6E0E, 0x1698DB3B, 0x4C98A0BE, 0x3278E964,
			0x9F1F9532, 0xE0D392DF, 0xD3A0342B, 0x8971F21E,
			0x1B0A7441, 0x4BA3348C, 0xC5BE7120, 0xC37632D8,
			0xDF359F8D, 0x9B992F2E, 0xE60B6F47, 0x0FE3F11D,
			0xE54CDA54, 0x1EDAD891, 0xCE6279CF, 0xCD3E7E6F,
			0x1618B166, 0xFD2C1D05, 0x848FD2C5, 0xF6FB2299,
			0xF523F357, 0xA6327623, 0x93A83531, 0x56CCCD02,
			0xACF08162, 0x5A75EBB5, 0x6E163697, 0x88D273CC,
			0xDE966292, 0x81B949D0, 0x4C50901B, 0x71C65614,
			0xE6C6C7BD, 0x327A140A, 0x45E1D006, 0xC3F27B9A,
			0xC9AA53FD, 0x62A80F00, 0xBB25BFE2, 0x35BDD2F6,
			0x71126905, 0xB2040222, 0xB6CBCF7C, 0xCD769C2B,
			0x53113EC0, 0x1640E3D3, 0x38ABBD60, 0x2547ADF0,
			0xBA38209C, 0xF746CE76, 0x77AFA1C5, 0x20756060,
			0x85CBFE4E, 0x8AE88DD8, 0x7AAAF9B0, 0x4CF9AA7E,
			0x1948C25C, 0x02FB8A8C, 0x01C36AE4, 0xD6EBE1F9,
			0x90D4F869, 0xA65CDEA0, 0x3F09252D, 0xC208E69F,
			0xB74E6132, 0xCE77E25B, 0x578FDFE3, 0x3AC372E6
		]
	];
	//==============================================================


} //END CLASS


/*** Sample Usage
$bf = new SmartCryptoCipherBlowfishCBC('a-56-bytes-secret-key-abcdefghijklmnopqrstuvwxyz-1234567', 'iv:2345;'); // the key must be 56 bytes (characters) ; the iv must be 8 bytes (characters)
$encrypted = $bf->encrypt('this is some example plain text');
$plaintext = $bf->decrypt($encrypted);
echo "plain text: $plaintext";
*/


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: SmartDhKx
 * Provides methods to implement a secure algorithm for Diffie-Hellman key exchange between a server and a client ; Supports dual operation mode (Int64 or BigInt ; for using BigInt requires the PHP GMP extension ...)
 * It implements a modified version of the DH algo to provide much more secure shared data ...
 *
 * @usage       dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 *
 * @access 		private
 * @internal
 *
 * @depends     classes: SmartFrameworkRegistry, Smart, SmartHashCrypto, SmartCipherCrypto, SmartCryptoCipherBlowfishCBC ; PHP GMP extension (optional, only if uses BigInt)
 * @version     v.20220418
 *
 */
final class SmartDhKx {

	private $useBigInt = false;
	private $size = 'default';
	private $prix = 'default';


	public function __construct(bool $useBigInt=false, ?string $prix=null, ?string $size=null) {
		//--
		if(((bool)$useBigInt === true) AND (function_exists('gmp_binomial'))) {
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


	public function getBaseGen(?string $size=null) : string {
		//--
		$size = (string) trim((string)$size);
		if(((string)$size == '') OR ((string)$size == 'default')) {
			$size = (string) $this->size;
		} //end if
		//--
		return (string) $this->rng((string)$size);
		//--
	} //END FUNCTION


	public function getSrvData(?string $basegen) : array {
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


	public function getSrvShad(?string $ssec, ?string $cpub) : string {
		//--
		$prix 	= (string) $this->prix;
		$p 		= (string) $this->prime((string)$prix);
		$shad 	= (string) $this->powm((string)$cpub, (string)$ssec, (string)$p);
		//--
		return (string) $this->shadizer((string)$shad);
		//--
	} //END FUNCTION


	public function getCliData(?string $basegen) : array {
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


	public function getCliShad(?string $csec, ?string $spub) : string {
		//--
		$prix 	= (string) $this->prix;
		$p 		= (string) $this->prime((string)$prix);
		$shad 	= (string) $this->powm((string)$spub, (string)$csec, (string)$p);
		//--
		return (string) $this->shadizer((string)$shad);
		//--
	} //END FUNCTION


	// partial data (cli) derived from any valid idz data
	public function getIdzShadData(?string $idz) : array {
		//--
		$idz = (string) trim((string)$idz);
		//--
		$err = '';
		$cliShad = '';
		if((string)$idz == '') {
			$err = 'Empty Idz';
		} else {
			$arr = (array) $this->idxtizer((string)$idz);
			$arr['err'] = (string) trim((string)($arr['err'] ?? null));
			if((string)$arr['err'] != '') {
				$err = (string) $arr['err'];
			} else {
				$cliShad = (string) trim((string)$this->getCliShad((string)$arr['csec'], (string)$arr['spub']));
				if((string)$cliShad == '') {
					$err = 'Empty Shad';
				} //end if
			} //end if
		} //end if
		//--
		return [
			'type' 	=> 'IdzShadData',
			'mode' 	=> (string) $this->getMode(),
			'size' 	=> (string) $this->size,
			'prix' 	=> (string) $this->prix,
			'shad' 	=> (string) $cliShad,
			'err' 	=> (string) $err,
		];
		//--
	} //END FUNCTION


	// full data
	public function getData() : array {
		//--
		$basegen = $this->getBaseGen((string)$this->size);
		//--
		$srvData = (array)  $this->getSrvData((string)$basegen);
		$cliData = (array)  $this->getCliData((string)$basegen);
		$srvShad = (string) $this->getSrvShad((string)$srvData['sec'], (string)$cliData['pub']);
		$cliShad = (string) $this->getCliShad((string)$cliData['sec'], (string)$srvData['pub']);
		//--
		$err = '';
		if(((string)trim((string)$srvShad) == '') OR ((string)trim((string)$srvShad) == '0') OR ((string)$srvShad !== (string)$cliShad)) {
			$err = 'Shad Mismatch';
		} //end if
		//--
		return [
			'type' 	=> 'Data',
			'mode' 		=> (string) $this->getMode(),
			'size' 		=> (string) $this->size,
			'prix' 		=> (string) $this->prix,
			'prim' 		=> (string) $this->prime((string)$this->prix),
			'basegen' 	=> (string) $basegen,
			'srv' => [
				'sec'  => (string) $srvData['sec'],
				'pub'  => (string) $srvData['pub'],
				'shad' => (string) $srvShad,
			],
			'cli' => [
				'sec'  => (string) $cliData['sec'],
				'pub'  => (string) $cliData['pub'],
				'shad' => (string) $cliShad,
			],
			'idz' => (string) $this->idatizer((string)$cliData['sec'], (string)$srvData['pub']),
			'err' => (string) $err,
		];
		//--
	} //END FUNCTION


	//== [PRIVATES]


	// iddxtizer
	private function idxtizer(?string $idz) : array {
		//--
		$idz = (string) trim((string)$idz);
		if((string)$idz == '') {
			return [
				'err' => 'Invalid IDZ (1)'
			];
		} //end if
		//--
		if(strpos((string)$idz , '!') === false) {
			return [
				'err' => 'Invalid IDZ (2)'
			];
		} //end if
		//--
		$arr = (array) explode('!', (string)$idz);
		if(Smart::array_size($arr) != 3) {
			return [
				'err' => 'Invalid IDZ (3)'
			];
		} //end if
		//--
		$pfx = 'dH.';
		$ver = 'v1';
		$sig = '';
		$mod = '0';
		if($this->useBigInt === true) {
			$sig = 'iHg.';
			$mod = '1';
		} else {
			$sig = 'i64.';
			$mod = '2';
		} //end if else
		if((string)$arr[0] != $pfx.$sig.$ver) {
			return [
				'err' => 'Invalid IDZ (4.'.$mod.')'
			];
		} //end if
		//--
		$arr[1] = (string) trim((string)Smart::b64s_dec((string)$arr[1]));
		if((string)$arr[1] == '') {
			return [
				'err' => 'Invalid IDZ (5)'
			];
		} //end if
		//--
		$arr[2] = (string) trim((string)Smart::b64s_dec((string)$arr[2]));
		if((string)$arr[2] == '') {
			return [
				'err' => 'Invalid IDZ (6)'
			];
		} //end if
		//--
		$bk = (string) trim((string)Smart::base_to_hex_convert((string)$arr[2],85));
		if((string)$bk == '') {
			return [
				'err' => 'Invalid IDZ (7)'
			];
		} //end if
		$arr[2] = (string) trim((string)substr((string)hex2bin((string)$bk),1));
		if((string)$arr[2] == '') {
			return [
				'err' => 'Invalid IDZ (8)'
			];
		} //end if
		//--
		$arr[1] = (string) trim((string)SmartCipherCrypto::blowfish_decrypt((string)Smart::base_from_hex_convert((string)SmartHashCrypto::sha256('&='.$bk.'#'),92),(string)$arr[1]));
		if((string)$arr[1] == '') {
			return [
				'err' => 'Invalid IDZ (9)'
			];
		} //end if
		$arr[1] = (string) trim((string)substr((string)hex2bin((string)trim((string)Smart::base_to_hex_convert((string)$arr[1], 92))),1));
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


	// iddatizer
	private function idatizer(?string $csec, ?string $spub) : string {
		//--
		$shd = 'dH';
		//--
		if($this->useBigInt === true) {
			$shd .= '.iHg';
		} else {
			$shd .= '.i64';
		} //end if else
		//--
		$bk = (string) bin2hex('@'.$spub);
		//--
		return (string) $shd.'.v1!'.Smart::b64s_enc((string)SmartCipherCrypto::blowfish_encrypt((string)Smart::base_from_hex_convert((string)SmartHashCrypto::sha256('&='.$bk.'#'),92),(string)Smart::base_from_hex_convert((string)bin2hex('$'.$csec),92))).'!'.Smart::b64s_enc((string)Smart::base_from_hex_convert((string)$bk,85));
		//--
	} //END FUNCTION


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
				$px = (string) self::primesBigint['h127'];
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

// provide the (WEAK but FAST :: symetrical :: HASH) cryptography support // (ENCRYPT + DECRYPT)
// v.1.2.1 (unixworld)
// Simple but secure encryption based on hash functions
// Basically this algorithm provides a block cipher in OFB mode (output feedback mode)
// requires sha1 function in PHP
// based on :: Quadracom's class v.1.0

/**
 * Class Smart Crypto Hash Encryption
 * This class uses a dynamic generated initialization vector based on Visitor Data thus will cannot be used between different visits ...
 * The purpose of this class is to encrypt / decrypt just per visit data
 *
 * @usage       dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 *
 * @access 		private
 * @internal
 *
 * @depends     classes: Smart, SmartHashCrypto
 * @version     v.20210825
 *
 */
final class SmartCryptoCipherHash {

	// ->


	//========================================
	// @ PRIVATE
	private $hash_key 		= null;		// @var	string :: Hashed value of the user provided encryption key
	// @ PRIVATE
	private $hash_length 	= 0;		// @var	int :: String length of hashed values using the current algorithm
	// @PRIVATE
	private $mode 			= null;		// @var enum :: md5, sha1, sha256, sha512
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
			if(SmartFrameworkRegistry::ifInternalDebug()) {
				if(SmartFrameworkRegistry::ifDebug()) {
					Smart::log_notice(__METHOD__.' # Key is too short: `'.$key.'`');
				} //end if
			} //end if
			Smart::raise_error(__METHOD__.' # Key Size is lower than 7 bytes ('.(int)$klen.') which is not safe against brute force attacks !');
			return;
		} elseif((int)$klen > 4096) { // {{{SYNC-CRYPTO-KEY-MAX}}} ; max key size is enforced to allow ZERO theoretical colissions on any of: md5, sha1, sha256 or sha512
			if(SmartFrameworkRegistry::ifInternalDebug()) {
				if(SmartFrameworkRegistry::ifDebug()) {
					Smart::log_notice(__METHOD__.' # Key is too long: `'.$key.'`');
				} //end if
			} //end if
			Smart::raise_error(__METHOD__.' # Key Size is higher than 4096 bytes ('.(int)$klen.') which is not safe against collisions !');
			return;
		} //end if
		//--

		// for the case: hash/sha256
		if((string)substr((string)$mode, 0, 5) != 'hash/') {
			Smart::raise_error(__METHOD__.' # Invalid Mode: '.$mode);
			return;
		} //end if

		$cfgcrypto = (string) $mode;
		$mode = '';
		$arr = (array) explode('/', (string)$cfgcrypto, 2);
		$cfgcrypto = null;
		$mode = (string) trim((string)($arr[1] ?? ''));
		$arr = null;

		// mode
		switch((string)$mode) {
			case 'md5':
				$this->mode = 'md5';
				break;
			case 'sha1':
				$this->mode = 'sha1';
				break;
			case 'sha512':
				$this->mode = 'sha512';
				break;
			case 'sha256':
			default:
				$this->mode = 'sha256';
		} //end switch

		// Instead of using the key directly we compress it using a hash function
		$this->hash_key = (string) $this->_hash($key);

		// Remember length of hashvalues for later use
		$this->hash_length = (int) strlen((string)$this->hash_key);

	} //END FUNCTION
	//==============================================================


	//==============================================================
	// [PUBLIC]
	 // Method used for encryption
	 // @param	string	$string	Message to be encrypted
	 // @return string	Encrypted message
	public function encrypt($string) {

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

		$string = (string) base64_encode((string)$string); // this is required because it cannot handle unicode characters
		$string = (string) $string.'#CKSUM256#'.SmartHashCrypto::sha256((string)$string, true);

		// gen IV
		$iv = $this->_generate_iv();

		// Clear output
		$out = '';

		// First block of output is ($this->hash_hey XOR IV)
		for($c=0;$c < $this->hash_length;$c++) {
			$out .= chr(ord($iv[$c]) ^ ord($this->hash_key[$c]));
		} //end for

		// Use IV as first key
		$key = $iv;
		$c = 0;

		// Go through input string
		while($c < strlen($string)) {
			// If we have used all characters of the current key we switch to a new one
			if(($c != 0) and ($c % $this->hash_length == 0)) {
				// New key is (Last block of plaintext XOR current Key)
				$key = $this->_hash($key.substr($string,$c - $this->hash_length,$this->hash_length));
			} //end if
			// Generate output by xor-ing input and key character for character
			$out .= chr(ord($key[$c % $this->hash_length]) ^ ord($string[$c]));
			$c++;
		} //end while

	//	return (string) strtoupper((string)bin2hex((string)$out));
		return (string) Smart::b64s_enc((string)$out);

	} //END FUNCTION
	//==============================================================


	//==============================================================
	// [PUBLIC]
	 // Method used for decryption
	 // @param	string	$string	Message to be decrypted
	 // @return string	Decrypted message
	public function decrypt($string) {

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

	//	$string = (string) @hex2bin((string)strtolower((string)trim((string)$string)));
		$string = (string) Smart::b64s_dec((string)trim((string)$string));

		//-- Extract encrypted IV from input
		$tmp_iv = (string) substr((string)$string, 0, (int)$this->hash_length);
		//-- Extract encrypted message from input
		$string = (string) substr((string)$string, (int)$this->hash_length, (int)((int)strlen((string)$string) - (int)$this->hash_length));
		//--
		$iv = '';
		$out = '';
		//--

		//-- Regenerate IV by xor-ing encrypted IV from block 1 and $this->hashed_key :: Mathematics: (IV XOR KeY) XOR Key = IV
		for($c=0; $c<$this->hash_length;$c++) {
			$iv .= chr(ord($tmp_iv[$c]) ^ ord($this->hash_key[$c]));
		} //end for
		//-- Use IV as key for decrypting the first block cyphertext
		$key = $iv;
		$c = 0;
		//--

		//-- Loop through the whole input string
		while($c < strlen($string)) {
			//-- If we have used all characters of the current key we switch to a new one
			if(($c != 0) and ($c % $this->hash_length == 0)) {
				// New key is (Last block of recovered plaintext XOR current Key)
				$key = $this->_hash($key.substr($out,$c - $this->hash_length,$this->hash_length));
			} //end if
			//-- Generate output by xor-ing input and key character for character
			$out .= chr(ord($key[$c % $this->hash_length]) ^ ord($string[$c]));
			//--
			$c++;
			//--
		} //end while
		//--

		//--
		$arr = (array) explode('#CKSUM256#', $out, 2);
		$out = (string) trim((string)($arr[0] ?? ''));
		$chk = (string) trim((string)($arr[1] ?? ''));
		//--
		if((string)SmartHashCrypto::sha256((string)$out, true) == (string)$chk) {
			$out = (string) base64_decode((string)$out);
		} else {
			$out = ''; // invalid checksum
		} //end if
		//--

		//--
		return (string) $out;
		//--

	} //END FUNCTION
	//==============================================================


	//==============================================================
	//============================================================== PRIVATES
	//==============================================================


	//==============================================================
	 // Hashfunction used for encryption
	 // This class hashes any given string using the best available hash algorithm.
	 // Default is using sha1, but it is not the best recommended ...
	 // @access	private
	 // @param	string	$string	Message to hashed
	 // @return string	Hash value of input message
	private function _hash($string) {

		// force use sha1() encryption (unixman)
		//$result = sha1($string);
		//$out ='';
		// Convert hexadecimal hash value to binary string
		//for($c=0;$c<strlen($result);$c+=2) {
		//	$out .= chr(hexdec($result[$c].$result[$c+1]));
		//} //end for
		//return $out;

		switch((string)$this->mode) { // enhancement by unixman
			case 'md5':
				$result = SmartHashCrypto::md5($string);
				break;
			case 'sha1':
				$result = SmartHashCrypto::sha1($string);
				break;
			case 'sha256':
				$result = SmartHashCrypto::sha256($string);
				break;
			case 'sha512':
				$result = SmartHashCrypto::sha512($string);
				break;
			default:
				Smart::log_warning(__METHOD__.' # ERROR: Invalid mode: '.$this->mode.' ; Using SHA1');
				$result = (string) sha1((string)$string);
		} //end switch

		return (string) hex2bin((string)$result); // convert hexadecimal hash value to binary string

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

		// Initialize pseudo random generator
		// seed rand: (double)microtime()*1000000 // no more needed

		// Collect very random data.
		// Add as many "pseudo" random sources as you can find.
		// Possible sources: Memory usage, diskusage, file and directory content...
		$iv =  (string) Smart::random_number();
		$iv .= (string) Smart::unique_entropy();
		$iv .= (string) SmartUtils::get_visitor_tracking_uid();
		$iv .= (string) implode("\r", (array)$_SERVER);
		$iv .= (string) implode("\r", (array)$_COOKIE);

		return $this->_hash($iv);

	} //END FUNCTION
	//==============================================================


	//------------------------------------
	//-- # EXAMPLE USAGE:
	// $crypt = new SmartCryptoCipherHash('the secret ...');
	// $enc_text = $crypt->encrypt('text to be encrypted');
	// $dec_text = $crypt->decrypt($enc_text);
	//-- # WARNING: !!! The $encrypted WILL BE ALWAYS (ALMOST) DIFFERENT !!!
	//------------------------------------


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
