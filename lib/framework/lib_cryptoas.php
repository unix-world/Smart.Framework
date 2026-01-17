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
// Smart-Framework - Asymmetric Crypto Support # r.20260116
// 		* security key:
// 			- Csrf, built-in ; requires PHP GMP extension
// 		* digital certificates sign and verify, utilities:
// 			- EcDSA (Elliptic Curve), using OpenSSL
// 			- EcDSA ASN1 conversions, built-in
//======================================================
// NOTICE: This is unicode safe
//======================================================

// [PHP8]

//--
if(!function_exists('gmp_export')) { // most recent GMP method in PHP used by SmartCryptoEcdsaAsn1Sig
	@http_response_code(500);
	die('ERROR: The PHP GMP extension is required for Smart.Framework / Crypto / AS');
} //end if
//--


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
 * @depends 	PHP GMP extension, classes: Smart, SmartHashCrypto
 *
 * @version 	v.20250714
 * @package 	Application
 *
 */
final class SmartCsrf {

	// ::


	//==============================================================
	// this is intended to store in a private session ; if need to be exposed in a cookie (public) it have to be encrypted, ex: Blowfish !
	public static function newPrivateKey() : string {
		//--
		return (string) Smart::int10_to_base62_str((int)time()).'#'.Smart::uuid_35(); // case sensitive ; bind the key with a date/day prefix, will be checked in verify ; this way a key cannot be reused more than max 24 hours !
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
		if(!function_exists('gmp_init')) {
			Smart::raise_error(__METHOD__.' # PHP GMP Extension is required');
			return false;
		} //end if
		//--
		$privKey = (string) trim((string)$privKey);
		if(strpos((string)$privKey, '#') === false) {
			return false;
		} //end if
		$arr = (array) explode('#', $privKey, 2);
		$b62 = (string) trim((string)($arr[0] ?? null));
		$arr = null;
		if((string)$b62 == '') {
			return false;
		} //end if
		if((int)strlen((string)$b62) !== (int)strspn((string)$b62, (string)Smart::CHARSET_BASE_62)) {
			return false;
		} //end if
		$hex = (string) Smart::base_to_hex_convert((string)$b62, 62);
		$b62 = null;
		if((string)trim((string)$hex) == '') {
			return false;
		} //end if
		//--
		$b10N = (string) gmp_init('0x'.$hex);
		$hex = null;
		//--
		$now10N = (string) gmp_init('0x'.dechex((int)time()));
		//--
		$diff10N = (string) gmp_sub((string)$now10N, (string)$b10N);
		if((int)gmp_cmp((string)$diff10N, '0') <= 0) { // diff must be positive
			return false;
		} //end if
		//--
		if((int)gmp_cmp('3600', (string)$diff10N) <= 0) { // the difference in seconds must not be more than 3600 (1 hour) ; must be positive
			return false;
		} //end if
		//--
		$pubKey = (string) trim((string)$pubKey);
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


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: SmartCryptoEddsaSodium - provides PHP LibSodium based EdDSA crypto implementation
 * Supports: Ed25519
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @depends 	extensions: PHP LibSodium ; classes: Smart
 *
 * @version 	v.20260116
 * @package     @Core:Crypto
 *
 */
final class SmartCryptoEddsaSodium {

	public const ED25519_SECRET_SEED_LEN = 32;

	public const ED25519_PRIVATE_KEY_LEN = 64;
	public const ED25519_PUBLIC_KEY_LEN  = 32;


	//==============================================================
	/**
	 * Verifies if LibSodium extension is available and supports the EdDSA Ed25519
	 *
	 * @return BOOL 								TRUE if available, FALSE if not
	 */
	public static function isAvailable() : bool {
		//--
		if(
			(!function_exists('sodium_crypto_sign_keypair'))
			||
			(!function_exists('sodium_crypto_sign_seed_keypair'))
			||
			(!function_exists('sodium_crypto_sign_secretkey'))
			||
			(!function_exists('sodium_crypto_sign_publickey_from_secretkey'))
			||
			(!function_exists('sodium_crypto_sign_publickey'))
			||
			(!function_exists('sodium_crypto_sign_detached'))
			||
			(!function_exists('sodium_crypto_sign_verify_detached'))
		) {
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Generates a New EdDSA Ed25519 Key Pair: Private Key and Public Key
	 *
	 * @param NULL | STRING $secret 				*OPTIONAL* default is NULL ; if Non-Null will use the secret as a seed
	 *
	 * @return ARRAY 								[ err, mode, encoding, secret, privKey, pubKey ]
	 */
	public static function ed25519NewKeypair(?string $secret=null) : array {
		//--
		// if secret is null will seed it randomly ; this is the recommended approach
		//--
		$arr = [
			'err' 			=> '?',
			'mode' 			=> 'Ed25519',
			'encoding' 		=> 'Base64',
			'secret' 		=> '',
			'privKey' 		=> '',
			'pubKey' 		=> '',
		];
		//--
		if(self::isAvailable() !== true) {
			$arr['err'] = 'PHP Sodium Extension is missing';
			return (array) $arr;
		} //end if
		//--
		$keyPair = '';
		if($secret === null) {
			$arr['secret'] = 'CryptoRand';
			try {
				$keyPair = (string) sodium_crypto_sign_keypair();
			} catch(Exception $e) {
				$arr['err'] = 'Random KeyPair Generation Failed: # '.$e->getMessage();
				return (array) $arr;
			} //end try catch
		} else if((int)strlen((string)$secret) == (int)self::ED25519_SECRET_SEED_LEN) {
			$arr['secret'] = 'Seeded';
			try {
				$keyPair = (string) sodium_crypto_sign_seed_keypair((string)$secret);
			} catch(Exception $e) {
				$arr['err'] = 'Secret Based KeyPair Generation Failed: # '.$e->getMessage();
				return (array) $arr;
			} //end try catch
		} else {
			$arr['err'] = 'Secret must be exact 32 bytes';
			return (array) $arr;
		} //end if
		if((string)trim((string)$keyPair) == '') {
			$arr['err'] = 'Key Pair generation Failed: Empty';
			return (array) $arr;
		} //end if
		//--
		$privateKey = '';
		try {
			$privateKey = (string) sodium_crypto_sign_secretkey((string)$keyPair);
		} catch(Exception $e) {
			$arr['err'] = 'Private Key Generation Failed: # '.$e->getMessage();
			return (array) $arr;
		} //end try catch
		if((string)trim((string)$privateKey) == '') {
			$arr['err'] = 'Private Key generation Failed: Empty';
			return (array) $arr;
		} //end if
		if((int)strlen((string)$privateKey) != (int)self::ED25519_PRIVATE_KEY_LEN) { // SODIUM_CRYPTO_SIGN_BYTES
			$arr['err'] = 'Private Key generation Failed: Invalid Length';
			return (array) $arr;
		} //end if
		//--
		$publicKey = '';
		try {
			$publicKey  = (string) sodium_crypto_sign_publickey_from_secretkey((string)$privateKey);
		} catch(Exception $ex) {
			try {
				$publicKey = (string) sodium_crypto_sign_publickey((string)$keyPair);
			} catch(Exception $e) {
				$arr['err'] = 'Public Key Generation Failed: # '.$e->getMessage();
				return (array) $arr;
			} //end try catch
		} //end try catch
		if((string)trim((string)$publicKey) == '') {
			$arr['err'] = 'Public Key generation Failed: Empty';
			return (array) $arr;
		} //end if
		if((int)strlen((string)$publicKey) != (int)self::ED25519_PUBLIC_KEY_LEN) { // SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
			$arr['err'] = 'Public Key generation Failed: Invalid Length';
			return (array) $arr;
		} //end if
		//--
		$privateKey = (string) trim((string)Smart::b64_enc((string)$privateKey));
		if((string)$privateKey == '') {
			$arr['err'] = 'B64 Private Key is Empty';
			return (array) $arr;
		} //end if
		//--
		$publicKey = (string) trim((string)Smart::b64_enc((string)$publicKey));
		if((string)$publicKey == '') {
			$arr['err'] = 'B64 Public Key is Empty';
			return (array) $arr;
		} //end if
		//--
		$arr['privKey'] = (string) $privateKey;
		$arr['pubKey']  = (string) $publicKey;
		//--
		$arr['err'] = ''; // clear
		return (array) $arr;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Sign a Message using EdDSA Ed25519
	 *
	 * @param STRING $privateKey 					The Private Key (Base64)
	 * @param STRING $publicKey 					The Public Key (Base64)
	 * @param STRING $message 						The Message to be Signed
	 *
	 * @return ARRAY 								[ err, mode, signatureB64 ]
	 */
	public static function ed25519SignData(string $privateKey, string $publicKey, string $message) : array {
		//--
		// to verify with a private key just create a signature of the message with the secret and compare strings with the private signature if match
		// to verify with a public key use the ed25519VerifySignedData() method
		//--
		$arr = [
			'err' 			=> '?',
			'mode' 			=> 'Ed25519',
			'signatureB64' 	=> '',
		];
		//--
		if(self::isAvailable() !== true) {
			$arr['err'] = 'PHP Sodium Extension is missing';
			return (array) $arr;
		} //end if
		//--
		$privateKey = (string) trim((string)$privateKey);
		if((string)$privateKey == '') {
			$arr['err'] = 'B64 Private Key is Empty';
			return (array) $arr;
		} //end if
		//--
		$rawPrivKey = (string) Smart::b64_dec((string)$privateKey, true); // strict
		if((string)$rawPrivKey == '') { // do not trim !
			$arr['err'] = 'Private Key is Empty';
			return (array) $arr;
		} //end if
		if((int)strlen((string)$rawPrivKey) != (int)self::ED25519_PRIVATE_KEY_LEN) { // SODIUM_CRYPTO_SIGN_BYTES
			$arr['err'] = 'Private Key Invalid Size';
			return (array) $arr;
		} //end if
		//--
		$publicKey = (string) trim((string)$publicKey);
		if((string)$publicKey == '') {
			$arr['err'] = 'B64 Public Key is Empty';
			return (array) $arr;
		} //end if
		//--
		$rawPubKey = (string) Smart::b64_dec((string)$publicKey, true); // strict
		if((string)$rawPubKey == '') { // do not trim !
			$arr['err'] = 'Public Key is Empty';
			return (array) $arr;
		} //end if
		if((int)strlen((string)$rawPubKey) != (int)self::ED25519_PUBLIC_KEY_LEN) { // SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
			$arr['err'] = 'Public Key Invalid Size';
			return (array) $arr;
		} //end if
		//--
		if((string)$message == '') { // do not trim !
			$arr['err'] = 'Message is Empty';
			return (array) $arr;
		} //end if
		//--
		$signature = '';
		try {
			$signature = (string) sodium_crypto_sign_detached((string)$message, (string)$rawPrivKey);
		} catch(Exception $e) {
			$arr['err'] = 'Signature Generation Failed: # '.$e->getMessage();
			return (array) $arr;
		} //end try catch
		if((string)$signature == '') {
			$arr['err'] = 'Signature generation Failed: Empty';
			return (array) $arr;
		} //end if
		//--
		$signatureB64 = (string) trim((string)Smart::b64_enc((string)$signature));
		if((string)trim((string)$signatureB64) == '') {
			$arr['err'] = 'B64 Signature is Empty';
			return (array) $arr;
		} //end if
		//--
		$arrVfy = (array) self::ed25519VerifySignedData((string)Smart::b64_enc((string)$rawPubKey), (string)$signatureB64, (string)$message);
		if((string)$arrVfy['err'] != '') {
			$arr['err'] = 'Verification Failed: '.$arrVfy['err'];
			return (array) $arr;
		} //end if
		if($arrVfy['verifyResult'] !== true) {
			$arr['err'] = 'Verification Failed';
			return (array) $arr;
		} //end if
		//--
		$arr['signatureB64'] = (string) $signatureB64;
		//--
		$arr['err'] = ''; // clear
		return (array) $arr;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Verify the Signature of a previous Message using EdDSA Ed25519
	 *
	 * @param STRING $publicKey 					The Public Key (Base64)
	 * @param STRING $signatureB64 					The Signature (Base64)
	 * @param STRING $message 						The Message to be verified with the signature
	 *
	 * @return ARRAY 								[ err, mode, verifyResult ] ; the verifyResult must be TRUE if verified, otherwise may return NULL or INTEGER
	 */
	public static function ed25519VerifySignedData(string $publicKey, string $signatureB64, string $message) : array {
		//--
		$arr = [
			'err' 			=> '?',
			'mode' 			=> 'Ed25519',
			'verifyResult' 	=> null, // null (init); TRUE or FALSE
		];
		//--
		if(self::isAvailable() !== true) {
			$arr['err'] = 'PHP Sodium Extension is missing';
			return (array) $arr;
		} //end if
		//--
		$publicKey = (string) trim((string)$publicKey);
		if((string)$publicKey == '') {
			$arr['err'] = 'B64 Public Key is Empty';
			return (array) $arr;
		} //end if
		//--
		$rawPubKey = (string) Smart::b64_dec((string)$publicKey, true); // strict
		if((string)$rawPubKey == '') { // do not trim !
			$arr['err'] = 'Public Key is Empty';
			return (array) $arr;
		} //end if
		if((int)strlen((string)$rawPubKey) != (int)self::ED25519_PUBLIC_KEY_LEN) { // SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
			$arr['err'] = 'Public Key Invalid Size';
			return (array) $arr;
		} //end if
		//--
		$signatureB64 = (string) trim((string)$signatureB64);
		if((string)$signatureB64 == '') {
			$arr['err'] = 'B64 Signature is Empty';
			return (array) $arr;
		} //end if
		//--
		$signature = (string) Smart::b64_dec((string)$signatureB64, true); // strict
		if((string)$signature == '') { // do not trim !
			$arr['err'] = 'Signature is Empty';
			return (array) $arr;
		} //end if
		if((int)strlen((string)$signature) != (int)self::ED25519_PRIVATE_KEY_LEN) { // SODIUM_CRYPTO_SIGN_BYTES
			$arr['err'] = 'Signature Invalid Size';
			return (array) $arr;
		} //end if
		//--
		if((string)$message == '') { // do not trim, must be preserved exactly how it is
			$arr['err'] = 'Message is Empty';
			return (array) $arr;
		} //end if
		//--
		$ok = false;
		try {
			$ok = sodium_crypto_sign_verify_detached((string)$signature, (string)$message, (string)$rawPubKey); // do not cast, it is a security risk, will be checked below if === TRUE
		} catch(Exception $e) {
			$arr['verifyResult'] = false;
			$arr['err'] = 'Signature Verification Failed with Error: # '.$e->getMessage();
			return (array) $arr;
		} //end try catch
		if(!is_bool($ok)) {
			$arr['verifyResult'] = false;
			$arr['err'] = 'Signature Verification Failed';
			return (array) $arr;
		} //end if
		if($ok !== true) {
			$arr['verifyResult'] = false;
			$arr['err'] = 'Signature is Invalid';
			return (array) $arr;
		} //end if
		$arr['verifyResult'] = true;
		//--
		$arr['err'] = ''; // clear
		return (array) $arr;
		//--
	} //END FUNCTION
	//==============================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=====================================================================================
//===================================================================================== INTERFACE START [OK: NAMESPACE]
//=====================================================================================


/**
 * Asymmetric Crypto OpenSSL Generic Interface
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20260116
 * @package     @Core:Crypto
 *
 */
interface SmartInterfaceCryptoAsOpenSSL {

	// :: INTERFACE

	//=====
	/**
	 * Sign Data, using OpenSSL
	 * THIS MUST BE EXTENDED
	 * RETURN: ARRAY
	 */
	public static function signData(?string $thePrivKey, ?string $thePubKey, ?string $data, string $algo='', ?string $privKeyPassword=null, bool $useASN1=true) : array;
	//=====


	//=====
	/**
	 * Verify Signed Data, using OpenSSL
	 * THIS MUST BE EXTENDED
	 * RETURN: ARRAY
	 */
	public static function verifySignedData(?string $thePubKey, ?string $data, ?string $signatureB64, string $algo='', bool $useASN1=true) : array;
	//=====


	//=====
	/**
	 * Create a New Certificate and the coresponding KeyPair (PrivateKey and PublicKey), all in PEM format, using OpenSSL
	 * THIS MUST BE EXTENDED
	 * RETURN: ARRAY
	 */
	public static function newCertificate(array $dNames=[], int $years=1, string $method='', string $algo='', ?string $privKeyPassword=null) : array;


} //END INTERFACE


//=====================================================================================
//===================================================================================== INTERFACE END
//=====================================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Asymmetric Crypto OpenSSL Generic AbstractClass
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20260116
 * @package     @Core:Crypto
 *
 */
abstract class SmartAbstractCryptoAsOpenSSL
	implements SmartInterfaceCryptoAsOpenSSL {

	// :: ABSTRACT

	// Can be any, golang/smartgo supports all
	public const OPENSSL_CSR_DEF_ALGO 		= 'sha512'; 	// 'sha512'   | 'sha384'   | 'sha256'   | 'sha3-512' | 'sha3-384' | 'sha3-256'
	public const OPENSSL_SIGN_DEF_ALGO 		= 'sha3-512'; 	// 'sha3-512' | 'sha3-384' | 'sha3-256' | 'sha512'   | 'sha384'   | 'sha256'

	public const SIGNATURE_MODE_ASN1 		= 'ASN1';
	public const SIGNATURE_MODE_NON_ASN1 	= 'nonASN1';

	protected const OPENSSL_CONF_PATH = 'tmp/openssl-smart.conf';
	protected const OPENSSL_CONF_DATA = '
[ req_distinguished_name ]

[ req ]
distinguished_name = req_distinguished_name
req_extensions = v3_req
default_md = null

[ v3_req ]
basicConstraints = critical, CA:FALSE
keyUsage = critical, keyEncipherment, dataEncipherment, digitalSignature, nonRepudiation, keyAgreement
';

	protected const OPENSSL_DN_KEYS = [
		'commonName' 				=> true,  // 'localhost' * REQUIRED *
		'emailAddress' 				=> false, // '' | 'admin@localhost'
		'countryName' 				=> false, // '' | 'GB'
		'localityName' 				=> false, // '' | 'London'
		'stateOrProvinceName' 		=> false, // '' | 'Greater London'
		'organizationName' 			=> false, // '' | 'Unix-World'
		'organizationalUnitName' 	=> false, // '' | 'Unix-World.org'
	];
	protected const OPENSSL_CSR_OPTIONS = [
		'config' 			=> SMART_FRAMEWORK_ROOT_ABSOLUTE_PATH . self::OPENSSL_CONF_PATH,
		'req_extensions' 	=> 'v3_req', // {{{SYNC-OPENSSL-V3}}}
		'x509_extensions' 	=> 'v3_req', // {{{SYNC-OPENSSL-V3}}}
		'digest_alg' 		=> self::OPENSSL_CSR_DEF_ALGO,
	];

	private static $initialized = null;


	//-------- [PUBLIC METHODS]: generic, can be re-implemented


	//==============================================================
	/**
	 * Create a Digital Signature
	 * Supported Algorithms: sha3-512, sha512, sha3-384, sha384, sha3-256, sha256, ...
	 *
	 * @param STRING $thePrivKey 					The PEM Private Key (plain or encrypted)
	 * @param STRING $thePubKey 					The PEM Public Key, required for verification
	 * @param STRING $data 							Data to be Signed
	 * @param STRING $algo 							Algorithm to be used for the Signature, must be compliant with the algorithm used by the used keys and certificate ; by example cannot sign using sha256 or sha3-256 with a sha512 or sha3-512 certificate private/public key because the hash size differs
	 * @param STRING $privKeyPassword 				*OPTIONAL* The password for the PEM Private Key if requires
	 * @param BOOL   $useASN1 						*OPTIONAL* by default is TRUE, will create an ASN1 compliant signature ; if set to FALSE will create a Raw (Non-ASN1) signature
	 *
	 * @return ARRAY 								[ err, algo, mode, signatureB64 ]
	 */
	public static function signData(?string $thePrivKey, ?string $thePubKey, ?string $data, string $algo=self::OPENSSL_SIGN_DEF_ALGO, ?string $privKeyPassword=null, bool $useASN1=true) : array {
		//--
		$arr = [
			'err' 			=> '?',
			'algo' 			=> (string) $algo,
			'mode' 			=> (string) (($useASN1 === false) ? self::SIGNATURE_MODE_NON_ASN1 : self::SIGNATURE_MODE_ASN1),
			'signatureB64' 	=> '',
		];
		//--
		$err = (string) self::init();
		if($err) {
			$arr['err'] = (string) 'Failed: '.$err;
			return (array) $arr;
		} //end if
		//--
		$arr['err'] = 'Method Not Implemented';
		return (array) $arr;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Verify a Digital Signature
	 * Supported Algorithms: sha3-512, sha512, sha3-384, sha384, sha3-256, sha256, ...
	 *
	 * @param STRING $thePubKey 					The PEM Public Key or the Certificate PEM
	 * @param STRING $data 							Data to be Verified
	 * @param STRING $signatureB64 					The Base64 Signature
	 * @param STRING $algo 							Algorithm to be used for the Signature, must be compliant with the algorithm used by the used keys and certificate ; by example cannot sign using sha256 or sha3-256 with a sha512 or sha3-512 certificate private/public key because the hash size differs
	 * @param BOOL   $useASN1 						*OPTIONAL* by default is TRUE, will create an ASN1 compliant signature ; if set to FALSE will create a Raw (Non-ASN1) signature
	 *
	 * @return ARRAY 								[ err, algo, mode, verifyResult ] ; the verifyResult must be TRUE if verified, otherwise may return NULL or INTEGER
	 */
	public static function verifySignedData(?string $thePubKey, ?string $data, ?string $signatureB64, string $algo=self::OPENSSL_SIGN_DEF_ALGO, bool $useASN1=true) : array {
		//--
		// $thePubKey can be a PEM Public Key or a PEM Certificate
		//--
		$arr = [
			'err' 			=> '?',
			'algo' 			=> (string) $algo,
			'mode' 			=> (string) (($useASN1 === false) ? self::SIGNATURE_MODE_NON_ASN1 : self::SIGNATURE_MODE_ASN1),
			'verifyResult' 	=> null, // null (init); TRUE if verify = 1 ; otherwise verify can be INTEGER, NON-VALID: 0 / -1 ...
		];
		//--
		$err = (string) self::init();
		if($err) {
			$arr['err'] = (string) 'Failed: '.$err;
			return (array) $arr;
		} //end if
		//--
		$arr['err'] = 'Method Not Implemented';
		return (array) $arr;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Create a New Certificate and the key Pair as Private and Public Key, all in PEM format
	 * Supported Algorithms: sha3-512, sha512, sha3-384, sha384, sha3-256, sha256, ...
	 * Supported Methods: see EcDSA or RSA ...
	 *
	 * @param ARRAY  $dNames 						The MetaInfo Certificate Names, minimum required is: [ commonName ] ; example: ['commonName' => 'My Sample Name', 'emailAddress' => 'my@email.local', 'organizationName' => 'my.local', 'organizationalUnitName' => 'My Sample Test - Digital Signature']
	 * @param INT    $years 						The number of years for the certificate to be valid, 1..100
	 * @param STRING $method 						The EcDSA curve or RSA Mode
	 * @param STRING $algo 							Algorithm to be used for the Signature, must be compliant with the algorithm used by the curve
	 * @param STRING $privKeyPassword 				*OPTIONAL* The password for the PEM Private Key ; if provided the returned Private Key PEM will be encrypted
	 *
	 * @return ARRAY 								[ err, mode, algo, curve, scheme, years, dNames, certificate, privKey, pubKey, serial, dateTime ]
	 */
	public static function newCertificate(array $dNames=['commonName' => 'localhost'], int $years=1, string $method='', string $algo=self::OPENSSL_CSR_DEF_ALGO, ?string $privKeyPassword=null) : array {
		//--
		// IMPORTANT: returned CERTIFICATE, PRIVATE KEY, PUBLIC KEY are plain, not passphrase protected, must be protected if stored as ENCRYPTED !
		//--
		$serial = (string) self::newSerialForCertificate(); // hex
		//--
		$arr = [
			'err' 			=> '?',
			'mode' 			=> '', 					// Ex: 'EcDSA' or 'RSA'
			'algo' 			=> (string) $algo, 		// signature algo
			'curve' 		=> '', 					// set $method for curves only (ex: EcDSA) ; must be empty for non-curves
			'scheme' 		=> '', 					// set $method for non-curves  (ex: RSA)   ; N/A for curves, must be empty for curves, use for non-curves only such as RSA, Dilithium, Kyber, ...
			'years' 		=> (int)    $years,
			'dNames' 		=> (array)  $dNames,
			'certificate' 	=> '',
			'privKey' 		=> '',
			'pubKey' 		=> '',
			'serial' 		=> (string) '0x'.$serial,
			'dateTime' 		=> (string) date('Y-m-d H:i:s O'),
		];
		//--
		$err = (string) self::init();
		if($err) {
			$arr['err'] = (string) 'Failed: '.$err;
			return (array) $arr;
		} //end if
		//--
		$arr['err'] = 'Method Not Implemented';
		return (array) $arr;
		//--
	} //END FUNCTION
	//==============================================================


	//-------- [PUBLIC METHODS]: final


	//==============================================================
	/**
	 * Verifies if OpenSSL extension is Enabled and the openssl local config has been completed
	 *
	 * @return BOOL 								TRUE if available, FALSE if not
	 */
	final public static function isAvailable() : bool {
		//--
		return (bool) (self::init() ? false : true);
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Parse the PEM Certificate via OpenSSL
	 *
	 * @param STRING $theCertificate 				The PEM Certificate
	 * @param STRING $privKeyPassword 				*OPTIONAL* The password for the PEM Private Key if requires
	 *
	 * @return ARRAY 								The structure of the Certificate as parsed by OpenSSL
	 */
	final public static function parsePemCertificate(string $theCertificate, bool $shortNames=true) : array {
		//--
		$theCertificate = (string) trim((string)$theCertificate);
		if((string)$theCertificate == '') {
			return [];
		} //end if
		//--
		if(self::isValidCertificatePEM((string)$theCertificate) !== true) {
			return [];
		} //end if
		//--
		$arrInf = openssl_x509_parse((string)$theCertificate, (bool)$shortNames);
		if(!is_array($arrInf)) {
			return [];
		} //end if
		//-- fix:
		if(isset($arrInf['version'])) { // PHP bug # https://github.com/php/php-src/issues/11918 ; PHP reports less than the certificate version with -1
			if(is_int($arrInf['version'])) {
				$arrInf['version']++;
			} //end if
		} //end if
		//-- safe array conversion, OpenSSL may include weird characters from certificate parsing, fix this via JSON encode/decode
		$jsonInf = (string) trim((string)Smart::json_encode((array)$arrInf, true, false, false));
		if((string)$jsonInf == '') {
			return [];
		} //end if
		$arrInf  = Smart::json_decode((string)$jsonInf);
		if(!is_array($arrInf)) {
			return [];
		} //end if
		//--
		return (array) $arrInf;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Extract the PEM Public Key from a PEM Private Key or a PEM Certificate
	 *
	 * @param STRING $thePrivKeyOrCertificate 		The PEM Private Key or PEM Certificate
	 * @param STRING $privKeyPassword 				*OPTIONAL* The password for the PEM Private Key if requires
	 *
	 * @return ARRAY 								[ err, pubKey, version ]
	 */
	final public static function getPemPublicKeyFromPemPrivateKeyOrPemCertificate(string $thePrivKeyOrCertificate, ?string $privKeyPassword=null) : array {
		//--
		$arr = [
			'err' 		=> '?',
			'pubKey' 	=> null, // or string PEM
			'version' 	=> null,
		];
		//--
		$err = (string) self::init();
		if($err) {
			$arr['err'] = (string) 'Failed: '.$err;
			return (array) $arr;
		} //end if
		//--
		$pKey = null;
		if(self::isValidPrivateKeyPEM((string)$thePrivKeyOrCertificate) === true) {
			$pKey = openssl_pkey_get_private((string)$thePrivKeyOrCertificate, $privKeyPassword);
		} elseif(self::isValidCertificatePEM((string)$thePrivKeyOrCertificate) === true) {
			$pKey = openssl_pkey_get_public((string)$thePrivKeyOrCertificate);
		} else {
			$arr['err'] = 'Private Key or Certificate is Empty or Invalid';
			return (array) $arr;
		} //end if
		if(!$pKey) {
			$arr['err'] = 'Failed to Extract the Private Key or Certificate';
			return (array) $arr;
		} //end if
		//--
		$detailsPrivKey = openssl_pkey_get_details($pKey);
	//	openssl_free_key($pKey); // free the key from memory ; not needed ; deprecated since 8.0, as OpenSSLAsymmetricKey objects are freed automatically
		if(!is_array($detailsPrivKey)) {
			$arr['err'] = 'Failed to Get the Private Key Details';
			return (array) $arr;
		} //end if
		//print_r($detailsPrivKey); die();
		$thePubKey = (string) trim((string)($detailsPrivKey['key'] ?? null));
		if((string)$thePubKey == '') {
			$arr['err'] = 'Public Key PEM is Empty';
			return (array) $arr;
		} //end if
		if(self::isValidPublicKeyPEM((string)$thePubKey) !== true) {
			$arr['err'] = 'Public Key PEM is Invalid';
			return (array) $arr;
		} //end if
		//--
		$keyVersion = (string) trim((string)($detailsPrivKey['type'] ?? null));
		//--
		$arr['pubKey']  = (string) $thePubKey;
		$arr['version'] = (string) $keyVersion;
		//--
		$arr['err'] = ''; // clear
		return (array) $arr;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Decrypt an encrypted PEM Private Key using a password
	 *
	 * @param STRING $encryptedPrivKeyPem 			The encrypted PEM Private Key
	 * @param STRING $privKeyPassword 				The password used to decrypt the PEM Private Key
	 *
	 * @return ARRAY 								[ err, privKey ]
	 */
	final public static function decryptPrivateKeyPem(string $encryptedPrivKeyPem, ?string $privKeyPassword) : array {
		//--
		$arr = [
			'err' 			=> '?',
			'privKey' 		=> '',
		];
		//--
		$err = (string) self::init();
		if($err) {
			$arr['err'] = (string) 'Failed: '.$err;
			return (array) $arr;
		} //end if
		//--
		if(self::isValidPrivateKeyPEM((string)$encryptedPrivKeyPem) !== true) {
			$arr['err'] = 'Encrypted Private Key is Empty or Invalid';
			return (array) $arr;
		} //end if
		if(self::isValidEncryptedPrivateKeyPEM((string)$encryptedPrivKeyPem) !== true) {
			$arr['err'] = 'Private Key is Not Encrypted';
			return (array) $arr;
		} //end if
		//--
		if((string)$privKeyPassword == '') {
			$arr['err'] = 'PassPhrase is Empty';
			return (array) $arr;
		} //end if
		//--
		$pKey = openssl_pkey_get_private((string)$encryptedPrivKeyPem, (string)$privKeyPassword);
		if(!$pKey) {
			$arr['err'] = 'Failed to Extract the Encrypted Private Key';
			return (array) $arr;
		} //end if
		//--
		$options = [ // {{{SYNC-OPENSSL-ENC-PKEY-EXPORT-OPTIONS}}}
			'encrypt_key_cipher' => OPENSSL_CIPHER_AES_256_CBC,
		];
		$thePrivKey = '';
		if(!openssl_pkey_export($pKey, $thePrivKey, null, (array)$options)) {
			$arr['err'] = 'Failed to Export the Plain Private Key';
			return (array) $arr;
		} //end if
		$thePrivKey = (string) trim((string)$thePrivKey);
		if((string)$thePrivKey == '') {
			$arr['err'] = 'Plain Private Key PEM is Empty';
			return (array) $arr;
		} //end if
		if(self::isValidPrivateKeyPEM((string)$thePrivKey) !== true) {
			$arr['err'] = 'Plain Private Key PEM is Invalid';
			return (array) $arr;
		} //end if
		if(self::isValidPlainPrivateKeyPEM((string)$thePrivKey) !== true) {
			$arr['err'] = 'Private Key is Not Plain';
			return (array) $arr;
		} //end if
		//--
		$arr['privKey'] = (string) $thePrivKey;
		//--
		$arr['err'] = ''; // clear
		return (array) $arr;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Encrypt a plain PEM Private Key using a password
	 *
	 * @param STRING $plainPrivKeyPem 				The plain PEM Private Key
	 * @param STRING $privKeyPassword 				The password used to encrypt the PEM Private Key
	 *
	 * @return ARRAY 								[ err, privKey ]
	 */
	final public static function encryptPrivateKeyPem(string $plainPrivKeyPem, ?string $privKeyPassword) : array {
		//--
		$arr = [
			'err' 			=> '?',
			'privKey' 		=> '',
		];
		//--
		$err = (string) self::init();
		if($err) {
			$arr['err'] = (string) 'Failed: '.$err;
			return (array) $arr;
		} //end if
		//--
		if(self::isValidPrivateKeyPEM((string)$plainPrivKeyPem) !== true) {
			$arr['err'] = 'Private Key is Empty or Invalid';
			return (array) $arr;
		} //end if
		if(self::isValidPlainPrivateKeyPEM((string)$plainPrivKeyPem) !== true) {
			$arr['err'] = 'Private Key is Not Plain';
			return (array) $arr;
		} //end if
		//--
		if((string)$privKeyPassword == '') {
			$arr['err'] = 'PassPhrase is Empty';
			return (array) $arr;
		} //end if
		//--
		$pKey = openssl_pkey_get_private((string)$plainPrivKeyPem, null);
		if(!$pKey) {
			$arr['err'] = 'Failed to Extract the Plain Private Key';
			return (array) $arr;
		} //end if
		//--
		$options = [ // {{{SYNC-OPENSSL-ENC-PKEY-EXPORT-OPTIONS}}}
			'encrypt_key_cipher' => OPENSSL_CIPHER_AES_256_CBC,
		];
		$thePrivKey = '';
		if(!openssl_pkey_export($pKey, $thePrivKey, (string)$privKeyPassword, (array)$options)) {
			$arr['err'] = 'Failed to Export the Encrypted Private Key';
			return (array) $arr;
		} //end if
		$thePrivKey = (string) trim((string)$thePrivKey);
		if((string)$thePrivKey == '') {
			$arr['err'] = 'Encrypted Private Key PEM is Empty';
			return (array) $arr;
		} //end if
		if(self::isValidPrivateKeyPEM((string)$thePrivKey) !== true) {
			$arr['err'] = 'Encrypted Private Key PEM is Invalid';
			return (array) $arr;
		} //end if
		if(
			(
				(
					( // here we only support encrypted type
						(Smart::str_startswith((string)$thePrivKey, '-----BEGIN ENCRYPTED PRIVATE KEY-----'."\n") !== true)
						OR
						(Smart::str_endswith((string)$thePrivKey, "\n".'-----END ENCRYPTED PRIVATE KEY-----') !== true)
					)
					OR
					(Smart::str_contains((string)$thePrivKey, "\n".'M') !== true)
				)
			)
		) {
			$arr['err'] = 'Private Key is Not Plain';
			return (array) $arr;
		} //end if
		//--
		$arr['privKey'] = (string) $thePrivKey;
		//--
		$arr['err'] = ''; // clear
		return (array) $arr;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Validates a Private Key PEM format
	 * It does not validate the type, by example if it is EcDSA or RSA
	 * Supports: Plain or Encrypted Private Key PEM
	 * Plain     Private Key PEM, in base 64 format must be enclosed within: -----BEGIN PRIVATE KEY----- ... -----END PRIVATE KEY----- not having Proc-Type: * ; must be plain, unencrypted
	 * Encrypted Private Key PEM, in base 64 format must be enclosed within: -----BEGIN PRIVATE KEY----- ... -----END PRIVATE KEY----- and having Proc-Type: 4,ENCRYPTED in header
	 * or, alternate encrypted key as
	 * Encrypted Private Key PEM, in base 64 format must be enclosed within: -----BEGIN ENCRYPTED PRIVATE KEY----- ... -----END ENCRYPTED PRIVATE KEY-----
	 *
	 * @param STRING $thePrivKey 					The plain or encrypted Private Key PEM
	 *
	 * @return BOOL 								TRUE if Valid, FALSE if not valid or not supported
	 */
	final public static function isValidPrivateKeyPEM(?string $thePrivKey) : bool {
		//--
		$thePrivKey = (string) trim((string)$thePrivKey);
		if((string)$thePrivKey == '') {
			return false;
		} //end if
		//--
		if(
			(
				(
					( // can be encrypted or not, if encrypted will have to start with `Proc-Type:`
						(Smart::str_startswith((string)$thePrivKey, '-----BEGIN PRIVATE KEY-----'."\n") !== true)
						OR
						(Smart::str_endswith((string)$thePrivKey, "\n".'-----END PRIVATE KEY-----') !== true)
					)
					OR
					(
						(Smart::str_contains((string)$thePrivKey, "\n".'M') !== true)
						AND
						(Smart::str_contains((string)$thePrivKey, "\n".'Proc-Type: 4,ENCRYPTED') !== true)
					)
				)
				AND
				( // {{{SYNC-OPENSSL-VALD-ENCRYPTED-PRIVKEY}}}
					(
						(Smart::str_startswith((string)$thePrivKey, '-----BEGIN ENCRYPTED PRIVATE KEY-----'."\n") !== true)
						OR
						(Smart::str_endswith((string)$thePrivKey, "\n".'-----END ENCRYPTED PRIVATE KEY-----') !== true)
					)
					OR
					(
						(Smart::str_contains((string)$thePrivKey, "\n".'M') !== true)
						AND
						(Smart::str_contains((string)$thePrivKey, "\n".'Proc-Type: 4,ENCRYPTED') !== true)
					)
				)
			)
		) {
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Validates a Plain Private Key PEM format
	 * It does not validate the type, by example if it is EcDSA or RSA
	 * Supports: Plain Private Key PEM
	 * Plain     Private Key PEM, in base 64 format must be enclosed within: -----BEGIN PRIVATE KEY----- ... -----END PRIVATE KEY----- not having Proc-Type: * ; must be plain, unencrypted
	 *
	 * @param STRING $plainPrivKeyPem 				The plain Private Key PEM
	 *
	 * @return BOOL 								TRUE if Valid, FALSE if not valid or not supported
	 */
	final public static function isValidPlainPrivateKeyPEM(?string $plainPrivKeyPem) : bool {
		//--
		$plainPrivKeyPem = (string) trim((string)$plainPrivKeyPem);
		if((string)$plainPrivKeyPem == '') {
			return false;
		} //end if
		//--
		if(
			(
				(
					( // here we only support plain (unencrypted) type
						(Smart::str_startswith((string)$plainPrivKeyPem, '-----BEGIN PRIVATE KEY-----'."\n") !== true)
						OR
						(Smart::str_endswith((string)$plainPrivKeyPem, "\n".'-----END PRIVATE KEY-----') !== true)
					)
					OR
					(Smart::str_contains((string)$plainPrivKeyPem, "\n".'M') !== true)
				)
			)
		) {
			return false;
		} //end if
		//--
		return (bool) self::isValidPrivateKeyPEM((string)$plainPrivKeyPem); // safety check
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Validates an Encrypted Private Key PEM format
	 * It does not validate the type, by example if it is EcDSA or RSA
	 * Supports: Encrypted Private Key PEM
	 * Encrypted Private Key PEM, in base 64 format must be enclosed within: -----BEGIN PRIVATE KEY----- ... -----END PRIVATE KEY----- and having Proc-Type: 4,ENCRYPTED in header
	 * or, alternate encrypted key as
	 * Encrypted Private Key PEM, in base 64 format must be enclosed within: -----BEGIN ENCRYPTED PRIVATE KEY----- ... -----END ENCRYPTED PRIVATE KEY-----
	 *
	 * @param STRING $encryptedPrivKeyPem 			The encrypted Private Key PEM
	 *
	 * @return BOOL 								TRUE if Valid, FALSE if not valid or not supported
	 */
	final public static function isValidEncryptedPrivateKeyPEM(?string $encryptedPrivKeyPem) : bool {
		//--
		$encryptedPrivKeyPem = (string) trim((string)$encryptedPrivKeyPem);
		if((string)$encryptedPrivKeyPem == '') {
			return false;
		} //end if
		//--
		if(
			(
				(
					( // can be encrypted or not, if encrypted will have to start with `Proc-Type:`
						(Smart::str_startswith((string)$encryptedPrivKeyPem, '-----BEGIN PRIVATE KEY-----'."\n") !== true)
						OR
						(Smart::str_endswith((string)$encryptedPrivKeyPem, "\n".'-----END PRIVATE KEY-----') !== true)
					)
					OR
					(Smart::str_contains((string)$encryptedPrivKeyPem, "\n".'Proc-Type: 4,ENCRYPTED') !== true) // here we only support encrypted type, have to start with `Proc-Type:`
				)
				AND
				( // {{{SYNC-OPENSSL-VALD-ENCRYPTED-PRIVKEY}}}
					(
						(Smart::str_startswith((string)$encryptedPrivKeyPem, '-----BEGIN ENCRYPTED PRIVATE KEY-----'."\n") !== true)
						OR
						(Smart::str_endswith((string)$encryptedPrivKeyPem, "\n".'-----END ENCRYPTED PRIVATE KEY-----') !== true)
					)
					OR
					(
						(Smart::str_contains((string)$encryptedPrivKeyPem, "\n".'M') !== true)
						AND
						(Smart::str_contains((string)$encryptedPrivKeyPem, "\n".'Proc-Type: 4,ENCRYPTED') !== true)
					)
				)
			)
		) {
			return false;
		} //end if
		//--
		return (bool) self::isValidPrivateKeyPEM((string)$encryptedPrivKeyPem); // safety check
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Validates a Public Key PEM format
	 * It does not validate the type, by example if it is EcDSA or RSA
	 * Public Key PEM, in base 64 format must be enclosed within: -----BEGIN PUBLIC KEY----- ... -----END PUBLIC KEY-----
	 *
	 * @param STRING $thePubKey 					The Public Key PEM
	 *
	 * @return BOOL 								TRUE if Valid, FALSE if not valid or not supported
	 */
	final public static function isValidPublicKeyPEM(?string $thePubKey) : bool {
		//--
		$thePubKey = (string) trim((string)$thePubKey);
		if((string)$thePubKey == '') {
			return false;
		} //end if
		//--
		if(
			(Smart::str_startswith((string)$thePubKey, '-----BEGIN PUBLIC KEY-----'."\n") !== true)
			OR
			(Smart::str_endswith((string)$thePubKey, "\n".'-----END PUBLIC KEY-----') !== true)
			OR
			(Smart::str_contains((string)$thePubKey, "\n".'M') !== true)
		) {
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Validates a Certificate PEM format
	 * It does not validate the type, by example if it is EcDSA or RSA
	 * Certificate PEM, in base 64 format must be enclosed within: -----BEGIN CERTIFICATE----- ... -----END CERTIFICATE-----
	 *
	 * @param STRING $theCertPem 					The Certificate PEM
	 *
	 * @return BOOL 								TRUE if Valid, FALSE if not valid or not supported
	 */
	final public static function isValidCertificatePEM(?string $theCertPem) : bool {
		//--
		$theCertPem = (string) trim((string)$theCertPem);
		if((string)$theCertPem == '') {
			return false;
		} //end if
		//--
		if(
			(Smart::str_startswith((string)$theCertPem, '-----BEGIN CERTIFICATE-----'."\n") !== true)
			OR
			(Smart::str_endswith((string)$theCertPem, "\n".'-----END CERTIFICATE-----') !== true)
			OR
			(Smart::str_contains((string)$theCertPem, "\n".'M') !== true)
		) {
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//==============================================================


	//-------- [PROTECTED METHODS]: final


	//==============================================================
	final protected static function newSerialForCertificate() : string {
		//--
		$ser = (string) openssl_random_pseudo_bytes(16); // 16 * 8 = 128 bit
		//--
		return (string) bin2hex((string)$ser);
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	final protected static function init() : string {
		//--
		if(self::$initialized !== null) {
			return (string) self::$initialized;
		} //end if
		//--
		if(
			(!function_exists('openssl_get_curve_names')) // newest method from the PHP OpenSSL extension
			OR
			(!function_exists('openssl_random_pseudo_bytes'))
			OR
			(!function_exists('openssl_sign'))
			OR
			(!function_exists('openssl_verify'))
			OR
			(!function_exists('openssl_pkey_new'))
			OR
			(!function_exists('openssl_csr_new'))
			OR
			(!function_exists('openssl_csr_sign'))
			OR
			(!function_exists('openssl_x509_export'))
			OR
			(!function_exists('openssl_x509_parse'))
			OR
			(!function_exists('openssl_pkey_export'))
			OR
			(!function_exists('openssl_pkey_get_private'))
			OR
			(!function_exists('openssl_pkey_get_public'))
			OR
			(!function_exists('openssl_pkey_get_details'))
			OR
			(!defined('OPENSSL_KEYTYPE_EC'))
			OR
			(!defined('OPENSSL_CIPHER_AES_256_CBC'))
		) { // test req. methods and constants from PHP OpenSSL Extensions
			self::$initialized = 'Init: PHP OpenSSL Extension is missing';
			return (string) self::$initialized;
		} //end if
		//--
		if(SmartFileSysUtils::writeStaticFile((string)self::OPENSSL_CONF_PATH, (string)trim((string)self::OPENSSL_CONF_DATA)."\n"."\n"."\n"."\n") !== true) {
			self::$initialized = 'Init: Failed to write the OpenSSL config file';
			return (string) self::$initialized;
		} //end if
		//--
		self::$initialized = ''; // no errors
		//--
		return (string) self::$initialized;
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
 * Class: SmartCryptoEcdsaOpenSSL - provides PHP OpenSSL based EcDSA crypto implementation
 * Supports (standard): 	secp521r1/sha512   ; secp384r1/sha384   ; secp256k1/sha256
 * Supports (non-standard): secp521r1/sha3-512 ; secp384r1/sha3-384 ; secp256k1/sha3-256
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @depends 	extensions: PHP OpenSSL ; classes: Smart, SmartFileSysUtils, SmartCryptoEcdsaAsn1Sig
 *
 * @version 	v.20260116
 * @package     @Core:Crypto
 *
 */
final class SmartCryptoEcdsaOpenSSL
	extends SmartAbstractCryptoAsOpenSSL {

	// ::

	public const CURVE_P521 = 'secp521r1'; // NIST P-521
	public const CURVE_P384 = 'secp384r1'; // NIST P-384
	public const CURVE_P256 = 'secp256k1'; // upgraded NIST P-256

	public const ALGOS = [
		'sha3-512' => self::CURVE_P521,
		'sha512'   => self::CURVE_P521,
		'sha3-384' => self::CURVE_P384,
		'sha384'   => self::CURVE_P384,
		'sha3-256' => self::CURVE_P256,
		'sha256'   => self::CURVE_P256,
	];

	// Important: the standard in golang is as this: secp521r1/sha512 ; secp384r1/sha384 ; secp256k1/sha256 ; all other combinations are non-standard ...
	public const OPENSSL_ECDSA_DEF_CURVE = self::CURVE_P521; // 'secp521r1' | 'secp384r1' | 'secp256k1'


	//==============================================================
	/**
	 * Get the appropriate EcDSA Curve for the given Hash Algo
	 * Supported Algorithms: sha3-512, sha512, sha3-384, sha384, sha3-256, sha256
	 *
	 * @param STRING $algo 							Algorithm to be used for the Signature
	 *
	 * @return STRING 								One of the supported curves: 'secp521r1' | 'secp384r1' | 'secp256k1' ; if Algorithm is not supported will return an empty string
	 */
	public static function getCurveForAlgo(string $algo) : string {
		//--
		$algo = (string) trim((string)$algo);
		if((string)$algo == '') {
			return '';
		} //end if
		//--
		foreach(self::ALGOS as $key => $val) {
			if((string)$key == (string)$algo) {
				return (string) $val;
			} //end if
		} //end foreach
		//--
		return '';
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Create an EcDSA Digital Signature
	 * Supported Algorithms: sha3-512, sha512, sha3-384, sha384, sha3-256, sha256
	 *
	 * @param STRING $eccPrivKey 					The PEM Private Key (plain or encrypted)
	 * @param STRING $eccPubKey 					The PEM Public Key, required for verification
	 * @param STRING $data 							Data to be Signed
	 * @param STRING $algo 							Algorithm to be used for the Signature, must be compliant with the algorithm used by the used keys and certificate ; by example cannot sign using sha256 or sha3-256 with a sha512 or sha3-512 certificate private/public key because the hash size differs
	 * @param STRING $privKeyPassword 				*OPTIONAL* The password for the PEM Private Key if requires
	 * @param BOOL   $useASN1 						*OPTIONAL* by default is TRUE, will create an ASN1 compliant signature ; if set to FALSE will create a Raw (Non-ASN1) signature
	 *
	 * @return ARRAY 								[ err, algo, mode, signatureB64 ]
	 */
	public static function signData(?string $eccPrivKey, ?string $eccPubKey, ?string $data, string $algo=self::OPENSSL_SIGN_DEF_ALGO, ?string $privKeyPassword=null, bool $useASN1=true) : array {
		//--
		$arr = [
			'err' 			=> '?',
			'algo' 			=> (string) $algo,
			'mode' 			=> (string) (($useASN1 === false) ? self::SIGNATURE_MODE_NON_ASN1 : self::SIGNATURE_MODE_ASN1),
			'signatureB64' 	=> '',
		];
		//--
		$err = (string) self::init();
		if($err) {
			$arr['err'] = (string) 'Failed: '.$err;
			return (array) $arr;
		} //end if
		//--
		$hashLen = (int) 0; // {{{SYNC-X509-PADDING-ECDSA-REQ-LEN}}}
		switch((string)$algo) { // {{{SYNC-OPENSSL-SIGN-ALGO}}}
			case 'sha3-512':
			case 'sha512':
				$hashLen = (int) 66 * 2; // because algorithm differ, value is double than in golang
				break;
			case 'sha3-384':
			case 'sha384': // preferred ; wide compatibility and length attack safe
				$hashLen = (int) 48 * 2; // because algorithm differ, value is double than in golang
				break;
			case 'sha3-256':
			case 'sha256':
				$hashLen = (int) 32 * 2; // because algorithm differ, value is double than in golang
				break;
			default:
				$arr['err'] = 'Invalid EcDsa Sign Algo: `'.$algo.'`';
				return (array) $arr;
		} //end switch
		if((int)$hashLen <= 0) {
				$arr['err'] = 'Invalid EcDsa Hash Length: `'.$hashLen.'`';
				return (array) $arr;
		} //end if
		//--
		if(self::isValidPrivateKeyPEM((string)$eccPrivKey) !== true) {
			$arr['err'] = 'Private Key is Empty or Invalid';
			return (array) $arr;
		} //end if
		//--
		if(self::isValidPublicKeyPEM((string)$eccPubKey) !== true) {
			$arr['err'] = 'Public Key is Empty or Invalid';
			return (array) $arr;
		} //end if
		//--
		if((string)$privKeyPassword != '') {
			$arrDecrypt = (array) self::decryptPrivateKeyPem((string)$eccPrivKey, (string)$privKeyPassword);
			if((string)$arrDecrypt['err'] != '') {
				$arr['err'] = 'Private Key Decription Failed: '.$arrDecrypt['err'];
				return (array) $arr;
			} //end if
			$eccPrivKey = (string) ($arrDecrypt['privKey'] ?? null);
			if(self::isValidPrivateKeyPEM((string)$eccPrivKey) !== true) {
				$arr['err'] = 'Decrypted Private Key is Empty or Invalid';
				return (array) $arr;
			} //end if
		} //end if
		//--
		if((string)$data == '') {
			$arr['err'] = 'Data is Empty';
			return (array) $arr;
		} //end if
		//--
		$rawSignature = '';
		if(openssl_sign((string)$data, $rawSignature, (string)$eccPrivKey, (string)$algo) !== true) {
			$arr['err'] = 'Failed to Sign Data';
			return (array) $arr;
		} //end if
		if((string)$rawSignature == '') {
			$arr['err'] = 'Data Signature is Empty';
			return (array) $arr;
		} //end if
		$arr['signatureB64'] = (string) trim((string)Smart::b64_enc((string)$rawSignature));
		if((string)$arr['signatureB64'] == '') {
			$arr['err'] = 'Data B64 Signature is Empty';
			return (array) $arr;
		} //end if
		//--
		if($useASN1 === false) {
			$arrAsn1Conv = (array) SmartCryptoEcdsaAsn1Sig::fromAsn1((string)$arr['signatureB64'], (int)$hashLen);
			if((string)$arrAsn1Conv['err'] != '') {
				$arr['err'] = 'Non-ASN1 Signature Conversion Failed: '.$arrAsn1Conv['err'];
				return (array) $arr;
			} //end if
			$arr['signatureB64'] = (string) trim((string)($arrAsn1Conv['sig'] ?? null));
			if((string)$arr['signatureB64'] == '') {
				$arr['err'] = 'Non-ASN1 Data B64 Signature is Empty';
				return (array) $arr;
			} //end if
		} //end if
		//--
		$vfyArr = (array) self::verifySignedData((string)$eccPubKey, (string)$data, (string)$arr['signatureB64'], (string)$algo, (bool)$useASN1);
		if((string)$vfyArr['err'] != '') {
			$arr['err'] = 'Sign Verification Failed: '.$vfyArr['err'];
			return (array) $arr;
		} //end if
		//--
		$arr['err'] = ''; // clear
		return (array) $arr;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Verify an EcDSA Digital Signature
	 * Supported Algorithms: sha3-512, sha512, sha3-384, sha384, sha3-256, sha256
	 *
	 * @param STRING $eccPubKey 					The PEM Public Key or the Certificate PEM
	 * @param STRING $data 							Data to be Verified
	 * @param STRING $signatureB64 					The Base64 Signature
	 * @param STRING $algo 							Algorithm to be used for the Signature, must be compliant with the algorithm used by the used keys and certificate ; by example cannot sign using sha256 or sha3-256 with a sha512 or sha3-512 certificate private/public key because the hash size differs
	 * @param BOOL   $useASN1 						*OPTIONAL* by default is TRUE, will create an ASN1 compliant signature ; if set to FALSE will create a Raw (Non-ASN1) signature
	 *
	 * @return ARRAY 								[ err, algo, mode, verifyResult ] ; the verifyResult must be TRUE if verified, otherwise may return NULL or INTEGER
	 */
	public static function verifySignedData(?string $eccPubKey, ?string $data, ?string $signatureB64, string $algo=self::OPENSSL_SIGN_DEF_ALGO, bool $useASN1=true) : array {
		//--
		// $eccPubKey can be a PEM Public Key or a PEM Certificate
		//--
		$arr = [
			'err' 			=> '?',
			'algo' 			=> (string) $algo,
			'mode' 			=> (string) (($useASN1 === false) ? self::SIGNATURE_MODE_NON_ASN1 : self::SIGNATURE_MODE_ASN1),
			'verifyResult' 	=> null, // null (init); TRUE if verify = 1 ; otherwise verify can be INTEGER, NON-VALID: 0 / -1 ...
		];
		//--
		$err = (string) self::init();
		if($err) {
			$arr['err'] = (string) 'Failed: '.$err;
			return (array) $arr;
		} //end if
		//--
		$hashLen = (int) 0; // {{{SYNC-X509-PADDING-ECDSA-REQ-LEN}}}
		switch((string)$algo) { // {{{SYNC-OPENSSL-SIGN-ALGO}}}
			case 'sha3-512':
			case 'sha512':
				$hashLen = (int) 66 * 2; // because algorithm differ, value is double than in golang
				break;
			case 'sha3-384':
			case 'sha384': // preferred ; wide compatibility and length attack safe
				$hashLen = (int) 48 * 2; // because algorithm differ, value is double than in golang
				break;
			case 'sha3-256':
			case 'sha256':
				$hashLen = (int) 32 * 2; // because algorithm differ, value is double than in golang
				break;
			default:
				$arr['err'] = 'Invalid EcDsa Sign Algo: `'.$algo.'`';
				return (array) $arr;
		} //end switch
		if((int)$hashLen <= 0) {
				$arr['err'] = 'Invalid EcDsa Hash Length: `'.$hashLen.'`';
				return (array) $arr;
		} //end if
		//--
		if(
			(self::isValidPublicKeyPEM((string)$eccPubKey) !== true)
			AND
			(self::isValidCertificatePEM((string)$eccPubKey) !== true)
		) {
			$arr['err'] = 'Public Key or Certificate is Empty or Invalid';
			return (array) $arr;
		} //end if
		//--
		if((string)$data == '') {
			$arr['err'] = 'Data is Empty';
			return (array) $arr;
		} //end if
		//--
		$signatureB64 = (string) trim((string)$signatureB64);
		if((string)$signatureB64 == '') {
			$arr['err'] = 'Signed B64 Data is Empty';
			return (array) $arr;
		} //end if
		//--
		if($useASN1 === false) {
			$arrAsn1Conv = (array) SmartCryptoEcdsaAsn1Sig::toAsn1((string)$signatureB64, (int)$hashLen);
			if((string)$arrAsn1Conv['err'] != '') {
				$arr['err'] = 'Non-ASN1 Signature Conversion Failed: '.$arrAsn1Conv['err'];
				return (array) $arr;
			} //end if
			$signatureB64 = (string) trim((string)($arrAsn1Conv['sig'] ?? null));
			if((string)$signatureB64 == '') {
				$arr['err'] = 'Non-ASN1 Signed B64 Data is Empty';
				return (array) $arr;
			} //end if
		} //end if
		//--
		$rawSignature = (string) Smart::b64_dec((string)$signatureB64, true); // strict
		if((string)$rawSignature == '') {
			$arr['err'] = 'Signed Data is Empty';
			return (array) $arr;
		} //end if
		//--
		$signVerify = openssl_verify((string)$data, (string)$rawSignature, (string)$eccPubKey, (string)$algo);
		if($signVerify !== 1) { // 1 if the signature is correct, 0 if it is incorrect, and -1 or false on error
			$arr['verifyResult'] = (int) $signVerify;
			$arr['err'] = 'Signature Verification Failed';
			return (array) $arr;
		} //end if
		$arr['verifyResult'] = true; // if OK set to TRUE
		//--
		$arr['err'] = ''; // clear
		return (array) $arr;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Create a New EcDSA Certificate and the key Pair as Private and Public Key, all in PEM format
	 * Supported Algorithms: sha3-512, sha512, sha3-384, sha384, sha3-256, sha256
	 * Supported Curves: secp521r1, secp384r1, secp256k1
	 *
	 * @param ARRAY  $dNames 						The MetaInfo Certificate Names, minimum required is: [ commonName ] ; example: ['commonName' => 'My Sample Name', 'emailAddress' => 'my@email.local', 'organizationName' => 'my.local', 'organizationalUnitName' => 'My Sample Test - ECDSA Digital Signature']
	 * @param INT    $years 						The number of years for the certificate to be valid, 1..100
	 * @param STRING $method 						The EcDSA curve ; can use only: secp521r1 with sha3-256 or sha256 ; secp384r1 with sha3-384 or sha384 ; secp256k1 with sha3-256 or sha256 ; any other combinations are invalid due to the hash size required by the curve
	 * @param STRING $algo 							Algorithm to be used for the Signature, must be compliant with the algorithm used by the curve
	 * @param STRING $privKeyPassword 				*OPTIONAL* The password for the PEM Private Key ; if provided the returned Private Key PEM will be encrypted
	 *
	 * @return ARRAY 								[ err, mode, algo, curve, scheme, years, dNames, certificate, privKey, pubKey, serial, dateTime ]
	 */
	public static function newCertificate(array $dNames=['commonName' => 'localhost'], int $years=1, string $method=self::OPENSSL_ECDSA_DEF_CURVE, string $algo=self::OPENSSL_CSR_DEF_ALGO, ?string $privKeyPassword=null) : array {
		//--
		// IMPORTANT: returned CERTIFICATE, PRIVATE KEY, PUBLIC KEY are plain, not passphrase protected, must be protected if stored as ENCRYPTED !
		//--
		$serial = (string) self::newSerialForCertificate(); // hex
		//--
		$arr = [
			'err' 			=> '?',
			'mode' 			=> 'EcDSA', 			// TODO: in the future when PHP will have real support for Ed25519 / Ed448 add these curves too
			'algo' 			=> (string) $algo, 		// signature algo
			'curve' 		=> (string) $method, 	// for curves only, must be empty for non-curves
			'scheme' 		=> '', 					// N/A for curves, must be empty for curves, use for non-curves only such as RSA, Dilithium, Kyber, ...
			'years' 		=> (int)    $years,
			'dNames' 		=> (array)  $dNames,
			'certificate' 	=> '',
			'privKey' 		=> '',
			'pubKey' 		=> '',
			'serial' 		=> (string) '0x'.$serial,
			'dateTime' 		=> (string) date('Y-m-d H:i:s O'),
		];
		//--
		$err = (string) self::init();
		if($err) {
			$arr['err'] = (string) 'Failed: '.$err;
			return (array) $arr;
		} //end if
		//--
		if((int)Smart::array_size($dNames) <= 0) {
			$arr['err'] = 'D-Names are Empty';
			return (array) $arr;
		} //end if
		if((int)Smart::array_size($dNames) > 16) { // {{{SYNC-OPENSSL-DNAMES-MAX}}}
			$arr['err'] = 'D-Names are OverSized';
			return (array) $arr;
		} //end if
		if((int)Smart::array_type_test($dNames) != 2) { // associative
			$arr['err'] = 'D-Names have an Invalid Format'; // TODO, check keys ...
			return (array) $arr;
		} //end if
		//--
		if(((int)$years <= 0) OR ((int)$years > 100)) {
			$arr['err'] = 'Invalid Validity Years: `'.$years.'`';
			return (array) $arr;
		} //end if
		//--
		switch((string)$method) {
			case self::CURVE_P521: // preferred ; golang compatible
			case self::CURVE_P384: // golang compatible
			case self::CURVE_P256: // weak
				$availableCurves = openssl_get_curve_names();
				if(!is_array($availableCurves)) {
					$availableCurves = [];
				} //end if
				if(!in_array((string)$method, (array)$availableCurves)) {
					$arr['err'] = 'Unsupported EcDsa Curve: `'.$method.'`';
					return (array) $arr;
				} //end if
				break;
			default:
				$arr['err'] = 'Invalid EcDsa Curve: `'.$method.'`';
				return (array) $arr;
		} //end switch
		//--
		switch((string)$algo) { // {{{SYNC-OPENSSL-SIGN-ALGO}}}
			case 'sha3-512': // better
			case 'sha512': // preferred ; also compatible with golang
				break;
			case 'sha3-384':
			case 'sha384': // also compatible with golang
				break;
			case 'sha3-256':
			case 'sha256': // weak ; also compatible with golang
				break;
			default:
				$arr['err'] = 'Invalid EcDsa Digest Algo: `'.$algo.'`';
				return (array) $arr;
		} //end switch
		//--
		$dn = [];
		foreach(self::OPENSSL_DN_KEYS as $key => $val) {
			if($val === true) {
				$dn[(string)$key] = (string) trim((string)strval($dNames[(string)$key] ?? null));
			} else {
				if(isset($dNames[(string)$key])) {
					$dn[(string)$key] = (string) trim((string)strval($dNames[(string)$key] ?? null));
				} //end if
			} //end if else
		} //end foreach
		if((int)Smart::array_size($dn) <= 0) {
			$arr['err'] = 'Distinguished Name fields are empty';
			return (array) $arr;
		} //end if
		//--
		$private_key = openssl_pkey_new([
			'encrypt_key_cipher' 	=> OPENSSL_CIPHER_AES_256_CBC,
			'private_key_type' 		=> OPENSSL_KEYTYPE_EC,
			'curve_name' 			=> (string) $method,
		]);
		if(!$private_key) {
			$arr['err'] = 'Failed to Generate the New Private Key';
			return (array) $arr;
		} //end if
		//--
		$options_csr = (array) self::OPENSSL_CSR_OPTIONS;
		$options_csr['digest_alg'] = (string) $algo;
		//print_r($options_csr); die();
		//--
		$csr = openssl_csr_new((array)$dn, $private_key, $options_csr); // returns FALSE if fail ; TRUE if success but sign FAIL ; CSR on SUCCESS
		if(is_bool($csr)) {
			$arr['err'] = 'Failed to Generate the Certificate';
			return (array) $arr;
		} //end if
		//--
		$days   = (int) 365 * (int)$years;
		if((int)$years >= 4) {
			$modulo = (int) floor($years / 4);
			$days += (int) $modulo;
		} //end if
		//--
		$cacert = null;
		$x509 = openssl_csr_sign($csr, $cacert, $private_key, (int)$days, (array)$options_csr, 0, (string)$serial); // using hex serial not numeric serial
		if(!$x509) {
			$arr['err'] = 'Failed to Sign the Certificate';
			return (array) $arr;
		} //end if
		//--
		$eccCertPem = '';
		if(!openssl_x509_export($x509, $eccCertPem, true)) {
			$arr['err'] = 'Failed to Export the Certificate as X509';
			return (array) $arr;
		} //end if
		$eccCertPem = (string) trim((string)$eccCertPem);
		if((string)$eccCertPem == '') {
			$arr['err'] = 'Certificate PEM is Empty';
			return (array) $arr;
		} //end if
		if(self::isValidcertificatePEM((string)$eccCertPem) !== true) {
			$arr['err'] = 'Certificate PEM is Invalid';
			return (array) $arr;
		} //end if
		$arr['certificate'] = (string) $eccCertPem;
		//--
		$options = [ // {{{SYNC-OPENSSL-ENC-PKEY-EXPORT-OPTIONS}}}
			'encrypt_key_cipher' => OPENSSL_CIPHER_AES_256_CBC,
		];
		$eccPrivKey = '';
		if(!openssl_pkey_export($private_key, $eccPrivKey, $privKeyPassword, (array)$options)) {
			$arr['err'] = 'Failed to Export the Private Key';
			return (array) $arr;
		} //end if
		$eccPrivKey = (string) trim((string)$eccPrivKey);
		if((string)$eccPrivKey == '') {
			$arr['err'] = 'Private Key PEM is Empty';
			return (array) $arr;
		} //end if
		if(self::isValidPrivateKeyPEM((string)$eccPrivKey) !== true) {
			$arr['err'] = 'Private Key PEM is Invalid';
			return (array) $arr;
		} //end if
		$arr['privKey'] = (string) $eccPrivKey;
		//--
		$arrGetPubKey = (array) self::getPemPublicKeyFromPemPrivateKeyOrPemCertificate((string)$eccPrivKey, $privKeyPassword);
		if((string)$arrGetPubKey['err'] != '') {
			$arr['err'] = 'Failed to get the Public Key: '.$arrGetPubKey['err'];
			return (array) $arr;
		} //end if
		//--
		$arr['pubKey'] = (string) trim((string)($arrGetPubKey['pubKey'] ?? null));
		if((string)$arr['pubKey'] == '') {
			$arr['err'] = 'Failed to get the Public Key: Empty';
			return (array) $arr;
		} //end if
		//--
		$keyVersion = (string) trim((string)($arrGetPubKey['version'] ?? null));
		if((string)$keyVersion != '3') { // expects version 3 ; {{{SYNC-OPENSSL-V3}}}
			$arr['err'] = 'Invalid Private Key Version: `'.$keyVersion.'`';
			return (array) $arr;
		} //end if
		//--
		$arr['err'] = ''; // clear
		return (array) $arr;
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
// PHP implementation of the ASN1 conversions for EcDSA signature
// Class support fromAsn1/toAsn1 conversions of the EcDSA Signature
//
// LICENSE: MIT
// (c) 2014-2017 Spomky-Labs
//
// LICENSE: BSD
// (c) 2026-present unix-world.org
// Modified from by unixman (iradu@unix-world.org), uses GMP (can work with larger numbers than 64-bit) instead of the original non-safe approach using only 64-bit integers
// this class is a rework of the class: Jose\Component\Core\Util\ECSignature
// from the project: web-token/jwt-framework (JSON Object Signing and Encryption library for PHP and Symfony Bundle)
//--

/**
 * Class: SmartCryptoEcdsaAsn1Sig - provides PHP ASN1 conversions for EcDSA Signatures
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @access 		private
 * @internal
 *
 * @depends 	extensions: PHP GMP extension, classes: Smart
 *
 * @version 	v.20260116
 * @package     @Core:Crypto
 *
 */
final class SmartCryptoEcdsaAsn1Sig {

	private const ASN1_SEQUENCE = '30';
	private const ASN1_INTEGER = '02';
	private const ASN1_MAX_SINGLE_BYTE = 128;
	private const ASN1_LENGTH_2BYTES = '81';
	private const BYTE_SIZE = 2;

//	private const ASN1_NEGATIVE_INTEGER = '00';  // unixman
//	private const ASN1_BIG_INTEGER_LIMIT = '7f'; // unixman


	public static function toAsn1(string $signature, int $length) : array {
		//--
		$arr = [
			'err' => '?',
			'sig' => '',
		];
		//--
		$signature = (string) trim((string)$signature);
		if((string)$signature == '') {
			$arr['err'] = 'B64 Signature is Empty';
			return (array) $arr;
		} //end if
		$signature = (string) Smart::b64_dec((string)$signature, true); // strict
		if((string)$signature == '') {
			$arr['err'] = 'Signature is Empty';
			return (array) $arr;
		} //end if
		//--
		$signature = (string) bin2hex((string)$signature);
		//--
		$lenSgn = (int) self::octetLength((string)$signature);
		if((int)$lenSgn != (int)$length) {
			$arr['err'] = 'Invalid signature length, having: '.$lenSgn.', but expects: '.$length;
			return (array) $arr;
		} //end if
		//-- OK
		$pointR = (string) self::preparePositiveInteger((string)substr((string)$signature, 0, (int)$length));
		$pointS = (string) self::preparePositiveInteger((string)substr((string)$signature, (int)$length));
		if((string)$pointR == '') {
			$arr['err'] = 'R is empty';
			return (array) $arr;
		} //end if
		if((string)$pointS == '') {
			$arr['err'] = 'S is empty';
			return (array) $arr;
		} //end if
		//--
		$lengthR = (int) self::octetLength((string)$pointR);
		$lengthS = (int) self::octetLength((string)$pointS);
		//--
		$totalLength  = (int)    $lengthR + $lengthS + self::BYTE_SIZE + self::BYTE_SIZE;
		$lengthPrefix = (string) ($totalLength > self::ASN1_MAX_SINGLE_BYTE ? self::ASN1_LENGTH_2BYTES : '');
		//--
		$encodeHexToBin = (string) self::ASN1_SEQUENCE . $lengthPrefix . dechex($totalLength) . self::ASN1_INTEGER . dechex($lengthR) . $pointR . self::ASN1_INTEGER . dechex($lengthS) . $pointS;
		//--
		/*
		$bin = hex2bin((string)$encodeHexToBin); // DO NOT CAST to string, can return false, is checked below
		if(!is_string($bin)) {
			$arr['err'] = 'Unable to decode HEX data, is invalid';
			return (array) $arr;
		} //end if
		*/
		$bin = (string) Smart::safe_hex_2_bin(
			(string) $encodeHexToBin,
			false,   // do not ignore case
			true     // log notice if invalid
		);
		if((string)$bin == '') {
			$arr['err'] = 'Unable to decode HEX data, is invalid';
			return (array) $arr;
		} //end if

		//--
		$arr['sig'] = (string) Smart::b64_enc((string)$bin);
		//--
		$arr['err'] = ''; // clear
		return (array) $arr;
		//--
	} //END FUNCTION


	public static function fromAsn1(string $signature, int $length) : array {
		//--
		$arr = [
			'err' => '?',
			'sig' => '',
		];
		//--
		$signature = (string) trim((string)$signature);
		if((string)$signature == '') {
			$arr['err'] = 'B64 Signature is Empty';
			return (array) $arr;
		} //end if
		$signature = (string) Smart::b64_dec((string)$signature, true); // strict
		if((string)$signature == '') {
			$arr['err'] = 'Signature is Empty';
			return (array) $arr;
		} //end if
		//--
		$message = (string) bin2hex((string)$signature);
		//--
		$position = 0; // init ; DO NOT cast $position anywhere in this method, it is a value by REFERENCE, from the below lines (readAsn1Content) !
		//--
		$startSeq = (string) self::readAsn1Content((string)$message, $position, (int)self::BYTE_SIZE);
		//--
		if((string)$startSeq !== (string)self::ASN1_SEQUENCE) {
			$arr['err'] = 'Invalid data, the Sequence should starts with: `'.self::ASN1_SEQUENCE.'`, but starts with: `'.$startSeq.'`';
			return (array) $arr;
		} //end if
		//--
		if((string)self::readAsn1Content((string)$message, $position, (int)self::BYTE_SIZE) === (string)self::ASN1_LENGTH_2BYTES) {
			$position += (int) self::BYTE_SIZE;
		} //end if
		//--
		$r = self::readAsn1Integer((string)$message, $position); // do not cast, can return null on error, otherwise string
		if($r === null) {
			$arr['err'] = 'Invalid data for R part, should contain an integer';
			return (array) $arr;
		} //end if
		//--
		$s = self::readAsn1Integer((string)$message, $position); // do not cast, can return null on error, otherwise string
		if($s === null) {
			$arr['err'] = 'Invalid data for S part, should contain an integer';
			return (array) $arr;
		} //end if
		//--
		$pointR = (string) self::retrievePositiveInteger((string)$r);
		$pointS = (string) self::retrievePositiveInteger((string)$s);
		$encodeHexToBin = (string) str_pad((string)$pointR, (int)$length, '0', STR_PAD_LEFT) . str_pad((string)$pointS, (int)$length, '0', STR_PAD_LEFT);
		//--
		/*
		$bin = hex2bin((string)$encodeHexToBin); // DO NOT CAST to string, can return false, is checked below
		if(!is_string($bin)) {
			$arr['err'] = 'Unable to decode HEX data, is invalid';
			return (array) $arr;
		} //end if
		*/
		//--
		$bin = (string) Smart::safe_hex_2_bin(
			(string) $encodeHexToBin,
			false,   // do not ignore case
			true     // log notice if invalid
		);
		if((string)$bin == '') {
			$arr['err'] = 'Unable to decode HEX data, is invalid';
			return (array) $arr;
		} //end if
		//--
		$arr['sig'] = (string) Smart::b64_enc((string)$bin);
		//--
		$arr['err'] = ''; // clear
		return (array) $arr;
		//--
	} //END FUNCTION


	//-------- [PRIVATES]


	private static function octetLength(string $data) : int {
		//--
	//	return (int) ((int)strlen((string)$data) / (int)self::BYTE_SIZE);
		return (int) ceil((int)strlen((string)$data) / (int)self::BYTE_SIZE); // fix by unixman
		//--
	} //END FUNCTION


	private static function retrievePositiveInteger(string $data) : string {
		//--
		/* unixman
		while(!!str_starts_with((string)$data, (string)self::ASN1_NEGATIVE_INTEGER) && ((string)substr((string)$data, 2, self::BYTE_SIZE) > (string)self::ASN1_BIG_INTEGER_LIMIT)) {
			$data = (string) substr((string)$data, 2);
		} //end while
		//--
		return (string) $data;
		*/
		//--
		return (string) gmp_strval((string)'0x'.$data, 16);
		//--
	} //END FUNCTION


	private static function preparePositiveInteger(string $data) : string {
		//--
		/* unixman
		if((string)substr((string)$data, 0, (int)self::BYTE_SIZE) > (string)self::ASN1_BIG_INTEGER_LIMIT) {
			return (string) self::ASN1_NEGATIVE_INTEGER . $data;
		} //end if
		//--
		while(!!str_starts_with((string)$data, (string)self::ASN1_NEGATIVE_INTEGER) && ((string)substr((string)$data, 2, (int)self::BYTE_SIZE) <= (string)self::ASN1_BIG_INTEGER_LIMIT)) {
			$data = (string) substr((string)$data, 2);
		} //end while
		//--
		return (string) $data;
		*/
		//-- github.com/sop/asn1/lib/ASN1/Util/BigInt.php # _signedPositiveOctets()
		if((string)$data == '') {
			return '';
		} //end if
		$abs = (string) gmp_abs('0x'.$data);
		if((string)$abs == '') {
			return '';
		} //end if
		//--
		$exp = (string) gmp_export((string)$abs, 1, GMP_MSW_FIRST | GMP_BIG_ENDIAN);
		if((string)$exp == '') {
			return '';
		} //end if
		if(ord($exp[0]) & 0x80) {
            $exp = (string) chr(0x00) . $exp;
        } //end if
		//--
		return (string) bin2hex((string)$exp);
		//--
	} //END FUNCTION


	private static function readAsn1Content(string $message, int &$position, int $length) : string {
		//--
		// DO NOT cast $position anywhere in this method, it is a variable by REFERENCE !
		//--
		$content = (string) substr((string)$message, $position, (int)$length);
		//--
		$position += (int) $length;
		//--
		return (string) $content;
		//--
	} //END FUNCTION


	private static function readAsn1Integer(string $message, int &$position) : ?string {
		//--
		// DO NOT cast $position anywhere in this method, it is a variable by REFERENCE !
		//--
		if((string)self::readAsn1Content((string)$message, $position, (int)self::BYTE_SIZE) !== (string)self::ASN1_INTEGER) {
			return null; // invalid data, should contain an integer
		} //end if
		//--
		$length = (int) hexdec((string)self::readAsn1Content((string)$message, $position, (int)self::BYTE_SIZE));
		//--
		return (string) self::readAsn1Content($message, $position, (int)((int)$length * (int)self::BYTE_SIZE));
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================





// end of php code
