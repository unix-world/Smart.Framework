<?php
// Class: \SmartModExtLib\AuthAdmins\AuthTokens
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

namespace SmartModExtLib\AuthAdmins;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

// [PHP8]


//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
//=====================================================================================


/**
 * Auth Tokens
 * This class provides Authentication support for (Opaque) Tokens
 *
 * (c) 2023-2024 unix-world.org
 * License: BSD
 *
 * @ignore
 *
 * @depends     classes: Smart, SmartAuth
 * @version 	v.20250207
 * @package 	development:modules:AuthAdmins
 *
 */
final class AuthTokens {

	// ::

	public const STK_VERSION_PREFIX 		= 'STK'; // {{{SYNC-AUTH-TOKEN-STK}}}
	public const STK_VERSION_SUFFIX 		= 'v1.2';
	public const STK_VERSION_SIGNATURE 		= 'stk:1.2';

	private const REGEX_VALID_TOKEN_SEED 	= '/^[0-9]{10}\-[0-9A-Za-z]{13}\-[0-9A-Z]{10}$/';


	public static function validateSTKEncData(string $id, string $token_hex, int $expires, string $token_data) : array {
		//--
		return (array) self::validateSTKData(
			(string) $id,
			(int)    $expires,
			(string) self::decryptSTKData((string)$id, (string)$token_hex, (string)$token_data, (string)self::createChecksum((string)$token_hex, (string)$token_data))
		);
		//--
	} //END FUNCTION


	public static function generatePrivateSeed() : string { // {{{SYNC-AUTH-ADMINS-TOKEN-SEED}}}
		//--
		return (string) \Smart::uuid_10_num().'-'.\Smart::uuid_13_seq().'-'.\Smart::uuid_10_str(); // 35 chars, fixed length
		//--
	} //END FUNCTION


	public static function createPublicPassKey(string $id, string $seed) : string {
		//--
		// on error, it may return an empty string ; returns a string 42..46 chars ; common expected length is 44 ; sha256.B58
		//--
		$id = (string) \trim((string)$id); // account
		if(
			((string)$id == '')
			OR
			(\SmartAuth::validate_auth_username((string)$id, true) !== true) // {{{SYNC-AUTH-VALIDATE-USERNAME}}} ; check for reasonable length, as 5 chars
		) {
			\Smart::log_warning(__METHOD__.' # Token ID is empty or invalid: `'.$id.'`');
			return '';
		} //end if
		//--
		$seed = (string) \trim((string)$seed);
		if(
			((string)$seed == '')
			OR
			((int)\strlen((string)$seed) != 35)
			OR
			(!\preg_match((string)self::REGEX_VALID_TOKEN_SEED, (string)$seed)) // regex validate ; {{{SYNC-AUTH-ADMINS-TOKEN-SEED}}}
		) {
			\Smart::log_warning(__METHOD__.' # Token Seed is empty or invalid: `'.$seed.'`');
			return '';
		} //end if
		//--
		$namespace = '';
		if(\defined('\\SMART_SOFTWARE_NAMESPACE')) {
			$namespace = (string) \SMART_SOFTWARE_NAMESPACE;
		} //end if
		if((string)\trim((string)$namespace) == '') {
			\Smart::log_warning(__METHOD__.' # Namespace is empty');
			return '';
		} //end if
		//--
		$secret = '';
		if(\defined('\\SMART_FRAMEWORK_SECURITY_KEY')) {
			$secret = (string) \SMART_FRAMEWORK_SECURITY_KEY;
		} //end if
		if((string)\trim((string)$secret) == '') {
			\Smart::log_warning(__METHOD__.' # Secret is empty');
			return '';
		} //end if
		//--
		$key = (string) \trim((string)\Smart::base_from_hex_convert((string)\SmartHashCrypto::sh3a256((string)$seed.'^'.$id.'@'.$namespace.'#'.$secret), 58));
		if((int)\strlen((string)$key) < 42) {
			\Smart::log_warning(__METHOD__.' # Token length is undersized');
			return '';
		} elseif((int)\strlen((string)$key) > 46) {
			\Smart::log_warning(__METHOD__.' # Token length is oversized');
			return '';
		} //end if
		//--
		return (string) $key;
		//--
	} //END FUNCTION


	public static function createHexHash(string $id, string $token_key) : string {
		//--
		return (string) \SmartHashCrypto::sh3a512((string)$token_key.\chr(0).\Smart::b64s_enc((string)$id)); // 128 hex {{{SYNC-STK-128BIT-HEXHASH}}}
		//--
	} //END FUNCTION


	public static function createChecksum(string $token_hex, string $enc_token) : string {
		//--
		$token_hex = (string) \trim((string)$token_hex);
		$enc_token = (string) \trim((string)$enc_token);
		//--
		if(
			((string)$token_hex == '')
			OR
			((int)\strlen((string)$token_hex) !== 128) // 128 hex
			OR
			(!\preg_match((string)\Smart::REGEX_SAFE_HEX_STR, (string)$token_hex)) // {{{SYNC-STK-128BIT-HEXHASH}}}
			OR
			((string)$enc_token == '')
		) {
			return '';
		} //end if
		//--
		return (string) \SmartHashCrypto::checksum((string)$enc_token, (string)\Smart::base_from_hex_convert((string)$token_hex, 85));
		//--
	} //END FUNCTION


	public static function validateSTKData(string $id, int $expires, string $token) : array {
		//--
		$valid = [
			'error' 		=> '?', 	// error or empty
			'ernum' 		=> 99, 		// error num or zero
			'auth-id' 		=> '',  	// auth id
			'expires' 		=> '',  	// expire time or zero
			'restr-priv' 	=> [], 		// restricted privileges array
			'seed' 			=> '',  	// token seed
			'key' 			=> '',  	// token key
		];
		//--
		if(!\defined('\\SMART_SOFTWARE_NAMESPACE')) {
			$valid['error'] = 'Auth Realm is Undefined';
			$valid['ernum'] = 88;
			return (array) $valid;
		} //end if
		if((string)\trim((string)\SMART_SOFTWARE_NAMESPACE) == '') {
			$valid['error'] = 'Auth Realm is Empty';
			$valid['ernum'] = 87;
			return (array) $valid;
		} //end if
		//--
		if(\SmartAuth::validate_auth_username((string)$id, false) !== true) {
			$valid['error'] = 'Auth ID is Invalid';
			$valid['ernum'] = 78;
			return (array) $valid;
		} //end if
		//--
		if((int)$expires < 0) {
			$valid['error'] = 'Token Expiration is Negative';
			$valid['ernum'] = 77;
			return (array) $valid;
		} //end if
		//--
		$token = (string) \trim((string)$token);
		if((string)$token == '') {
			$valid['error'] = 'Token is Empty';
			$valid['ernum'] = 68;
			return (array) $valid;
		} //end if
		$len_token = (int) \strlen((string)$token);
		if(((int)$len_token < 128) OR ((int)$len_token > 4096)) { // {{{SYNC-STK-AUTH-TOKEN-ALLOWED-LENGTH}}} ; token length is ~ 250 .. 600 characters, but be extremely flexible, just in case, this is stored in DB ...
			$valid['error'] = 'Token have an Invalid Length';
			$valid['ernum'] = 67;
			return (array) $valid;
		} //end if
		//--
		if(\strpos((string)$token, self::STK_VERSION_PREFIX.';') !== 0) {
			$valid['error'] = 'Token Prefix is Invalid';
			$valid['ernum'] = 66;
			return (array) $valid;
		} //end if
		$len_suffix = (int) ((int)\strlen((string)self::STK_VERSION_SUFFIX) + 1);
		if((string)\substr($token, -1 * (int)$len_suffix, (int)$len_suffix) !== (string)';'.self::STK_VERSION_SUFFIX) {
			$valid['error'] = 'Token Suffix is Invalid';
			$valid['ernum'] = 65;
			return (array) $valid;
		} //end if
		//--
		$tokarr = (array) \explode(';', (string)$token, 4);
		if((string)\trim((string)($tokarr[0] ?? null)) !== (string)self::STK_VERSION_PREFIX) {
			$valid['error'] = 'Token Prefix Part is Invalid';
			$valid['ernum'] = 64;
			return (array) $valid;
		} //end if
		if((string)\trim((string)($tokarr[3] ?? null)) !== (string)self::STK_VERSION_SUFFIX) {
			$valid['error'] = 'Token Suffix Part is Invalid';
			$valid['ernum'] = 63;
			return (array) $valid;
		} //end if
		$token = (string) \trim((string)($tokarr[1] ?? null));
		$tksum = (string) \trim((string)($tokarr[2] ?? null));
		$tokarr = null;
		if((string)$token == '') {
			$valid['error'] = 'Token Core Part is Invalid';
			$valid['ernum'] = 58;
			return (array) $valid;
		} //end if
		if((string)\SmartHashCrypto::checksum((string)self::STK_VERSION_PREFIX.';'.$token.';'.self::STK_VERSION_SUFFIX, '') !== (string)$tksum) {
			$valid['error'] = 'Token Core Checksum is Invalid';
			$valid['ernum'] = 57;
			return (array) $valid;
		} //end if
		$tksum = '';
		$json = (string) \Smart::b64s_dec((string)$token, true); // B64 STRICT
		$token = '';
		if((string)\trim((string)$json) == '') {
			$valid['error'] = 'Base64S decoding Failed';
			$valid['ernum'] = 56;
			return (array) $valid;
		} //end if
		//--
		$arr = \Smart::json_decode((string)$json);
		$json = '';
		if(!\is_array($arr)) {
			$valid['error'] = 'JSON decoding Failed';
			$valid['ernum'] = 55;
			return (array) $valid;
		} //end if
		if((int)\Smart::array_size($arr) != 6) {
			$valid['error'] = 'JSON object size is Invalid';
			$valid['ernum'] = 54;
			return (array) $valid;
		} //end if
		if((int)\Smart::array_type_test($arr) != 2) {
			$valid['error'] = 'JSON object type is Invalid';
			$valid['ernum'] = 53;
			return (array) $valid;
		} //end if
		//--
		$keys = [ '#', '@', '$', '^', '!', '$' ];
		$err = '';
		for($i=0; $i<\Smart::array_size($keys); $i++) {
			if(
				(\array_key_exists((string)$keys[$i], (array)$arr) !== true)
				OR
				!\is_string($arr[(string)$keys[$i]])
				OR
				((string)\trim((string)$arr[(string)$keys[$i]]) == '')
			) {
				$err = 'key `'.$keys[$i].'` is empty or invalid';
				break;
			} //end if
		} //end for
		if((string)$err != '') {
			$valid['error'] = 'JSON object validation: '.$err;
			$valid['ernum'] = 52;
			return (array) $valid;
		} //end if
		//--
		if((string)$arr['#'] !== (string)self::STK_VERSION_SIGNATURE) {
			$valid['error'] = 'JSON object have an Invalid Version Signature';
			$valid['ernum'] = 48;
			return (array) $valid;
		} //end if
		//--
		if(
			(\SmartAuth::validate_auth_username((string)$arr['@'], false) !== true)
			OR
			((string)$arr['@'] !== (string)$id)
		) {
			$valid['error'] = 'JSON object have an Invalid Auth ID';
			$valid['ernum'] = 47;
			return (array) $valid;
		} //end if
		//--
		if(
			((string)\trim((string)$arr['!']) == '')
			OR
			((string)$arr['!'] !== (string)$expires)
			OR
			((int)$arr['!'] !== (int)$expires)
		) {
			$valid['error'] = 'JSON object have an Invalid Expiration';
			$valid['ernum'] = 46;
			return (array) $valid;
		} //end if
		//--
		if( // {{{SYNC-STK-TOKEN-PRIVILEGES}}}
			((string)\trim((string)$arr['^']) == '')
			OR
			((int)\strlen((string)$arr['^']) > (int)(2*255)) // use double size of 255 as it may have <>,
		) {
			$valid['error'] = 'JSON object have an Empty or Too Long Privileges List';
			$valid['ernum'] = 45;
			return (array) $valid;
		} //end if
		if((string)$arr['^'] == '<*>') {
			//--
			$valid['restr-priv'] = ['*']; // explicit
			//--
		} else {
			//--
			$arr_privileges = (array) \SmartAuth::safe_arr_privileges_or_restrictions((string)$arr['^'], true);
			//--
			if((int)\Smart::array_size($arr_privileges) <= 0) {
				$valid['error'] = 'JSON object have an Invalid Privileges list';
				$valid['ernum'] = 44;
				return (array) $valid;
			} //end if
			$valid['restr-priv'] = (array) $arr_privileges;
			$arr_privileges = null;
		} //end if else
		//--
		if(
			((string)$arr['$'] == '')
			OR
			((int)\strlen((string)$arr['$']) != 35)
			OR
			(!\preg_match((string)self::REGEX_VALID_TOKEN_SEED, (string)$arr['$'])) // regex validate ; {{{SYNC-AUTH-ADMINS-TOKEN-SEED}}}
		) {
			$valid['error'] = 'JSON object have an Invalid Seed';
			$valid['ernum'] = 43;
			return (array) $valid;
		} //end if
		//--
		$hash = (string) \SmartHashCrypto::checksum(
			(string) self::STK_VERSION_SIGNATURE."\n".\SMART_SOFTWARE_NAMESPACE."\n".$arr['@']."\n".$arr['!']."\n".$arr['^']."\n".$arr['$'],
			'' // default (empty), will use a derivation of SMART_FRAMEWORK_SECURITY_KEY
		);
		if((string)$arr['='] != (string)$hash) {
			$valid['error'] = 'JSON object have an Invalid Checksum';
			$valid['ernum'] = 42;
			return (array) $valid;
		} //end if
		//--
		$valid['error'] 			= ''; // clear
		$valid['ernum'] 			= 0; // clear
		$valid['auth-id'] 			= (string) $arr['@']; // auth ID
		$valid['expires'] 			= (string) $arr['!']; // expires
		$valid['seed'] 				= (string) $arr['$']; // expires
		$valid['key'] 				= (string) self::createPublicPassKey((string)$valid['auth-id'], (string)$valid['seed']);
		//-- this check must be at the end after all checks and must include the above data
		if((int)$expires > 0) { // zero means no expiration ; {{{SYNC-STK-TOKEN-EXPIRATION}}}
			if((int)$expires < (int)\time()) {
				$valid['error'] = 'Token is Expired';
				$valid['ernum'] = 1; // sync with view
			} //end if
		} //end if
		//--
		return (array) $valid;
		//--
	} //END FUNCTION


	public static function createSTKData(string $id, int $expires, string $token_priv, string $seed) : array {
		//--
		$stk = [
			'error' => '?', // error or empty
			'ernum' => -99,
			'json'  => '',  // the stk json
			'token' => '',  // the stk token (b64s)
		];
		//--
		if(!\defined('\\SMART_SOFTWARE_NAMESPACE')) {
			$stk['error'] = 'Auth Realm is Undefined';
			$stk['ernum'] = -1;
			return (array) $stk;
		} //end if
		if((string)\trim((string)\SMART_SOFTWARE_NAMESPACE) == '') {
			$stk['error'] = 'Auth Realm is Empty';
			$stk['ernum'] = -2;
			return (array) $stk;
		} //end if
		//--
		if(\SmartAuth::validate_auth_username((string)$id, false) !== true) {
			$stk['error'] = 'Auth ID is Invalid';
			$stk['ernum'] = -3;
			return (array) $stk;
		} //end if
		//--
		if((int)$expires < 0) {
			$stk['error'] = 'Token Expiration is Negative';
			$stk['ernum'] = -5;
			return (array) $stk;
		} elseif((int)$expires > 0) { // zero means no expiration ; {{{SYNC-STK-TOKEN-EXPIRATION}}}
			if((int)$expires < (int)\time()) {
				$stk['error'] = 'Token Expiration is Invalid';
				$stk['ernum'] = -4;
				return (array) $stk;
			} //end if
		} //end if
		//--
		$token_priv = (string) \trim((string)$token_priv);
		if( // {{{SYNC-STK-TOKEN-PRIVILEGES}}}
			((string)$token_priv == '')
			OR
			((int)\strlen((string)$token_priv) > 255)
		) {
			$stk['error'] = 'Token Privileges List is Empty or Too Long';
			$stk['ernum'] = -6;
			return (array) $stk;
		} //end if else
		//--
		//-- {{{SYNC-STK-TOKEN-COMPOSE-PRIVILEGES}}}
		$privs_list = '';
		//--
		if((string)$token_priv == '*') {
			//--
			$privs_list = '<*>';
			//--
		} else {
			//--
			$privs_arr = (array) \SmartAuth::safe_arr_privileges_or_restrictions((array)\explode(',', (string)\str_replace([' ', "\t", "\r", "\n"], '', (string)\strtolower((string)$token_priv))), true);
			//--
			$valid_privs = [];
			//--
			if(\Smart::array_size($privs_arr) > 0) {
				foreach($privs_arr as $key => $val) {
					if(\Smart::is_nscalar($val)) {
						$val = (string) \trim((string)$val);
						if((string)$val != '') {
							if(\SmartAuth::validate_privilege_or_restriction_key((string)$val) === true) { // if valid privilege key name
								$valid_privs[] = (string) $val;
							} else {
								$stk['error'] = 'Privileges List is Invalid: Contains an Invalid Value: `'.$val.'`';
								$stk['ernum'] = -21;
								return (array) $stk;
							} //end if else
						} else {
							$stk['error'] = 'Privileges List is Invalid: Contains an Empty Value';
							$stk['ernum'] = -22;
							return (array) $stk;
						} //end if
					} else {
						$stk['error'] = 'Privileges List is Invalid: Contains a Non-Scalar Value';
						$stk['ernum'] = -23;
						return (array) $stk;
					} //end if
				} //end foreach
			} else {
				$stk['error'] = 'Privileges List is Invalid: Contain only Empty Values';
				$stk['ernum'] = -24;
				return (array) $stk;
			} //end if else
			//--
			if(\Smart::array_size($valid_privs) > 0) {
				$privs_list = (string) \str_replace(' ', '', (string)\Smart::array_to_list((array)$valid_privs));
			} else {
				$stk['error'] = 'Privileges List is Invalid: Contain only Non-Compliant Values';
				$stk['ernum'] = -25;
				return (array) $stk;
			} //end if else
			//--
			$valid_privs = null; // clear
			$privs_arr = null;
			//--
		} //end if else
		//--
		if((string)\trim((string)$privs_list) == '') {
			$stk['error'] = 'Privileges List is Empty';
			$stk['ernum'] = -26;
			return (array) $stk;
		} //end if
		//--
		if(
			((string)$seed == '')
			OR
			((int)\strlen((string)$seed) != 35)
			OR
			(!\preg_match((string)self::REGEX_VALID_TOKEN_SEED, (string)$seed)) // regex validate ; {{{SYNC-AUTH-ADMINS-TOKEN-SEED}}}
		) {
			$stk['error'] = 'Token Seed is Invalid';
			$stk['ernum'] = -7;
			return (array) $stk;
		} //end if
		//--
		$arr = [
			'#' => (string) self::STK_VERSION_SIGNATURE,
			'@' => (string) $id, // auth id
			'!' => (string) ((int)$expires), // expire max time, store as string in json to preserve large numbers as they are
			'^' => (string) $privs_list, // privileges list as: <priv-a>,<priv-b> ; cannot be empty ; max 255 chars ; {{{SYNC-STK-TOKEN-PRIVILEGES}}}
			'$' => (string) $seed, // token seed
			'=' => (string) \SmartHashCrypto::checksum(
				(string) self::STK_VERSION_SIGNATURE."\n".\SMART_SOFTWARE_NAMESPACE."\n".$id."\n".$expires."\n".$privs_list."\n".$seed,
				'' // default (empty), will use a derivation of SMART_FRAMEWORK_SECURITY_KEY
			)
		];
		//--
		$json = (string) \Smart::json_encode((array)$arr, false, true, false);
		if((string)\trim((string)$json) == '') {
			$stk['error'] = 'JSON encoding Failed';
			$stk['ernum'] = -8;
			return (array) $stk;
		} //end if
		//--
		$b64s = (string) \Smart::b64s_enc((string)$json);
		if((string)\trim((string)$b64s) == '') {
			$stk['error'] = 'Base64S encoding Failed';
			$stk['ernum'] = -9;
			return (array) $stk;
		} //end if
		//--
		$cksign = (string) \SmartHashCrypto::checksum(
			(string) self::STK_VERSION_PREFIX.';'.$b64s.';'.self::STK_VERSION_SUFFIX,
			'' // default (empty), will use a derivation of SMART_FRAMEWORK_SECURITY_KEY
		);
		//--
		$stk['error'] = ''; // clear
		$stk['ernum'] = 0; // clear
		$stk['json']  = (string) $json;
		$stk['token'] = (string) self::STK_VERSION_PREFIX.';'.$b64s.';'.$cksign.';'.self::STK_VERSION_SUFFIX;
		return (array) $stk;
		//--
	} //END FUNCTION


	public static function encryptSTKData(string $id, string $token_hex, string $b64s_token) : string {
		//--
		$id = (string) \trim((string)$id);
		$token_hex = (string) \trim((string)$token_hex);
		$b64s_token = (string) \trim((string)$b64s_token);
		//--
		if(
			((string)$id == '')
			OR
			((string)$token_hex == '')
			OR
			((int)\strlen((string)$token_hex) !== 128)
			OR
			(!\preg_match((string)\Smart::REGEX_SAFE_HEX_STR, (string)$token_hex))
			OR
			((string)$b64s_token == '')
		) {
			return '';
		} //end if
		//--
		$ek = (string) \Smart::base_from_hex_convert((string)$token_hex, 92);
		if((string)\trim((string)$ek) == '') {
			return '';
		} //end if
		//--
		$hashpass = (string) \SmartHashCrypto::hmac('sha3-512', (string)$id, (string)$ek, false); // hex
		if((string)\trim((string)$hashpass) == '') {
			return '';
		} //end if
		$hashpass = (string) \Smart::base_from_hex_convert((string)$hashpass, 92);
		if((string)\trim((string)$hashpass) == '') {
			return '';
		} //end if
		//--
		$secret = '';
		if(\defined('\\SMART_FRAMEWORK_SECURITY_KEY')) {
			$secret = (string) \SMART_FRAMEWORK_SECURITY_KEY;
		} //end if
		if((string)\trim((string)$secret) == '') {
			\Smart::log_warning(__METHOD__.' # Secret is empty');
			return '';
		} //end if
		//--
		return (string) \SmartAuth::encrypt_sensitive_data(
			(string) $b64s_token,
			(string) $hashpass.\chr(0).$secret
		);
		//--
	} //END FUNCTION


	public static function decryptSTKData(string $id, string $token_hex, string $enc_token, string $checksum) : string {
		//--
		$id = (string) \trim((string)$id);
		$token_hex = (string) \trim((string)$token_hex);
		$enc_token = (string) \trim((string)$enc_token);
		$checksum = (string) \trim((string)$checksum);
		//--
		if(
			((string)$id == '')
			OR
			((string)$token_hex == '')
			OR
			((int)\strlen((string)$token_hex) !== 128)
			OR
			(!\preg_match((string)\Smart::REGEX_SAFE_HEX_STR, (string)$token_hex))
			OR
			((string)$enc_token == '')
			OR
			((string)$checksum == '')
			OR
			((string)$checksum != (string)self::createChecksum((string)$token_hex, (string)$enc_token))
		) {
			return '';
		} //end if
		//--
		$ek = (string) \Smart::base_from_hex_convert((string)$token_hex, 92);
		if((string)\trim((string)$ek) == '') {
			return '';
		} //end if
		//--
		$hashpass = (string) \SmartHashCrypto::hmac('sha3-512', (string)$id, (string)$ek, false); // hex
		if((string)\trim((string)$hashpass) == '') {
			return '';
		} //end if
		$hashpass = (string) \Smart::base_from_hex_convert((string)$hashpass, 92);
		if((string)\trim((string)$hashpass) == '') {
			return '';
		} //end if
		//--
		$secret = '';
		if(\defined('\\SMART_FRAMEWORK_SECURITY_KEY')) {
			$secret = (string) \SMART_FRAMEWORK_SECURITY_KEY;
		} //end if
		if((string)\trim((string)$secret) == '') {
			\Smart::log_warning(__METHOD__.' # Secret is empty');
			return '';
		} //end if
		//--
		return (string) \SmartAuth::decrypt_sensitive_data( // b64s token
			(string) $enc_token,
			(string) $hashpass.\chr(0).$secret
		);
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
