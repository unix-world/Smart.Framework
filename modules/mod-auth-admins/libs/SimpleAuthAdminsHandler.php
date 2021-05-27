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
 * This class provide a very simple authentication for admin area (admin.php) using a single account with username/password set in config-admin.php
 *
 * Required constants: APP_AUTH_ADMIN_USERNAME, APP_AUTH_ADMIN_PASSWORD and *optional* the APP_AUTH_ADMIN_ENCRYPTED_PRIVKEY ; they must be set in set in config-admin.php
 * Optional constants: APP_AUTH_PRIVILEGES (set in set in config-admin.php)
 *
 * @version 	v.20210526
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
		if(defined('APP_AUTH_ADMIN_ENFORCE_HTTPS')) {
			if(APP_AUTH_ADMIN_ENFORCE_HTTPS !== false) {
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
		if(
			isset($_SERVER['PHP_AUTH_USER']) AND isset($_SERVER['PHP_AUTH_PW']) AND
			((string)trim((string)$_SERVER['PHP_AUTH_USER']) != '') AND ((string)trim((string)$_SERVER['PHP_AUTH_PW']) != '') AND
			((string)$_SERVER['PHP_AUTH_USER'] === (string)\APP_AUTH_ADMIN_USERNAME) AND ((string)$_SERVER['PHP_AUTH_PW'] === (string)\APP_AUTH_ADMIN_PASSWORD)
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
				(string) $_SERVER['PHP_AUTH_USER'], 	// this should be always the user login ID (login user name)
				(string) $_SERVER['PHP_AUTH_USER'], 	// username alias (in this case is the same as the login ID, but may be different)
				'admin@smart.framework', 				// user email * Optional * (this may be also redundant if the login ID is actually the user email)
				'Super Admin', 							// user full name (Title + ' ' + First Name + ' ' + Last name) * Optional *
				(array) $privileges, 					// login privileges * Optional *
				0, 										// quota * Optional *
				[ // metadata
					'title' => 'Mr.',
					'name_f' => 'Super',
					'name_l' => 'Admin'
				],
				'ADMINS-AREA-SIMPLE', // realm
				'HTTP-BASIC', // method
				(string) $_SERVER['PHP_AUTH_PW'], 		// safe store password
				(string) $priv_keys 					// safe store privacy-keys as encrypted (will be decrypted in-memory) {{{SYNC-ADM-AUTH-KEYS}}}
			);
			//--
			if( // single user login hook by user account {{{SYNC-SINGLE-USER-LOGIN-HOOK}}}
				\defined('\\SMART_FRAMEWORK_SINGLEUSER_LOCK_FILE') AND
				\defined('\\SMART_FRAMEWORK_SINGLEUSER_LOCK_MESSAGE') AND
				\defined('\\SMART_FRAMEWORK_SINGLEUSER_LOCK_ACCOUNT_ID')
			) {
				if((string)\SMART_FRAMEWORK_SINGLEUSER_LOCK_ACCOUNT_ID != '') {
					if((string)\SMART_FRAMEWORK_SINGLEUSER_LOCK_ACCOUNT_ID !== (string)\SmartAuth::get_login_id()) {
						\SmartFrameworkRuntime::Raise503Error(
							(string) \SMART_FRAMEWORK_SINGLEUSER_LOCK_MESSAGE,
							(string) \SmartComponents::operation_ok('Single User Lock File: '.\Smart::escape_html((string)SMART_FRAMEWORK_SINGLEUSER_LOCK_FILE), '80%').\SmartComponents::operation_notice((string)\Smart::nl_2_br((string)\Smart::escape_html((string)\SmartFileSystem::read((string)\SMART_FRAMEWORK_SINGLEUSER_LOCK_FILE))), '80%')
						);
						die('SimpleAuthAdminsHandler:SingleUserAccountIdHook');
					} //end if
				} //end if
			} //end if
			//--
		} else {
			//-- log unsuccessful login
			if(isset($_SERVER['PHP_AUTH_USER']) AND ((string)$_SERVER['PHP_AUTH_USER'] != '')) {
				\SmartFileSystem::write(
					'tmp/logs/adm/'.\Smart::safe_filename('simple-auth-fail-'.\date('Y-m-d@H').'.log'),
					'[FAIL]'."\t".\Smart::normalize_spaces((string)\date('Y-m-d H:i:s O'))."\t".\Smart::normalize_spaces((string)$_SERVER['PHP_AUTH_USER'])."\t".\Smart::normalize_spaces((string)\SmartUtils::get_ip_client())."\t".\Smart::normalize_spaces((string)\SmartUtils::get_visitor_useragent())."\n",
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
