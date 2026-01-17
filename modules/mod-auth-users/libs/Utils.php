<?php
// PHP Auth Users Utils for Smart.Framework
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

//-- set in: config.php
//define('SMART_AUTHUSERS_DB_TYPE', 'sqlite');
//or
//define('SMART_AUTHUSERS_DB_TYPE', 'pgsql');
//-- set in: config-index.php
//define('SMART_FRAMEWORK_CUSTOM_ERR_PAGE_401', 'modules/mod-auth-users/error-pages/'); // optional, register a custom 401 handler by mod auth users
//define('SMART_AUTHUSERS_FAIL_EXTLOG', true); // optional, if set will log to 'tmp/logs/idx/' all the ExtAuth Fails
//--


/**
 * Class: \SmartModExtLib\AuthUsers\Utils
 * Auth Users Utils
 *
 * @depends 	\SmartCryptoEddsaSodium
 * @depends 	\SmartCryptoEcdsaOpenSSL
 *
 * @depends 	\SmartModExtLib\AuthUsers\AuthCsrf
 * @depends 	\SmartModExtLib\AuthUsers\Auth2FA
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20260115
 * @package 	modules:AuthUsers
 *
 */
final class Utils {

	// ::

	public const AUTH_USERS_AREA 					= 'SMART-USERS-AREA';
	public const AUTH_USERS_PAGE_SIGNIN 			= 'auth-users.signin';
	public const AUTH_USERS_URL_SIGNIN 				= '?page=auth-users.signin';
	public const AUTH_USERS_URL_SIGNUP 				= '?page=auth-users.signup';
	public const AUTH_USERS_URL_SIGNOUT 			= '?page=auth-users.signout';
	public const AUTH_USERS_URL_ACCOUNT 			= '?page=auth-users.account';
	public const AUTH_USERS_URL_APPS 				= '?page=auth-users.apps';

	public const AUTH_USERS_RDR_COOKIE_NAME 		= 'Sf_SignRdr';

	private const AUTH_2FA_REGEX_TOKEN 				= '/^[0-9]{8}$/';
	private const AUTH_USERS_REGISTER_CAPTCHA_NAME 	= 'AuthUsers-Captcha';

	private const AUTH_OTC_REGEX_PASSCODE 			= '/^\([0-9]{10}\)\#\[[0-9A-Z]{10}\]$/'; // ex: (0123456789)#[AB3DEF78WZ]

	private const DIGICERT_TEXT 					= 'User Account / Digital Signature: EC';



	//---- user auth Logging


	public static function logFailedExtAuth(string $action, int $status, string $message) : bool {
		//--
		$action = (string) \trim((string)$action);
		if((string)$action == '') {
			return false;
		} //end if
		if(((int)$status < 100) || ((int)$status > 999)) {
			return false;
		} //end if
		//--
		if(!\defined('\\SMART_AUTHUSERS_FAIL_EXTLOG')) {
			return true; // disabled, undefined
		} //end if
		if(\SMART_AUTHUSERS_FAIL_EXTLOG !== true) {
			return true; // disabled, explicit
		} //end if
		//--
		$message = (string) \trim((string)$message); // can be empty, by ex an empty http response with just a status code
		\Smart::log_info('AuthUsers :: FailedExtAuth # '.(int)$status.' :: '.$action, (string)$message);
		//--
		return true;
		//--
	} //END FUNCTION


	//---- user auth Validate Request URL


	public static function isValidRequestUri() : bool {
		//--
		// Disallow use of pretty URLs because of too complicate and various rules
		//--
		if(
			(\strpos((string)\SmartUtils::get_server_current_request_uri(), '/?page=') === false) // auth users
			AND
			(\strpos((string)\SmartUtils::get_server_current_request_uri(), '/index.php?page=') === false) // auth users
			AND
			(\strpos((string)\SmartUtils::get_server_current_request_uri(), '/?/page/') === false) // auth users ext
			AND
			(\strpos((string)\SmartUtils::get_server_current_request_uri(), '/index.php?/page/') === false) // auth users ext
		) {
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


	//---- user auth Redir Cookie


	public static function setRedirUrlCookie(string $obfsUrl) : void {
		//--
		$obfsUrl = (string) \trim((string)$obfsUrl);
		if((string)$obfsUrl == '') {
			return;
		} //end if
		//--
		if((string)\trim((string)\SmartUtils::url_obfs_decrypt((string)$obfsUrl)) == '') {
			return;
		} //end if
		//--
		\SmartUtils::set_cookie((string)self::AUTH_USERS_RDR_COOKIE_NAME, (string)$obfsUrl, 300); // force expire after 300 seconds in the case the headers are overflow, this is not important ; plus no need to manage it on clearing
		//--
	} //END FUNCTION


	public static function getRedirUrlCookie() : string {
		//--
		$obfsUrl = (string) \trim((string)\SmartUtils::get_cookie((string)self::AUTH_USERS_RDR_COOKIE_NAME));
		if((string)$obfsUrl == '') {
			return '';
		} //end if
		//--
		$obfsUrl = (string) \trim((string)\SmartUtils::url_obfs_decrypt((string)$obfsUrl));
		if((string)$obfsUrl == '') {
			return '';
		} //end if
		//--
		if(\strpos((string)$obfsUrl, '/') !== false) { // disallow path ; must be either: ?query=val& | test.html | mixed
			return '';
		} //end if
		//--
		if(!\preg_match((string)\Smart::REGEX_ASCII_NOSPACE_CHARACTERS, (string)$obfsUrl)) { // mime types are only ISO-8859-1
			return '';
		} //end if
		//--
		return (string) $obfsUrl;
		//--
	} //END FUNCTION


	public static function unsetRedirUrlCookie() : void {
		//--
		\SmartUtils::unset_cookie((string)self::AUTH_USERS_RDR_COOKIE_NAME);
		//--
	} //END FUNCTION


	//---- user auth CSRF


	public static function setCsrfCookie() : string {
		//--
		$csrfPrivKey = (string) \SmartModExtLib\AuthUsers\AuthCsrf::csrfNewPrivateKey().'#Auth:Users';
		$csrfPubKey  = (string) \SmartModExtLib\AuthUsers\AuthCsrf::csrfPublicKey((string)$csrfPrivKey); // state
		//--
		$csrfSafety = (string) \Smart::b64_to_b64s((string)\SmartHashCrypto::sh3a512('AuthUsers:CSRF'.\SmartUtils::get_visitor_tracking_uid()."\v".$csrfPrivKey."\f".$csrfPubKey."\r".\SMART_FRAMEWORK_SECURITY_KEY));
		$csrfSafety = (string) \SmartHashCrypto::crc32b((string)$csrfSafety).'.'.\SmartHashCrypto::crc32b((string)\strrev((string)$csrfSafety));
		//--
		$arr = [
			'u' => (string) $csrfSafety,
			'b' => (string) $csrfPubKey,  // pb
			'v' => (string) $csrfPrivKey, // pv
		];
		//--
		$csrfCk = (string) \Smart::json_encode((array)$arr, false, true, false);
		$csrfCk = (string) \SmartModExtLib\AuthUsers\AuthCsrf::csrfPrivateKeyEncrypt((string)$csrfCk);
		//--
		if(\SmartUtils::set_cookie((string)\SmartModExtLib\AuthUsers\AuthCsrf::AUTH_USERS_COOKIE_NAME_CSRF, (string)$csrfCk, 0, '/', '@', '@', false, true) === true) {
			return (string) $csrfPubKey;
		} //end if
		//--
		return '';
		//--
	} //END FUNCTION


	public static function getCsrfCookie() : array {
		//--
		$csrf = '';
		if(self::isSetCsrfCookie() === true) {
			$csrf = (string) \trim((string)\SmartUtils::get_cookie((string)\SmartModExtLib\AuthUsers\AuthCsrf::AUTH_USERS_COOKIE_NAME_CSRF));
		} //end if
		//--
		if((string)$csrf != '') {
			$csrf = (string) \trim((string)\SmartModExtLib\AuthUsers\AuthCsrf::csrfPrivateKeyDecrypt((string)$csrf));
		} //end if
		//--
		if((string)$csrf != '') {
			$csrf = \Smart::json_decode((string)$csrf);
		} //end if else
		if(!\is_array($csrf)) {
			$csrf = [];
		} //end if
		//--
		$arr = [
			'b' => (string) ($csrf['b'] ?? null), // pb
			'v' => (string) ($csrf['v'] ?? null), // pv
		];
		//--
		$csrfSafety = (string) \Smart::b64_to_b64s((string)\SmartHashCrypto::sh3a512('AuthUsers:CSRF'.\SmartUtils::get_visitor_tracking_uid()."\v".$arr['v']."\f".$arr['b']."\r".\SMART_FRAMEWORK_SECURITY_KEY));
		$csrfSafety = (string) \SmartHashCrypto::crc32b((string)$csrfSafety).'.'.\SmartHashCrypto::crc32b((string)\strrev((string)$csrfSafety));
		//--
		if((string)($csrf['u'] ?? null) !== (string)$csrfSafety) {
			$arr = [];
		} //end if
		//--
		return [
			'b' => (string) ($arr['b'] ?? null), // pb
			'v' => (string) ($arr['v'] ?? null), // pv
		];
		//--
	} //END FUNCTION


	public static function isValidCsrfCookie() : bool {
		//--
		if(self::isSetCsrfCookie() !== true) {
			return false;
		} //end if
		//--
		$csrf = (array) self::getCsrfCookie();
		//--
		return (bool) \SmartModExtLib\AuthUsers\AuthCsrf::csrfCheckState((string)($csrf['b'] ?? null), (string)($csrf['v'] ?? null));
		//--
	} //END FUNCTION


	public static function isSetCsrfCookie() : bool {
		//--
		return (bool) \SmartUtils::isset_cookie((string)\SmartModExtLib\AuthUsers\AuthCsrf::AUTH_USERS_COOKIE_NAME_CSRF);
		//--
	} //END FUNCTION


	public static function unsetCsrfCookie() : bool {
		//--
		return (bool) \SmartUtils::unset_cookie((string)\SmartModExtLib\AuthUsers\AuthCsrf::AUTH_USERS_COOKIE_NAME_CSRF);
		//--
	} //END FUNCTION


	//---- user auth Captcha


	public static function isAuthLoginCaptchaEnabled() : bool {
		//--
		if(\defined('\\SMART_AUTH_USERS_ENABLE_CAPTCHA_ON_LOGIN')) {
			if(\SMART_AUTH_USERS_ENABLE_CAPTCHA_ON_LOGIN === true) { // for login, if captcha must be explicit enabled, it is disabled by default
				return true;
			} //end if
		} //end if
		//--
		return false;
		//--
	} //END FUNCTION


	public static function isAuthRegisterCaptchaEnabled() : bool {
		//--
		if(\defined('\\SMART_AUTH_USERS_DISABLE_CAPTCHA_ON_REGISTER')) {
			if(\SMART_AUTH_USERS_DISABLE_CAPTCHA_ON_REGISTER === true) { // for register, captcha must be explicit disabled, it is enabled by default
				return false;
			} //end if
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


	public static function isAuthRecoveryCaptchaEnabled() : bool {
		//--
		if(\defined('\\SMART_AUTH_USERS_DISABLE_CAPTCHA_ON_RECOVERY')) {
			if(\SMART_AUTH_USERS_DISABLE_CAPTCHA_ON_RECOVERY === true) { // for recovery, captcha must be explicit disabled, it is enabled by default
				return false;
			} //end if
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


	public static function drawAuthUsersCaptchaHtml() : string {
		//--
		if(self::isAuthRegisterCaptchaEnabled() === false) {
			return '';
		} //end if
		//--
		return (string) \SmartCaptcha::drawCaptchaForm((string)self::AUTH_USERS_REGISTER_CAPTCHA_NAME);
		//--
	} //END FUNCTION


	public static function verifyAuthUsersCaptchaHtml() : bool {
		//--
		return (bool) \SmartCaptcha::verifyCaptcha((string)self::AUTH_USERS_REGISTER_CAPTCHA_NAME, false); // do not clear on success, clear must be called explicit !
		//--
	} //END FUNCTION


	public static function clearAuthUsersCaptchaHtml() : bool {
		//--
		return (bool) \SmartCaptcha::clearCaptcha((string)self::AUTH_USERS_REGISTER_CAPTCHA_NAME);
		//--
	} //END FUNCTION


	//---- user auth password


	public static function verifyPassword(string $username, string $plainPassword, int $passwordAlgo, string $passwordHash) : bool {
		//--
		$username = (string) \strtolower((string)\trim((string)$username));
		if((string)$username == '') {
			return false; // username is empty
		} //end if
		if(\SmartAuth::validate_auth_ext_username((string)$username) !== true) {
			return false; // username is invalid
		} //end if
		//--
		if((string)\trim((string)$plainPassword) == '') { // do not trim outside, must preserve password as it is
			return false; // password cannot be empty or all just spacing characters
		} //end if
		if(\SmartAuth::validate_auth_password((string)$plainPassword) !== true) {
			return false; // plain password is invalid
		} //end if
		//--
		$passwordAlgo = (int) $passwordAlgo;
		if(((int)$passwordAlgo < 0) || ((int)$passwordAlgo > 255)) {
			return false; // password algo is uint8, must be between 0..255
		} //end if
		//--
		$passwordHash = (string) \trim((string)$passwordHash);
		if((string)$passwordHash == '') {
			return false; // password hash is empty
		} //end if
		//--
		switch((int)$passwordAlgo) { // {{{SYNC-ALLOWED-PASS-ALGOS}}}
			case \SmartAuth::ALGO_PASS_NONE:
				//--
				return false; // not supported
				//--
				break;
			case \SmartAuth::ALGO_PASS_PLAIN:
				//--
				if(\defined('\\SMART_FRAMEWORK_AUTH_USERS_ALLOW_UNSAFE_PASSWORDS') AND (SMART_FRAMEWORK_AUTH_USERS_ALLOW_UNSAFE_PASSWORDS === true)) {
					if(
						((string)$plainPassword == (string)$passwordHash)
						AND
						((string)\SmartHashCrypto::sh3a512((string)$plainPassword) == (string)\SmartHashCrypto::sh3a512((string)$passwordHash))
						AND
						((string)\SmartHashCrypto::sha384((string)$plainPassword) == (string)\SmartHashCrypto::sha384((string)$passwordHash))
					) {
						return true;
					}
				} //end if
				//--
				return false; // unsafe !!
				//--
				break;
			case \SmartAuth::ALGO_PASS_SMART_SAFE_SF_PASS:
				//--
				if(\SmartHashCrypto::validatepasshashformat((string)$passwordHash) !== true) {
					return false; // invalid SF Pass Hash format
				} //end if
				//--
				$isValid = \SmartHashCrypto::checkpassword((string)$plainPassword, (string)$passwordHash, (string)$username);
				if($isValid === true) {
					return true; // OK: password is valid, verified
				} //end if
				//--
				return false; // password is invalid
				//--
				break;
			case \SmartAuth::ALGO_PASS_SMART_SAFE_ARGON_PASS:
				//--
				return false; // currently unsupported in PHP
				//--
				break;
			case \SmartAuth::ALGO_PASS_SMART_SAFE_BCRYPT:
				//--
				if(\SmartAuth::password_hash_validate_format((string)$passwordHash) !== true) {
					return false; // invalid BCrypt Pass Hash format
				} //end if
				//--
				$isValid = \SmartAuth::password_hash_check((string)$plainPassword, (string)$passwordHash); // do not cast to boolean, will check below, must be === TRUE
				if($isValid === true) {
					return true; // OK: password is valid, verified
				} //end if
				//--
				return false; // password is invalid
				//--
				break;
			case \SmartAuth::ALGO_PASS_CUSTOM_HASH_PASS:
				//--
				return false; // can't be verified with this method, requires a custom method ...
				//--
				break;
			default:
				//--
				return false; // invalid pass algo
				//--
		} //end switch
		//--
		return false; // password is invalid, fallback, just in case ...
		//--
	} //END FUNCTION


	//---- user auth 2FA


	public static function isAuth2FAEnabled() : bool {
		//--
		return (bool) \SmartEnvironment::is2FAEnabled();
		//--
	} //END FUNCTION


	public static function isAuth2FARequired() : bool {
		//--
		return (bool) \SmartEnvironment::is2FARequired();
		//--
	} //END FUNCTION


	public static function validateAuth2FACodeFormat(?string $fa2Code) : bool {
		//--
		$fa2Code = (string) \trim((string)$fa2Code);
		//--
		if((string)$fa2Code == '') {
			return false;
		} //end if
		//--
		if((int)\strlen((string)$fa2Code) != 8) { // 8 digits code
			return false;
		} //end if
		//--
		if(!\preg_match((string)self::AUTH_2FA_REGEX_TOKEN, (string)$fa2Code)) {
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


	public static function createOneTimePassCodePlain() : string {
		//--
		$plainOneTimePassCode = (string) '('.\Smart::uuid_10_num().')#['.\Smart::uuid_10_str().']'; // AUTH_OTC_REGEX_PASSCODE
		//--
		if(self::isValidOneTimePassCodePlain((string)$plainOneTimePassCode) !== true) {
			return '';
		} //end if
		//--
		return (string) $plainOneTimePassCode;
		//--
	} //END FUNCTION


	public static function isValidOneTimePassCodePlain(string $plainOneTimePassCode) : bool {
		//--
		if((string)\trim((string)$plainOneTimePassCode) == '') {
			return false;
		} //end if
		//--
		if((int)\strlen((string)$plainOneTimePassCode) != 25) {
			return false;
		} //end if
		//--
		if(!\preg_match((string)self::AUTH_OTC_REGEX_PASSCODE, (string)$plainOneTimePassCode)) {
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


	public static function createOneTimePassCodeHash(string $plainOneTimePassCode) : string {
		//--
		if(self::isValidOneTimePassCodePlain((string)$plainOneTimePassCode) !== true) {
			return '';
		} //end if
		//--
		return (string) \SmartHashCrypto::sha512((string)$plainOneTimePassCode, true); // B64
		//--
	} //END FUNCTION


	public static function verifyOneTimePassCode(string $plainOneTimePassCode, string $passwordHash) : bool {
		//--
		$passwordHash = (string) \trim((string)$passwordHash); // ~ 88 chars B64
		if((string)$passwordHash == '') {
			return false;
		} //end if
		if((int)\strlen((string)$passwordHash) < 80) {
			return false;
		} //end if
		if((int)\strlen((string)$passwordHash) > 100) {
			return false;
		} //end if
		//--
		if((string)\trim((string)$plainOneTimePassCode) == '') {
			return false;
		} //end if
		//--
		if(self::isValidOneTimePassCodePlain((string)$plainOneTimePassCode) !== true) {
			return false;
		} //end if
		//--
		$hash = (string) \trim((string)self::createOneTimePassCodeHash((string)$plainOneTimePassCode));
		if((string)\trim((string)$hash) == '') {
			return false;
		} //end if
		//--
		if((string)$hash !== (string)$passwordHash) {
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


	public static function encrypt2FASecret(string $id, string $fa2code, string $fa2secret) : string { // ok
		//--
		$id = (string) \trim((string)$id); // userID is: self::userAccountIdToUserName($userData['id']) ; ex: `ymdvjfr1p0.8204174162`
		if((string)$id == '') {
			return ''; // empty username
		} //end if
		//--
		$userEncKey = (string) self::userSecretKeyForEncryption((string)$id); // ok ; also validates the ID
		if((string)\trim((string)$userEncKey) == '') {
			return ''; // user encryption key failed to generate ; user id may be invalid ...
		} //end if
		//--
		$fa2code = (string) \trim((string)$fa2code);
		if(((string)\trim((string)$fa2code) == '') OR (self::validateAuth2FACodeFormat((string)$fa2code) !== true)) {
			return ''; // empty or invalid 2FA Code
		} //end if
		//--
		$fa2secret = (string) \trim((string)$fa2secret);
		if((string)$fa2secret == '') {
			return ''; // 2fa secret is empty
		} //end if
		//--
		if(self::verify2FACode((string)$id, (string)$fa2code, (string)$fa2secret, false) !== true) { // ok ; not encrypted
			return ''; // verification failed
		} //end if
		//--
		$fa2secret = (string) \trim((string)\SmartCipherCrypto::tf_encrypt((string)$fa2secret, (string)$userEncKey)); // TF
		if((string)$fa2secret == '') {
			return ''; // empty secret after decrypt
		} //end if
		//--
		if(self::verify2FACode((string)$id, (string)$fa2code, (string)$fa2secret, true) !== true) { // ok, encrypted
			return ''; // verification failed
		} //end if
		//--
		return (string) $fa2secret;
		//--
	} //END FUNCTION


	public static function decrypt2FASecret(string $id, string $fa2secret) : string { // ok
		//--
		$id = (string) \trim((string)$id); // userID is: self::userAccountIdToUserName($userData['id']) ; ex: `ymdvjfr1p0.8204174162`
		if((string)$id == '') {
			return ''; // empty username
		} //end if
		//--
		$userEncKey = (string) self::userSecretKeyForEncryption((string)$id); // ok ; also validates the ID
		if((string)\trim((string)$userEncKey) == '') {
			return ''; // user encryption key failed to generate ; user id may be invalid ...
		} //end if
		//--
		$fa2secret = (string) \trim((string)$fa2secret);
		if((string)$fa2secret == '') {
			return ''; // 2fa secret is empty
		} //end if
		//--
		$fa2secret = (string) \trim((string)\SmartCipherCrypto::tf_decrypt((string)$fa2secret, (string)$userEncKey)); // TF ; {{{SYNC-FA2-DECRYPT}}}
		if((string)$fa2secret == '') {
			return ''; // empty secret after decrypt
		} //end if
		if(\SmartModExtLib\AuthUsers\Auth2FA::is2FASecretValid((string)$fa2secret) !== true) {
			return ''; // invalid secret
		} //end if
		//--
		return (string) $fa2secret;
		//--
	} //END FUNCTION


	public static function verify2FACode(string $id, string $fa2code, string $fa2secret, bool $isEncrypted) : bool { // ok
		//--
		$id = (string) \trim((string)$id); // userID is: self::userAccountIdToUserName($userData['id']) ; ex: `ymdvjfr1p0.8204174162`
		if((string)$id == '') {
			return false; // user id is empty
		} //end if
		//--
		$userEncKey = (string) \trim((string)self::userSecretKeyForEncryption((string)$id)); // ok ; also validates the ID
		if((string)$userEncKey == '') {
			return false; // user encryption key failed to generate ; user id may be invalid ...
		} //end if
		//--
		$fa2code = (string) \trim((string)$fa2code);
		if(((string)\trim((string)$fa2code) == '') OR (self::validateAuth2FACodeFormat((string)$fa2code) !== true)) {
			return false; // empty or invalid 2FA Code
		} //end if
		//--
		$fa2secret = (string) \trim((string)$fa2secret);
		if((string)$fa2secret == '') {
			return false; // 2fa secret is empty
		} //end if
		if($isEncrypted === true) {
			$fa2secret = (string) \trim((string)\SmartCipherCrypto::tf_decrypt((string)$fa2secret, (string)$userEncKey)); // TF ; {{{SYNC-FA2-DECRYPT}}}
			if((string)$fa2secret == '') {
				return false; // empty secret after decrypt
			} //end if
		} //end if
		if(\SmartModExtLib\AuthUsers\Auth2FA::is2FASecretValid((string)$fa2secret) !== true) {
			return false; // invalid secret
		} //end if
		//--
		$valid2FACode = (string) \SmartModExtLib\AuthUsers\Auth2FA::get2FAPinToken((string)$fa2secret);
		if(((string)\trim((string)$valid2FACode) == '') OR (self::validateAuth2FACodeFormat((string)$valid2FACode) !== true)) {
			return false; // empty or invalid 2FA Pin Token
		} //end if
		//--
		if((string)$valid2FACode !== (string)$fa2code) {
			return false; // 2FA Pin does not match 2FA code
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


	//---- user Security Key


	private static function packSecurityKey(string $privKey, string $pubKey) : string {
		//--
		// This method should remain PRIVATE, not intended for external use other than this context
		//--
		$privKey = (string) \trim((string)$privKey);
		if((string)$privKey == '') {
			\Smart::log_warning(__METHOD__.' # Error: The PrivKey is Empty');
			return '';
		} //end if
		//--
		$pubKey = (string) \trim((string)$pubKey);
		if((string)$pubKey == '') {
			\Smart::log_warning(__METHOD__.' # Error: The PubKey is Empty');
			return '';
		} //end if
		//--
		$privOrigKey = (string) $privKey;
		$pubOrigKey  = (string) $pubKey;
		//--
		$privKey = (string) \Smart::b64_dec((string)$privKey, true); // strict
		if((string)$privKey == '') {
			\Smart::log_warning(__METHOD__.' # Error: The Raw PrivKey is Empty');
			return '';
		} //end if
		if((int)\strlen((string)$privKey) != (int)\SmartCryptoEddsaSodium::ED25519_PRIVATE_KEY_LEN) {
			\Smart::log_warning(__METHOD__.' # Error: The Raw PrivKey have an Invalid Size');
			return '';
		} //end if
		//--
		$pubKey = (string) \Smart::b64_dec((string)$pubKey, true); // strict
		if((string)$pubKey == '') {
			\Smart::log_warning(__METHOD__.' # Error: The Raw PubKey is Empty');
			return '';
		} //end if
		if((int)\strlen((string)$pubKey) != (int)\SmartCryptoEddsaSodium::ED25519_PUBLIC_KEY_LEN) {
			\Smart::log_warning(__METHOD__.' # Error: The Raw PubKey have an Invalid Size');
			return '';
		} //end if
		//--
		$privKey = (string) \trim((string)\Smart::base_from_hex_convert((string)\bin2hex((string)$privKey), 85));
		$pubKey  = (string) \trim((string)\Smart::base_from_hex_convert((string)\bin2hex((string)$pubKey), 85));
		//--
		if((string)$privKey == '') {
			\Smart::log_warning(__METHOD__.' # Error: The B85 PrivKey is Empty');
			return '';
		} //end if
		if((string)$pubKey == '') {
			\Smart::log_warning(__METHOD__.' # Error: The B85 PubKey is Empty');
			return '';
		} //end if
		//--
		$packedSecKey = (string) $privKey.';'.$pubKey; // {{{SYNC-USERS-SECURITY-PACKED-KEY-SEPARATOR}}} ; separator is `;` as B85 charset does not contain it
		//--
		$arrUnpackSecKey = (array) self::unpackSecurityKey((string)$packedSecKey);
	//	\Smart::log_notice(__METHOD__.' '.print_r($arrUnpackSecKey,1)."\n".$privKey."\n".$pubKey."\n".$packedSecKey);
		if((int)\Smart::array_size($arrUnpackSecKey) <= 0) {
			\Smart::log_warning(__METHOD__.' # UnPacked Keys are Empty');
			return '';
		} //end if
		//--
		$privUKey = (string) \trim((string)($arrUnpackSecKey['privKey'] ?? null));
		$pubUKey  = (string) \trim((string)($arrUnpackSecKey['pubKey'] ?? null));
		//--
		if((string)$privUKey == '') {
			\Smart::log_warning(__METHOD__.' # UnPacked PrivKey is Empty');
			return '';
		} //end if
		if((string)$pubUKey == '') {
			\Smart::log_warning(__METHOD__.' # UnPacked PubKey is Empty');
			return '';
		} //end if
		//--
		if((string)$privUKey != (string)$privOrigKey) {
			\Smart::log_warning(__METHOD__.' # UnPacked PrivKey is Different than Original');
			return '';
		} //end if
		if((string)$pubUKey != (string)$pubOrigKey) {
			\Smart::log_warning(__METHOD__.' # UnPacked PubKey is Different than Original');
			return '';
		} //end if
		//--
		return (string) $packedSecKey;
		//--
	} //END FUNCTION


	public static function unpackSecurityKey(string $securityKey) : array {
		//--
		// This method should remain PUBLIC, can be used to unpack security key into Ed2559 Priv/Pub Keypair that can be used to sign different things other than EcDSA ...
		//--
		$securityKey = (string) \trim((string)$securityKey);
		if((string)$securityKey == '') {
			\Smart::log_warning(__METHOD__.' # Error: The Security Key is Empty');
			return [];
		} //end if
		//--
		if(self::isSecurityKeyAvailable() !== true) {
			return []; // no logging
		} //end if
		//--
		if(\Smart::str_contains((string)$securityKey, ';') !== true) { // {{{SYNC-USERS-SECURITY-PACKED-KEY-SEPARATOR}}} ; separator is `;` as B85 charset does not contain it
			\Smart::log_warning(__METHOD__.' # Error: The Security Key is Invalid');
			return [];
		} //end if
		//--
		$arr = (array) \explode(';', (string)$securityKey, 2); // {{{SYNC-USERS-SECURITY-PACKED-KEY-SEPARATOR}}}
		//--
		$privKey = (string) \trim((string)\Smart::base_to_hex_convert((string)($arr[0] ?? null), 85));
		$pubKey  = (string) \trim((string)\Smart::base_to_hex_convert((string)($arr[1] ?? null), 85));
		//--
		if((string)$privKey == '') {
			\Smart::log_warning(__METHOD__.' # Error: The Hex PrivKey Key is Empty');
			return [];
		} //end if
		if((string)$pubKey == '') {
			\Smart::log_warning(__METHOD__.' # Error: The Hex PubKey Key is Empty');
			return [];
		} //end if
		//--
		$privKey = (string) \Smart::safe_hex_2_bin((string)$privKey, false, true); // do not trim
		$pubKey  = (string) \Smart::safe_hex_2_bin((string)$pubKey, false, true); // do not trim
		//--
		if((string)$privKey == '') {
			\Smart::log_warning(__METHOD__.' # Error: The Raw PrivKey Key is Empty');
			return [];
		} //end if
		if((string)$pubKey == '') {
			\Smart::log_warning(__METHOD__.' # Error: The Raw PubKey Key is Empty');
			return [];
		} //end if
		//--
		if((int)\strlen((string)$privKey) != (int)\SmartCryptoEddsaSodium::ED25519_PRIVATE_KEY_LEN) {
			\Smart::log_warning(__METHOD__.' # Error: The Raw PrivKey have an Invalid Size');
			return [];
		} //end if
		if((int)\strlen((string)$pubKey) != (int)\SmartCryptoEddsaSodium::ED25519_PUBLIC_KEY_LEN) {
			\Smart::log_warning(__METHOD__.' # Error: The Raw PubKey have an Invalid Size');
			return [];
		} //end if
		//--
		$privKey = (string) \trim((string)\Smart::b64_enc((string)$privKey));
		$pubKey  = (string) \trim((string)\Smart::b64_enc((string)$pubKey));
		//--
		if((string)$privKey == '') {
			\Smart::log_warning(__METHOD__.' # Error: The B64 PrivKey Key is Empty');
			return [];
		} //end if
		if((string)$pubKey == '') {
			\Smart::log_warning(__METHOD__.' # Error: The B64 PubKey Key is Empty');
			return [];
		} //end if
		//--
		return [
			'privKey' 	=> (string) $privKey,
			'pubKey' 	=> (string) $pubKey,
		];
		//--
	} //END FUNCTION


	public static function getSecurityKeyType() : string {
		//--
		return 'EdDSA';
		//--
	} //END FUNCTION


	public static function getSecurityKeyMode() : string {
		//--
		return 'Ed25519';
		//--
	} //END FUNCTION


	public static function isSecurityKeyAvailable() : bool {
		//--
		return (bool) \SmartCryptoEddsaSodium::isAvailable();
		//--
	} //END FUNCTION


	public static function generateSecurityKey(string $id) : string {
		//--
		if(self::isSecurityKeyAvailable() !== true) {
			return '';
		} //end if
		//-- {{{SYNC-KEYS-USER-ID}}}
		$id = (string) \trim((string)$id); // userID is: self::userAccountIdToUserName($userData['id']) ; ex: `ymdvjfr1p0.8204174162`
		if(((string)$id == '') || (\strpos((string)$id, '.') === false) || ((int)\strlen((string)$id) != 21)) {
			\Smart::log_warning(__METHOD__.' # Empty or Invalid User ID: `'.$id.'`');
			return '';
		} //end if
		if(\SmartAuth::validate_auth_username((string)$id) !== true) {
			\Smart::log_warning(__METHOD__.' # Invalid User ID: `'.$id.'`');
			return '';
		} //end if
		//-- #end sync
		$edKeyPair = (array) \SmartCryptoEddsaSodium::ed25519NewKeypair(); // random secret
		if((string)$edKeyPair['err'] != '') {
			\Smart::log_warning(__METHOD__.' # New Keypair Error: '.$edKeyPair['err']);
			return '';
		} //end if
		//--
		$privKey = (string) \trim((string)($edKeyPair['privKey'] ?? null));
		if((string)$privKey == '') {
			\Smart::log_warning(__METHOD__.' # New Keypair Error: The PrivKey is Empty');
			return '';
		} //end if
		//--
		$pubKey = (string) \trim((string)($edKeyPair['pubKey'] ?? null));
		if((string)$pubKey == '') {
			\Smart::log_warning(__METHOD__.' # New Keypair Error: The PubKey is Empty');
			return '';
		} //end if
		//--
		$edSignData = (array) \SmartCryptoEddsaSodium::ed25519SignData(
			(string) $privKey,
			(string) $pubKey,
			(string) $id
		);
		if((string)$edSignData['err'] != '') {
			\Smart::log_warning(__METHOD__.' # New Keypair Error: Test Signature Failed: '.$edSignData['err']);
			return '';
		} //end if
		if((string)\trim((string)($edSignData['signatureB64'] ?? null)) == '') {
			\Smart::log_warning(__METHOD__.' # New Keypair Error: Test Signature Failed, is Empty');
			return '';
		} //end if
		//--
		$edVerifyData = (array) \SmartCryptoEddsaSodium::ed25519VerifySignedData(
			(string) $pubKey,
			(string) ($edSignData['signatureB64'] ?? null),
			(string) $id
		);
		if((string)$edVerifyData['err'] != '') {
			\Smart::log_warning(__METHOD__.' # New Keypair Error: Test Signature Verification Failed: '.$edVerifyData['err']);
			return '';
		} //end if
		if(($edVerifyData['verifyResult'] ?? null) !== true) {
			\Smart::log_warning(__METHOD__.' # New Keypair Error: Test Signature Verification Failed, Invalid Result: '.($edVerifyData['verifyResult'] ?? null));
			return '';
		} //end if
		//--
		$packedSecKey = (string) \trim((string)self::packSecurityKey((string)$privKey, (string)$pubKey));
		if((string)$packedSecKey == '') {
			\Smart::log_warning(__METHOD__.' # Packed Key is Empty');
			return '';
		} //end if
		//--
		return (string) $packedSecKey;
		//--
	} //END FUNCTION


	public static function encryptSecurityKey(string $id, string $secKey) : string {
		//--
		$id = (string) \trim((string)$id); // userID is: self::userAccountIdToUserName($userData['id']) ; ex: `ymdvjfr1p0.8204174162`
		if((string)$id == '') {
			return '';
		} //end if
		//--
		$userEncKey = (string) \trim((string)self::userSecretKeyForEncryption((string)$id)); // ok ; also validates the ID
		if((string)$userEncKey == '') {
			return '';
		} //end if
		//--
		$secKey = (string) \trim((string)$secKey);
		if((string)$secKey == '') {
			return '';
		} //end if
		//--
		return (string) \trim((string)\SmartCipherCrypto::t3f_encrypt((string)$secKey, (string)$userEncKey)); // 3F ; Ed25519 Private Key is not encrypted such as EcDSA Priv Key, so use a stronger cipher
		//--
	} //END FUNCTION


	public static function decryptSecurityKey(string $id, string $secKey) : string {
		//--
		$id = (string) \trim((string)$id); // userID is: self::userAccountIdToUserName($userData['id']) ; ex: `ymdvjfr1p0.8204174162`
		if((string)$id == '') {
			return '';
		} //end if
		//--
		$userEncKey = (string) \trim((string)self::userSecretKeyForEncryption((string)$id)); // ok ; also validates the ID
		if((string)$userEncKey == '') {
			return '';
		} //end if
		//--
		$secKey = (string) \trim((string)$secKey);
		if((string)$secKey == '') {
			return '';
		} //end if
		//--
		return (string) \trim((string)\SmartCipherCrypto::t3f_decrypt((string)$secKey, (string)$userEncKey)); // 3F ; Ed25519 Private Key is not encrypted such as EcDSA Priv Key, so use a stronger cipher
		//--
	} //END FUNCTION


	//---- user Sign Keys


	public static function encryptSignKeys(string $id, array $certData) : string {
		//--
		$id = (string) \trim((string)$id); // userID is: self::userAccountIdToUserName($userData['id']) ; ex: `ymdvjfr1p0.8204174162`
		if((string)$id == '') {
			return '';
		} //end if
		//--
		$userEncKey = (string) \trim((string)self::userSecretKeyForEncryption((string)$id)); // ok ; also validates the ID
		if((string)$userEncKey == '') {
			return '';
		} //end if
		//--
		if((int)\Smart::array_size($certData) <= 0) {
			return '';
		} //end if
		if((int)\Smart::array_type_test($certData) != 2) { // associative
			return '';
		} //end if
		//--
		if((string)$certData['err'] != '') {
			return '';
		} //end if
		//--
		$certData['mode'] 			= (string) \trim((string)\strval($certData['mode'] ?? null));
		$certData['algo'] 			= (string) \trim((string)\strval($certData['algo'] ?? null));
		$certData['curve'] 			= (string) \trim((string)\strval($certData['curve'] ?? null)); 	// for curves only: EcDSA, EdDSA, ...
		$certData['scheme'] 		= (string) \trim((string)\strval($certData['scheme'] ?? null)); // for non-curves only: RSA, Dilitium, ...
		$certData['years'] 			= (int)    \intval($certData['years'] ?? null);
		$certData['dNames'] 		= (array)  (\is_array($certData['dNames']) ? $certData['dNames'] : null);
		$certData['certificate'] 	= (string) \trim((string)\strval($certData['certificate'] ?? null));
		$certData['privKey'] 		= (string) \trim((string)\strval($certData['privKey'] ?? null));
		$certData['pubKey'] 		= (string) \trim((string)\strval($certData['pubKey'] ?? null));
		$certData['serial'] 		= (string) \trim((string)\strval($certData['serial'] ?? null));
		$certData['dateTime'] 		= (string) \trim((string)\strval($certData['dateTime'] ?? null));
		//--
		if((string)$certData['mode'] == '') {
			return '';
		} //end if
		if((string)$certData['algo'] == '') {
			return '';
		} //end if
		if(
			((string)$certData['curve'] == '')
			AND
			((string)$certData['scheme'] == '')
		) {
			return ''; // cannot be both empty, at least one must be non-empty
		} //end if
		if(((int)$certData['years'] < 1) OR ((int)$certData['years'] > 100)) {
			return '';
		} //end if
		if((int)\Smart::array_size($certData['dNames']) <= 0) {
			return '';
		} //end if
		if((int)\Smart::array_size($certData['dNames']) > 16) { // {{{SYNC-ECDSA-DNAMES-MAX}}}
			return '';
		} //end if
		if((int)\Smart::array_type_test($certData['dNames']) != 2) { // associative
			return '';
		} //end if
		if((string)$certData['certificate'] == '') {
			return '';
		} //end if
		if((string)$certData['privKey'] == '') {
			return '';
		} //end if
		if((string)$certData['pubKey'] == '') {
			return '';
		} //end if
		if((string)$certData['serial'] == '') {
			return '';
		} //end if
		if((string)$certData['dateTime'] == '') {
			return '';
		} //end if
		//--
		$arr = [
			'mode' 			=> (string) $certData['mode'],
			'algo' 			=> (string) $certData['algo'],
			'curve' 		=> (string) $certData['curve'],
			'scheme' 		=> (string)	$certData['scheme'],
			'years' 		=> (int)    $certData['years'],
			'dNames' 		=> (array)  $certData['dNames'],
			'certificate' 	=> (string) $certData['certificate'],
			'privKey' 		=> (string) $certData['privKey'],
			'pubKey' 		=> (string) $certData['pubKey'],
			'serial' 		=> (string) $certData['serial'],
			'dateTime' 		=> (string) $certData['dateTime'],
		];
		//--
		$jsonData = (string) \trim((string)\Smart::json_encode((array)$arr, false, false, false, 2)); // max 2 levels
		if((string)$jsonData == '') {
			return '';
		} //end if
		//--
		$encData = (string) \trim((string)\SmartCipherCrypto::tf_encrypt((string)$jsonData, (string)$userEncKey, true)); // TF+BF
		if((string)$encData == '') {
			return '';
		} //end if
		//--
		return (string) $encData;
		//--
	} //END FUNCTION


	public static function decryptSignKeys(string $id, string $signKeys) : array { // ok
		//--
		$id = (string) \trim((string)$id); // userID is: self::userAccountIdToUserName($userData['id']) ; ex: `ymdvjfr1p0.8204174162`
		if((string)$id == '') {
			return []; // empty username
		} //end if
		//--
		$userEncKey = (string) self::userSecretKeyForEncryption((string)$id); // ok ; also validates the ID
		if((string)\trim((string)$userEncKey) == '') {
			return []; // user encryption key failed to generate ; user id may be invalid ...
		} //end if
		//--
		$signKeys = (string) \trim((string)$signKeys);
		if((string)$signKeys == '') {
			return []; // sign keys are empty
		} //end if
		//--
		$jsonData = (string) \trim((string)\SmartCipherCrypto::tf_decrypt((string)$signKeys, (string)$userEncKey, true)); // TF+BF
		if((string)$jsonData == '') {
			return []; // decryption failed
		} //end if
		//--
		$arr = \Smart::json_decode((string)$jsonData, true, 2); // max 2 levels
		if((int)\Smart::array_size($arr) <= 0) {
			return []; // empty array or not array
		} //end if
		if((int)\Smart::array_type_test($arr) != 2) { // associative
			return []; // invalid array type
		} //end if
		$mandatoryKeys = [ 'certificate', 'privKey', 'pubKey' ];
		for($i=0; $i<\count($mandatoryKeys); $i++) {
			if(!\array_key_exists((string)$mandatoryKeys[$i], (array)$arr)) {
				return []; // mandatory key is missing
			} //end if
		} //end if
		//--
		return (array) $arr;
		//--
	} //END FUNCTION


	public static function getSignKeysType(array $certData) : string {
		//--
		// only the following array key is used: mode
		//--
		if((int)\Smart::array_size($certData) <= 0) {
			return ''; // must be empty, when certificate is empty avoid return '?'
		} //end if
		if((int)\Smart::array_type_test($certData) != 2) { // associative
			return '?';
		} //end if
		//--
		$type = (string) \trim((string)\strval($certData['mode'] ?? null));
		if((string)$type == '') {
			$type = '(Unknown)';
		} //end if
		//--
		return (string) $type;
		//--
	} //END FUNCTION


	public static function isSignKeysExpired(array $certData) : bool {
		//--
		// only the following array keys are used: years, dateTime
		//--
		if((int)\Smart::array_size($certData) <= 0) {
			return true;
		} //end if
		if((int)\Smart::array_type_test($certData) != 2) { // associative
			return true;
		} //end if
		//--
		$certIsExpired = false;
		//--
		$certExpYears = (int) \intval($certData['years'] ?? null);
		if((int)$certExpYears > 0) {
			$certExpDateTime = (string) \trim((string)\strval($certData['dateTime'] ?? null));
			if((string)$certExpDateTime != '') {
				$certExpDateTime  = (string) \date('Y-m-d H:i:s', (int)\strtotime((string)$certExpDateTime));
				$certExpYDateTime = (string) \date('Y-m-d H:i:s', (int)\strtotime((string)$certExpDateTime.' +'.(int)$certExpYears.' years'));
				if((string)$certExpYDateTime <= (string)$certExpDateTime) {
					$certIsExpired = true;
				} //end if
			//	\Smart::log_notice(__METHOD__.' # '.$certExpDateTime.' # '.$certExpYears.' # '.$certExpYDateTime.' # '.($certIsExpired === true ? 'Expired' : 'Valid'));
			} else {
				$certIsExpired = true;
			} //end if
		} else {
			$certIsExpired = true;
		} //end if else
		//--
		return (bool) $certIsExpired;
		//--
	} //END FUNCTION


	public static function isSignPrivateKeyEncrypted(string $privKey) : bool {
		//--
		if(\SmartCryptoEcdsaOpenSSL::isValidPrivateKeyPEM((string)$privKey) !== true) {
			return false; // if is not a valid Private Key something may be wrong, consider is not encrypted so avoid to display
		} //end if
		//--
		if(\SmartCryptoEcdsaOpenSSL::isValidEncryptedPrivateKeyPEM((string)$privKey) !== true) {
			return false; // it is plain
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


	public static function isSignKeysAvailable() : bool {
		//--
		return (bool) \SmartCryptoEcdsaOpenSSL::isAvailable();
		//--
	} //END FUNCTION


	public static function generateSignKeys(string $id, string $theEmailAddress, string $theFullName, int $years) : array {
		//--
		if(self::isSignKeysAvailable() !== true) {
			return [];
		} //end if
		//-- {{{SYNC-KEYS-USER-ID}}}
		$id = (string) \trim((string)$id); // userID is: self::userAccountIdToUserName($userData['id']) ; ex: `ymdvjfr1p0.8204174162`
		if(((string)$id == '') || (\strpos((string)$id, '.') === false) || ((int)\strlen((string)$id) != 21)) {
			\Smart::log_warning(__METHOD__.' # Empty or Invalid User ID: `'.$id.'`');
			return [];
		} //end if
		if(\SmartAuth::validate_auth_username((string)$id) !== true) {
			\Smart::log_warning(__METHOD__.' # Invalid User ID: `'.$id.'`');
			return [];
		} //end if
		//-- #end sync
		$theEmailAddress = (string) \trim((string)$theEmailAddress);
		if((string)$theEmailAddress == '') {
			\Smart::log_warning(__METHOD__.' # Empty Email Address');
			return [];
		} //end if
		if(\SmartAuth::validate_auth_ext_username((string)$theEmailAddress) !== true) {
			\Smart::log_warning(__METHOD__.' # Invalid Email Address: '.$theEmailAddress);
			return [];
		} //end if
		//--
		$theFullName = (string) \trim((string)$theFullName);
		if((string)$theFullName == '') {
			\Smart::log_warning(__METHOD__.' # Empty Full Name');
			return [];
		} //end if
		//--
		if(((int)$years < 1) OR ((int)$years > 100)) {
			\Smart::log_warning(__METHOD__.' # Invalid Years: '.$years);
			return [];
		} //end if
		//--
		$passPhrasePrivKey = (string) \trim((string)self::passPhrasePrivSignKey((string)$id));
		if((string)$passPhrasePrivKey == '') {
			\Smart::log_warning(__METHOD__.' # Failed, PassPhrase is Empty for UserID: `'.$id.'`');
			return [];
		} //end if
		//--
		$arr = (array) \SmartCryptoEcdsaOpenSSL::newCertificate(
			[
				'commonName' 				=> (string) $theFullName,
				'emailAddress' 				=> (string) $theEmailAddress,
				'organizationName' 			=> (string) \SMART_SOFTWARE_NAMESPACE,
				'organizationalUnitName' 	=> (string) \Smart::get_from_config('app.info-url', 'string').' - '.self::DIGICERT_TEXT
			],
			100, // 100 years
			(string) \SmartCryptoEcdsaOpenSSL::OPENSSL_ECDSA_DEF_CURVE,
			(string) \SmartCryptoEcdsaOpenSSL::OPENSSL_CSR_DEF_ALGO,
			(string) $passPhrasePrivKey // passPhrase for the Private Key
		);
		//--
		if((string)$arr['err'] != '') {
			\Smart::log_warning(__METHOD__.' # New Certificate Error: '.$arr['err']);
			return [];
		} //end if
		//--
		if(self::isSignPrivateKeyEncrypted((string)($arr['privKey'] ?? null)) !== true) {
			\Smart::log_warning(__METHOD__.' # Private Key is Not Encrypted');
			return [];
		} //end if
		//--
		return (array) $arr;
		//--
	} //END FUNCTION


	private static function passPhrasePrivSignKey(string $id) : string { // ok ; on failure will return empty string
		//--
		$id = (string) \trim((string)$id); // userID is: self::userAccountIdToUserName($userData['id']) ; ex: `ymdvjfr1p0.8204174162`
		if(((string)$id == '') || (\strpos((string)$id, '.') === false) || ((int)\strlen((string)$id) != 21)) {
			return '';
		} //end if
		if(\SmartAuth::validate_auth_username((string)$id) !== true) {
			return '';
		} //end if
		//--
		$userKey = (string) \trim((string)self::userSecretKeyForEncryption((string)$id));
		if((string)$userKey == '') {
			return '';
		} //end if
		//--
		$userPassPhrase = (string) \trim((string)\Smart::base_from_hex_convert((string)\SmartHashCrypto::sh3a224((string)$userKey), 58));
		if((string)$userPassPhrase == '') {
			return '';
		} //end if
		//--
		return (string) $userPassPhrase;
		//--
	} //END FUNCTION


	//---- user account Secret Key (Encryption Key)


	public static function userSecretKeyForEncryption(string $id) : string { // ok ; on failure will return empty string
		//--
		if(!\defined('\\SMART_FRAMEWORK_SECURITY_KEY')) {
			return '';
		} //end if
		if((string)\trim((string)\SMART_FRAMEWORK_SECURITY_KEY) == '') {
			return '';
		} //end if
		//--
		$id = (string) \trim((string)$id); // userID is: self::userAccountIdToUserName($userData['id']) ; ex: `ymdvjfr1p0.8204174162`
		if(((string)$id == '') || (\strpos((string)$id, '.') === false) || ((int)\strlen((string)$id) != 21)) {
			return '';
		} //end if
		if(\SmartAuth::validate_auth_username((string)$id) !== true) {
			return '';
		} //end if
		//--
		return (string) \trim((string)\SmartHashCrypto::sh3a512((string)$id."\r".\SMART_FRAMEWORK_SECURITY_KEY, true)); // B64
		//--
	} //END FUNCTION


	//---- user account ID to UserName and reverse conversions


	public static function userAccountIdToUserName(string $id) : string {
		//--
		return (string) \strtr((string)\strtolower((string)\trim((string)$id)), ['-' => '.']);
		//--
	} //END FUNCTION


	public static function userNameToUserAccountId(string $username) : string {
		//--
		return (string) \strtr((string)\strtoupper((string)\trim((string)$username)), ['.' => '-']);
		//--
	} //END FUNCTION


	//---- user account DB Type


	public static function getDbType() : string {
		//--
		$type = '';
		//--
		if(\defined('\\SMART_AUTHUSERS_DB_TYPE')) {
			if((string)\SMART_AUTHUSERS_DB_TYPE == 'sqlite') {
				$type = 'sqlite';
			} elseif(((string)\SMART_AUTHUSERS_DB_TYPE == 'pgsql') AND ((int)\Smart::array_size(\Smart::get_from_config('pgsql')) > 0)) {
				$type = 'pgsql';
			} //end if
		} //end if
		//--
		return (string) $type;
		//--
	} //END FUNCTION


	//---- #


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
