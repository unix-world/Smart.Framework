<?php
// Class: \SmartModExtLib\AuthAdmins\SimpleAuthAdminsHandler
// (c) 2006-2021 unix-world.org - all rights reserved
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
 * @version 	v.20210610
 * @package 	development:modules:AuthAdmins
 *
 */
final class SimpleAuthAdminsHandler {

	// ::

	//================================================================
	public static function Authenticate(bool $enforce_https=false) {
		//--
		if(\headers_sent()) {
			\SmartFrameworkRuntime::Raise500Error('Authentication Failed, Headers Already Sent ...');
			die('SimpleAuthAdminsHandler:headersSent');
			return;
		} //end if
		//--
		if(\SmartFrameworkRegistry::isAdminArea() !== true) {
			\SmartFrameworkRuntime::Raise500Error('Authentication system is designed for admin area only ...');
			die('SimpleAuthAdminsHandler:NotAdminArea');
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
				die('SimpleAuthAdminsHandler:NotHTTPS');
				return;
			} //end if
		} //end if
		//--
		if(!\defined('\\APP_AUTH_ADMIN_USERNAME') OR !defined('\\APP_AUTH_ADMIN_PASSWORD')) {
			\SmartFrameworkRuntime::Raise503Error('Authentication APP_AUTH_ADMIN_USERNAME / APP_AUTH_ADMIN_PASSWORD not set in config ...'); // must be set in config-admin.php
			die('SimpleAuthAdminsHandler:UserOrPasswordNotSet');
			return;
		} elseif((string)\trim((string)\APP_AUTH_ADMIN_USERNAME) == '') {
			\SmartFrameworkRuntime::Raise503Error('Authentication APP_AUTH_ADMIN_USERNAME was set but is Empty ...');
			die('SimpleAuthAdminsHandler:UserIsEmpty');
			return;
		} elseif((string)\trim((string)\APP_AUTH_ADMIN_PASSWORD) == '') {
			\SmartFrameworkRuntime::Raise503Error('Authentication APP_AUTH_ADMIN_PASSWORD was set but is Empty ...');
			die('SimpleAuthAdminsHandler:PasswordIsEmpty');
			return;
		} //end if
		//--
		if(\SmartAuth::validate_auth_username(
			(string) \APP_AUTH_ADMIN_USERNAME,
			true // check for reasonable length, as 5 chars
		) !== true) { // {{{SYNC-AUTH-VALIDATE-USERNAME}}}
			\SmartFrameworkRuntime::Raise503Error('Invalid value set in config for: `APP_AUTH_ADMIN_USERNAME` !'."\n".'The `APP_AUTH_ADMIN_USERNAME` set in config must be at least 5 characters long ! Manually REFRESH this page after by pressing F5 ...');
			die('SimpleAuthAdminsHandler:CHECK-CREDENTIALS:UserName');
			return;
		} //end if
		if(\SmartAuth::validate_auth_password( // {{{SYNC-AUTH-VALIDATE-PASSWORD}}}
			(string) \APP_AUTH_ADMIN_PASSWORD,
			(bool) ((\defined('\\APP_AUTH_ADMIN_COMPLEX_PASSWORDS') && (\APP_AUTH_ADMIN_COMPLEX_PASSWORDS === true)) ? true : false)
		) !== true) {
			\SmartFrameworkRuntime::Raise503Error('Invalid value set in config for: `APP_AUTH_ADMIN_PASSWORD` ... need to be changed !'."\n".'THE PASSWORD IS TOO SHORT OR DOES NOT MEET THE REQUIRED COMPLEXITY CRITERIA.'."\n".'Must be min 7 or 8 chars and max 30 chars.'."\n".'Must contain at least 1 character A-Z, 1 character a-z, one digit 0-9, one special character such as: ! @ # $ % ^ & * ( ) _ - + = [ { } ] / | . , ; ? ...'."\n".'Manually REFRESH this page after by pressing F5 ...');
			die('SimpleAuthAdminsHandler:CHECK-CREDENTIALS:Password');
			return;
		} //end if
		//--
		$auth_data = (array) \SmartModExtLib\AuthAdmins\AuthProviderHttpBasic::GetCredentials((bool)$enforce_https);
		$auth_method = (string) $auth_data['auth-mode'];
		$auth_safe = (int) $auth_data['auth-safe'];
		//--
		$auth_user_name = (string) \strtolower((string)$auth_data['auth-user']);
		$auth_user_pass = (string) $auth_data['auth-pass'];
		//--
		$auth_data = null;
		//--
		if(
			((string)trim((string)$auth_user_name) != '') AND
			((string)trim((string)$auth_user_pass) != '') AND
			((string)$auth_user_name === (string)\APP_AUTH_ADMIN_USERNAME) AND
			((string)$auth_user_pass === (string)\APP_AUTH_ADMIN_PASSWORD)
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
			$priv_keys = '';
			if(\defined('\\APP_AUTH_ADMIN_ENCRYPTED_PRIVKEY')) { // need to be stored as encrypted
				if((string)\trim((string)\APP_AUTH_ADMIN_ENCRYPTED_PRIVKEY) != '') {
					$priv_keys = (string)\trim((string)\APP_AUTH_ADMIN_ENCRYPTED_PRIVKEY);
				} //end if
			} //end if
			//--
			\SmartAuth::set_login_data(
				(string) $auth_user_name, 														// this should be always the user login ID (login user name)
				(string) \ucwords((string)\str_replace('.', ' ', (string)$auth_user_name)), 	// alias, make a nice alias from the login ID
				'admin@smart.framework', 														// user email * Optional * (this may be also redundant if the login ID is actually the user email)
				'Default Admin', 																// user full name (First Name + ' ' + Last name) * Optional *
				(array) $privileges, 															// login privileges * Optional *
				0, 																				// quota * Optional * ... zero, aka unlimited
				[ // metadata
					'auth-safe' => (int) $auth_safe,
					'name_f' 	=> 'Default',
					'name_l' 	=> 'Admin',
				],
				'ADMINS-AREA-SIMPLE', // realm
				(string) $auth_method, // method
				(string) $auth_user_pass, 				// safe store password
				(string) $priv_keys 					// safe store privacy-keys as encrypted (will be decrypted in-memory) {{{SYNC-ADM-AUTH-KEYS}}}
			);
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
				'Default Private Area'
			);
			die('SimpleAuthAdminsHandler:401Prompt');
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
