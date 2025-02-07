<?php
// Class: \SmartModExtLib\AuthAdmins\SmartAuthAdminsHandler
// (c) 2008-present unix-world.org - all rights reserved
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
//	* SmartModelAuthAdmins
// 	* \SmartModExtLib\AuthAdmins\AuthTokens

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
 * Required (only init, after dissalowed) constants: APP_AUTH_ADMIN_INIT_IP_ADDRESS, APP_AUTH_ADMIN_USERNAME, APP_AUTH_ADMIN_PASSWORD (must be set in set in config-admin.php only for init, thereafter must be unset)
 * Required constants: APP_AUTH_PRIVILEGES (must be set in set in config-admin.php)
 * Required configuration: $configs['app-auth']['adm-namespaces'][ 'Admins Manager' => 'admin.php?page=auth-admins.manager.stml', ... ] (must be set in set in config-admin.php)
 *
 * @version 	v.20250128
 * @package 	development:modules:AuthAdmins
 *
 */
final class SmartAuthAdminsHandler
	extends \SmartModExtLib\AuthAdmins\AbstractAuthHandler
	implements \SmartModExtLib\AuthAdmins\AuthHandlerInterface {

	// ::

	private static $is_init_db = false;

	private const CAPTCHA_FORM_NAME = 'Smart-Unicorn-Auth';


	//================================================================
	public static function Authenticate() : void {

		//--
		// On FAILED Logins this method should STOP EXECUTION and provide the proper HTTP Status Message: ex: 401, 403, 429, ...
		//--

		//-- {{{SYNC-CHECK-AUTH-ADMINS-MODEL}}}
		if((!\class_exists('\\SmartModelAuthAdmins')) || (!\is_subclass_of('\\SmartModelAuthAdmins', '\\SmartModDataModel\\AuthAdmins\\AbstractAuthAdmins'))) {
			\SmartFrameworkRuntime::Raise208Status(
				'Authentication Model Not Available or Invalid',
				'Authentication is N/A'
			);
			die((string)self::getClassName().':AUTH-ADMINS-MODEL-MISSING-OR-INVALID');
			return;
		} //end if
		//--

		//--
		if((!\class_exists('\\SmartModelAuthLogAdmins')) || (!\is_subclass_of('\\SmartModelAuthLogAdmins', '\\SmartModDataModel\\AuthAdmins\\AbstractAuthLog'))) {
			\SmartFrameworkRuntime::Raise208Status(
				'Authentication Logging Model Not Available or Invalid',
				'Authentication Logging is N/A'
			);
			die((string)self::getClassName().':AUTH-ADMINS-LOG-MODEL-MISSING-OR-INVALID');
			return;
		} //end if
		//--

		//--
		$disable_tokens = (bool) ! \SmartEnvironment::isATKEnabled();
		$disable_2fa    = (bool) ! \SmartEnvironment::is2FAEnabled();
		//--

		//--
		$errPreCheck = (string) self::preCheckForbiddenConditions();
		if((string)$errPreCheck != '') {
			\SmartFrameworkRuntime::Raise403Error((string)$errPreCheck);
			die((string)self::getClassName().'::'.__METHOD__.' # 403 # '.$errPreCheck);
			return;
		} //end if
		//--

		//--
		$errPreCheck = (string) self::preCheckInternalErrorConditions((bool)$disable_tokens, (bool)$disable_2fa);
		if((string)$errPreCheck != '') {
			\SmartFrameworkRuntime::Raise500Error((string)$errPreCheck);
			die((string)self::getClassName().'::'.__METHOD__.' # 500 # '.$errPreCheck);
			return;
		} //end if
		//--

		//--
		$enforce_https = false;
		if(\defined('\\APP_AUTH_ADMIN_ENFORCE_HTTPS')) {
			if(\APP_AUTH_ADMIN_ENFORCE_HTTPS !== false) {
				$enforce_https = true;
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

		//--
		$init_1st_time_db = false;
		$init_db = null;
		//--
		$theDbExists = true; // assume it exists to avoid accidental re-init !
		try {
			$theDbExists = (bool) (new \SmartModelAuthAdmins(false))->dbExists(); // skip init here, to prevent DB initialization if does not exists !
		} catch(\Exception $e) {
			\Smart::log_warning(__METHOD__.' # '.'AUTH STORAGE (Pre-Init / Skip Connect) Get Path Failed: `'.$e->getMessage().'`');
			\SmartFrameworkRuntime::Raise500Error('AUTH STORAGE initialization FAILED !');
			die((string)self::getClassName().':AUTH-STORAGE:INIT');
			return;
		} //end try catch
		if($theDbExists !== true) {
			$init_db = (string) self::initDb((bool)$disable_2fa);
		} //end if
		//--
		if($init_db !== null) {
			//--
			if((string)$init_db != '') { // 1st elem is the error
				\SmartFrameworkRuntime::Raise503Error((string)$init_db);
				die((string)self::getClassName().':INIT-FAILED');
			} //end if
			//--
			$init_1st_time_db = true; // just on success, this is the 1st time ; init IP address is checked inside initDb()
			//--
		} //end if
		//--
		$init_db = null;
		//--

		//-- do auth except of display the login page
		$try_auth = (bool) self::tryAuthGuard();
		//--

		//--
		$use_2fa 	 = (bool) ! $disable_2fa; // init
		$require_2fa = (bool) \SmartEnvironment::is2FARequired(); // init
		//--

		//-- get Auth Credentials
		$auth_data = (array) self::getAuthCredentials((bool)$enforce_https, (bool)$disable_tokens, (bool)$disable_2fa);
		//\Smart::log_notice(print_r($auth_data,1));
		//--
		$auth_mode = (string) $auth_data['auth-mode']; // v2
		$use_www_auth_prompt = (bool) ($auth_data['use-www-401-auth-prompt'] === false) ? false : true; // v2
		$use_2fa = (bool) ($auth_data['use-2fa-auth'] === false) ? false : true; // v2
		//--

		//-- auth data checks
		$auth_valid 	= (bool)   $auth_data['auth-valid']; // v2
		$auth_select 	= (string) $auth_data['auth-select']; // v2
		$auth_error 	= (string) $auth_data['auth-ermsg']; // v2
		$auth_safe 		= (int)    $auth_data['auth-safe']; // v2
		//-- inits
		$auth_user_name = '';
		$auth_user_pass_hash = '';
		$auth_user_arr_ips = [];
		$auth_user_arr_priv = [];
		$is_normal_auth = false;
		$is_swt_token_auth = false;
		$is_stk_token_auth = false;
		//--

		//-- step #1 checks
		if(
			($auth_valid === true)
			AND
			((string)$auth_error == '')
			AND
			((int)$auth_safe > 0)
			AND
			((string)$auth_select != '')
			AND
			\array_key_exists((string)$auth_select, (array)$auth_data) // array key exists
			AND
			\is_array($auth_data[(string)$auth_select]) // is array
			AND
			((int)\Smart::array_size($auth_data[(string)$auth_select]) > 0) // is non-empty
			AND
			((int)\Smart::array_type_test($auth_data[(string)$auth_select]) === 2) // is an associative array
		) {
			//--
			switch((string)$auth_select) {
				//--
				case 'stk-token':
					//--
					if(
						\array_key_exists('is-valid', (array)$auth_data[(string)$auth_select])
						AND
						\array_key_exists('error-msg', (array)$auth_data[(string)$auth_select])
						AND
						\array_key_exists('user-name', (array)$auth_data[(string)$auth_select])
						AND
						\array_key_exists('pass-hash', (array)$auth_data[(string)$auth_select])
						AND
						\array_key_exists('token-key', (array)$auth_data[(string)$auth_select])
						AND
						\array_key_exists('token-data', (array)$auth_data[(string)$auth_select])
						AND
						($auth_data[(string)$auth_select]['is-valid'] === true)
						AND
						((string)$auth_data[(string)$auth_select]['error-msg'] == '')
					) {
						//--
						if(
							((string)\trim((string)$auth_data[(string)$auth_select]['user-name']) != '')
							AND
							((string)$auth_data[(string)$auth_select]['pass-hash'] == '')
							AND
							((string)\trim((string)$auth_data[(string)$auth_select]['token-key']) != '')
							AND
							(\SmartAuth::validate_auth_username(
								(string) $auth_data[(string)$auth_select]['user-name'],
								true // check for reasonable length, as 5 chars
							) === true)
							AND
							( // {{{SYNC-VALIDATE-STK-TOKEN-LENGTH}}} ; to validate Token Key, see: \SmartModExtLib\AuthAdmins\AuthTokens::createPublicPassKey()
								((int)\strlen((string)$auth_data[(string)$auth_select]['token-key']) >= 42)
								AND // token key should be between 42 and 46 characters ; sha256.B58
								((int)\strlen((string)$auth_data[(string)$auth_select]['token-key']) <= 46)
								AND
								((int)\strlen((string)$auth_data[(string)$auth_select]['token-key']) === (int)\strspn((string)$auth_data[(string)$auth_select]['token-key'], (string)\Smart::CHARSET_BASE_58)) // B58 valid chars only
							)
						) {
							//--
							$auth_user_name = (string) $auth_data[(string)$auth_select]['user-name'];
							//--
							$auth_user_pass_hash = (string) $auth_data[(string)$auth_select]['token-key']; // ! this must be replaced later with the real pass hash, after having a DB connection to real validate this STK Token !
							//--
							$is_stk_token_auth = true;
							//--
						} //end if
						//--
					} //end if
					//--
					break;
					//--
				case 'user-pass':
					//--
					if(
						\array_key_exists('is-valid', (array)$auth_data[(string)$auth_select])
						AND
						\array_key_exists('error-msg', (array)$auth_data[(string)$auth_select])
						AND
						\array_key_exists('user-name', (array)$auth_data[(string)$auth_select])
						AND
						\array_key_exists('pass-hash', (array)$auth_data[(string)$auth_select])
						AND
						($auth_data[(string)$auth_select]['is-valid'] === true)
						AND
						((string)$auth_data[(string)$auth_select]['error-msg'] == '')
					) {
						//--
						if(
							((string)\trim((string)$auth_data[(string)$auth_select]['user-name']) != '')
							AND
							((string)\trim((string)$auth_data[(string)$auth_select]['pass-hash']) != '')
							AND
							(\SmartAuth::validate_auth_username(
								(string) $auth_data[(string)$auth_select]['user-name'],
								true // check for reasonable length, as 5 chars
							) === true)
							AND
							(\SmartAuth::validate_auth_password( // {{{SYNC-AUTH-VALIDATE-PASSWORD}}}
								(string) $auth_data[(string)$auth_select]['pass-hash'],
								(bool) ((\defined('\\APP_AUTH_ADMIN_COMPLEX_PASSWORDS') && (\APP_AUTH_ADMIN_COMPLEX_PASSWORDS === true)) ? true : false) // check for complexity as set in configs
							) === true)
						) {
							//--
							$auth_user_name = (string) $auth_data[(string)$auth_select]['user-name'];
							//--
							$auth_user_pass_hash = (string) \SmartHashCrypto::password( // create it, based on the provided user plain password ...
								(string) $auth_data[(string)$auth_select]['pass-hash'], // this is the plain pass, in this case, auth: user/pass
								(string) $auth_user_name
							);
							if(
								(\SmartHashCrypto::validatepasshashformat((string)$auth_user_pass_hash) !== true)
								OR
								(\SmartHashCrypto::checkpassword( // an extra security check, check if the hash is correct against trimmed username and password
									(string) \trim((string)$auth_data[(string)$auth_select]['pass-hash']),
									(string) $auth_user_pass_hash,
									(string) \trim((string)$auth_user_name)
								) !== true)
							) {
								$auth_user_pass_hash = ''; // reset, it does not match the trimmed variant of username / password !
							} //end if
							//-- final check
							if((string)\trim((string)$auth_user_name) !== '') {
								if((string)\trim((string)$auth_user_pass_hash) !== '') {
									$is_normal_auth = true;
								} //end if
							} //end if
							//--
						} //end if
						//--
					} //end if
					//--
					break;
					//--
				//--
				case 'swt-token':
					//--
					if(
						\array_key_exists('is-valid', (array)$auth_data[(string)$auth_select])
						AND
						\array_key_exists('error-msg', (array)$auth_data[(string)$auth_select])
						AND
						\array_key_exists('user-name', (array)$auth_data[(string)$auth_select])
						AND
						\array_key_exists('pass-hash', (array)$auth_data[(string)$auth_select])
						AND
						\array_key_exists('token-key', (array)$auth_data[(string)$auth_select])
						AND
						\array_key_exists('token-data', (array)$auth_data[(string)$auth_select])
						AND
						\array_key_exists('restr-ip', (array)$auth_data[(string)$auth_select])
						AND
						\array_key_exists('restr-priv', (array)$auth_data[(string)$auth_select])
						AND
						($auth_data[(string)$auth_select]['is-valid'] === true)
						AND
						((string)$auth_data[(string)$auth_select]['error-msg'] == '')
					) {
						//--
						if(
							((string)\trim((string)$auth_data[(string)$auth_select]['token-key']) != '')
							AND
							((int)\Smart::array_size($auth_data[(string)$auth_select]['token-data']) > 0)
							AND
							((int)\Smart::array_type_test($auth_data[(string)$auth_select]['token-data']) === 2) // expects an associative array
							AND
							\array_key_exists('error', (array)$auth_data[(string)$auth_select]['token-data'])
							AND
							($auth_data[(string)$auth_select]['token-data']['error'] === '')
							AND
							\array_key_exists('json-arr', (array)$auth_data[(string)$auth_select]['token-data'])
							AND
							((int)\Smart::array_size($auth_data[(string)$auth_select]['token-data']['json-arr']) > 0)
							AND
							((int)\Smart::array_type_test($auth_data[(string)$auth_select]['token-data']['json-arr']) === 2) // expects an associative array
							AND
							\array_key_exists('#', (array)$auth_data[(string)$auth_select]['token-data']['json-arr'])
							AND
							($auth_data[(string)$auth_select]['token-data']['json-arr']['#'] === \SmartAuth::SWT_VERSION_SIGNATURE) // validate version, just in case ...
							AND
							\array_key_exists('r', (array)$auth_data[(string)$auth_select]['token-data']['json-arr'])
							AND
							($auth_data[(string)$auth_select]['token-data']['json-arr']['r'] === 'A') // validate realm, just in case ...
							AND
							\array_key_exists('n', (array)$auth_data[(string)$auth_select]['token-data']['json-arr'])
							AND
							($auth_data[(string)$auth_select]['token-data']['json-arr']['n'] === \SMART_SOFTWARE_NAMESPACE) // validate namespace, just in case ...
							AND
							\array_key_exists('user-name', (array)$auth_data[(string)$auth_select]['token-data'])
							AND
							((string)$auth_data[(string)$auth_select]['token-data']['user-name'] != '')
							AND
							((string)$auth_data[(string)$auth_select]['token-data']['user-name'] == (string)$auth_data[(string)$auth_select]['user-name'])
							AND
							\array_key_exists('pass-hash', (array)$auth_data[(string)$auth_select]['token-data'])
							AND
							((int)\strlen((string)$auth_data[(string)$auth_select]['token-data']['pass-hash']) == (int)\SmartHashCrypto::PASSWORD_HASH_LENGTH)
							AND
							((string)$auth_data[(string)$auth_select]['token-data']['pass-hash'] == (string)$auth_data[(string)$auth_select]['pass-hash'])
							AND
							((int)\Smart::array_size($auth_data[(string)$auth_select]['restr-ip']) > 0) // this is mandatory for a SWT Token
							AND
							((int)\Smart::array_type_test($auth_data[(string)$auth_select]['restr-ip']) === 1) // needs to be a non-associative array list with IP Addresses
							AND
							((int)\Smart::array_size($auth_data[(string)$auth_select]['restr-priv']) > 0) // this is mandatory for a SWT Token
							AND
							((int)\Smart::array_type_test($auth_data[(string)$auth_select]['restr-priv']) === 1) // needs to be a non-associative array list with Privileges Restrictions
							AND
							((string)\trim((string)$auth_data[(string)$auth_select]['user-name']) != '')
							AND
							((string)\trim((string)$auth_data[(string)$auth_select]['pass-hash']) != '')
							AND
							(\SmartAuth::validate_auth_username(
								(string) $auth_data[(string)$auth_select]['user-name'],
								true // check for reasonable length, as 5 chars
							) === true)
							AND
							(\SmartHashCrypto::validatepasshashformat((string)$auth_data[(string)$auth_select]['pass-hash']) === true)
						) {
							//--
							$swt_validate = (array) \SmartAuth::swt_token_validate( // 2nd round validation, for safety ; this was already done in pre-validation, but for security standards, double validation of non-opaque tokens in the code is wellcome because if code changes and have a bug at any of step 1 or two will at least block logins instead having a security breach !
								(string) $auth_data[(string)$auth_select]['token-key'], // swt token via Auth Bearer
								(string) \SmartUtils::get_ip_client(), // client's current IP Address
							);
							//--
							if($swt_validate['error'] === '') {
								//--
								$auth_user_name = (string) $auth_data[(string)$auth_select]['user-name'];
								//--
								$auth_user_pass_hash = (string) $auth_data[(string)$auth_select]['pass-hash']; // a validated SWT Token provides a valid format (as expected) password hash, consider it safe, it was previous validated inside getAuthCredentials(), with no errors/warnings (as checked above)
								//--
								$auth_user_arr_ips = (array) $auth_data[(string)$auth_select]['restr-ip'];
								//--
								$auth_user_arr_priv = [];
								if(\in_array('*', (array) $auth_data[(string)$auth_select]['restr-priv'])) { // {{{SYNC-TOKEN-AUTH-WILDCARD-PRIVS}}}
									$auth_user_arr_priv = ['*'];
								} else {
									$auth_user_arr_priv = (array) \SmartAuth::safe_arr_privileges_or_restrictions((array)$auth_data[(string)$auth_select]['restr-priv'], true);
								} //end if else
								//--
								if((string)\trim((string)$auth_user_name) !== '') {
									if((string)\trim((string)$auth_user_pass_hash) !== '') {
										if( // the `$auth_user_arr_ips` and `$auth_user_arr_priv` must be provided for step #2 checks
											((int)\Smart::array_size($auth_user_arr_ips) > 0) // a SWT Token must have at least one IP Address Restriction ; if it does not, something went wrong
											AND
											((int)\Smart::array_type_test($auth_user_arr_ips) === 1) // non-associative
											AND
											((int)\Smart::array_size($auth_user_arr_priv) > 0) // a SWT Token must have at least one Privilege Restriction ; if it does not, something went wrong
											AND
											((int)\Smart::array_type_test($auth_user_arr_priv) === 1) // non-associative
										) {
											$is_swt_token_auth = true;
										} //end if
									} //end if
								} //end if
								//--
							} //end if
							//--
						} //end if
						//--
					} //end if
					//--
					break;
					//--
				default: // invalid !
					//--
					die('Auth Mode Not Yet Implemented, or Invalid');
					//--
			} //end switch
			//--
		} //end if
		//--

		//--
		$css_toolkit_ux = '<link rel="stylesheet" type="text/css" href="'.\Smart::escape_html((string)\SmartUtils::get_server_current_url()).'lib/css/toolkit/ux-toolkit.css?'.\Smart::escape_html((string)\SmartUtils::get_app_release_hash()).'" media="all"><link rel="stylesheet" type="text/css" href="'.\Smart::escape_html((string)\SmartUtils::get_server_current_url()).'lib/css/toolkit/ux-toolkit-responsive.css?'.\Smart::escape_html((string)\SmartUtils::get_app_release_hash()).'" media="all">';
		$btn_return_login_screen = '<a class="ux-button ux-button-details" href="'.\Smart::escape_html((string)\SmartUtils::get_server_current_url().\SmartUtils::get_server_current_script()).'">Go&nbsp;Back</a>';
		//--

		//-- manage login or logout
		$login_msg_2fa = '';
		if($use_2fa === true) {
			$login_msg_2fa = ' (2FA)';
		} //end if
		$logged_in = false; // user is not logged in (unsuccessful username or password)
		//--

		//-- this is storing the login/logout/401-prompt special page response content
		$stopper_page = null; // initialize as null ! not empty string ... below checks for null
		//--

		//-- step #2 checks
		if(self::isAuthLogout() === true) { // do logout
			//--
			\SmartUtils::unset_cookie((string)self::AUTH_2FA_COOKIE_NAME);
			//--
			$stopper_page = (string) self::renderAuthLogoutPage();
			//--
		} elseif($try_auth !== false) { // requires login ; check login
			//-- open connection to AuthLog DB
			$modelAuthLog = null;
			try {
				$modelAuthLog = new \SmartModelAuthLogAdmins(); // will create + initialize DB if not found
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
						$html_429_js = (string) '<div class="operation_info">To UNLOCK the Sign-In Requests for your IP Address earlier, SOLVE the CAPTCHA below and click the `Go Back` button below ...</div>'.\SmartCaptcha::drawCaptchaForm((string)self::CAPTCHA_FORM_NAME, '', '', true, true);
					} else {
						$html_429_js = '<script>setTimeout(() => { self.location = self.location; }, 15000);</script>'; // refresh every 15 sec
					} //end if
					\SmartFrameworkRuntime::outputHttpSafeHeader('Retry-After: '.(int)$retry_seconds);
					\SmartFrameworkRuntime::Raise429Error(
						(string) 'TOO MANY FAILED Sign-In ATTEMPTS For This IP ADDRESS ['.\SmartUtils::get_ip_client().']'."\n".
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
				$modelAdmins = new \SmartModelAuthAdmins(); // will create + initialize DB if not found
			} catch(\Exception $e) {
				$modelAdmins = null;
				\Smart::log_warning(__METHOD__.' # '.'AUTH DB Failed: `'.$e->getMessage().'`');
				\SmartFrameworkRuntime::Raise504Error('AUTH DB is Unavailable'); // fatal error, can't continue without it
				die((string)self::getClassName().':AUTH-DB-FAILED');
				return;
			} //end try catch
			//-- manage STK Token Validation ; if valid, get the real password hash
			$the_stk_token_is_really_valid = false;
			if(
				((string)\trim((string)$auth_user_name) != '')
				AND
				((string)\trim((string)$auth_user_pass_hash) != '')
			) {
				if($is_stk_token_auth === true) {
					//--
					$check_stk_token = (array) $modelAdmins->getLoginActiveTokenByIdAndKey(
						(string) $auth_user_name,
						(string) $auth_user_pass_hash
					);
					//--
					if((int)\Smart::array_size($check_stk_token) > 0) { // token found, needs validation
						//--
						$validate_stk_token = (array) \SmartModExtLib\AuthAdmins\AuthTokens::validateSTKEncData(
							(string) ($check_stk_token['id'] ?? null),
							(string) ($check_stk_token['token_hash'] ?? null),
							(int)    (int)($check_stk_token['expires'] ?? null),
							(string) ($check_stk_token['token_data'] ?? null)
						);
						//--
						if(
							($validate_stk_token['error'] === '')
							AND
							($validate_stk_token['ernum'] === 0)
							AND
							((string)\trim((string)$validate_stk_token['auth-id']) != '')
							AND
							((string)$validate_stk_token['auth-id'] === (string)$auth_user_name)
							AND
							((string)\trim((string)$validate_stk_token['key']) != '')
							AND
							((string)$validate_stk_token['key'] === (string)$auth_user_pass_hash)
							AND
							((string)\trim((string)$validate_stk_token['seed']) != '')
							AND
							((string)$auth_user_pass_hash === (string)\SmartModExtLib\AuthAdmins\AuthTokens::createPublicPassKey((string)$auth_user_name, (string)$validate_stk_token['seed']))
							AND
							((int)\Smart::array_size($validate_stk_token['restr-priv']) > 0) // must have at least one privilege to have a valid login
						) { // token is valid
							//--
							$data_stk_user = (array) $modelAdmins->getById((string)$validate_stk_token['auth-id']);
							//--
							if((int)\Smart::array_size($data_stk_user) > 0) {
								if((string)($data_stk_user['id'] ?? null) === (string)$auth_user_name) { // validate again the username, be sure the retrieved account data match
									//-- STK: ALL OK ...
									$the_stk_token_is_really_valid = true;
									$auth_user_pass_hash = (string) ($data_stk_user['pass'] ?? null); // everything is ok, assign the real pass hash to the already verified user for the already verified opaque token STK
									if(\in_array('*', (array)$validate_stk_token['restr-priv'])) { // {{{SYNC-TOKEN-AUTH-WILDCARD-PRIVS}}}
										$auth_user_arr_priv = ['*'];
									} else {
										$auth_user_arr_priv = (array) \SmartAuth::safe_arr_privileges_or_restrictions((array)$validate_stk_token['restr-priv'], true); // pass the restricted privileges as they are bind to this STK Token
									} //end if else
									//--
								} //end if else
							} //end if else
							//--
							$data_stk_user = null; // reset
							//--
						} //end if
						//--
						$validate_stk_token = null; // reset
						//--
					} //end if
					//--
					$check_stk_token = null; // reset
					//--
					if($the_stk_token_is_really_valid !== true) {
						$auth_error = 'STK Token Validation Failed';
						$auth_user_pass_hash = ''; // reset ; token is invalid ; if this is empty will skip lookup in accounts ...
					} //end if
					//--
				} //end if
			} //end if
			//-- try to get the user account from DB
			$account_data = null; // by default, consider it is an INVALID Sign-In, having Username and/or Password empty !
			//--
			if(
				((string)\trim((string)$auth_user_name) != '')
				AND
				((string)\trim((string)$auth_user_pass_hash) != '')
			) { // if the combination of userName/passHash is not empty, try to read login data just ; if does not match a valid/active account will return an empty array
				//--
				$account_data = (array) $modelAdmins->getLoginData(
					(string) $auth_user_name,
					(string) $auth_user_pass_hash
				); // try to login
				//--
			} else { // username or pass is empty, SKIP
				//--
				$account_data = [];
				//--
			} //end if else
			//-- Valid Login breakpoint ; test if login is successful
			$is_login_data_valid = (bool) self::isAuthLoginValid(
				(string) $auth_user_name,
				(string) $auth_user_pass_hash,
				(array)  $account_data
			);
			//--
			if($is_login_data_valid === true) {
				//-- init
				$is_ip_valid = false;
				//-- Valid IP break point ; if there is an IP restrictions list, test if current client login is allowed
				$is_ip_valid = (bool) self::isAuthIPAddressValid(
					(string) \trim((string)($account_data['ip_restr'] ?? null))
				);
				if($is_ip_valid) {
					if(\Smart::array_size($auth_user_arr_ips) > 0) { // double check, Client IP Validation
						$is_ip_valid = (bool) \in_array('*', (array)$auth_user_arr_ips); // first try
						if(!$is_ip_valid) {
							$is_ip_valid = (bool) \in_array((string)\SmartUtils::get_ip_client(), (array)$auth_user_arr_ips);
						} //end if
					} else { // do not set as invalid !
						// this case is used for common basic auth ...
					} //end if
				} //end if
				//-- 2FA break point
				$is_2fa_valid = true;
				if(($use_2fa === true) AND (($require_2fa === true) || ((string)($account_data['fa2'] ?? null) != ''))) { // optional use 2FA, only if enabled for this account
					$is_2fa_valid = (bool) self::isAuth2FAValid(
						(string) ($account_data['id'] ?? null),
						(bool)   $use_2fa,
						(string) $modelAdmins->get2FAPinToken((string)$modelAdmins->decrypt2FAKey((string)($account_data['fa2'] ?? null), (string)($account_data['id'] ?? null)))
					);
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
					AND
					(
						($is_normal_auth === true)
						OR
						($is_swt_token_auth === true)
						OR
						(($is_stk_token_auth === true) && ($the_stk_token_is_really_valid === true))
					)
				) { // SUCCESSFUL login ; at this step, the login is secure enough (by checks) to be considered valid
					//--
					$logged_in = true; // FLAG: user is logged in !
					//-- restrictions fix: tokens have `modify` and `account` restriction on login, always
					if(($is_swt_token_auth === true) OR ($is_stk_token_auth === true)) {
						//--
						$account_data['restrict'] = (string) self::AUTH_VIA_TOKEN_ENFORCED_RESTRICTIONS_LIST; // if logged in with a Token (SWT or STK) these are the restrictions which are enforced ; {{{SYNC-AUTH-TOKEN-RESTRICTIONS}}} ; {{{SYNC-AUTH-RESTRICTIONS}}} ; {{{SYNC-DEF-ACC-EDIT-RESTRICTION}}} ; {{{SYNC-ACC-NO-EDIT-RESTRICTION}}}
						if($auth_user_arr_priv !== ['*']) { // if not wildcard privileges in the token, use only the valid ones from the token
							$account_data['priv'] = (string) \Smart::array_to_list((array)\array_values(
								(array) \array_intersect( // {{{SYNC-AUTH-TOKEN-PRIVS-INTERSECT}}}
									(array) \Smart::list_to_array((string)$account_data['priv']),
									(array) $auth_user_arr_priv
								)
							)); // {{{SYNC-SWT-IMPLEMENT-PRIVILEGES}}}
						} //end if
						//--
						//die(\Smart::escape_html($account_data['priv']));
					} //end if
					//--
					$passalgo = (int) \SmartAuth::ALGO_PASS_SMART_SAFE_SF_PASS;
					if($is_swt_token_auth === true) {
						$passalgo = (int) \SmartAuth::ALGO_PASS_SMART_SAFE_SWT_TOKEN;
					} else if($is_stk_token_auth === true) {
						$passalgo = (int) \SmartAuth::ALGO_PASS_SMART_SAFE_OPQ_TOKEN;
					} //end if
					//--
					\SmartAuth::set_auth_data( // v.20250124
						'SMART-ADMINS-AREA', // auth realm
						(string) $auth_mode, // auth method
						(int)    $passalgo, // pass algo
						(string) $account_data['pass'], // auth password hash (will be stored as encrypted, in-memory)
						(string) $account_data['id'], // auth user name
						(string) $account_data['id'], // auth ID (on backend must be set exact as the auth username)
						(string) $account_data['email'], // user email * Optional *
						(string) \trim((string)\trim((string)$account_data['name_f']).' '.\trim((string)$account_data['name_l'])), // user full name (First Name + ' ' + Last name) * Optional *
						(string) $account_data['priv'], // user privileges * Optional *
						(string) $account_data['restrict'], // user restrictions * Optional *
						(array)  [ // {{{SYNC-AUTH-KEYS}}}
							'privkey' => (string) $modelAdmins->decryptPrivKey((string)$account_data['keys'], (string)$account_data['pass']), // user private key (will be stored as encrypted, in-memory) {{{SYNC-ADM-AUTH-KEYS}}}
							// TODO: store keys in a json to be able to store also pubkey and security key
						], // keys
						(int)    \Smart::format_number_int($account_data['quota'],'+'), // user quota in MB * Optional * ... zero, aka unlimited
						[ // user metadata (array) ; may vary
							'auth-safe' => (int)    $auth_safe,
							'title' 	=> (string) $account_data['title'],
							'name_f' 	=> (string) $account_data['name_f'],
							'name_l' 	=> (string) $account_data['name_l'],
							'address' 	=> (string) $account_data['address'],
							'zip' 		=> (string) $account_data['zip'],
							'city' 		=> (string) $account_data['city'],
							'region' 	=> (string) $account_data['region'],
							'country' 	=> (string) $account_data['country'],
							'phone' 	=> (string) $account_data['phone'],
							'settings' 	=> (array)  \Smart::json_decode((string)$account_data['settings'], true, 7), // max 7 levels ; {{{SYNC-AUTH-METADATA-MAX-LEVELS}}}
						]
					);
					//die('<pre>'.\Smart::escape_html(\SmartUtils::pretty_print_var(\SmartAuth::get_auth_data())).'</pre>');
					//--
					\SmartFrameworkRuntime::SingleUser_Mode_AuthBreakPoint();
					//--
					if($modelAuthLog !== null) {
						$modelAuthLog->logAuthSuccess(
							(string) $account_data['id'], // successful auth account ID
							(string) \SmartUtils::get_ip_client(), // client IP
							(string) (($is_swt_token_auth === true) ? 'SWT Token: Success' : (($is_stk_token_auth === true) ? 'SWT Token: Success' : 'Username/Password: Success')).' ; '.$auth_mode.' ; ['.$auth_safe.']' // message
						);
					} //end if
					//--
				} else { // log unsuccessful login
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
								$failMsg = 'STK Token ERR: '.$auth_error;
							} //end if
							$modelAuthLog->logAuthFail(
								(string) $account_data['id'], // successful auth account ID
								(string) \SmartUtils::get_ip_client(), // client IP
								'Sign-In FAILED: 2FA or IP Check ; AuthUserName: `'.$auth_user_name.'` ; '.$failMsg.' ; '.$auth_mode.' ; ['.$auth_safe.']' // message
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
						$failMsg = 'Invalid or Expired STK Token ; ERR: '.$auth_error;
					} //end if
					$modelAuthLog->logAuthFail(
						(string) $auth_user_name, // successful auth account ID
						(string) \SmartUtils::get_ip_client(), // client IP
						(string) $failMsg.' ; '.$auth_mode.' ; ['.$auth_safe.']' // message
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
			$stopper_page = (string) self::renderAuthLoginPage(
				(bool)   $disable_2fa,
				(string) $auth_user_name,
				(string) $auth_mode,
				(string) ((!!$is_swt_token_auth) ? 'SWT' : ((!!$is_stk_token_auth) ? 'STK' : 'DEF')).$login_msg_2fa
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
				($disable_2fa !== true)
			) {
				$extra_html .= '<br><hr>';
				$extra_html .= '<div style="color:#FF3300;"><b>After the first login you can to enable the Two Factor Authentication for your account.</b></div>'."\n";
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
		if(($logged_in !== true) OR (\SmartAuth::is_authenticated() !== true)) { // IF NOT LOGGED IN
			//--
			if($stopper_page === null) {
				$stopper_page = (string) \SmartComponents::http_message_401_unauthorized(
					(string) 'Authorization Required'.$login_msg_2fa,
					(string) \SmartComponents::operation_notice('Sign-In FAILED: Either you supplied the wrong credentials or your browser doesn\'t understand how to supply the credentials required.').
								$css_toolkit_ux."\n".
								'<div>'.$btn_return_login_screen.'</div><hr>'."\n".
								'<img width="48" height="48" src="'.\Smart::escape_html((string)\SmartUtils::get_server_current_url().self::IMG_LOADER).'">'.
								'&nbsp;&nbsp;'.
								'<img title="'.\Smart::escape_html((string)self::TXT_UNICORN).'" width="48" height="48" src="'.\Smart::escape_html((string)\SmartUtils::get_server_current_url().self::IMG_UNICORN).'">'."\n".
								'<script>setTimeout(() => { self.location = \''.\Smart::escape_js((string)\SmartUtils::get_server_current_url().\SmartUtils::get_server_current_script()).'\'; }, 3500);</script>'."\n"
				);
			} //end if
			//--
			if($try_auth !== false) { // this is optional because on this side will die() anyway ...
				\SmartFrameworkRuntime::Raise401Prompt(
					'Authorization Required'.$login_msg_2fa,
					(string) $stopper_page,
					'Private Area',
					(bool) $use_www_auth_prompt
				);
				die((string)self::getClassName().':401Prompt'.$login_msg_2fa);
				return;
			} //end if
			//--
			if(!\headers_sent()) {
				\SmartFrameworkRuntime::outputHttpHeadersCacheControl(); // fix: needs no cache headers
			} else {
				\Smart::log_warning(__METHOD__.' # Headers Already Sent ...');
			} //end if
			die((string)$stopper_page); // display login or logout form
			//--
		} //end if
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	private static function initDb(bool $disable_2fa) : string {
		//--
		self::$is_init_db = true;
		//--
		if(!\defined('\\APP_AUTH_ADMIN_INIT_IP_ADDRESS')) {
			return 'Set in config the `APP_AUTH_ADMIN_INIT_IP_ADDRESS` constant !'."\n".'The `APP_AUTH_ADMIN_INIT_IP_ADDRESS` constant is required to initialize this Authentication plugin ...';
		} //end if
		$init_ip_addr = (string) \Smart::ip_addr_compress((string)\APP_AUTH_ADMIN_INIT_IP_ADDRESS);
		if((string)\trim((string)$init_ip_addr) == '') {
			return 'The config value of `APP_AUTH_ADMIN_INIT_IP_ADDRESS` constant is wrong !'."\n".'The current value for `APP_AUTH_ADMIN_INIT_IP_ADDRESS` constant is: `'.\APP_AUTH_ADMIN_INIT_IP_ADDRESS.'` ...';
		} //end if
		if((string)$init_ip_addr != (string)\SmartUtils::get_ip_client()) {
			return 'The config value of `APP_AUTH_ADMIN_INIT_IP_ADDRESS` constant is restricting you to access the initialization of this area !'."\n".'Your IP Address is: `'.\SmartUtils::get_ip_client().'` ...';
		} //end if
		//--
		if(!\defined('\\APP_AUTH_ADMIN_USERNAME')) {
			return 'Set in config: `APP_AUTH_ADMIN_USERNAME` !'."\n".'You must set the `APP_AUTH_ADMIN_USERNAME` constant in config before installation. Manually REFRESH this page after by pressing F5 ...';
		} //end if
		if(\SmartAuth::validate_auth_username(
			(string) \APP_AUTH_ADMIN_USERNAME,
			true // check for reasonable length, as 5 chars
		) !== true) { // {{{SYNC-AUTH-VALIDATE-USERNAME}}}
			return 'Invalid value set in config for: `APP_AUTH_ADMIN_USERNAME` !'."\n".'The `APP_AUTH_ADMIN_USERNAME` set in config must be valid and at least 5 characters long ! Manually REFRESH this page after by pressing F5 ...';
		} //end if
		//--
		if(!\defined('\\APP_AUTH_ADMIN_PASSWORD')) {
			return 'Set in config: `APP_AUTH_ADMIN_PASSWORD` !'."\n".'You must set the `APP_AUTH_ADMIN_PASSWORD` constant into config before installation. Manually REFRESH this page after by pressing F5 ...';
		} //end if
		if(\SmartAuth::validate_auth_password( // {{{SYNC-AUTH-VALIDATE-PASSWORD}}}
			(string) \APP_AUTH_ADMIN_PASSWORD,
			(bool) ((\defined('\\APP_AUTH_ADMIN_COMPLEX_PASSWORDS') && (\APP_AUTH_ADMIN_COMPLEX_PASSWORDS === true)) ? true : false) // check for complexity just on login ! ... for the rest do not check because if this constant changes ... cannot re-update everything !
		) !== true) {
			return 'Invalid value set in config for: `APP_AUTH_ADMIN_PASSWORD` ... need to be changed !'."\n".'THE PASSWORD IS TOO SHORT OR DOES NOT MEET THE REQUIRED COMPLEXITY CRITERIA.'."\n".'Must be min 8 chars and max 72 chars.'."\n".'Must contain at least 1 character A-Z, 1 character a-z, one digit 0-9, one special character such as: ! @ # $ % ^ & * ( ) _ - + = [ { } ] / | . , ; ? ...'."\n".'Manually REFRESH this page after by pressing F5 ...';
		} //end if
		//--
		$idb = null;
		//--
		try {
			$idb = new \SmartModelAuthAdmins(); // will create + initialize DB if not found
		} catch(\Exception $e) {
			$idb = null;
			return 'AUTH DB Failed to Initialize: `'.$e->getMessage().'`'; // fatal error, can't continue
		} //end try catch
		//--
		$init_username = (string) \APP_AUTH_ADMIN_USERNAME;
		$init_password = (string) \APP_AUTH_ADMIN_PASSWORD;
		//--
		$init_privileges = (string) \SmartAuth::DEFAULT_PRIVILEGES; // {{{SYNC-AUTH-DEFAULT-ADM-SUPER-PRIVS}}}
		$init_privileges = \Smart::list_to_array((string)$init_privileges);
		$init_privileges = \Smart::array_to_list((array)$init_privileges);
		//--
		$wr = (int) $idb->insertAccount(
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
			return 'AUTH DB Failed to Create the account for: `'.$init_username.'` [ERR='.(int)$wr.']';
		} //end if
		//--
		$select_user = (array) $idb->getById((string)$init_username);
		//--
		if(
			((int)\Smart::array_size($select_user) <= 0)
			OR
			((string)($select_user['id'] ?? null) != (string)\APP_AUTH_ADMIN_USERNAME)
		) {
			return 'AUTH DB Failed to Find the account for: `'.$init_username.'`';
		} //end if
		//--
		return '';
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function AuthLock() : void {
		//--
		\SmartAuth::lock_auth_data();
		//--
		return;
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
