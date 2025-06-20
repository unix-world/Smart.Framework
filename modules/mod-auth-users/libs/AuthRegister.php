<?php
// PHP Auth Users Register for Smart.Framework
// Module Library
// (c) 2008-present unix-world.org - all rights reserved

// this class integrates with the default Smart.Framework modules autoloader so does not need anything else to be setup

namespace SmartModExtLib\AuthUsers;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: \SmartModExtLib\AuthUsers\AuthRegister
 * Auth Users Register
 *
 * @depends 	\SmartModExtLib\AuthUsers\Utils
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20250314
 * @package 	modules:AuthUsers
 *
 */
final class AuthRegister {

	// ::

	private const SWT_REG_ID = '0000000000.reg.0000000000'; // for the [I] area this ID would never conflict with an existing one, all the rest are 0000000000.0000000000 (21)
	private const SWT_EXPIRE = 60 * 60 * 24; // 24h
	private const REG_PRIV = 'auth-users:register';


	public static function isRegistrationRestrictedByIp() : bool {
		//--
		if(\defined('\\SMART_FRAMEWORK_MOD_AUTH_USERS_REGISTRATION_ALLOW_IPLIST') AND ((string)\trim((string)\SMART_FRAMEWORK_MOD_AUTH_USERS_REGISTRATION_ALLOW_IPLIST) !== '')) {
			return true;
		} //end if
		//--
		return false;
		//--
	} //END FUNCTION


	public static function isRegistrationAllowedFromClientIp() : bool {
		//--
		if(self::isRegistrationRestrictedByIp() === true) {
			if(\stripos((string)\SMART_FRAMEWORK_MOD_AUTH_USERS_REGISTRATION_ALLOW_IPLIST, '<'.\SmartUtils::get_ip_client().'>') === false) {
				return false;
			} //end if
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


	public static function buildUrlActivate(?string $token, ?string $hash='') : string {
		//--
		$token = (string) \trim((string)$token);
		$hash  = (string) \trim((string)$hash);
		//--
		return (string) \Smart::url_add_params(
			(string)\SmartModExtLib\AuthUsers\Utils::AUTH_USERS_URL_SIGNUP,
			[
				'hash'  => (string)$hash,
				'token' => (string)$token,
			],
			false
		);
		//--
	} //END FUNCTION


	public static function tokenCreate(string $email, string $pass) : array {
		//--
		$token = [
			'err' 	=> -1,
			'hash' 	=> '',
			'token' => '',
		];
		//--
		if((!\defined('\\SMART_FRAMEWORK_SECURITY_KEY')) OR ((string)\trim((string)\SMART_FRAMEWORK_SECURITY_KEY) == '')) {
			\Smart::log_warning(__METHOD__.' # SMART_FRAMEWORK_SECURITY_KEY is not defined or empty !');
			$token['err'] = 100;
			return (array) $token;
		} //end if
		//--
		$email = (string) \trim((string)$email);
		if( // {{{SYNC-AUTH-USERS-EMAIL-AS-USERNAME-SAFE-VALIDATION}}}
			((string)$email == '')
			OR
			((int)\strlen((string)$email) < 5)
			OR
			((int)\strlen((string)$email) > 72)
			OR
			(\strpos((string)$email, '@') == false)
			OR
			(\SmartAuth::validate_auth_ext_username((string)$email) !== true)
		) {
			$token['err'] = 101;
			return (array) $token;
		} //end if
		//--
		if((string)\trim((string)$pass) == '') {
			$token['err'] = 102;
			return (array) $token;
		} //end if
		if(\SmartAuth::validate_auth_password((string)$pass) !== true) {
			$token['err'] = 103;
			return (array) $token;
		} //end if
		//--
		$pass = (string) \SmartAuth::password_hash_create((string)$pass); // mandatory, use the irreversible pass hash for return
		//--
		$hash = (string) \Smart::b64_to_b64s((string)\SmartHashCrypto::sh3a256((string)\SmartHashCrypto::checksum((string)$email, (string)$pass), true), false); // b64u
		//--
		$swtPassHash = (string) \SmartHashCrypto::password((string)$hash, (string)self::SWT_REG_ID);
		//--
		$swt_token = (array) \SmartAuth::swt_token_create(
			'I',
			(string) self::SWT_REG_ID,
			(string) $swtPassHash,
			(int)    self::SWT_EXPIRE,
			[
			//	(string) \SmartUtils::get_ip_client(), // do not bind this token to the IP, it just activates the account ; if user used desktop to SignUp then receive the email on mobile and click from mobile, having a different IP will not work ...
			],
			[
				(string) self::REG_PRIV,
			]
		);
		if($swt_token['error'] !== '') {
			\Smart::log_warning(__METHOD__.' # SWT Token Creation Failed with ERROR: '.$swt_token['error']);
			$token['err'] = 110;
			return (array) $token;
		} //end if
		//--
		$swt_validate = (array) \SmartAuth::swt_token_validate((string)$swt_token['token'], (string)\SmartUtils::get_ip_client());
		if($swt_validate['error'] !== '') {
			\Smart::log_warning(__METHOD__.' # SWT Token Validation Failed with ERROR: '.$swt_validate['error']);
			$token['err'] = 111;
			return (array) $token;
		} //end if
		//--
		$date = (string) \date('Y-m-d H:i:s O');
		$data = [ // {{{SYNC-AUTH-USERS-REGISTER-DATA}}}
			'd' 	=> (string) $date, // date
			'e' 	=> (string) $email, // email
			'p' 	=> (string) $pass,  // pass hash
			't' 	=> (string) $swt_token['token'],
			'h' 	=> (string) \SmartHashCrypto::checksum((string)$date."\f".$email."\v".$pass, (string)$swt_token['token']), // checksum hash
		];
		//--
		$data = (string) \trim((string)\Smart::json_encode((array)$data, false, true, false, 1));
		if((string)$data == '') {
			\Smart::log_warning(__METHOD__.' # JSON Encoding Failed');
			$token['err'] = 120;
			return (array) $token;
		} //end if
		//--
		$data = (string) \trim((string)\SmartCipherCrypto::t3f_encrypt((string)$data, 'AuthUsers:Register:'."\r".\SMART_FRAMEWORK_SECURITY_KEY)); // {{{SYNC-USER-AUTH-REGISTER-CRYPTO-KEY}}}
		if((string)$data == '') {
			\Smart::log_warning(__METHOD__.' # Encryption Failed');
			$token['err'] = 121;
			return (array) $token;
		} //end if
		$data = (string) \trim((string)\substr((string)$data, (int)\strlen((string)\SmartCipherCrypto::SIGNATURE_3FISH_1K_V1_DEFAULT)));
		if((string)$data == '') {
			\Smart::log_warning(__METHOD__.' # Encryption Prefix Remove Failed');
			$token['err'] = 122;
			return (array) $token;
		} //end if
		//--
		$token['err'] = 0;
		$token['hash'] = (string) $hash;
		$token['token'] = (string) $data;
		//--
		return (array) $token;
		//--
	} //END FUNCTION


	public static function tokenValidate(string $data, ?string $hash=null) : array {
		//--
		$token = [
			'err' 	=> -1,
			'hash' 	=> '',
			'token' => [],
		];
		//--
		if((!\defined('\\SMART_FRAMEWORK_SECURITY_KEY')) OR ((string)\trim((string)\SMART_FRAMEWORK_SECURITY_KEY) == '')) {
			\Smart::log_warning(__METHOD__.' # SMART_FRAMEWORK_SECURITY_KEY is not defined or empty !');
			$token['err'] = 100;
			return (array) $token;
		} //end if
		//--
		if($hash !== null) {
			$hash = (string) \trim((string)$hash);
			if((string)$hash == '') {
				$token['err'] = 101;
				return (array) $token;
			} //end if
		} //end if
		//--
		$data = (string) \trim((string)$data);
		if((string)$data == '') {
			$token['err'] = 102;
			return (array) $token;
		} //end if
		//--
		$data = (string) \trim((string)\SmartCipherCrypto::t3f_decrypt((string)\SmartCipherCrypto::SIGNATURE_3FISH_1K_V1_DEFAULT.$data, 'AuthUsers:Register:'."\r".SMART_FRAMEWORK_SECURITY_KEY));
		if((string)$data == '') {
			$token['err'] = 103;
			return (array) $token;
		} //end if
		//--
		$data = \Smart::json_decode((string)$data, true, 1);
		if((int)\Smart::array_size($data) != 5) { // {{{SYNC-AUTH-USERS-REGISTER-DATA}}}
			$token['err'] = 104;
			return (array) $token;
		} //end if
		$keys = [ 'd', 'e', 'p', 't', 'h' ];
		for($i=0; $i<\count($keys); $i++) {
			if(!\array_key_exists((string)$keys[$i], (array)$data)) {
				$token['err'] = 105;
				return (array) $token;
			} //end if
			if(!\Smart::is_nscalar($data[(string)$keys[$i]])) {
				$token['err'] = 106;
				return (array) $token;
			} //end if
			$data[(string)$keys[$i]] = (string) \trim((string)$data[(string)$keys[$i]]);
			if((string)$data[(string)$keys[$i]] == '') {
				$token['err'] = 107;
				return (array) $token;
			} //end if
		} //end for
		//--
		if((string)\SmartHashCrypto::checksum((string)$data['d']."\f".$data['e']."\v".$data['p'], (string)$data['t']) != (string)$data['h']) {
			$token['err'] = 110;
			return (array) $token;
		} //end if
		//--
		$email = (string) \trim((string)$data['e']);
		if( // {{{SYNC-AUTH-USERS-EMAIL-AS-USERNAME-SAFE-VALIDATION}}}
			((string)$email == '')
			OR
			((int)\strlen((string)$email) < 5)
			OR
			((int)\strlen((string)$email) > 72)
			OR
			(\strpos((string)$email, '@') == false)
			OR
			(\SmartAuth::validate_auth_ext_username((string)$email) !== true)
		) {
			$token['err'] = 111;
			return (array) $token;
		} //end if
		//--
		if(
			((int)\strlen((string)$data['p']) != (int)\SmartAuth::PASSWORD_BHASH_LENGTH) // {{{SYNC-PASS-HASH-AUTH-LEN}}}
			OR
			(\SmartAuth::password_hash_validate_format((string)$data['p']) !== true) // {{{SYNC-PASS-HASH-AUTH-FORMAT}}}
		) {
			$token['err'] = 112;
			return (array) $token;
		} //end if
		//--
		$swt_validate = (array) \SmartAuth::swt_token_validate((string)$data['t'], (string)\SmartUtils::get_ip_client());
		if($swt_validate['error'] !== '') {
			\Smart::log_notice(__METHOD__.' # SWT Validation Failed: '.$swt_validate['error']);
			$token['err'] = 120;
			return (array) $token;
		} //end if
		$data['@swt'] = (array) $swt_validate;
		$swt_validate = null;
		//--
		if((string)$data['@swt']['user-name'] != (string)self::SWT_REG_ID) {
			$token['err'] = 121;
			return (array) $token;
		} //end if
		if((int)\Smart::array_size($data['@swt']['restr-priv']) <= 0) {
			$token['err'] = 122;
			return (array) $token;
		} //end if
		if(!\in_array((string)self::REG_PRIV, (array)$data['@swt']['restr-priv'])) {
			$token['err'] = 123;
			return (array) $token;
		} //end if
		//--
		if($hash !== null) {
			if((string)$data['@swt']['pass-hash'] != (string)\SmartHashCrypto::password((string)$hash, (string)self::SWT_REG_ID)) {
				$token['err'] = 178; // {{{SYNC-ACTIVATION-CODE-HASH-CHECK-CODE}}}
				return (array) $token;
			} //end if
		} //end if
		//--
		$token['err'] = 0;
		$token['hash'] = (string) $hash;
		$token['token'] = (array) $data;
		//--
		return (array) $token;
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
