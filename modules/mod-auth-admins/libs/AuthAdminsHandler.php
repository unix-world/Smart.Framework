<?php
// Class: \SmartModExtLib\AuthAdmins\AuthAdminsHandler
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


/**
 * Multi-Account Auth Admins Handler
 * This class provide a complex authentication for admin area (admin.php|task.php) using multi-accounts system with SQLite DB
 *
 * Required constants: APP_AUTH_ADMIN_USERNAME, APP_AUTH_ADMIN_PASSWORD, APP_AUTH_PRIVILEGES (must be set in set in config-admin.php)
 * Required configuration: $configs['app-auth']['adm-namespaces'][ 'Admins Manager' => 'admin.php?page=auth-admins.manager.stml', ... ] (must be set in set in config-admin.php)
 *
 * @version 	v.20210630
 * @package 	development:modules:AuthAdmins
 *
 */
final class AuthAdminsHandler {

	// ::

	private static $template_path = 'modules/mod-auth-admins/templates/';
	private static $template_file = 'template.htm';

	private const TPL_INC_PATH = 'modules/mod-auth-admins/libs/templates/auth-admins-handler/';
	private const IMG_LOADER   = 'lib/framework/img/loading-spokes.svg';
	private const IMG_UNICORN  = 'lib/framework/img/unicorn-auth-logo.svg';
	private const TXT_UNICORN  = 'Unicorn Secure Authentication';


	//================================================================
	public static function Authenticate(bool $enforce_https=false, ?string $tpl_path='', ?string $tpl_file='') {

		//--
		if(\headers_sent()) {
			\SmartFrameworkRuntime::Raise500Error('Authentication Failed, Headers Already Sent ...');
			die('AuthAdminsHandler:headersSent');
			return;
		} //end if
		//--

		//--
		if(\defined('\\APP_AUTH_DB_SQLITE')) {
			\SmartFrameworkRuntime::Raise500Error('AUTH STORAGE must not be defined outside AdminAuth !');
			die('AuthAdminsHandler:APP_AUTH_DB_SQLITE:1');
			return;
		} //end if
		\define('APP_AUTH_DB_SQLITE', '#db/auth-admins-'.\sha1((string)\SMART_FRAMEWORK_SECURITY_KEY).'.sqlite'); // define inject constants direct in global scope, no need to prefix-slashes
		if(!\defined('\\APP_AUTH_DB_SQLITE')) {
			\SmartFrameworkRuntime::Raise500Error('AUTH STORAGE could not be defined inside AdminAuth !');
			die('AuthAdminsHandler:APP_AUTH_DB_SQLITE:2');
			return;
		} //end if
		//--

		//--
		if(\SmartFrameworkRegistry::isAdminArea() !== true) {
			\SmartFrameworkRuntime::Raise500Error('Authentication system is designed for admin area only ...');
			die('AuthAdminsHandler:NotAdminArea');
			return;
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
		if(\defined('\\APP_AUTH_ADMIN_ENFORCE_HTTPS')) {
			if(\APP_AUTH_ADMIN_ENFORCE_HTTPS !== false) {
				$enforce_https = true;
			} else {
				$enforce_https = false;
			} //end if else
		} //end if
		if($enforce_https === true) {
			if((string)\SmartUtils::get_server_current_protocol() !== 'https://') {
				\SmartFrameworkRuntime::Raise403Error('This Web Area require HTTPS'."\n".'Switch from http:// to https:// in order to use this Web Area');
				die('AuthAdminsHandler:NotHTTPS');
				return;
			} //end if
		} //end if
		//--

		//--
		if(!\defined('\\APP_AUTH_PRIVILEGES')) { // this must be check always, not only on initDb
			\SmartFrameworkRuntime::Raise503Error('Set in config the `APP_AUTH_PRIVILEGES` constant !'."\n".'The `APP_AUTH_PRIVILEGES` constant is required to run this Authentication plugin ...');
			die('AuthAdminsHandler:CHECK-PRIVS');
			return;
		} //end if
		//--
		$init_err = '';
		if(!\SmartFileSystem::is_type_file((string)\APP_AUTH_DB_SQLITE)) {
			$init_err = self::initDb();
		} //end if
		if((string)$init_err != '') {
			\SmartFrameworkRuntime::Raise503Error((string)$init_err);
			die('AuthAdminsHandler:INIT-FAILED');
		} //end if
		$init_err = '';
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

		//--
		$auth_data = (array) \SmartModExtLib\AuthAdmins\AuthProviderHttpBasic::GetCredentials((bool)$enforce_https);
		$auth_method = (string) $auth_data['auth-mode'];
		$auth_safe = (int) $auth_data['auth-safe'];
		//-- validate username
		$auth_user_name = (string) \strtolower((string)$auth_data['auth-user']); // can contain only a-z 0-9 .
		if(\SmartAuth::validate_auth_username(
			(string) $auth_user_name
		) !== true) { // {{{SYNC-AUTH-VALIDATE-USERNAME}}}
			$auth_user_name = ''; // unset invalid user name
		} //end if
		//-- validate password
		$auth_user_pass = '';
		if((string)$auth_user_name != '') {
			$auth_user_pass = (string) $auth_data['auth-pass'];
			if(\SmartAuth::validate_auth_password( // {{{SYNC-AUTH-VALIDATE-PASSWORD}}}
				(string) $auth_user_pass,
				(bool) ((\defined('\\APP_AUTH_ADMIN_COMPLEX_PASSWORDS') && (\APP_AUTH_ADMIN_COMPLEX_PASSWORDS === true)) ? true : false) // check for complexity just on login ! ... for the rest do not check because if this constant changes ... cannot re-update everything !
			) !== true) {
				$auth_user_pass = ''; // unset invalid password
			} //end if
		} //end if
		//--
		$auth_data = null;
		//--

		//-- manage login or logout
		$logged_in = 'no'; // user is not logged in (unsuccessful username or password)
		$login_or_logout_form = (string) \SmartComponents::http_message_401_unauthorized('Authorization Required', \SmartComponents::operation_notice('Login Failed. Either you supplied the wrong credentials or your browser doesn\'t understand how to supply the credentials required.<script>setTimeout(() => { self.location = \''.\Smart::escape_js(\SmartUtils::get_server_current_url().\SmartUtils::get_server_current_script()).'\'; }, 3500);</script>').'<img width="48" height="48" src="'.\Smart::escape_html((string)\SmartUtils::get_server_current_url().self::IMG_LOADER).'">'.'&nbsp;&nbsp;'.'<img title="'.\Smart::escape_html((string)self::TXT_UNICORN).'" width="48" height="48" src="'.\Smart::escape_html((string)\SmartUtils::get_server_current_url().self::IMG_UNICORN).'">');
		//--
		if(isset($_REQUEST['logout']) AND ((string)$_REQUEST['logout'] != '')) { // do logout
			//--
			$login_or_logout_form = (string) \SmartComponents::render_app_template(
				(string) self::$template_path,
				(string) self::$template_file,
				[
					'TITLE' => 'Logout from Admins Area',
					'MAIN'  => (string) \SmartMarkersTemplating::render_file_template(
						(string) self::TPL_INC_PATH.'logout.htm',
						[
							'IMG-LOADER' 	=> (string) self::IMG_LOADER,
							'URL-LOGOUT' 	=> (string) \SmartUtils::get_server_current_url().\SmartUtils::get_server_current_script(),
							'TIME-REDIR' 	=> 1500 // 1.5 sec.
						]
					)
				]
			);
			//--
		} elseif((string)$try_auth != 'no') { // requires login ; check login
			//-- open connection
			$db = null;
			try {
				$db = new \SmartModDataModel\AuthAdmins\SqAuthAdmins(); // will create + initialize DB if not found
			} catch(\Exception $e) {
				$db = null;
				\SmartFrameworkRuntime::Raise500Error('AUTH DB Failed: `'.$e->getMessage().'`');
				die('AuthAdminsHandler:AUTH-DB-FAILED');
				return;
			} //end try catch
			//-- try to check the failed logins
			$hash_pass = (string) \SmartHashCrypto::password((string)$auth_user_pass, $auth_user_name);
			if((string)$auth_user_name != '') {
				//--
				$check_fail = (int) $db->checkFailLoginData((string)$auth_user_name, (string)$hash_pass, (string)\SmartUtils::get_ip_client());
				$retry_seconds = (int) \Smart::format_number_int(($check_fail - \time()), '+');
				//--
				if($check_fail > 0) {
					\SmartFrameworkRuntime::outputHttpSafeHeader('Retry-After: '.(int)$retry_seconds);
					\SmartFrameworkRuntime::Raise429Error('429 TOO MANY FAILED LOGIN REQUESTS FOR IP :: ['.\SmartUtils::get_ip_client().'] :: LOGIN TIMEOUT: '.(int)$retry_seconds.'sec.'."\n".'Next Allowed Login Time is: '.\Smart::escape_html((string)\date('Y-m-d H:i:s O'), (int)$check_fail).' / Current Server Time is: '.\Smart::escape_html((string)\date('Y-m-d H:i:s O')), '<script>setTimeout(() => { self.location = self.location; }, 15000);</script>'.'<img width="48" height="48" src="'.\Smart::escape_html((string)\SmartUtils::get_server_current_url().self::IMG_LOADER).'">'.'&nbsp;&nbsp;'.'<img title="'.\Smart::escape_html((string)self::TXT_UNICORN).'" width="48" height="48" src="'.\Smart::escape_html((string)\SmartUtils::get_server_current_url().self::IMG_UNICORN).'">');
					die('AuthAdminsHandler:TOO-MANY-ATTEMPTS');
					return;
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
				$admin_login = (array) $db->getLoginData((string)$auth_user_name, (string)$hash_pass); // try to login
				//--
			} //end if else
			//-- test if login is successful
			if(isset($admin_login['id']) AND ((string)$admin_login['id'] != '')) {
				//--
				$logged_in = 'yes'; // user is logged in
				//--
				\SmartAuth::set_login_data(
					(string) $admin_login['id'], // login user id
					(string) \ucwords((string)\str_replace('.', ' ', (string)$admin_login['id'])), // alias, make a nice alias from the login ID
					(string) $admin_login['email'], // email
					(string) \trim((string)\trim((string)$admin_login['name_f']).' '.\trim((string)$admin_login['name_l'])), // login full user name
					(string) $admin_login['priv'], // login privileges
					(int)    \Smart::format_number_int($admin_login['quota'],'+'), // quota in MB
					[ // metadata (array)
						'auth-safe' => (int)    $auth_safe,
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
						'settings' 	=> (string) $admin_login['settings'],
					],
					'ADMINS-AREA', // realm
					(string) $auth_method, // method
					(string) $auth_user_pass, 		// safe store password
					(string) $admin_login['keys'] 	// safe store privacy-keys as encrypted (will be decrypted in-memory) {{{SYNC-ADM-AUTH-KEYS}}}
				);
				//--
				\SmartFrameworkRuntime::SingleUser_Mode_AuthBreakPoint();
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
			$db = null; // close connection
			//--
		} else { // display login form
			//--
			$arr_bw = (array) \SmartComponents::get_imgdesc_by_bw_id((string)\SmartUtils::get_os_browser_ip('bw'));
			//--
			$login_or_logout_form = (string) \SmartComponents::render_app_template(
				(string) self::$template_path,
				(string) self::$template_file,
				[
					'TITLE' => 'Login to '.((\SmartFrameworkRegistry::isTaskArea() === true) ? 'Task' : 'Admin').' Area',
					'MAIN'  => (string) \SmartMarkersTemplating::render_file_template(
						(string) self::TPL_INC_PATH.'login.htm',
						[
							'LOGIN-SCRIPT' 	=> (string) ((\SmartFrameworkRegistry::isTaskArea() === true) ? 'task.php' : 'admin.php'),
							'LOGIN-URL' 	=> (string)  \SmartUtils::crypto_blowfish_encrypt((string)'#!'.((\SmartFrameworkRegistry::isTaskArea() === true) ? 'tsk' : 'adm').'/DISPLAY-REALMS'), // {{{SYNC-AUTH-ADMINS-LOGIN-SCRIPT}}}
							'LOGIN-AREA' 	=> (string) ((\SmartFrameworkRegistry::isTaskArea() === true) ? 'Task' : 'Admin'),
							'POWERED-HTML' 	=> (string) \SmartComponents::app_powered_info(
								'no',
								[
									[],
									[
										'type' => 'sside',
										'name' => (string) self::TXT_UNICORN,
										'logo' => (string) \SmartUtils::get_server_current_url().self::IMG_UNICORN,
										'url' => ''
									],
									[
										'type' => 'cside',
										'name' => (string) $arr_bw['desc'],
										'logo' => (string) \SmartUtils::get_server_current_url().$arr_bw['img'],
										'url' => ''
									]
								],
								false, // show dbs
								true, // watch
								true // show logo
							),
							'AREA-ID' 		=> (string) (defined('SMART_SOFTWARE_NAMESPACE') ? SMART_SOFTWARE_NAMESPACE : 'N/A'),
							'CRR-YEAR' 		=> (string) \date('Y'),
							'LOGOUT-URL' 	=> (string) \SmartUtils::get_server_current_url().\SmartUtils::get_server_current_script().'?logout=yes',
						]
					)
				]
			);
			//--
		} //end if else
		//--

		//--
		if(
			\defined('\\APP_AUTH_ADMIN_USERNAME') OR
			\defined('\\APP_AUTH_ADMIN_PASSWORD') OR
			\defined('\\APP_AUTH_ADMIN_ENCRYPTED_PRIVKEY')
		) {
			\SmartFrameworkRuntime::Raise202Status(
				'UNSET FROM CONFIG the following constants: `APP_AUTH_ADMIN_USERNAME`, `APP_AUTH_ADMIN_PASSWORD`, `APP_AUTH_ADMIN_ENCRYPTED_PRIVKEY`.'."\n".
				'AFTER THE AUTH INITIALIZATION these constants have to be unset because it is a security risk to keep them as unencrypted strings in a config file.'."\n".
				'Manually REFRESH this page after that by pressing F5 ...',
				'DB Initialization Completed ...'
			);
			die('AuthAdminsHandler:SAFETY-CHECK-USER-SET');
			return;
		} //end if
		//--

		//--
		if(((string)$logged_in != 'yes') OR (\SmartAuth::check_login() !== true)) { // IF NOT LOGGED IN
			//--
			if((string)$try_auth != 'no') { // this is optional because on this side will die() anyway ...
				\SmartFrameworkRuntime::Raise401Prompt(
					'Authorization Required',
					(string) $login_or_logout_form,
					'Private Area'
				);
				die('AuthAdminsHandler:401Prompt');
				return;
			} //end if
			//--
			\SmartFrameworkRuntime::outputHttpHeadersNoCache(); // fix: needs no cache headers
			die((string)$login_or_logout_form); // display login or logot form
			//--
		} //end if
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	private static function initDb() {
		//--
		if(!\defined('\\APP_AUTH_ADMIN_USERNAME')) {
			return 'Set in config: `APP_AUTH_ADMIN_USERNAME` !'."\n".'You must set the `APP_AUTH_ADMIN_USERNAME` constant in config before installation. Manually REFRESH this page after by pressing F5 ...';
		} //end if
		if(\SmartAuth::validate_auth_username(
			(string) \APP_AUTH_ADMIN_USERNAME,
			true // check for reasonable length, as 5 chars
		) !== true) { // {{{SYNC-AUTH-VALIDATE-USERNAME}}}
			return 'Invalid value set in config for: `APP_AUTH_ADMIN_USERNAME` !'."\n".'The `APP_AUTH_ADMIN_USERNAME` set in config must be at least 5 characters long ! Manually REFRESH this page after by pressing F5 ...';
		} //end if
		//--
		if(!\defined('\\APP_AUTH_ADMIN_PASSWORD')) {
			return 'Set in config: `APP_AUTH_ADMIN_PASSWORD` !'."\n".'You must set the `APP_AUTH_ADMIN_PASSWORD` constant into config before installation. Manually REFRESH this page after by pressing F5 ...';
		} //end if
		if(\SmartAuth::validate_auth_password( // {{{SYNC-AUTH-VALIDATE-PASSWORD}}}
			(string) \APP_AUTH_ADMIN_PASSWORD,
			true
		) !== true) {
			return 'Invalid value set in config for: `APP_AUTH_ADMIN_PASSWORD` ... need to be changed !'."\n".'THE PASSWORD IS TOO SHORT OR DOES NOT MEET THE REQUIRED COMPLEXITY CRITERIA.'."\n".'Must be min 8 chars and max 30 chars.'."\n".'Must contain at least 1 character A-Z, 1 character a-z, one digit 0-9, one special character such as: ! @ # $ % ^ & * ( ) _ - + = [ { } ] / | . , ; ? ...'."\n".'Manually REFRESH this page after by pressing F5 ...';
		} //end if
		//--
		$idb = null;
		//--
		try {
			$idb = new \SmartModDataModel\AuthAdmins\SqAuthAdmins(); // will create + initialize DB if not found
		} catch(\Exception $e) {
			$idb = null;
			return 'AUTH DB Failed to Initialize: `'.$e->getMessage().'`';
		} //end try catch
		//--
		$init_username = (string) \APP_AUTH_ADMIN_USERNAME;
		$init_password = (string) \APP_AUTH_ADMIN_PASSWORD;
		$init_hash_pass = (string) \SmartHashCrypto::password((string)$init_password, (string)$init_username);
		//--
		$init_privileges = (string) '<superadmin>,<admin>';
		$init_privileges = \Smart::list_to_array((string)$init_privileges, true);
		$init_privileges = \Smart::array_to_list((array)$init_privileges);
		//--
		$wr = $idb->insertAccount(
			[
				'id' 	 	=> (string) $init_username,
				'email'  	=> null,
				'pass' 	 	=> (string) $init_hash_pass,
				'name_f' 	=> (string) 'Super',
				'name_l' 	=> (string) 'Admin',
				'priv'   	=> (string) $init_privileges,
				'restrict' 	=> '<modify>',
			],
			1
		);
		//--
		if((int)$wr !== 1) {
			return 'AUTH DB Failed to Create the account for: `'.\APP_AUTH_ADMIN_USERNAME.'`'.print_r($wr,1);
		} //end if
		//--
		$test_init = (int) $idb->countByFilter((string)\APP_AUTH_ADMIN_USERNAME);
		//--
		$idb = null;
		//--
		if((int)$test_init !== 1) {
			return 'AUTH DB Failed to Find the account for: `'.\APP_AUTH_ADMIN_USERNAME.'`';
		} //end if
		//--
		return '';
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
