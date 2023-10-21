<?php
// Class: \SmartModExtLib\AuthAdmins\SmartAuthAdminsHandler
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
 * Smart.Unicorn (Multi-Account) Auth Admins Handler
 * This class provide a complex authentication for admin area (admin.php|task.php) using multi-accounts system with SQLite DB
 *
 * Supports: HTTP Basic Auth ; HTTP Bear Auth (SWT) *optional* ; built-in HTTP Basic Token Auth (STK) *optional*
 *
 * Required constants: APP_AUTH_ADMIN_USERNAME, APP_AUTH_ADMIN_PASSWORD, APP_AUTH_PRIVILEGES (must be set in set in config-admin.php)
 * Required configuration: $configs['app-auth']['adm-namespaces'][ 'Admins Manager' => 'admin.php?page=auth-admins.manager.stml', ... ] (must be set in set in config-admin.php)
 *
 * @version 	v.20231021
 * @package 	development:modules:AuthAdmins
 *
 */
final class SmartAuthAdminsHandler {

	// ::

	private static $is_init_db = false;

	private const TPL_PATH 		= 'modules/mod-auth-admins/templates/';
	private const TPL_FILE 		= 'template.htm';
	private const TPL_INC_PATH 	= 'modules/mod-auth-admins/libs/templates/auth-admins-handler/';
	private const IMG_LOADER   	= 'lib/framework/img/loading-spokes.svg';
	private const IMG_UNICORN  	= 'lib/framework/img/unicorn-auth-logo.svg';
	private const TXT_UNICORN  	= 'Smart.Unicorn Secure Authentication';

	private const AUTH_2FA_COOKIE_NAME = 'Sf_2FA';
	private const AUTH_2FA_REGEX_TOKEN = '/^[0-9]{8}$/';

	private const CAPTCHA_FORM_NAME = 'Smart-Unicorn-Auth';

	//================================================================
	public static function Authenticate(bool $enforce_https=false, bool $disable_tokens=false, bool $disable_2fa=false) : void {

		//--
		if(\headers_sent()) {
			\SmartFrameworkRuntime::Raise500Error('Authentication Failed, Headers Already Sent ...');
			die((string)self::getClassName().':headersSent');
			return;
		} //end if
		//--

		//--
		if(\defined('\\SMART_AUTH_TOKENS_ENABLED')) {
			\SmartFrameworkRuntime::Raise500Error('A required constant should not be already defined: SMART_AUTH_TOKENS_ENABLED !');
			die((string)self::getClassName().':SMART_AUTH_TOKENS_ENABLED');
			return;
		} //end if
		\define('SMART_AUTH_TOKENS_ENABLED', (bool)(!$disable_tokens)); // define has a global scope, no // prefix
		//--
		if(\defined('\\SMART_AUTH_2FA_ENABLED')) {
			\SmartFrameworkRuntime::Raise500Error('A required constant should not be already defined: SMART_AUTH_2FA_ENABLED !');
			die((string)self::getClassName().':SMART_AUTH_2FA_ENABLED');
			return;
		} //end if
		\define('SMART_AUTH_2FA_ENABLED', (bool)(!$disable_2fa)); // define has a global scope, no // prefix
		//--

		//--
		if(!\defined('\\SMART_FRAMEWORK_SECURITY_KEY')) {
			\SmartFrameworkRuntime::Raise500Error('A required constant is missing: SMART_FRAMEWORK_SECURITY_KEY !');
			die((string)self::getClassName().':SMART_FRAMEWORK_SECURITY_KEY:1');
			return;
		} //end if
		if((string)\trim((string)\SMART_FRAMEWORK_SECURITY_KEY) == '') {
			\SmartFrameworkRuntime::Raise500Error('A required constant is empty: SMART_FRAMEWORK_SECURITY_KEY !');
			die((string)self::getClassName().':SMART_FRAMEWORK_SECURITY_KEY:2');
			return;
		} //end if
		//--

		//--
		if(\defined('\\APP_AUTH_DB_SQLITE')) {
			\SmartFrameworkRuntime::Raise500Error('AUTH STORAGE must not be defined outside AdminAuth !');
			die((string)self::getClassName().':APP_AUTH_DB_SQLITE:1');
			return;
		} //end if
		\define('APP_AUTH_DB_SQLITE', '#db/auth-admins-'.\SmartHashCrypto::sha1((string)\SMART_FRAMEWORK_SECURITY_KEY).'.sqlite'); // define inject constants direct in global scope, no need to prefix-slashes
		if(!\defined('\\APP_AUTH_DB_SQLITE')) {
			\SmartFrameworkRuntime::Raise500Error('AUTH STORAGE could not be defined inside AdminAuth !');
			die((string)self::getClassName().':APP_AUTH_DB_SQLITE:2');
			return;
		} //end if
		//--

		//--
		if(\SmartEnvironment::isAdminArea() !== true) {
			\SmartFrameworkRuntime::Raise403Error('Authentication system is designed for admin/task areas only ...');
			die((string)self::getClassName().':NotAdminArea');
			return;
		} //end if
		//--

		//--
		$css_toolkit_ux = '<link rel="stylesheet" type="text/css" href="'.\Smart::escape_html((string)\SmartUtils::get_server_current_url()).'lib/css/toolkit/ux-toolkit.css?'.\Smart::escape_html((string)\SmartUtils::get_app_release_hash()).'" media="all"><link rel="stylesheet" type="text/css" href="'.\Smart::escape_html((string)\SmartUtils::get_server_current_url()).'lib/css/toolkit/ux-toolkit-responsive.css?'.\Smart::escape_html((string)\SmartUtils::get_app_release_hash()).'" media="all">';
		$btn_return_login_screen = '<a class="ux-button ux-button-details" href="'.\Smart::escape_html((string)\SmartUtils::get_server_current_url().\SmartUtils::get_server_current_script()).'">Go&nbsp;Back</a>';
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
				\SmartFrameworkRuntime::Raise502Error('This Web Area require HTTPS'."\n".'Switch from http:// to https:// in order to use this Web Area');
				die((string)self::getClassName().':NotHTTPS');
				return;
			} //end if
		} //end if
		//--

		//--
		if(\defined('\\APP_AUTH_ADMIN_ENCRYPTED_PRIVKEY')) {
			\SmartFrameworkRuntime::Raise208Status(
				'Unset from config: `APP_AUTH_ADMIN_ENCRYPTED_PRIVKEY` !'."\n".'This is not supported by Smart Auth system ...',
				'Unsupported Config Settings'
			);
			die((string)self::getClassName().':UNSUPPORTED-SETTINGS-OVERALL-ENC-PRIVKEY');
			return;
		} //end if
		//--
		if(!\defined('\\APP_AUTH_PRIVILEGES')) { // this must be check always, not only on initDb
			\SmartFrameworkRuntime::Raise503Error('Set in config the `APP_AUTH_PRIVILEGES` constant !'."\n".'The `APP_AUTH_PRIVILEGES` constant is required to run this Authentication plugin ...');
			die((string)self::getClassName().':CHECK-PRIVS');
			return;
		} //end if
		//--

		//--
		$init_1st_time_db = false;
		$init_db = null;
		$init_fa2_key = '';
		$init_fa2_url = '';
		$init_fa2_qrcode = '';
		//--
		if(!\SmartFileSystem::is_type_file((string)\APP_AUTH_DB_SQLITE)) {
			$init_db = (array) self::initDb((bool)$disable_2fa);
		} //end if
		//--
		if(\is_array($init_db)) {
			//--
			if((string)($init_db[0] ?? null) != '') { // 1st elem is the error
				\SmartFrameworkRuntime::Raise503Error((string)($init_db[0] ?? null));
				die((string)self::getClassName().':INIT-FAILED');
			} //end if
			//--
			$init_1st_time_db = true; // just on success, this is the 1st time ; init IP address is checked inside initDb()
			//--
			if($disable_2fa !== true) {
				$init_fa2_key 		= (string) \trim((string)($init_db[1] ?? null)); // 2nd elem is 2fa key
				$init_fa2_url 		= (string) \trim((string)($init_db[2] ?? null)); // 3rd elem is 2fa url
				$init_fa2_qrcode 	= (string) \trim((string)($init_db[3] ?? null)); // 3rd elem is 2fa qrcode (svg)
			} //end if
			//--
		} //end if
		//--
		$init_db = null;
		//--

		//-- do auth except of login page
		$try_auth = 'yes';
		if(isset($_SERVER['PATH_INFO']) AND (!empty($_SERVER['PATH_INFO']))) { // use raw value not from registry, it is safer in the auth context !
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
		// NEW: when 2FA is not explicit disabled, only SWT Token may override the 2FA ; otherwise normal logins will require 2FA !
		// HttpBasicAuth must be preserved to have compatibility with WebDAV login ; Ex: gvfs
		// Thus, implementing Token Auth should be via: HttpBasicAuth + HttpHeaderBearer + Cookie to support all login sub-systems.
		// In order to do this, the trick to combine Token with HttpBasicAuth (for case of gvfs) is to set an user `.token..swt.` (other user accounts cannot start with a dot, checked by validate auth username).
		// If the username is like above, the pass is actually the SWT token which have to be parsed/checked/validated and converted to real user/pass.
		// An idea is to allow HttpBasicAuth just with Tokens and for the rest to have set 2FA (OTP) auth !?
		// Need a system to generate tokens (and also store, for Smart.Unicorn)
		// IDEA: optional disable normal HttpBasicAuth and allow just HttpBasicAuth use with tokens !?
		//-- #END
		$auth_data = (array) \SmartModExtLib\AuthAdmins\AuthProviderHttp::GetCredentials((bool)$enforce_https, (bool)(!$disable_tokens));
		$auth_method = (string) $auth_data['auth-mode'];
		$use_www_auth_prompt = true;
		if(stripos((string)$auth_method, (string)\SmartModExtLib\AuthAdmins\AuthProviderHttp::AUTH_MODE_PREFIX_AUTHEN.\SmartModExtLib\AuthAdmins\AuthProviderHttp::AUTH_MODE_PREFIX_HTTP_BEARER) === 0) { // {{{SYNC-AUTH-PROVIDER-BEARER-SKIP-401-AUTH-PROMPT}}}
			$use_www_auth_prompt = false; // hide the www-auth header for bearer
		} //end if
		$auth_safe = (int) $auth_data['auth-safe'];
		$auth_error = (string) $auth_data['auth-error'];
		//-- validate username
		$auth_user_name = (string) \strtolower((string)$auth_data['auth-user']); // can contain only a-z 0-9 .
		$auth_user_pass = (string) $auth_data['auth-pass']; // plain pass
		$auth_user_hash = (string) $auth_data['auth-hash']; // hash pass ; irreversible hash of pass
		$auth_user_priv = (array)  $auth_data['auth-priv']; // array of privs
		//--
		$auth_stk_error = '';
		$is_stk_token_auth = false;
		if($disable_tokens === false) {
			if((string)\trim((string)$auth_user_hash) == '') { // for normal login only, not for SWT !
				if((string)trim((string)$auth_user_name) != '') {
					if((string)\substr((string)$auth_user_name, -6, 6) == '#token') { // detect STK Tokens Auth as: user.name#token
						$is_stk_token_auth = true;
						$auth_user_name = (string) \substr((string)$auth_user_name, 0, -6);
					} //end if
				} //end if
			} //end if
		} //end if
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
		$hash_of_pass = ''; // {{{SYNC-AUTH-LOGIC-HASH-VS-PASS}}} ; {{{SYNC-AUTH-TOKEN-SWT}}}
		$use_2fa = (bool) ! $disable_2fa;
		if((string)trim((string)$auth_user_name) != '') {
			//--
			if( // SWT Token Auth
				($is_stk_token_auth !== true)
				AND
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
				$use_2fa = false;
				//--
			} elseif($is_stk_token_auth === true) {
				//-- STK Token Auth
				$use_2fa = false;
				$hash_of_pass = (string) $auth_user_pass; // temporary store here, $auth_user_pass is reset below
				//--
			} else {
				//-- Plain Pass (Standard Basic Auth)
				if(\SmartAuth::validate_auth_password( // {{{SYNC-AUTH-VALIDATE-PASSWORD}}}
					(string) $auth_user_pass,
					(bool) ((\defined('\\APP_AUTH_ADMIN_COMPLEX_PASSWORDS') && (\APP_AUTH_ADMIN_COMPLEX_PASSWORDS === true)) ? true : false) // check for complexity just on login ! ... for the rest do not check because if this constant changes ... cannot re-update everything !
				) === true) {
					//-- Normal Login: user + password ; if 2FA is not disabled will require TOTP token via cookie
					$hash_of_pass = (string) \SmartHashCrypto::password((string)$auth_user_pass, (string)$auth_user_name);
					if(\SmartHashCrypto::checkpassword((string)$hash_of_pass, (string)$auth_user_pass, (string)$auth_user_name) !== true) {
						$hash_of_pass = ''; // reset
					} //end if
					//--
				} //end if
				//--
			} //end if else
		} //end if
		//--
		$auth_user_pass = ''; // no more needed !
		$auth_user_hash = ''; // no more needed !
		//--

		//-- manage login or logout
		$login_msg_2fa = '';
		if($use_2fa === true) {
			$login_msg_2fa = ' (2FA)';
		} //end if
		$logged_in = 'no'; // user is not logged in (unsuccessful username or password)
		$login_or_logout_form = (string) \SmartComponents::http_message_401_unauthorized(
			(string) 'Authorization Required'.$login_msg_2fa,
			(string) \SmartComponents::operation_notice('LOGIN FAILED: Either you supplied the wrong credentials or your browser doesn\'t understand how to supply the credentials required.').
						$css_toolkit_ux."\n".
						'<div>'.$btn_return_login_screen.'</div><hr>'."\n".
						'<img width="48" height="48" src="'.\Smart::escape_html((string)\SmartUtils::get_server_current_url().self::IMG_LOADER).'">'.
						'&nbsp;&nbsp;'.
						'<img title="'.\Smart::escape_html((string)self::TXT_UNICORN).'" width="48" height="48" src="'.\Smart::escape_html((string)\SmartUtils::get_server_current_url().self::IMG_UNICORN).'">'."\n".
						'<script>setTimeout(() => { self.location = \''.\Smart::escape_js((string)\SmartUtils::get_server_current_url().\SmartUtils::get_server_current_script()).'\'; }, 3500);</script>'."\n"
		);
		//--
		if(isset($_REQUEST['logout']) AND ((string)$_REQUEST['logout'] != '')) { // do logout
			//--
			\SmartUtils::unset_cookie((string)self::AUTH_2FA_COOKIE_NAME);
			//--
			$login_or_logout_form = (string) \SmartComponents::render_app_template(
				(string) self::TPL_PATH,
				(string) self::TPL_FILE,
				[
					'TITLE' => 'Logout from Admins Area',
					'MAIN'  => (string) \SmartMarkersTemplating::render_file_template(
						(string) self::TPL_INC_PATH.'logout.htm',
						[
							'RELEASE-HASH' 	=> (string) \SmartUtils::get_app_release_hash(),
							'IMG-LOADER' 	=> (string) self::IMG_LOADER,
							'URL-LOGOUT' 	=> (string) \SmartUtils::get_server_current_url().\SmartUtils::get_server_current_script(),
							'TIME-REDIR' 	=> 1500 // 1.5 sec.
						]
					)
				]
			);
			//--
		} elseif((string)$try_auth != 'no') { // requires login ; check login
			//-- open connection to AuthLog DB
			$modelAuthLog = null;
			try {
				$modelAuthLog = new \SmartModDataModel\AuthAdmins\SqAuthLog(); // will create + initialize DB if not found
			} catch(\Exception $e) {
				$modelAuthLog = null;
				\Smart::log_warning(__METHOD__.' # '.'AUTH-LOG DB Failed: `'.$e->getMessage().'`'); // just log the message
				// IMPORTANT: this is not a fatal error, should continue without the Auth Logs DB ; the DB file may be corrupt due huge number of log entries, DDOS conditions or something else ...
			} //end try catch
			//-- try to check the failed logins ; Brute Force / Login DDOS Protection
			$check_fail = 0;
			if($modelAuthLog !== null) {
				$check_fail = (int) $modelAuthLog->checkFailLoginsByIp(
					(string) \SmartUtils::get_ip_client()
				);
			} //end if
			if(
				((int)$check_fail < 0)
				OR
				((int)$check_fail > 0)
			) {
				//--
				$require_captcha = false;
				if((int)$check_fail < 0) {
					$require_captcha = true;
				} //end if
				//--
				$is_captcha_verified = (bool) \SmartCaptcha::verifyCaptcha((string)self::CAPTCHA_FORM_NAME, false, '');
				if($is_captcha_verified === true) {
					$require_captcha = false;
					\SmartCaptcha::clearCaptcha((string)self::CAPTCHA_FORM_NAME, '', false);
					if($modelAuthLog !== null) {
						$modelAuthLog->resetFailedLogins(
							(string) \SmartUtils::get_ip_client(), // client IP
						);
					} //end if
				} //end if
				//--
				if($is_captcha_verified !== true) {
					//--
					$retry_seconds = (int) \strtotime('tomorrow'); // default, for captcha ; this is because after total number of logins match the captcha criteria there are 2 possibilities only: solve captcha or wait until tomorrow 00:00:00 when the DB file resets ...
					if((int)$check_fail > 0) {
						$retry_seconds = (int) $check_fail;
					} //end if
					//--
					$html_429_js = '';
					if($require_captcha === true) {
						$html_429_js = (string) '<div class="operation_info">To UNLOCK the Login Requests for your IP Address earlier, SOLVE the CAPTCHA below and click the `Go Back` button below ...</div>'.\SmartCaptcha::drawCaptchaForm((string)self::CAPTCHA_FORM_NAME, '', '', true, true);
					} else {
						$html_429_js = '<script>setTimeout(() => { self.location = self.location; }, 15000);</script>'; // refresh every 15 sec
					} //end if
					\SmartFrameworkRuntime::outputHttpSafeHeader('Retry-After: '.(int)$retry_seconds);
					\SmartFrameworkRuntime::Raise429Error(
						(string) 'TOO MANY FAILED Login ATTEMPTS For This IP ADDRESS ['.\SmartUtils::get_ip_client().']'."\n".
							'IP Lock TIMEOUT: up to '.((int)$retry_seconds - (int)\time()).' seconds ...'."\n",
						(string) \SmartComponents::operation_result('Retry After DateTime: '.\date('Y-m-d H:i:s O', (int)$retry_seconds))."\n".
							\SmartComponents::operation_notice('Current Server DateTime: '.\Smart::escape_html((string)\date('Y-m-d H:i:s O')))."\n".
							$css_toolkit_ux."\n".
							$html_429_js."\n".
							'<div>'.$btn_return_login_screen.'</div><hr>'."\n".
							'<img width="48" height="48" src="'.\Smart::escape_html((string)\SmartUtils::get_server_current_url().self::IMG_LOADER).'">'.
							'&nbsp;&nbsp;'.
							'<img title="'.\Smart::escape_html((string)self::TXT_UNICORN).'" width="48" height="48" src="'.\Smart::escape_html((string)\SmartUtils::get_server_current_url().self::IMG_UNICORN).'">'
					);
					die((string)self::getClassName().':TOO-MANY-ATTEMPTS');
					return;
					//--
				} //end if
				//--
			} //end if
			$check_fail = null;
			//-- open connection to Admins DB
			$modelAdmins = null;
			try {
				$modelAdmins = new \SmartModDataModel\AuthAdmins\SqAuthAdmins(); // will create + initialize DB if not found
			} catch(\Exception $e) {
				$modelAdmins = null;
				\Smart::log_warning(__METHOD__.' # '.'AUTH DB Failed: `'.$e->getMessage().'`');
				\SmartFrameworkRuntime::Raise504Error('AUTH DB is Unavailable'); // fatal error, can't continue without it
				die((string)self::getClassName().':AUTH-DB-FAILED');
				return;
			} //end try catch
			//--
			if((string)$auth_user_name != '') {
				//-- # STK Token Login Manage
				if($is_stk_token_auth === true) {
					$token_key = (string) $hash_of_pass;
					$hash_of_pass = ''; // security: reset here !
					$check_token = (array) $modelAdmins->getLoginActiveTokenByIdAndKey(
						(string) $auth_user_name,
						(string) $token_key
					);
					if((int)\Smart::array_size($check_token) > 0) { // token found, needs validation
						$valid_token = (array) \SmartModExtLib\AuthAdmins\AuthTokens::validateSTKEncData(
							(string) ($check_token['id'] ?? null),
							(string) ($check_token['token_hash'] ?? null),
							(int)    (int)($check_token['expires'] ?? null),
							(string) ($check_token['token_data'] ?? null)
						);
						if(
							((string)$valid_token['error'] == '')
							AND
							((string)$valid_token['ernum'] == '0')
							AND
							((string)$valid_token['auth-id'] === (string)$auth_user_name)
							AND
							((string)$valid_token['key'] === (string)$token_key)
							AND
							((string)$token_key === (string)\SmartModExtLib\AuthAdmins\AuthTokens::createPublicPassKey((string)$auth_user_name, (string)$valid_token['seed']))
						) { // token is valid
							if( // {{{SYNC-STK-TOKEN-PRIVILEGES}}}
								((string)\trim((string)$valid_token['restr-priv-lst']) != '') // cannot be empty ; must be * or <priv-a>,<priv-b>,...
							) { // token URL is authorized
								$check_token_user_data = (array) $modelAdmins->getById((string)$valid_token['auth-id']);
								if(
									((int)\Smart::array_size($check_token_user_data) > 0)
									AND
									isset($check_token_user_data['id'])
									AND
									((string)\trim((string)$check_token_user_data['id']) != '')
									AND
									((string)$check_token_user_data['id'] === (string)$auth_user_name)
								) {
									//-- !!! at this point all Token login, Token validation and URL validation checks have to be completed and 100% safe
									$hash_of_pass = (string) ($check_token_user_data['pass'] ?? null); // re-init with the account pass hash !
									$auth_user_priv = (array) $valid_token['restr-priv-arr']; // array of STK token privs ; above must be checked that $valid_token['restr-priv-lst'] is non-empty because empty array is accepted only if list of privs = *
									if((int)\Smart::array_size($auth_user_priv) <= 0) { // {{{SYNC-AUTH-TOKENS-EMPTY-PRIVS-PROTECTION}}}
										if((string)$valid_token['restr-priv-lst'] != '*') {
											$auth_user_priv = [ 'invalid' ]; // protection
										} //end if
									} //end if
									//-- !!! #
								} //end if
								$check_token_user_data = null;
							} //end if
						} else {
							$auth_stk_error = '#'.(int)$valid_token['ernum'].' `'.$valid_token['error'].'`';
						} //end if else
						$valid_token = null;
					} //end if
					$check_token = null;
					$token_key = null;
				} //end if
				//-- # end STK
			} //end if
			//-- try to get the user account from DB
			if(((string)$auth_user_name == '') OR ((string)$hash_of_pass == '')) {
				//--
				$admin_login = []; // Invalid Login, Username or Password is empty
				//--
			} else {
				//--
				$admin_login = (array) $modelAdmins->getLoginData(
					(string) $auth_user_name,
					(string) $hash_of_pass
				); // try to login
				//--
			} //end if else
			//--

			//-- test if login is successful
			if(isset($admin_login['id']) AND ((string)$admin_login['id'] != '')) {
				//-- Valid IP break point ; if there is an IP restrictions list, test if current client login is allowed
				$is_ip_valid = true;
				//--
				$admin_ip_restr_login = (string) \trim((string)($admin_login['ip_restr'] ?? null));
				if((string)$admin_ip_restr_login != '') { // if we have a list of restrictions
					//--
					$admin_arr_allowed_ips = (array) self::parseAccountIpRestrictionsList((string)$admin_ip_restr_login);
					//--
					if((int)\Smart::array_size((array)$admin_arr_allowed_ips) > 0) {
						//--
						if(
							(!\in_array((string)\SmartUtils::get_ip_client(), (array)$admin_arr_allowed_ips))
							OR // double check: in array and in list
							(\stripos((string)$admin_ip_restr_login, '<'.\SmartUtils::get_ip_client().'>') === false)
						) { // {{{SYNC-IPV6-STORE-SHORTEST-POSSIBLE}}} ; IPV6 addresses may vary .. find a standard form, ex: shortest
							$is_ip_valid = false; // Invalid Login, the Ip Address is not in the list
						} //end if
						//--
					} else {
						//--
						$is_ip_valid = false; // Invalid Login, the Ip Address List exists but is Invalid (empty parsed array)
						//--
					} //end if else
					//--
					$admin_arr_allowed_ips = null; // free mem
					//--
				} //end if
				$admin_ip_restr_login = null; // free mem
				//-- 2FA break point
				$is_2fa_valid = false;
				if($use_2fa === true) {
					//--
					$cookie_2fa = (string) \trim((string)\SmartUtils::get_cookie((string)self::AUTH_2FA_COOKIE_NAME));
					if(
						((string)$cookie_2fa != '')
						AND
						((int)\strlen((string)$cookie_2fa) <= 128) // hardcoded max length supported to avoid overloads
					) {
						if(strpos((string)$cookie_2fa, '#') === 0) {
							$cookie_2fa = (string) \trim((string)\trim((string)$cookie_2fa, '#'));
							if((string)$cookie_2fa != '') {
								$cookie_2fa = (string) \trim((string)\Smart::base_to_hex_convert((string)$cookie_2fa, 32));
								$cookie_2fa = (string) \trim((string)\SmartUnicode::utf8_to_iso((string)$cookie_2fa)); // safety
								if((string)$cookie_2fa != '') {
									$cookie_2fa = (string) \trim((string)\hex2bin((string)$cookie_2fa));
									if(strpos((string)$cookie_2fa, '#') === 0) {
										$cookie_2fa = (string) \trim((string)\trim((string)$cookie_2fa, '#'));
										if((string)$cookie_2fa != '') {
											if(!preg_match((string)self::AUTH_2FA_REGEX_TOKEN, (string)$cookie_2fa)) { // 2FA token
												$cookie_2fa = ''; // wrong value, expected having the numeric token
											} //end if
										} //end if
									} //end if
								} //end if
							} //end if
						} //end if
					} //end if
					//--
					$is_2fa_valid = false;
					//--
					if((string)$cookie_2fa != '') {
						//--
						$user_hash_2fa = (string) \Smart::b64_to_b64s((string)\SmartHashCrypto::sha256((string)\SmartUtils::client_ident_private_key().'#'.$admin_login['id'].'#'.\date('Y-m-d').'#'.\SmartUtils::get_server_current_basedomain_name().'#'.\SMART_FRAMEWORK_SECURITY_KEY, true)); // the 2FA hash based on client unique signature + date yyyy-mm-dd, so is valid just in that day
						//--
						if(preg_match((string)self::AUTH_2FA_REGEX_TOKEN, (string)$cookie_2fa)) { // 2FA token
							//--
							$user_2fa = (string) \trim((string)$modelAdmins->get2FAGetToken((string)$modelAdmins->decrypt2FAKey((string)$admin_login['fa2'], (string)$admin_login['id'])));
							//-- 2FA over HTTP (not HTTPS) will not work in new browsers, ex: Chromium because it does not support cookie None policy over plain HTTP but just over HTTPS
							if((string)$cookie_2fa === (string)$user_2fa) { // check if 2FA token is valid
								//--
								\SmartUtils::unset_cookie((string)self::AUTH_2FA_COOKIE_NAME); // req. to be sure change policy to: None
								//--
								// IMPORTANT: this cookie is ploicy None / optional HTTPS only ;
								// but is still safe since the value of this cookie is a safe hash based on current visitor IP and Signature
								// so it does not make any importance if leaked on 3rd party websites
								// the reasons of being set with cookie policy None is to work in protected iframes even with Firefox and other modern browsers
								// altought the reason being optional HTTPS only with cookie policy none is because some modern browsers does not allow cookie policy None over plain HTTP but in that case if 2FA is used over plain HTTP only will not be safe in any case and makes no importance !
								//--
								\SmartUtils::set_cookie( // change the 2FA token (expiring) with the 2FA hash
									(string) self::AUTH_2FA_COOKIE_NAME,
									(string) $user_hash_2fa,
									0, // session expire
									'/', // path
									'@', // domain
									'None' // {{{SYNC-COOKIE-POLICY-NONE}}} ; same site policy: None # Safety: OK, the token cookie is bind to visitor ID, incl. IP address, thus is not important if revealed to 3rd party ... # this is a fix (required for iFrame srcdoc, will not send cookies if Lax or Strict on Firefox impl.)
								);
								//--
								$is_2fa_valid = true;
								//--
							} //end if
							//--
						} else { // check if 2FA hash is valid
							//--
							if((string)$cookie_2fa === (string)$user_hash_2fa) {
								$is_2fa_valid = true;
							} //end if
							//--
						} //end if
						//--
					} //end if
					//--
				} //end if
				//-- #end# 2FA break point
				if(
					($is_ip_valid === true) // IP is valid
					AND
					(
						($use_2fa !== true) // 2FA is disabled
						OR
						(($use_2fa === true) AND ($is_2fa_valid === true)) // 2FA is enabled and 2FA token/hash is valid
					)
				) {
					//--
					$logged_in = 'yes'; // user is logged in
					//-- restrictions fix: tokens have `modify` and `account` restriction on login, always
					if(($is_swt_token_auth === true) OR ($is_stk_token_auth === true)) {
						$admin_login['restrict'] = '<def-account>,<account>,<token>'; // {{{SYNC-AUTH-TOKEN-RESTRICTIONS}}} ; {{{SYNC-AUTH-RESTRICTIONS}}} ; {{{SYNC-DEF-ACC-EDIT-RESTRICTION}}} ; {{{SYNC-ACC-NO-EDIT-RESTRICTION}}}
						if((int)\Smart::array_size($auth_user_priv) > 0) { // {{{SYNC-AUTH-TOKEN-PRIVS-INTERSECT}}}
							$admin_login['priv'] = (string) \Smart::array_to_list((array)\array_values((array)\array_intersect((array)\Smart::list_to_array((string)$admin_login['priv'], true), (array)$auth_user_priv))); // {{{SYNC-SWT-IMPLEMENT-PRIVILEGES}}}
						} //end if
						//die(\Smart::escape_html($admin_login['priv']));
					} //end if
					//--
					\SmartAuth::set_login_data( // v.20231018
						'SMART-ADMINS-AREA', // auth realm
						(string) $auth_method, // auth method
						(string) $admin_login['pass'], // auth password hash (will be stored as encrypted, in-memory)
						(string) $admin_login['id'], // auth ID (on backend must be set exact as the auth username)
						(string) $admin_login['id'], // auth user name
						(string) $admin_login['email'], // user email * Optional *
						(string) \trim((string)\trim((string)$admin_login['name_f']).' '.\trim((string)$admin_login['name_l'])), // user full name (First Name + ' ' + Last name) * Optional *
						(string) $admin_login['priv'], // user privileges * Optional *
						(string) $admin_login['restrict'], // user restrictions * Optional *
						(int)    \Smart::format_number_int($admin_login['quota'],'+'), // user quota in MB * Optional * ... zero, aka unlimited
						[ // user metadata (array) ; may vary
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
							'settings' 	=> (string) $admin_login['settings'],
						],
						(string) $modelAdmins->decryptPrivKey((string)$admin_login['keys'], (string)$admin_login['pass']), // user private key (will be stored as encrypted, in-memory) {{{SYNC-ADM-AUTH-KEYS}}}
					);
					//print_r(\SmartAuth::get_login_data()); die();
					//--
					\SmartFrameworkRuntime::SingleUser_Mode_AuthBreakPoint();
					//--
					if($modelAuthLog !== null) {
						$modelAuthLog->logAuthSuccess(
							(string) $admin_login['id'], // successful auth account ID
							(string) \SmartUtils::get_ip_client(), // client IP
							(string) (($is_swt_token_auth === true) ? 'SWT Token: Success' : (($is_stk_token_auth === true) ? 'SWT Token: Success' : 'Username/Password: Success')).' ; '.$auth_method.' ; ['.$auth_safe.']' // message
						);
					} //end if
					//--
				} else {
					//--
					if(
						($is_ip_valid !== true) // IP is not valid
						OR
						(($use_2fa === true) AND ($is_2fa_valid !== true)) // 2FA is enabled and 2FA token/hash is not valid
					) {
						//--
						// ensure the 429 status also for these particular situations:
						// 		* 2FA enabled and invalid
						//			- OR -
						// 		* IP Invalid
						//--
						if($modelAuthLog !== null) {
							$failMsg = (string) 'Auth ERR: '.$auth_error;
							if($is_swt_token_auth === true) {
								$failMsg = 'SWT Token ERR: '.$auth_error;
							} elseif($is_stk_token_auth === true) {
								$failMsg = 'STK Token ERR: '.$auth_stk_error;
							} //end if
							$modelAuthLog->logAuthFail(
								(string) $admin_login['id'], // successful auth account ID
								(string) \SmartUtils::get_ip_client(), // client IP
								'Login FAILED: 2FA or IP Check ; AuthUserName: `'.$auth_user_name.'` ; '.$failMsg.' ; '.$auth_method.' ; ['.$auth_safe.']' // message
							);
							$failMsg = null;
						} //end if
						//--
					} //end if
					//--
				} //end if else
				//--
			} else { // log unsuccessful login
				//--
				// ensure 429 for all the rest of login situations
				//--
				if($modelAuthLog !== null) {
					$failMsg = (string) ($auth_user_name ? 'Username and/or Password does not match' : 'Empty Username and/or Password').' ; ERR: '.$auth_error;
					if($is_swt_token_auth === true) {
						$failMsg = 'Invalid SWT Token ; ERR: '.$auth_error;
					} elseif($is_stk_token_auth === true) {
						$failMsg = 'Invalid or Expired STK Token ; ERR: '.$auth_stk_error;
					} //end if
					$modelAuthLog->logAuthFail(
						(string) $auth_user_name, // successful auth account ID
						(string) \SmartUtils::get_ip_client(), // client IP
						(string) $failMsg.' ; '.$auth_method.' ; ['.$auth_safe.']' // message
					);
					$failMsg = null;
				} //end if
				//--
			} //end if else
			//--
			$modelAdmins = null; // close connection
			$modelAuthLog = null; // close connection
			//--
		} else { // display login form
			//--
			$arr_bw = (array) \SmartComponents::get_imgdesc_by_bw_id((string)\SmartUtils::get_os_browser_ip('bw'));
			//--
			$login_or_logout_form = (string) \SmartComponents::render_app_template(
				(string) self::TPL_PATH,
				(string) self::TPL_FILE,
				[
					'TITLE' => 'Login to '.((\SmartEnvironment::isTaskArea() === true) ? 'Task' : 'Admin').' Area',
					'MAIN'  => (string) \SmartMarkersTemplating::render_file_template(
						(string) self::TPL_INC_PATH.'login.htm',
						[
							'RELEASE-HASH' 	=> (string) \SmartUtils::get_app_release_hash(),
							'USE-2FA' 		=> (string) (($use_2fa === true) ? '1' : '0'),
							'REGEX-2FA' 	=> (string) self::AUTH_2FA_REGEX_TOKEN,
							'COOKIE-N-2FA' 	=> (string) self::AUTH_2FA_COOKIE_NAME,
							'LOGIN-SCRIPT' 	=> (string) ((\SmartEnvironment::isTaskArea() === true) ? 'task.php' : 'admin.php'),
							'LOGIN-URL' 	=> (string)  \SmartUtils::crypto_blowfish_encrypt((string)'#!'.((\SmartEnvironment::isTaskArea() === true) ? 'tsk' : 'adm').'/DISPLAY-REALMS'), // {{{SYNC-AUTH-ADMINS-LOGIN-SCRIPT}}}
							'LOGIN-AREA' 	=> (string) ((\SmartEnvironment::isTaskArea() === true) ? 'Task' : 'Admin'),
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
							'AREA-ID' 		=> (string) (\defined('\\SMART_SOFTWARE_NAMESPACE') ? \SMART_SOFTWARE_NAMESPACE : 'N/A'),
							'CRR-YEAR' 		=> (string) \date('Y'),
							'LOGOUT-URL' 	=> (string) \SmartUtils::get_server_current_url().\SmartUtils::get_server_current_script().'?logout=yes',
							'ID-USER' 		=> (string) $auth_user_name,
							'ID-AUTH-MET' 	=> (string) $auth_method,
							'ID-AUTH-TYP' 	=> (string) ((!!$is_swt_token_auth) ? 'SWT' : ((!!$is_stk_token_auth) ? 'STK' : 'DEF')).$login_msg_2fa,
						]
					),
					'SEMAPHORE' => (string) \Smart::array_to_list([ 'skip:js-ui', 'skip:js-media', 'skip:unveil-js' ]),
				]
			);
			//--
		} //end if else
		//--

		//--
		if(\defined('\\APP_AUTH_ADMIN_INIT_IP_ADDRESS') OR \defined('\\APP_AUTH_ADMIN_USERNAME') OR \defined('\\APP_AUTH_ADMIN_PASSWORD')) {
			//--
			$extra_html = '';
			if(
				(self::$is_init_db === true)
				AND
				($init_1st_time_db === true)
				AND
				((string)\trim((string)$init_fa2_key) != '')
				AND
				((string)\trim((string)$init_fa2_url) != '')
			) {
				$extra_html .= '<br><hr>';
				$extra_html .= '<div style="color:#FF3300;"><b>Before refreshing this page save or scan the FA2 code to be able to login using Two Factor Authentication.</b></div>';
				$extra_html .= '<h5>2FA Setup QRCode to use with <i>FreeOTP App</i> or similar:</h5><div title="'.\Smart::escape_html((string)$init_fa2_url).'">'.$init_fa2_qrcode.'</div>'."\n";
				$extra_html .= '<h6 style="color:#778899">2FA Setup Token (Algorithm=SHA384 ; Digits=8 ; Seconds=30):<br><span style="color:#ECECEC">`'.\Smart::escape_html((string)$init_fa2_key).'`</span></h6>'."\n";
			} //end if
			//--
			$msg_extra_init_type = 'CHECK';
			$msg_extra_init_check = '';
			if($init_1st_time_db === true) {
				$msg_extra_init_type = 'DB';
				$msg_extra_init_check = "\n".'IP Access Check: ['.\Smart::escape_html((string)\SmartUtils::get_ip_client()).']';
			} //end if
			//--
			$msg_extra_init_html = 'REMOVE (UNSET) from CONFIG the following constants: `APP_AUTH_ADMIN_INIT_IP_ADDRESS`, `APP_AUTH_ADMIN_USERNAME`, `APP_AUTH_ADMIN_PASSWORD`.'."\n";
			$msg_extra_init_html .= 'INFO: AFTER the SMART AUTH INITIALIZATION the above constants have to be completely REMOVED (not just commented out) from the CONFIG to avoid the security risk being revealed by mistake or unattended re-initialization of the accounts system.'."\n\n";
			$msg_extra_init_html .= 'Manually REFRESH this page after fixing the config by pressing F5 in your browser ...'.$msg_extra_init_check;
			//--
			$msg_extra_init_title = 'Smart Auth Initialization Completed ['.$msg_extra_init_type.'] ...';
			//--
			if($init_1st_time_db === true) {
				\SmartFrameworkRuntime::Raise202Status(
					(string) $msg_extra_init_html,
					(string) $msg_extra_init_title,
					(string) $extra_html
				);
			} else {
				\SmartFrameworkRuntime::Raise203Status(
					(string) $msg_extra_init_html,
					(string) $msg_extra_init_title,
					(string) $extra_html
				);
			} //end if else
			die((string)self::getClassName().':SAFETY-CHECK-USER-SET');
			return;
			//--
		} //end if
		//--

		//--
		if(((string)$logged_in != 'yes') OR (\SmartAuth::check_login() !== true)) { // IF NOT LOGGED IN
			//--
			if((string)$try_auth != 'no') { // this is optional because on this side will die() anyway ...
				\SmartFrameworkRuntime::Raise401Prompt(
					'Authorization Required'.$login_msg_2fa,
					(string) $login_or_logout_form,
					'Private Area',
					(bool) $use_www_auth_prompt
				);
				die((string)self::getClassName().':401Prompt'.$login_msg_2fa);
				return;
			} //end if
			//--
			\SmartFrameworkRuntime::outputHttpHeadersCacheControl(); // fix: needs no cache headers
			die((string)$login_or_logout_form); // display login or logot form
			//--
		} //end if
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	private static function parseAccountIpRestrictionsList(string $acc_restr_ips) : array {
		//--
		$acc_restr_ips = (string) \trim((string)$acc_restr_ips);
		if((string)$acc_restr_ips == '') {
			return [];
		} //end if
		//--
		$arr_ips = [];
		//--
		$arr_tmp_ips = (array) \Smart::list_to_array((string)$acc_restr_ips);
		foreach($arr_tmp_ips as $key => $val) {
			if((string)\trim((string)\SmartValidator::validate_filter_ip_address((string)$val)) != '') { // if valid IP address
				$val = (string) \trim((string)\Smart::ip_addr_compress((string)$val)); // {{{SYNC-IPV6-STORE-SHORTEST-POSSIBLE}}} ; IPV6 addresses may vary .. find a standard form, ex: shortest
				if((string)$val != '') {
					$arr_ips[] = (string) $val;
				} //end if
			} //end if
		} //end foreach
		$arr_tmp_ips = null;
		//--
		return (array) $arr_ips;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function initDb(bool $disable_2fa) : array {
		//--
		self::$is_init_db = true;
		//--
		if(!\defined('\\APP_AUTH_ADMIN_INIT_IP_ADDRESS')) {
			return [
				'Set in config the `APP_AUTH_ADMIN_INIT_IP_ADDRESS` constant !'."\n".'The `APP_AUTH_ADMIN_INIT_IP_ADDRESS` constant is required to initialize this Authentication plugin ...',
			];
		} //end if
		$init_ip_addr = (string) \Smart::ip_addr_compress((string)\APP_AUTH_ADMIN_INIT_IP_ADDRESS);
		if((string)\trim((string)$init_ip_addr) == '') {
			return [
				'The config value of `APP_AUTH_ADMIN_INIT_IP_ADDRESS` constant is wrong !'."\n".'The current value for `APP_AUTH_ADMIN_INIT_IP_ADDRESS` constant is: `'.\APP_AUTH_ADMIN_INIT_IP_ADDRESS.'` ...',
			];
		} //end if
		if((string)$init_ip_addr != (string)\SmartUtils::get_ip_client()) {
			return [
				'The config value of `APP_AUTH_ADMIN_INIT_IP_ADDRESS` constant is restricting you to access the initialization of this area !'."\n".'Your IP Address is: `'.\SmartUtils::get_ip_client().'` ...',
			];
		} //end if
		//--
		if(!\defined('\\APP_AUTH_ADMIN_USERNAME')) {
			return [
				'Set in config: `APP_AUTH_ADMIN_USERNAME` !'."\n".'You must set the `APP_AUTH_ADMIN_USERNAME` constant in config before installation. Manually REFRESH this page after by pressing F5 ...',
			];
		} //end if
		if(\SmartAuth::validate_auth_username(
			(string) \APP_AUTH_ADMIN_USERNAME,
			true // check for reasonable length, as 5 chars
		) !== true) { // {{{SYNC-AUTH-VALIDATE-USERNAME}}}
			return [
				'Invalid value set in config for: `APP_AUTH_ADMIN_USERNAME` !'."\n".'The `APP_AUTH_ADMIN_USERNAME` set in config must be valid and at least 5 characters long ! Manually REFRESH this page after by pressing F5 ...',
			];
		} //end if
		//--
		if(!\defined('\\APP_AUTH_ADMIN_PASSWORD')) {
			return [
				'Set in config: `APP_AUTH_ADMIN_PASSWORD` !'."\n".'You must set the `APP_AUTH_ADMIN_PASSWORD` constant into config before installation. Manually REFRESH this page after by pressing F5 ...',
			];
		} //end if
		$real_plain_password = (string) \SmartAuth::decrypt_privkey((string)\APP_AUTH_ADMIN_PASSWORD, (string)\APP_AUTH_ADMIN_USERNAME);
		if(\SmartAuth::validate_auth_password( // {{{SYNC-AUTH-VALIDATE-PASSWORD}}}
			(string) $real_plain_password,
			(bool) ((\defined('\\APP_AUTH_ADMIN_COMPLEX_PASSWORDS') && (\APP_AUTH_ADMIN_COMPLEX_PASSWORDS === true)) ? true : false) // check for complexity just on login ! ... for the rest do not check because if this constant changes ... cannot re-update everything !
		) !== true) {
			return [
				'Invalid value set in config for: `APP_AUTH_ADMIN_PASSWORD` ... need to be changed !'."\n".'THE PASSWORD IS TOO SHORT OR DOES NOT MEET THE REQUIRED COMPLEXITY CRITERIA.'."\n".'Must be min 8 chars and max 30 chars.'."\n".'Must contain at least 1 character A-Z, 1 character a-z, one digit 0-9, one special character such as: ! @ # $ % ^ & * ( ) _ - + = [ { } ] / | . , ; ? ...'."\n".'Manually REFRESH this page after by pressing F5 ...',
			];
		} //end if
		//--
		$idb = null;
		//--
		try {
			$idb = new \SmartModDataModel\AuthAdmins\SqAuthAdmins(); // will create + initialize DB if not found
		} catch(\Exception $e) {
			$idb = null;
			return [
				'AUTH DB Failed to Initialize: `'.$e->getMessage().'`', // fatal error, can't continue
			];
		} //end try catch
		//--
		$init_username = (string) \APP_AUTH_ADMIN_USERNAME;
		$init_password = (string) $real_plain_password;
		//--
		$init_privileges = (string) '<superadmin>,<admin>';
		$init_privileges = \Smart::list_to_array((string)$init_privileges, true);
		$init_privileges = \Smart::array_to_list((array)$init_privileges);
		//--
		$wr = $idb->insertAccount(
			[
				'id' 	 	=> (string) $init_username,
				'email'  	=> null,
				'pass' 	 	=> (string) $init_password,
				'name_f' 	=> (string) 'Super',
				'name_l' 	=> (string) 'Admin',
				'priv'   	=> (string) $init_privileges,
				'restrict' 	=> '<def-account>', // {{{SYNC-AUTH-RESTRICTIONS}}} ; {{{SYNC-DEF-ACC-EDIT-RESTRICTION}}} ; it have to be marked as the default account, which is special, can't be deleted or privilege edited / deactivated ; this is like a backup account in case of troubles
			],
			true
		);
		//--
		if((int)$wr !== 1) {
			return [
				'AUTH DB Failed to Create the account for: `'.$init_username.'` [ERR='.(int)$wr.']',
			];
		} //end if
		//--
		$select_user = (array) $idb->getById((string)$init_username);
		//--
		if(
			((int)\Smart::array_size($select_user) <= 0)
			OR
			((string)($select_user['id'] ?? null) != (string)\APP_AUTH_ADMIN_USERNAME)
		) {
			return [
				'AUTH DB Failed to Find the account for: `'.$init_username.'`',
			];
		} //end if
		//--
		$user_2fakey = '';
		$user_2faurl = '';
		$user_2faqrcode = '';
		if($disable_2fa !== true) {
			$user_2fakey = (string) $idb->decrypt2FAKey((string)$select_user['fa2'], (string)$select_user['id']); // {{{SYNC-ADM-AUTH-2FA-MANAGEMENT}}}
			$user_2faurl = (string) $idb->get2FAUrl((string)$user_2fakey, (string)$select_user['id']);
			$user_2faqrcode = (string) $idb->get2FASvgBarCode((string)$user_2fakey, (string)$select_user['id']);
		} //end if
		//--
		return [
			'', 						// ERR or empty
			(string) $user_2fakey, 		// FA2 Key
			(string) $user_2faurl, 		// FA2 URL
			(string) $user_2faqrcode, 	// FA2 Barcode (SVG)
		];
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
