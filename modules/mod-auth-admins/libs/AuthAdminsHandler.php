<?php
// Class: \SmartModExtLib\AuthAdmins\AuthAdminsHandler
// (c) 2006-2021 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

namespace SmartModExtLib\AuthAdmins;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

//# Depends on:
//	* Smart
//	* SmartUnicode
//	* SmartHashCrypto
//	* SmartAuth
//	* SmartUtils
//	* SmartMarkersTemplating
//	* SmartComponents
//	* SmartFrameworkSecurity
//	* \SmartModDataModel\AuthAdmins\SqAuthAdmins

// [PHP8]

//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
//=====================================================================================

//--
if(\headers_sent()) {
	\http_response_code(500);
	die(\SmartComponents::http_error_message('500 Internal Server Error', 'Authentication Failed, Headers Already Sent ...'));
} //end if
//--

//--
if(\defined('\\APP_AUTH_DB_SQLITE')) {
	\http_response_code(500);
	die(\SmartComponents::http_error_message('500 Internal Server Error', 'APP_AUTH_DB_SQLITE must not be defined outside AdminAuth !'));
} //end if
\define('APP_AUTH_DB_SQLITE', '#db/auth-admins-'.\sha1((string)\SMART_FRAMEWORK_SECURITY_KEY).'.sqlite'); // define inject constants direct in global scope, no need to prefix-slashes
if(!\defined('\\APP_AUTH_DB_SQLITE')) {
	\http_response_code(500);
	die(\SmartComponents::http_error_message('500 Internal Server Error', 'APP_AUTH_DB_SQLITE failed to be defined inside AdminAuth !'));
} //end if
//--


/**
 * Multi-Account Auth Admins Handler
 * This class provide a complex authentication for admin area (admin.php) using multi-accounts system with SQLite DB
 *
 * Required constants: APP_AUTH_ADMIN_USERNAME, APP_AUTH_ADMIN_PASSWORD, APP_AUTH_PRIVILEGES (must be set in set in config-admin.php)
 * Required configuration: $configs['app-auth']['adm-namespaces'][ 'Admins Manager' => 'admin.php?page=auth-admins.manager.stml', ... ] (must be set in set in config-admin.php)
 *
 * @version 	v.20210320
 * @package 	development:modules:AuthAdmins
 *
 */
final class AuthAdminsHandler {

	// ::

	private static $template_path = 'etc/templates/default/';
	private static $template_file = 'template.htm';
	private static $tpl_inc_path  = 'modules/mod-auth-admins/libs/templates/auth-admins-handler/';
	private static $img_inc_path  = 'modules/mod-auth-admins/views/img/';
	private static $img_loader    = 'lib/framework/img/loading-spokes.svg';


	//================================================================
	public static function Authenticate($enforce_ssl=false, $tpl_path='', $tpl_file='') {

		//--
		if(\SmartFrameworkRuntime::isAdminArea() !== true) {
			\http_response_code(500);
			die(\SmartComponents::http_message_500_internalerror('Authentication system is designed for admin area only ...'));
		} //end if
		//--

		//--
		if((string)$tpl_path != '') {
			self::$template_path = (string) $tpl_path;
			if((string)$tpl_file != '') {
				self::$template_file = (string) $tpl_file;
			} //end if
		} //end if
		//--

		//--
		if($enforce_ssl === true) {
			if((string)\SmartUtils::get_server_current_protocol() !== 'https://') {
				\http_response_code(403);
				die(\SmartComponents::http_error_message('This Web Area require SSL', 'You have to switch from http:// to https:// in order to use this Web Area'));
			} //end if
		} //end if
		//--

		//--
		if(!\SmartFileSystem::is_type_file(\APP_AUTH_DB_SQLITE)) {
			//--
			if(!\defined('\\APP_AUTH_ADMIN_USERNAME')) {
				\http_response_code(503);
				die(\SmartComponents::http_error_message('Set in config: APP_AUTH_ADMIN_USERNAME !', 'You must set the APP_AUTH_ADMIN_USERNAME constant into config before installation. Manually REFRESH this page after by pressing F5 ...'));
			} //end if
			if(\SmartUnicode::str_len(\APP_AUTH_ADMIN_USERNAME) < 3) {
				\http_response_code(503);
				die(\SmartComponents::http_error_message('Invalid value set in config for: APP_AUTH_ADMIN_USERNAME !', 'The APP_AUTH_ADMIN_USERNAME set in config must be at least 3 characters long ! Manually REFRESH this page after by pressing F5 ...'));
			} //end if
			//--
			if(!\defined('\\APP_AUTH_ADMIN_PASSWORD')) {
				\http_response_code(503);
				die(\SmartComponents::http_error_message('Set in config: APP_AUTH_ADMIN_PASSWORD !', 'You must set the APP_AUTH_ADMIN_PASSWORD constant into config before installation. Manually REFRESH this page after by pressing F5 ...'));
			} //end if
			if(\SmartUnicode::str_len(\APP_AUTH_ADMIN_PASSWORD) < 7) {
				\http_response_code(503);
				die(\SmartComponents::http_error_message('Invalid value set in config for: APP_AUTH_ADMIN_PASSWORD !', 'The APP_AUTH_ADMIN_PASSWORD set in config must be at least 7 characters long ! Manually REFRESH this page after by pressing F5 ...'));
			} //end if
			//--
		} //end if
		//--
		if(!\defined('\\APP_AUTH_PRIVILEGES')) {
			\http_response_code(503);
			die(\SmartComponents::http_error_message('Set in config: APP_AUTH_PRIVILEGES !', 'You must set the APP_AUTH_PRIVILEGES constant into config to run this Authentication plugin ...'));
		} //end if
		//--

		//--
		$db = new \SmartModDataModel\AuthAdmins\SqAuthAdmins(); // open connection (and create + initialize DB if not found)
		//--

		//-- do auth except of login page
		if(isset($_SERVER['PATH_INFO']) AND (!empty($_SERVER['PATH_INFO']))) {
			$try_auth = 'yes';
		} elseif(!empty($_GET)) {
			$try_auth = 'yes';
		} elseif(!empty($_POST)) {
			$try_auth = 'yes';
		} elseif(!empty($_REQUEST)) {
			$try_auth = 'yes';
		} else {
			$try_auth = 'no'; // if we have no GET/POST/REQUEST then this is the login page
		} //end if
		//--

		//-- validate username
		$auth_user_name = (string) \SmartUnicode::str_tolower(\trim(\SmartFrameworkSecurity::FilterUnsafeString((string)(isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : ''))));
		if((\strlen((string)$auth_user_name) < 3) OR (\strlen((string)$auth_user_name) > 25) OR (!\preg_match('/^[a-z0-9\.]+$/', (string)$auth_user_name))) {
			$auth_user_name = ''; // unset invalid user names
		} //end if
		//-- validate password
		$auth_user_pass = '';
		if((string)$auth_user_name != '') {
			$auth_user_pass = (string) \SmartFrameworkSecurity::FilterUnsafeString((string)(isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : ''));
			if((\strlen($auth_user_pass) < 7) OR (\strlen($auth_user_pass) > 30)) { // {{{SYNC-MOD-AUTH-VALIDATIONS}}}
				$auth_user_pass = '';
			} //end if
		} //end if
		//--

		//--
		$secret = \SmartHashCrypto::sha256('Smart.Framework.Modules/AuthAdmins @'.\date('Y-m-d H:i:s').'#'.\SMART_FRAMEWORK_SECURITY_KEY);
		//--

		//-- manage login or logout
		$logged_in = 'no'; // user is not logged in (unsuccessful username or password)
		$login_or_logout_form = (string) \SmartComponents::http_message_401_unauthorized('Authorization Required', \SmartComponents::operation_notice('Login Failed. Either you supplied the wrong credentials or your browser doesn\'t understand how to supply the credentials required.<script type="text/javascript">setTimeout(function(){ self.location = \''.\Smart::escape_js(\SmartUtils::get_server_current_url().\SmartUtils::get_server_current_script()).'\'; }, 3500);</script>', '100%').'<img src="'.\SmartUtils::get_server_current_url().self::$img_loader.'">');
		//--
		if(isset($_REQUEST['logout']) AND ((string)$_REQUEST['logout'] != '')) { // do logout
			//--
			$login_or_logout_form = (string) \SmartComponents::render_app_template(
				(string) self::$template_path,
				(string) self::$template_file,
				[
					'TITLE' => 'Logout from Admins Area',
					'MAIN'  => (string) \SmartMarkersTemplating::render_file_template(
						(string) self::$tpl_inc_path.'logout.htm',
						[
							'MOD-IMG-PATH' 	=> (string) self::$img_inc_path,
							'IMG-LOADER' 	=> (string) self::$img_loader,
							'URL-LOGOUT' 	=> (string) \SmartUtils::get_server_current_url().\SmartUtils::get_server_current_script(),
							'TIME-REDIR' 	=> 1500 // 1.5 sec.
						]
					)
				]
			);
			//--
		} elseif((string)$try_auth != 'no') { // requires login ; check login
			//-- try to check the failed logins
			if((string)$auth_user_name != '') {
				//--
				$check_fail = (int) $db->checkFailLoginData((string)$auth_user_name, (string)\SmartUtils::get_ip_client());
				$retry_seconds = (int) \Smart::format_number_int(($check_fail - \time()), '+');
				//--
				if($check_fail > 0) {
					\http_response_code(503);
					\header('Retry-After: '.(int)$retry_seconds);
					die(\SmartComponents::http_error_message('503 FAILED LOGIN TIMEOUT: '.(int)$retry_seconds.'sec.', 'Next Allowed Login Time is: '.\date('Y-m-d H:i:s O', (int)$check_fail).' / Current Server Time is: '.\date('Y-m-d H:i:s O')));
				} //end if
				//--
			} //end if
			//-- try to get the user account from DB
			if(((string)$auth_user_name == '') OR ((string)$auth_user_pass == '')) {
				//--
				$admin_login = array(); // Invalid Login, Username or Password is empty
				//--
			} else {
				//--
				$admin_login = (array) $db->getLoginData((string)$auth_user_name, \SmartHashCrypto::password((string)$auth_user_pass, $auth_user_name)); // try to login
				//--
			} //end if else
			//-- test if login is successful
			if(isset($admin_login['id']) AND ((string)$admin_login['id'] != '')) {
				//--
				$logged_in = 'yes'; // user is logged in
				//--
				\SmartAuth::set_login_data(
					(string) $admin_login['id'], // login user id
					(string) $admin_login['id'], // alias (for admins this is the same as login ID)
					(string) $admin_login['email'], // email
					(string) \trim((string)$admin_login['name_f'].' '.$admin_login['name_l']), // login full user name
					(string) $admin_login['priv'], // login privileges
					(int)    \Smart::format_number_int($admin_login['quota'],'+'), // quota in MB
					[ // metadata (array)
						'title' 	=> (string) $admin_login['title'],
						'name_f' 	=> (string) $admin_login['name_f'],
						'name_l' 	=> (string) $admin_login['name_l'],
						'address' 	=> (string) $admin_login['address'],
						'city' 		=> (string) $admin_login['city'],
						'region' 	=> (string) $admin_login['region'],
						'country' 	=> (string) $admin_login['country'],
						'zip' 		=> (string) $admin_login['zip'],
						'phone' 	=> (string) $admin_login['phone'],
						'restrict' 	=> (string) $admin_login['restrict'],
						'settings' 	=> (string) $admin_login['settings']
					],
					'ADMINS-AREA', // realm
					'HTTP-BASIC', // method
					(string) $auth_user_pass, 		// safe store password
					(string) $admin_login['keys'] 	// safe store privacy-keys as encrypted (will be decrypted in-memory) {{{SYNC-ADM-AUTH-KEYS}}}
				);
				//--
				$db->logSuccessfulLoginData(
					(string) $admin_login['id'], 			// successful auth account ID
					(string) \SmartUtils::get_ip_client() 	// client IP
				);
				//--
			} else { // log unsuccessful login
				//--
				$db->logUnsuccessfulLoginData(
					(string) $auth_user_name, 						// failed auth account ID
					(string) \SmartUtils::get_ip_client(), 			// client IP
					(string) \SmartUtils::get_visitor_useragent() 	// client Browser Signature
				);
				//--
			} //end if else
			//--
		} else { // display login form
			//--
			$arr_login_namespaces = \Smart::get_from_config('app-auth.adm-namespaces');
			if(\Smart::array_size($arr_login_namespaces) <= 0) {
				\http_response_code(503);
				die(\SmartComponents::http_error_message('App Auth Namespaces not set in config !', 'You must set the App Auth Namespaces in config as array (app-auth.adm-namespaces) with pairs of [key=namespace title / val=namespace url] of all login namespaces. Manually REFRESH this page after by pressing F5 ...'));
			} //end if
			foreach((array)$arr_login_namespaces as $key => $val) {
				$arr_login_namespaces[(string)$key] = (string) \SmartUtils::crypto_blowfish_encrypt((string)$val, (string)$secret);
			} //end foreach
			//--
			$login_or_logout_form = (string) \SmartComponents::render_app_template(
				(string) self::$template_path,
				(string) self::$template_file,
				[
					'TITLE' => 'Login to Admins Area',
					'MAIN'  => (string) \SmartMarkersTemplating::render_file_template(
						(string) self::$tpl_inc_path.'login.htm',
						[
							'MOD-IMG-PATH' 	=> (string) self::$img_inc_path,
							'LOGIN-NSPACES' => (array) $arr_login_namespaces,
							'POWERED-HTML' 	=> (string) \SmartComponents::app_powered_info('no'),
							'CRR-YEAR' 		=> (string) \date('Y'),
							'SECRET' 		=> (string) \bin2hex((string)(string)$secret),
							'LOGOUT-URL' 	=> (string) \SmartUtils::get_server_current_url().\SmartUtils::get_server_current_script().'?logout=yes'
						]
					)
				]
			);
			//--
		} //end if else
		//--

		//--
		$db = null; // close connection
		//--

		//--
		if(\defined('\\APP_AUTH_ADMIN_USERNAME')) {
			\http_response_code(503);
			die(\SmartComponents::http_error_message('Unset from config: APP_AUTH_ADMIN_USERNAME.', 'You must finally unset the APP_AUTH_ADMIN_USERNAME constant from config after Auth Initialization. Manually REFRESH this page after by pressing F5 ...'));
		} //end if
		if(\defined('\\APP_AUTH_ADMIN_PASSWORD')) {
			\http_response_code(503);
			die(\SmartComponents::http_error_message('Unset from config: APP_AUTH_ADMIN_PASSWORD.', 'You must finally unset the APP_AUTH_ADMIN_PASSWORD constant from config after Auth Initialization. Manually REFRESH this page after by pressing F5 ...'));
		} //end if
		if(\defined('\\APP_AUTH_ADMIN_ENCRYPTED_PRIVKEY')) {
			\http_response_code(503);
			die(\SmartComponents::http_error_message('Unset from config: APP_AUTH_ADMIN_ENCRYPTED_PRIVKEY.', 'The APP_AUTH_ADMIN_ENCRYPTED_PRIVKEY is not used for this type of authentication ... Manually REFRESH this page after by pressing F5 ...'));
		} //end if
		//--

		//--
		if(((string)$logged_in != 'yes') OR (\SmartAuth::check_login() !== true)) { // IF NOT LOGGED IN
			//--
			if((string)$try_auth != 'no') { // this is optional because on this side will die() anyway ...
				\header('WWW-Authenticate: Basic realm="Private Area"');
				\http_response_code(401);
			} else {
				\SmartFrameworkRuntime::outputHttpHeadersNoCache(); // fix: needs no cache headers
			} //end if
			//--
			die((string)$login_or_logout_form); // display login or logot form
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
