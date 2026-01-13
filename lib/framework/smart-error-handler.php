<?php
// [Smart.Framework / Smart ERROR Handler]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

// # r.20260112 # this should be loaded from app web root only

// ===== IMPORTANT =====
//	* NO VARIABLES SHOULD BE DEFINED IN THIS FILE BECAUSE IS LOADED BEFORE REGISTERING ANY OF GET/POST VARIABLES (CAN CAUSE SECURITY ISSUES)
//	* ONLY CONSTANTS CAN BE DEFINED HERE
//	* FOR ERRORS WILL USE htmlspecialchars($string, ENT_HTML401 | ENT_COMPAT | ENT_SUBSTITUTE, SMART_FRAMEWORK_CHARSET, true); // as default, with double encoding
//===================

// ALL ERRORS WILL BE LOGGED TO A LOG FILE: SMART_ERROR_LOGDIR/SMART_ERROR_LOGFILE defined below

//===== WARNING: =====
// Changing the code below is on your own risk and may lead to severe disrupts in the execution of this software !
//====================

//-- {{{SYNC-LOCALES-CHECK}}}
// Set Locales to Default: C
// WARNING: NEVER CHANGE LOCALES to anything else than C, all the logic inside this framework is arround C locales ; THEY MUST BE 'C' (default PHP locales) !!!
// IMPORTANT: you should work with overall C locales and never mix the locales as the results will be unpredictable
// Changing locales with other values than C may break many things like Examples: 3.5 may become become 3,5 ; dates may become uncompatible as the format may vary in the overall context, ...
// HINTS: if you need to display localised values never use setlocale() but instead write your own formatters to just format the displayed values in Views
setlocale(LC_ALL, 'C'); // DON'T CHANGE THIS !!! THIS IS COMPATIBLE WILL ALL UTF-8 UNICODE CONTEXTS !!!
if((string)setlocale(LC_ALL, 0) != 'C') { // {{{SYNC-LOCALES-CHECK}}}
	@http_response_code(500);
	die('PHP Default Locales must be: `C` but it set to: `'.setlocale(LC_ALL, 0).'`');
} //end if
//--
if((string)trim((string)ini_get('default_mimetype')) != 'text/html') {
	@http_response_code(500);
	die('PHP Default MimeType must be set as: `text/html` but it set to: `'.ini_get('default_mimetype').'`');
} //end if
//--

//-- PHP version, 64-bit support and various checks
if(version_compare((string)phpversion(), '8.1.0') < 0) { // check for PHP 8.1 or later
	@http_response_code(500);
	die('PHP Runtime not supported: '.phpversion().' !'.'<br>PHP versions to run this software are: 8.1 / 8.2 / 8.3 / 8.4 / 8.5 or later');
} //end if
//--
if(((int)PHP_INT_SIZE < 8) OR ((string)(int)PHP_INT_MAX < '9223372036854775807')) { // check for 64-bit integer
	@http_response_code(500);
	die('PHP Runtime not supported: this version of PHP does not support 64-bit Integers (PHP_INT_SIZE should be 8 and is: '.PHP_INT_SIZE.' ; PHP_INT_MAX should be at least 9223372036854775807 and is: '.PHP_INT_MAX.') ...');
} //end if
//--
if((string)(int)strtotime('2038-03-16 07:55:08 UTC') != '2152338908') { // test year.2038 bug with an integer value longer than 32-bit max int which is: 2147483647
	@http_response_code(500);
	die('PHP OS not supported: this version of OS ('.PHP_OS.') does not support 64-bit time or date detection is broken ...');
} //end if
//--
if((int)PHP_MAXPATHLEN < 255) { // test min req. path length
	@http_response_code(500);
	die('PHP OS not supported: this version of OS ('.PHP_OS.') does not support the minimum required path length which is 255 characters (PHP_MAXPATHLEN='.PHP_MAXPATHLEN.') ...');
} //end if
//--
if(!function_exists('preg_match')) {
	@http_response_code(500);
	die('PHP PCRE Extension is missing. It is needed for Regular Expression ...');
} //end if
//--
if((int)ini_get('pcre.backtrack_limit') < 1000000) {
	@http_response_code(500);
	die('Invalid PCRE Settings: pcre.backtrack_limit in app init file ... Must be at least 1M = 1000000 ; recommended value is 8M = 8000000');
} //end if
if((int)ini_get('pcre.recursion_limit') < 100000) {
	@http_response_code(500);
	die('Invalid PCRE Settings: pcre.recursion_limit in app init file ... Must be at least 100K = 100000 ; ; recommended value is 800K = 800000');
} //end if
//--
if(defined('SMART_FRAMEWORK_ERR_PCRE_SETTINGS')) {
	@http_response_code(500);
	die('A Reserved Constant have been already defined: SMART_FRAMEWORK_ERR_PCRE_SETTINGS');
} //end if
define('SMART_FRAMEWORK_ERR_PCRE_SETTINGS', 'PCRE Failed ... Try to increase the `pcre.backtrack_limit` and `pcre.recursion_limit` in app init file ...');
//--

//--
if(defined('SMART_FRAMEWORK_RELEASE_TAGVERSION') || defined('SMART_FRAMEWORK_RELEASE_VERSION') || defined('SMART_FRAMEWORK_RELEASE_URL') || defined('SMART_FRAMEWORK_RELEASE_NAME')) {
	@http_response_code(500);
	die('Reserved Constants names have been already defined: SMART_FRAMEWORK_RELEASE_* is reserved');
} //end if
//-- {{{SYNC-SF-SIGNATURES-AND-VERSIONS}}}
define('SMART_FRAMEWORK_RELEASE_TAGVERSION', 'v.8.7'); // tag version
define('SMART_FRAMEWORK_RELEASE_VERSION', 'r.2026.01.12'); // tag release-date
define('SMART_FRAMEWORK_RELEASE_URL', 'http://demo.unix-world.org/smart-framework/');
define('SMART_FRAMEWORK_RELEASE_NAME', 'Smart.Framework, a PHP / JavaScript Framework for Web featuring Middlewares + MVC, (c) unix-world.org');
//--
if(defined('SMART_FRAMEWORK_VERSION')) {
	@http_response_code(500);
	die('A Reserved Constant have been already defined: SMART_FRAMEWORK_VERSION');
} //end if
define('SMART_FRAMEWORK_VERSION', 'smart.framework.v.8.7'); // major version ; required for the framework libs
//--

//--
if(defined('SMART_FRAMEWORK_FILESYSUTILS_ROOTPATH')) {
	@http_response_code(500);
	die('A Reserved Constant have been already defined: SMART_FRAMEWORK_FILESYSUTILS_ROOTPATH');
} //end if
define('SMART_FRAMEWORK_FILESYSUTILS_ROOTPATH', ''); // for the Smart.Framework Environment it must be set to EMPTY STRING as '' to allow only relative paths ; in other environments can be set to something like (string)realpath('./').'/'
//--

//--
if(defined('SMART_ERROR_LOG_MANAGEMENT')) {
	@http_response_code(500);
	die('Smart.Framework / Errors Management already loaded, the constant SMART_ERROR_LOG_MANAGEMENT has been already defined ...'); // avoid load more than once
} //end if
if(!define('SMART_ERROR_LOG_MANAGEMENT', 'Smart.Error.Handler')) {
	die('Failed to define the SMART_ERROR_LOG_MANAGEMENT ...');
} //end if
//--
if(!defined('SMART_FRAMEWORK_DEBUG_MODE')) {
	define('SMART_FRAMEWORK_DEBUG_MODE', false); // if not explicit defined, this must be set here to avoid PHP 7.3+ warnings
} //end if
//--
if(defined('SMART_ERROR_HANDLER')) {
	@http_response_code(500);
	die('SMART_ERROR_HANDLER cannot be defined outside ERROR HANDLER');
} //end if
//--
if(!defined('SMART_FRAMEWORK_ENV')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_ENV');
} //end if
switch((string)SMART_FRAMEWORK_ENV) { // allow just 'dev' or 'prod'
	case 'dev':
		if(!define('SMART_ERROR_HANDLER', 'dev')) {
			@http_response_code(500);
			die('Failed to define the SMART_ERROR_HANDLER (dev) ...');
		} //end if
		break;
	case 'prod':
		if(!define('SMART_ERROR_HANDLER', 'log')) {
			@http_response_code(500);
			die('Failed to define the SMART_ERROR_HANDLER (log) ...');
		} //end if
		break;
	default:
		@http_response_code(500);
		die('A required INIT constant has a wrong value: SMART_FRAMEWORK_ENV');
} //end switch
if(((string)SMART_ERROR_HANDLER !== 'dev') AND ((string)SMART_ERROR_HANDLER !== 'log')) {
	@http_response_code(500);
	die('A required INIT constant has a wrong value: SMART_FRAMEWORK_ENV');
} //end if
//--
if(!defined('SMART_FRAMEWORK_LOG_DEBUG_BACKTRACE')) {
	define('SMART_FRAMEWORK_LOG_DEBUG_BACKTRACE', !!(((string)SMART_ERROR_HANDLER != 'log') OR (SMART_FRAMEWORK_DEBUG_MODE === true)));
} //end if
if(!is_bool(SMART_FRAMEWORK_LOG_DEBUG_BACKTRACE)) {
	@http_response_code(500);
	die('Invalid definition for SMART_FRAMEWORK_LOG_DEBUG_BACKTRACE ...');
} //end if
//--
if((!defined('SMART_FRAMEWORK_ADMIN_AREA')) OR (!is_bool(SMART_FRAMEWORK_ADMIN_AREA))) {
	@http_response_code(500);
	die('A required RUNTIME constant has not been defined or have an invalid value: SMART_FRAMEWORK_ADMIN_AREA');
} //end if
//--
array_map(function($const){
	if(!defined((string)$const)) {
		@http_response_code(500);
		die('A required INIT constant has not been defined: '.$const);
	}
},
[ // {{{SYNC-SMART-APP-INI-SETTINGS}}}
	'SMART_STANDALONE_APP', 'SMART_FRAMEWORK_RUNTIME_MODE', 'SMART_FRAMEWORK_LIB_PATH',
	'SMART_SOFTWARE_NAMESPACE', 'SMART_FRAMEWORK_SECURITY_KEY', 'SMART_FRAMEWORK_SRVPROXY_ENABLED',
	'SMART_FRAMEWORK_COOKIES_DEFAULT_LIFETIME', 'SMART_FRAMEWORK_COOKIES_DEFAULT_SAMESITE', 'SMART_FRAMEWORK_COOKIES_DEFAULT_DOMAIN',
	'SMART_FRAMEWORK_TIMEZONE', 'SMART_FRAMEWORK_CHARSET', 'SMART_FRAMEWORK_SECURITY_FILTER_INPUT', 'SMART_FRAMEWORK_SQL_CHARSET',
	'SMART_FRAMEWORK_MEMORY_LIMIT', 'SMART_FRAMEWORK_EXECUTION_TIMEOUT', 'SMART_FRAMEWORK_NETSOCKET_TIMEOUT', 'SMART_FRAMEWORK_NETSERVER_ID',
	'SMART_FRAMEWORK_SSL_MODE', 'SMART_FRAMEWORK_SSL_CIPHERS', 'SMART_FRAMEWORK_SSL_VFY_HOST', 'SMART_FRAMEWORK_SSL_VFY_PEER', 'SMART_FRAMEWORK_SSL_VFY_PEER_NAME', 'SMART_FRAMEWORK_SSL_ALLOW_SELF_SIGNED', 'SMART_FRAMEWORK_SSL_DISABLE_COMPRESS', // 'SMART_FRAMEWORK_SSL_CA_FILE', is optional
	'SMART_FRAMEWORK_CHMOD_DIRS', 'SMART_FRAMEWORK_CHMOD_FILES', 'SMART_FRAMEWORK_DENY_UPLOAD_EXTENSIONS',
	'SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER', 'SMART_SOFTWARE_MKTPL_DEBUG_LEN',
	'SMART_FRAMEWORK_IDENT_ROBOTS', 'SMART_FRAMEWORK_RUNTIME_TASK_ALLOWED_IPS',
]);
//--
if((string)date_default_timezone_get() != (string)SMART_FRAMEWORK_TIMEZONE) {
	@http_response_code(500);
	die('The current PHP local TimeZone `'.date_default_timezone_get().'` is different than what is set in SMART_FRAMEWORK_TIMEZONE: `'.SMART_FRAMEWORK_TIMEZONE.'`');
} //end if
//--
if(((int)strlen((string)SMART_SOFTWARE_NAMESPACE) < 4) OR ((int)strlen((string)SMART_SOFTWARE_NAMESPACE) > 63)) { // {{{SYNC-SMART-NAMESPACE-LENGTH}}}
	@http_response_code(500);
	die('A required INIT constant must have a length between 4 and 63 characters: SMART_SOFTWARE_NAMESPACE');
} //end if
if(!preg_match('/^[_a-z0-9\-\.]+$/', (string)SMART_SOFTWARE_NAMESPACE)) { // regex namespace ; {{{SYNC-SMART-NAMESPACE-REGEX}}}
	@http_response_code(500);
	die('A required INIT constant contains invalid characters: SMART_SOFTWARE_NAMESPACE');
} //end if
//--
if((string)trim((string)SMART_FRAMEWORK_SECURITY_KEY) != (string)SMART_FRAMEWORK_SECURITY_KEY) {
	die('A required INIT constant contains invalid characters, must not start or end with spaces: SMART_FRAMEWORK_SECURITY_KEY');
} //end if
if(((int)strlen((string)SMART_FRAMEWORK_SECURITY_KEY) < 16) OR ((int)strlen((string)SMART_FRAMEWORK_SECURITY_KEY) > 256)) { // {{{SYNC-SMART-SECURITY-KEY-LENGTH}}}
	die('A required INIT constant must have a length between 16 and 256 characters: SMART_FRAMEWORK_SECURITY_KEY');
} //end if
//--
if(((int)SMART_FRAMEWORK_NETSERVER_ID < 0) OR ((int)SMART_FRAMEWORK_NETSERVER_ID > 1295)) { // {{{SYNC-MIN-MAX-NETSERVER-ID}}}
	@http_response_code(500);
	die('The required INIT constant SMART_FRAMEWORK_NETSERVER_ID can have values between 0 and 1295');
} //end if
//--
if(!defined('SMART_FRAMEWORK_MAX_BROWSER_COOKIE_SIZE')) {
	define('SMART_FRAMEWORK_MAX_BROWSER_COOKIE_SIZE', 3072); // max cookie size is 4096 but includding the name, time, domain, path and the rest ...
} //end if
//--
if(!defined('SMART_FRAMEWORK_ALLOW_UPLOAD_EXTENSIONS')) {
	define('SMART_FRAMEWORK_ALLOW_UPLOAD_EXTENSIONS', '');
} //end if
//--
if(!is_bool(SMART_FRAMEWORK_SRVPROXY_ENABLED)) {
	@http_response_code(500);
	die('Invalid value for SMART_FRAMEWORK_SRVPROXY_ENABLED');
} //end if
if(SMART_FRAMEWORK_SRVPROXY_ENABLED === true) {
	if(
		(!defined('SMART_FRAMEWORK_SRVPROXY_CLIENT_IP')) OR (!defined('SMART_FRAMEWORK_SRVPROXY_CLIENT_PROXY_IP'))
		OR
		(!defined('SMART_FRAMEWORK_SRVPROXY_SERVER_PROTO')) OR (!defined('SMART_FRAMEWORK_SRVPROXY_SERVER_PORT'))
		OR
		(!defined('SMART_FRAMEWORK_SRVPROXY_SERVER_IP')) OR (!defined('SMART_FRAMEWORK_SRVPROXY_SERVER_DOMAIN'))
	) {
		@http_response_code(500);
		die('The following constants must be defined when SMART_FRAMEWORK_SRVPROXY_ENABLED is set to TRUE: SMART_FRAMEWORK_SRVPROXY_CLIENT_IP, SMART_FRAMEWORK_SRVPROXY_CLIENT_PROXY_IP, SMART_FRAMEWORK_SRVPROXY_SERVER_PROTO, SMART_FRAMEWORK_SRVPROXY_SERVER_PORT, SMART_FRAMEWORK_SRVPROXY_SERVER_IP, SMART_FRAMEWORK_SRVPROXY_SERVER_DOMAIN');
	} //end if
} else {
	if(
		(defined('SMART_FRAMEWORK_SRVPROXY_CLIENT_IP')) OR (defined('SMART_FRAMEWORK_SRVPROXY_CLIENT_PROXY_IP'))
		OR
		(defined('SMART_FRAMEWORK_SRVPROXY_SERVER_PROTO')) OR (defined('SMART_FRAMEWORK_SRVPROXY_SERVER_PORT'))
		OR
		(defined('SMART_FRAMEWORK_SRVPROXY_SERVER_IP')) OR (defined('SMART_FRAMEWORK_SRVPROXY_SERVER_DOMAIN'))
	) {
		@http_response_code(500);
		die('The following constants must NOT be defined when SMART_FRAMEWORK_SRVPROXY_ENABLED is NOT SET to TRUE: SMART_FRAMEWORK_SRVPROXY_CLIENT_IP, SMART_FRAMEWORK_SRVPROXY_CLIENT_PROXY_IP, SMART_FRAMEWORK_SRVPROXY_SERVER_PROTO, SMART_FRAMEWORK_SRVPROXY_SERVER_PORT, SMART_FRAMEWORK_SRVPROXY_SERVER_IP, SMART_FRAMEWORK_SRVPROXY_SERVER_DOMAIN');
	} //end if
	define('SMART_FRAMEWORK_SRVPROXY_CLIENT_IP', 'REMOTE_ADDR');
	define('SMART_FRAMEWORK_SRVPROXY_CLIENT_PROXY_IP', '<HTTP_X_FORWARDED_FOR>,<HTTP_CLIENT_IP>,<HTTP_X_REAL_IP>,<HTTP_VIA>');
	define('SMART_FRAMEWORK_SRVPROXY_SERVER_PROTO', null);
	define('SMART_FRAMEWORK_SRVPROXY_SERVER_PORT', false);
	define('SMART_FRAMEWORK_SRVPROXY_SERVER_IP', null);
	define('SMART_FRAMEWORK_SRVPROXY_SERVER_DOMAIN', null);
} //end if else
//--
if(defined('SMART_FRAMEWORK_SRVPROXY_UNTRUSTED_CLIENT_IP')) {
	@http_response_code(500);
	die('A reserved constant was defined: SMART_FRAMEWORK_SRVPROXY_UNTRUSTED_CLIENT_IP ... this constant is reserved to be used internally only by Smart.Framework and should never be defined in any config or custom context !');
} //end if
//--
if(defined('SMART_ERROR_AREA')) { // display this error area
	@http_response_code(500);
	die('SMART_ERROR_AREA cannot be defined outside ERROR HANDLER');
} //end if
if(defined('SMART_ERROR_LOGSUFFIXDIR')) {
	@http_response_code(500);
	die('SMART_ERROR_LOGSUFFIXDIR cannot be defined outside ERROR HANDLER');
} //end if
if(defined('SMART_FRAMEWORK_INFO_DIR_LOG')) {
	@http_response_code(500);
	die('A Reserved Constant have been already defined: SMART_FRAMEWORK_INFO_DIR_LOG');
} //end if
if(SMART_STANDALONE_APP === true) {
	if(!defined('APP_CUSTOM_LOG_PATH')) {
		@http_response_code(500);
		die('APP_CUSTOM_LOG_PATH must be defined for STANDALONE APPS');
	} //end if
	if((string)SMART_FRAMEWORK_LIB_PATH != '') {
		@http_response_code(500);
		die('SMART_FRAMEWORK_LIB_PATH must be empty for STANDALONE APPS');
	} //end if
	define('SMART_ERROR_LOGSUFFIXDIR', (string)APP_CUSTOM_LOG_PATH); // must have the trailing slash and must not have a prefix slash
	define('SMART_ERROR_AREA', 'STD');
	define('SMART_FRAMEWORK_INFO_DIR_LOG', (string)SMART_ERROR_LOGSUFFIXDIR);
} else {
	if(defined('APP_CUSTOM_LOG_PATH')) {
		@http_response_code(500);
		die('APP_CUSTOM_LOG_PATH must not be defined, except for STANDALONE APPS');
	} //end if
	define('SMART_ERROR_LOGSUFFIXDIR', 'tmp/logs/'); // must have the trailing slash and must not have a prefix slash ; for smart framework default is 'tmp/logs/'
	if(SMART_FRAMEWORK_ADMIN_AREA === true) {
		if((string)SMART_FRAMEWORK_RUNTIME_MODE == 'web.task') { // {{{SYNC-RUNTIME-MODE-OVERRIDE-TASK}}}
			define('SMART_ERROR_AREA', 'TSK');
			define('SMART_FRAMEWORK_INFO_DIR_LOG', (string)SMART_ERROR_LOGSUFFIXDIR.'tsk/');
		} else {
			define('SMART_ERROR_AREA', 'ADM');
			define('SMART_FRAMEWORK_INFO_DIR_LOG', (string)SMART_ERROR_LOGSUFFIXDIR.'adm/');
		} //end if else
	} else {
		define('SMART_ERROR_AREA', 'IDX');
		define('SMART_FRAMEWORK_INFO_DIR_LOG', (string)SMART_ERROR_LOGSUFFIXDIR.'idx/');
	} //end if else
} //end if else
if(!defined('SMART_ERROR_LOGSUFFIXDIR')) {
	@http_response_code(500);
	die('Failed to define the SMART_ERROR_LOGSUFFIXDIR ...');
} //end if
if(!defined('SMART_ERROR_AREA')) {
	@http_response_code(500);
	die('Failed to define the SMART_ERROR_AREA ...');
} //end if
if(!defined('SMART_FRAMEWORK_INFO_DIR_LOG')) {
	@http_response_code(500);
	die('Failed to define the SMART_FRAMEWORK_INFO_DIR_LOG ...');
} //end if
//--
if(defined('SMART_ERROR_LOGDIR')) {
	@http_response_code(500);
	die('SMART_ERROR_LOGDIR cannot be defined outside ERROR HANDLER');
} //end if
if(!define('SMART_ERROR_LOGDIR', (string)smart__framework__err__handler__get__absolute_logpath((string)SMART_ERROR_LOGSUFFIXDIR))) { // the function will check if path is safe and correct ; if not will raise a fatal error !
	@http_response_code(500);
	die('Failed to define the SMART_ERROR_LOGDIR ...');
} //end if
//--
array_map(function($const){
	if(!defined((string)$const)) {
		@http_response_code(500);
		die('A required constant has not been defined: '.$const);
	}
	if(
		((string)trim((string)str_replace(['\\', '/', '.', '-', '_', '#', '@', ' '], '', (string)constant((string)$const))) == '') OR
		((string)substr((string)constant((string)$const), -1, 1) != '/')
	) {
		@http_response_code(500);
		die('Invalid constant '.$const.' ...');
	} //end if
},
[
	'SMART_ERROR_LOGSUFFIXDIR',
	'SMART_ERROR_LOGDIR',
]);
//--
if(defined('SMART_ERROR_LOGFILE')) { // if set as 'log' or 'off' the errors will be registered into this local error log file
	@http_response_code(500);
	die('SMART_ERROR_LOGFILE cannot be defined outside ERROR HANDLER');
} //end if
define('SMART_ERROR_LOGFILE', 'phperrors-'.strtolower((string)SMART_ERROR_AREA).'-'.date('Y-m-d@H').'.log');
if(!defined('SMART_ERROR_LOGFILE')) { // if set as 'log' or 'off' the errors will be registered into this local error log file
	@http_response_code(500);
	die('Failed to define the SMART_ERROR_LOGFILE ...');
} //end if
//--

//==
if(((string)SMART_ERROR_HANDLER == 'log') AND (SMART_FRAMEWORK_DEBUG_MODE !== true)) { // if log and not debug :: hide errors and just log them
	ini_set('display_startup_errors', '0');
	ini_set('display_errors', '0');
} else { // dev or log+debug :: display errors and log them
	ini_set('display_startup_errors', '1');
	ini_set('display_errors', '1');
} //end if else
ini_set('track_errors', '0');
//== E_STRICT has been deprecated and removed since PHP 8.4
if((string)SMART_ERROR_HANDLER == 'log') {
	error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED); // error reporting for display only, production
} else {
	error_reporting(E_ALL & ~E_NOTICE); // error reporting for display only, development (show deprecated)
} //end if else
//==
$smart_____framework_____last__exception_html = ''; // initialize, empty
$smart_____framework_____last__error = ''; // initialize, empty
$smart_____framework_____is_html_last__error = false; // initialize, false
//==
// IMPORTANT: SINCE PHP7 there is no need to reserve memory for logging ... it does itself and will log via the err log registered shutdown handler below
//==
set_error_handler(function($errno, $errstr, $errfile, $errline) {
	//--
	global $smart_____framework_____last__exception_html;
	global $smart_____framework_____last__error;
	global $smart_____framework_____is_html_last__error;
	//--
	if(((string)SMART_ERROR_HANDLER == 'log') AND (SMART_FRAMEWORK_DEBUG_MODE !== true)) { // if long and debug not enabled :: hide errors and just log them
		$smart_____framework_____last__error = ''; // hide errors if explicit set so (make sense in production environments)
		$smart_____framework_____is_html_last__error = false;
	} //end if
	//-- The following error types cannot be handled with a user defined function: E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING, and most of E_STRICT raised in the file where set_error_handler() is called : http://php.net/manual/en/function.set-error-handler.php
	$app_halted = '';
	$is_supressed = false;
	$is_fatal = false;
	switch($errno) { // friendly err names
		case E_DEPRECATED:
			$ferr = 'DEPRECATED';
		//	if(0 == error_reporting()) { // fix: don't log E_NOTICE from @functions
			if(!(error_reporting() & $errno)) { // fix: don't log E_NOTICE from @functions, fix for PHP 8
				$is_supressed = true;
			} //end if
			break;
		case E_NOTICE:
			$ferr = 'NOTICE';
		//	if(0 == error_reporting()) { // fix: don't log E_NOTICE from @functions
			if(!(error_reporting() & $errno)) { // fix: don't log E_NOTICE from @functions, fix for PHP 8
				$is_supressed = true;
			} //end if
			break;
		case E_WARNING:
			$ferr = 'WARNING';
		//	if(0 == error_reporting()) { // fix: don't log E_WARNING from @functions
			if(!(error_reporting() & $errno)) { // fix: don't log E_WARNING from @functions, fix for PHP 8
				$is_supressed = true;
			} //end if
			break;
		case E_USER_NOTICE:
			$ferr = 'APP-NOTICE';
			if((string)SMART_ERROR_HANDLER == 'log') {
				$is_supressed = true;
			} //end if
			break;
		case E_USER_WARNING: // this must handle both: E_USER_WARNING and the emulation of E_USER_ERROR
			if(
				(strpos((string)$errstr, '#SMART-FRAMEWORK.ERROR#') === 0) // handler for raise_error() ; {{{SF-PHP-EMULATE-E_USER_ERROR}}}
				OR
				(strpos((string)$errstr, '***** EXCEPTION ***** [#') === 0) // handler for Exception() ; {{{SF-PHP-EMULATE-EXCEPTION-E_USER_ERROR}}}
			) { // emulate E_USER_ERROR
				$is_fatal = true;
				$app_halted = ' :: Execution HALTED !';
				$ferr = 'APP-ERROR';
			} else { // E_USER_WARNING
				$ferr = 'APP-WARNING';
			} //end if else
			break;
	//	case E_USER_ERROR: // this is N/A since PHP 8.4
	//		$is_fatal = true;
	//		$app_halted = ' :: Execution HALTED !';
	//		$ferr = 'APP-ERROR';
	//		break;
		case E_RECOVERABLE_ERROR:
			$is_fatal = true;
			$app_halted = ' :: Execution FAILED !';
			$ferr = 'ERROR';
			break;
		default:
			$ferr = 'OTHER';
	} //end switch
	//--
	if(((string)SMART_ERROR_HANDLER != 'log') OR (SMART_FRAMEWORK_DEBUG_MODE === true)) { // if not log or debug
		$is_supressed = false;
	} //end if
	if((string)SMART_ERROR_HANDLER == 'log') {
		if(defined('SMART_ERROR_SILENCE_WARNS_NOTICE')) { // to silence warnings and notices from logs in prod environments with debug on this must be set explicit in init.php as: define('SMART_ERROR_SILENCE_WARNS_NOTICE', true); // Error Handler silence warnings and notices log (available just for SMART_ERROR_HANDLER=log)
			$is_supressed = true;
		} //end if
	} //end if
	//-- {{{SYNC-SF-ERR-CLIENT-IP-PROXIES}}}
	$clientip = '';
	if(
		(defined('SMART_FRAMEWORK_SRVPROXY_ENABLED') AND (SMART_FRAMEWORK_SRVPROXY_ENABLED === true))
		AND
		defined('SMART_FRAMEWORK_SRVPROXY_CLIENT_IP')
	) {
		if((string)trim((string)SMART_FRAMEWORK_SRVPROXY_CLIENT_IP) != '') {
			$clientip = (string) trim((string)($_SERVER[(string)trim((string)SMART_FRAMEWORK_SRVPROXY_CLIENT_IP)] ?? ''));
		} //end if
	} else {
		$clientip = (string) trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
	} //end if else
	$proxies = [];
	if( // must not check if SMART_FRAMEWORK_SRVPROXY_ENABLED because this have always use the values set in SMART_FRAMEWORK_SRVPROXY_CLIENT_PROXY_IP
		defined('SMART_FRAMEWORK_SRVPROXY_CLIENT_PROXY_IP')
	) {
		$tmp_proxies = (string) strtoupper((string)SMART_FRAMEWORK_SRVPROXY_CLIENT_PROXY_IP);
		$tmp_proxies = (string) strtr((string)$tmp_proxies, [ '<' => '', '>' => '' ]);
		$tmp_proxies = (array) explode(',', (string)$tmp_proxies);
		foreach($tmp_proxies as $key => $val) {
			$val = (string) trim((string)$val);
			if($val != '') {
				if(isset($_SERVER[(string)$val])) {
					$proxies[] = (string) trim((string)$_SERVER[(string)$val]);
				} //end if
			} //end if
		} //end if
		$tmp_proxies = null;
	} //end if
	$proxies = (string) implode(' ; ', (array)$proxies);
	//-- #end sync
	if(($is_supressed !== true) OR ($is_fatal === true)) {
		$log_message = (string) "\n".
			'==================================='."\n".
			'PHP '.PHP_VERSION.' [SMART-ERR-HANDLER:'.strtoupper((string)SMART_FRAMEWORK_ENV).'] #'.$errno.' ['.$ferr.']'.$app_halted.' @ '.date('Y-m-d H:i:s O')."\n".
			'-----------------'."\n".
			'HTTP-METHOD: '.($_SERVER['REQUEST_METHOD'] ?? '').' # '.'CLIENT: '.trim((string)$clientip.' ; '.$proxies, '; ').' @ '.($_SERVER['HTTP_USER_AGENT'] ?? '')."\n".
			'URI: ['.SMART_ERROR_AREA.'] @ '.($_SERVER['SERVER_NAME'] ?? '').':'.($_SERVER['SERVER_PORT'] ?? '').($_SERVER['REQUEST_URI'] ?? '')."\n".
			'-----------------'."\n".
			'Script: '.$errfile."\n".
			'Line number: '.$errline."\n".
			'-----------------'."\n".
			$errstr."\n".
			'==================================='."\n"
		; // {{{SYNC-SF-ERR-LOG-FORMAT}}}
		if((is_dir((string)SMART_ERROR_LOGDIR)) && (is_writable((string)SMART_ERROR_LOGDIR))) { // here must be is_dir(), is_writable() and file_put_contents() as the smart framework libs are not yet initialized in this phase ...
			error_log(
				(string) $log_message,
				0,
				(string) SMART_ERROR_LOGDIR.SMART_ERROR_LOGFILE
			);
		} else {
			error_log(
				(string) $log_message,
				4 // send the message to the SAPI (server) logging handler to avoid lost
			);
		} //end if
	} //end if
	//--
//	if(($errno === E_RECOVERABLE_ERROR) OR ($errno === E_USER_ERROR)) {
	if(($errno === E_RECOVERABLE_ERROR) OR (($errno === E_USER_WARNING) AND ($is_fatal === true))) {
		//--
		// this is necessary for: E_RECOVERABLE_ERROR and E_USER_ERROR (which is used just for Exceptions) and all other PHP errors which are FATAL and will stop the execution ; For WARNING / NOTICE type errors we just want to log them, not to stop the execution !
		//--
		$script = '';
		$appenv = null;
		$err_prepend = '';
		$err_append = '';
		//--
		$message = 'Server Script Execution Halted ...'."\n".'See the App Error Log for details';
		if((string)SMART_ERROR_HANDLER == 'dev') { // if dev mode
			$message .= ':';
			$script = (string) SMART_ERROR_LOGDIR.SMART_ERROR_LOGFILE;
			$appenv = (string) SMART_FRAMEWORK_ENV;
			if($smart_____framework_____is_html_last__error !== true) {
				$smart_____framework_____last__error = (string) '<div style="font-size:1.5rem; color:#222222; font-weight:bold;"><i>Error&nbsp;Message</i>: <span style="color:#444444;">'.htmlspecialchars((string)$smart_____framework_____last__error, ENT_HTML401 | ENT_COMPAT | ENT_SUBSTITUTE, (string)SMART_FRAMEWORK_CHARSET, true).'</span></div>';
				$err_prepend = '<div style="text-align:left!important;"> &nbsp; <span style="font-size:3rem; color:#ED2839;"><img width="64" height="64" src="'.smart__framework__err__handler__get__safe_img_url('php-logo.svg').'" alt="[php]" title="[php]"> <b>Code Execution FAILED</b></span> <img align="right" width="48" height="48" src="'.smart__framework__err__handler__get__safe_img_url('sign-error.svg').'" alt="[ERR]" title="[ERR]"><div><hr size="1"><pre style="white-space:pre-wrap;overflow-x:auto;">';
				$err_append = '</pre></div><br><div style="color:#888888; text-align:right;"><small>'.date('Y-m-d H:i:s O').'</small><hr size="1"></div><div title="Powered by Smart.Framework" style="cursor:help;"><center><img width="64" height="64" src="'.smart__framework__err__handler__get__safe_img_url('sf-logo.svg').'" alt="[S.F]" title="[S.F]"></center></div></div>';
			} //end if
		} else {
			$message .= '.';
		} //end if
		//--
		$errlogo = '<img src="'.smart__framework__err__handler__get__safe_img_url('sign-crit-error.svg').'" alt="[!]" title="[!]" width="32" height="32">';
		//--
		if(!headers_sent()) {
			@http_response_code(500); // try, if not headers send
		} //end if
		die('<!DOCTYPE html>'."\n".'<!-- Smart.Framework @ Smart Error Reporting / Smart Error Handler :: '.date('Y-m-d H:i:s O').' -->'."\n".'<html>'."\n".'<head><meta charset="'.SMART_FRAMEWORK_CHARSET.'"><title>! ERROR !</title><link rel="icon" href="data:,"><style>* { font-family: \'IBM Plex Mono\',mono; font-smooth: always; } hr { border: none 0; border-top: 1px solid #EEEEEE; height: 1px; }</style></head>'."\n".'<body>'."\n".'<br><div><center><div style="min-width:300px; max-width:'.(SMART_FRAMEWORK_ENV === 'dev' ? '75vw' : '57vw').'; border: 1px solid #EEEEEE; margin-top:10px; margin-bottom:10px; color:#333333;"><table cellpadding="4" style="max-width:70vw;"><tr valign="top"><td width="32">'.$errlogo.'</td><td>&nbsp;</td><td><b><span style="font-size:1.75rem;">HTTP 500 Internal Server Error</span><br><span style="font-size:1.25rem">'.'App Critical Error'.($appenv ? ' ('.$appenv.')' : '').' @ '.SMART_ERROR_AREA.' [#'.$errno.']:</span><br>'.'</b><i>'.nl2br((string)htmlspecialchars((string)$message, ENT_HTML401 | ENT_COMPAT | ENT_SUBSTITUTE, (string)SMART_FRAMEWORK_CHARSET, true), false).($script ? '<br><span style="color:#778899;">'.htmlspecialchars((string)$script, ENT_HTML401 | ENT_COMPAT | ENT_SUBSTITUTE, (string)SMART_FRAMEWORK_CHARSET, true).'</span>' : '').'</i></td></tr></table></div><br>'.(($smart_____framework_____last__error || $smart_____framework_____last__exception_html) ? "\n".'<!-- START: Last ERR Message --><div style="max-width:70vw; padding:5px; border: 1px solid #F0F0F0; border-radius:3px;"><span style="color:#222222; font-style:italic; font-weight:bold; font-size:3rem;"><span style="color:#4e5a92;">PHP&nbsp;'.PHP_VERSION.'</span> ERROR</span><br><br>'.$err_prepend.$smart_____framework_____last__error.$smart_____framework_____last__exception_html.$err_append.'<br></div><br><br><hr size="1">'."\n".'<!-- #END: Last ERR Message -->'."\n" : '').'</center></div>'."\n".'</body>'."\n".'</html>'."\n");
		//--
	} //end if else
	//--
}, E_ALL & ~E_NOTICE); // error reporting for logging
//==
set_exception_handler(function($exception) { // no type for EXCEPTION to be PHP 7+ compatible
	//--
	global $smart_____framework_____last__exception_html;
	//--
	$code = (int) $exception->getCode();
	$message = (string) $exception->getMessage();
	$details = ' Script: '.(string)$exception->getFile()."\n".' Line: '.(string)$exception->getLine();
	$exid = (string) sha1('Exception:'.$code.':'.$message.':'.$details);
	//--
	if(is_array($exception->getTrace())) {
		//--
		$arr = (array) $exception->getTrace();
		//--
		$hide_last_err = false;
		if(((string)SMART_ERROR_HANDLER != 'log') OR (SMART_FRAMEWORK_DEBUG_MODE === true)) { // if not log or debug is on
			$hide_last_err = true;
		} //end if
		if($hide_last_err !== false) {
			$smart_____framework_____last__exception_html = (string) "\n".'<!-- Exception -->'.'<b><span style="color:#C2203F;"><i>Exception Throw [#'.$code.'] / '.$exid.'</i></span><br><br><div style="font-size:1.5rem; color:#222222; font-weight:bold;"><i>Exception&nbsp;Message</i>: <span style="color:#444444;">'.htmlspecialchars((string)$message, ENT_HTML401 | ENT_COMPAT | ENT_SUBSTITUTE, (string)SMART_FRAMEWORK_CHARSET, true).'</span></div></b><div style="color:#555555; padding:5px; margin:5px;">'.htmlspecialchars((string)$details, ENT_HTML401 | ENT_COMPAT | ENT_SUBSTITUTE, (string)SMART_FRAMEWORK_CHARSET, true).'</div>'.'<!-- # Exception -->'."\n"; // fix for PHP 7+
		} //end if
		//--
		if(SMART_FRAMEWORK_DEBUG_MODE === true) { // if debug
			//--
			$details .= "\n".print_r($arr,1);
			//--
		} else {
			//--
			for($i=0; $i<2; $i++) { // trace just 2 levels
				$details .= "\n".'  ----- Line #'.($arr[$i]['line'] ?? '').' @ Class: ['.($arr[$i]['class'] ?? '').'] '.($arr[$i]['type'] ?? '').' Function: ['.($arr[$i]['function'] ?? '').'] | File: '.($arr[$i]['file'] ?? '');
				$details .= "\n".'    ----- Args * '.(isset($arr[$i]['args']) ? print_r($arr[$i]['args'],1) : '');
			} //end for
			//--
		} //end if else
		//--
	} //end if
	//--
//	trigger_error('***** EXCEPTION ***** [#'.$exid.']:'."\n".'Error-Message: '.$message."\n".$details, E_USER_ERROR); // log the exception as ERROR
	trigger_error('***** EXCEPTION ***** [#'.$exid.']:'."\n".'Error-Message: '.$message."\n".$details, E_USER_WARNING); // log the exception as ERROR ; {{{SF-PHP-EMULATE-EXCEPTION-E_USER_ERROR}}}
	//-- below code would be executed only if E_USER_ERROR fails to stop the execution
	if(!headers_sent()) {
		@http_response_code(500); // try, if not headers send
	} //end if
	die('Execution Halted. Application Level Exception. See the App Error Log for more details.');
	//--
});
//==
ini_set('ignore_repeated_source', '0'); // do not ignore repeated errors if in different files
if(((string)SMART_ERROR_HANDLER == 'log') AND (SMART_FRAMEWORK_DEBUG_MODE !== true)) { // if log and not debug :: hide errors and just log them
	ini_set('ignore_repeated_errors', '1'); // ignore repeated errors in the same file on the same line
} else { // dev or log+debug
	ini_set('ignore_repeated_errors', '0'); // do not ignore repeated errors
} //end if else
ini_set('error_prepend_string', '');
ini_set('error_append_string', '');
ini_set('html_errors', '0'); // display errors in TEXT format
ini_set('log_errors', '1'); // log always the errors
ini_set('log_errors_max_len', 65535); // max size of one error to log 16k
ini_set('error_log', (string)SMART_ERROR_LOGDIR.SMART_ERROR_LOGFILE); // error log file
//==
register_shutdown_function(function(){
	//--
	$error = error_get_last();
	if(is_array($error) && array_key_exists('type', (array)$error)) {
		if(!isset($error['message'])) {
			$error['message'] = 'Unknown ERROR ...';
		} //end if
		if(!isset($error['file'])) {
			$error['file'] = '?';
		} //end if
		if(!isset($error['line'])) {
			$error['line'] = 0;
		} //end if
		switch($error['type']) {
			case E_ERROR:
			case E_PARSE:
			case E_CORE_ERROR:
			case E_COMPILE_ERROR:
				//-- {{{SYNC-SF-ERR-CLIENT-IP-PROXIES}}}
				$clientip = '';
				if(
					(defined('SMART_FRAMEWORK_SRVPROXY_ENABLED') AND (SMART_FRAMEWORK_SRVPROXY_ENABLED === true))
					AND
					defined('SMART_FRAMEWORK_SRVPROXY_CLIENT_IP')
				) {
					if((string)trim((string)SMART_FRAMEWORK_SRVPROXY_CLIENT_IP) != '') {
						$clientip = (string) trim((string)($_SERVER[(string)trim((string)SMART_FRAMEWORK_SRVPROXY_CLIENT_IP)] ?? ''));
					} //end if
				} else {
					$clientip = (string) trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
				} //end if else
				$proxies = [];
				if( // must not check if SMART_FRAMEWORK_SRVPROXY_ENABLED because this have always use the values set in SMART_FRAMEWORK_SRVPROXY_CLIENT_PROXY_IP
					defined('SMART_FRAMEWORK_SRVPROXY_CLIENT_PROXY_IP')
				) {
					$tmp_proxies = (string) strtoupper((string)SMART_FRAMEWORK_SRVPROXY_CLIENT_PROXY_IP);
					$tmp_proxies = (string) strtr((string)$tmp_proxies, [ '<' => '', '>' => '' ]);
					$tmp_proxies = (array) explode(',', (string)$tmp_proxies);
					foreach($tmp_proxies as $key => $val) {
						$val = (string) trim((string)$val);
						if($val != '') {
							if(isset($_SERVER[(string)$val])) {
								$proxies[] = (string) trim((string)$_SERVER[(string)$val]);
							} //end if
						} //end if
					} //end if
					$tmp_proxies = null;
				} //end if
				$proxies = (string) implode(' ; ', (array)$proxies);
				//-- #end sync
				$log_message = (string) "\n".
					'==================================='."\n".
					'PHP '.PHP_VERSION.' [SMART-ERR-HANDLER:'.strtoupper((string)SMART_FRAMEWORK_ENV).'] #0 [APP-SHUTDOWN-ERROR] :: Execution COMPLETED ! @ '.date('Y-m-d H:i:s O')."\n".
					'-----------------'."\n".
					'HTTP-METHOD: '.($_SERVER['REQUEST_METHOD'] ?? '').' # '.'CLIENT: '.trim((string)$clientip.' ; '.$proxies, '; ').' @ '.($_SERVER['HTTP_USER_AGENT'] ?? '')."\n".
					'URI: ['.SMART_ERROR_AREA.'] @ '.($_SERVER['SERVER_NAME'] ?? '').':'.($_SERVER['SERVER_PORT'] ?? '').($_SERVER['REQUEST_URI'] ?? '')."\n".
					'-----------------'."\n".
					'Script: '.$error['file']."\n".
					'Line number: '.$error['line']."\n".
					'-----------------'."\n".
					'Error-Message: '.$error['message']."\n".
					'==================================='."\n"
				; // {{{SYNC-SF-ERR-LOG-FORMAT}}}
				//--
				if((is_dir((string)SMART_ERROR_LOGDIR)) && (is_writable((string)SMART_ERROR_LOGDIR))) { // here must be is_dir(), is_writable() and file_put_contents() as the smart framework libs are not yet initialized in this phase ...
					error_log(
						(string) $log_message,
						0,
						(string) SMART_ERROR_LOGDIR.SMART_ERROR_LOGFILE
					);
				} else {
					error_log(
						(string) $log_message,
						4 // send the message to the SAPI (server) logging handler to avoid lost
					);
				} //end if else
				//--
				break;
			default:
				// don't handle
		} //end switch
	} //end if
	//--
});
//==
/**
 * Function Error Handler Get Image URL (html escaped)
 * @access 		private
 * @internal
 */
function smart__framework__err__handler__get__safe_img_url($img) {
	//--
	if(!is_string($img)) {
		$img = '';
	} //end if
	$img = (string)trim((string)$img);
	switch((string)$img) {
		case 'sign-crit-error.svg':
		case 'sign-error.svg':
		case 'php-logo.svg':
		case 'sf-logo.svg':
			break;
		default:
			$img = '';
	} //end switch
	//--
	$prefix = (string) trim((string)dirname((string)($_SERVER['SCRIPT_NAME'] ?? '')));
	//--
	if(((string)$prefix == '') || ((string)$prefix == '/') || ((string)$prefix == '\\') || ((string)$prefix == '.') || ((string)$prefix == '..')) {
		$prefix = ''; // no prefix
	} else {
		$prefix .= '/'; // fix: add a trailing slash
	} //end if
	//--
	if(((string)trim((string)$img) != '') AND ((string)trim((string)SMART_FRAMEWORK_LIB_PATH) != '')) {
		$data = (string) $prefix.SMART_FRAMEWORK_LIB_PATH.'img/'.$img;
	} else {
		$data = 'data:,';
	} //end if else
	//--
	return (string) htmlspecialchars((string)$data, ENT_HTML401 | ENT_COMPAT | ENT_SUBSTITUTE, (string)SMART_FRAMEWORK_CHARSET, true);
	//--
} //END FUNCTION
//==
/**
 * Function: Get the Error Handler log folder ; it must be .htaccess protected
 * @hints 		The phperrors-idx-yyyy-mm-dd@hh.log and phperrors-adm-yyyy-mm-dd@hh.log error log files will be written into this folder
 * @access 		private
 * @internal
 */
function smart__framework__err__handler__get__absolute_logpath($suffix_path) {
	//--
	// the PHP Bug #31570 (not fixed since a very long time) : cannot access relative paths after destruct of main executors started, ex: handlers registered with register_shutdown_function()
	//--
	// INFO: this must be a full / absolute path (not a relative path) because register_shutdown_function() handlers may not always work with relative paths
	// EXAMPLE: need to log to tmp/logs after majority of objects have been destroyed and no more detection of relative path
	// NOTICE: this converts windows path from using backslash to using slash
	//--
	if(!is_string($suffix_path)) {
		@http_response_code(500);
		die('Invalid parameter type (not string) for error handler method: '.__METHOD__);
		return '';
	} //end if
	$suffix_path = (string) trim((string)$suffix_path);
	//--
	$unix_regex = '/^[_a-zA-Z0-9\-\.@\#\/]+$/'; // regex for linux/unix ; ; {{{SYNC-CHK-SAFE-FILENAME}}} with extra `/`
	$windows_regex = '/^[_a-zA-Z0-9\-\.@\#\/\:]+$/'; // regex for windows, after converting backslashes to normal slashes ; {{{SYNC-CHK-SAFE-FILENAME}}} with extra `/` and `:`
	//--
	if(
		((string)$suffix_path == '') OR
		((string)$suffix_path == '.') OR
		((string)$suffix_path == '..') OR
		(strpos((string)$suffix_path, '.') === 0) OR
		(strpos((string)$suffix_path, '..') !== false) OR
		(strpos((string)$suffix_path, '\\') !== false) OR // must not have any backslash
		(strpos((string)$suffix_path, '//') !== false) OR // must not have double slashes
		((string)$suffix_path == '/') OR // must not be /
		(strpos((string)$suffix_path, '/') === 0) OR // must not start with a slash
		((string)substr((string)$suffix_path, -1, 1) != '/') OR // must have the last trailing slash
		(!preg_match((string)$unix_regex, (string)$suffix_path)) // this must be unix compliant as will be added as suffix to the $path below which will be unix conformed also on windows
	) { // if realpath fails and return an empty path or /
		@http_response_code(500);
		die('Smart.Framework # ERROR HANDLER: Invalid Log Path suffix `'.$suffix_path.'`');
		return '';
	} //end if
	//--
	$path = (string) trim((string)realpath('./')); // get the current absolute path of current running folder (this will be run from index.php or admin.php or task.php which are outside lib/ ... so this scenario is considered)
	if((string)DIRECTORY_SEPARATOR == '\\') { // if on Windows, Fix Path Separator !!!
		if(strpos((string)$path, '\\') !== false) {
			$path = (string) str_replace((string)DIRECTORY_SEPARATOR, '/', (string)$path); // convert windows path from using backslash to using slash
		} //end if
		$regex = (string) $windows_regex;
	} else {
		$regex = (string) $unix_regex;
	} //end if else
	$path = (string) trim((string)rtrim((string)$path, '/'));
	if(
		((string)$path == '') OR
		((string)$path == '.') OR
		((string)$path == '..') OR
		(strpos((string)$path, '.') === 0) OR
		(strpos((string)$path, '..') !== false) OR
		(strpos((string)$path, '\\') !== false) OR // must not have any backslash
		(strpos((string)$path, '//') !== false) OR // must not have double slashes
		((string)$path == '/') OR // must not be /
		((string)substr((string)$path, -1, 1) == '/') OR // must not have the last trailing slash, it was rtrimmed above and will be added below
		(!preg_match((string)$regex, (string)$path)) // on windows can have : from drive letter prefix ..., on unix not (regex vary by os)
	) { // if realpath fails and return an empty path or /
		@http_response_code(500);
		die('Smart.Framework # ERROR HANDLER: the RealPath detection is broken in the current PHP installation. Detected Path is `'.$path.'`. Please fix this by install a PHP version without this bug (PHP Bug #31570) ...');
		return '';
	} //end if
	//--
	$path .= '/'; // add last slash to the path after above checks, it has been trimmed above
	//-- unixman: fix ceil
	$max_path_len = (int) ceil((string)(PHP_MAXPATHLEN * 0.33)); // the path to the Smart.Framework installation should not be longer than 33% of max path length supported by OS
	//--
	if((int)strlen((string)$path) > (int)$max_path_len) {
		@http_response_code(500);
		die('Smart.Framework # ERROR HANDLER: the Curent installation Path detected is too long `'.$path.'` (length=`'.(int)strlen((string)$path).'`) ; must be max 33% of the PHP_MAXPATHLEN which is `'.PHP_MAXPATHLEN.'` ...');
		return '';
	} //end if
	//--
	$path .= (string) $suffix_path; // append the suffix (it was checked above)
	//--
	if( // final check
		((string)$path == '') OR
		((string)$path == '/') OR // must not be /
		((string)$path == '\\') OR // must not be /
		(strpos((string)$path, '..') !== false) OR
		((string)substr((string)$path, -1, 1) != '/') OR // must have the last trailing slash
		(!preg_match((string)$regex, (string)$path)) // on windows can have : from drive letter prefix ..., on unix not (regex vary by os)
	) {
		@http_response_code(500);
		die('Smart.Framework # ERROR HANDLER: Something went wrong with composing the absolute path to the logs dir: `'.$path.'`');
		return '';
	} //end if
	//--
	return (string) $path;
	//--
} //END FUNCTION
//==

// end of php code
