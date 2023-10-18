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
 * Required constants: APP_AUTH_ADMIN_USERNAME, APP_AUTH_ADMIN_PASSWORD and *optional* the APP_AUTH_ADMIN_ENCRYPTED_PRIVKEY ; they must be set in set in config-admin.php
 * Optional constants: APP_AUTH_PRIVILEGES (set in set in config-admin.php)
 *
 * @version 	v.20231018
 * @package 	development:modules:AuthAdmins
 *
 */
final class SimpleAuthAdminsHandler {

	// ::

	//================================================================
	public static function Authenticate(bool $enforce_https=false, bool $disable_tokens=false) {
		//--
		if(\headers_sent()) {
			\SmartFrameworkRuntime::Raise500Error('Authentication Failed, Headers Already Sent ...');
			die((string)self::getClassName().':headersSent');
			return;
		} //end if
		//--
		if(\SmartEnvironment::isAdminArea() !== true) {
			\SmartFrameworkRuntime::Raise500Error('Authentication system is designed for admin/task areas only ...');
			die((string)self::getClassName().':NotAdminArea');
			return;
		} //end if
		//--
		if(\defined('\\APP_AUTH_ADMIN_ENFORCE_HTTPS')) {
			if(\APP_AUTH_ADMIN_ENFORCE_HTTPS !== false) {
				$enforce_https = true;
			} else {
				$enforce_https = false;
			} //end if else
		} //end if
		if($enforce_https === true) {
			if((string)\SmartUtils::get_server_current_protocol() !== 'https://') {
				\SmartFrameworkRuntime::Raise403Error('This Web Area require HTTPS.'."\n".'Switch from http:// to https:// in order to use this Web Area');
				die((string)self::getClassName().':NotHTTPS');
				return;
			} //end if
		} //end if
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
		$real_plain_password = (string) \SmartAuth::decrypt_privkey((string)\APP_AUTH_ADMIN_PASSWORD, (string)\APP_AUTH_ADMIN_USERNAME);
		//--
		if(\SmartAuth::validate_auth_password( // {{{SYNC-AUTH-VALIDATE-PASSWORD}}}
			(string) $real_plain_password,
			(bool) ((\defined('\\APP_AUTH_ADMIN_COMPLEX_PASSWORDS') && (\APP_AUTH_ADMIN_COMPLEX_PASSWORDS === true)) ? true : false)
		) !== true) {
			\SmartFrameworkRuntime::Raise503Error('Invalid value set in config for: `APP_AUTH_ADMIN_PASSWORD` ... need to be changed !'."\n".'THE PASSWORD IS TOO SHORT OR DOES NOT MEET THE REQUIRED COMPLEXITY CRITERIA.'."\n".'Must be min 7 or 8 chars and max 30 chars.'."\n".'Must contain at least 1 character A-Z, 1 character a-z, one digit 0-9, one special character such as: ! @ # $ % ^ & * ( ) _ - + = [ { } ] / | . , ; ? ...'."\n".'Manually REFRESH this page after by pressing F5 ...');
			die((string)self::getClassName().':CHECK-CREDENTIALS:Password');
			return;
		} //end if
		//--
		$auth_data = (array) \SmartModExtLib\AuthAdmins\AuthProviderHttp::GetCredentials((bool)$enforce_https, (bool)(!$disable_tokens));
		$auth_method = (string) $auth_data['auth-mode'];
		$use_www_auth_prompt = true;
		if(stripos((string)$auth_method, (string)\SmartModExtLib\AuthAdmins\AuthProviderHttp::AUTH_MODE_PREFIX_AUTHEN.\SmartModExtLib\AuthAdmins\AuthProviderHttp::AUTH_MODE_PREFIX_HTTP_BEARER) === 0) { // {{{SYNC-AUTH-PROVIDER-BEARER-SKIP-401-AUTH-PROMPT}}}
			$use_www_auth_prompt = false; // hide the www-auth header for bearer
		} //end if
		$auth_safe = (int) $auth_data['auth-safe'];
		//--
		$auth_user_name = (string) \strtolower((string)$auth_data['auth-user']);
		$auth_user_pass = (string) $auth_data['auth-pass']; // plain pass
		$auth_user_hash = (string) $auth_data['auth-hash']; // hash pass ; irreversible hash of pass
		$auth_user_priv = (array)  $auth_data['auth-priv']; // array of privs
		//--
		$auth_error     = (string) $auth_data['auth-error'];
		//--
		$auth_data = null; // no more needed
		//--
		$is_swt_token_auth = false;
		//--
		$hash_of_pass = ''; // {{{SYNC-AUTH-LOGIC-HASH-VS-PASS}}} ; {{{SYNC-AUTH-TOKEN-SWT}}}
		if((string)trim((string)$auth_user_name) != '') {
			if((string)trim((string)$auth_user_pass) == '') {
				if( // SWT Token
					((string)$auth_error == '')
					AND
					((string)trim((string)$auth_user_pass) == '')
					AND
					((string)\trim((string)$auth_user_hash) != '')
					AND
					((int)strlen((string)$auth_user_hash) == (int)\SmartHashCrypto::PASSWORD_HASH_LENGTH) // {{{SYNC-AUTH-HASHPASS-LENGTH}}}
				) {
					//-- SWT Token Login ; will provide only the pass hash, ireversible, for security
					$is_swt_token_auth = true;
					$hash_of_pass = (string) $auth_user_hash;
					//--
				} //end if
			} else { // Plain Pass (Standard Basic Auth)
				$hash_of_pass = (string) \SmartHashCrypto::password((string)$auth_user_pass, (string)$auth_user_name);
			} //end if else
		} //end if
		//--
		$auth_user_pass = ''; // no more needed !
		$auth_user_hash = ''; // no more needed !
		//--
		if(
			((string)trim((string)$auth_user_name) != '')
			AND
			((string)trim((string)$hash_of_pass) != '')
			AND
			((string)$auth_user_name === (string)\APP_AUTH_ADMIN_USERNAME)
			AND
			(\SmartHashCrypto::checkpassword((string)$hash_of_pass, (string)$real_plain_password, (string)\APP_AUTH_ADMIN_USERNAME) === true)
		) {
			//-- OK, logged in
			$privileges = '<superadmin>,<admin>';
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
			if(\defined('\\APP_AUTH_ADMIN_ENCRYPTED_PRIVKEY')) { // need to be stored as encrypted, use \SmartAuth::encrypt_privkey() using hash pass as key
				if((string)\trim((string)\APP_AUTH_ADMIN_ENCRYPTED_PRIVKEY) != '') {
					$priv_keys = (string) \trim((string)\APP_AUTH_ADMIN_ENCRYPTED_PRIVKEY); // will be decrypted by lib auth, see: \SmartAuth::decrypt_privkey() using hash pass as key
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


	//================================================================
	private static function getClassName() : string {
		//--
		return (string) \Smart::getClassNameWithoutNamespacePrefix((string)__CLASS__);
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================

// end of php code
