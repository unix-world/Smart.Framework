<?php
// PHP Auth Users Utils for Smart.Framework
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

namespace SmartModExtLib\AuthUsers;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
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
 * AuthUsers Utils
 *
 * Passwords:
 * It disallows the usage of Plain Passwords by default ; to enable this set this constant in configs: const SMART_FRAMEWORK_AUTH_USERS_ALLOW_UNSAFE_PASSWORDS = true;
 * It supports as default 2 kind of passwords hashes: BCrypt (120-bit) and SF.Pass (256-bit)
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20250207
 * @package 	AuthUsers
 *
 */
final class Utils {

	// ::

	public const AUTH_USERS_AREA = 'SMART-USERS-AREA';
	public const AUTH_USERS_PAGE_SIGNIN = 'auth-users.signin';
	public const AUTH_USERS_URL_SIGNIN = '?page=auth-users.signin';
	public const AUTH_USERS_URL_ACCOUNT = '?page=auth-users.account';

	private const AUTH_USERS_REGISTER_CAPTCHA_NAME = 'AuthUsers-Captcha';

	private const AUTH_2FA_REGEX_TOKEN = '/^[0-9]{8}$/';


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
		\SmartUtils::set_cookie((string)\SmartModExtLib\AuthUsers\AuthCookie::AUTH_USERS_RDR_COOKIE_NAME, (string)$obfsUrl, 300); // force expire after 300 seconds in the case the headers are overflow, this is not important ; plus no need to manage it on clearing
		//--
	} //END FUNCTION


	public static function getRedirUrlCookie() : string {
		//--
		$obfsUrl = (string) \trim((string)\SmartUtils::get_cookie((string)\SmartModExtLib\AuthUsers\AuthCookie::AUTH_USERS_RDR_COOKIE_NAME));
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
		\SmartUtils::unset_cookie((string)\SmartModExtLib\AuthUsers\AuthCookie::AUTH_USERS_RDR_COOKIE_NAME);
		//--
	} //END FUNCTION


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
			if(\SMART_AUTH_USERS_DISABLE_CAPTCHA_ON_REGISTER === true) { // for register, if captcha must be explicit disabled, it is enabled by default
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


	public static function verify2FACode(string $username, string $fa2code, string $fa2secret, bool $isEncrypted) : bool {
		//--
		$username = (string) \trim((string)$username);
		if((string)$username == '') {
			return false; // empty username
		} //end if
		if(\SmartAuth::validate_auth_ext_username((string)$username) !== true) {
			return false; // invalid username
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
			//--
			$userEncKey = (string) self::userEncryptionKey((string)$username);
			if((string)\trim((string)$userEncKey) == '') {
				return false; // user encryption key failed to generate
			} //end if
			//--
			$fa2secret = (string) \trim((string)\SmartCipherCrypto::tf_decrypt((string)$fa2secret, (string)$userEncKey, true)); // TF+BF
			if((string)$fa2secret == '') {
				return false; // empty secret after decrypt
			} //end if
			//--
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


	public static function userEncryptionKey(string $username) : string { // on failure will return empty string
		//--
		if(!\defined('\\SMART_FRAMEWORK_SECURITY_KEY')) {
			return '';
		} //end if
		if((string)\trim((string)\SMART_FRAMEWORK_SECURITY_KEY) == '') {
			return '';
		} //end if
		//--
		$username = (string) \trim((string)$username);
		if((string)$username == '') {
			return '';
		} //end if
		if(\SmartAuth::validate_auth_ext_username((string)$username) !== true) {
			return '';
		} //end if
		//--
		return (string) $username."\r".\SMART_FRAMEWORK_SECURITY_KEY;
		//--
	} //END FUNCTION


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


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
