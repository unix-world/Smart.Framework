<?php
// Class: \SmartModExtLib\AuthAdmins\SimpleAuthAdminsHandler
// (c) 2006-2022 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

namespace SmartModExtLib\AuthAdmins;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

//# Depends on:
//	* SmartAuth
//	* SmartUtils
//	* SmartComponents
//	* SmartFrameworkSecurity

// [PHP8]

//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
//=====================================================================================


/**
 * Simple Auth Admins Handler
 * This class provide a very simple authentication for admin area (admin.php|task.php) using a single account with username/password set in config-admin.php
 *
 * Supports: HTTP Basic Auth ; HTTP Bear Auth (SWT) *optional*
 *
 * Required constants: APP_AUTH_ADMIN_USERNAME, APP_AUTH_ADMIN_PASSWORD and *optional* the APP_AUTH_ADMIN_ENCRYPTED_PRIVKEY ; they must be set in set in config-admin.php
 * Optional constants: APP_AUTH_PRIVILEGES (set in set in config-admin.php)
 *
 * @version 	v.20231028
 * @package 	development:modules:AuthAdmins
 *
 */
final class SimpleAuthAdminsHandler
	extends \SmartModExtLib\AuthAdmins\AbstractAuthHandler
	implements \SmartModExtLib\AuthAdmins\AuthHandlerInterface {

	// ::

	//================================================================
	public static function Authenticate(bool $enforce_https=false, bool $disable_tokens=false, bool $disable_2fa=false) : void {

		//--
		$errPreCheck = (string) self::preCheckForbiddenConditions();
		if((string)$errPreCheck != '') {
			\SmartFrameworkRuntime::Raise403Error((string)$errPreCheck);
			die((string)self::getClassName().'::'.__METHOD__.' # 403 # '.$errPreCheck);
			return;
		} //end if
		//--

		//--
		$errPreCheck = (string) self::preCheckInternalErrorConditions();
		if((string)$errPreCheck != '') {
			\SmartFrameworkRuntime::Raise500Error((string)$errPreCheck);
			die((string)self::getClassName().'::'.__METHOD__.' # 500 # '.$errPreCheck);
			return;
		} //end if
		//--

		//--
		if(\defined('\\APP_AUTH_ADMIN_ENFORCE_HTTPS')) {
			if(\APP_AUTH_ADMIN_ENFORCE_HTTPS !== false) {
				$enforce_https = true;
			} else {
				$enforce_https = false;
			} //end if else
		} //end if
		//--
		$errPreCheck = (string) self::preCheckBadGatewayConditions((bool)$enforce_https);
		if((string)$errPreCheck != '') {
			\SmartFrameworkRuntime::Raise502Error((string)$errPreCheck);
			die((string)self::getClassName().'::'.__METHOD__.' # 502 # '.$errPreCheck);
			return;
		} //end if
		//--

		//--
		$errPreCheck = (string) self::preCheckServiceUnavailableConditions();
		if((string)$errPreCheck != '') {
			\SmartFrameworkRuntime::Raise503Error((string)$errPreCheck);
			die((string)self::getClassName().'::'.__METHOD__.' # 503 # '.$errPreCheck);
			return;
		} //end if
		//--

		//-- Simple Auth Admins REQUIRES a global Private Key
		if(!\defined('\\APP_AUTH_ADMIN_ENCRYPTED_PRIVKEY')) {
			\SmartFrameworkRuntime::Raise208Status(
				'Set in config: `APP_AUTH_ADMIN_ENCRYPTED_PRIVKEY` !'."\n".'This is required by this Authentication Handler ...',
				'Required Config Settings'
			);
			die((string)self::getClassName().':REQUIRED-SETTINGS-OVERALL-ENC-PRIVKEY');
			return;
		} //end if
		//--


// TODO:
// save in a tmp cache: simple-auth-admins.json which have to be encryped and contain the init password hash SHA512 SFv2

		//--
		if(!\defined('\\APP_AUTH_ADMIN_USERNAME') OR !\defined('\\APP_AUTH_ADMIN_PASSWORD')) {
			\SmartFrameworkRuntime::Raise503Error('Authentication APP_AUTH_ADMIN_USERNAME / APP_AUTH_ADMIN_PASSWORD not set in config ...'); // must be set in config-admin.php
			die((string)self::getClassName().':UserOrPasswordNotSet');
			return;
		} elseif((string)\trim((string)\APP_AUTH_ADMIN_USERNAME) == '') {
			\SmartFrameworkRuntime::Raise503Error('Authentication APP_AUTH_ADMIN_USERNAME was set but is Empty ...');
			die((string)self::getClassName().':UserIsEmpty');
			return;
		} elseif((string)\trim((string)\APP_AUTH_ADMIN_PASSWORD) == '') {
			\SmartFrameworkRuntime::Raise503Error('Authentication APP_AUTH_ADMIN_PASSWORD was set but is Empty ...');
			die((string)self::getClassName().':PasswordIsEmpty');
			return;
		} //end if
		//--
		if(\SmartAuth::validate_auth_username(
			(string) \APP_AUTH_ADMIN_USERNAME,
			true // check for reasonable length, as 5 chars
		) !== true) { // {{{SYNC-AUTH-VALIDATE-USERNAME}}}
			\SmartFrameworkRuntime::Raise503Error('Invalid value set in config for: `APP_AUTH_ADMIN_USERNAME` !'."\n".'The `APP_AUTH_ADMIN_USERNAME` set in config must be valid and at least 5 characters long ! Manually REFRESH this page after by pressing F5 ...');
			die((string)self::getClassName().':CHECK-CREDENTIALS:UserName');
			return;
		} //end if
		//--

//echo \Smart::int10_to_hex((int)'100'); die();
/*
$psx = 'Aaghtd7'.microtime(true);
$hsx = \SmartAuth::password_hash_create((string)$psx);
$vfx = \SmartAuth::password_hash_validate_format((string)$hsx);
$chx = \SmartAuth::password_hash_check((string)$psx, (string)$hsx);
var_dump($psx, $hsx, $vfx, $chx);
die();
*/

$authCredentials = (array) self::getAuthCredentials((bool)$enforce_https, (bool)$disable_tokens, (bool)$disable_2fa);
echo '<pre>';
echo \Smart::escape_html((string)\SmartUtils::pretty_print_var($authCredentials));
echo '</pre>';
die();



		$auth_data = (array) \SmartModExtLib\AuthAdmins\AuthProviderHttp::GetCredentials((bool)$enforce_https, (bool)(!$disable_tokens));
		$auth_method = (string) $auth_data['auth-mode'];
		$use_www_auth_prompt = true;
		if(stripos((string)$auth_method, (string)\SmartModExtLib\AuthAdmins\AuthProviderHttp::AUTH_MODE_PREFIX_AUTHEN.\SmartModExtLib\AuthAdmins\AuthProviderHttp::AUTH_MODE_PREFIX_HTTP_BEARER) === 0) { // {{{SYNC-AUTH-PROVIDER-BEARER-SKIP-401-AUTH-PROMPT}}}
			$use_www_auth_prompt = false; // hide the www-auth header for bearer
		} //end if
		//-- auth data checks
		$auth_safe = (int) $auth_data['auth-safe'];
		$auth_error = (string) $auth_data['auth-error'];
		//-- auth data user / pass / token
		$auth_user_name = (string) \strtolower((string)$auth_data['auth-user']); // ensure lowercase username
		$auth_user_pass = (string) $auth_data['auth-pass']; // plain pass
		$auth_user_hash = (string) $auth_data['auth-hash']; // hash pass ; irreversible hash of pass
		$auth_user_priv = (array)  $auth_data['auth-priv']; // array of privs ; {{{SYNC-AUTH-TOKENS-EMPTY-PRIVS-PROTECTION}}} ; for SWT this protection is supposed to be enabled in the Auth Provider, which also validates it !
		//-- #
		$auth_stk_error = 'STK Tokens are not supported by this Auth Provider';
		$is_stk_token_auth = false; // always FALSE, not implemented in this Auth Provider
		$stk_token = ''; // always empty, not implemented in this Auth Provider
		//--
		if(\SmartAuth::validate_auth_username(
			(string) $auth_user_name,
			true // check for reasonable length, as 5 chars
		) !== true) { // {{{SYNC-AUTH-VALIDATE-USERNAME}}}
			$auth_user_name = ''; // unset invalid user name
		} //end if
		//--
		$auth_data = null; // no more needed
		//--
		$is_swt_token_auth = false;
		//--
		$hash_of_pass = ''; // {{{SYNC-AUTH-LOGIC-HASH-VS-PASS}}} ; {{{SYNC-AUTH-TOKEN-SWT}}}
		if((string)\trim((string)$auth_user_name) != '') {
			//-- (check: if not empty user name ONLY)
			if( // SWT Token Auth ; {{{SYNC-AUTH-PROVIDER-CONDITIONS-SWT-TOKEN-LOGIN}}}
				($is_stk_token_auth !== true)
				AND
				((string)$auth_error == '')
				AND
				((string)\trim((string)$auth_user_pass) == '')
				AND
				((string)\trim((string)$auth_user_hash) != '')
				AND
				((int)\strlen((string)$auth_user_hash) == (int)\SmartHashCrypto::PASSWORD_HASH_LENGTH) // {{{SYNC-AUTH-HASHPASS-LENGTH}}}
			) {
				//-- SWT Token Login ; will provide only the pass hash, ireversible, for security
				$is_swt_token_auth = true;
				$hash_of_pass = (string) $auth_user_hash; // SWT token should be providing it ...
$hash_of_pass = ''; // aaaaaaaaaaa TODO: check if hash is valid ! ; until then this is DISABLED
				//--
			} elseif( // Plain Pass Auth (Standard Basic Auth) ; {{{SYNC-AUTH-PROVIDER-CONDITIONS-STD-PASS-LOGIN}}}
				($is_stk_token_auth !== true)
				AND
				((string)$auth_error == '')
				AND
				((string)\trim((string)$auth_user_pass) != '')
				AND
				((string)\trim((string)$auth_user_hash) == '')
			) {
				//-- Plain Pass Login (Standard Basic Auth)
				if(
					((int)\strlen((string)\APP_AUTH_ADMIN_PASSWORD) == 60) // {{{SYNC-PASS-HASH-AUTH-LEN}}}
					AND
					((int)\strlen((string)\trim((string)\APP_AUTH_ADMIN_PASSWORD)) == 60) // {{{SYNC-PASS-HASH-AUTH-LEN}}}
				) {
					//--
					if(\SmartAuth::password_hash_check(
						(string) $auth_user_pass, // plain
						(string) \APP_AUTH_ADMIN_PASSWORD, // hash
						(bool) ((\defined('\\APP_AUTH_ADMIN_COMPLEX_PASSWORDS') && (\APP_AUTH_ADMIN_COMPLEX_PASSWORDS === true)) ? true : false)
					) === true) {
						//--
						if(\SmartAuth::validate_auth_password( // {{{SYNC-AUTH-VALIDATE-PASSWORD}}}
							(string) $auth_user_pass,
							(bool) ((\defined('\\APP_AUTH_ADMIN_COMPLEX_PASSWORDS') && (\APP_AUTH_ADMIN_COMPLEX_PASSWORDS === true)) ? true : false)
						) !== true) {
							\SmartFrameworkRuntime::Raise503Error('Invalid value set in config for: `APP_AUTH_ADMIN_PASSWORD` ... need to be changed !'."\n".'THE PASSWORD IS TOO SHORT OR DOES NOT MEET THE REQUIRED COMPLEXITY CRITERIA.'."\n".'Must be min 8 chars and max 72 chars.'."\n".'Must contain at least 1 character A-Z, 1 character a-z, one digit 0-9, one special character such as: ! @ # $ % ^ & * ( ) _ - + = [ { } ] / | . , ; ? ...'."\n".'Manually REFRESH this page after by pressing F5 ...');
							die((string)self::getClassName().':CHECK-CREDENTIALS:Password');
							return;
						} //end if



						//--
						$hash_of_pass = (string) \SmartHashCrypto::password((string)$auth_user_pass, (string)$auth_user_name); // create it, based on the provided user plain password ...
						if(\SmartHashCrypto::checkpassword((string)\trim((string)$auth_user_pass), (string)$hash_of_pass, (string)$auth_user_name) !== true) { // just an extra security check, that the hash is correct against trimmed password
							$hash_of_pass = ''; // reset, it does not match the trimmed password !
						} //end if
$hash_of_pass = ''; // reset
						//--
					} //end if
					//--
				} //end if
				//--
			} //end if else



		//--



				$hash_of_pass = (string) \SmartHashCrypto::password((string)$auth_user_pass, (string)$auth_user_name);

			//-- #end (check: if not empty user name ONLY)
		} //end if
		//--
		$auth_user_pass = ''; // no more needed !
		$auth_user_hash = ''; // no more needed !
		//--
		if(
			((string)\trim((string)$hash_of_pass) != '') // if hash pass is empty, login failed !
			AND
			((string)\trim((string)$auth_user_name) != '')
			AND
			((string)$auth_user_name === (string)\APP_AUTH_ADMIN_USERNAME)
		) {
			//-- OK, logged in
			$privileges = (string) \SmartAuth::DEFAULT_PRIVILEGES; // {{{SYNC-AUTH-DEFAULT-ADM-SUPER-PRIVS}}}
			if(\defined('\\APP_AUTH_PRIVILEGES')) {
				$privileges .= ','.\APP_AUTH_PRIVILEGES;
			} //end if
			$privileges = (array) \Smart::list_to_array(
				(string) $privileges,
				true
			);
			//--
			$restrictions = [ 'def-account', 'account' ]; // {{{SYNC-AUTH-RESTRICTIONS}}} ; {{{SYNC-DEF-ACC-EDIT-RESTRICTION}}} ; {{{SYNC-ACC-NO-EDIT-RESTRICTION}}}
			//--
			if($is_swt_token_auth === true) {
				$restrictions = [ 'def-account', 'account', 'token' ]; // {{{SYNC-AUTH-TOKEN-RESTRICTIONS}}} ; {{{SYNC-AUTH-RESTRICTIONS}}} ; {{{SYNC-DEF-ACC-EDIT-RESTRICTION}}} ; {{{SYNC-ACC-NO-EDIT-RESTRICTION}}}
				if((int)\Smart::array_size($auth_user_priv) > 0) { // {{{SYNC-AUTH-TOKEN-PRIVS-INTERSECT}}}
					$privileges = (array) \array_values((array)\array_intersect((array)$privileges, (array)$auth_user_priv)); // {{{SYNC-SWT-IMPLEMENT-PRIVILEGES}}}
				} //end if
			} //end if
			//--
			$priv_keys = '';
			if(\defined('\\APP_AUTH_ADMIN_ENCRYPTED_PRIVKEY')) { // need to be stored as encrypted, use \SmartAuth::encrypt_privkey('key', 'pass-hash')
				if((string)\trim((string)\APP_AUTH_ADMIN_ENCRYPTED_PRIVKEY) != '') {
					$priv_keys = (string) \trim((string)\APP_AUTH_ADMIN_ENCRYPTED_PRIVKEY);
					if((string)$priv_keys != '') {
						$priv_keys = (string) \SmartAuth::decrypt_privkey((string)$priv_keys, (string)$hash_of_pass);
					} //end if
				} //end if
			} //end if
			//--
			\SmartAuth::set_login_data( // v.20231018
				'ADMINS-AREA-SIMPLE', 						// auth realm
				(string) $auth_method, 						// auth method
				(string) $hash_of_pass, 					// auth password hash (will be stored as encrypted, in-memory)
				(string) $auth_user_name, 					// auth ID (on backend must be set exact as the auth username)
				(string) $auth_user_name, 					// auth user name
				'admin@smart.framework', 					// user email * Optional *
				'Default Admin', 							// user full name (First Name + ' ' + Last name) * Optional *
				(array)  $privileges, 						// user privileges * Optional *
				(array)  $restrictions, 					// user restrictions
				0, 											// user quota in MB * Optional * ... zero, aka unlimited
				[ 											// user metadata (array) ; may vary
					'auth-safe' => (int) $auth_safe,
					'name_f' 	=> 'Default',
					'name_l' 	=> 'Admin',
				],
				(string) $priv_keys 						// user private key (will be stored as encrypted, in-memory) {{{SYNC-ADM-AUTH-KEYS}}}
			);
			//print_r(\SmartAuth::get_login_data()); die();
			//--
			\SmartFrameworkRuntime::SingleUser_Mode_AuthBreakPoint();
			//--
		} else {
			//-- log unsuccessful login
			if((string)$auth_user_name != '') {
				\SmartFileSystem::write(
					'tmp/logs/adm/'.\Smart::safe_filename('simple-auth-fail-'.\date('Y-m-d@H').'.log'),
					'[FAIL]'."\t".\Smart::normalize_spaces((string)\date('Y-m-d H:i:s O'))."\t".\Smart::normalize_spaces((string)$auth_user_name)."\t".\Smart::normalize_spaces((string)\SmartUtils::get_ip_client())."\t".\Smart::normalize_spaces((string)\SmartUtils::get_visitor_useragent())."\n",
					'a'
				);
			} //end if
			//-- NOT OK, display the Login Form and Exit
			\SmartFrameworkRuntime::Raise401Prompt(
				'Authorization Required',
				(string) \SmartComponents::http_message_401_unauthorized('Authorization Required', \SmartComponents::operation_notice('Login Failed. Either you supplied the wrong credentials or your browser doesn\'t understand how to supply the credentials required.')),
				'Default Private Area',
				(bool) $use_www_auth_prompt
			);
			die((string)self::getClassName().':401Prompt');
			return;
			//--
		} //end if
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================

// end of php code
