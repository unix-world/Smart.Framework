<?php
// Class: \SmartModExtLib\AuthAdmins\AbstractAuthHandler
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

namespace SmartModExtLib\AuthAdmins;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

// [PHP8]

//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
//=====================================================================================


/**
 * Abstract Auth Provider
 *
 * @access 		private
 * @internal
 *
 * DEPENDS classes: 	Smart, SmartAuth, SmartEnvironment, SmartUtils, \SmartModExtLib\AuthAdmins\AuthProviderHttp
 * DEPENDS constants: 	SMART_FRAMEWORK_SECURITY_KEY
 *
 * @version 	v.20250118
 * @package 	development:modules:AuthAdmins
 *
 */
abstract class AbstractAuthHandler {

	// ::

	protected const AUTH_2FA_COOKIE_NAME = 'Sf_2FA';
	protected const AUTH_2FA_REGEX_TOKEN = '/^[0-9]{8}$/';

	protected const TPL_PATH 		= 'modules/mod-auth-admins/templates/';
	protected const TPL_FILE 		= 'template.htm';
	protected const TPL_INC_PATH 	= 'modules/mod-auth-admins/libs/templates/auth-admins-handler/';
	protected const IMG_LOADER 		= 'lib/framework/img/loading-spokes.svg';
	protected const IMG_UNICORN 	= 'lib/framework/img/unicorn-auth-logo.svg';
	protected const TXT_UNICORN 	= 'Smart.Unicorn Secure Authentication';
	protected const TPL_LOGIN 		= 'login.htm';
	protected const TXT_LOGIN 		= 'Sign-In Authenticated Area';
	protected const TPL_LOGOUT 		= 'logout.htm';
	protected const TXT_LOGOUT 		= 'Sign-Out Authenticated Area';

	protected const STK_TOKEN_AUTH_USERNAME_SUFFIX = '#token'; // be sure it starts with an invalid character such as `#` which below will not be validated by the \SmartAuth::validate_auth_username() !
	protected const AUTH_VIA_TOKEN_ENFORCED_RESTRICTIONS_LIST = '<def-account>,<account>,<token>';

	private const  AUTH_CREDENTIALS = [ // init
		'auth-select' 				=> '',
		'auth-valid' 				=> false,
		'auth-ermsg' 				=> '',
		'auth-safe' 				=> -500,
		'auth-mode' 				=> '',
		'use-2fa-auth' 				=> true,
		'use-www-401-auth-prompt' 	=> true,
		'user-pass' => [ // Standard (Default), via HTTP Basic Auth
			'is-valid' 		=> false,
			'error-msg' 	=> 'User/Pass NOT Yet Validated ...',
			'user-name' 	=> '',
			'pass-hash' 	=> '', // may provide a plain password or just a one-way encrypted pass hash only
			'token-key' 	=> null, // NULL, is not available for this type
			'token-data' 	=> null, // NULL, is not available for this type
			'restr-priv' 	=> null, // NULL, is not available for this type
			'restr-ip' 		=> null, // NULL, is not available for this type
		],
		'swt-token' => [ // SWT, via Auth Bearer token ; this is N/A using username and/or pass, just via Bearer header, HTTP
			'is-valid' 		=> false,
			'error-msg' 	=> 'SWT Token NOT Yet Validated ...',
			'user-name' 	=> '',
			'pass-hash' 	=> '', // provides the one-way encrypted pass hash only
			'token-key' 	=> '', // should store the SWT Token string, from Auth Bearer
			'token-data' 	=> [], // ARRAY, should store the Token Data that comes from the SWT Token Validation
			'restr-priv' 	=> [], // ARRAY, should be non-empty ; should contain the list of privileges restrictions
			'restr-ip' 		=> [], // ARRAY, should store the Token Data that comes from the SWT Token Validation
		],
		'stk-token' => [ // STK, via HTTP Basic Auth token ; Ex: username=admin#token ; pass=...stk-token-goes-here...
			'is-valid' 		=> false,
			'error-msg' 	=> 'STK Token NOT Yet Pre-Validated ...',
			'user-name' 	=> '', // should be get from the username as prefix before `#token`
			'pass-hash' 	=> null, // NULL ! ; pass hash needs to be get later ... N/A with this token type
			'token-key' 	=> '', // should store the STK Token Key string from Auth Pass
			'token-data' 	=> [ 'validated' => false, 'warning' => 'Opaque Tokens are only pre-validated on 1st step. They must be validated on 2nd step' ],
			'restr-priv' 	=> null, // NULL, is not available for this type
			'restr-ip' 		=> null, // NULL, is not available for this type
		],
	];

	private static $preCheckForbiddenConditions 			= false;
	private static $preCheckInternalErrorConditions 		= false;
	private static $preCheckBadGatewayConditions 			= false;
	private static $preCheckServiceUnavailableConditions 	= false;
	private static $getAuthCredentials 						= false;
	private static $isAuth2FAValid 							= false;


	//================================================================
	/**
	 * RETURN: STRING ; The current called class name that has been extended from this one, without namespace prefix
	 */
	final protected static function getClassName() : string {
		//--
		return (string) \Smart::getClassNameWithoutNamespacePrefix((string)\get_called_class());
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Auth Pre-Check FORBIDDEN Conditions : Status 403
	 *
	 * This method should not be called more than once !
	 *
	 * IMPORTANT: All messages from this method may be display to public via HTTP Status Pages
	 * DO NOT INCLUDE SENSITIVE INFORMATION IN THIS MESSAGES !
	 *
	 * RETURN: STRING ; 'ERR-Message' or ''
	 */
	final protected static function preCheckForbiddenConditions() : string {

		//--
		if(self::$preCheckForbiddenConditions !== false) {
			return 'Method `preCheckForbiddenConditions` should not be called more than once';
		} //end if
		self::$preCheckForbiddenConditions = true;
		//--

		//--
		if(\SmartEnvironment::isAdminArea() !== true) {
			return 'Authentication system is designed for admin/task areas only ...';
		} //end if
		//--

		//--
		return '';
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Auth Pre-Check INTERNAL ERROR Conditions : Status 500
	 *
	 * This method should not be called more than once !
	 *
	 * IMPORTANT: All messages from this method may be display to public via HTTP Status Pages
	 * DO NOT INCLUDE SENSITIVE INFORMATION IN THIS MESSAGES !
	 *
	 * RETURN: STRING ; 'ERR-Message' or ''
	 */
	final protected static function preCheckInternalErrorConditions(bool $disable_tokens=false, bool $disable_2fa=false) : string {

		//--
		if(self::$preCheckInternalErrorConditions !== false) {
			return 'Method `preCheckInternalErrorConditions` should not be called more than once';
		} //end if
		self::$preCheckInternalErrorConditions = true;
		//--

		//--
		if(\headers_sent()) {
			return 'Authentication Failed, Headers Already Sent !...';
		} //end if
		//--

		//--
		if(!\defined('\\SMART_SOFTWARE_NAMESPACE')) {
			return 'A required constant is missing: SMART_SOFTWARE_NAMESPACE !';
		} //end if
		if((string)\trim((string)\SMART_SOFTWARE_NAMESPACE) == '') {
			return 'A required constant is empty: SMART_SOFTWARE_NAMESPACE !';
		} //end if
		//--
		if(!\defined('\\SMART_FRAMEWORK_SECURITY_KEY')) {
			return 'A required constant is missing: SMART_FRAMEWORK_SECURITY_KEY !';
		} //end if
		if((string)\trim((string)\SMART_FRAMEWORK_SECURITY_KEY) == '') {
			return 'A required constant is empty: SMART_FRAMEWORK_SECURITY_KEY !';
		} //end if
		//--

		//--
		if(\SmartEnvironment::isATKEnabled() !== (bool)(!$disable_tokens)) {
			return 'Conflict in ATK (Auth Tokens) Settings !';
		} //end if
		//--

		//--
		if(\SmartEnvironment::is2FAEnabled() !== (bool)(!$disable_2fa)) {
			return 'Conflict in 2FA (Two Factor Authentication) Settings !';
		} //end if
		//--

		//--
		return '';
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Auth Pre-Check BAD GATEWAY Conditions : Status 502
	 *
	 * This method should not be called more than once !
	 *
	 * IMPORTANT: All messages from this method may be display to public via HTTP Status Pages
	 * DO NOT INCLUDE SENSITIVE INFORMATION IN THIS MESSAGES !
	 *
	 * RETURN: STRING ; 'ERR-Message' or ''
	 */
	final protected static function preCheckBadGatewayConditions(bool $enforce_https) : string {

		//--
		if(self::$preCheckBadGatewayConditions !== false) {
			return 'Method `preCheckBadGatewayConditions` should not be called more than once';
		} //end if
		self::$preCheckBadGatewayConditions = true;
		//--

		//-- {{{SYNC-AUTH-HANDLER-HTTPS-CHECK}}}
		if($enforce_https === true) {
			if((string)\SmartUtils::get_server_current_protocol() !== 'https://') {
				return 'This Web Area require HTTPS'."\n".'Switch from http:// to https:// in order to use this Web Area';
			} //end if
		} //end if
		//--

		//--
		return '';
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Auth Pre-Check SERVICE UNAVAILABLE Conditions : Status 503
	 *
	 * This method should not be called more than once !
	 *
	 * IMPORTANT: All messages from this method may be display to public via HTTP Status Pages
	 * DO NOT INCLUDE SENSITIVE INFORMATION IN THIS MESSAGES !
	 *
	 * RETURN: STRING ; 'ERR-Message' or ''
	 */
	final protected static function preCheckServiceUnavailableConditions() : string {

		//--
		if(self::$preCheckServiceUnavailableConditions !== false) {
			return 'Method `preCheckServiceUnavailableConditions` should not be called more than once';
		} //end if
		self::$preCheckServiceUnavailableConditions = true;
		//--

		//--
		if(!\defined('\\APP_AUTH_PRIVILEGES')) { // this must be check always, not only on initDb
			return 'Set in config the `APP_AUTH_PRIVILEGES` constant !'."\n".'The `APP_AUTH_PRIVILEGES` constant is required to run this Authentication Handler ...';
		} //end if
		//--

		//--
		return '';
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Auth Get Credentials
	 * It will retry the Auth Credentials from the HTTP Headers (Basic Auth or Bearer Auth)
	 *
	 * This method should not be called more than once !
	 *
	 * RETURN: ARRAY ; see the AUTH_CREDENTIALS array definition in this class ...
	 */
	final protected static function getAuthCredentials(bool $enforce_https, bool $disable_tokens, bool $disable_2fa) : array {

		//-- init
		$authCredentials = (array) self::AUTH_CREDENTIALS;
		$authCredentials['use-2fa-auth'] 			= (bool) (!$disable_2fa);
		$authCredentials['use-www-401-auth-prompt'] = true; // make sure it is enabled at the begining
		//--

		//--
		if(self::$getAuthCredentials !== false) {
			$authCredentials['auth-ermsg'] = 'Method `getAuthCredentials` should not be called more than once';
			return (array) $authCredentials;
		} //end if
		self::$getAuthCredentials = true;
		//--

		//-- {{{SYNC-AUTH-HANDLER-HTTPS-CHECK}}}
		if($enforce_https === true) {
			if((string)\SmartUtils::get_server_current_protocol() !== 'https://') {
				$authCredentials['auth-ermsg'] = 'Not HTTPS ...';
			} //end if
		} //end if
		//--

		//--
		$authData = []; // init
		//--
		$authTemplateData = (array) \SmartModExtLib\AuthAdmins\AuthProviderHttp::AUTH_RESULT; // pre-init, to be sure is implemented in a correct way
		//--
		$authHttpData = (array) \SmartModExtLib\AuthAdmins\AuthProviderHttp::GetCredentials((bool)(!$disable_tokens)); // get credentials data
		//--
		foreach($authTemplateData as $key => $val) {
			$authData[(string)$key] = ($authHttpData[(string)$key] ?? null); // do not cast, must be preserved !
		} //end foreach
		//--
		$authHttpData = null;
		$authTemplateData = null;
		//--

		//--
		if((string)$authData['auth-error'] == '') { // IF NO ERRORS
			//--
			$authData['auth-user'] 		= (string) \trim((string)$authData['auth-user']);
			$authData['auth-pass'] 		= (string) \trim((string)$authData['auth-pass']);
			$authData['auth-bearer'] 	= (string) \trim((string)$authData['auth-bearer']);
			//--
			if((string)$authData['auth-bearer'] != '') { // bearer token auth ; SWT
				//--
				if(
					((string)$authData['auth-user'] == '')
					AND // explicit user/pass must not be set either
					((string)$authData['auth-pass'] == '')
				) {
					//--
					// SWT Token have a lot of info embedded, just like JWT ; SWT provides the username and also provides the pass-hash (the salted, one-way hash of the password) that can be used to identify the user account and decide later if match a valid user account or not
					//--
					$token_swt = (string) $authData['auth-bearer']; // already trimmed above
					//--
					if(
						((string)$token_swt != '') // check again, after trim
						AND
						((string)\trim((string)\SmartAuth::SWT_VERSION_PREFIX) != '')
						AND
						((string)\trim((string)\SmartAuth::SWT_VERSION_SUFFIX) != '')
						AND
						(\strpos((string)$token_swt, (string)\SmartAuth::SWT_VERSION_PREFIX.';') === 0)
						AND
						(\strpos((string)\strrev((string)$token_swt), (string)\strrev((string)\SmartAuth::SWT_VERSION_SUFFIX).';') === 0)
					) { // {{{SYNC-AUTH-TOKEN-SWT}}}
						//-- SWT Token validation # start ; this should be on the early phase of auth, in a common place to be shore that all the Auth Handlers are using the 100% same implementation ...
						$swt_validate = (array) \SmartAuth::swt_token_validate(
							(string) $token_swt, // swt token via Auth Bearer
							(string) \SmartUtils::get_ip_client(), // client's current IP Address
						);
						//--
						$auth_swt_data = (array) $authCredentials['swt-token']; // create a copy
						//--
						$auth_swt_data['token-key'] 	= (string) $token_swt;
						$auth_swt_data['token-data'] 	= (array)  $swt_validate;
						$auth_swt_data['error-msg'] 	= (string) 'SWT: Not Yet Validated ...'; // init as non-empty
						//--
						if($swt_validate['error'] === '') { // OK
							//--
							$auth_swt_data['is-valid'] 		= true;
							$auth_swt_data['error-msg'] 	= ''; // clear
							//--
							$auth_swt_data['user-name'] 	= (string) $swt_validate['user-name'];
							$auth_swt_data['pass-hash'] 	= (string) $swt_validate['pass-hash'];
							//--
							if(
								((int)\Smart::array_size($swt_validate['restr-ip']) > 0) // non-empty array
								AND
								((int)\Smart::array_type_test($swt_validate['restr-ip']) === 1) // must be non-associative array
							) {
								$auth_swt_data['restr-ip'] 	= (array) $swt_validate['restr-ip'];
							} //end if
							//--
							if(
								((int)\Smart::array_size($swt_validate['restr-priv']) > 0) // if not array, or empty array
								AND
								((int)\Smart::array_type_test($swt_validate['restr-priv']) === 1) // if other type than non-associative array
							) {
								$auth_swt_data['restr-priv'] = (array) $swt_validate['restr-priv'];
							} //end if
							//--
							$authCredentials['use-2fa-auth'] 			= false;
							$authCredentials['use-www-401-auth-prompt'] = false; // This should be disabled for successful Auth Bearer only, the case here ; the logic is to be able to hide the www-auth prompt when Auth Bearer is used
							//--
						} else {
							//--
							$auth_swt_data['is-valid'] 		= false;
							$auth_swt_data['error-msg'] 	= (string) 'SWT.ERR: '.$swt_validate['error']; // make sure is non-empty
							//--
						} //end if else
						//--
						$authCredentials['swt-token'] = (array) $auth_swt_data; // save back
						$auth_swt_data = null;
						//--
						$swt_validate = null;
						//-- #end :: SWT Token validation
					} else {
						//--
						$authCredentials['auth-ermsg'] = 'The Auth Bearer Token [SWT] is not recognized by the signature';
						//--
					} //end if else
					//--
				} else {
					//--
					$authCredentials['auth-ermsg'] = 'The Auth Bearer Token [SWT] can not be used when Auth UserName and/or Pass is set';
					//--
				} //end if else
				//--
			} else { // if it is not Bearer Token Auth, then it can be either: User/Pass or STK Token Auth
				//--
				// the difference between User/Pass and STK Token Auth is:
				// 	* STK Token Auth uses (example): username=admin#token ; pass=theSTKTokenKey ; theSTKTokenKey is just a virtual key mapped to a tokens per user storage and does not contain any embedded info inside ; later binding must read and validate the STK token and return the (one-way) pass-hash that together with the username will be used to decide later if match a valid user account or not
				//	* STANDARD HTTP Auth using UserName / Password is (example):
				//--
				if(
					((string)$authData['auth-user'] != '')
					AND // explicit user/pass ; needs both
					((string)$authData['auth-pass'] != '')
				) {
					//--
					$auth_userpass_data = (array) $authCredentials['user-pass']; // create a copy
					//--
					if(\SmartAuth::validate_auth_username( // user/pass ; {{{SYNC-AUTH-VALIDATE-USERNAME}}}
						(string) $authData['auth-user'],
						false // do not check for reasonable length here, use minimal ; will later decide
					) === true) { // OK
						//--
						if(\SmartAuth::validate_auth_password( // {{{SYNC-AUTH-VALIDATE-PASSWORD}}}
							(string) $authData['auth-pass'],
							false // do not check for complex passwords, validate also if non-complex ; will later decide
						) === true) { // OK
							//--
							$auth_userpass_data['is-valid']  	= true;
							$auth_userpass_data['error-msg'] 	= ''; // clear
							//--
							$auth_userpass_data['user-name'] 	= (string) $authData['auth-user'];
							$auth_userpass_data['pass-hash'] 	= (string) $authData['auth-pass'];
							//--
						} else { // invalid pass, FAIL
							//--
							$auth_userpass_data['is-valid']  = false;
							$auth_userpass_data['error-msg'] = 'Auth UserName is provided and Valid ; Password is provided but Invalid';
							//--
						} //end if else
						//--
					} elseif((string)\substr((string)$authData['auth-user'], -1 * (int)\strlen((string)self::STK_TOKEN_AUTH_USERNAME_SUFFIX), (int)\strlen((string)self::STK_TOKEN_AUTH_USERNAME_SUFFIX)) === (string)self::STK_TOKEN_AUTH_USERNAME_SUFFIX) { // try if this can be a STK Token
						//-- {{{SYNC-AUTH-PROVIDER-CONDITIONS-STK-TOKEN-LOGIN}}} ; detect STK Tokens Auth as: user.name#token
						$auth_stk_data = (array) $authCredentials['stk-token']; // create a copy
						//--
						$stk_user_name = (string) \trim((string)\substr((string)$authData['auth-user'], 0, -1 * (int)\strlen((string)self::STK_TOKEN_AUTH_USERNAME_SUFFIX)));
						//--
						if(\SmartAuth::validate_auth_username( // {{{SYNC-AUTH-VALIDATE-USERNAME}}}
							(string) $stk_user_name,
							false // do not check for reasonable length here, use minimal ; will later decide
						) === true) { // OK
							//--
							if( // {{{SYNC-VALIDATE-STK-TOKEN-LENGTH}}} ; to validate Token Key, see: \SmartModExtLib\AuthAdmins\AuthTokens::createPublicPassKey()
								((int)\strlen((string)$authData['auth-pass']) >= 42)
								AND // token key should be between 42 and 46 characters ; sha256.B58
								((int)\strlen((string)$authData['auth-pass']) <= 46)
								AND
								((int)\strlen((string)$authData['auth-pass']) === (int)\strspn((string)$authData['auth-pass'], (string)\Smart::CHARSET_BASE_58)) // B58 valid chars only
							) { // OK
								//--
								$auth_stk_data['is-valid']  	= true;
								$auth_stk_data['error-msg'] 	= ''; // reset
								//--
								$auth_stk_data['user-name'] 	= (string) $stk_user_name; // provided via a substring of auth user with a #token suffix, separed above
								$auth_stk_data['pass-hash'] 	= ''; // reset, this is not actually a valid Pass, but a Token Key, registered below !
								//--
								$auth_stk_data['token-key'] 	= (string) $authData['auth-pass']; // provided via Auth Pass ...
								//--
								$authCredentials['use-2fa-auth'] = false;
								// the 'use-www-401-auth-prompt' should be ENABLED for this case ; STK Tokens are not using Auth Bearer !
								//--
							} else { // invalid token key for the STK Token
								//--
								$auth_stk_data['is-valid']  = false;
								$auth_stk_data['error-msg'] = 'The STK Token Auth UserName is provided and Valid ; Auth Key is Invalid';
								//--
							} //end if else
							//--
						} else { // invalid user name for the STK Token
							//--
							$auth_stk_data['is-valid']  = false;
							$auth_stk_data['error-msg'] = 'The STK Token Auth UserName is Invalid ; No need to check the Token key ...';
							//--
						} //end if else
						//--
						$stk_user_name = null;
						//--
						$authCredentials['stk-token'] = (array) $auth_stk_data; // save back
						$auth_stk_data = null;
						//--
					} else { // not a STK Token, not a valid UserName ; no need to check for Pass in this scenario
						//--
						$auth_userpass_data['is-valid']  = false;
						$auth_userpass_data['error-msg'] = 'Auth UserName is provided but is Invalid ; No need to check the password ...';
						//--
					} //end if
					//--
					$authCredentials['user-pass'] = (array) $auth_userpass_data; // save back
					$auth_userpass_data = null;
					//--
				} else {
					//--
					$authCredentials['auth-ermsg'] = 'The Auth type UserName/Password needs both: UserName and Password to be set';
					//--
				} //end if else
				//--
			} //end if else
			//--
		} //end if #end if no auth error
		//--

		//-- set some meta-data
		$authCredentials['auth-mode'] = (string) $authData['auth-mode'];
		$authCredentials['auth-safe'] = (string) $authData['auth-safe'];
		//--

		//-- final check
		if((string)$authData['auth-error'] != '') { // IF ERRORS
			$authCredentials['auth-ermsg'] = (string) $authData['auth-error'];
		} //end if
		//-- internal consistency check and final set auth-valid
		if((string)$authCredentials['auth-ermsg'] != '') { // IF ERRORS
			$authCredentials['auth-valid'] = false;
		} else { // IF NO ERRORS, run a consistency check !
			$authCredentials['auth-valid'] = false; // pre-set as FALSE, later on first error/invalid reset it as FALSE
			foreach($authCredentials as $key => $val) {
				if(\is_array($val)) {
					if( // check if array key exists and not isset() because some keys are NULL !
						\array_key_exists('is-valid', (array)$val)
						AND
						\array_key_exists('error-msg', (array)$val)
					) {
						if(
							($val['is-valid'] === true)
							AND // only if is valid and have empty error message
							((string)$val['error-msg'] == '')
						) {
							$authCredentials['auth-valid'] = true; // found at least one auth entry which is valid
							$authCredentials['auth-select'] = (string) $key;
						} //end if
					} //end if
				} //end if
			} //end foreach
		} //end if else
		//--

		//--
		return (array) $authCredentials;
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Try Authentication Guard
	 * This will detect the single following situation, that will skip Try Auth (this is the entry page):
	 * 	- current script ends in `.php`
	 * 	- current REQUEST_METHOD is GET or HEAD
	 * 	- there are no input variables from REQUEST_URI, QUERY_STRING, PATH_INFO, REQUEST, GET, POST, FILES
	 * For all the rest of situations will return TRUE (ask Try Auth)
	 * There is no need to check for COOKIES, this is an HTTP Auth Handler, not managing Auth by Cookies ...
	 *
	 * REQUEST_URI != SCRIPT_NAME (it means have query variables)
	 *
	 * RETURN: BOOL ; if TRUE, Auth is required, otherwise if FALSE should not
	 */
	final protected static function tryAuthGuard() : bool {
		//-- ask for Auth except of the login page
		$tryAuth = true; // init
		//-- work direct with PHP variables, don't rely on LibUtils ... for safety and security !
		if(\is_array($_SERVER)) {
			if(isset($_SERVER['REQUEST_METHOD'])) {
				if(
					((string)\strtoupper((string)\trim((string)$_SERVER['REQUEST_METHOD'])) == 'GET')
					OR
					((string)\strtoupper((string)\trim((string)$_SERVER['REQUEST_METHOD'])) == 'HEAD')
				//	OR
				//	((string)\strtoupper((string)\trim((string)$_SERVER['REQUEST_METHOD'])) == 'OPTIONS')
				) {
					if(isset($_SERVER['SCRIPT_NAME']) AND isset($_SERVER['REQUEST_URI'])) {
						if(
							((string)\strtolower((string)\substr((string)\trim((string)$_SERVER['SCRIPT_NAME']), -4, 4)) == '.php') // make sure the script ends in PHP, also test at once if it is correct and non-empty
						) {
							if((string)\trim((string)$_SERVER['SCRIPT_NAME']) === (string)\trim((string)$_SERVER['REQUEST_URI'])) {
								if( // assume these keys may not be set except if they are non-empty ; apache vs PHP CGI
									((string)\trim((string)($_SERVER['QUERY_STRING'] ?? null)) == '')
									AND
									((string)\trim((string)($_SERVER['PATH_INFO'] ?? null)) == '')
								) {
									if(
										empty($_REQUEST)
										AND
										empty($_GET)
										AND
										empty($_POST)
										AND
										empty($_FILES)
									) {
										$tryAuth = false;
									} //end if
								} //end if
							} //end if
						} //end if
					} //end if
				} //end if
			} //end if
		} //end if
		//--
		return (bool) $tryAuth;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Detect Logout Request
	 * This will detect the single following situation, that will display the Logout page:
	 * 	- current script ends in `.php`
	 * 	- current REQUEST_METHOD is GET or HEAD
	 * 	- the $_REQUEST['logout'] variable is set and not empty
	 *
	 * RETURN: BOOL ; if TRUE, Auth will display the Logout Page
	 */
	final protected static function isAuthLogout() : bool {
		//--
		return (bool) (isset($_REQUEST['logout']) AND (!empty($_REQUEST['logout'])));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Render the Logout Page
	 *
	 * RETURN: STRING ; HTML MAIN TPL
	 */
	final protected static function renderAuthLogoutPage() : string {
		//--
		return (string) \SmartComponents::render_app_template(
			(string) self::TPL_PATH,
			(string) self::TPL_FILE,
			[
				'SEMAPHORE' => (string) \Smart::array_to_list([ 'skip:js-ui', 'skip:js-media', 'skip:unveil-js' ]),
				'HEAD-META' => '<meta name="robots" content="noindex">',
				'TITLE' => (string) self::TXT_LOGOUT,
				'MAIN'  => (string) \SmartMarkersTemplating::render_file_template(
					(string) self::TPL_INC_PATH . self::TPL_LOGOUT,
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
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Render the Login Page
	 *
	 * RETURN: STRING ; HTML MAIN TPL
	 */
	final protected static function renderAuthLoginPage(bool $disable_2fa, string $auth_user_name, string $auth_mode, string $auth_desc_mode, string $html_inc_code_poweredby_area='') : string {
		//--
		$use_2fa = (bool) ! $disable_2fa;
		$require_2fa = (bool) \SmartEnvironment::is2FARequired();
		//--
		$auth_user_name = (string) \trim((string)$auth_user_name);
		$auth_mode = (string) \trim((string)$auth_mode);
		$auth_desc_mode = (string) \trim((string)$auth_desc_mode);
		$html_inc_code_poweredby_area = (string) \trim((string)$html_inc_code_poweredby_area);
		//--
		if((string)$html_inc_code_poweredby_area == '') {
			//--
			$arr_bw = (array) \SmartComponents::get_imgdesc_by_bw_id((string)\SmartUtils::get_os_browser_ip('bw'));
			//--
			$html_inc_code_poweredby_area = \SmartComponents::app_powered_info(
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
			);
			//--
		} //end if
		//--
		$login_url = (string) '#!'.((\SmartEnvironment::isTaskArea() === true) ? 'tsk' : 'adm').'/DISPLAY-REALMS/'.\SmartHashCrypto::sh3a224((string)microtime(true).chr(0).SMART_FRAMEWORK_SECURITY_KEY); // need a randomizer factor here to avoid expose all 3 algorithms (BF, TF, TF+BF) with a fixed string encryption ... which can easy the reverse encryption engineering !
		$rand_enc = (int) rand(0, 2);
		$opacity = '1.00';
		$ciphmark = '[N/A]';
		if(((int)$rand_enc == 0) OR ((int)$rand_enc == 1)) { // TF or TF+BF
			$login_url = (string) \SmartCipherCrypto::tf_encrypt((string)$login_url, '', (bool)$rand_enc);
			if((int)$rand_enc == 1) {
				$opacity = '0.37';
				$ciphmark = '[TF:BF]';
			} else {
				$opacity = '0.62';
				$ciphmark = '[TF]';
			} //end if else
		} else { // BF
			$login_url = (string) \SmartCipherCrypto::bf_encrypt((string)$login_url, '');
			$opacity = '0.87';
			$ciphmark = '[BF]';
		} //end if else
		//--
		return (string) \SmartComponents::render_app_template(
			(string) self::TPL_PATH,
			(string) self::TPL_FILE,
			[
				'SEMAPHORE' => (string) \Smart::array_to_list([ 'skip:js-ui', 'skip:js-media', 'skip:unveil-js' ]),
				'HEAD-META' => '<meta name="robots" content="noindex">',
				'TITLE' => (string) self::TXT_LOGIN,
				'MAIN'  => (string) \SmartMarkersTemplating::render_file_template(
					(string) self::TPL_INC_PATH . self::TPL_LOGIN,
					[
						'RELEASE-HASH' 	=> (string) \SmartUtils::get_app_release_hash(),
						'USE-2FA' 		=> (string) (($use_2fa === true) ? '1' : '0'),
						'REQUIRED-2FA' 	=> (string) (($require_2fa === true) ? '1' : '0'),
						'REGEX-2FA' 	=> (string) self::AUTH_2FA_REGEX_TOKEN,
						'COOKIE-N-2FA' 	=> (string) self::AUTH_2FA_COOKIE_NAME,
						'LOGIN-SCRIPT' 	=> (string) ((\SmartEnvironment::isTaskArea() === true) ? 'task.php' : 'admin.php'),
						'LOGIN-URL' 	=> (string) $login_url, // {{{SYNC-AUTH-ADMINS-LOGIN-SCRIPT}}}
						'LOGIN-AREA' 	=> (string) ((\SmartEnvironment::isTaskArea() === true) ? 'Task' : 'Admin'),
						'OPACITY-DEC' 	=> (string) $opacity,
						'CIPH-MARK' 	=> (string) $ciphmark,
						'POWERED-HTML' 	=> (string) $html_inc_code_poweredby_area,
						'AREA-ID' 		=> (string) (\defined('\\SMART_SOFTWARE_NAMESPACE') ? \SMART_SOFTWARE_NAMESPACE : 'N/A'),
						'CRR-YEAR' 		=> (string) \date('Y'),
						'LOGOUT-URL' 	=> (string) \SmartUtils::get_server_current_url().\SmartUtils::get_server_current_script().'?logout=yes',
						'ID-USER' 		=> (string) $auth_user_name,
						'ID-AUTH-MODE' 	=> (string) $auth_mode,
						'ID-AUTH-DESC' 	=> (string) $auth_desc_mode,  // ('SWT' | 'STK' | 'DEF') + ('' | ' (2FA)')
						'TXT-PRIV-AREA' => (string) 'Private Area',
					]
				),
			]
		);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Validate the Account Data against Auth UserName / PassHash ;
	 *
	 * RETURN: BOOL ; if TRUE, the Login is SUCCESS ; Otherwise Login is FAIL
	 */
	final protected static function isAuthLoginValid(string $auth_user_name, string $hash_of_pass, array $account_data) : bool {
		//--
		$auth_user_name = (string) \trim((string)$auth_user_name);
		$hash_of_pass = (string) \trim((string)$hash_of_pass);
		//--
		if(
			((string)$auth_user_name == '') // Auth UserName cannot be empty
			OR
			(\SmartAuth::validate_auth_username( // {{{SYNC-AUTH-VALIDATE-USERNAME}}}
				(string) $auth_user_name,
				true // check for reasonable length, as 5 chars
			) !== true)
			OR
			((string)$hash_of_pass == '') // Auth PassHash cannot be empty
			OR
			((int)\strlen((string)$hash_of_pass) != (int)\SmartHashCrypto::PASSWORD_HASH_LENGTH) // {{{SYNC-AUTH-HASHPASS-LENGTH}}}
		) {
			return false; // UserName and/or PassHash are empty or invalid
		} //end if
		//--
		$success = false;
		//--
		if(
			\is_array($account_data)
			AND
			((int)\Smart::array_size($account_data) === 23) // be sure all fields of the account record (if found) are returned ; this also ensures ~ that is in sync with the current DB structure ...
			AND
			((int)\Smart::array_type_test($account_data) == 2) // must be associative array
			AND
			//-- {{{SYNC-ADMINS-ACCOUNT-DATA-STRUCTURE}}}
			isset($account_data['id']) AND !!\Smart::is_nscalar($account_data['id'])
			AND
			isset($account_data['pass']) AND !!\Smart::is_nscalar($account_data['pass'])
			AND
			isset($account_data['active']) AND !!\Smart::is_nscalar($account_data['active'])
			AND
			isset($account_data['quota']) AND !!\Smart::is_nscalar($account_data['quota'])
			AND
			\array_key_exists('email', $account_data) AND !!\Smart::is_nscalar($account_data['email']) // email, if not set is NULL ; cannot be tested with isset() ...
			AND
			isset($account_data['title']) AND !!\Smart::is_nscalar($account_data['title'])
			AND
			isset($account_data['name_f']) AND !!\Smart::is_nscalar($account_data['name_f'])
			AND
			isset($account_data['name_l']) AND !!\Smart::is_nscalar($account_data['name_l'])
			AND
			isset($account_data['address']) AND !!\Smart::is_nscalar($account_data['address'])
			AND
			isset($account_data['zip']) AND !!\Smart::is_nscalar($account_data['zip'])
			AND
			isset($account_data['city']) AND !!\Smart::is_nscalar($account_data['city'])
			AND
			isset($account_data['region']) AND !!\Smart::is_nscalar($account_data['region'])
			AND
			isset($account_data['country']) AND !!\Smart::is_nscalar($account_data['country'])
			AND
			isset($account_data['phone']) AND !!\Smart::is_nscalar($account_data['phone'])
			AND
			isset($account_data['priv']) AND !!\Smart::is_nscalar($account_data['priv'])
			AND
			isset($account_data['restrict']) AND !!\Smart::is_nscalar($account_data['restrict'])
			AND
			isset($account_data['settings']) AND !!\Smart::is_nscalar($account_data['settings'])
			AND
			isset($account_data['keys']) AND !!\Smart::is_nscalar($account_data['keys'])
			AND
			isset($account_data['fa2']) AND !!\Smart::is_nscalar($account_data['fa2'])
			AND
			isset($account_data['ip_restr']) AND !!\Smart::is_nscalar($account_data['ip_restr'])
			AND
			isset($account_data['ip_addr']) AND !!\Smart::is_nscalar($account_data['ip_addr'])
			AND
			isset($account_data['modif']) AND !!\Smart::is_nscalar($account_data['modif'])
			AND
			isset($account_data['created']) AND !!\Smart::is_nscalar($account_data['created'])
			AND
			//--
			((string)\trim((string)$account_data['id']) != '')
			AND
			(\SmartAuth::validate_auth_username( // {{{SYNC-AUTH-VALIDATE-USERNAME}}}
				(string) $account_data['id'],
				true // check for reasonable length, as 5 chars
			) === true)
			AND
			((string)$account_data['id'] === (string)$auth_user_name) // must be exact the same Auth UserName
			AND
			((string)\trim((string)$account_data['pass']) != '')
			AND
			((int)\strlen((string)$account_data['pass']) == (int)\SmartHashCrypto::PASSWORD_HASH_LENGTH) // {{{SYNC-AUTH-HASHPASS-LENGTH}}}
			AND
			((string)$account_data['pass'] === (string)$hash_of_pass) // must be exact the same Auth PassHash
			AND
			((string)$account_data['active'] == '1') // must be explicit Active = 1
			AND
			((int)$account_data['quota'] >= 0) // quota cannot be negative ; zero = no quota ; > 0, is restricted quota
		) {
			//--
			$success = true;
			//--
		} //end if
		//--
		return (bool) $success;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Validate the 2FA login Value from Cookies compared with 2FA Token Pin or Visitor UUID Hash
	 *
	 * IMPORTANT: the value of cookie will be compared with the provided $valid_2fa_pin_token which have to be a valid TOTP Pin Token
	 *
	 * RETURN: BOOL ; if TRUE, the 2FA is VALID ; otherwise is INVALID
	 */
	final protected static function isAuth2FAValid(string $auth_user_name, bool $use_2fa, string $valid_2fa_pin_token='') : bool {
		//--
		if(!\defined('\\SMART_FRAMEWORK_SECURITY_KEY')) {
			return false; // this is required to proceed further
		} //end if
		//--
		$auth_user_name = (string) \trim((string)$auth_user_name);
		if((string)$auth_user_name == '') {
			return false; // auth user name is mandatory !
		} //end if
		//--
		if($use_2fa === false) {
			return true; // if 2FA is explicit DISABLED. consider it is SUCCESS
		} //end if
		//--
		if(
			((string)\trim((string)$valid_2fa_pin_token) == '')
			OR
			(!\preg_match((string)self::AUTH_2FA_REGEX_TOKEN, (string)$valid_2fa_pin_token)) // 2FA token
		) {
			return false; // empty or invalid 2FA Pin Token
		} //end if
		//--
		$isValid2FA = false; // by default consider it FALSE ; later, explicit set to TRUE if SUCCESS
		//--
		if($use_2fa === true) { // check for 2FA
			//--
			$cookie2FA = (string) \trim((string)\SmartUtils::get_cookie((string)self::AUTH_2FA_COOKIE_NAME));
			if(
				((string)$cookie2FA != '')
				AND
				((int)\strlen((string)$cookie2FA) <= 128) // hardcoded max length supported to avoid overloads
			) {
				if(\strpos((string)$cookie2FA, '#') === 0) {
					$cookie2FA = (string) \trim((string)\trim((string)$cookie2FA, '#'));
					if((string)$cookie2FA != '') {
						$cookie2FA = (string) \trim((string)\Smart::base_to_hex_convert((string)$cookie2FA, 32));
						$cookie2FA = (string) \trim((string)\SmartUnicode::utf8_to_iso((string)$cookie2FA)); // safety
						if(
							((string)$cookie2FA != '')
							AND
							((int)\strlen((string)$cookie2FA) === (int)\strspn((string)$cookie2FA, (string)\Smart::CHARSET_BASE_16))
						) {
							$cookie2FA = (string) \trim((string)\Smart::safe_hex_2_bin((string)$cookie2FA, true, false)); // ignore case, don't log, it is from untrusted source !
							if(\strpos((string)$cookie2FA, '#') === 0) {
								$cookie2FA = (string) \trim((string)\trim((string)$cookie2FA, '#'));
								if((string)$cookie2FA != '') {
									if(!\preg_match((string)self::AUTH_2FA_REGEX_TOKEN, (string)$cookie2FA)) { // 2FA token
										$cookie2FA = ''; // wrong value, expected having the numeric token
									} //end if
								} //end if
							} //end if
						} //end if
					} //end if
				} //end if
			} //end if
			//--
			if((string)$cookie2FA != '') {
				//--
				$uuidCookieVal = '';
				if(defined('\\SMART_APP_VISITOR_COOKIE') AND ((string)trim((string)\SMART_APP_VISITOR_COOKIE) != '')) { // {{{SYNC-SMART-UNIQUE-VAL-COOKIE}}}
					$uuidCookieVal = (string) \SMART_APP_VISITOR_COOKIE;
				} //end if
				//--
				$visitorUuidHash = (string) \Smart::b64_to_b64s((string)\SmartHashCrypto::sh3a256((string)\SmartUtils::client_ident_private_key().'#'.$auth_user_name.'#'.\date('Y-m-d').'#'.\SmartUtils::get_server_current_basedomain_name().'#'.$uuidCookieVal.'#'.\SMART_FRAMEWORK_SECURITY_KEY, true)); // the 2FA hash based on client unique signature + date yyyy-mm-dd, so is valid just in that day
				//--
				if(\preg_match((string)self::AUTH_2FA_REGEX_TOKEN, (string)$cookie2FA)) { // 2FA token
					//--
					$valid_2fa_pin_token = (string) \trim((string)$valid_2fa_pin_token);
					//-- 2FA over HTTP (not HTTPS) will not work in new browsers, ex: Chromium because it does not support cookie None policy over plain HTTP but just over HTTPS
					if(
						((string)$valid_2fa_pin_token != '')
						AND
						((string)$cookie2FA === (string)$valid_2fa_pin_token)
					) { // check if 2FA token is valid
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
							(string) $visitorUuidHash,
							0, // session expire
							'/', // path
							'@', // domain
							'None' // {{{SYNC-COOKIE-POLICY-NONE}}} ; same site policy: None # Safety: OK, the token cookie is bind to visitor ID, incl. IP address, thus is not important if revealed to 3rd party ... # this is a fix (required for iFrame srcdoc, will not send cookies if Lax or Strict on Firefox impl.)
						);
						//--
						$isValid2FA = true;
						//--
					} //end if
					//--
				} else { // check if 2FA hash is valid
					//--
					if((string)$cookie2FA === (string)$visitorUuidHash) {
						//--
						$isValid2FA = true;
						//--
					} //end if
					//--
				} //end if
				//--
			} //end if
			//--
		} //end if
		//--
		return (bool) $isValid2FA;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Validate the IP Restrictions login Value using the User IPAddress List Restrictions ; ex: <ip1>,<ip2>,...
	 *
	 * RETURN: BOOL ; if TRUE, the Current Visitor IP is VALID ; otherwise is INVALID
	 */
	final protected static function isAuthIPAddressValid(string $restricted_ip_list) : bool {
		//--
		$is_ip_valid = true;
		//--
		$restricted_ip_list = (string) \trim((string)$restricted_ip_list);
		//--
		if((string)$restricted_ip_list != '') { // if we have a list of restrictions
			//--
			$admin_arr_allowed_ips = (array) self::parseAccountIpRestrictionsList((string)$restricted_ip_list);
			//--
			if((int)\Smart::array_size((array)$admin_arr_allowed_ips) > 0) {
				//--
				if(
					(!\in_array((string)\SmartUtils::get_ip_client(), (array)$admin_arr_allowed_ips))
					OR // double check: in array and in list
					(\stripos((string)$restricted_ip_list, '<'.\SmartUtils::get_ip_client().'>') === false)
				) { // {{{SYNC-IPV6-STORE-SHORTEST-POSSIBLE}}} ; IPV6 addresses may vary .. find a standard form, ex: shortest
					//--
					$is_ip_valid = false; // Invalid Login, the Ip Address is not in the list
					//--
				} //end if
				//--
			} else {
				//--
				$is_ip_valid = false; // Invalid Login, the Ip Address List exists but is Invalid (results in empty parsed array)
				//--
			} //end if else
			//--
			$admin_arr_allowed_ips = null; // free mem
			//--
		} //end if
		//--
		return (bool) $is_ip_valid;
		//--
	} //END FUNCTION
	//================================================================


	//======== [PRIVATES]


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


} //END INTERFACE


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================

// end of php code
