<?php
// AppCodeUnPack - Deploy Manager: NetArchive UnPacker
// (c) 2006-2021 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

//##############################################################################
//############### RUNTIME: DO NOT MODIFY CODE BELOW !!!
//##############################################################################

//--
if(!function_exists('apache_setenv')) {
	@http_response_code(500);
	die('FATAL ERROR: This script requires PHP and Apache Server !'); // Apache is required for .htaccess protection
} //end if
//--

//== customize per app
//--
define('APP_ERR_LOGFILE', '#APPCODE-UNPACK#/---AppCodeUnPack-PHP-Errors---.log'); 	// path to error log :: (protected) :: {{{SYNC-UNPACK-FOLDER-NAME}}}
define('APP_MAX_RUN_TIMEOUT', '610');												// max execution time in seconds :: differs, ~ 10 min (for unpack)
//--
//==

//##### #START: SHARED INIT

//== required constants for AppPackUtils
//--
define('SMART_FRAMEWORK_CHARSET', 			'UTF-8'); 				// App Charset: UTF-8
define('SMART_FRAMEWORK_CHMOD_DIRS', 		0770);					// Folder Permissions: 0770 | 0700
define('SMART_FRAMEWORK_CHMOD_FILES', 		0660);					// File Permissions: 0660 | 0600
//--
if(version_compare(phpversion(), '7.3') < 0) { // check for PHP 7.3 or later
	@http_response_code(500);
	die('PHP Runtime not supported : '.phpversion().' !'.'<br>PHP versions to run this software are: 7.2 / 7.3 / 7.4 or later');
} //end if
//--
date_default_timezone_set('UTC');
//--
ini_set('zlib.output_compression', '0');							// disable ZLib Output Compression
if((string)ini_get('zlib.output_compression') != '0') {
	@http_response_code(500);
	die('FATAL ERROR: The PHP.INI ZLib Output Compression cannot be disabled !');
} //end if
if((string)ini_get('zlib.output_handler') != '') {
	@http_response_code(500);
	die('FATAL ERROR: The PHP.INI Zlib Output Handler must be unset !');
} //end if
if((string)ini_get('output_handler') != '') {
	@http_response_code(500);
	die('FATAL ERROR: The PHP.INI Output Handler must be unset !');
} //end if
//--
if((string)ini_get('zend.multibyte') != '0') {
	@http_response_code(500);
	die('FATAL ERROR: The PHP.INI Zend-MultiByte must be disabled ! Unicode support is managed via MBString ...');
} //end if
ini_set('default_charset', (string)SMART_FRAMEWORK_CHARSET); 		// default charset UTF-8
if(!function_exists('mb_internal_encoding')) { 						// *** MBString is required ***
	@http_response_code(500);
	die('FATAL ERROR: The MBString PHP Module is required for Unicode support !');
} //end if
if(mb_internal_encoding((string)SMART_FRAMEWORK_CHARSET) !== true) { // this setting is required for UTF-8 mode
	@http_response_code(500);
	die('FATAL ERROR: Failed to set MB Internal Encoding to: '.SMART_FRAMEWORK_CHARSET);
} //end if
if(mb_substitute_character(63) !== true) {
	@http_response_code(500);
	die('FATAL ERROR: Failed to set the MB Substitute Character to standard: 63(?) ...');
} //end if
//--
ini_set('default_socket_timeout', '60');							// socket timeout (1 min.)
ini_set('max_execution_time', (string)(int)APP_MAX_RUN_TIMEOUT); 	// execution timeout this value must be close to httpd.conf's timeout
ini_set('memory_limit', '512M');									// set the memory limit
ini_set('ignore_user_abort', '0');									// do no ignore user abort
ini_set('auto_detect_line_endings', '0');							// auto detect line endings
ini_set('y2k_compliance', '0');										// it is recommended to use this as disabled since POSIX systems keep time based on UNIX epoch
ini_set('precision', '14');											// decimal number precision
//--
ini_set('display_errors', '0');										// don't display runtime errors (use the log)
ini_set('error_log', (string)APP_ERR_LOGFILE); 						// error log file
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED); 	// error reporting for display only
set_error_handler(function($errno, $errstr, $errfile, $errline) {
	//--
	global $smart_____framework_____last__error; // presume it is already html special chars safe
	//-- The following error types cannot be handled with a user defined function: E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING, and most of E_STRICT raised in the file where set_error_handler() is called : http://php.net/manual/en/function.set-error-handler.php
	$app_halted = '';
	$is_supressed = false;
	$is_fatal = false;
	switch($errno) { // friendly err names
		case E_NOTICE:
			$ferr = 'NOTICE';
			if(0 == error_reporting()) { // fix: don't log E_NOTICE from @functions
				$is_supressed = true;
			} //end if
			break;
		case E_USER_NOTICE:
			$ferr = 'APP-NOTICE';
			$is_supressed = true;
			break;
		case E_WARNING:
			$ferr = 'WARNING';
			if(0 == error_reporting()) { // fix: don't log E_WARNING from @functions
				$is_supressed = true;
			} //end if
			break;
		case E_USER_WARNING:
			$ferr = 'APP-WARNING';
			break;
		case E_RECOVERABLE_ERROR:
			$is_fatal = true;
			$app_halted = ' :: Execution FAILED !';
			$ferr = 'ERROR';
			break;
		case E_USER_ERROR:
			$is_fatal = true;
			$app_halted = ' :: Execution Halted !';
			$ferr = 'APP-ERROR';
			break;
		default:
			$ferr = 'OTHER';
	} //end switch
	//--
	$logfile = (string) APP_ERR_LOGFILE;
	//--
	if(($is_supressed !== true) OR ($is_fatal === true)) {
		if((string)$logfile != '') {
			@file_put_contents(
				(string) $logfile,
				(string) "\n".'===== '.date('Y-m-d H:i:s O')."\n".'PHP '.PHP_VERSION.' [APP-ERR-HANDLER] #'.$errno.' ['.$ferr.']'.$app_halted."\n".'HTTP-METHOD: '.$_SERVER['REQUEST_METHOD'].' # '.'CLIENT: '.trim($_SERVER['REMOTE_ADDR'].' ; '.$_SERVER['HTTP_CLIENT_IP'].' ; '.$_SERVER['HTTP_X_FORWARDED_FOR'], '; ').' @ '.$_SERVER['HTTP_USER_AGENT']."\n".'URI: '.$_SERVER['REQUEST_URI']."\n".'Script: '.$errfile."\n".'Line number: '.$errline."\n".$errstr."\n".'==================================='."\n\n",
				FILE_APPEND | LOCK_EX
			);
		} //end if
	} //end if
	//--
	if(($errno === E_RECOVERABLE_ERROR) OR ($errno === E_USER_ERROR)) { // this is necessary for: E_RECOVERABLE_ERROR and E_USER_ERROR (which is used just for Exceptions) and all other PHP errors which are FATAL and will stop the execution ; For WARNING / NOTICE type errors we just want to log them, not to stop the execution !
		//--
		$message = 'Server Script Execution Halted.'."\n".'See the App Error Log for details.';
		//--
		if(!headers_sent()) {
			@http_response_code(500); // try, if not headers send
		} //end if
		die('<!-- APP Error Reporting / APP Error Handler --><center><div><div style="width:548px; border: 1px solid #CCCCCC; margin-top:10px; margin-bottom:10px;"><table align="center" cellpadding="4" style="max-width:540px;"><tr valign="top"><td width="32">[!]</td><td>&nbsp;</td><td><b>'.'Application Runtime Error [#'.$errno.']:<br>'.'</b><i>'.nl2br(htmlspecialchars((string)$message, ENT_HTML401 | ENT_COMPAT | ENT_SUBSTITUTE, 'ISO-8859-1'),false).'</i></td></tr></table></div><br><div style="width:550px; color:#778899; text-align:justify;"></div>'.$smart_____framework_____last__error.'</div></center>');
		//--
	} //end if else
	//--
}, E_ALL & ~E_NOTICE); // error reporting for logging
set_exception_handler(function($exception) { // no type for EXCEPTION to be PHP 7 compatible
	//--
	//print_r($exception);
	//print_r($exception->getTrace());
	//--
	$message = $exception->getMessage();
	$details = '#'.$exception->getLine().' @ '.$exception->getFile();
	$exid = sha1('ExceptionID:'.$message.'/'.$details);
	//--
	if(is_array($exception->getTrace())) {
		//--
		$arr = (array) $exception->getTrace();
		//--
		for($i=0; $i<2; $i++) { // trace just 2 levels
			$details .= "\n".'  ----- Line #'.$arr[$i]['line'].' @ Class:['.$arr[$i]['class'].'] '.$arr[$i]['type'].' Function:['.$arr[$i]['function'].'] | File: '.$arr[$i]['file'];
			$details .= "\n".'  ----- Args * '.print_r($arr[$i]['args'],1);
		} //end for
		//--
	} //end if
	//--
	@trigger_error('***** EXCEPTION ***** [#'.$exid.']:'."\n"."\n".'Error-Message: '.$message."\n".$details, E_USER_ERROR); // log the exception as ERROR
	//-- below code would be executed only if E_USER_ERROR fails to stop the execution
	if(!headers_sent()) {
		@http_response_code(500); // try, if not headers send
	} //end if
	die('Execution Halted. Application Level Exception. See the App Error Log for more details.');
	//--
});
register_shutdown_function('app__err__handler__catch_fatal_errs');
function app__err__handler__catch_fatal_errs() {
	$error = error_get_last();
	if(is_array($error)) {
		switch($error['type']) {
			case E_ERROR:
			case E_PARSE:
			case E_CORE_ERROR:
			case E_COMPILE_ERROR:
				@trigger_error('FATAL ERROR: '.(string)print_r($error,1), E_USER_ERROR);
				break;
			default:
				// don't handle
		} //end switch
	} //end if
} //END FUNCTION
//--
//==

//##### #END: SHARED INIT

//==
define('APPCODEUNPACK_VERSION', 'v.20210312.1255'); // current version of this script
//==
header('Cache-Control: no-cache'); 															// HTTP 1.1
header('Pragma: no-cache'); 																// HTTP 1.0
header('Expires: '.gmdate('D, d M Y', @strtotime('-1 year')).' '.date('H:i:s').' GMT'); 	// HTTP 1.0
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
//==
AppCodeUnPack::Run(); // outputs directly
//==

//###############
//############### END EXECUTION / INITS
//###############


final class AppCodeUnPack {

	public static function Run() {

	// REQUIRE the 'appcodeunpack.ini' in the base folder

	//##### Create BaseFolder
	$err_create_basefolder = AppPackUtils::unpack_create_basefolder();
	if((string)$err_create_basefolder != '') {
		AppPackUtils::raise_error('ERROR: '.$err_create_basefolder);
		return;
	} //end if
	//##### Check BaseFolder
	$the_app_basefolder = (string) AppPackUtils::unpack_get_basefolder_name();
	AppPackUtils::raise_error_if_unsafe_path((string)$the_app_basefolder);
	if(!AppPackUtils::is_type_dir((string)$the_app_basefolder)) {
		if(AppPackUtils::path_exists((string)$the_app_basefolder)) {
			AppPackUtils::raise_error('ERROR: NetArchive Unpack Base Folder path exists but does not appear to be a Directory ...');
			return;
		} //end if
		AppPackUtils::raise_error('ERROR: NetArchive Unpack Base Folder not found ...');
		return;
	} //end if
	//##### Check htaccess
	if(!AppPackUtils::is_type_file((string)$the_app_basefolder.'.htaccess')) {
		AppPackUtils::raise_error('ERROR: NetArchive Unpack Base Folder htaccess not found ...');
		return;
	} //end if
	if(!AppPackUtils::have_access_read((string)$the_app_basefolder.'.htaccess')) {
		AppPackUtils::raise_error('ERROR: NetArchive Unpack Base Folder htaccess not readable ...');
		return;
	} //end if
	//##### Access Fail Log
	$the_app_auth_logfile = (string) $the_app_basefolder.'---AppCodeUnPack-FAIL-Auth---'.date('Ymd-H').'.log';
	AppPackUtils::raise_error_if_unsafe_path((string)$the_app_auth_logfile);
	//#####

	//##### Parse INI
	$appcode_ini_file_options = [ //===== SETTINGS
		'ADMIN_AREA_FORCE_HTTPS' => false,
		'ADMIN_AREA_RESTRICT_IP_LIST' => false,
		'ADMIN_AREA_USER' => false,
		'ADMIN_AREA_PASSWORD' => false,
		'ADMIN_AREA_SECRET' => false,
		'ADMIN_AREA_APP_IDS' => false
	];
	$appcode_ini_file_parse = array();
	$appcode_ini_file_path = (string) $the_app_basefolder.'appcodeunpack.ini';
	AppPackUtils::raise_error_if_unsafe_path((string)$appcode_ini_file_path);
	if((AppPackUtils::is_type_file((string)$appcode_ini_file_path)) AND (AppPackUtils::have_access_read((string)$appcode_ini_file_path))) {
		//--
		$appcode_ini_file_parse = (array) @parse_ini_file((string)$appcode_ini_file_path, false, INI_SCANNER_RAW);
		//--
		foreach($appcode_ini_file_parse as $appcode_ini_file_pkey => $appcode_ini_file_pval) {
			if(array_key_exists((string)$appcode_ini_file_pkey, (array)$appcode_ini_file_options)) {
				if(!defined((string)$appcode_ini_file_pkey)) {
					define((string)$appcode_ini_file_pkey, (string)$appcode_ini_file_pval);
					if(defined((string)$appcode_ini_file_pkey)) {
						$appcode_ini_file_options[(string)$appcode_ini_file_pkey] = true;
					} else {
						AppPackUtils::raise_error('Failed to define an INI Key: '.(string)$appcode_ini_file_pkey);
						return;
					} //end if
				} else {
					AppPackUtils::raise_error('INI Key already defined: '.(string)$appcode_ini_file_pkey);
					return;
				} //end if else
			} else {
				AppPackUtils::raise_error('Invalid INI Key detected: '.(string)$appcode_ini_file_pkey);
				return;
			} //end if else
		} //end foreach
		//--
		foreach($appcode_ini_file_options as $appcode_ini_file_pkey => $appcode_ini_file_pval) {
			if($appcode_ini_file_pval !== true) {
				AppPackUtils::raise_error('A required INI Key was not defined: '.(string)$appcode_ini_file_pkey);
				return;
			} //end if
		} //end foreach
		//--
	} else {
		//--
		AppPackUtils::raise_error('ERROR: App INI not found !');
		return;
		//--
	} //end if else
	//#####

	//##### Check if HTTPS Restricted
	if(defined('ADMIN_AREA_FORCE_HTTPS')) {
		if(ADMIN_AREA_FORCE_HTTPS == 1) {
			if((!isset($_SERVER['HTTPS'])) OR ((string)strtolower((string)trim((string)$_SERVER['HTTPS'])) != 'on')) {
				AppPackUtils::raise_error('ERROR: HTTPS is required !');
				return;
			} //end if
		} //end if
	} //end if
	//##### Check IP Restrict Settings
	if(!defined('ADMIN_AREA_RESTRICT_IP_LIST')) {
		AppPackUtils::raise_error('ERROR: IP Restrict List not set !');
		return;
	} //end if
	//##### Test IP Restrict
	if((string)trim((string)ADMIN_AREA_RESTRICT_IP_LIST) != '') {
		if(stripos((string)ADMIN_AREA_RESTRICT_IP_LIST, '<'.(string)trim((string)$_SERVER['REMOTE_ADDR']).'>') === false) {
			http_response_code(403);
			AppPackUtils::write(
				(string)$the_app_auth_logfile,
				'403 Invalid Access from IP: '.(string)$_SERVER['REMOTE_ADDR'].' @ Client-Signature: '.(string)$_SERVER['HTTP_USER_AGENT']."\n",
				'a' // append
			);
			die('<h1>IP Restriction !</h1><h2>Login Failed ...</h2>Client IP is not in the allowed list: '.(string)$_SERVER['REMOTE_ADDR']);
		} //end if
	} //end if
	//#####

	//##### Check Auth Settings
	if(!defined('ADMIN_AREA_USER') OR !defined('ADMIN_AREA_PASSWORD')) {
		AppPackUtils::raise_error('ERROR: Authentication user / password not set !');
		return;
	} elseif(strlen((string)trim((string)ADMIN_AREA_USER)) < 5) {
		AppPackUtils::raise_error('ERROR: Authentication user is set but must be at least 5 characters !');
		return;
	} elseif(strlen((string)trim((string)base64_decode((string)ADMIN_AREA_PASSWORD))) < 7) {
		AppPackUtils::raise_error('ERROR: Authentication password is set but must be at least 7 characters !');
		return;
	} //end if else
	//##### Ask Auth
	if(((string)trim((string)$_SERVER['PHP_AUTH_USER']) == '') OR ((string)$_SERVER['PHP_AUTH_USER'] != (string)ADMIN_AREA_USER) OR ((string)$_SERVER['PHP_AUTH_PW'] != (string)base64_decode((string)ADMIN_AREA_PASSWORD))) {
		header('WWW-Authenticate: Basic realm="AppCodeUnpack"');
		http_response_code(401);
		if((string)trim((string)$_SERVER['PHP_AUTH_USER']) != '') { // avoid register if empty username to avoid 1st time hit issue
			AppPackUtils::write(
				(string)$the_app_auth_logfile,
				'401 Invalid Auth from User: '.(string)$_SERVER['PHP_AUTH_USER'].' @ IP: '.(string)$_SERVER['REMOTE_ADDR'].' @ Client-Signature: '.(string)$_SERVER['HTTP_USER_AGENT']."\n",
				'a' // append
			);
		} //end if
		die('<h1>Authorization Required !</h1><h2>Login Failed ...</h2>Either you supplied the wrong credentials or your browser does not understand how to supply the credentials required.');
	} //end if
	//#####

	//##### Check Secret {{{SYNC-VALID-APPCODEPACK-APPSECRET}}}
	if(!defined('ADMIN_AREA_SECRET')) {
		AppPackUtils::raise_error('App Secret was not set !');
		return;
	} elseif(strlen((string)trim((string)ADMIN_AREA_SECRET)) < 40) {
		AppPackUtils::raise_error('App Secret is set but must be at least 40 characters !');
		return;
	} elseif(strlen((string)trim((string)ADMIN_AREA_SECRET)) > 128) {
		AppPackUtils::raise_error('App Secret is set but must be max 128 characters !');
		return;
	} //end if else
	//#####

	//##### Check AppIDs
	if(!defined('ADMIN_AREA_APP_IDS')) {
		AppPackUtils::raise_error('App IDs was not set !');
		return;
	} elseif((strlen((string)trim((string)ADMIN_AREA_APP_IDS)) < 3) OR (strpos((string)ADMIN_AREA_APP_IDS, '<') === false) OR (strpos((string)ADMIN_AREA_APP_IDS, '>') === false)) {
		AppPackUtils::raise_error('Invalid App IDs List. Must be like: <app-id>(,<another-app-id>) !');
		return;
	} //end if else
	//#####

	//--
	$img_logo_app = 'data:image/svg+xml;base64,PHN2ZyB4bWxuczpkYz0iaHR0cDovL3B1cmwub3JnL2RjL2VsZW1lbnRzLzEuMS8iIHhtbG5zOmNjPSJodHRwOi8vY3JlYXRpdmVjb21tb25zLm9yZy9ucyMiIHhtbG5zOnN2Zz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgdmVyc2lvbj0iMS4xIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiB2aWV3Qm94PSIwIDAgMTI4IDEyOCIgaWQ9ImFwcGNvZGV1bnBhY2stbG9nbyI+CiAgPGRlZnMgaWQ9ImRlZnMxNCIgLz4KICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgwLDEsLTEsMCwxMjgsLTMuODg4NWUtNikiIGlkPSJnMzc1NSI+CiAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgwLjEyOTAxODM5LDAsMCwwLjExNjMyNDE2LC0xMy4zMDEzNjgsMTE4LjkwNSkiIGlkPSJnMjk5MyI+CiAgICAgIDxwYXRoIGQ9Ik0gMTAzNS42LC0xOTIuNyA2MTcuNSw0My44IHYgLTE4NC4yIGwgMjYwLjUsLTE0My4zIDE1Ny42LDkxIHogbSAyOC42LC0yNS45IHYgLTQ5NC42IGwgLTE1Myw4OC4zIFYgLTMwNyBsIDE1Myw4OC40IHogbSAtOTAxLjUsMjUuOSA0MTguMSwyMzYuNSB2IC0xODQuMiBsIC0yNjAuNSwtMTQzLjMgLTE1Ny42LDkxIHogbSAtMjguNiwtMjUuOSB2IC00OTQuNiBsIDE1Myw4OC4zIFYgLTMwNyBsIC0xNTMsODguNCB6IE0gMTUyLC03NDUuMiA1ODAuOCwtOTg3LjggdiAxNzguMSBsIC0yNzQuNywxNTEuMSAtMi4xLDEuMiAtMTUyLC04Ny44IHogbSA4OTQuMywwIC00MjguOCwtMjQyLjYgdiAxNzguMSBsIDI3NC43LDE1MS4xIDIuMSwxLjIgMTUyLC04Ny44IHoiIGlkPSJwYXRoOCIgc3R5bGU9ImZpbGw6I2RjZGNkYztmaWxsLW9wYWNpdHk6MSIgLz4KICAgICAgPHBhdGggZD0ibSA1ODAuOCwtMTgyLjMgLTI1NywtMTQxLjMgMCwtMjgwIDI1NywxNDguNCB6IG0gMzYuNywwIDI1NywtMTQxLjMgMCwtMjgwIC0yNTcsMTQ4LjQgeiBNIDM0MS4yLC02MzYgNTk5LjIsLTc3Ny45IDg1Ny4yLC02MzYgNjYxLjI3NDg0LC01MjIuODQ5NDMgNTk5LjIsLTQ4NyB6IiBpZD0icGF0aDEwIiBzdHlsZT0iZmlsbDojNzc4ODk5O2ZpbGwtb3BhY2l0eToxIiAvPgogICAgPC9nPgogICAgPHBhdGggZD0ibSA5NS43MjUsMjUuOTk5NjI2IHEgMCwxLjIwNTcwNyAtMS41MDY4NCwyLjEyNzcxOCBsIC0zLjA1NDM5LDEuNzczMDk4IHEgLTEuNTQ3NTYsMC44OTgzNjkgLTMuNzA2LDAuODk4MzY5IC0yLjE5OTE2OSwwIC0zLjY2NTI4LC0wLjg5ODM2OSBMIDcxLjgxOTI1NSwyMi45NzM1MzkgdiAxNi42NDM0NzggcSAwLDEuMjI5MzQ5IC0xLjUyNzE5OCwxLjk5NzY5MSAtMS41MjcxOTgsMC43NjgzNDIgLTMuNjg1NjM5LDAuNzY4MzQyIGggLTUuMjEyODM2IHEgLTIuMTU4NDQxLDAgLTMuNjg1NjM5LC0wLjc2ODM0MiAtMS41MjcxOTgsLTAuNzY4MzQyIC0xLjUyNzE5OCwtMS45OTc2OTEgViAyMi45NzM1MzkgTCA0NC4yMDc1MSwyOS45MDA0NDIgcSAtMS40NjYxMTEsMC44OTgzNjkgLTMuNjY1Mjc2LDAuODk4MzY5IC0yLjE5OTE2NiwwIC0zLjY2NTI3NiwtMC44OTgzNjkgTCAzMy44MjI1NjEsMjguMTI3MzQ0IFEgMzIuMjc1LDI3LjIyODk3NCAzMi4yNzUsMjUuOTk5NjI2IHEgMCwtMS4yNTI5ODkgMS41NDc1NjEsLTIuMTUxMzU5IEwgNjAuMzM0NzI0LDguNDU3Nzc4MyBRIDYxLjc2MDEwOSw3LjU4MzA1IDY0LDcuNTgzMDUgcSAyLjE5OTE2NiwwIDMuNzA2MDAxLDAuODc0NzI4MyBMIDk0LjIxODE2LDIzLjg0ODI2NyBxIDEuNTA2ODQsMC45MjIwMTEgMS41MDY4NCwyLjE1MTM1OSB6IiBpZD0icGF0aDMwMjkiIHN0eWxlPSJmaWxsOiNmZjk5MDA7ZmlsbC1vcGFjaXR5OjEiIC8+CiAgPC9nPgo8L3N2Zz4=';
	//--
	$img_logo_php    = 'data:image/svg+xml;base64,PHN2ZyB4bWxuczpkYz0iaHR0cDovL3B1cmwub3JnL2RjL2VsZW1lbnRzLzEuMS8iIHhtbG5zOmNjPSJodHRwOi8vY3JlYXRpdmVjb21tb25zLm9yZy9ucyMiIHhtbG5zOnN2Zz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgdmVyc2lvbj0iMS4xIiB3aWR0aD0iMTI4IiBoZWlnaHQ9IjEyOCIgdmlld0JveD0iMCAwIDEyMi44OCAxMjIuODgiIGlkPSJwaHAtbG9nby1zdmciPgogIDxkZWZzIGlkPSJkZWZzMTIiIC8+CiAgPGcgdHJhbnNmb3JtPSJtYXRyaXgoMC40NTMzMzMzMywwLDAsMC41OTAxNzQ1OCwzLjk5MzYsMTkuNTU4NCkiIGlkPSJnNCIgc3R5bGU9ImZpbGwtcnVsZTpldmVub2RkIj4KICAgIDxlbGxpcHNlIGN4PSIxMjgiIGN5PSI2Ni42Mjk5OTciIHJ4PSIxMjgiIHJ5PSI2Ni42Mjk5OTciIGlkPSJlbGxpcHNlNiIgc3R5bGU9ImZpbGw6IzczNzQ5NSIgLz4KICAgIDxwYXRoIGQ9Ik0gMzUuOTQ1LDEwNi4wODIgNDkuOTczLDM1LjA2OCBIIDgyLjQxIGMgMTQuMDI3LDAuODc3IDIxLjA0MSw3Ljg5IDIxLjA0MSwyMC4xNjUgMCwyMS4wNDEgLTE2LjY1NywzMy4zMTUgLTMxLjU2MiwzMi40MzggSCA1Ni4xMSBsIC0zLjUwNywxOC40MTEgSCAzNS45NDUgeiBNIDU5LjYxNiw3NC41MjEgNjQsNDguMjE5IGggMTEuMzk3IGMgNi4xMzcsMCAxMC41MiwyLjYzIDEwLjUyLDcuODkgLTAuODc2LDE0LjkwNSAtNy44OSwxNy41MzUgLTE1Ljc4LDE4LjQxMiBoIC0xMC41MiB6IG0gNDAuNTc2LDEzLjE1IDE0LjAyNywtNzEuMDEzIGggMTYuNjU4IGwgLTMuNTA3LDE4LjQxIGggMTUuNzggYyAxNC4wMjgsMC44NzcgMTkuMjg4LDcuODkgMTcuNTM1LDE2LjY1OCBsIC02LjEzNywzNS45NDUgaCAtMTcuNTM0IGwgNi4xMzcsLTMyLjQzOCBjIDAuODc2LC00LjM4NCAwLjg3NiwtNy4wMTQgLTUuMjYsLTcuMDE0IEggMTI0Ljc0IGwgLTcuODksMzkuNDUyIGggLTE2LjY1OCB6IG0gNTMuMjMzLDE4LjQxMSAxNC4wMjcsLTcxLjAxNCBoIDMyLjQzOCBjIDE0LjAyOCwwLjg3NyAyMS4wNDIsNy44OSAyMS4wNDIsMjAuMTY1IDAsMjEuMDQxIC0xNi42NTgsMzMuMzE1IC0zMS41NjIsMzIuNDM4IGggLTE1Ljc4MSBsIC0zLjUwNywxOC40MTEgaCAtMTYuNjU3IHogbSAyMy42NywtMzEuNTYxIDQuMzg0LC0yNi4zMDIgaCAxMS4zOTggYyA2LjEzNywwIDEwLjUyLDIuNjMgMTAuNTIsNy44OSAtMC44NzYsMTQuOTA1IC03Ljg5LDE3LjUzNSAtMTUuNzgsMTguNDEyIGggLTEwLjUyMSB6IiBpZD0icGF0aDgiIHN0eWxlPSJmaWxsOiNGRkZGRkYiIC8+CiAgPC9nPgo8L3N2Zz4=';
	$img_logo_apache = 'data:image/svg+xml;base64,PHN2ZyB4bWxuczpkYz0iaHR0cDovL3B1cmwub3JnL2RjL2VsZW1lbnRzLzEuMS8iIHhtbG5zOmNjPSJodHRwOi8vY3JlYXRpdmVjb21tb25zLm9yZy9ucyMiIHhtbG5zOnN2Zz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHZlcnNpb249IjEuMSIgd2lkdGg9IjEyOCIgaGVpZ2h0PSIxMjgiIHZpZXdCb3g9IjAgMCAxMDAwIDEwMDAiIGlkPSJhcGFjaGUtbG9nby1zdmciIHN0eWxlPSJlbmFibGUtYmFja2dyb3VuZDpuZXciPgogIDxkZWZzIGlkPSJkZWZzNCI+CiAgICA8bGluZWFyR3JhZGllbnQgeDE9Ii01MTY3LjEwMDEiIHkxPSI2OTcuNTQ5OTkiIHgyPSItNDU3MC4xMDAxIiB5Mj0iMTM5NS42IiBpZD0iaSIgZ3JhZGllbnRVbml0cz0idXNlclNwYWNlT25Vc2UiIGdyYWRpZW50VHJhbnNmb3JtPSJtYXRyaXgoLTAuMjE1OTEsMC4wNDQxNDIsLTAuMDQ0MTQyLC0wLjIxNTkxLC05NzQuODMsNDM3LjQ5KSI+CiAgICAgIDxzdG9wIGlkPSJzdG9wNyIgc3R5bGU9InN0b3AtY29sb3I6I2Y2OTkyMztzdG9wLW9wYWNpdHk6MSIgb2Zmc2V0PSIwIiAvPgogICAgICA8c3RvcCBpZD0ic3RvcDkiIHN0eWxlPSJzdG9wLWNvbG9yOiNmNzlhMjM7c3RvcC1vcGFjaXR5OjEiIG9mZnNldD0iMC4zMTIzIiAvPgogICAgICA8c3RvcCBpZD0ic3RvcDExIiBzdHlsZT0ic3RvcC1jb2xvcjojZTk3ODI2O3N0b3Atb3BhY2l0eToxIiBvZmZzZXQ9IjAuODM4Mjk5OTkiIC8+CiAgICA8L2xpbmVhckdyYWRpZW50PgogICAgPGxpbmVhckdyYWRpZW50IHgxPSItOTU4NS4yOTk4IiB5MT0iNjIwLjUiIHgyPSItNTMyNi4yMDAyIiB5Mj0iNjIwLjUiIGlkPSJoIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgZ3JhZGllbnRUcmFuc2Zvcm09Im1hdHJpeCgtMC4yMTU5MSwwLjA0NDE0MiwtMC4wNDQxNDIsLTAuMjE1OTEsLTk3NC44Myw0MzcuNDkpIj4KICAgICAgPHN0b3AgaWQ9InN0b3AxNCIgc3R5bGU9InN0b3AtY29sb3I6IzllMjA2NDtzdG9wLW9wYWNpdHk6MSIgb2Zmc2V0PSIwLjMyMzMiIC8+CiAgICAgIDxzdG9wIGlkPSJzdG9wMTYiIHN0eWxlPSJzdG9wLWNvbG9yOiNjOTIwMzc7c3RvcC1vcGFjaXR5OjEiIG9mZnNldD0iMC42MzAyMDAwMyIgLz4KICAgICAgPHN0b3AgaWQ9InN0b3AxOCIgc3R5bGU9InN0b3AtY29sb3I6I2NkMjMzNTtzdG9wLW9wYWNpdHk6MSIgb2Zmc2V0PSIwLjc1MTM5OTk5IiAvPgogICAgICA8c3RvcCBpZD0ic3RvcDIwIiBzdHlsZT0ic3RvcC1jb2xvcjojZTk3ODI2O3N0b3Atb3BhY2l0eToxIiBvZmZzZXQ9IjEiIC8+CiAgICA8L2xpbmVhckdyYWRpZW50PgogICAgPGxpbmVhckdyYWRpZW50IHgxPSItOTA3MS4yMDAyIiB5MT0iMTA0Ny43IiB4Mj0iLTY1MzMuMjAwMiIgeTI9IjEwNDcuNyIgaWQ9ImciIGdyYWRpZW50VW5pdHM9InVzZXJTcGFjZU9uVXNlIiBncmFkaWVudFRyYW5zZm9ybT0ibWF0cml4KC0wLjIxNTkxLDAuMDQ0MTQyLC0wLjA0NDE0MiwtMC4yMTU5MSwtOTc0LjgzLDQzNy40OSkiPgogICAgICA8c3RvcCBpZD0ic3RvcDIzIiBzdHlsZT0ic3RvcC1jb2xvcjojMjgyNjYyO3N0b3Atb3BhY2l0eToxIiBvZmZzZXQ9IjAiIC8+CiAgICAgIDxzdG9wIGlkPSJzdG9wMjUiIHN0eWxlPSJzdG9wLWNvbG9yOiM2NjJlOGQ7c3RvcC1vcGFjaXR5OjEiIG9mZnNldD0iMC4wOTU0ODQiIC8+CiAgICAgIDxzdG9wIGlkPSJzdG9wMjciIHN0eWxlPSJzdG9wLWNvbG9yOiM5ZjIwNjQ7c3RvcC1vcGFjaXR5OjEiIG9mZnNldD0iMC43ODgyMDAwMiIgLz4KICAgICAgPHN0b3AgaWQ9InN0b3AyOSIgc3R5bGU9InN0b3AtY29sb3I6I2NkMjAzMjtzdG9wLW9wYWNpdHk6MSIgb2Zmc2V0PSIwLjk0ODcwMDAxIiAvPgogICAgPC9saW5lYXJHcmFkaWVudD4KICAgIDxsaW5lYXJHcmFkaWVudCB4MT0iLTkzNDYuMDk5NiIgeTE9IjU4MC44MjAwMSIgeDI9Ii01MDg3IiB5Mj0iNTgwLjgyMDAxIiBpZD0iZiIgZ3JhZGllbnRVbml0cz0idXNlclNwYWNlT25Vc2UiIGdyYWRpZW50VHJhbnNmb3JtPSJtYXRyaXgoLTAuMjE1OTEsMC4wNDQxNDIsLTAuMDQ0MTQyLC0wLjIxNTkxLC05NzQuODMsNDM3LjQ5KSI+CiAgICAgIDxzdG9wIGlkPSJzdG9wMzIiIHN0eWxlPSJzdG9wLWNvbG9yOiM5ZTIwNjQ7c3RvcC1vcGFjaXR5OjEiIG9mZnNldD0iMC4zMjMzIiAvPgogICAgICA8c3RvcCBpZD0ic3RvcDM0IiBzdHlsZT0ic3RvcC1jb2xvcjojYzkyMDM3O3N0b3Atb3BhY2l0eToxIiBvZmZzZXQ9IjAuNjMwMjAwMDMiIC8+CiAgICAgIDxzdG9wIGlkPSJzdG9wMzYiIHN0eWxlPSJzdG9wLWNvbG9yOiNjZDIzMzU7c3RvcC1vcGFjaXR5OjEiIG9mZnNldD0iMC43NTEzOTk5OSIgLz4KICAgICAgPHN0b3AgaWQ9InN0b3AzOCIgc3R5bGU9InN0b3AtY29sb3I6I2U5NzgyNjtzdG9wLW9wYWNpdHk6MSIgb2Zmc2V0PSIxIiAvPgogICAgPC9saW5lYXJHcmFkaWVudD4KICAgIDxsaW5lYXJHcmFkaWVudCB4MT0iLTkwMzUuNSIgeTE9IjYzOC40NCIgeDI9Ii02Nzk3LjIwMDIiIHkyPSI2MzguNDQiIGlkPSJlIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgZ3JhZGllbnRUcmFuc2Zvcm09Im1hdHJpeCgtMC4yMTU5MSwwLjA0NDE0MiwtMC4wNDQxNDIsLTAuMjE1OTEsLTk3NC44Myw0MzcuNDkpIj4KICAgICAgPHN0b3AgaWQ9InN0b3A0MSIgc3R5bGU9InN0b3AtY29sb3I6IzI4MjY2MjtzdG9wLW9wYWNpdHk6MSIgb2Zmc2V0PSIwIiAvPgogICAgICA8c3RvcCBpZD0ic3RvcDQzIiBzdHlsZT0ic3RvcC1jb2xvcjojNjYyZThkO3N0b3Atb3BhY2l0eToxIiBvZmZzZXQ9IjAuMDk1NDg0IiAvPgogICAgICA8c3RvcCBpZD0ic3RvcDQ1IiBzdHlsZT0ic3RvcC1jb2xvcjojOWYyMDY0O3N0b3Atb3BhY2l0eToxIiBvZmZzZXQ9IjAuNzg4MjAwMDIiIC8+CiAgICAgIDxzdG9wIGlkPSJzdG9wNDciIHN0eWxlPSJzdG9wLWNvbG9yOiNjZDIwMzI7c3RvcC1vcGFjaXR5OjEiIG9mZnNldD0iMC45NDg3MDAwMSIgLz4KICAgIDwvbGluZWFyR3JhZGllbnQ+CiAgICA8bGluZWFyR3JhZGllbnQgeDE9Ii05MzQ2LjA5OTYiIHkxPSIxMDIxLjYiIHgyPSItNTA4NyIgeTI9IjEwMjEuNiIgaWQ9ImQiIGdyYWRpZW50VW5pdHM9InVzZXJTcGFjZU9uVXNlIiBncmFkaWVudFRyYW5zZm9ybT0ibWF0cml4KC0wLjIxNTkxLDAuMDQ0MTQyLC0wLjA0NDE0MiwtMC4yMTU5MSwtOTc0LjgzLDQzNy40OSkiPgogICAgICA8c3RvcCBpZD0ic3RvcDUwIiBzdHlsZT0ic3RvcC1jb2xvcjojOWUyMDY0O3N0b3Atb3BhY2l0eToxIiBvZmZzZXQ9IjAuMzIzMyIgLz4KICAgICAgPHN0b3AgaWQ9InN0b3A1MiIgc3R5bGU9InN0b3AtY29sb3I6I2M5MjAzNztzdG9wLW9wYWNpdHk6MSIgb2Zmc2V0PSIwLjYzMDIwMDAzIiAvPgogICAgICA8c3RvcCBpZD0ic3RvcDU0IiBzdHlsZT0ic3RvcC1jb2xvcjojY2QyMzM1O3N0b3Atb3BhY2l0eToxIiBvZmZzZXQ9IjAuNzUxMzk5OTkiIC8+CiAgICAgIDxzdG9wIGlkPSJzdG9wNTYiIHN0eWxlPSJzdG9wLWNvbG9yOiNlOTc4MjY7c3RvcC1vcGFjaXR5OjEiIG9mZnNldD0iMSIgLz4KICAgIDwvbGluZWFyR3JhZGllbnQ+CiAgICA8bGluZWFyR3JhZGllbnQgeDE9Ii05NjEwLjI5OTgiIHkxPSI5OTkuNzI5OTgiIHgyPSItNTM1MS4yMDAyIiB5Mj0iOTk5LjcyOTk4IiBpZD0iYyIgZ3JhZGllbnRVbml0cz0idXNlclNwYWNlT25Vc2UiIGdyYWRpZW50VHJhbnNmb3JtPSJtYXRyaXgoLTAuMjE1OTEsMC4wNDQxNDIsLTAuMDQ0MTQyLC0wLjIxNTkxLC05NzQuODMsNDM3LjQ5KSI+CiAgICAgIDxzdG9wIGlkPSJzdG9wNTkiIHN0eWxlPSJzdG9wLWNvbG9yOiM5ZTIwNjQ7c3RvcC1vcGFjaXR5OjEiIG9mZnNldD0iMC4zMjMzIiAvPgogICAgICA8c3RvcCBpZD0ic3RvcDYxIiBzdHlsZT0ic3RvcC1jb2xvcjojYzkyMDM3O3N0b3Atb3BhY2l0eToxIiBvZmZzZXQ9IjAuNjMwMjAwMDMiIC8+CiAgICAgIDxzdG9wIGlkPSJzdG9wNjMiIHN0eWxlPSJzdG9wLWNvbG9yOiNjZDIzMzU7c3RvcC1vcGFjaXR5OjEiIG9mZnNldD0iMC43NTEzOTk5OSIgLz4KICAgICAgPHN0b3AgaWQ9InN0b3A2NSIgc3R5bGU9InN0b3AtY29sb3I6I2U5NzgyNjtzdG9wLW9wYWNpdHk6MSIgb2Zmc2V0PSIxIiAvPgogICAgPC9saW5lYXJHcmFkaWVudD4KICAgIDxsaW5lYXJHcmFkaWVudCB4MT0iLTkzNDYuMDk5NiIgeTE9IjExNTIuNyIgeDI9Ii01MDg3IiB5Mj0iMTE1Mi43IiBpZD0iYiIgZ3JhZGllbnRVbml0cz0idXNlclNwYWNlT25Vc2UiIGdyYWRpZW50VHJhbnNmb3JtPSJtYXRyaXgoLTAuMjE1OTEsMC4wNDQxNDIsLTAuMDQ0MTQyLC0wLjIxNTkxLC05NzQuODMsNDM3LjQ5KSI+CiAgICAgIDxzdG9wIGlkPSJzdG9wNjgiIHN0eWxlPSJzdG9wLWNvbG9yOiM5ZTIwNjQ7c3RvcC1vcGFjaXR5OjEiIG9mZnNldD0iMC4zMjMzIiAvPgogICAgICA8c3RvcCBpZD0ic3RvcDcwIiBzdHlsZT0ic3RvcC1jb2xvcjojYzkyMDM3O3N0b3Atb3BhY2l0eToxIiBvZmZzZXQ9IjAuNjMwMjAwMDMiIC8+CiAgICAgIDxzdG9wIGlkPSJzdG9wNzIiIHN0eWxlPSJzdG9wLWNvbG9yOiNjZDIzMzU7c3RvcC1vcGFjaXR5OjEiIG9mZnNldD0iMC43NTEzOTk5OSIgLz4KICAgICAgPHN0b3AgaWQ9InN0b3A3NCIgc3R5bGU9InN0b3AtY29sb3I6I2U5NzgyNjtzdG9wLW9wYWNpdHk6MSIgb2Zmc2V0PSIxIiAvPgogICAgPC9saW5lYXJHcmFkaWVudD4KICAgIDxsaW5lYXJHcmFkaWVudCB4MT0iLTkzNDYuMDk5NiIgeTE9IjExMzcuNyIgeDI9Ii01MDg3IiB5Mj0iMTEzNy43IiBpZD0iYSIgZ3JhZGllbnRVbml0cz0idXNlclNwYWNlT25Vc2UiIGdyYWRpZW50VHJhbnNmb3JtPSJtYXRyaXgoLTAuMjE1OTEsMC4wNDQxNDIsLTAuMDQ0MTQyLC0wLjIxNTkxLC05NzQuODMsNDM3LjQ5KSI+CiAgICAgIDxzdG9wIGlkPSJzdG9wNzciIHN0eWxlPSJzdG9wLWNvbG9yOiM5ZTIwNjQ7c3RvcC1vcGFjaXR5OjEiIG9mZnNldD0iMC4zMjMzIiAvPgogICAgICA8c3RvcCBpZD0ic3RvcDc5IiBzdHlsZT0ic3RvcC1jb2xvcjojYzkyMDM3O3N0b3Atb3BhY2l0eToxIiBvZmZzZXQ9IjAuNjMwMjAwMDMiIC8+CiAgICAgIDxzdG9wIGlkPSJzdG9wODEiIHN0eWxlPSJzdG9wLWNvbG9yOiNjZDIzMzU7c3RvcC1vcGFjaXR5OjEiIG9mZnNldD0iMC43NTEzOTk5OSIgLz4KICAgICAgPHN0b3AgaWQ9InN0b3A4MyIgc3R5bGU9InN0b3AtY29sb3I6I2U5NzgyNjtzdG9wLW9wYWNpdHk6MSIgb2Zmc2V0PSIxIiAvPgogICAgPC9saW5lYXJHcmFkaWVudD4KICAgIDxsaW5lYXJHcmFkaWVudCB4MT0iLTY5NTMuMzk5OSIgeTE9IjExMzQuNyIgeDI9Ii02MDEyIiB5Mj0iMTEzNC43IiBpZD0iaiIgZ3JhZGllbnRVbml0cz0idXNlclNwYWNlT25Vc2UiIGdyYWRpZW50VHJhbnNmb3JtPSJtYXRyaXgoLTAuMjE1OTEsMC4wNDQxNDIsLTAuMDQ0MTQyLC0wLjIxNTkxLC05NzQuODMsNDM3LjQ5KSI+CiAgICAgIDxzdG9wIGlkPSJzdG9wODYiIHN0eWxlPSJzdG9wLWNvbG9yOiM5ZTIwNjQ7c3RvcC1vcGFjaXR5OjEiIG9mZnNldD0iMC4zMjMzIiAvPgogICAgICA8c3RvcCBpZD0ic3RvcDg4IiBzdHlsZT0ic3RvcC1jb2xvcjojYzkyMDM3O3N0b3Atb3BhY2l0eToxIiBvZmZzZXQ9IjAuNjMwMjAwMDMiIC8+CiAgICAgIDxzdG9wIGlkPSJzdG9wOTAiIHN0eWxlPSJzdG9wLWNvbG9yOiNjZDIzMzU7c3RvcC1vcGFjaXR5OjEiIG9mZnNldD0iMC43NTEzOTk5OSIgLz4KICAgICAgPHN0b3AgaWQ9InN0b3A5MiIgc3R5bGU9InN0b3AtY29sb3I6I2U5NzgyNjtzdG9wLW9wYWNpdHk6MSIgb2Zmc2V0PSIxIiAvPgogICAgPC9saW5lYXJHcmFkaWVudD4KICAgIDxsaW5lYXJHcmFkaWVudCB4MT0iLTkwMzUuNSIgeTE9IjYzOC40NCIgeDI9Ii02Nzk3LjIwMDIiIHkyPSI2MzguNDQiIGlkPSJsaW5lYXJHcmFkaWVudDMxNTEiIHhsaW5rOmhyZWY9IiNlIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgZ3JhZGllbnRUcmFuc2Zvcm09Im1hdHJpeCgtMC4yMTU5MSwwLjA0NDE0MiwtMC4wNDQxNDIsLTAuMjE1OTEsLTk3NC44Myw0MzcuNDkpIiAvPgogICAgPGxpbmVhckdyYWRpZW50IHgxPSItNTE2Ny4xMDAxIiB5MT0iNjk3LjU0OTk5IiB4Mj0iLTQ1NzAuMTAwMSIgeTI9IjEzOTUuNiIgaWQ9ImxpbmVhckdyYWRpZW50MzE1MyIgeGxpbms6aHJlZj0iI2kiIGdyYWRpZW50VW5pdHM9InVzZXJTcGFjZU9uVXNlIiBncmFkaWVudFRyYW5zZm9ybT0ibWF0cml4KC0wLjIxNTkxLDAuMDQ0MTQyLC0wLjA0NDE0MiwtMC4yMTU5MSwtOTc0LjgzLDQzNy40OSkiIC8+CiAgICA8bGluZWFyR3JhZGllbnQgeDE9Ii05NTg1LjI5OTgiIHkxPSI2MjAuNSIgeDI9Ii01MzI2LjIwMDIiIHkyPSI2MjAuNSIgaWQ9ImxpbmVhckdyYWRpZW50MzE1NSIgeGxpbms6aHJlZj0iI2giIGdyYWRpZW50VW5pdHM9InVzZXJTcGFjZU9uVXNlIiBncmFkaWVudFRyYW5zZm9ybT0ibWF0cml4KC0wLjIxNTkxLDAuMDQ0MTQyLC0wLjA0NDE0MiwtMC4yMTU5MSwtOTc0LjgzLDQzNy40OSkiIC8+CiAgICA8bGluZWFyR3JhZGllbnQgeDE9Ii05MzQ2LjA5OTYiIHkxPSI1ODAuODIwMDEiIHgyPSItNTA4NyIgeTI9IjU4MC44MjAwMSIgaWQ9ImxpbmVhckdyYWRpZW50MzE1NyIgeGxpbms6aHJlZj0iI2YiIGdyYWRpZW50VW5pdHM9InVzZXJTcGFjZU9uVXNlIiBncmFkaWVudFRyYW5zZm9ybT0ibWF0cml4KC0wLjIxNTkxLDAuMDQ0MTQyLC0wLjA0NDE0MiwtMC4yMTU5MSwtOTc0LjgzLDQzNy40OSkiIC8+CiAgICA8bGluZWFyR3JhZGllbnQgeDE9Ii05MzQ2LjA5OTYiIHkxPSIxMDIxLjYiIHgyPSItNTA4NyIgeTI9IjEwMjEuNiIgaWQ9ImxpbmVhckdyYWRpZW50MzE1OSIgeGxpbms6aHJlZj0iI2QiIGdyYWRpZW50VW5pdHM9InVzZXJTcGFjZU9uVXNlIiBncmFkaWVudFRyYW5zZm9ybT0ibWF0cml4KC0wLjIxNTkxLDAuMDQ0MTQyLC0wLjA0NDE0MiwtMC4yMTU5MSwtOTc0LjgzLDQzNy40OSkiIC8+CiAgICA8bGluZWFyR3JhZGllbnQgeDE9Ii05NjEwLjI5OTgiIHkxPSI5OTkuNzI5OTgiIHgyPSItNTM1MS4yMDAyIiB5Mj0iOTk5LjcyOTk4IiBpZD0ibGluZWFyR3JhZGllbnQzMTYxIiB4bGluazpocmVmPSIjYyIgZ3JhZGllbnRVbml0cz0idXNlclNwYWNlT25Vc2UiIGdyYWRpZW50VHJhbnNmb3JtPSJtYXRyaXgoLTAuMjE1OTEsMC4wNDQxNDIsLTAuMDQ0MTQyLC0wLjIxNTkxLC05NzQuODMsNDM3LjQ5KSIgLz4KICAgIDxsaW5lYXJHcmFkaWVudCB4MT0iLTkzNDYuMDk5NiIgeTE9IjExNTIuNyIgeDI9Ii01MDg3IiB5Mj0iMTE1Mi43IiBpZD0ibGluZWFyR3JhZGllbnQzMTYzIiB4bGluazpocmVmPSIjYiIgZ3JhZGllbnRVbml0cz0idXNlclNwYWNlT25Vc2UiIGdyYWRpZW50VHJhbnNmb3JtPSJtYXRyaXgoLTAuMjE1OTEsMC4wNDQxNDIsLTAuMDQ0MTQyLC0wLjIxNTkxLC05NzQuODMsNDM3LjQ5KSIgLz4KICAgIDxsaW5lYXJHcmFkaWVudCB4MT0iLTkzNDYuMDk5NiIgeTE9IjExMzcuNyIgeDI9Ii01MDg3IiB5Mj0iMTEzNy43IiBpZD0ibGluZWFyR3JhZGllbnQzMTY1IiB4bGluazpocmVmPSIjYSIgZ3JhZGllbnRVbml0cz0idXNlclNwYWNlT25Vc2UiIGdyYWRpZW50VHJhbnNmb3JtPSJtYXRyaXgoLTAuMjE1OTEsMC4wNDQxNDIsLTAuMDQ0MTQyLC0wLjIxNTkxLC05NzQuODMsNDM3LjQ5KSIgLz4KICAgIDxsaW5lYXJHcmFkaWVudCB4MT0iLTY5NTMuMzk5OSIgeTE9IjExMzQuNyIgeDI9Ii02MDEyIiB5Mj0iMTEzNC43IiBpZD0ibGluZWFyR3JhZGllbnQzMTY3IiB4bGluazpocmVmPSIjaiIgZ3JhZGllbnRVbml0cz0idXNlclNwYWNlT25Vc2UiIGdyYWRpZW50VHJhbnNmb3JtPSJtYXRyaXgoLTAuMjE1OTEsMC4wNDQxNDIsLTAuMDQ0MTQyLC0wLjIxNTkxLC05NzQuODMsNDM3LjQ5KSIgLz4KICAgIDxsaW5lYXJHcmFkaWVudCB4MT0iLTkwNzEuMjAwMiIgeTE9IjEwNDcuNyIgeDI9Ii02NTMzLjIwMDIiIHkyPSIxMDQ3LjciIGlkPSJsaW5lYXJHcmFkaWVudDMxNjkiIHhsaW5rOmhyZWY9IiNnIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgZ3JhZGllbnRUcmFuc2Zvcm09Im1hdHJpeCgtMC4yMTU5MSwwLjA0NDE0MiwtMC4wNDQxNDIsLTAuMjE1OTEsLTk3NC44Myw0MzcuNDkpIiAvPgogIDwvZGVmcz4KICA8ZyB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMy45NDMzLDkxOC4xODAwMSkiIGlkPSJnOTQiPgogICAgPGcgdHJhbnNmb3JtPSJtYXRyaXgoMC43MDkyNjYzNCwxLjAxMTcyMTEsLTEuMDA4Mzg1OSwwLjU3MDY4NTY2LDk4LjQwOTgzLC04ODEuMzA3MzcpIiBpZD0iZzMxMjUiPgogICAgICA8cGF0aCBkPSJtIDY1Ni43MSwtNy4wOTA5IGMgMjAuMjY3LC05LjU5MzkgMzkuNzE0LC0yMC4xNDQgNTguMTM3LC0zMS44NTUgMC42NDU4NiwtMC40MjM5OCAxLjMwMDMsLTAuNzk5NDMgMS45NDYxLC0xLjIyMzQgLTcuNTE0NiwxOS4wOTcgLTMuODM4Miw1Mi41ODUgLTMuNzUzNyw1Mi40NCAxMS44NSwtMzQuNzExIDI2LjUxNSwtNjUuMjU1IDQ4Ljc0NCwtODQuNjcyIDguNDgyNiw3LjU5MjggMTQuOTIzLDIxLjgzIDIwLjIxNCwzOS44ODIgMi42MTg2LC0yNC44NDkgLTEuODU4LC0zOC43MzIgLTQuMDUxNCwtNDMuNzk5IDEwLjkxNywxMi45NDggMzAuNzM0LDIwLjE3NiA1Mi4wNDUsMjYuNDYgLTIzLjk0NiwtMTQuNTM2IC0zOS43OTIsLTI4Ljg3OCAtNDYuMDA0LC00Mi45NTUgNjguMzEyLC0yMS4wMTggMTQyLjI5LC00Ni4xNTcgMjE5Ljk2LC03NC4wMjYgLTYuMDkyNSwtNC41OTExIC0xMi42MzksLTUuMTY5NCAtMTkuNDQyLC0zLjE3MDYgLTE0LjAyMyw1LjEwMTcgLTEwNi4yMiwzOC4zMTIgLTIzMS42NCw3Ni4yNDQgLTMuNTYyOSwxLjA3NzggLTcuMTU2NywyLjE1MTEgLTEwLjc2OCwzLjIzNzUgLTEuMDA2NywwLjMwNzM4IC0yLjAyNjQsMC41OTcwOCAtMy4wMTUzLDAuODkxMzUgLTEzLjE3NCwzLjk1MTUgLTI2LjY2Niw3LjkxODkgLTQwLjQ2MiwxMS45MiAtMy4xNDMzLDAuOTAzODQgLTYuMjg2NSwxLjgwNzcgLTkuNDQ3NSwyLjcyNDcgLTAuMDY2MywwLjAyMTg4IC0wLjEzMjQ2LDAuMDQzMzQgLTAuMTgxMDYsMC4wNTIgbCAtMzQuNTE0LDY4LjkzNSBjIDAuNzExNSwtMC4zMzU0NiAxLjQ5MzksLTAuNzIzNTggMi4yNDA4LC0xLjA4NTMgeiIgaWQ9InBhdGg5NiIgc3R5bGU9ImZpbGw6dXJsKCNsaW5lYXJHcmFkaWVudDMxNTEpIiAvPgogICAgICA8cGF0aCBkPSJtIDIzLjE0OCw2Ni44MTcgYyAxOS4wNzEsNy4xOTYxIDM5LjYwOCw5LjA4NDUgNTkuNzMzLDExLjI3NCAxMS44NTQsMS4xMjM0IDIzLjczOCwxLjkxNDIgMzUuNjMsMi40OTI2IDguMTY5MiwtMTcuMTA0IDE2LjMzOCwtMzQuMjA3IDI0LjUwOCwtNTEuMzExIC0zMS40OTEsMC44NTY0OCAtNjMuMDg3LDAuMTc2MTggLTk0LjM5MywtMy41NDAxIDMwLjk0MywzLjEwMyA2Mi4xMzcsMS44NDk0IDkzLjEyOSwwLjE3MDA3IC0yMi4zNywtMjYuNjY5IC00Ni45MDksLTUxLjQyOCAtNzEuOTMsLTc1LjU4OCAtMjMuNTE5LDExLjgzOSAtNDYuMjkxLDI4LjM1IC01OC42MzQsNTIuMjQ0IC02LjMwMDEsMTQuNTQ2IC05Ljk1MDgsMzEuNTM0IC00LjgyMSw0Ni45ODEgMi43NTYyLDcuODk0IDkuMDY1LDE0LjE2IDE2Ljc3MywxNy4yNzcgeiIgaWQ9InBhdGg5OCIgc3R5bGU9ImZpbGw6dXJsKCNsaW5lYXJHcmFkaWVudDMxNTMpIiAvPgogICAgICA8cGF0aCBkPSJtIDI4MC4yNCwtNjcuNjg5IGMgLTAuMTUzNTIsLTAuMTMzMDggLTAuMjg5MzYsLTAuMjc5MjkgLTAuNDQyOSwtMC40MTIzNSBsIDIuNjIwMiwyLjgzMSBjIDAuMjMyODgsMC4xMjkwOSAwLjQzOTU0LDAuMjIyODEgMC42NTQ3MiwwLjM2NTA0IC0wLjk0Njg0LC0wLjk0NDA2IC0xLjg5ODMsLTEuODU3MyAtMi44MzIsLTIuNzgzNiB6IiBpZD0icGF0aDEwMCIgc3R5bGU9ImZpbGw6bm9uZSIgLz4KICAgICAgPHBhdGggZD0ibSAzNjguNzIsLTk5LjQ2NSBjIDEuNjg4OSwxLjQ2MzggMy4zMTU1LDMuMDI4NyA0LjkxOTgsNC42Mzc2IC0xLjYwNDMsLTEuNjA4OCAtMy4yNDg3LC0zLjE2MDYgLTQuOTE5OCwtNC42Mzc2IHoiIGlkPSJwYXRoMTAyIiBzdHlsZT0iZmlsbDpub25lIiAvPgogICAgICA8cGF0aCBkPSJtIDY0MC44OSwtNzEuMzU0IGMgLTAuNzcyNTYsMC4yMTYxMyAtMS41NDUxLDAuNDMyMjMgLTIuMzA0NSwwLjY2NjA2IC0xNS45MTksNC41MDUgLTMxLjQ0NCw4LjgwMDYgLTQ2LjY0MywxMi45MDggLTE3LjA1Miw0LjYwNDUgLTMzLjYyNyw4Ljk2NDggLTQ5Ljc3MywxMy4wODkgLTE3LjAxNiw0LjM1NzkgLTMzLjUzNiw4LjQ1ODQgLTQ5LjU3OSwxMi4zMTUgLTE2LjgyOSw0LjA1NDggLTMzLjEyNyw3LjgyNiAtNDguODg3LDExLjM2MiAtMTIuODI1LDIuODY5MiAtMjUuMjk2LDUuNTg2IC0zNy40MjYsOC4xMzI4IC00LjAzMzMsMC44NTA0NyAtOC4wNDg5LDEuNjg3OCAtMTIuMDI5LDIuNDk4OSAtNy44ODEsMS42MTgyIC0xNS42NDMsMy4xNzU0IC0yMy4yMjMsNC42ODA2IC03LjAxMTQsMS4zODUgLTEzLjg4NiwyLjY5NTggLTIwLjY1OSwzLjk1ODcgLTIuMjU0NywwLjQzNzE1IC00LjQ4NzIsMC44MzAzNCAtNi43MjQyLDEuMjU0NCAtMC4zNTc0LDAuMDcyOTU0IC0wLjcyNzk2LDAuMTI4MjIgLTEuMDg1NCwwLjIwMTE4IGwgMi4yMjU4LDIuNDEwMSAtMi41NTgzLDUuMTM0MSBjIDAuNTUxNTQsLTAuMTA3MTYgMS4wOTg1LC0wLjE4MzQ3IDEuNjYzMiwtMC4yNzI5MSAxMC4yMzYsLTEuNzcyNyAyMC41MTYsLTMuNjMzMyAzMC44NCwtNS41ODE5IDUuOTY1NCwtMS4xMzA3IDExLjk0OSwtMi4yNzQ2IDE3LjkzNiwtMy40NDkyIDE2LjYsLTMuMjUzOCAzMy4yNCwtNi42NzUgNDkuODksLTEwLjI2OCAxNi44MjYsLTMuNjE0IDMzLjYzMiwtNy40MDQ1IDUwLjM3NiwtMTEuMzE0IDE2LjQ0OSwtMy44Mjc3IDMyLjgyMywtNy43OTI0IDQ5LjA5NiwtMTEuODE5IDE2LjI0MiwtNC4wMzE2IDMyLjM1OCwtOC4xNjA4IDQ4LjMwNiwtMTIuMzMxIDE2LjY1NCwtNC4zNjQzIDMzLjA4LC04Ljc3ODMgNDkuMjQ1LC0xMy4yNDcgMy42NDYzLC0xLjAwMjUgNy4yOTI1LC0yLjAwNDkgMTAuOTI2LC0zLjAyNTEgMTMuMDMyLC0zLjYyNjEgMjUuODQzLC03LjI1MzUgMzguNDY0LC0xMC44NzggbCA0LjA1NTgsLTguMTA5OCAtMi40OTc1LC0yLjcwMjUgYyAtMC4zNzk2NiwwLjExNjkzIC0wLjc1OTQsMC4yMzM4MiAtMS4xMjE0LDAuMzM3NiAtMTYuNTQyLDQuODg1IC0zMi43MDksOS41NzM3IC00OC41MTQsMTQuMDQ4IHoiIGlkPSJwYXRoMTA0IiBzdHlsZT0iZmlsbDpub25lIiAvPgogICAgICA8cGF0aCBkPSJtIDM3NC44NiwtOTMuNjIyIGMgLTAuMDEyLC0wLjAxNzY5IC0wLjAzMDksLTAuMDA0NCAtMC4wNDQxLC0wLjAyMjE5IDAsMCAwLjAxMiwwLjAxNzY5IDAuMDQ0MSwwLjAyMjE5IHoiIGlkPSJwYXRoMTA2IiBzdHlsZT0iZmlsbDpub25lIiAvPgogICAgICA8cGF0aCBkPSJtIDQxNS4zNiwtMTEyLjY3IGMgMi41MTcsMi4zNTg4IDUuMDgxOSw0LjgxOTIgNy43MTcsNy4zMzczIDAuMDEyLDAuMDE3NyAwLjA0NDEsMC4wMjIyIDAuMDU3LDAuMDQgLTEuMjg4NywtMS4yOTQyIC0yLjU2NDMsLTIuNTcwNiAtMy44NjIyLC0zLjgwMzEgLTEuMjk3OSwtMS4yMzI1IC0yLjU4NzIsLTIuNDE2NSAtMy45MTE5LC0zLjU3NDIgeiIgaWQ9InBhdGgxMDgiIHN0eWxlPSJmaWxsOiNiZTIwMmUiIC8+CiAgICAgIDxwYXRoIGQ9Im0gNDE1LjM2LC0xMTIuNjcgYyAyLjUxNywyLjM1ODggNS4wODE5LDQuODE5MiA3LjcxNyw3LjMzNzMgMC4wMTIsMC4wMTc3IDAuMDQ0MSwwLjAyMjIgMC4wNTcsMC4wNCAtMS4yODg3LC0xLjI5NDIgLTIuNTY0MywtMi41NzA2IC0zLjg2MjIsLTMuODAzMSAtMS4yOTc5LC0xLjIzMjUgLTIuNTg3MiwtMi40MTY1IC0zLjkxMTksLTMuNTc0MiB6IiBpZD0icGF0aDExMCIgc3R5bGU9Im9wYWNpdHk6MC4zNTtmaWxsOiNiZTIwMmUiIC8+CiAgICAgIDxwYXRoIGQ9Im0gMzc0Ljc3LC05My42NjYgYyAwLDAgMC4wMTIsMC4wMTc2OCAwLjAzMDksMC4wMDQ2IDAuMDEyLDAuMDE3NjkgMC4wMzA5LDAuMDA0NCAwLjA0NDEsMC4wMjIxOSAtMC4zOTQzNiwtMC40MjA5MiAtMC44MTEwMiwtMC43OTc4NiAtMS4xOTIzLC0xLjIwMTEgLTEuNjE3NSwtMS42MjY2IC0zLjI2MTgsLTMuMTc4MyAtNC45MTk4LC00LjYzNzYgMS45Njg1LDEuOTE1IDMuOTk4NiwzLjgzOTEgNi4wMzczLDUuODExOCB6IiBpZD0icGF0aDExMiIgc3R5bGU9ImZpbGw6I2JlMjAyZSIgLz4KICAgICAgPHBhdGggZD0ibSAzNzQuNzcsLTkzLjY2NiBjIDAsMCAwLjAxMiwwLjAxNzY4IDAuMDMwOSwwLjAwNDYgMC4wMTIsMC4wMTc2OSAwLjAzMDksMC4wMDQ0IDAuMDQ0MSwwLjAyMjE5IC0wLjM5NDM2LC0wLjQyMDkyIC0wLjgxMTAyLC0wLjc5Nzg2IC0xLjE5MjMsLTEuMjAxMSAtMS42MTc1LC0xLjYyNjYgLTMuMjYxOCwtMy4xNzgzIC00LjkxOTgsLTQuNjM3NiAxLjk2ODUsMS45MTUgMy45OTg2LDMuODM5MSA2LjAzNzMsNS44MTE4IHoiIGlkPSJwYXRoMTE0IiBzdHlsZT0ib3BhY2l0eTowLjM1O2ZpbGw6I2JlMjAyZSIgLz4KICAgICAgPHBhdGggZD0ibSAyOTQuMDUsMTQuOTkyIGMgLTE2Ljk2MSwyLjUwNjkgLTMzLjcyMyw0LjcyODUgLTUwLjIxOCw2LjY0MyAtMTcuMTIyLDEuOTk0OCAtMzMuOTU1LDMuNjM4NSAtNTAuNDE2LDQuODk2NCAtMC45NTY5LDAuMDc4NDcgLTEuOTQ0NiwwLjE1MjM1IC0yLjkxOTIsMC4yNDM5NSAtMTYuMjE4LDEuMjE1MiAtMzIuMDY0LDIuMDQ0NiAtNDcuNDg5LDIuNDc5NiBsIC0yNC41MDgsNTEuMzExIGMgMy4xNTk1LDAuMTU0MDYgNi4zNTQzLDAuMjgxOSA5LjYxMDgsMC40MTg4NyAxMi4yNTEsMC40NjQyOSAyNS4yNywwLjc0MzI4IDM4LjkwMiwwLjgxNDA5IDE1LjM2NSwwLjA3NTk1IDMxLjU2LC0wLjEzNDQgNDguMzYsLTAuNjAxNDggMTUuNTIyLC0wLjQ1MjA5IDMxLjYwMSwtMS4xNTI0IDQ4LjA1MSwtMi4xMjgyIDE0LjAwMywtMC44MzUxMiAyOC4zMTYsLTEuODQ0OCA0Mi44MzgsLTMuMDkxNCAwLjU0MjQsLTAuMDQ1NSAxLjA2OCwtMC4wNzgxOCAxLjYxMDQsLTAuMTIzNjUgMTMuMzIsLTIyLjE3IDIyLjg5LC00NS43MjIgMzQuMzM1LC02OC41ODMgLTE2LjE4MywyLjc4MDEgLTMyLjI0MSw1LjM1ODMgLTQ4LjE1Nyw3LjcyMTEgeiIgaWQ9InBhdGgxMTYiIHN0eWxlPSJmaWxsOnVybCgjbGluZWFyR3JhZGllbnQzMTU1KSIgLz4KICAgICAgPHBhdGggZD0ibSA2MzkuNTYsLTYxLjAxMiBjIC0xNi4xNzksNC40NTA2IC0zMi42MDQsOC44NjQ3IC00OS4yNDUsMTMuMjQ3IC0xNS45MzUsNC4xODc1IC0zMi4wNDYsOC4yODU5IC00OC4zMDYsMTIuMzMxIC0xNi4yNiw0LjA0NDcgLTMyLjYzLDcuOTc4NiAtNDkuMDk2LDExLjgxOSAtMTYuNzQ0LDMuOTA5OCAtMzMuNTYzLDcuNjgyNSAtNTAuMzc2LDExLjMxNCAtMTYuNjMyLDMuNTc5OCAtMzMuMjksNy4wMTQxIC00OS44OSwxMC4yNjggLTUuOTg3NywxLjE3NDcgLTExLjk3MSwyLjMxODUgLTE3LjkzNiwzLjQ0OTIgLTEwLjMyNSwxLjk0ODUgLTIwLjYwNSwzLjgwOTEgLTMwLjg0LDUuNTgxOSAtMC41NTE1NiwwLjEwNzE1IC0xLjA5ODUsMC4xODM0NiAtMS42NjMyLDAuMjcyOSBsIC0zNC4zMyw2OC41ODMgYyAxLjA4NDgsLTAuMDkxIDIuMTU2NSwtMC4xOTk2OCAzLjI0MTMsLTAuMjkwNjUgMTUuMzksLTEuMzY5NiAzMS4wMTUsLTIuOTQwNyA0Ni43NzksLTQuODA2MyAxNS45MjIsLTEuODczNiAzMS45NjUsLTQuMDI4NiA0OC4wNjIsLTYuNDQzMyAxMy41ODIsLTIuMDMxOSAyNy4xNjIsLTQuMjUzNCA0MC43MjQsLTYuNjgyMSAyLjc1MzIsLTAuNTA0OTEgNS40NTc4LC0xLjAwMTMgOC4xODAyLC0xLjUxMDggMTYuOTgzLC0zLjE4MTIgMzMuMDk0LC02LjY0OTMgNDguMzY1LC0xMC4yOSAxNy4yOTYsLTQuMTI3MSAzMy41MSwtOC40OTM4IDQ4LjYwOSwtMTIuOTk1IDkuOTM4LC0yLjk1MTIgMTkuNDE0LC01Ljk3MTEgMjguNDMxLC04Ljk4MDMgNy42Mzk5LC0yLjY0NjUgMTUuMjMyLC01LjM5NDcgMjIuNzEsLTguMjIyOCAxNy42NTQsLTYuNjQxOSAzNC44NCwtMTMuODQyIDUxLjUsLTIxLjY0IDExLjY0LC0yMi42MTMgMjMuNjY5LC00NS41ODggMzQuNTE0LC02OC45MzUgLTEyLjYyMiwzLjYyNCAtMjUuNDY4LDcuMjc3NSAtMzguNSwxMC45MDQgLTMuNjE1NSwxLjAwNyAtNy4yNzk0LDIuMDIyNiAtMTAuOTI2LDMuMDI1MSB6IiBpZD0icGF0aDExOCIgc3R5bGU9ImZpbGw6dXJsKCNsaW5lYXJHcmFkaWVudDMxNTcpIiAvPgogICAgICA8cGF0aCBkPSJtIDM0My42NiwtMC41MDA5NiBjIDIuMjE5MywtMC40MTA4OSA0LjQ1NjQsLTAuODM0OTEgNi43MjQyLC0xLjI1NDMgNi43NTk2LC0xLjI4MDYgMTMuNjM0LC0yLjU5MTQgMjAuNjU5LC0zLjk1ODcgNy41OTM4LC0xLjQ4NzYgMTUuMzI1LC0zLjA0OTMgMjMuMjIzLC00LjY4MDYgMy45ODAyLC0wLjgxMTA5IDcuOTY1LC0xLjY1MyAxMi4wMjksLTIuNDk4OSAxMi4xMzEsLTIuNTQ2OCAyNC42MDIsLTUuMjYzNyAzNy40MjYsLTguMTMyOCAxNS43NTksLTMuNTM2MiAzMi4wNTgsLTcuMzA3NCA0OC44ODcsLTExLjM2MiAxNi4wNDMsLTMuODU2NCAzMi41NjQsLTcuOTU2OSA0OS41NzksLTEyLjMxNSAxNi4xNDYsLTQuMTI0NyAzMi43MzksLTguNDk4MSA0OS43NzMsLTEzLjA4OSAxNS4xOTgsLTQuMTA3OCAzMC43MjQsLTguNDAzNCA0Ni42NDMsLTEyLjkwOCAwLjc3MjU0LC0wLjIxNjEzIDEuNTQ1MSwtMC40MzIyMyAyLjMwNDUsLTAuNjY2MDYgMTUuODA0LC00LjQ3NDggMzEuOTcxLC05LjE2MzUgNDguNDgzLC0xNC4wNTMgMC4zNzk3LC0wLjExNjkgMC43NTk0LC0wLjIzMzgyIDEuMTIxNCwtMC4zMzc2MSBsIC0xOS4xNDMsLTIwLjY3NiBjIDAuMjM5NSwwLjUwODIgMC41Mjc4LDEuMDA3OSAwLjc2NzQsMS41MTYxIC0yMy4yOTYsLTI0LjQyOCAtNjkuOTIsLTQ1LjI3OCAtMTExLjg4LC00OS45OTcgLTE5LjMzOCwtMi4xNzgzIC0zOS45OTQsLTEuODQyNyAtNjIuNDEzLDAuOTI1MDQgLTE2LjY5LDIuMDU4OSAtMzQuMzUzLDUuNDY5OSAtNTMuMTkxLDEwLjIxOSAtMTYuNDY0LDQuMTQwNSAtMzMuODE0LDkuMjY3OSAtNTIuMTg0LDE1LjQyNSA3LjgzODEsMy43NjMzIDE1LjQ3OCw5LjA3MjcgMjIuOTMxLDE1LjY0NiAxLjI5MzksMS4xNTMxIDIuNjE0LDIuMzQxNyAzLjkxMTksMy41NzQyIDEuMjk3OSwxLjIzMjUgMi41OTEyLDIuNDk1OCAzLjg2MjIsMy44MDMxIC0wLjAxMiwtMC4wMTc3IC0wLjA0NDEsLTAuMDIyMiAtMC4wNTY5LC0wLjA0IC0yOC4yNDcsLTE3LjkzMiAtNTguNjIyLC0xOS45MzggLTg5Ljk3MywtMTQuNTI2IDkuMzkyNCwzLjI2OTQgMjMuMjQyLDkuNjI2OCAzNS42MDMsMjAuMzc5IDEuNjg4OSwxLjQ2MzkgMy4zMTU1LDMuMDI4NyA0LjkxOTgsNC42Mzc2IDAuNDEyMDgsMC40MDc3OSAwLjgxMTAyLDAuNzk3ODYgMS4xOTIzLDEuMjAxMSAtMC4wMTIsLTAuMDE3NjkgLTAuMDMwOSwtMC4wMDQ1IC0wLjA0NDEsLTAuMDIyMTcgMCwwIC0wLjAxMiwtMC4wMTc2OSAtMC4wMzA5LC0wLjAwNDUgLTEwLjEwNSwtNi4wMDYxIC0xOS42MjMsLTEwLjMzNCAtMjkuMTY0LC0xMy4xMzcgLTIuMDM3NCwtMC42MDE4NiAtNC4wODQsLTEuMTQyMSAtNi4xNDQ0LC0xLjU4OTggLTMuMTI1NiwtMC43MDA0NCAtNi4yNjA5LC0xLjIyOSAtOS40MzIzLC0xLjYyMTIgLTIuMDI2MiwtMC4yNTM2MSAtNC4wNjE0LC0wLjQ0NTU1IC02LjEyMzYsLTAuNTYyNzEgLTQuODI0OCwtMC4yOTEwNyAtOS43NjE2LC0wLjI1MjIgLTE0Ljg3NywwLjEzODMgLTEuNTYwOSwwLjExNDggLTMuMTI2NSwwLjI2MDQ0IC00LjcyNzQsMC40MzIzMSAtMi4yMjc5LDAuMzYyMzYgLTQuMzk0MSwwLjczMzg4IC02LjUyOTUsMS4xMSAtOS42NDQ3LDEuNzE4NyAtMTguMDAzLDMuNTgxMSAtMjUuMTgsNS40NDU2IC0zLjU4ODYsMC45MzIyNyAtNi44NTk3LDEuODQ4NiAtOS44NDg5LDIuNzc1NCAtMS4xODc2LDAuMzU5MjggLTIuMzQ0NSwwLjcyMzE0IC0zLjQ1MjgsMS4wNzg1IC0zLjMxMTcsMS4wODM2IC02LjIwNDQsMi4xMDM1IC04LjY5NTcsMy4wNzI3IC0zLjcyODEsMS40NDcyIC02LjU2OTQsMi43NTgyIC04LjU2NzUsMy44MDA2IDEuMzAyNywwLjM1MDk4IDIuNjk3NCwwLjgyNTkgNC4xNjYxLDEuNDM3OCAxMC4xNSw0LjIxNjkgMjMuODkxLDE0LjE2NiAzMy41OTEsMjMuMjE1IGwgLTE3LjU4MywtMTguOTggMTcuNTgzLDE4Ljk4IGMgMC4xNTM1NCwwLjEzMzA4IDAuMjg5MzgsMC4yNzkzIDAuNDQyOTIsMC40MTIzNyAwLjk1MTQyLDAuOTEzMjMgMS44OTgzLDEuODU3MyAyLjg2MjgsMi43ODgyIC0wLjIzMjg4LC0wLjEyOTEgLTAuNDM5NTQsLTAuMTcyODMgLTAuNjU0NzIsLTAuMzE1MDQgbCA2MC4xNjQsNjQuODkgYyAwLjMzOTcyLC0wLjA1OTgyNCAwLjY3OTQ0LC0wLjExOTY1IDEuMDUsLTAuMTc0OTIgeiIgaWQ9InBhdGgxMjAiIHN0eWxlPSJmaWxsOnVybCgjbGluZWFyR3JhZGllbnQzMTU5KSIgLz4KICAgICAgPHBhdGggZD0ibSAxNDEuNzYsMjUuODcxIGMgMTQuMzA4LC0wLjg2ODcxIDMwLjM1OSwtMi4xMjQ1IDQ4LjI0MiwtMy45NDMyIDAuOTI2MDYsLTAuMDgzMDYgMS44ODc2LC0wLjE5MjM0IDIuODMxMywtMC4yODg1IDE1LjQ3MSwtMS41OTQgMzIuMjg4LC0zLjU1NTIgNTAuNDg4LC02LjAyIDE1LjcyMSwtMi4xMDgzIDMyLjQzOSwtNC41NzI1IDUwLjI0NSwtNy40NTgyIDE1LjUxMSwtMi41MDE3IDMxLjg4OCwtNS4zMTYyIDQ5LjA0MywtOC40ODc2IGwgLTU5LjQ5LC02NC41NjkgYyAtMjQuNzM2LC0xMy41NjcgLTQwLjgyMSwtMTYuNzk0IC01OS44NDEsLTE2LjU0NyAtNS4xNzU1LDAuMTYwOTkgLTEwLjUyMywwLjQyMjM4IC0xNS45ODIsMC43OTMzNSAtMTYuNzExLDEuMTQyIC0zNC40NjksMy4yNzg3IC01MS42MDQsNi4xMDY0IC0xNi41NCwyLjc0MjkgLTMyLjQzNSw2LjEzMjggLTQ2LjIzLDkuOTEzNCAtOC43NzE0LDIuNDE1NSAtMTYuNjg0LDQuOTkgLTIzLjMzMiw3LjY0MiAtNS44NjE5LDIuMzQzNCAtMTEuMjY2LDQuNzg2MyAtMTYuMjg3LDcuMzAxOCAyNS4xMDEsMjQuMDUgNTQuNTg5LDU0LjQyMyA3MS45MzEsNzUuNTU3IHoiIGlkPSJwYXRoMTIyIiBzdHlsZT0iZmlsbDp1cmwoI2xpbmVhckdyYWRpZW50MzE2MSkiIC8+CiAgICAgIDxwYXRoIGQ9Im0gNDE5LjI3LC0xMDkuMSBjIDEuMjk3OSwxLjIzMjUgMi41OTEyLDIuNDk1OCAzLjg2MjIsMy44MDMxIC0xLjI3MSwtMS4zMDczIC0yLjU2NDMsLTIuNTcwNiAtMy44NjIyLC0zLjgwMzEgeiIgaWQ9InBhdGgxMjQiIHN0eWxlPSJmaWxsOiNiZTIwMmUiIC8+CiAgICAgIDxwYXRoIGQ9Im0gNDE5LjI3LC0xMDkuMSBjIDEuMjk3OSwxLjIzMjUgMi41OTEyLDIuNDk1OCAzLjg2MjIsMy44MDMxIC0xLjI3MSwtMS4zMDczIC0yLjU2NDMsLTIuNTcwNiAtMy44NjIyLC0zLjgwMzEgeiIgaWQ9InBhdGgxMjYiIHN0eWxlPSJvcGFjaXR5OjAuMzU7ZmlsbDojYmUyMDJlIiAvPgogICAgICA8cGF0aCBkPSJtIDQxOS4yNywtMTA5LjEgYyAxLjI5NzksMS4yMzI1IDIuNTkxMiwyLjQ5NTggMy44NjIyLDMuODAzMSAtMS4yNzEsLTEuMzA3MyAtMi41NjQzLC0yLjU3MDYgLTMuODYyMiwtMy44MDMxIHoiIGlkPSJwYXRoMTI4IiBzdHlsZT0iZmlsbDp1cmwoI2xpbmVhckdyYWRpZW50MzE2MykiIC8+CiAgICAgIDxwYXRoIGQ9Im0gMzc0Ljg2LC05My42MjIgYyAtMC4zOTQzOCwtMC40MjA5MSAtMC44MTEwMiwtMC43OTc4NyAtMS4xOTIzLC0xLjIwMTEgMC4zODEyNiwwLjQwMzIgMC43ODAyLDAuNzkzMjggMS4xOTIzLDEuMjAxMSB6IiBpZD0icGF0aDEzMCIgc3R5bGU9ImZpbGw6I2JlMjAyZSIgLz4KICAgICAgPHBhdGggZD0ibSAzNzQuODYsLTkzLjYyMiBjIC0wLjM5NDM4LC0wLjQyMDkxIC0wLjgxMTAyLC0wLjc5Nzg3IC0xLjE5MjMsLTEuMjAxMSAwLjM4MTI2LDAuNDAzMiAwLjc4MDIsMC43OTMyOCAxLjE5MjMsMS4yMDExIHoiIGlkPSJwYXRoMTMyIiBzdHlsZT0ib3BhY2l0eTowLjM1O2ZpbGw6I2JlMjAyZSIgLz4KICAgICAgPHBhdGggZD0ibSAzNzQuODYsLTkzLjYyMiBjIC0wLjM5NDM4LC0wLjQyMDkxIC0wLjgxMTAyLC0wLjc5Nzg3IC0xLjE5MjMsLTEuMjAxMSAwLjM4MTI2LDAuNDAzMiAwLjc4MDIsMC43OTMyOCAxLjE5MjMsMS4yMDExIHoiIGlkPSJwYXRoMTM0IiBzdHlsZT0iZmlsbDp1cmwoI2xpbmVhckdyYWRpZW50MzE2NSkiIC8+CiAgICAgIDxwYXRoIGQ9Im0gMzc0LjgxLC05My42NjIgYyAwLDAgLTAuMDEyLC0wLjAxNzY5IC0wLjAzMDksLTAuMDA0NSAwLDAgMC4wMTIsMC4wMTc2OSAwLjAzMDksMC4wMDQ1IHoiIGlkPSJwYXRoMTM2IiBzdHlsZT0iZmlsbDojYmUyMDJlIiAvPgogICAgICA8cGF0aCBkPSJtIDM3NC44MSwtOTMuNjYyIGMgMCwwIC0wLjAxMiwtMC4wMTc2OSAtMC4wMzA5LC0wLjAwNDUgMCwwIDAuMDEyLDAuMDE3NjkgMC4wMzA5LDAuMDA0NSB6IiBpZD0icGF0aDEzOCIgc3R5bGU9Im9wYWNpdHk6MC4zNTtmaWxsOiNiZTIwMmUiIC8+CiAgICAgIDxwYXRoIGQ9Im0gMzc0LjgxLC05My42NjIgYyAwLDAgLTAuMDEyLC0wLjAxNzY5IC0wLjAzMDksLTAuMDA0NSAwLDAgMC4wMTIsMC4wMTc2OSAwLjAzMDksMC4wMDQ1IHoiIGlkPSJwYXRoMTQwIiBzdHlsZT0iZmlsbDp1cmwoI2xpbmVhckdyYWRpZW50MzE2NykiIC8+CiAgICAgIDxwYXRoIGQ9Im0gNjk3Ljc0LC04Ny44ODIgYyAxMi44NTYsLTMuODI1NSAyNS45MDMsLTcuNzY0NSAzOS4xOTIsLTExLjg1NiAwLjE5ODcsLTAuMDY1MDEgMC4zNzk3LC0wLjExNjkgMC41Nzg0LC0wLjE4MTkzIDEuODgwOSwtMC41NzE0NCAzLjc0ODUsLTEuMTYwNiA1LjYyOTMsLTEuNzMyIDguOTQ5NywtMi43NjcxIDE2Ljk1NSwtNS4zMjc5IDM1LjE3OCwtMTEuMTI5IC0yLjM0MTcsLTEwLjk2NiAyLjYxODYsLTI0Ljg0OSA5LjE2OTIsLTM5LjM2MyAtMTEuMDc1LDkuMjI1OSAtMTguMzg3LDIyLjA4MiAtMjAuMDYzLDM3LjM5OSAtMjguMTQyLC00MS43NjggLTY0LjE3NCwtNjkuMDE3IC0xMDcuMDcsLTY1LjExNSAtMy44MzIyLDAuMzQ0NjggLTcuNjgzMywwLjkyMjg4IC0xMS42MjQsMS43ODcxIDE2LjQzOCwwLjQ4NzM4IDI4LjM2Nyw3LjM2MzEgNDEuNDQ5LDI3LjI2NSAwLjA0NDEsMC4wMjIzIDAuMTAxLDAuMDYyMyAwLjE0NTEsMC4wODQ2IC0wLjA0NDEsLTAuMDIyMyAtMC4xMDEsLTAuMDYyMyAtMC4xNDUxLC0wLjA4NDYgLTMzLjQzLC0xOC43OCAtNTUuOTY0LC0yMy45MzggLTg1LjIyNywtMjEuNTU3IC02LjkzNjQsMC41NjExOCAtMTQuMjUsMS41MzkgLTIyLjE4MiwyLjg2NiA0My40NzYsNS44NzI5IDcxLjc5MSwyOS4wMzcgODguNTg2LDYzLjA4NiBsIDAuNzc1OCwxLjQ5MzkgMTguMzY3LDE5LjE4MiBjIDIuNDE5MiwtMC42OTYyOSA0LjgyNTQsLTEuNDEwMyA3LjI0OTIsLTIuMTM3NCB6IiBpZD0icGF0aDE0NCIgc3R5bGU9ImZpbGw6dXJsKCNsaW5lYXJHcmFkaWVudDMxNjkpIiAvPgogICAgPC9nPgogIDwvZz4KPC9zdmc+';
	//--
	$img_loading  = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAzMiAzMiIgd2lkdGg9IjMyIiBoZWlnaHQ9IjMyIiBmaWxsPSJncmV5IiBpZD0ibG9hZGluZy1jeWNsb24tc3ZnIj4KICA8cGF0aCB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwIDApIiBkPSJNMCAxMiBWMjAgSDQgVjEyeiI+CiAgICA8YW5pbWF0ZVRyYW5zZm9ybSBhdHRyaWJ1dGVOYW1lPSJ0cmFuc2Zvcm0iIHR5cGU9InRyYW5zbGF0ZSIgdmFsdWVzPSIwIDA7IDI4IDA7IDAgMDsgMCAwIiBkdXI9IjEuNXMiIGJlZ2luPSIwIiByZXBlYXRDb3VudD0iaW5kZWZpbml0ZSIga2V5dGltZXM9IjA7MC4zOzAuNjsxIiBrZXlTcGxpbmVzPSIwLjIgMC4yIDAuNCAwLjg7MC4yIDAuMiAwLjQgMC44OzAuMiAwLjIgMC40IDAuOCIgY2FsY01vZGU9InNwbGluZSIgLz4KICA8L3BhdGg+CiAgPHBhdGggb3BhY2l0eT0iMC41IiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwIDApIiBkPSJNMCAxMiBWMjAgSDQgVjEyeiI+CiAgICA8YW5pbWF0ZVRyYW5zZm9ybSBhdHRyaWJ1dGVOYW1lPSJ0cmFuc2Zvcm0iIHR5cGU9InRyYW5zbGF0ZSIgdmFsdWVzPSIwIDA7IDI4IDA7IDAgMDsgMCAwIiBkdXI9IjEuNXMiIGJlZ2luPSIwLjFzIiByZXBlYXRDb3VudD0iaW5kZWZpbml0ZSIga2V5dGltZXM9IjA7MC4zOzAuNjsxIiBrZXlTcGxpbmVzPSIwLjIgMC4yIDAuNCAwLjg7MC4yIDAuMiAwLjQgMC44OzAuMiAwLjIgMC40IDAuOCIgY2FsY01vZGU9InNwbGluZSIgLz4KICA8L3BhdGg+CiAgPHBhdGggb3BhY2l0eT0iMC4yNSIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMCAwKSIgZD0iTTAgMTIgVjIwIEg0IFYxMnoiPgogICAgPGFuaW1hdGVUcmFuc2Zvcm0gYXR0cmlidXRlTmFtZT0idHJhbnNmb3JtIiB0eXBlPSJ0cmFuc2xhdGUiIHZhbHVlcz0iMCAwOyAyOCAwOyAwIDA7IDAgMCIgZHVyPSIxLjVzIiBiZWdpbj0iMC4ycyIgcmVwZWF0Q291bnQ9ImluZGVmaW5pdGUiIGtleXRpbWVzPSIwOzAuMzswLjY7MSIga2V5U3BsaW5lcz0iMC4yIDAuMiAwLjQgMC44OzAuMiAwLjIgMC40IDAuODswLjIgMC4yIDAuNCAwLjgiIGNhbGNNb2RlPSJzcGxpbmUiIC8+CiAgPC9wYXRoPgo8L3N2Zz4KPCEtLSBodHRwczovL2dpdGh1Yi5jb20vanhuYmxrL2xvYWRpbmcvbG9hZGluZy1jeWNsb24uc3ZnIDsgTGljZW5zZTogTUlUIC0tPg==';
	$code_loading_start = '<div id="img-loader" style="position:fixed; top:30px; right:10px;"><img width="48" height="48" alt="Loading ..." title="Loading ..." src="'.AppPackUtils::escape_html($img_loading).'"></div>';
	$code_loading_stop  = '<script>try{ document.getElementById(\'img-loader\').innerHTML = \'\'; }catch(err){ console.error(err); }</script>';
	//--
	$btn_return_index = '<button style="position:fixed; top:10px; right:10px; padding: 3px 12px 3px 12px !important; font-size:1em !important; font-weight:bold !important; color:#FFFFFF !important; background-color:#4B73A4 !important; border:1px solid #3C5A98 !important; border-radius:3px !important; cursor: pointer !important;" onclick="self.location=\'?\';" title="Click this button to return to the application main index">Return to Index</button>';
	$btn_return_goback = '<button style="position:fixed; top:100px; right:10px; padding: 3px 12px 3px 12px !important; font-size:1em !important; font-weight:bold !important; color:#FFFFFF !important; background-color:#4B73A4 !important; border:1px solid #3C5A98 !important; border-radius:3px !important; cursor: pointer !important;" onclick="try{ self.history.back(); } catch(err){ console.log(err); }" title="Click this button to Return Back">Go Back</button>';
	//--
	if((string)$_REQUEST['remote'] == 'appcodepack') {
		$action_cursor_style_header = 'default';
		$action_header = '';
		$btn_return_index = '';
		$btn_return_goback = '';
		$server_info_url = ($_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://').$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];
		$background_color = '#F5F5F5';
	} else {
		$action_cursor_style_header = 'pointer';
		$action_header = "self.location='?';";
		$background_color = '#FFFFFF';
		$server_info_url = '';
	} //end if
	//--

	//--
	$app_ids_arr = (array) AppPackUtils::list_to_array((string)ADMIN_AREA_APP_IDS, true);
	$app_ok_ids_arr = [];
	for($i=0; $i<AppPackUtils::array_size($app_ids_arr); $i++) {
		$app_ids_arr[$i] = (string) $app_ids_arr[$i];
		if(((string)$app_ids_arr[$i] != '') AND (strlen((string)$app_ids_arr[$i]) >= 5) AND (strlen((string)$app_ids_arr[$i]) <= 63)) {
			if(preg_match('/^[_a-zA-Z0-9\-\.@]+$/', (string)$app_ids_arr[$i])) { // {{{SYNC-VALID-APPCODEPACK-APPID}}} allow safe path characters except: # / which are reserved
				$app_ok_ids_arr[] = (string) $app_ids_arr[$i];
			} //end if
		} //end if
	} //end for
	$app_ids_arr = array(); // free mem
	if(AppPackUtils::array_size($app_ok_ids_arr) <= 0) {
		AppPackUtils::raise_error('Empty App IDs Validated List ... ');
		return;
	} //end if
	//--

	//--
	$apps_list_html = '';
	$apps_list_html .= '<select name="appid" title="Select an AppID from the list" style="font-size:1em!important;" required>'."\n";
	$apps_list_html .= '<option value="">--- No AppID Selected ---</option>'."\n";
	for($i=0; $i<AppPackUtils::array_size($app_ok_ids_arr); $i++) {
		$apps_list_html .= '<option value="'.AppPackUtils::escape_html((string)$app_ok_ids_arr[$i]).'">'.AppPackUtils::escape_html((string)$app_ok_ids_arr[$i]).'</option>'."\n";
	} //end for
	$apps_list_html .= '</select>';
	//--
	$is_request_app_id_ok = false;
	if(((string)trim((string)$_REQUEST['appid']) != '') AND (in_array((string)trim((string)$_REQUEST['appid']), (array)$app_ok_ids_arr))) {
		$is_request_app_id_ok = true;
	} //end if
	//--

	//--
	$the_err_logs_dir = 'tmp/logs/';
	//--

	//--
	$data = [];
	$data['(c)-year'] = date('Y');
	$data['app_version'] = (string) APPCODEUNPACK_VERSION;
	$data['app-logo'] = (string) $img_logo_app;
	$data['php-logo'] = (string) $img_logo_php;
	$data['apache-logo'] = (string) $img_logo_apache;
	//--
	$data['html-main-area'] = '';
	switch((string)$_REQUEST['run']) {
		case 'verrlog':
			//--
			$the_err_action_log = (string) trim((string)$_REQUEST['q']);
			$the_err_log = (string) trim((string)$_REQUEST['f']);
			$the_err_chk_log = (string) trim((string)$_REQUEST['c']);
			if((string)$the_err_log != '') {
				$the_err_log = (string) trim((string)hex2bin((string)$the_err_log));
			} //end if
			if((string)$the_err_log == '') {
				$data['html-main-area'] .= '<div title="Status / Errors" style="background:#FF3300; color:#FFFFFF; font-weight:bold; padding:5px; border-radius:5px;">'.'ERROR: Empty Error Log FileName'.'</div>';
			} else { // OK
				if((string)$the_err_chk_log == '') {
					$data['html-main-area'] .= '<div title="Status / Errors" style="background:#FF3300; color:#FFFFFF; font-weight:bold; padding:5px; border-radius:5px;">'.'ERROR: Empty Checksum for the Error Log FileName: '.AppPackUtils::escape_html($the_err_log).'</div>';
				} else { // OK
					if((string)$the_err_chk_log !== sha1((string)$the_err_log.ADMIN_AREA_SECRET)) {
						$data['html-main-area'] .= '<div title="Status / Errors" style="background:#FF3300; color:#FFFFFF; font-weight:bold; padding:5px; border-radius:5px;">'.'ERROR: Invalid Checksum for the Error Log FileName: '.AppPackUtils::escape_html($the_err_log).'</div>';
					} else { // OK
						if(substr((string)$the_err_log, -4, 4) != '.log') {
							$data['html-main-area'] .= '<div title="Status / Errors" style="background:#FF3300; color:#FFFFFF; font-weight:bold; padding:5px; border-radius:5px;">'.'ERROR: Invalid File Extension for Log FileName: '.AppPackUtils::escape_html($the_err_log).'</div>';
						} else { // OK
							AppPackUtils::raise_error_if_unsafe_path((string)$the_err_log);
							if(!AppPackUtils::is_type_file((string)$the_err_log)) {
								$data['html-main-area'] .= '<div title="Status / Warning" style="background:#FFCC00; color:#333333; font-weight:bold; padding:5px; border-radius:5px;">'.'Error Log File does No more Exists: '.AppPackUtils::escape_html((string)$the_err_log).'</div>';
							} else { // OK
								switch((string)$the_err_action_log) {
									case 'x': // DELETE
										$data['html-main-area'] .= '<h2>DELETING the App Error Log</h2>';
										$data['html-main-area'] .= '<h4 style="color:#FF9900">LogFile: '.AppPackUtils::escape_html((string)$the_err_log).'</h4>';
										AppPackUtils::delete((string)$the_err_log);
										if(AppPackUtils::path_exists((string)$the_err_log)) {
											$data['html-main-area'] .= '<div title="Status / Errors" style="background:#FF3300; color:#FFFFFF; font-weight:bold; padding:5px; border-radius:5px;">'.'ERROR: Failed to DELETE the Error Log FileName: '.AppPackUtils::escape_html($the_err_log).'</div>';
										} else {
											$data['html-main-area'] .= '<div title="Status / OK" style="background:#98C726; color:#000000; font-weight:bold; padding:5px; border-radius:5px;">'.'<h3>'.'The Error Log File has been DELETED: '.AppPackUtils::escape_html($the_err_log).'</h3>'.'</div>';
										} //end if else
										$tmp_arr_goback = (array) explode('/', (string)$the_err_log);
										if((string)trim((string)$tmp_arr_goback[0]) != '') {
											$data['html-main-area'] .= '<button style="position:fixed; top:100px; right:10px; padding: 3px 12px 3px 12px !important; font-size:1em !important; font-weight:bold !important; color:#FFFFFF !important; background-color:#4B73A4 !important; border:1px solid #3C5A98 !important; border-radius:3px !important; cursor: pointer !important;" onclick="self.location=\''.'?run=errlogs&appid='.rawurlencode((string)trim((string)$tmp_arr_goback[0])).'\';" title="Click this button to return to the Errors Log List">Return to the List</button>';
										} //end if
										break;
									case 'r': // READ + DISPLAY (RAW)
										header('Content-Type: text/plain');
										header('Content-Disposition: inline; filename="'.AppPackUtils::safe_filename((string)pathinfo((string)$the_err_log, PATHINFO_FILENAME), '-').'"');
										header('Content-Length: '.(int)AppPackUtils::get_file_size((string)$the_err_log));
										readfile((string)$the_err_log);
										return; // stop here
										break;
									default: // VIEW
										$data['html-main-area'] .= '<h2>DISPLAYING the App Error Log</h2>';
										$data['html-main-area'] .= '<h4 style="color:#FF9900;display:inline-block!important;">LogFile: '.AppPackUtils::escape_html((string)$the_err_log).'</h4> &nbsp; ';
										$data['html-main-area'] .= '<a style="display:inline-block; text-decoration: none !important; padding: 3px 12px 3px 12px !important; font-size:1em !important; font-weight:bold !important; color:#FFFFFF !important; background-color:#FF9900 !important; border:1px solid #FFAA00 !important; border-radius:3px !important; cursor: pointer !important;" title="Delete this Error Log File" href="'.AppPackUtils::escape_html('?run='.rawurlencode((string)$_REQUEST['run']).'&q=x'.'&f='.rawurlencode((string)$_REQUEST['f']).'&c='.rawurlencode($_REQUEST['c'])).'" onClick="return confirm(\'Are you sure you want to delete this log file ?\');">Delete</a>';
										$data['html-main-area'] .= '<iframe style="border:1px solid #CCCCCC; width:99vw; height:50vh; background:#EEEEEE; color:#111111;" name="app_view_err_log" id="app_view_err_log" scrolling="auto" src="'.AppPackUtils::escape_html('?run='.rawurlencode((string)$_REQUEST['run']).'&q=r'.'&f='.rawurlencode((string)$_REQUEST['f']).'&c='.rawurlencode($_REQUEST['c'])).'" marginwidth="5" marginheight="5" hspace="0" vspace="0" frameborder="0"></iframe>'."\n";
										$data['html-main-area'] .= (string) $btn_return_goback;
								} //end switch
							} //end if else
						} //end if else
					} //end if else
				} //end if else
			} //end if else
			//--
			$data['html-main-area'] .= (string) $btn_return_index;
			//--
			break;
			//--
		case 'errlogs':
			//--
			if($is_request_app_id_ok === true) {
				//--
				if((string)trim((string)$_REQUEST['appid']) == '') {
					AppPackUtils::raise_error('Empty AppID provided for logs !');
					return;
				} //end if
				if(!in_array((string)trim((string)$_REQUEST['appid']), (array)$app_ok_ids_arr)) {
					AppPackUtils::raise_error('Invalid AppID provided for logs: '.(string)$_REQUEST['appid']);
					return;
				} //end if
				//--
				$data['html-main-area'] .= '<h2>DISPLAYING the App Error Logs on this App Server</h2>';
				$data['html-main-area'] .= '<h4 style="color:#FF9900">Selected APP-ID: '.AppPackUtils::escape_html((string)$_REQUEST['appid']).'</h4>';
				//--
				$the_real_err_logs_dir = (string) AppPackUtils::safe_filename((string)trim((string)$_REQUEST['appid']));
				if((string)$the_real_err_logs_dir == '') {
					AppPackUtils::raise_error('Invalid AppID Directory Name: '.$the_real_err_logs_dir);
					return;
				} //end if
				$the_real_err_logs_dir = (string) AppPackUtils::add_dir_last_slash((string)$the_real_err_logs_dir);
				AppPackUtils::raise_error_if_unsafe_path((string)$the_real_err_logs_dir);
				$the_real_err_logs_dir = (string) AppPackUtils::add_dir_last_slash($the_real_err_logs_dir.$the_err_logs_dir);
				AppPackUtils::raise_error_if_unsafe_path((string)$the_real_err_logs_dir);
				//--
				if((!AppPackUtils::is_type_dir((string)$the_real_err_logs_dir)) OR (!AppPackUtils::have_access_read((string)$the_real_err_logs_dir))) {
					//--
					$data['html-main-area'] .= '<div title="Status / Warning" style="background:#FFCC00; color:#333333; font-weight:bold; padding:5px; border-radius:5px;">'.'Folder does Not Exists or Is Not Readable: '.AppPackUtils::escape_html((string)$the_real_err_logs_dir).'</div>';
					//--
				} else { // OK
					//--
					clearstatcache(true, (string)$the_real_err_logs_dir);
					$scan_items = scandir((string)$the_real_err_logs_dir);
					//--
					if(!is_array($scan_items)) {
						//--
						$data['html-main-area'] .= '<div title="Status / Errors" style="background:#FF3300; color:#FFFFFF; font-weight:bold; padding:5px; border-radius:5px;">'.'FAILED: Cannot Get Folder Content for: '.AppPackUtils::escape_html($the_real_err_logs_dir).'</div>';
						//--
					} else { // OK
						//--
						$arr_err_logs = [];
						for($i=0; $i<AppPackUtils::array_size($scan_items); $i++) {
							if(((string)trim((string)$scan_items[$i]) != '') AND ((string)$scan_items[$i] != '.') AND ((string)$scan_items[$i] != '..')) { // fix ok
								if(AppPackUtils::check_if_safe_file_or_dir_name((string)$scan_items[$i])) {
									$chk_path = (string) $the_real_err_logs_dir.$scan_items[$i];
									if(AppPackUtils::check_if_safe_path((string)$chk_path)) {
										if(AppPackUtils::is_type_file((string)$chk_path)) {
											if((substr((string)$chk_path, -4, 4) == '.log') AND (substr((string)$scan_items[$i], 0, 10) == 'phperrors-')) {
												$arr_err_logs[(string)$scan_items[$i]] = (string) $chk_path;
											} //end if
										} //end if
									} //end if
								} //end if
							} //end if
						} //end for
						//--
						if(AppPackUtils::array_size($arr_err_logs) > 0) {
							$data['html-main-area'] .= '<h5 style="color:#778899">LIST of Error Log Files: '.AppPackUtils::escape_html((string)$the_real_err_logs_dir).'</h5>';
							foreach($arr_err_logs as $key => $val) {
								$data['html-main-area'] .= '<div title="'.AppPackUtils::escape_html((string)$val).'" style="background:#ECECEC; color:#333333; border-radius:5px; padding:8px; margin-bottom:5px; font-weight:bold; cursor:help;"><a style="display:inline-block; text-decoration: none !important; padding: 3px 12px 3px 12px !important; font-size:1em !important; font-weight:bold !important; color:#FFFFFF !important; background-color:#4B73A4 !important; border:1px solid #3C5A98 !important; border-radius:3px !important; cursor: pointer !important;" title="Display the Error Log File: '.AppPackUtils::escape_html((string)$key).'" href="'.AppPackUtils::escape_html('?run=verrlog&f='.rawurlencode((string)bin2hex((string)$val)).'&c='.rawurlencode(sha1((string)$val.ADMIN_AREA_SECRET))).'">View</a>&nbsp;&nbsp;&nbsp;'.AppPackUtils::escape_html((string)$key).'&nbsp;&nbsp;&nbsp;<span style="color:#555555;">['.AppPackUtils::pretty_print_bytes(AppPackUtils::get_file_size($the_real_err_logs_dir.$key), 2, '&nbsp;').']</span></div>';
							} //end foreach
						} else {
							$data['html-main-area'] .= '<div style="margin-bottom:20px; padding:7px; line-height:1.125em; font-weight:bold; color: #FFFFFF; background:#778899; border:1px solid #8899AA; border-radius:6px; box-shadow: 2px 2px 3px #D2D2D2;">';
							$data['html-main-area'] .= '<h3>'.'There are no Error Log Files in: '.AppPackUtils::escape_html($the_real_err_logs_dir).'</h3>';
							$data['html-main-area'] .= '</div>';
						} //end if else
						//--
					} //end if else
					//--
				} //end if else
				//--
			} else {
				//--
				$data['html-main-area'] .= '<h2>DISPLAY the App Error Logs on this App Server</h2>';
				$data['html-main-area'] .= '<div style="margin-bottom:20px; padding:7px; line-height:1.125em; font-weight:bold; color: #FFFFFF; background:#009ACE; border:1px solid #0089BD; border-radius:6px; box-shadow: 2px 2px 3px #D2D2D2;">';
				$data['html-main-area'] .= 'SELECT an AppID from below to list the App Error Logs from this App Server.<br>';
				$data['html-main-area'] .= '</div>';
				$data['html-main-area'] .= '<form name="viewlogs-form" id="viewlogs-form" method="get" action="?">';
				$data['html-main-area'] .= '<input type="hidden" name="run" value="'.AppPackUtils::escape_html((string)$_REQUEST['run']).'">';
				$data['html-main-area'] .= '<div style="font-size:1.25em!important;">'."\n";
				$data['html-main-area'] .= (string) $apps_list_html.' &nbsp; '."\n";
				$data['html-main-area'] .= '<button type="submit" style="padding: 3px 12px 3px 12px !important; font-size:1em !important; font-weight:bold !important; color:#FFFFFF !important; background-color:#4B73A4 !important; border:1px solid #3C5A98 !important; border-radius:3px !important; cursor: pointer !important;" title="Click this button to proceed">Display the Error Logs from this server</button>'."\n";
				$data['html-main-area'] .= '</div>'."\n";
				$data['html-main-area'] .= '</form>';
				//--
			} //end if else
			//--
			$data['html-main-area'] .= (string) $btn_return_index;
			//--
			break;
		case 'lstdpls': // list deploys
			//--
			$data['html-main-area'] .= '<h2>DISPLAYING the AppCodePack Deployments from this App Server</h2>';
			$data['html-main-area'] .= '<div style="margin-bottom:20px; padding:7px; line-height:1.125em; font-weight:bold; color: #FFFFFF; background:#009ACE; border:1px solid #0089BD; border-radius:6px; box-shadow: 2px 2px 3px #D2D2D2;">';
			$data['html-main-area'] .= '<h4 style="display:inline!important;">List of Managed AppIDs: '.AppPackUtils::escape_html(implode(' ; ', (array)$app_ok_ids_arr)).'</h4>';
			$data['html-main-area'] .= '</div>';
			//--
			$the_dpls_dir = (string) AppPackUtils::unpack_get_basefolder_name().'#DEPLOY-VERSIONS/';
			AppPackUtils::raise_error_if_unsafe_path((string)$the_dpls_dir);
			//--
			if((!AppPackUtils::is_type_dir((string)$the_dpls_dir)) OR (!AppPackUtils::have_access_read((string)$the_dpls_dir))) {
				//--
				$data['html-main-area'] .= '<div title="Status / Warning" style="background:#FFCC00; color:#333333; font-weight:bold; padding:5px; border-radius:5px;">'.'Folder does Not Exists or Is Not Readable: '.AppPackUtils::escape_html((string)$the_dpls_dir).'</div>';
				//--
			} else { // OK
				//--
				clearstatcache(true, (string)$the_dpls_dir);
				$scan_items = scandir((string)$the_dpls_dir);
				//--
				if(!is_array($scan_items)) {
					//--
					$data['html-main-area'] .= '<div title="Status / Errors" style="background:#FF3300; color:#FFFFFF; font-weight:bold; padding:5px; border-radius:5px;">'.'FAILED: Cannot Get Folder Content for: '.AppPackUtils::escape_html($the_dpls_dir).'</div>';
					//--
				} else { // OK
					//--
					rsort($scan_items);
					$arr_lst_dpls = [];
					for($i=0; $i<AppPackUtils::array_size($scan_items); $i++) {
						if(((string)trim((string)$scan_items[$i]) != '') AND ((string)$scan_items[$i] != '.') AND ((string)$scan_items[$i] != '..')) { // fix ok
							if(AppPackUtils::check_if_safe_file_or_dir_name((string)$scan_items[$i])) {
								$chk_path = (string) $the_dpls_dir.$scan_items[$i];
								if(AppPackUtils::check_if_safe_path((string)$chk_path)) {
									if(AppPackUtils::is_type_dir((string)$chk_path)) { // is a dir
										if(AppPackUtils::is_type_file((string)$chk_path.'.log')) { // and have a valid log attached
											$arr_lst_dpls[(string)$scan_items[$i]] = (string) $chk_path;
										} //end if
									} //end if
								} //end if
							} //end if
						} //end if
					} //end for
					//--
					if(AppPackUtils::array_size($arr_lst_dpls) > 0) {
						$data['html-main-area'] .= '<h4 style="color:#778899;">LIST of App Deployments for ALL managed AppIDs (listed in reverse order, newer first) found in folder: '.AppPackUtils::escape_html((string)$the_dpls_dir).'</h4>';
						$data['html-main-area'] .= '<script>function doFilter(filter) { var expr = ""; if(filter) { expr = String(document.getElementById("filter-fld").value); } var el = document.getElementById("filter-container").querySelectorAll(".filter-element"); for(var i=0; i<el.length; i++) { var elTxt = String(el[i].innerText); el[i].style.display = "block"; if(expr) { if(elTxt.indexOf(expr) === -1) { el[i].style.display = "none"; } } } }</script><input type="text" id="filter-fld" title="Filter by" placeholder="Filter by" style="width:200px;"> &nbsp; <button id="filter-btn" onClick="doFilter(true); return false;">Display by Filter</button>&nbsp;<button id="filter-reset-btn" onClick="doFilter(false); return false;">Display All</button><br><br>';
						$data['html-main-area'] .= '<div id="filter-container">';
						foreach($arr_lst_dpls as $key => $val) {
							$tmp_arr_parse_dpl = (array) explode('#', (string)$key);
							$tmp_arr_parse_dpl = (int) ceil((string)$tmp_arr_parse_dpl[(int)AppPackUtils::array_size($tmp_arr_parse_dpl)-1]);
							if($tmp_arr_parse_dpl > 0) {
								$tmp_arr_dtime_dpl = (string) date('Y-m-d H:i:s O', (int)$tmp_arr_parse_dpl);
							} else {
								$tmp_arr_dtime_dpl = '(? unknown, could not parse date/time)';
							} //end if else
							$tmp_arr_parse_dpl = array(); // reset
							$data['html-main-area'] .= '<div title="'.AppPackUtils::escape_html((string)$val).'" class="filter-element" style="background:#ECECEC; color:#333333; border-radius:5px; padding:8px; margin-bottom:5px; font-weight:bold; cursor:help;">'.AppPackUtils::escape_html((string)$key).' &nbsp; &nbsp; @  &nbsp; &nbsp; '.AppPackUtils::escape_html((string)$tmp_arr_dtime_dpl).'</div>';
							$tmp_arr_dtime_dpl = ''; // reset
						} //end foreach
						$data['html-main-area'] .= '</div>';
					} else {
						$data['html-main-area'] .= '<div style="margin-bottom:20px; padding:7px; line-height:1.125em; font-weight:bold; color: #FFFFFF; background:#778899; border:1px solid #8899AA; border-radius:6px; box-shadow: 2px 2px 3px #D2D2D2;">';
						$data['html-main-area'] .= '<h3>'.'There are no Deployments in: '.AppPackUtils::escape_html($the_dpls_dir).'</h3>';
						$data['html-main-area'] .= '</div>';
					} //end if else
					//--
				} //end if else
				//--
			} //end if else
			//--
			$data['html-main-area'] .= (string) $btn_return_index;
			//--
			break;
		case 'deploy':
			//--
			if((AppPackUtils::array_size($_FILES['znetarch']) > 0) AND ($is_request_app_id_ok === true)) {
				//--
				$data['html-main-area'] .= '<h2>DEPLOYING a new AppCodePack Archive on this App Server</h2>';
				//$data['html-main-area'] .= '<pre>'.AppPackUtils::escape_html(print_r($_FILES['znetarch'],1)).'</pre>';
				//--
				if(((int)$_FILES['znetarch']['size'] <= 0) OR ((int)$_FILES['znetarch']['error'] > 0) OR ((string)$_FILES['znetarch']['tmp_name'] == '') OR ((string)$_FILES['znetarch']['name'] == '') OR ((string)substr((string)$_FILES['znetarch']['name'], -10, 10) !== '.z-netarch')) {
					//--
					$data['html-main-area'] .= '<br><div title="Status / Errors" style="background:#FF3300; color:#FFFFFF; font-weight:bold; padding:5px; border-radius:5px;">'.'File Upload FAILED !'.'</div>';
					//--
				} else { // OK
					//-- check AppID, must not be defined already
					if(defined('APPCODEPACK_APP_ID')) {
						AppPackUtils::raise_error('APPCODEPACK_APP_ID was already defined and should not ... ');
						return;
					} //end if
					//-- check and define AppID
					if((string)trim((string)$_REQUEST['appid']) == '') {
						AppPackUtils::raise_error('Empty AppID provided for unpack !');
						return;
					} //end if
					if(!in_array((string)trim((string)$_REQUEST['appid']), (array)$app_ok_ids_arr)) {
						AppPackUtils::raise_error('Invalid AppID provided for unpack: '.(string)$_REQUEST['appid']);
						return;
					} //end if
					define('APPCODEPACK_APP_ID', (string)trim((string)$_REQUEST['appid']));
					//-- re-check AppID
					if(!in_array((string)APPCODEPACK_APP_ID, (array)$app_ok_ids_arr)) {
						AppPackUtils::raise_error('APPCODEPACK_APP_ID defined but Invalid: '.APPCODEPACK_APP_ID);
						return;
					} //end if
					//-- show AppID
					$data['html-main-area'] .= '<h4 style="color:#FF9900">Selected APP-ID: '.AppPackUtils::escape_html((string)APPCODEPACK_APP_ID).'</h4>';
					$data['html-main-area'] .= '<h5 style="color:#778899">Package FileName: '.AppPackUtils::escape_html((string)$_FILES['znetarch']['name']).'</h5>';
					//-- security check AppID Hash {{{SYNC-VALID-APPCODEPACK-APPHASH}}}
					if((strlen((string)trim((string)$_REQUEST['apphashid'])) != 40) OR ((string)sha1((string)APPCODEPACK_APP_ID.'*AppCode(Un)Pack*'.(string)ADMIN_AREA_SECRET) !== (string)trim((string)$_REQUEST['apphashid']))) {
						//--
						$data['html-main-area'] .= '<div title="Status / Errors" style="background:#FF3300; color:#FFFFFF; font-weight:bold; padding:5px; border-radius:5px;">'.'FAILED: The AppID-Hash is Invalid !'.'</div>';
						//--
					} else { // OK
						//--
						$fdata = '';
						//-- test if uploaded file and read it
						if(is_uploaded_file((string)$_FILES['znetarch']['tmp_name'])) {
							$fdata = AppPackUtils::read_uploaded((string)$_FILES['znetarch']['tmp_name'], 0, 'no', 'no'); // allow absolute path access as 1st was checked if uploaded file !
						} //end if
						//-- check if data was read
						if((string)$fdata == '') {
							//--
							$data['html-main-area'] .= '<div title="Status / Errors" style="background:#FF3300; color:#FFFFFF; font-weight:bold; padding:5px; border-radius:5px;">'.'FAILED to Read the Uploaded File !'.'</div>';
							//--
						} else { // OK
							//--
							$err_deploy = (string) AppPackUtils::unpack_netarchive(
								(string) $fdata,
								false
							);
							//--
							if((string)$err_deploy != '') {
								//--
								$data['html-main-area'] .= '<div title="Status / Warning" style="background:#FFCC00; color:#333333; font-weight:bold; padding:5px; border-radius:5px;">'.'Deployment CANCELED: '.AppPackUtils::escape_html($err_deploy).'</div>';
								//--
							} else {
								//--
								$data['html-main-area'] .= '<div title="Status / OK" style="background:#98C726; color:#000000; font-weight:bold; padding:5px; border-radius:5px;">'.'<h3>'.'[ &nbsp; STATUS: OK &nbsp; &radic; ]'.'</h3>'.'</div>';
								//--
							} //end if else
							//--
						} //end if
						//--
					} //end if else
					//--
				} //end if else
				//--
			} else { // app package upload form
				//--
				$data['html-main-area'] .= '<h2>DEPLOY a new AppCodePack Archive on this App Server</h2>';
				$data['html-main-area'] .= '<div style="margin-bottom:20px; padding:7px; line-height:1.125em; font-weight:bold; color: #FFFFFF; background:#009ACE; border:1px solid #0089BD; border-radius:6px; box-shadow: 2px 2px 3px #D2D2D2;">';
				$data['html-main-area'] .= '*** INFO: DEPLOYING A PACKAGE ON THE SERVER CANNOT BE UNDONE ***<br>** TO UPGRADE/ROLLBACK ANY APP ON THIS SERVER REQUIRES TO UPLOAD AN APPCODEPACK NETARCHIVE PACKAGE ... **<br><br>';
				$data['html-main-area'] .= '* PACKAGE DEPLOYMENT INSTRUCTIONS: *<ol>';
				$data['html-main-area'] .= '<li>SELECT an AppID from below and UPLOAD a valid AppCodePack NetArchive Package (.z-netarch) that match the selected AppID using a valid AppID-Hash.</li>';
				$data['html-main-area'] .= '<li>After the upload, if the Package will be validated, it will be deployed on the Apache/PHP server within the coresponding AppID folder (directory).</li>';
				$data['html-main-area'] .= '<li>Deploying the Package on the server will replace all the files and folders within the coresponding AppID base folder, except: [ tmp, wpub, #{protected} and @symlinks ].</li>';
				$data['html-main-area'] .= '<li>If the Package contains the `maintenance.html` file the HTTP 503 Maintenance Mode will be enabled automatically if explicit enabled (.htaccess settings).</li>';
				$data['html-main-area'] .= '<li>If the Package contains the `appcode-upgrade.php` file (App deploy task/upgrade script) it will be executed prior to disable the Maintenance Mode.</li>';
				$data['html-main-area'] .= '</ol>';
				$data['html-main-area'] .= '</div>';
				$data['html-main-area'] .= '<form name="unpack-form" id="unpack-form" method="post" enctype="multipart/form-data" action="?run='.AppPackUtils::escape_html(rawurlencode((string)$_REQUEST['run'])).'">';
				$data['html-main-area'] .= '<div style="font-size:1.25em!important;">'."\n";
				$data['html-main-area'] .= (string) $apps_list_html.' &nbsp; '."\n";
				$data['html-main-area'] .= '<input type="text" name="apphashid" size="40" maxlength="40" style="font-size:1em!important;" placeholder="AppID-Hash" title="Enter the AppID-Hash = sha1 of AppID+Secret" autocomplete="off" required>'."\n";
				$data['html-main-area'] .= '<br><br>';
				$data['html-main-area'] .= 'Package: <input type="file" title="Browse an AppPackCode NetArchive (.z-netarch) Package" name="znetarch" id="znetarch" style="font-size:1em !important;" required> &nbsp; ';
				$data['html-main-area'] .= '<span style="color:#DCDCDC;">Max File Size support (php.ini): '.AppPackUtils::escape_html(ini_get('upload_max_filesize')).'</span><br><br>';
				$data['html-main-area'] .= '<button type="submit" style="padding: 3px 12px 3px 12px !important; font-size:1em !important; font-weight:bold !important; color:#FFFFFF !important; background-color:#FF9900 !important; border:1px solid #FFAA00 !important; border-radius:3px !important; cursor: pointer !important;" title="Click this button to proceed">Upload the Package and Deploy the Code on this App Server</button>'."\n";
				$data['html-main-area'] .= '</div>'."\n";
				$data['html-main-area'] .= '</form>';
				//--
			} //end if else
			//--
			$data['html-main-area'] .= (string) $btn_return_index;
			//--
			break;
		default:
			//--
			$data['html-main-area'] .= '<div style="margin-bottom:10px; padding:7px; line-height:1.125em; font-weight:bold; color: #FFFFFF; background:#009ACE; border:1px solid #0089BD; border-radius:6px; box-shadow: 2px 2px 3px #D2D2D2;"><table>'.'<tr valign="top">'.'<td>Authenticated User: </td><td> &nbsp; &nbsp; </td><td>'.AppPackUtils::escape_html((string)$_SERVER['PHP_AUTH_USER'].' @ '.(string)$_SERVER['REMOTE_ADDR']).'</td>'.'</tr><tr valign="top">'.'<td>List of Managed AppIDs: </td><td> &nbsp; &nbsp; </td><td>'.AppPackUtils::escape_html(implode(' ; ', (array)$app_ok_ids_arr)).'</td>'.'</tr>'.'</table></div>';
			$data['html-main-area'] .= '<div style="padding:4px; background:#D3E397; font-weight:bold;">';
			$data['html-main-area'] .= '<h2>Select a TASK to RUN from the list below</h2>';
			$data['html-main-area'] .= '</div>';
			$data['html-main-area'] .= '<hr>'."\n";
			$data['html-main-area'] .= '<div style="font-size:1.25em!important;">'."\n";
			$data['html-main-area'] .= '<select id="task-run-sel" style="font-size:1em!important;">'."\n";
			$data['html-main-area'] .= '<option value="">--- No Task Selected ---</option>'."\n";
			$data['html-main-area'] .= '<optgroup label="AppCode.UnPack TASKS: DEPLOYMENT">'."\n";
			$data['html-main-area'] .= '<option value="deploy">DEPLOY a NEW AppCodePack ARCHIVE on this App Server</option>'."\n";
			$data['html-main-area'] .= '<option value="lstdpls">DISPLAY the LIST of DEPLOYMENTS from this App Server</option>'."\n";
			$data['html-main-area'] .= '</optgroup>'."\n";
			$data['html-main-area'] .= '<optgroup label="AppCode.UnPack TASKS: DISPLAY-LOGS">'."\n";
			$data['html-main-area'] .= '<option value="errlogs">DISPLAY the App ERROR LOGS on this App Server</option>'."\n";
			$data['html-main-area'] .= '</optgroup>'."\n";
			$data['html-main-area'] .= '</select> &nbsp; ';
			$data['html-main-area'] .= '<button style="padding: 3px 12px 3px 12px !important; font-size:1em !important; font-weight:bold !important; color:#FFFFFF !important; background-color:#4B73A4 !important; border:1px solid #3C5A98 !important; border-radius:3px !important; cursor: pointer !important;" onClick="var selTask = \'\'; try { selTask = document.getElementById(\'task-run-sel\').value; } catch(err){} if(selTask){ self.location = \'?run=\' + selTask; } else { alert(\'No Task Selected ...\'); }" title="Click this button to run the selected task from the near list">Run the Selected TASK</button>'."\n";
			$data['html-main-area'] .= '</div>'."\n";
			//--
	} //end switch
	//--
	foreach($data as $key => $val) {
		if(stripos((string)$key, 'html') === false) {
			$data[(string)$key] = (string) AppPackUtils::escape_html((string)$val);
		} else {
			$data[(string)$key] = (string) $val;
		} //end if
	} //end foreach
	//--
	$action_cursor_style_header = (string) AppPackUtils::escape_html((string)$action_cursor_style_header);
	$action_header = (string) AppPackUtils::escape_html((string)$action_header);
	$meta_charset = (string) AppPackUtils::escape_html((string)SMART_FRAMEWORK_CHARSET);
	$background_color = (string) AppPackUtils::escape_html((string)$background_color);
	if($server_info_url) {
		$server_info_url = (string) '<div align="right" style="font-weight:bold; color:#009ace;">[Remote:'.AppPackUtils::escape_html((string)$_REQUEST['remote']).']&nbsp;#&nbsp;'.AppPackUtils::escape_html((string)$server_info_url).'&nbsp;</div>';
	} //end if
	//--

//--
$tpl = <<<HTML_CODE
<!DOCTYPE html>
<html>
<head>
	<meta charset="{$meta_charset}">
	<title>Smart::AppCode.UNPACK</title>
	<style type="text/css">
		* { font-family:arial,sans-serif;font-smooth:always; }
		body { background-color: {$background_color}; }
		hr {border:none 0; border-top:1px solid #CCCCCC; height:1px; }
		a { color:#000000 !important; }
	</style>
</head>
<body>
	<table><tr>
		<td width="64" style="cursor:help;"><img width="48" height="48" alt="AppCodeUnpack {$data['app_version']}" title="AppCodeUnpack {$data['app_version']}" src="{$data['app-logo']}"></td>
		<td><h1 style="display:inline; cursor:{$action_cursor_style_header};" onclick="{$action_header}"><span style="color:#778899;">Smart</span><span style="color:#DCDCDC;">::</span><span style="color:#555555;">AppCode</span><span style="color:#999999;">.</span><span style="color:#3C5A98;">UnPack</span></h1></td><td> &nbsp; &nbsp; &nbsp; </td>
		<td>
			<img width="32" height="32" alt="PHP-Logo" title="PHP-Logo" src="{$data['php-logo']}">&nbsp;
			<img width="32" height="32" alt="Apache-Logo" title="Apache-Logo" src="{$data['apache-logo']}">&nbsp;
		</td>
	</tr></table>
	{$server_info_url}
	<hr>
	{$data['html-main-area']}
	<hr>
	<div align="right"><small style="color:#CCCCCC;">&copy; 2013-{$data['(c)-year']} unix-world.org</small></div>
	<br>
</body>
</html>
HTML_CODE;
//--

	//--
	echo (string) $tpl;
	//--

	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


abstract class AppCodePackAbstractUpgrade {

	// ::
	// v.20200121


	// exec a command and return the exit status
	// returns: TRUE/FALSE ; Throws Error if not successful
	final public static function ExecCmd($cmd) {
		//--
		$arr = AppPackUtils::exec_cmd((string)$cmd);
		$exitcode = (int) $arr['exitcode'];
		$output = (string) $arr['output'];
		//--
		if($exitcode !== 0) {
			throw new Exception(__METHOD__.'() :: FAILED to exec command ['.$cmd.'] # ExitCode: '.$exitcode.' ; Errors: '.$output);
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


	// run a command and if not successful throw error
	// returns: ARRAY[stdout/stderr/exitcode] ; Throws Error if not successful
	final public static function RunCmd($cmd) {
		//--
		$parr = (array) AppPackUtils::run_proc_cmd(
			(string) $cmd,
			null,
			null,
			null
		);
		$exitcode = $parr['exitcode']; // don't make it INT !!!
		$stdout = (string) $parr['stdout'];
		$stderr = (string) $parr['stderr'];
		if(($exitcode !== 0) OR ((string)$stderr != '')) { // exitcode is zero (0) on success and no stderror
			throw new Exception(__METHOD__.'() :: FAILED to run command ['.$cmd.'] # ExitCode: '.$exitcode.' ; Errors: '.$stderr);
			return (array) $parr;
		} //end if
		//--
		return (array) $parr;
		//--
	} //END FUNCTION


	// clear the maintenance.html file (may be needed if need to run a command after maintenance has been disabled ...)
	// returns: 0/1 ; Throws Error if not successful
	final public static function RemoveMaintenanceFile() {
		//--
		return (int) self::RemoveAppFile('maintenance.html');
		//--
	} //END FUNCTION


	// remove a file inside app folder (may be needed for some upgrades to remove temporary task files ...)
	// returns: 0/1 ; Throws Error if not successful
	final public static function RemoveAppFile($file_path) {
		//--
		$file_path = AppPackUtils::safe_pathname((string)$file_path);
		if((string)$file_path == '') {
			throw new Exception(__METHOD__.'() :: Empty FilePath');
			return 0;
		} //end if
		//--
		if((string)APPCODEPACK_APP_ID == '') {
			throw new Exception(__METHOD__.'() :: Empty APPCODEPACK_APP_ID');
			return 0;
		} //end if
		//--
		if(!AppPackUtils::check_if_safe_file_or_dir_name((string)APPCODEPACK_APP_ID)) {
			throw new Exception(__METHOD__.'() :: Unsafe APPCODEPACK_APP_ID: '.APPCODEPACK_APP_ID);
			return 0;
		} //end if
		//--
		$file_app_path = (string) AppPackUtils::add_dir_last_slash((string)APPCODEPACK_APP_ID).$file_path;
		if(!AppPackUtils::check_if_safe_path((string)$file_app_path)) {
			throw new Exception(__METHOD__.'() :: Unsafe Path: '.$file_app_path);
			return 0;
		} //end if
		//--
		if(AppPackUtils::is_type_file((string)$file_app_path)) { // this scripts runs in the parent of {app-id}/
			AppPackUtils::delete((string)$file_app_path);
			if(AppPackUtils::path_exists((string)$file_app_path)) {
				throw new Exception(__METHOD__.'() :: FAILED to remove the file: '.(string)$file_app_path);
				return 0;
			} //end if
		} //end if
		//--
		return 1;
		//--
	} //END FUNCTION


	// returns a prefixed path for a file with the AppID
	// returns: STRING
	final public static function GetPrefixedFilePath($y_file) {
		//--
		if((string)APPCODEPACK_APP_ID == '') {
			throw new Exception(__METHOD__.'() :: Empty APPCODEPACK_APP_ID');
			return 'nonexistent-file-path-@error';
		} //end if
		//--
		return (string) AppPackUtils::add_dir_last_slash((string)APPCODEPACK_APP_ID).$y_file;
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

// DO NOT MODIFY THIS CLASS !!! JUST REPLACE WITH THE MASTER VERSION FROM appcodepack.php !!!

final class AppPackUtils {

	// ::
	// v.20210302 {{{SYNC-CLASS-APP-PACK-UTILS}}}

	private static $cache = [];

	private static $htaccess_protect = '
# Deny Access: Apache 2.2
<IfModule !mod_authz_core.c>
	Order allow,deny
	Deny from all
</IfModule>
# Deny Access: Apache 2.4
<IfModule mod_authz_core.c>
	Require all denied
</IfModule>

# Disable Indexing
<IfModule mod_autoindex.c>
	IndexIgnore *
</IfModule>
Options -Indexes
';


	//================================================================
	public static function InstantFlush() {
		//--
		$output_buffering_status = @ob_get_status();
		//-- type: 0 = PHP_OUTPUT_HANDLER_INTERNAL ; 1 = PHP_OUTPUT_HANDLER_USER
		if(is_array($output_buffering_status)) {
			if(((string)$output_buffering_status['type'] == '0') AND ($output_buffering_status['chunk_size'] > 0)) { // avoid to break user level output buffering(s), so enable this just for level zero (internal, if set in php.ini)
				@ob_flush();
			} //end if
		} //end if
		//--
		@flush();
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function unpack_get_basefolder_name() {
		//--
		return '#APPCODE-UNPACK#/'; // must end with slash ; {{{SYNC-UNPACK-FOLDER-NAME}}}
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function unpack_create_basefolder() {
		//--
		$unpack_basefolder = self::unpack_get_basefolder_name();
		self::raise_error_if_unsafe_path((string)$unpack_basefolder);
		//--
		clearstatcache(true, (string)$unpack_basefolder);
		//--
		if(!self::is_type_dir((string)$unpack_basefolder)) {
			self::dir_create((string)$unpack_basefolder, false); // non-recursive !!
			if(!self::is_type_dir((string)$unpack_basefolder)) {
				return 'Failed to create the NetArchive Unpack Base Folder';
			} //end if
		} //end if
		//--
		if(self::write_if_not_exists((string)$unpack_basefolder.'.htaccess', (string)self::$htaccess_protect, 'yes') != 1) { // write if not exists wit content compare
			return 'NetArchive Unpack Base Folder .htaccess failed to be (re)written !';
		} //end if
		//--
		if(!self::is_type_file((string)$unpack_basefolder.'index.html')) {
			if(self::write((string)$unpack_basefolder.'index.html', '') != 1) {
				return 'NetArchive Unpack Base Folder index.html failed to be (re)written !';
			} //end if
		} //end if
		//--
		return '';
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function unpack_netarchive($y_content, $testonly) {
		//--
		clearstatcache(true); // do a full clear stat cache at the begining
		//--
		if(!$testonly) { // IF NOT TEST: CREATE NEW @ TMP NETARCH FOLDER
			$test_create_basefolder = self::unpack_create_basefolder();
			if((string)$test_create_basefolder != '') {
				return 'ERROR: '.$test_create_basefolder;
			} //end if
		} //end if
		//--
		$tmp_ppfx = (string) self::unpack_get_basefolder_name().'#TMP-UNPACK-@'.self::safe_filename((string)APPCODEPACK_APP_ID);
		//--
		$the_tmp_netarch_lock = (string) rtrim($tmp_ppfx, '/').'.LOCK'; // the lock file ; {{{SYNC-NETARCH-DENIED-PATHS}}}
		if(self::check_if_safe_path((string)$the_tmp_netarch_lock) != 1) {
			return 'ERROR: Invalid TMP Package Lock File Path: '.$the_tmp_netarch_lock;
		} //end if
		$the_tmp_netarch_folder = (string) self::add_dir_last_slash($tmp_ppfx); // must end with trailing slash ; {{{SYNC-NETARCH-DENIED-PATHS}}}
		if(self::check_if_safe_path((string)$the_tmp_netarch_folder) != 1) {
			return 'ERROR: Invalid TMP Package Unpack Folder Path: '.$the_tmp_netarch_folder;
		} //end if
		//--
		if(!$testonly) { // IF NOT TEST: CREATE NEW @ TMP NETARCH FOLDER
			//--
			if(self::path_exists((string)$the_tmp_netarch_lock)) {
				return 'ERROR: TMP Package Lock File must be manually cleared first !';
			} //end if
			//--
			$test_fxop = self::write((string)$the_tmp_netarch_lock, 'NetArchive Unpack Lock File @ '.date('Y-m-d H:i:s O'));
			if(($test_fxop != 1) OR (!self::is_type_file((string)$the_tmp_netarch_lock))) {
				return 'ERROR: TMP Package LockFile failed to be created !';
			} //end if
			//--
			if(self::path_exists((string)$the_tmp_netarch_folder)) {
				self::dir_delete((string)$the_tmp_netarch_folder);
				if(self::path_exists((string)$the_tmp_netarch_folder)) {
					return 'ERROR: TMP Package Folder cannot be cleared !';
				} //end if
			} //end if
			//--
		} //end if
		//--
		$err = (string) self::unpack_operate_netarchive($y_content, $testonly, $the_tmp_netarch_folder);
		//--
		if(!$testonly) { // IF NOT TEST: CREATE NEW @ TMP NETARCH FOLDER
			//--
			if(self::path_exists((string)$the_tmp_netarch_folder)) {
				self::dir_delete((string)$the_tmp_netarch_folder);
				if(self::path_exists((string)$the_tmp_netarch_folder)) {
					return 'ERROR: TMP Package Folder cannot be cleared !';
				} //end if
			} //end if
			//--
			$test_fxop = self::delete((string)$the_tmp_netarch_lock);
			if(($test_fxop != 1) OR (self::is_type_file((string)$the_tmp_netarch_lock))) {
				return 'ERROR: TMP Package LockFile failed to be removed !';
			} //end if
			//--
		} //end if
		//--
		return (string) $err;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function unpack_operate_netarchive($y_content, $testonly, $the_tmp_netarch_folder) {
		//--
		$y_content = (string) trim((string)$y_content);
		//--
		if(!function_exists('gzdecode')) {
			self::raise_error('ERROR: The PHP ZLIB Extension (gzdecode) is required for AppNetPackager');
			return;
		} //end if
		//-- CHECK RESTORE ROOT
		if(!defined('APPCODEPACK_APP_ID')) {
			return 'A required constant (APPCODEPACK_APP_ID) has not been defined';
		} //end if
		$restoreroot = (string) self::safe_filename((string)APPCODEPACK_APP_ID);
		self::raise_error_if_unsafe_path((string)$restoreroot);
		//-- DEFINE @ TMP NETARCH FOLDERS
		$unpack_versionsfolder = (string) self::unpack_get_basefolder_name().'#DEPLOY-VERSIONS/'; // must have trailing slash
		$the_tmp_netarch_data_hash = (string) self::sha512((string)$y_content);
		//-- CHECK SAFE NAME @ TMP NETARCH FOLDER
		self::raise_error_if_unsafe_path((string)$the_tmp_netarch_folder);
		self::raise_error_if_unsafe_path((string)$unpack_versionsfolder);
		//--
		if(!$testonly) { // IF NOT TEST: CREATE NEW @ TMP NETARCH FOLDER
			//--
			self::dir_create((string)$the_tmp_netarch_folder, true); // recursive dir create
			if(self::is_type_dir((string)$the_tmp_netarch_folder)) {
				if(!self::have_access_write((string)$the_tmp_netarch_folder)) {
					return 'ERROR: TMP Package Folder is not writable !';
				} //end if
				if(self::write((string)$the_tmp_netarch_folder.'.htaccess', (string)self::$htaccess_protect) != 1) {
					return 'ERROR: TMP Package Folder .htaccess failed to be (re)written !';
				} //end if
				if(self::write((string) $the_tmp_netarch_folder.'index.html', '') != 1) {
					return 'ERROR: TMP Package Folder index.html failed to be (re)written !';
				} //end if
			} else {
				return 'ERROR: TMP Package Folder cannot be created !';
			} //end if
			//--
			if(!self::is_type_dir((string)$unpack_versionsfolder)) {
				self::dir_create((string)$unpack_versionsfolder, false); // non-recursive !!
			} //end if
			if(!self::is_type_dir((string)$unpack_versionsfolder)) {
				return 'ERROR: Failed to create the NetArchive Saved Versions TMP Base Folder: '.$unpack_versionsfolder;
			} //end if
			//--
			$tmp_chk_last_data_hash = '';
			$tmp_the_package_logfile_path = (string) $unpack_versionsfolder.'package-@'.self::safe_filename((string)APPCODEPACK_APP_ID).'.log'; // package-@APP_ID.log registers the checksum of last uploaded package to avoid re-upload many times the same package ; but will alow restore from older or newer non-identical packages
			self::raise_error_if_unsafe_path((string)$tmp_the_package_logfile_path);
			if(self::is_type_file((string)$tmp_the_package_logfile_path)) {
				$tmp_chk_last_data_hash = (string) trim((string)self::read((string)$tmp_the_package_logfile_path));
			} //end if
			if((string)$tmp_chk_last_data_hash == (string)$the_tmp_netarch_data_hash) {
				return 'ERROR: NetArchive Package already Deployed (the current package to deploy is identical with last deployed package) !';
			} //end if
			if(self::write((string)$tmp_the_package_logfile_path, (string)$the_tmp_netarch_data_hash) != 1) {
				return 'ERROR: NetArchive Saved Versions TMP Folder '.$tmp_the_package_logfile_path.' failed to be (re)written !';
			} //end if
			//--
		} //end if
		//--
		if((string)$y_content == '') {
			return 'ERROR: Package is Empty !';
		} //end if
		//--
		if(substr($y_content, 0, 23) != '#AppCodePack-NetArchive') {
			return 'ERROR: Invalid Package Type !';
		} //end if
		//--
		if(strpos($y_content, '#END-NetArchive') === false) {
			return 'ERROR: Incomplete Package !';
		} //end if
		//--
		$y_content = (string)str_replace(["\r\n", "\r"], ["\n", "\n"], (string)$y_content);
		//--
		$the_pack_name = '';
		$the_pack_appid = '';
		$the_pack_dir = '';
		$the_pack_items = 0;
		$cksum_pak = '';
		$cksum_raw = '';
		$data = '';
		//--
		$arr = array(); // init
		$arr = (array) explode("\n", (string)$y_content);
		//$y_content = ''; // free mem !!! DO NOT CLEAR, MUST BE LOGGED !!!
		//--
		for($i=0; $i<count($arr); $i++) {
			$arr[$i] = (string) trim((string)$arr[$i]);
			if(strlen($arr[$i]) > 0) {
				if((string)substr($arr[$i], 0, 1) == '#') {
					if((string)substr((string)strtolower($arr[$i]), 0, 6) == '#file:') {
						$the_pack_name = (string) trim((string)substr($arr[$i], 6));
					} elseif((string)substr((string)strtolower($arr[$i]), 0, 8) == '#app-id:') {
						$the_pack_appid = (string) trim((string)substr((string)$arr[$i], 8));
					} elseif((string)substr((string)strtolower($arr[$i]), 0, 18) == '#package-info-dir:') {
						$the_pack_dir = (string) trim((string)substr((string)$arr[$i], 19));
					} elseif((string)substr((string)strtolower($arr[$i]), 0, 20) == '#package-info-items:') {
						$the_pack_items = (int) (string) trim((string)substr($arr[$i], 20));
					} elseif((string)substr((string)strtolower($arr[$i]), 0, 19) == '#package-signature:') {
						$cksum_pak = (string) trim((string)substr((string)$arr[$i], 19));
					} elseif((string)substr((string)strtolower($arr[$i]), 0, 20) == '#checksum-signature:') {
						$cksum_raw = (string) trim((string)substr((string)$arr[$i], 20));
					} //end if
				} else {
					$data = (string) trim((string)$arr[$i]);
				} //end if
			} //end if
		} //end for
		$arr = array(); // free mem
		if((string)$the_pack_name == '') {
			return 'ERROR: Empty Package File Name !';
		} //end if
		if(self::check_if_safe_file_or_dir_name((string)$the_pack_name) != 1) {
			return 'ERROR: Invalid Package File Name: '.$the_pack_name;
		} //end if
		if((string)$the_pack_appid == '') {
			return 'ERROR: Empty Package AppID !';
		} //end if
		if(self::check_if_safe_file_or_dir_name((string)$the_pack_appid) != 1) {
			return 'ERROR: Invalid Package AppID: '.$the_pack_appid;
		} //end if
		if((string)$the_pack_dir == '') {
			return 'ERROR: Empty Package Dir !';
		} //end if
		if(self::check_if_safe_file_or_dir_name((string)$the_pack_dir) != 1) {
			return 'ERROR: Invalid Package Dir: '.$the_pack_dir;
		} //end if
		if((int)$the_pack_items <= 0) {
			return 'ERROR: Package Items Number appear to be Zero: '.$the_pack_items;
		} //end if
		if((string)$cksum_pak == '') {
			return 'ERROR: Empty Package Checksum !';
		} //end if
		if((string)$cksum_raw == '') {
			return 'ERROR: Empty Data Checksum !';
		} //end if
		if((string)$data == '') {
			return 'ERROR: Empty Data !';
		} //end if
		//--
		if((string)$cksum_pak != (string)self::sha512($data)) {
			return 'ERROR: Package Checksum Failed !';
		} //end if else
		//--
		$data = base64_decode((string)$data, true); // STRICT ! don't make it string, may return false
		if(($data === false) OR ((string)trim((string)$data) == '')) {
			return 'ERROR: Package B64 Failed !';
		} //end if
		$data = @gzdecode((string)$data); // don't make it string, may return false
		if(($data === false) OR ((string)trim((string)$data) == '')) {
			return 'ERROR: Data inflate ERROR !';
		} //end if
		//--
		if((string)$cksum_raw != (string)self::sha512($data)) {
			return 'ERROR: Data Checksum Failed !';
		} //end if else
		if(strpos((string)$data, '#[AppCodePack-Package//START]') === false) {
			return 'ERROR: Invalid Data Type !';
		} //end if
		if(strpos((string)$data, '#[AppCodePack-Package//END]') === false) {
			return 'ERROR: Incomplete Data !';
		} //end if
		//--
		$folders_pak = 0;
		$folders_num = 0;
		$files_pak = 0;
		$files_num = 0;
		//--
		$arr = array(); // init
		$arr = (array) explode("\n", (string)$data);
		$data = ''; // free mem, we do not need it anymore
		$basefoldername = '';
		$the_pack_files_n_dirs = [];
		for($i=0; $i<count($arr); $i++) {
			$arr[$i] = (string) trim((string)$arr[$i]);
			if((string)$arr[$i] != '') {
				if((string)substr($arr[$i], 0, 1) == '#') {
					//--
					//echo $arr[$i]."\n";
					if((string)substr((string)strtolower($arr[$i]), 0, 9) == '#folders:') {
						$folders_pak = (int) trim(substr($arr[$i], 9));
					} elseif((string)substr((string)strtolower($arr[$i]), 0, 7) == '#files:') {
						$files_pak = (int) trim(substr($arr[$i], 7));
					} //end if
					//--
				} else {
					//--
					$cols = (array) explode("\t", (string)$arr[$i]);
					//--
					$tmp_fname 			= (string) trim((string)$cols[0]);
					$tmp_ftype 			= (string) trim((string)$cols[1]);
					$tmp_fsize 			= (int) $cols[2];
					$tmp_cksum_name 	= (string) trim((string)$cols[3]);
					$tmp_cksum_cx_raw 	= (string) trim((string)$cols[4]);
					$tmp_cksum_cx_pak 	= (string) trim((string)$cols[5]);
					$tmp_fcontent 		= (string) trim((string)$cols[6]);
					//--
					$cols = array(); // free mem
					//--
					if(((string)$tmp_ftype == 'DIR') AND ((string)$tmp_fname != '')) {
						//--
						// dirname[\t]DIR[\t]0[\t]sha1checksumname[\t][\t][\t][\n]
						//--
						if((string)$tmp_cksum_name != (string)sha1($tmp_fname)) {
							return 'ERROR: DirName Checksum Failed on: '.$tmp_fname;
						} //end if
						//--
						if(!self::check_if_safe_path((string)$tmp_fname)) {
							return 'ERROR: Invalid Folder Name in archive: '.$tmp_fname;
						} //end if
						$the_new_dir = (string) self::add_dir_last_slash((string)$the_tmp_netarch_folder).$tmp_fname;
						if(!self::check_if_safe_path((string)$the_new_dir)) {
							return 'ERROR: Invalid Folder Path to unarchive: '.$the_new_dir;
						} //end if
						//--
						if((string)$basefoldername == '') { // get the first available, as unarch base folder
							if(strpos((string)$tmp_fname, '/') === false) {
								$basefoldername = (string) $tmp_fname;
							} //end if
						} //end if
						//--
						if(!$testonly) { // IF NOT TEST: CREATE NEW SUB-FOLDER AS IN ARCH @ TMP NETARCH FOLDER
							self::dir_create((string)$the_new_dir, true); // recursive dir create
							if(self::is_type_dir((string)$the_new_dir)) {
								if(!self::have_access_write((string)$the_new_dir)) {
									return 'ERROR: TMP Package Sub-Folder is not writable: '.$the_new_dir;
								} //end if
							} else {
								return 'ERROR: TMP Package Sub-Folder cannot be created: '.$the_new_dir;
							} //end if
						} //end if
						//--
						$the_new_dir = ''; // free mem
						//--
						$folders_num += 1;
						$the_pack_files_n_dirs[] = (string) '(D): '.$tmp_fname;
						//--
					} elseif(((string)$tmp_ftype == 'FILE') AND ((string)$tmp_fname != '')) {
						//--
						// filename[\t]filetype[\t]filesize[\t]sha1checksumname[\t]sha1checksumfile[\t]sha1checksumarch[\t]filecontent_gzencode-FORCE_GZIP_bin2hex[\n]
						//--
						if((string)$tmp_cksum_name != (string)sha1($tmp_fname)) {
							return 'ERROR: FileName Checksum Failed on: '.$tmp_fname;
						} //end if
						//--
						if((string)$tmp_cksum_cx_pak != (string)sha1($tmp_fcontent)) {
							return 'ERROR: File Package Checksum Failed on: '.$tmp_fname;
						} //end if
						//--
						$tmp_fcontent = hex2bin((string)trim((string)$tmp_fcontent)); // don't make it string, may return false
						if($tmp_fcontent === false) {
							return 'ERROR: File Content Failed to be restored on: '.$tmp_fname;
						} //end if
						$tmp_fcontent = (string) $tmp_fcontent;
						if((string)$tmp_cksum_cx_raw != (string)sha1($tmp_fcontent)) {
							return 'ERROR: File Content Checksum Failed on: '.$tmp_fname;
						} //end if
						//--
						$the_new_dir = (string) pathinfo((string)$tmp_fname, PATHINFO_DIRNAME);
						if((string)trim((string)$the_new_dir) == '') {
							return 'ERROR: Empty Folder Prefix for File Name to unarchive: '.$tmp_fname;
						} //end if
						$the_new_dir = (string) self::add_dir_last_slash((string)$the_tmp_netarch_folder).$the_new_dir;
						if(!self::check_if_safe_path((string)$the_new_dir)) {
							return 'ERROR: Invalid Folder Path of File to unarchive: '.$the_new_dir.' @ '.$tmp_fname;
						} //end if
						$the_new_file = (string) self::add_dir_last_slash((string)$the_tmp_netarch_folder).$tmp_fname;
						if(!self::check_if_safe_path((string)$the_new_file)) {
							return 'ERROR: Invalid File Path to unarchive: '.$the_new_file;
						} //end if
						//--
						if(!$testonly) { // IF NOT TEST: CREATE NEW FILES + RESTORE THEIR ORIGINAL CONTENT AS IN ARCH @ TMP NETARCH FOLDER
							self::dir_create((string)$the_new_dir, true); // recursive dir create
							if(self::is_type_dir((string)$the_new_dir)) {
								if(!self::have_access_write((string)$the_new_dir)) {
									return 'ERROR: TMP Package Sub-Folder of File is not writable: '.$the_new_dir.' @ '.$tmp_fname;
								} //end if
								if(self::write((string)$the_new_file, (string)$tmp_fcontent) != 1) { // returns 0/1
									return 'ERROR: Failed to restore a File from archive: '.$tmp_fname;
								} //end if
								if(!self::is_type_file((string)$the_new_file)) {
									return 'ERROR: Failed to restore a File from archive (path check): '.$tmp_fname;
								} //end if
								if(!self::have_access_read((string)$the_new_file)) {
									return 'ERROR: Failed to restore a File from archive (readable check): '.$tmp_fname;
								} //end if
								if(!self::have_access_write((string)$the_new_file)) {
									return 'ERROR: Failed to restore a File from archive (writable check): '.$tmp_fname;
								} //end if
								$fop = (string) self::read((string)$the_new_file);
								if((string)$fop !== (string)$tmp_fcontent) {
									return 'ERROR: Failed to restore a File from archive (content check): '.$tmp_fname;
								} //end if
								if((string)sha1((string)$fop) != (string)$tmp_cksum_cx_raw) {
									return 'ERROR: Failed to restore a File from archive (content checksum): '.$tmp_fname;
								} //end if
								$fop = ''; // free mem
								$tmp_fcontent = ''; // free mem
							} else {
								return 'ERROR: TMP Package Sub-Folder of File cannot be created: '.$the_new_dir.' @ '.$tmp_fname;
							} //end if
						} //end if
						//--
						$the_new_dir = ''; // free mem
						$the_new_file = ''; // free mem
						//--
						$files_num += 1;
						$the_pack_files_n_dirs[] = (string) '(F): '.$tmp_fname;
						//--
					} else {
						//--
						return 'ERROR: Invalid or Empty Item Type in NetArchive: ['.$tmp_ftype.'] @ '.$tmp_fname;
						//--
					} //end if else
					//--
				} //end if
			} //end if
		} //end for
		$arr = array();
		//--
		if(($folders_pak <= 0) OR ($folders_pak != $folders_num)) {
			return 'ERROR: Invalid Folders Number: '.self::add_dir_last_slash($folders_pak).$folders_num;
		} //end if else
		if(($files_pak <= 0) OR ($files_pak != $files_num)) {
			return 'ERROR: Invalid Files Number: '.self::add_dir_last_slash($files_pak).$files_num;
		} //end if else
		if((int)self::array_size($the_pack_files_n_dirs) !== (int)$the_pack_items) {
			return 'ERROR: Invalid Archive Total Items: [Registered='.(int)$the_pack_items.';Detected='.(int)self::array_size($the_pack_files_n_dirs).']';
		} //end if
		//--
		if((string)$basefoldername == '') {
			return 'ERROR: Failed to detect the Base Folder of Archive';
		} //end if
		if(!self::check_if_safe_file_or_dir_name((string)$basefoldername)) {
			return 'ERROR: Invalid Base Folder Name of Archive (check): '.$basefoldername;
		} //end if
		if((string)$the_pack_dir !== (string)$basefoldername) {
			return 'ERROR: The detected Base Folder of Archive does not match registered one: [Registered='.$the_pack_dir.';Detected='.$basefoldername.']';
		} //end if
		//--
		if(!$testonly) {
			//--
			$basefolderpath = (string) self::add_dir_last_slash((string)$the_tmp_netarch_folder.$basefoldername);
			//--
			if(!self::check_if_safe_path((string)$basefolderpath)) {
				return 'ERROR: Invalid Base Folder Path of Archive (Invalid Path): '.$basefolderpath;
			} //end if
			if(!self::is_type_dir((string)$basefolderpath)) {
				return 'ERROR: Invalid Base Folder Path of Archive (Not Directory): '.$basefolderpath;
			} //end if
			if(!self::have_access_read((string)$basefolderpath)) {
				return 'ERROR: Invalid Base Folder Path of Archive (Not Readable): '.$basefolderpath;
			} //end if
			//--
			$the_tmp_netarch_versions_hash = (string) self::safe_filename((string)APPCODEPACK_APP_ID.'@'.date('YmdHis').'#'.self::format_number_dec(microtime(true), 4, '.', '')); // use AppID and microtime
			$the_tmp_netarch_versions_folder = (string) $unpack_versionsfolder.$the_tmp_netarch_versions_hash.'/'; // must end with trailing slash ; {{{SYNC-NETARCH-DENIED-PATHS}}}
			$the_tmp_netarch_versions_logfile = (string) $unpack_versionsfolder.$the_tmp_netarch_versions_hash.'.log'; // must be file
			//--
			self::dir_create((string)$the_tmp_netarch_versions_folder, false);
			if(!self::is_type_dir((string)$the_tmp_netarch_versions_folder)) {
				return 'ERROR: Failed to create the NetArchive Saved Versions TMP Folder: '.$the_tmp_netarch_versions_folder;
			} //end if
			//--
			if((string)$restoreroot == '') { // restore to script path
				//--
				return 'ERROR: The NetArchive CURRENT Restore (Root) Folder Name is Empty !';
				//--
			} else { // have a restore root sub-folder
				//--
				if(strpos((string)$restoreroot, '/') !== false) { // must not have slashes
					return 'ERROR: Invalid NetArchive Restore (Root) Folder (must not contain slashes): '.$basefoldername;
				} //end if
				//--
				if((string)$the_pack_appid !== (string)$restoreroot) {
					return 'ERROR: The NetArchive Restore (Root) Folder does not match the AppID: [Registered='.$the_pack_appid.';Detected='.$restoreroot.']';
				} //end if
				//--
				$restoreroot = self::add_dir_last_slash($restoreroot); // add the trailing slash
				//--
				if(self::check_if_safe_path((string)$restoreroot)) {
					self::dir_create((string)$restoreroot, false); // not recursive
					if(!self::is_type_dir((string)$restoreroot)) {
						return 'ERROR: Failed to create the NetArchive Restore (Root) Folder: '.$restoreroot;
					} //end if
					if(!self::have_access_read((string)$restoreroot)) {
						return 'ERROR: The NetArchive Restore (Root) Folder is not readable: '.$restoreroot;
					} //end if
					if(!self::have_access_write((string)$restoreroot)) {
						return 'ERROR: The NetArchive Restore (Root) Folder is not writable: '.$restoreroot;
					} //end if
				} else {
					return 'ERROR: Invalid NetArchive Restore (Root) Folder: '.$restoreroot;
				} //end if
				//--
			} //end if
			//--
			$found_files_restored = [];
			//--
			clearstatcache(true, (string)$basefolderpath);
			$arr_dir_files = scandir((string)$basefolderpath); // don't make it array, can be false
			if(($arr_dir_files !== false) AND (self::array_size($arr_dir_files) > 0)) {
				$arr_dir_sorted_files = []; // init
				for($i=0; $i<self::array_size($arr_dir_files); $i++) {
					if((string)$arr_dir_files[$i] == 'maintenance.html') { // maintenance.html must be first !
						$arr_dir_sorted_files[] = (string) $arr_dir_files[$i];
					} //end if
				} //end for
				for($i=0; $i<self::array_size($arr_dir_files); $i++) {
					if(((string)$arr_dir_files[$i] != 'maintenance.html') AND ((string)trim((string)$arr_dir_files[$i]) != '') AND ((string)$arr_dir_files[$i] != '.') AND ((string)$arr_dir_files[$i] != '..')) { // fix ok
						$arr_dir_sorted_files[] = (string) $arr_dir_files[$i]; // add the rest of files except . and ..
					} //end if
				} //end for
				$arr_dir_files = (array) $arr_dir_sorted_files;
				$arr_dir_sorted_files = []; // free mem
			} else {
				$arr_dir_files = array();
			} //end if else
			if(self::array_size($arr_dir_files) > 0) {
				$found_files_total = 0;
				$found_files_ok = 0;
				$found_files_notok = [];
				for($i=0; $i<self::array_size($arr_dir_files); $i++) {
					$file = (string) $arr_dir_files[$i];
					if(((string)$file != '') AND ((string)$file != '.') AND ((string)$file != '..') AND ((string)$file != '.svn') AND ((string)$file != '.git') AND ((string)$file != '.gitignore') AND ((string)$file != '.gitattributes') AND (substr((string)$file, 0, 4) != '.DS_') AND ((string)$file != 'src') AND (substr((string)$file, 0, 4) != 'src.') AND (substr((string)$file, 0, 1) != '#') AND ((string)$file != 'tmp') AND ((string)$file != 'wpub')) { // {{{SYNC-NETARCH-DENIED-PATHS}}} ; `src`, `src.*` : are denied to avoid conflicts with appcodepack sources ; `tmp` may contain sensitive information so skip ; `#*` are protected path so skip
						$found_files_total++;
						if(self::check_if_safe_file_or_dir_name((string)$file)) {
							$fpath = (string) $basefolderpath.$file;
							if(self::check_if_safe_path((string)$fpath)) {
								if((self::is_type_dir((string)$fpath)) OR (self::is_type_file((string)$fpath))) { // dir or file
									if(!self::check_if_safe_path((string)$restoreroot.$file)) {
										return 'ERROR: Invalid NetArchive Restore Path: '.$restoreroot.$file;
									} //end if
									if(self::path_exists((string)$restoreroot.$file)) {
										$move_xop = self::unpack_move_file_or_dir_netarchive((string)$restoreroot.$file, (string)$the_tmp_netarch_versions_folder.$file);
										if($move_xop != 1) {
											return 'ERROR: Failed to move a File or Dir to the NetArchive Saved Versions TMP Folder ['.$move_xop.']: '.$the_tmp_netarch_versions_folder.$file;
										} //end if
									} //end if
									$move_xop = self::unpack_move_file_or_dir_netarchive((string)$fpath, (string)$restoreroot.$file);
									if($move_xop != 1) {
										return 'ERROR: Failed to restore a File or Dir from the NetArchive TMP Folder ['.$move_xop.']: '.$fpath;
									} //end if
									$found_files_ok++;
									$found_files_restored[] = (string) $file;
								} else {
									$found_files_notok[] = (string) $file;
								} //end if
							} else {
								$found_files_notok[] = (string) $file;
							} //end if
						} else {
							$found_files_notok[] = (string) $file;
						} //end if
					} //end if
				} //end for
				if(((int)$found_files_total !== (int)$found_files_ok) OR (self::array_size($found_files_notok) > 0)) {
					return 'ERROR: Invalid Files found in the Folder Path of Archive ('.((int)$found_files_total-(int)$found_files_ok).'): ['."\n".implode("\n", (array)$found_files_notok)."\n".']';
				} //end if
			} else {
				return 'ERROR: Invalid Base Folder Path of Archive (Is Empty, Have No Contents): '.$basefolderpath;
			} //end if else
			//--
			clearstatcache(true, (string)$basefolderpath);
			$arr_dir_files = scandir((string)$basefolderpath); // don't make it array, can be false
			$not_restored_files = [];
			if(self::array_size($arr_dir_files) > 0) {
				for($i=0; $i<self::array_size($arr_dir_files); $i++) {
					$file = (string) $arr_dir_files[$i];
					if(((string)trim((string)$file) != '') AND ((string)$file != '.') AND ((string)$file != '..')) { // fix ok
						$not_restored_files[] = (string) $file;
					} //end if
				} //end for
			} //end if
			//--
			if(self::array_size($not_restored_files) > 0) {
				return 'ERROR: The Base Folder Path of Archive (IS NOT EMPTY, There are some not-restored files or dirs ('.self::array_size($not_restored_files).'): ['."\n".implode("\n", (array)$not_restored_files)."\n".']';
			} //end if
			//--
			$the_log_txt = [];
			$the_log_txt[] = '##### AppCodePack/Unpack ('.APPCODEUNPACK_VERSION.') - Log (for AppID: '.(string)APPCODEPACK_APP_ID.') @ '.$the_tmp_netarch_versions_hash;
			$the_log_txt[] = '##### IP: '.trim($_SERVER['REMOTE_ADDR'].' ; '.$_SERVER['HTTP_CLIENT_IP'].' ; '.$_SERVER['HTTP_X_FORWARDED_FOR'], '; ').' @ Client-Signature: '.(string)$_SERVER['HTTP_USER_AGENT'];
			$the_log_txt[] = '### NetArchive Package: '.$the_pack_name;
			if(self::array_size($not_restored_files) > 0) {
				$the_log_txt[] = '### NOT OK: There are some Not Restored Files / Dirs ('.self::array_size($not_restored_files).'): '.'['."\n".implode("\n", (array)$not_restored_files)."\n".']';
			} else {
				$the_log_txt[] = '### *** OK: ALL FILES AND DIRS RESTORED ***';
			} //end if else
			$the_log_txt[] = '### OK: The list with Restored Base Files / Dirs: ('.self::array_size($found_files_restored).') ['."\n".implode("\n", (array)$found_files_restored)."\n".']';
			$the_log_txt[] = '## INFO: The complete list with archive Files and Dirs ('.self::array_size($the_pack_files_n_dirs).'): '.'['."\n".implode("\n", (array)$the_pack_files_n_dirs)."\n".']';
			$the_log_txt[] = "\n".'### PACKAGE:'."\n\n".$y_content."\n\n";
			$the_log_txt[] = '##### END';
			//--
			self::write((string)$the_tmp_netarch_versions_logfile, (string)implode("\n\n", (array) $the_log_txt));
			//--
			ob_start(); // prevent output from this script ...
			$chk_upgrade = (string) self::unpack_upgrade_script($restoreroot, $the_tmp_netarch_versions_logfile);
			ob_end_clean();
			if((string)$chk_upgrade != '') {
				return 'WARNING: Restored COMPLETE but the Upgrade Script returned an ERROR: '.$chk_upgrade;
			} //end if
			//--
			sleep(5);
			//--
			if(self::is_type_file((string)$restoreroot.'maintenance.html')) {
				self::delete((string)$restoreroot.'maintenance.html');
				if(self::path_exists((string)$restoreroot.'maintenance.html')) {
					self::write((string)$the_tmp_netarch_versions_logfile, "\n\n".'##### MAINTENANCE ERROR: FAILED to remove the maintenance.html ...', 'a'); // apend to log
					return 'WARNING: Restored COMPLETE but the Maintenance file could not be removed ...';
				} //end if
				self::write((string)$the_tmp_netarch_versions_logfile, "\n\n".'##### MAINTENANCE: Removing the maintenance.html ...', 'a'); // apend to log
			} //end if
			//--
		} //end if
		//--
		return '';
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function unpack_upgrade_script($restoreroot, $the_tmp_netarch_versions_logfile) {
		//--
		// ISOLATE UPGRADE SCRIPT INTO A FUNCTION
		//--
		$path_to_upgrade_script = (string) $restoreroot.'appcode-upgrade.php';
		//--
		if(!self::check_if_safe_path((string)$path_to_upgrade_script)) {
			return 'Invalid Upgrade Script Path: '.$path_to_upgrade_script;
		} //end if
		//--
		if(self::is_type_file((string)$path_to_upgrade_script)) {
			//--
			$upgr_lint_test = (string) php_strip_whitespace((string)$path_to_upgrade_script);
			//--
			if((string)$upgr_lint_test != '') {
				//--
				self::write((string)$the_tmp_netarch_versions_logfile, "\n\n".'##### UPGRADE: Running '.$path_to_upgrade_script.' ...', 'a'); // apend to log
				//--
				try {
					self::unpack_run_upgrade_script((string)$path_to_upgrade_script);
					self::write((string)$the_tmp_netarch_versions_logfile, "\n\n".'### '.$path_to_upgrade_script.' [OK]', 'a'); // apend to log
				} catch (Exception $e) {
					$the_upgr_err = (string) $e->getMessage();
					self::write((string)$the_tmp_netarch_versions_logfile, "\n\n".'### '.$path_to_upgrade_script.' [ERRORS]: '.$the_upgr_err, 'a'); // apend to log
					return 'Running the UPGRADE PHP script generated some errors: '.$the_upgr_err;
				} //end try catch
				//--
			} else {
				//--
				self::write((string)$the_tmp_netarch_versions_logfile, "\n\n".'##### UPGRADE ERROR: Failed to Run '.$path_to_upgrade_script.' (appear to have some errors) ...', 'a'); // apend to log
				//--
			} //end if else
			//--
			self::delete((string)$path_to_upgrade_script);
			//--
			if(self::path_exists((string)$path_to_upgrade_script)) {
				return 'The UPGRADE PHP script could not be removed ...';
			} //end if
			//--
			self::write((string)$the_tmp_netarch_versions_logfile, "\n\n".'##### UPGRADE: Removing the '.$path_to_upgrade_script.' ...', 'a'); // apend to log
			//--
		} //end if
		//--
		return '';
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function unpack_move_file_or_dir_netarchive($path, $newpath) {
		//--
		if((string)$path == '') {
			return -1;
		} //end if
		if((string)$newpath == '') {
			return -2;
		} //end if
		//--
		if(!self::check_if_safe_path((string)$path)) {
			return -3;
		} //end if
		if(!self::check_if_safe_path((string)$newpath)) {
			return -4;
		} //end if
		//--
		if(!self::path_exists((string)$path)) {
			return -5;
		} //end if
		if(self::path_exists((string)$newpath)) {
			return -6;
		} //end if
		//--
		if(self::is_type_link((string)$path)) { // link
			return -7; // important: don't operate on symlinks (they must not be moved or replaced) !!
		} elseif(self::is_type_dir((string)$path)) { // dir
			return self::dir_rename((string)$path, (string)$newpath);
		} elseif(self::is_type_file((string)$path)) { // file
			return self::rename((string)$path, (string)$newpath);
		} //end if else
		//--
		return -8;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// provide complete isolation of the upgrade script run (to avoid rewrite variables inside other functions)
	private static function unpack_run_upgrade_script($path_to_upgrade_script) {
		//--
		include_once((string)$path_to_upgrade_script); // don't suppress output errors !!
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function deaccent_fix_str_to_iso($str) {
		//--
		$str = (string) $str;
		if((string)trim((string)$str) == '') {
			return '';
		} //end if
		//--
		$charset_from = @mb_detect_encoding((string)$str, 'UTF-8, ISO-8859-1, ISO-8859-15, ISO-8859-2, ISO-8859-9, ISO-8859-3, ISO-8859-4, ISO-8859-5, ISO-8859-6, ISO-8859-7, ISO-8859-8, ISO-8859-10, ISO-8859-11, ISO-8859-13, ISO-8859-14, ISO-8859-16, UTF-7, ASCII, SJIS, EUC-JP, JIS, ISO-2022-JP, EUC-CN, GB18030, ISO-2022-KR, KOI8-R, KOI8-U', true);
		//--
		$str = (string) @mb_convert_encoding((string)$str, 'ISO-8859-1', (string)$charset_from);
		$str = (string) utf8_encode((string)utf8_decode((string)$str)); // ISO and unicode normalize as this environment is UTF-8
		//--
		return (string) $str;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Execute a command
	 *
	 * @param STRING 	$cmd			:: The command to run
	 *
	 * @return ARRAY 					:: The Array(exitcode, output)
	 */
	public static function exec_cmd($cmd) {
		//--
		exec((string)$cmd, $arr, $exitcode);
		//--
		return array(
			'exitcode' 	=> (int)    $exitcode,
			'output' 	=> (string) trim((string)implode("\n", (array)$arr))
		);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function is_curl_available() {
		//--
		return (bool) ((function_exists('curl_init')) AND (function_exists('curl_file_create')));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function browse_url($y_url, $y_method='GET', $y_user='', $y_pass='', $y_cookies=[], $y_postvars=[], $y_postfiles=[], $y_raw_headers=[], $y_protocol='1.0', $y_connect_timeout=30, $y_exec_timeout=0, $y_useragent='') {
		//--
		if(self::is_curl_available() !== true) {
			return -100; // PHP CURL Extension is N/A
		} //end if
		//--
		$y_url = (string) trim((string)$y_url);
		if((string)$y_url == '') {
			return -101; // empty URL
		} //end if
		//--
		$y_method = (string) strtoupper((string)trim((string)$y_method));
		if((string)$y_method == '') {
			return -102; // empty HTTP Method
		} //end if
		//--
		$y_user = (string) $y_user;
		$y_pass = (string) $y_pass;
		//--
		if(!is_array($y_postvars)) {
			$y_postvars = array();
		} //end if
		//--
		if(!is_array($y_postfiles)) {
			$y_postfiles = array();
		} //end if
		//--
		if(!is_array($y_cookies)) {
			$y_cookies = array();
		} //end if
		//--
		if(!is_array($y_raw_headers)) {
			$y_raw_headers = [];
		} //end if
		//--
		switch((string)$y_protocol) {
			case '1.1':
				$y_protocol = '1.1'; // for 1.1 the time can be significant LONGER than 1.0
				break;
			case '1.0':
			default:
				$y_protocol = '1.0'; // default is 1.0
		} //end switch
		//--
		$y_connect_timeout = (int) $y_connect_timeout;
		if((int)$y_connect_timeout < 1) {
			$y_connect_timeout = 1;
		} elseif((int)$y_connect_timeout > 60) {
			$y_connect_timeout = 60;
		} //end if
		//--
		$y_exec_timeout = (int) $y_exec_timeout;
		if((int)$y_exec_timeout > 0) {
			if((int)$y_exec_timeout < 30) {
				$y_exec_timeout = 30;
			} elseif((int)$y_exec_timeout > 600) {
				$y_exec_timeout = 600;
			} //end if
		} else {
			$y_exec_timeout = 0;
		} //end if else
		//--
		if((int)$y_exec_timeout < 0) {
			$y_exec_timeout = 0;
		} elseif((int)$y_exec_timeout > 600) {
			$y_exec_timeout = 600;
		} //end if else
		//--
		$y_useragent = (string) trim((string)$y_useragent);
		if((string)$y_useragent == '') {
			$y_useragent = 'Mozilla/5.0 PHP.CURL.SF.AppCodePack ('.APPCODEUNPACK_VERSION.'/'.php_uname().')';
		} //end if
		//--
		$ssl_ciphers = 'HIGH';
		//--
		$curl = @curl_init();  // Initialise a cURL handle
		if(!$curl) {
			return -103; // cannot initialize CURL
		} //end if
		//--
		@curl_setopt($curl, CURLOPT_USERAGENT, (string)$y_useragent);
		//--
		if((string)$y_protocol == '1.1') {
			@curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		} else {
			@curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
		} //end if else
		//--
		@curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
		@curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, (int)$y_connect_timeout);
		@curl_setopt($curl, CURLOPT_TIMEOUT, (int)$y_exec_timeout);
		//--
		@curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_DEFAULT); // CURL_SSLVERSION_TLSv1
		@curl_setopt($curl, CURLOPT_SSL_CIPHER_LIST, (string)$ssl_ciphers);
		@curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		@curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		//--
		if(((string)$y_user != '') AND ((string)$y_pass != '')) {
			@curl_setopt($curl, CURLOPT_USERPWD, (string)$y_user.':'.$y_pass);
		} //end if
		//--
		@curl_setopt($curl, CURLOPT_ENCODING, (string)SMART_FRAMEWORK_CHARSET);
		@curl_setopt($curl, CURLOPT_HEADER, true);
		@curl_setopt($curl, CURLOPT_COOKIESESSION, true);
		@curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		@curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
		@curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		//--
		$have_cookies = false;
		if(self::array_size($y_cookies) > 0) {
			$send_cookies = '';
			foreach($y_cookies as $key => $value) {
				if((string)$key != '') {
					if((string)$value != '') {
						$send_cookies .= (string) self::encode_var_cookie($key, $value);
					} //end if
				} //end if
			} //end foreach
			if((string)$send_cookies != '') {
				$have_cookies = true;
				$y_raw_headers[] = (string) 'Cookie: '.$send_cookies;
			} //end if
			$send_cookies = '';
		} //end if
		//--
		$post_vars = '';
		if((string)$y_method == 'POST') {
			//--
			if(self::array_size($y_postfiles) > 0) {
				$delimiter = (string) self::http_multipart_form_delimiter();
				$post_vars = (string) self::http_multipart_form_build((string)$delimiter, (array)$y_postvars, (array)$y_postfiles);
				$y_raw_headers[] = 'Content-Type: multipart/form-data; boundary='.$delimiter;
				$y_raw_headers[] = 'Content-Length: '.(int)strlen($post_vars);
			} elseif(self::array_size($y_postvars) > 0) {
				$post_vars = '';
				foreach($y_postvars as $key => $value) {
					$post_vars .= (string) self::encode_var_post($key, $value);
				} //end foreach
			} //end if
			//--
			if((string)$post_vars == '') { // if have post vars force POST if GET
				$y_method = 'GET';
			} //end if
			//--
		} //end if
		//--
		switch((string)$y_method) {
			case 'HEAD':
			case 'GET':
				break;
			case 'POST':
				@curl_setopt($curl, CURLOPT_POSTFIELDS, (string)$post_vars);
				@curl_setopt($curl, CURLOPT_POST, true);
				break;
			case 'PUT':
			case 'DELETE':
			default:
				@curl_setopt($curl, CURLOPT_CUSTOMREQUEST, (string)$y_method);
		} //end switch
		//--
		if(self::array_size($y_raw_headers) > 0) { // request headers are constructed above
			@curl_setopt($curl, CURLOPT_HTTPHEADER, (array)$y_raw_headers);
		} //end if
		//--
		@curl_setopt($curl, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
		@curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
		@curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
		//--
		@curl_setopt($curl, CURLOPT_URL, (string)$y_url);
		if(!$curl) {
			return -104; // Aborted before Execution
		} //end if
		//--
		$results = @curl_exec($curl);
		$error = @curl_errno($curl);
		$ermsg = @curl_error($curl);
		//-- eval results
		$is_unauth = false;
		$bw_info = array();
		$is_ok = 0;
		$header = '';
		$body = '';
		$status = 999; // pre-set to something that does not exists
		//--
		if($results) {
			//--
			$is_ok = 1;
			//--
			$bw_info = (array) array_change_key_case((array)@curl_getinfo($curl), CASE_LOWER);
			//--
			$hd_len = (int) $bw_info['header_size']; // get header length
			//--
			if($hd_len > 0) {
				//--
				$header = (string) substr((string)$results, 0, $hd_len);
				$body = (string) substr((string)$results, $hd_len);
				//--
			} else {
				//--
				$header = (string) $results;
				$body = '';
				//--
				$is_ok = 0;
				//--
			} //end if else
			//--
			$results = ''; // free memory
			//--
			if((string)$bw_info['http_code'] == '401') {
				//--
				$is_unauth = true;
				//--
			} //end if
			//--
			if($error) {
				//--
				$is_ok = 0;
				//--
			} //end if
			//--
			$status = (int) $bw_info['http_code'];
			//--
		} else {
			//--
			$is_ok = 0;
			//--
		} //end if
		//--
		if($is_unauth) {
			//--
			$is_ok = 0;
			//--
		} //end if
		//--
		return array(
			'status' 		=> (int)    $is_ok,
			'errno' 		=> (int)    $error,
			'ermsg' 		=> (string) $ermsg,
			'http-status' 	=> (int)    $status,
			'http-header' 	=> (string) $header,
			'http-body' 	=> (string) $body
		);
		//--
	} //END FUNCTION
	//================================================================


	//##### Smart v.20210302


	//================================================================
	/**
	 * Convert a List String to Array
	 *
	 * @param STRING 	$y_list			:: The List String to be converted: '<elem1>, <elem2>, ..., <elemN>'
	 * @param BOOLEAN 	$y_trim 		:: *Optional* default is TRUE ; If set to FALSE will not trim the values in the list
	 *
	 * @return ARRAY 					:: The Array: Array(elem1, elem2, ..., elemN)
	 */
	public static function list_to_array($y_list, $y_trim=true) {
		//--
		if((string)trim((string)$y_list) == '') {
			return array(); // empty list
		} //end if
		//--
		$y_list = (string) trim((string)$y_list);
		//--
		$arr = (array) explode(',', (string)$y_list);
		$new_arr = array();
		for($i=0; $i<self::array_size($arr); $i++) {
			$arr[$i] = (string) str_replace(['<', '>'], ['', ''], (string)$arr[$i]);
			if($y_trim !== false) {
				$arr[$i] = (string) trim((string)$arr[$i]);
			} //end if
			if((string)$arr[$i] != '') {
				if(!in_array((string)$arr[$i], $new_arr)) {
					$new_arr[] = (string) $arr[$i];
				} //end if
			} //end if
		} //end for
		//--
		return (array) $new_arr;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Safe array count(), for safety, with array type check ; this should be used instead of count() because count(string) returns a non-zero value and can confuse if a string is passed to count instead of an array
	 *
	 * @param ARRAY 		$y_arr			:: The array to count elements on
	 *
	 * @return INTEGER 						:: The array COUNT of elements, or zero if array is empty or non-array is provided
	 */
	public static function array_size($y_arr) { // !!! DO NOT FORCE ARRAY TYPE ON METHOD PARAMETER AS IT HAVE TO TEST ALSO NON-ARRAY VARS !!!
		//--
		if(is_array($y_arr)) {
			return count($y_arr);
		} else {
			return 0;
		} //end if else
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Test if the Array Type for being associative or non-associative sequential (0..n)
	 *
	 * @param ARRAY 		$y_arr			:: The array to test
	 *
	 * @return ENUM 						:: The array type as: 0 = not an array ; 1 = non-associative (sequential) array or empty array ; 2 = associative array or non-sequential, must be non-empty
	 */
	public static function array_type_test($y_arr) { // !!! DO NOT FORCE ARRAY TYPE ON METHOD PARAMETER AS IT HAVE TO TEST ALSO NON-ARRAY VARS !!!
		//--
		if(!is_array($y_arr)) {
			return 0; // not an array
		} //end if
		//--
	//	$c = (int) count($y_arr);
	//	if(((int)$c <= 0) OR ((array)array_keys($y_arr) === (array)range(0, ((int)$c - 1)))) { // most elegant, but slow
		//--
	//	$a = (array) array_keys((array)$y_arr);
	//	if((array)$a === (array)array_keys((array)$a)) { // memory-optimized (prev OK)
		//--
		if((array)array_values((array)$y_arr) === (array)$y_arr) { // speed-optimized, 10x faster with non-associative large arrays, tested in all scenarios with large or small arrays
			return 1; // non-associative
		} else {
			return 2; // associative
		} //end if else
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Format a number as INTEGER (NOTICE: On 64-bit systems PHP_INT_MAX is: 9223372036854775807 ; On 32-bit old systems the PHP_INT_MAX is just 2147483647)
	 *
	 * @param 	NUMERIC 	$y_number		:: A numeric value
	 * @param 	ENUM		$y_signed		:: Default to '' ; If set to '+' will return (enforce) an UNSIGNED/POSITIVE Integer, Otherwise if set to '' will return just a regular SIGNED INTEGER wich can be negative or positive
	 *
	 * @return 	INTEGER						:: An integer number
	 */
	public static function format_number_int($y_number, $y_signed='') {
		//--
		if((string)$y_signed == '+') { // unsigned integer
			if((int)$y_number < 0) { // {{{SYNC-SMART-INT+}}}
				$y_number = 0; // it must be zero if negative for the all logic in this framework
			} //end if
		} //end if
		//--
		return (int) $y_number;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Format a number as DECIMAL (NOTICE: The maximum PHP.INI precision is 14, includding decimals).
	 * This is a better replacement for the PHP's number_format() which throws a warning if first argument passed is a string since PHP 5.3
	 *
	 * @param 	NUMERIC 	$y_number			:: A numeric value
	 * @param 	INTEGER+	$y_decimals			:: The number of decimal to use (safe value is between 0..8, keeping in mind the 14 max precision)
	 * @param 	STRING		$y_sep_decimals 	:: The decimal separator symbol as: 	. or , (default is .)
	 * @param 	STRING 		$y_sep_thousands	:: The thousand separator symbol as: 	, or . (default is [none])
	 *
	 * @return 	DECIMAL							:: A decimal number
	 */
	public static function format_number_dec($y_number, $y_decimals=0, $y_sep_decimals='.', $y_sep_thousands='') {
		//-- by default number_format() returns string, so enforce string as output to keep decimals
		return (string) number_format(((float)$y_number), self::format_number_int($y_decimals,'+'), (string)$y_sep_decimals, (string)$y_sep_thousands); // {{{SYNC-SMART-DECIMAL}}}
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Fix for Directory Separator if on Windows
	 *
	 * @param 	STRING 	$y_path 			:: The path name to fix
	 *
	 * @return 	STRING						:: The fixed path name
	 */
	public static function fix_path_separator($y_path) {
		//--
		if((string)DIRECTORY_SEPARATOR == '\\') { // if on Windows, Fix Path Separator !!!
			if(strpos((string)$y_path, '\\') !== false) {
				$y_path = (string) str_replace((string)DIRECTORY_SEPARATOR, '/', (string)$y_path); // convert \ to / on paths
			} //end if
		} //end if
		//--
		return (string) $y_path;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Return the FIXED realpath(), also with fix on Windows
	 *
	 * @param 	STRING 	$y_path 			:: The path name from to extract realpath()
	 *
	 * @return 	STRING						:: The real path
	 */
	public static function real_path($y_path) {
		//--
		$y_path = (string) $y_path; // do not TRIM !!
		//--
		$the_path = (string) @realpath((string)$y_path);
		//--
		return (string) self::fix_path_separator($the_path); // FIX: on Windows, is possible to return a backslash \ instead of slash /
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Return the FIXED dirname(), also with fix on Windows
	 *
	 * @param 	STRING 	$y_path 			:: The path name from to extract dirname()
	 *
	 * @return 	STRING						:: The dirname or . or empty string
	 */
	public static function dir_name($y_path) {
		//--
		$y_path = (string) $y_path; // do not TRIM !!
		//--
		$dir_name = (string) dirname((string)$y_path);
		//--
		return (string) self::fix_path_separator($dir_name); // FIX: on Windows, is possible to return a backslash \ instead of slash /
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Return the FIXED basename(), in a safe way
	 * The directory separator character is the forward slash (/), except Windows where both slash (/) and backslash (\) are considered
	 *
	 * @param 	STRING 	$y_path 			:: The path name from to extract basename()
	 * @param 	STRING 	$y_suffix 			:: If the name component ends in suffix this will also be cut off
	 *
	 * @return 	STRING						:: The basename
	 */
	public static function base_name($y_path, $y_suffix='') {
		//--
		$y_path = (string) $y_path; // do not TRIM !!
		$y_suffix = (string) $y_suffix; // do not TRIM !!
		//--
		if((string)$y_suffix != '') {
			$base_name = (string) basename($y_path, $y_suffix);
		} else {
			$base_name = (string) basename($y_path);
		} //end if else
		//--
		return (string) $base_name;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Returns the Regex Expr. with the lower unsafe characters
	 *
	 * @access 		private
	 * @internal
	 *
	 * @return STRING 						:: A regex expression
	 *
	 */
	public static function lower_unsafe_characters() {
		//--
		return '/[\x00-\x08\x0B-\x0C\x0E-\x1F]/'; // all lower dangerous characters: x00 - x1F except: \t = x09 \n = 0A \r = 0D
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Normalize Spaces
	 * This will replace: "\r", "\n", "\t", "\x0B", "\0", "\f" with normal space ' '
	 *
	 * @param STRING 		$y_txt			:: Text to be normalized
	 *
	 * @return STRING 						:: The normalized text
	 */
	public static function normalize_spaces($y_txt) {
		//--
		return (string) str_replace(["\r\n", "\r", "\n", "\t", "\x0B", "\0", "\f"], ' ', (string)$y_txt);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Test and Fix if unsafe detected for: safe path / safe filename / safe valid name / safe username / safe varname
	 * This is intended to be used against the result of above functions to avoid generate an unsafe file system path (ex: . or .. or / or /. or /..)
	 * Because all the above functions may return an empty (string) result, if unsafe sequences are detected will just fix it by clear the result (enforce empty string is better than unsafe)
	 * It should allow also both: absolute and relative paths, thus if absolute path should be tested later
	 *
	 * @access 		private
	 * @internal
	 *
	 * @return STRING 						:: The fixed (filesys) safe string
	 *
	 */
	public static function safe_fix_invalid_filesys_names($y_fsname) {
		//-- v.190105
		$y_fsname = (string) trim((string)$y_fsname);
		//-- {{{SYNC-SAFE-PATH-CHARS}}} {{{SYNC-CHK-SAFE-PATH}}}
		if(
			((string)$y_fsname == '.') OR
			((string)$y_fsname == '..') OR
			((string)$y_fsname == ':') OR
			((string)$y_fsname == '/') OR
			((string)$y_fsname == '/.') OR
			((string)$y_fsname == '/..') OR
			((string)$y_fsname == '/:') OR
			((string)ltrim((string)$y_fsname, '/') == '.') OR
			((string)ltrim((string)$y_fsname, '/') == '..') OR
			((string)ltrim((string)$y_fsname, '/') == ':') OR
			((string)trim((string)$y_fsname, '/') == '') OR
			((string)substr((string)$y_fsname, -2, 2) == '/.') OR
			((string)substr((string)$y_fsname, -3, 3) == '/..')
		) {
			$y_fsname = '';
		} //end if
		//--
		return (string) $y_fsname; // returns trimmed value or empty if non-safe
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Create a Safe Path Name to be used to process dynamic build paths to avoid weird path character injections
	 * This should be used for relative or absolute path to files or dirs
	 * It should allow also both: absolute and relative paths, thus if absolute path should be tested later
	 * NOTICE: It may return an empty string if all characters in the path are invalid or invalid path sequences detected, so if empty path name must be tested later
	 * ALLOWED CHARS: [a-zA-Z0-9] _ - . @ # /
	 *
	 * @param STRING 		$y_path			:: Path to be processed
	 * @param STRING 		$ysupresschar	:: The suppression character to replace weird characters ; optional ; default is ''
	 *
	 * @return STRING 						:: The safe path ; if invalid will return empty value
	 */
	public static function safe_pathname($y_path, $ysupresschar='') {
		//--
		// !!! MODIFIED !!! to avoid depend on SmartUnicode
		//-- v.170920
		$y_path = (string) trim((string)$y_path); // force string and trim
		if((string)$y_path == '') {
			return '';
		} //end if
		//--
		if(preg_match('/^[_a-zA-Z0-9\-\.@#\/]+$/', (string)$y_path)) { // {{{SYNC-CHK-SAFE-PATH}}}
			return (string) self::safe_fix_invalid_filesys_names($y_path);
		} //end if
		//--
		$ysupresschar = (string) $ysupresschar; // force string and be sure is lower
		switch((string)$ysupresschar) {
			case '-':
			case '_':
				break;
			default:
				$ysupresschar = '';
		} //end if
		//--
		$y_path = (string) preg_replace((string)self::lower_unsafe_characters(), '', (string)$y_path); // remove dangerous characters
		// (modif:n/a) $y_path = (string) SmartUnicode::utf8_to_iso($y_path); // bring STRING to ISO-8859-1
		$y_path = (string) self::deaccent_fix_str_to_iso($y_path); // deaccent + bring STRING to ISO-8859-1
		$y_path = (string) stripslashes($y_path); // remove any possible back-slashes
		$y_path = (string) self::normalize_spaces($y_path); // normalize spaces to catch null seq.
		//$y_path = (string) str_replace('?', $ysupresschar, $y_path); // replace questionmark (that may come from utf8 decode) ; this is already done below
		$y_path = (string) preg_replace('/[^_a-zA-Z0-9\-\.@#\/]/', $ysupresschar, $y_path); // {{{SYNC-SAFE-PATH-CHARS}}} suppress any other characters than these, no unicode modifier
		$y_path = (string) preg_replace("/(\.)\\1+/", '.', $y_path); // suppress multiple . dots and replace with single dot
		$y_path = (string) preg_replace("/(\/)\\1+/", '/', $y_path); // suppress multiple // slashes and replace with single slash
		$y_path = (string) str_replace(array('../', './'), array('-', '-'), $y_path); // replace any unsafe path combinations (do not suppress but replace with a fixed character to avoid create security breaches)
		$y_path = (string) trim($y_path); // finally trim it
		//--
		return (string) self::safe_fix_invalid_filesys_names($y_path);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Create a Safe File Name to be used to process dynamic build file names or dir names to avoid weird path character injections
	 * To should be used only for file or dir names (not paths)
	 * NOTICE: DO NOT USE for full paths or full dir paths because will break them, as the / character is supressed
	 * NOTICE: It may return an empty string if all characters in the file/dir name are invalid or invalid path sequences detected, so if empty file/dir name must be tested later
	 * ALLOWED CHARS: [a-zA-Z0-9] _ - . @ #
	 *
	 * @param STRING 		$y_fname		:: File Name or Dir Name to be processed
	 * @param STRING 		$ysupresschar	:: The suppression character to replace weird characters ; optional ; default is ''
	 *
	 * @return STRING 						:: The safe file or dir name ; if invalid will return empty value
	 */
	public static function safe_filename($y_fname, $ysupresschar='') {
		//-- v.170920
		$y_fname = (string) trim((string)$y_fname); // force string and trim
		if((string)$y_fname == '') {
			return '';
		} //end if
		//--
		if(preg_match('/^[_a-zA-Z0-9\-\.@#]+$/', (string)$y_fname)) { // {{{SYNC-CHK-SAFE-FILENAME}}}
			return (string) self::safe_fix_invalid_filesys_names($y_fname);
		} //end if
		//--
		$ysupresschar = (string) $ysupresschar; // force string and be sure is lower
		switch((string)$ysupresschar) { // DO NOT ALLOW DOT . AS IS SECURITY RISK, replaced below
			case '-':
			case '_':
				break;
			default:
				$ysupresschar = '';
		} //end if
		//--
		$y_fname = (string) self::safe_pathname($y_fname, $ysupresschar);
		$y_fname = (string) str_replace('/', '-', $y_fname); // replace the path character with a fixed character (do not suppress to avoid create security breaches)
		$y_fname = (string) trim($y_fname); // finally trim it
		//--
		return (string) self::safe_fix_invalid_filesys_names($y_fname);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Create a (RFC, ISO) Safe compliant User Name, Domain Name or Email Address
	 * NOTICE: It may return an empty string if all characters in the given name are invalid or invalid path sequences detected, so if empty name must be tested later
	 * ALLOWED CHARS: [a-z0-9] _ - . @
	 *
	 * @param STRING 		$y_name			:: Name to be processed
	 * @param STRING 		$ysupresschar	:: The suppression character to replace weird characters ; optional ; default is ''
	 *
	 * @return STRING 						:: The safe name ; if invalid should return empty value
	 */
	public static function safe_validname($y_name, $ysupresschar='') {
		//-- v.170920
		$y_name = (string) trim((string)$y_name); // force string and trim
		if((string)$y_name == '') {
			return '';
		} //end if
		//--
		if(preg_match('/^[_a-z0-9\-\.@]+$/', (string)$y_name)) {
			return (string) self::safe_fix_invalid_filesys_names($y_name);
		} //end if
		//--
		$ysupresschar = (string) $ysupresschar; // force string and be sure is lower
		switch((string)$ysupresschar) {
			case '-':
			case '_':
				break;
			default:
				$ysupresschar = '';
		} //end if
		//--
		$y_name = (string) self::safe_filename($y_name, $ysupresschar);
		$y_name = (string) strtolower($y_name); // make all lower chars
		$y_name = (string) str_replace('#', '', $y_name); // replace also diez
		$y_name = (string) trim($y_name);
		//--
		return (string) self::safe_fix_invalid_filesys_names($y_name);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Creates a Safe Valid Variable Name
	 * NOTICE: this have a special usage and must allow also 0..9 as prefix because is can be used for other purposes not just for real safe variable names, thus if real safe valid variable name must be tested later (real safe variable names cannot start with numbers ...)
	 * NOTICE: It may return an empty string if all characters in the given variable name are invalid or invalid path sequences detected, so if empty variable name must be tested later
	 * ALLOWED CHARS: [a-zA-Z0-9] _
	 *
	 * @param STRING 		$y_name				:: Variable Name to be processed
	 * @param BOOL 			$y_allow_upper 		:: Allow UpperCase ; *Optional* ; Default is TRUE
	 *
	 * @return STRING 							:: The safe variable name ; if invalid should return empty value
	 */
	public static function safe_varname($y_name, $y_allow_upper=true) {
		//-- v.20210302
		$y_name = (string) trim((string)$y_name); // force string and trim
		if((string)$y_name == '') {
			return '';
		} //end if
		//--
		if($y_allow_upper === false) {
			$y_name = (string) strtolower((string)$y_name);
		} //end if
		//--
		if(preg_match('/^[_a-zA-Z0-9]+$/', (string)$y_name)) {
			return (string) self::safe_fix_invalid_filesys_names($y_name);
		} //end if
		//--
		$y_name = (string) self::safe_filename($y_name, '-');
		$y_name = (string) str_replace(array('-', '.', '@', '#'), '', $y_name); // replace the invalid - . @ #
		$y_name = (string) trim($y_name);
		//--
		return (string) self::safe_fix_invalid_filesys_names($y_name);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Safe escape strings to be injected in HTML code
	 * This is a shortcut to the htmlspecialchars() to avoid use long options each time and provide a standard into Smart.Framework
	 *
	 * @param 	STRING 		$y_string			:: The string to be escaped
	 *
	 * @return 	STRING							:: The escaped string using htmlspecialchars() standards with Unicode-Safe control
	 */
	public static function escape_html($y_string) {
		//-- v.181203
		// Default is: ENT_HTML401 | ENT_COMPAT
		// keep the ENT_HTML401 instead of ENT_HTML5 to avoid troubles with misc. HTML Parsers (robots, htmldoc, ...)
		// keep the ENT_COMPAT (replace only < > ") and not replace '
		// add ENT_SUBSTITUTE to avoid discard the entire invalid string (with UTF-8 charset) but substitute dissalowed characters with ?
		// enforce 4th param as TRUE as default (double encode)
		//--
		return (string) htmlspecialchars((string)$y_string, ENT_HTML401 | ENT_COMPAT | ENT_SUBSTITUTE, (string)SMART_FRAMEWORK_CHARSET, true); // use charset from INIT (to prevent XSS attacks) ; the 4th parameter double_encode is set to TRUE as default
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * A quick replacement for trigger_error() / E_USER_WARNING.
	 * This is intended to log APP Level Warnings.
	 * This will log the message as WARNING into the App Error Log.
	 *
	 * @param STRING 	$message		:: The message to be triggered
	 *
	 * @return -						:: This function does not return anything
	 */
	public static function log_warning($message) {
		//--
		@trigger_error('#APP.WARNING# '.$message, E_USER_WARNING);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * A quick replacement for trigger_error() / E_USER_ERROR.
	 * This is intended to log APP Level Errors.
	 * This will log the message as ERROR into the App Error Log and stop the execution (also in the Smart Error Handler will raise a HTTP 500 Code).
	 *
	 * @param STRING 	$message_to_log			:: The message to be triggered
	 * @param STRING 	$message_to_display 	:: *Optional* the message to be displayed (must be html special chars safe)
	 *
	 * @return -								:: This function does not return anything
	 */
	public static function raise_error($message_to_log, $message_to_display='') {
		//--
		global $smart_____framework_____last__error; // presume it is already html special chars safe
		//--
		if((string)trim((string)$message_to_display) == '') {
			$message_to_display = 'See Error Log for More Details'; // avoid empty message to display
		} //end if
		$smart_____framework_____last__error = (string) $message_to_display;
		@trigger_error('#SMART-FRAMEWORK.ERROR# '.$message_to_log, E_USER_ERROR);
		die('App Level Raise ERROR. Execution Halted. '.$message_to_display); // normally this line will never be executed because the E_USER_ERROR via Smart Error Handler will die() before ... but this is just in case, as this is a fatal error and the execution should be halted here !
		//--
	} //END FUNCTION
	//================================================================


	//##### SmartHashCrypto v.20200424


	//==============================================================
	/**
	 * Returns the SHA512 hash of a string
	 *
	 * @param STRING $y_str
	 * @return STRING, 128 chars length
	 */
	public static function sha512($y_str) {
		//--
		if(!self::algo_check('sha512')) {
			self::raise_error('ERROR: Crypto Hash requires SHA512 Hash/Algo', 'SHA512 Hash/Algo is missing');
			return '';
		} //end if
		//--
		return (string) hash('sha512', (string)$y_str, false); // execution cost: 0.35
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the SHA384 hash of a string
	 *
	 * @param STRING $y_str
	 * @return STRING, 96 chars length
	 */
	public static function sha384($y_str) {
		//--
		if(!self::algo_check('sha384')) {
			self::raise_error('ERROR: Crypto Hash requires SHA384 Hash/Algo', 'SHA384 Hash/Algo is missing');
			return '';
		} //end if
		//--
		return (string) hash('sha384', (string)$y_str, false); // execution cost: 0.34
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the SHA256 hash of a string
	 *
	 * @param STRING $y_str
	 * @return STRING, 64 chars length
	 */
	public static function sha256($y_str) {
		//--
		if(!self::algo_check('sha256')) {
			self::raise_error('ERROR: Crypto Hash requires SHA256 Hash/Algo', 'SHA256 Hash/Algo is missing');
			return '';
		} //end if
		//--
		return (string) hash('sha256', (string)$y_str, false); // execution cost: 0.21
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Returns the CRC32B hash of a string in base16 by default or base36 optional (better than CRC32, portable between 32-bit and 64-bit platforms, unsigned)
	 *
	 * @param STRING $y_str
	 * @param BOOLEAN $y_base36
	 * @return STRING, 8 chars length
	 */
	public static function crc32b($y_str, $y_base36=false) {
		//--
		if(!self::algo_check('sha512')) {
			self::raise_error('ERROR: Smart.Framework Crypto Hash requires CRC32B Hash/Algo', 'CRC32B Hash/Algo is missing');
			return '';
		} //end if
		//--
		$hash = (string) hash('crc32b', (string)$y_str, false); // execution cost: 0.21
		if($y_base36 === true) {
			$hash = (string) base_convert((string)$hash, 16, 36);
		} //end if
		//--
		return (string) $hash;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	private static function algo_check($y_algo) {
		//--
		if(in_array($y_algo, (array)self::algos())) {
			$out = 1;
		} else {
			$out = 0;
		} //end if else
		//--
		return $out;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	private static function algos() {
		//--
		if(!is_array(self::$cache['algos'])) {
			self::$cache['algos'] = (array) hash_algos();
		} //end if else
		//--
		return (array) self::$cache['algos'];
		//--
	} //END FUNCTION
	//==============================================================


	//##### SmartHttpUtils v.20200121


	//==============================================
	// encode a COOKIE variable ; returns the HTTP Cookie string
	public static function encode_var_cookie($name, $value) {
		//--
		$name = (string) self::safe_varname($name);
		//--
		if((string)$name == '') {
			return '';
		} //end if
		//--
		return (string) $name.'='.rawurlencode($value).';';
		//--
	} //END FUNCTION
	//==============================================


	//==============================================
	// encode a POST variable ; returns the HTTP POST String
	public static function encode_var_post($varname, $value) {
		//--
		$varname = (string) self::safe_varname($varname);
		//--
		if((string)$varname == '') {
			return '';
		} //end if
		//--
		$out = '';
		//--
		if(is_array($value)) {
			$arrtype = self::array_type_test($value); // 0: not an array ; 1: non-associative ; 2:associative
			if($arrtype === 1) { // 1: non-associative
				for($i=0; $i<self::array_size($value); $i++) {
					$out .= (string) $varname.'[]='.rawurlencode($value[$i]).'&';
				} //end foreach
			} else { // 2: associative
				foreach($value as $key => $val) {
					$out .= (string) $varname.'['.rawurlencode($key).']='.rawurlencode($val).'&';
				} //end foreach
			} //end if else
		} else {
			$out = (string) $varname.'='.rawurlencode($value).'&';
		} //end if else
		//--
		return (string) $out;
		//--
	} //END FUNCTION
	//==============================================


	//================================================================
	public static function http_multipart_form_delimiter() { // {{{SYNC-MULTIPART-BUILD}}}
		//--
		$timeduid = (string) strtolower((string)self::crc32b(microtime(true).'-'.time(), true));
		$entropy = (string) self::sha512(uniqid().'-'.microtime(true).'-'.time());
		//--
		return '_===-MForm.Part____.'.$timeduid.'_'.md5('@MFormPart---#Boundary@'.$entropy).'_P_.-=_'; // 69 chars of 70 max
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function http_multipart_form_build($delimiter, $fields, $files) { // {{{SYNC-MULTIPART-BUILD}}}
		//--
		$delimiter = (string) $delimiter;
		if((strlen($delimiter) < 50) OR (strlen($delimiter) > 70)) {
			return '';
		} //end if
		//--
		if(!is_array($fields)) {
			$fields = array();
		} //end if
		//--
		if(!is_array($files)) {
			$files = array();
		} //end if
		//--
		if((self::array_size($fields) <= 0) AND (self::array_size($files) <= 0)) {
			return '';
		} //end if
		//--
		$data = '';
		//--
		foreach((array)$fields as $name => $content) {
			//--
			if(is_array($content)) {
				//--
				foreach($content as $key => $val) {
					//--
					$data .= '--'.$delimiter."\r\n";
					//--
					$data .= 'Content-Disposition: form-data; name="'.self::safe_varname($name).'['.str_replace('"', '\\"', (string)$key).']'.'"'."\r\n";
					$data .= 'Content-Type: text/plain; charset=UTF-8'."\r\n";
					$data .= 'Content-Length: '.(int)(strlen((string)$val))."\r\n";
					//--
					$data .= "\r\n".$val."\r\n";
					//--
				} //end foreach
				//--
			} else {
				//--
				$data .= '--'.$delimiter."\r\n";
				//--
				$data .= 'Content-Disposition: form-data; name="'.self::safe_varname($name).'"'."\r\n";
				$data .= 'Content-Type: text/plain; charset=UTF-8'."\r\n";
				$data .= 'Content-Length: '.(int)(strlen((string)$content))."\r\n";
				//--
				$data .= "\r\n".$content."\r\n";
				//--
			} //end if else
			//--
		} //end foreach
		//--
		foreach((array)$files as $var_name => $arr_file) {
			//--
			if(self::array_size($arr_file) > 0) {
				//--
				$filename = (string) $arr_file['filename'];
				$content  = (string) $arr_file['content'];
				//--
				if($filename AND $content) {
					//--
					$data .= '--'.$delimiter."\r\n";
					//--
					$data .= 'Content-Disposition: form-data; name="'.self::safe_varname($var_name).'"; filename="'.self::safe_filename($filename).'"'."\r\n";
					$data .= 'Content-Transfer-Encoding: binary'."\r\n";
					$data .= 'Content-Length: '.(int)strlen((string)$content)."\r\n";
					$data .= "\r\n".$content."\r\n";
					//--
				} //end if
				//--
			} //end if
			//--
		} //end foreach
		//--
		$data .= '--'.$delimiter.'--'."\r\n";
		//--
		return (string) $data;
		//--
	} //END FUNCTION
	//================================================================


	//##### SmartUtils v.20200424


	//================================================================
	public static function pretty_print_bytes($y_bytes, $y_decimals=1, $y_separator=' ', $y_base=1000) {
		//--
		$y_decimals = (int) $y_decimals;
		if($y_decimals < 0) {
			$y_decimals = 0;
		} //end if
		if($y_decimals > 4) {
			$y_decimals = 4;
		} //end if
		//--
		if((int)$y_base === 1024) {
			$y_base = (int) 1024;
		} else {
			$y_base = (int) 1000;
		} //end if else
		//--
		if(!is_int($y_bytes)) {
			return (string) $y_bytes;
		} //end if
		//--
		if($y_bytes < $y_base) {
			return (string) self::format_number_int($y_bytes).$y_separator.'bytes';
		} //end if
		//--
		$y_bytes = $y_bytes / $y_base;
		if($y_bytes < $y_base) {
			return (string) self::format_number_dec($y_bytes, $y_decimals, '.', '').$y_separator.'KB';
		} //end if
		//--
		$y_bytes = $y_bytes / $y_base;
		if($y_bytes < $y_base) {
			return (string) self::format_number_dec($y_bytes, $y_decimals, '.', '').$y_separator.'MB';
		} //end if
		//--
		$y_bytes = $y_bytes / $y_base;
		if($y_bytes < $y_base) {
			return (string) self::format_number_dec($y_bytes, $y_decimals, '.', '').$y_separator.'GB';
		} //end if
		//--
		$y_bytes = $y_bytes / $y_base;
		//--
		return (string) self::format_number_dec($y_bytes, $y_decimals, '.', '').$y_separator.'TB';
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Function: Run Proc Cmd
	 * This method is using the proc_open() which provides a much greater degree of control over the program execution
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param $cmd 		STRING 			:: the command to run ; must be escaped using escapeshellcmd() and arguments using escapeshellarg()
	 * @param $inargs 	ARRAY / NULL 	:: *Optional*, Default NULL ; the array containing the input for the STDIN
	 * @param $cwd 		STRING / NULL 	:: *Optional*, Default 'tmp/cache/run-proc-cmd' ; Use NULL to use the the working dir of the current PHP process (not recommended) ; A path for a directory to run the process in ; If not null, if path does not exists will be created
	 * @param $env 		ARRAY / NULL 	:: *Optional*, default $env ; the array with environment variables ; If NULL will use the same environment as the current PHP process
	 *
	 * @return ARRAY					:: [ stdout, stderr, exitcode ]
	 *
	 */
	public static function run_proc_cmd($cmd, $inargs=null, $cwd='tmp/cache/run-proc-cmd', $env=null) {

		//-- initialize
		$descriptorspec = [
			0 => [ 'pipe', 'r' ], // stdin
			1 => [ 'pipe', 'w' ], // stdout
			2 => [ 'pipe', 'w' ]  // stderr
		];
		//--
		$output = array();
		$rderr = false;
		$pipes = array();
		//--

		//--
		$outarr = [
			'stdout' 	=> '',
			'stderr' 	=> '',
			'exitcode' 	=> -999
		];
		//--

		//-- checks
		if((int)strlen((string)$cmd) > (int)PHP_MAXPATHLEN) {
			self::log_warning(__METHOD__.' # The CMD Path is too long: '.$cmd);
			$outarr['exitcode'] = -799;
			return (array) $outarr;
		} //end if
		//--
		if((int)strlen((string)$cwd) > (int)PHP_MAXPATHLEN) {
			self::log_warning(__METHOD__.' # The CWD Path is too long: '.$cwd);
			$outarr['exitcode'] = -798;
			return (array) $outarr;
		} //end if
		if((string)$cwd != '') {
			if(!self::check_if_safe_path((string)$cwd, 'yes', 'yes')) { // this is synced with SmartFileSystem::dir_create() ; without this check if non-empty will fail with dir create below
				self::log_warning(__METHOD__.' # The CWD Path is not safe: '.$cwd);
				$outarr['exitcode'] = -797;
				return (array) $outarr;
			} //end if
		} //end if
		//--

		//-- exec
		if((string)$cwd != '') {
			if(!self::path_exists((string)$cwd)) {
				self::dir_create((string)$cwd, true); // recursive
			} //end if
			if(!self::is_type_dir((string)$cwd)) {
				//--
				self::log_warning(__METHOD__.' # The Proc Open CWD Path: ['.$cwd.'] cannot be created and is not available !', 'See Error Log for more details ...');
				//--
				$outarr['stdout'] 	= '';
				$outarr['stderr'] 	= '';
				$outarr['exitcode'] = -998;
				//--
				return (array) $outarr;
				//--
			} //end if
		} else {
			$cwd = null;
		} //end if
		$resource = proc_open((string)$cmd, (array)$descriptorspec, $pipes, $cwd, $env);
		//--
		if(!is_resource($resource)) {
			//--
			$outarr['stdout'] 	= '';
			$outarr['stderr'] 	= 'Could not open Process / Not Resource';
			$outarr['exitcode'] = -997;
			//--
			return (array) $outarr;
			//--
		} //end if
		//--

		//-- write to stdin
		if(is_array($inargs)) {
			if(count($inargs) > 0) {
				foreach($inargs as $key => $val) {
					fwrite($pipes[0], (string)$val);
				} //end foreach
			} //end if
		} //end if
		//--

		//-- read stdout
		$output = (string) stream_get_contents($pipes[1]); // don't convert charset as it may break binary files
		//--

		//-- read stderr (here may be errors or warnings)
		$errors = (string) stream_get_contents($pipes[2]); // don't convert charset as it may break binary files
		//--

		//--
		fclose($pipes[0]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		//--
		$exitcode = proc_close($resource);
		//--

		//--
		$outarr['stdout'] 	= (string) $output;
		$outarr['stderr'] 	= (string) $errors;
		$outarr['exitcode'] = $exitcode; // don't make it INT !!!
		//--
		return (array) $outarr;
		//--

	} //END FUNCTION
	//================================================================


	//##### SmartFileSysUtils v.20200511


	//================================================================
	/**
	 * Check a Name of a File or Directory (not a path containing /) if contain valid characters (to avoid filesystem path security injections)
	 * Security: provides check if unsafe filenames or dirnames are accessed.
	 *
	 * @param 	STRING 	$y_fname 								:: The dirname or filename, (not path containing /) to validate
	 *
	 * @return 	0/1												:: returns 1 if VALID ; 0 if INVALID
	 */
	public static function check_if_safe_file_or_dir_name($y_fname) {
		//-- test empty filename
		if((string)trim((string)$y_fname) == '') {
			return 0;
		} //end if else
		//-- test valid characters in filename or dirname (must not contain / (slash), it is not a path)
		if(!preg_match('/^[_a-zA-Z0-9\-\.@#]+$/', (string)$y_fname)) { // {{{SYNC-CHK-SAFE-FILENAME}}}
			return 0;
		} //end if
		//-- test valid path (should pass all tests from valid, especially: must not be equal with: / or . or .. (and they are includded in valid path)
		if(self::test_valid_path($y_fname) !== 1) {
			return 0;
		} //end if
		//--
		if(strlen((string)$y_fname) > 255) {
			return 0;
		} //end if
		//--
		//--
		// IMPORTANT: it should not test if filenames or dirnames start with a # (protected) as they are not paths !!!
		//--
		return 1;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Check a Path (to a Directory or to a File) if contain valid characters (to avoid filesystem path security injections)
	 * Security: provides check if unsafe paths are accessed.
	 *
	 * @param 	STRING 	$y_path 								:: The path (dir or file) to validate
	 * @param 	YES/NO 	$y_deny_absolute_path 					:: *Optional* If YES will dissalow absolute paths
	 * @param 	YES/NO 	$y_allow_protected_relative_paths 		:: *Optional* ! This is for very special case usage only so don't set it to YES except if you know what you are really doing ! If set to YES will allow access to special protected paths of this framework which may have impact on security ... ; this parameter is intended just for relative paths only (not absolute paths) as: #dir/.../file.ext ; #file.ext
	 *
	 * @return 	0/1												:: returns 1 if VALID ; 0 if INVALID
	 */
	public static function check_if_safe_path($y_path, $y_deny_absolute_path='yes', $y_allow_protected_relative_paths='yes') { // {{{SYNC-FS-PATHS-CHECK}}}
		//--
		// !!! MODIFIED !!! set the 3rd param $y_allow_protected_relative_paths='yes' by default
		//-- dissalow empty paths
		if((string)trim((string)$y_path) == '') {
			return 0;
		} //end if else
		//-- test valid path
		if(self::test_valid_path($y_path) !== 1) {
			return 0;
		} //end if
		//-- test backward path
		if(self::test_backward_path($y_path) !== 1) {
			return 0;
		} //end if
		//-- test absolute path and protected path
		if((string)$y_deny_absolute_path != 'no') {
			if(self::test_absolute_path($y_path) !== 1) {
				return 0;
			} //end if
		} //end if
		//-- test protected path
		if((string)$y_allow_protected_relative_paths != 'yes') {
			if(self::test_special_path($y_path) !== 1) { // check protected path only if deny absolute path access, otherwise n/a
				return 0;
			} //end if
		} //end if
		//-- test max path length
		if(((int)strlen($y_path) > 1024) OR ((int)strlen($y_path) > (int)PHP_MAXPATHLEN)) {
			return 0; // path is longer than the allowed path max length by PHP_MAXPATHLEN between 512 to 4096 (safe is 1024)
		} //end if
		//--
		return 1; // valid
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ CHECK ABSOLUTE PATH ACCESS
	/**
	 * Function: Raise Error if Unsafe Path.
	 * Security: implements protection if unsafe paths are accessed.
	 *
	 * @param 	STRING 	$y_path 								:: The path (dir or file) to validate
	 * @param 	YES/NO 	$y_deny_absolute_path 					:: *Optional* If YES will dissalow absolute paths
	 * @param 	YES/NO 	$y_allow_protected_relative_paths 		:: *Optional* ! This is for very special case usage only so don't set it to YES except if you know what you are really doing ! If set to YES will allow access to special protected paths of this framework which may have impact on security ... ; this parameter is intended just for relative paths only (not absolute paths) as: #dir/.../file.ext ; #file.ext
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function raise_error_if_unsafe_path($y_path, $y_deny_absolute_path='yes', $y_allow_protected_relative_paths='yes') { // {{{SYNC-FS-PATHS-CHECK}}}
		//--
		// !!! MODIFIED !!! set the 3rd param $y_allow_protected_relative_paths='yes' by default
		//-- dissalow empty paths
		if((string)trim((string)$y_path) == '') {
			//--
			self::raise_error(
				'FileSystemUtils // Check Valid Path // EMPTY PATH IS DISALLOWED',
				'FileSysUtils: EMPTY PATH IS DISALLOWED !' // msg to display
			);
			return;
			//--
		} //end if
		//-- test valid path
		if(self::test_valid_path($y_path) !== 1) {
			//--
			self::raise_error(
				'FileSystemUtils // Check Valid Path // ACCESS DENIED to invalid path: '.$y_path,
				'FileSysUtils: INVALID CHARACTERS IN PATH ARE DISALLOWED !' // msg to display
			);
			return;
			//--
		} //end if
		//-- test backward path
		if(self::test_backward_path($y_path) !== 1) {
			//--
			self::raise_error(
				'FileSystemUtils // Check Backward Path // ACCESS DENIED to invalid path: '.$y_path,
				'FileSysUtils: BACKWARD PATH ACCESS IS DISALLOWED !' // msg to display
			);
			return;
			//--
		} //end if
		//-- test absolute path and protected path
		if((string)$y_deny_absolute_path != 'no') {
			if(self::test_absolute_path($y_path) !== 1) {
				//--
				self::raise_error(
					'FileSystemUtils // Check Absolute Path // ACCESS DENIED to invalid path: '.$y_path,
					'FileSysUtils: ABSOLUTE PATH ACCESS IS DISALLOWED !' // msg to display
				);
				return;
				//--
			} //end if
		} //end if
		//-- test protected path
		if((string)$y_allow_protected_relative_paths != 'yes') {
			if(self::test_special_path($y_path) !== 1) { // check protected path only if deny absolute path access, otherwise n/a
				//--
				self::raise_error(
					'FileSystemUtils // Check Protected Path // ACCESS DENIED to invalid path: '.$y_path,
					'FileSysUtils: PROTECTED PATH ACCESS IS DISALLOWED !' // msg to display
				);
				return;
				//--
			} //end if
		} //end if
		//-- test max path length
		if(strlen((string)$y_path) > 1024) {
			//--
			self::raise_error(
				'FileSystemUtils // Check Max Path Length (1024) // ACCESS DENIED to invalid path: '.$y_path,
				'FileSysUtils: PATH LENGTH IS EXCEEDING THE MAX ALLOWED LENGTH !' // msg to display
			);
			return;
			//--
		} //end if
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ TEST IF SPECIAL PATH
	// special protected paths (only relative) start with '#'
	// returns 1 if OK
	private static function test_special_path($y_path) {
		//--
		$y_path = (string) $y_path;
		//--
		if((string)substr((string)trim($y_path), 0, 1) == '#') {
			return 0;
		} //end if
		//--
		return 1; // valid
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ TEST IF VALID PATH
	// test if path is valid ; on windows paths must use the / instead of backslash (and without drive letter prefix c:) to comply
	// path should not contain SPACE, BACKSLASH, :, |, ...
	// the : is denied also on unix because can lead to unpredictable paths behaviours
	// the | is denied because old MacOS is not supported
	// path should not be EMPTY or equal with: / . .. ./ ../ ./.
	// path should contain just these characters _ a-z A-Z 0-9 - . @ # /
	// returns 1 if OK
	private static function test_valid_path($y_path) {
		//--
		$y_path = (string) $y_path;
		//--
		if(!preg_match('/^[_a-zA-Z0-9\-\.@#\/]+$/', (string)$y_path)) { // {{{SYNC-SAFE-PATH-CHARS}}} {{{SYNC-CHK-SAFE-PATH}}} only ISO-8859-1 characters are allowed in paths (unicode paths are unsafe for the network environments !!!)
			return 0;
		} //end if
		//--
		if(
			((string)trim($y_path) == '') OR 							// empty path: error
			((string)trim($y_path) == '.') OR 							// special: protected
			((string)trim($y_path) == '..') OR 							// special: protected
			((string)trim($y_path) == '/') OR 							// root dir: security
			(strpos($y_path, ' ') !== false) OR 						// no space allowed
			(strpos($y_path, '\\') !== false) OR 						// no backslash allowed
			(strpos($y_path, '://') !== false) OR 						// no protocol access allowed
			(strpos($y_path, ':') !== false) OR 						// no dos/win disk access allowed
			(strpos($y_path, '|') !== false) OR 						// no macos disk access allowed
			((string)trim($y_path) == './') OR 							// this must not be used - dissalow FS operations to the app root path, enforce use relative paths such as path/to/something
			((string)trim($y_path) == '../') OR 						// backward path access denied: security
			((string)trim($y_path) == './.') OR 						// this is a risk that can lead to unpredictable results
			(strpos($y_path, '...') !== false) OR 						// this is a risk that can lead to unpredictable results
			((string)substr((string)trim($y_path), -2, 2) == '/.') OR 	// special: protected ; this may lead to rewrite/delete the special protected . in a directory if refered as a filename or dirname that may break the filesystem
			((string)substr((string)trim($y_path), -3, 3) == '/..')  	// special: protected ; this may lead to rewrite/delete the special protected .. in a directory if refered as a filename or dirname that may break the filesystem
		) {
			return 0;
		} //end if else
		//--
		return 1; // valid
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ TEST IF BACKWARD PATH
	// test backpath or combinations against crafted paths to access backward paths on filesystem
	// will test only combinations allowed by test_valid_path()
	// returns 1 if OK
	private static function test_backward_path($y_path) {
		//--
		$y_path = (string) $y_path;
		//--
		if(
			(strpos($y_path, '/../') !== false) OR
			(strpos($y_path, '/./') !== false) OR
			(strpos($y_path, '/..') !== false) OR
			(strpos($y_path, '../') !== false)
		) {
			return 0;
		} //end if else
		//--
		return 1; // valid
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ TEST IF ABSOLUTE PATH
	// test against absolute path access
	// will test only combinations allowed by test_valid_path() and test_backward_path()
	// the first character should not be / ; path must not contain :, :/
	// returns 1 if OK
	private static function test_absolute_path($y_path) {
		//--
		$y_path = (string) trim((string)$y_path);
		//--
		$c1 = (string) substr((string)$y_path, 0, 1);
		$c2 = (string) substr((string)$y_path, 1, 1);
		//--
		if(
			((string)$c1 == '/') OR // unix/linux # /path/to/
			((string)$c1 == ':') OR // windows # :/path/to/
			((string)$c2 == ':')    // windows # c:/path/to
		) {
			return 0;
		} //end if
		//--
		return 1; // valid
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Safe add a trailing slash to a path if not already have it, with safe detection and avoid root access.
	 *
	 * Adding a trailing slash to a path is not a simple task as if path is empty, adding the trailing slash will result in accessing the root file system as will be: /.
	 * Otherwise it have to detect if the trailing slash exists already to avoid double slash.
	 *
	 * @param 	STRING 	$y_path 					:: The path to add the trailing slash to
	 *
	 * @return 	STRING								:: The fixed path with a trailing
	 */
	public static function add_dir_last_slash($y_path) {
		//--
		$y_path = (string) trim((string)self::fix_path_separator(trim((string)$y_path)));
		//--
		if(((string)$y_path == '') OR ((string)$y_path == '.') OR ((string)$y_path == './')) {
			return './'; // this is a mandatory security fix for the cases when used with dirname() which may return empty or just .
		} //end if
		//--
		if(((string)$y_path == '/') OR ((string)trim((string)str_replace(['/', '.'], ['', ''], (string)$y_path)) == '') OR (strpos($y_path, '\\') !== false)) {
			self::log_warning(__METHOD__.'() // Add Last Dir Slash: Invalid Path: ['.$y_path.'] ; Returned TMP/INVALID/');
			return 'tmp/invalid/'; // Security Fix: avoid make the path as root: / (if the path is empty, adding a trailing slash is a huge security risk)
		} //end if
		//--
		if(substr($y_path, -1, 1) != '/') {
			$y_path = $y_path.'/';
		} //end if
		//--
		self::raise_error_if_unsafe_path($y_path, 'yes', 'yes'); // deny absolute paths ; allow #special paths
		//--
		return (string) $y_path;
		//--
	} //END FUNCTION
	//================================================================


	//##### SmartFileSystem v.20200410


	//================================================================
	/**
	 * Fix the Directory CHMOD as defined in SMART_FRAMEWORK_CHMOD_DIRS.
	 * This provides a safe way to fix chmod on directories (symlinks or files will be skipped) ...
	 *
	 * @param 	STRING 	$dir_name 					:: The relative path to the directory name to fix chmod for (folder)
	 *
	 * @return 	BOOLEAN								:: TRUE if success, FALSE if not
	 */
	public static function fix_dir_chmod($dir_name) {
		//--
		if(!defined('SMART_FRAMEWORK_CHMOD_DIRS')) {
			self::log_warning(__METHOD__.'() // Skip: A required constant (SMART_FRAMEWORK_CHMOD_DIRS) has not been defined ...');
			return false;
		} //end if
		//--
		$dir_name = (string) $dir_name;
		//--
		if((string)trim((string)$dir_name) == '') {
			self::log_warning(__METHOD__.'() // Skip: Empty DirName');
			return false;
		} //end if
		if(!self::is_type_dir($dir_name)) { // not a dir
			self::log_warning(__METHOD__.'() // Skip: Not a Directory Type: '.$dir_name);
			return false;
		} //end if
		if(self::is_type_link($dir_name)) { // skip links !!
			return true;
		} //end if
		//--
		return (bool) @chmod($dir_name, SMART_FRAMEWORK_CHMOD_DIRS);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Fix the File CHMOD as defined in SMART_FRAMEWORK_CHMOD_FILES.
	 * This provides a safe way to fix chmod on files (symlinks or dirs will be skipped) ...
	 *
	 * @param 	STRING 	$file_name 					:: The relative path to the file name to fix chmod for (file)
	 *
	 * @return 	BOOLEAN								:: TRUE if success, FALSE if not
	 */
	public static function fix_file_chmod($file_name) {
		//--
		if(!defined('SMART_FRAMEWORK_CHMOD_FILES')) {
			self::log_warning(__METHOD__.'() // Skip: A required constant (SMART_FRAMEWORK_CHMOD_FILES) has not been defined ...');
			return false;
		} //end if
		//--
		$file_name = (string) $file_name;
		//--
		if((string)trim((string)$file_name) == '') {
			self::log_warning(__METHOD__.'() // Skip: Empty FileName');
			return false;
		} //end if
		if(!self::is_type_file($file_name)) { // not a file
			self::log_warning(__METHOD__.'() // Skip: Not a File Type: '.$file_name);
			return false;
		} //end if
		if(self::is_type_link($file_name)) { // skip links !!
			return true;
		} //end if
		//--
		return (bool) @chmod($file_name, SMART_FRAMEWORK_CHMOD_FILES);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * GET the File Size in Bytes. If invalid file or not file or broken link will return 0 (zero).
	 * This provides a safe way to get the file size (works also with symlinks) ...
	 *
	 * @param 	STRING 	$file_name 					:: The relative path to the file name to get the size for (file or symlink)
	 *
	 * @return 	INTEGER								:: 0 (zero) if file does not exists or invalid file type ; the file size in bytes for the rest of cases
	 */
	public static function get_file_size($file_name) {
		//--
		$file_name = (string) $file_name;
		//--
		if((string)trim((string)$file_name) == '') {
			self::log_warning(__METHOD__.'() // Skip: Empty FileName');
			return 0;
		} //end if
		if(!self::path_real_exists($file_name)) {
			return 0;
		} //end if
		//--
		return (int) @filesize((string)$file_name); // should return INTEGER as some comparisons may fail if casted type
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * GET the File Modification Timestamp. If invalid file or not file or broken link will return 0 (zero).
	 * This provides a safe way to get the file modification timestamp (works also with symlinks) ...
	 *
	 * @param 	STRING 	$file_name 					:: The relative path to the file name to get the last modification timestamp for (file or symlink)
	 *
	 * @return 	INTEGER								:: 0 (zero) if file does not exists or invalid file type ; the file modification timestamp for the rest of cases
	 */
	public static function get_file_mtime($file_name) {
		//--
		$file_name = (string) $file_name;
		//--
		if((string)trim((string)$file_name) == '') {
			self::log_warning(__METHOD__.'() // Skip: Empty FileName');
			return 0;
		} //end if
		if(!self::path_real_exists($file_name)) {
			return 0;
		} //end if
		//--
		return (int) @filemtime((string)$file_name); // should return INTEGER as some comparisons may fail if casted type
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * CHECK if a path is a directory (folder) type and exists.
	 * This provides a safe way to check if a path is directory (folder) (works also with symlinks) ...
	 *
	 * @param 	STRING 	$path 						:: The relative path name to be checked (file or dir or symlink)
	 *
	 * @return 	BOOLEAN								:: TRUE if directory (folder), FALSE if not
	 */
	public static function is_type_dir($path) {
		//--
		$path = (string) $path;
		//--
		if((string)trim((string)$path) == '') {
			self::log_warning(__METHOD__.'() // Skip: Empty Path');
			return false;
		} //end if
		//--
		clearstatcache(true, $path);
		//--
		return (bool) is_dir($path);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * CHECK if a path is a file type and exists.
	 * This provides a safe way to check if a path is file (works also with symlinks) ...
	 *
	 * @param 	STRING 	$path 						:: The relative path name to be checked (file or dir or symlink)
	 *
	 * @return 	BOOLEAN								:: TRUE if file, FALSE if not
	 */
	public static function is_type_file($path) {
		//--
		$path = (string) $path;
		//--
		if((string)trim((string)$path) == '') {
			self::log_warning(__METHOD__.'() // Skip: Empty Path');
			return false;
		} //end if
		//--
		clearstatcache(true, $path);
		//--
		return (bool) is_file($path);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * CHECK if a path directory or file is a symlink and exists. Will not check if symlink is broken (not check if symlink origin exists)
	 * This provides a safe way to check if a path is symlink (may be broken or not) ...
	 *
	 * @param 	STRING 	$path 						:: The relative path name to be checked (file or dir or symlink)
	 *
	 * @return 	BOOLEAN								:: TRUE if symlink, FALSE if not
	 */
	public static function is_type_link($path) {
		//--
		$path = (string) $path;
		//--
		if((string)trim((string)$path) == '') {
			self::log_warning(__METHOD__.'() // Skip: Empty Path');
			return false;
		} //end if
		//--
		clearstatcache(true, $path);
		//--
		return (bool) is_link($path);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * CHECK if a path directory or file exists and is readable (includding if a symlink).
	 * This provides a safe way to check if a path is readable ...
	 *
	 * @param 	STRING 	$path 						:: The relative path name to be checked (file or dir or symlink)
	 *
	 * @return 	BOOLEAN								:: TRUE if readable, FALSE if not
	 */
	public static function have_access_read($path) {
		//--
		$path = (string) $path;
		//--
		if((string)trim((string)$path) == '') {
			self::log_warning(__METHOD__.'() // Skip: Empty Path');
			return false;
		} //end if
		//--
		clearstatcache(true, $path);
		//--
		return (bool) is_readable($path);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * CHECK if a path directory or file exists and is writable (includding if a symlink).
	 * This provides a safe way to check if a path is writable ...
	 *
	 * @param 	STRING 	$path 						:: The relative path name to be checked (file or dir or symlink)
	 *
	 * @return 	BOOLEAN								:: TRUE if writable, FALSE if not
	 */
	public static function have_access_write($path) {
		//--
		$path = (string) $path;
		//--
		if((string)trim((string)$path) == '') {
			self::log_warning(__METHOD__.'() // Skip: Empty Path');
			return false;
		} //end if
		//--
		clearstatcache(true, $path);
		//--
		return (bool) is_writable($path);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * CHECK if a path is an executable file.
	 * This provides a safe way to check if a file path is executable (works also with symlinks) ...
	 *
	 * @param 	STRING 	$path 						:: The relative path name to be checked (file or symlink)
	 *
	 * @return 	BOOLEAN								:: TRUE if file, FALSE if not
	 */
	public static function have_access_executable($path) {
		//--
		$path = (string) $path;
		//--
		if((string)trim((string)$path) == '') {
			self::log_warning(__METHOD__.'() // Skip: Empty Path');
			return false;
		} //end if
		//--
		clearstatcache(true, $path);
		//--
		return (bool) is_executable($path);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * CHECK if a path exists (includding if a symlink or a broken symlink).
	 * This provides a safe way to check if a path exists because using only PHP file_exists() will return false if the path is a broken symlink ...
	 *
	 * @param 	STRING 	$path 						:: The relative path name to be checked (file or dir or symlink)
	 *
	 * @return 	BOOLEAN								:: TRUE if exists, FALSE if not
	 */
	public static function path_exists($path) {
		//--
		$path = (string) $path;
		//--
		if((string)trim((string)$path) == '') {
			self::log_warning(__METHOD__.'() // Skip: Empty Path');
			return false;
		} //end if
		//--
		clearstatcache(true, $path);
		//--
		if((file_exists($path)) OR (is_link($path))) { // {{{SYNC-SF-PATH-EXISTS}}}
			return true;
		} else {
			return false;
		} //end if else
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * CHECK if a path real exists (excluding if a symlink or a broken symlink).
	 * This provides a way to check if a path exists but only if take in consideration that the path may be a broken symlink that will return false if checked
	 * For normal checking if a path exists use self::path_exists().
	 * Use this in special cases where you need to check if a path that may be a broken link ...
	 *
	 * @param 	STRING 	$path 						:: The relative path name to be checked (file or dir or symlink)
	 *
	 * @return 	BOOLEAN								:: TRUE if exists, FALSE if not
	 */
	// will return TRUE if file or dir exists ; if a symlink will return TRUE just if the symlink is not broken (it's target exists)
	public static function path_real_exists($path) {
		//--
		$path = (string) $path;
		//--
		if((string)trim((string)$path) == '') {
			self::log_warning(__METHOD__.'() // Skip: Empty Path');
			return false;
		} //end if
		//--
		clearstatcache(true, $path);
		//--
		return (bool) file_exists($path); // checks if a file or directory exists (but this is not safe with symlinks as if a symlink is broken will return false ...)
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ READ FILES
	/**
	 * Safe READ A FILE contents. WORKS ONLY WITH RELATIVE PATHS (Ex: path/to/a/file.ext).
	 * It can read the full file content or just a part, starting from the zero offset (ex: first 100 bytes only)
	 * IT CANNOT BE USED TO ACCESS TEMPORARY UPLOAD FILES WHICH ARE ALWAYS ABSOLUTE PATHS. To access uploaded files use the method self::read_uploaded()
	 *
	 * @param 	STRING 		$file_name 				:: The relative path of file to be read (can be a symlink to a file)
	 * @param 	INTEGER+ 	$file_len 				:: DEFAULT is 0 (zero) ; If zero will read the entire file ; If > 0 (ex: 100) will read only the first 100 bytes fro the file or less if the file size is under 100 bytes
	 * @param 	YES/NO 		$markchmod 				:: DEFAULT is 'no' ; If 'yes' will force a chmod (as defined in SMART_FRAMEWORK_CHMOD_FILES) on the file before trying to read to ensure consistent chmod on all accesible files.
	 * @param 	BOOLEAN 	$safelock 				:: DEFAULT is 'no' ; If 'yes' will try to get a read shared lock on file prior to read ; If cannot lock the file will return empty string to avoid partial content read where reading a file that have intensive writes (there is always a risk to cannot achieve the lock ... there is no perfect scenario for intensive file operations in multi threaded environments ...)
	 *
	 * @return 	STRING								:: The file contents (or a part of file contents if $file_len parameter is used) ; if the file does not exists will return an empty string
	 */
	public static function read($file_name, $file_len=0, $markchmod='no', $safelock='no') {
		//--
		$file_name = (string) $file_name;
		$file_len = (int) $file_len;
		$markchmod = (string) $markchmod; // no/yes
		//--
		if((string)$file_name == '') {
			self::log_warning(__METHOD__.'() // ReadFile // Empty File Name');
			return '';
		} //end if
		//--
		self::raise_error_if_unsafe_path($file_name);
		//--
		clearstatcache(true, $file_name);
		//--
		$fcontent = '';
		//--
		if(self::check_if_safe_path($file_name)) {
			//--
			if(!self::is_type_dir($file_name)) {
				//--
				if(self::is_type_file($file_name)) {
					//--
					if((string)$markchmod == 'yes') {
						self::fix_file_chmod($file_name); // force chmod
					} elseif(!self::have_access_read($file_name)) {
						self::fix_file_chmod($file_name); // try to make ir readable by applying chmod
					} //end if
					//--
					if(!self::have_access_read($file_name)) {
						self::log_warning(__METHOD__.'() // ReadFile // A file is not readable: '.$file_name);
						return '';
					} //end if
					//-- fix for read file locking when using file_get_contents() # https://stackoverflow.com/questions/49262971/does-phps-file-get-contents-ignore-file-locking
					// USE LOCKING ON READS, only if specified so:
					//	* because there is a risk a file remain locked if a process crashes, there are a lot of reads in every execution, but few writes
					//	* on systems where locking is mandatory and not advisory this is expensive from resources point of view
					//	* if a process have to wait until obtain a lock ... is not what we want in web environment ...
					//	* neither the LOCK_NB does not resolv this issue, what we do if not locked ? return empty file contents instead of partial ? ... actually this is how it works also without locking ... tricky ... :-)
					//	* without a lock there is a little risk to get empty file (a partial file just on Windows), but that risk cannot be avoid, there is no perfect solution in multi-threaded environments with file read/writes concurrency ... use an sqlite or dba if having many writes and reads on the same file and care of data integrity
					//	* anyway, hoping for the best file_get_contents() should be atomic and if writes are made with atomic and LOCK_EX file_put_contents() everything should be fine, in any scenario there is a compromise
					if((string)$safelock === 'yes') {
						$lock = @fopen((string)$file_name, 'rb');
						if($lock) {
							$is_locked = @flock($lock, LOCK_SH);
						} //end if
					} else {
						$is_locked = true; // non-required
					} //end if
					//--
					if($is_locked !== true) {
						$fcontent = '';
					} else {
						if($file_len > 0) {
							$tmp_file_len = self::format_number_int(self::get_file_size((string)$file_name), '+');
							if((int)$file_len > (int)$tmp_file_len) {
								$file_len = (int) $tmp_file_len; // cannot be more than file length
							} //end if
							$fcontent = @file_get_contents(
								(string) $file_name,
								false, // don't use include path
								null, // context resource
								0, // start from begining (negative offsets still don't work)
								(int) $file_len // max length to read ; if zero, read the entire file
							);
						} else {
							$file_len = 0; // can't be negative (by mistake) ; if zero reads the entire file
							$fcontent = @file_get_contents(
								(string) $file_name,
								false, // don't use include path
								null, // context resource
								0 // start from begining (negative offsets still don't work)
								// max length to read ; don't use this parameter here ...
							);
						} //end if else
					} //end if else
					//--
					if((string)$safelock === 'yes') {
						if($lock) {
							if($is_locked) {
								@flock($lock, LOCK_UN);
							} //end if
							@fclose($lock); // will release any lock even if not unlocked by flock LOCK_UN
						} //end if
					} //end if
					//-- #fix for locking
					if($fcontent === false) { // check
						self::log_warning(__METHOD__.'() // ReadFile // Failed to read the file: '.$file_name);
						$fcontent = '';
					} //end if
					//--
				} //end if
				//--
			} //end if else
			//--
		} else {
			//--
			self::log_warning(__METHOD__.'() // ReadFile // Invalid FileName to read: '.$file_name);
			//--
		} //end if
		//--
		return (string) $fcontent;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ CREATE AND WRITE FILES
	/**
	 * Safe CREATE AND/OR WRITE/APPEND CONTENTS TO A FILE. WORKS ONLY WITH RELATIVE PATHS (Ex: path/to/a/file.ext).
	 * It can create a new file or overwrite an existing file.
	 * It also can to write append to a file.
	 * The file will be chmod standardized, as set in SMART_FRAMEWORK_CHMOD_FILES.
	 *
	 * @param 	STRING 		$file_name 				:: The relative path of file to be written (can be an existing symlink to a file)
	 * @param 	STRING 		$file_content 			:: DEFAULT is '' ; The content string to be written to the file (binary safe)
	 * @param 	ENUM 		$write_mode 			:: DEFAULT is 'w' Write (If file exists then overwrite. If the file does not exist create it) ; If 'a' will use Write-Append by appending the content to a file which can exists or not.
	 *
	 * @return 	INTEGER								:: 1 if SUCCESS ; 0 on FAIL (this is integer instead of boolean for future extending with status codes)
	 */
	public static function write($file_name, $file_content='', $write_mode='w') {
		//--
		$file_name = (string) $file_name;
		//--
		if((string)$file_name == '') {
			self::log_warning(__METHOD__.'() // Write: Empty File Name');
			return 0;
		} //end if
		//--
		self::raise_error_if_unsafe_path($file_name);
		//--
		clearstatcache(true, $file_name);
		//--
		$result = false;
		//--
		if(self::check_if_safe_path($file_name)) {
			//--
			if(!self::is_type_dir($file_name)) {
				//--
				if(self::is_type_link($file_name)) {
					if(!self::path_real_exists($file_name)) {
						self::delete($file_name); // delete the link if broken
					} //end if
				} //end if
				//--
				if(self::is_type_file($file_name)) {
					if(!self::have_access_write($file_name)) {
						self::fix_file_chmod($file_name); // apply chmod first to be sure file is writable
					} //end if
					if(!self::have_access_write($file_name)) {
						self::log_warning(__METHOD__.'() // WriteFile // A file is not writable: '.$file_name);
						return 0;
					} //end if
				} //end if
				//-- fopen/fwrite method lacks the real locking which can be achieved just with flock which is not as safe as doing at once with: file_put_contents
				if((string)$write_mode == 'w') { // wb (write, binary safe)
					$result = @file_put_contents($file_name, (string)$file_content, LOCK_EX);
				} else { // ab (append, binary safe)
					$result = @file_put_contents($file_name, (string)$file_content, FILE_APPEND | LOCK_EX);
				} //end if else
				//--
				if(self::is_type_file($file_name)) {
					self::fix_file_chmod($file_name); // apply chmod afer write (fix as the file create chmod may be different !!)
					if(!self::have_access_write($file_name)) {
						self::log_warning(__METHOD__.'() // WriteFile // A file is not writable: '.$file_name);
					} //end if
				} //end if
				//-- check the write result (number of bytes written)
				if($result === false) {
					self::log_warning(__METHOD__.'() // WriteFile // Failed to write a file: '.$file_name);
				} else {
					if($result !== @strlen((string)$file_content)) {
						self::log_warning(__METHOD__.'() // WriteFile // A file was not completely written (removing it ...): '.$file_name);
						@unlink($file_name); // delete the file, was not completely written (do not use self::delete here, the file is still locked !)
					} //end if
				} //end if
				//--
			} else {
				//--
				self::log_warning(__METHOD__.'() // WriteFile // Failing to write file as this is a type Directory: '.$file_name);
				//--
			} //end if else
			//--
		} //end if else
		//--
		if($result === false) { // file was not written
			$out = 0;
		} else { // result can be zero or a positive number of bytes written
			$out = 1;
		} //end if
		//--
		return (int) $out;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ WRITE IF NOT EXISTS OR CONTENT DIFFERS
	/**
	 * Safe CREATE OR WRITE TO A FILE IF NOT EXISTS OR CONTENT DIFFERS. WORKS ONLY WITH RELATIVE PATHS (Ex: path/to/a/file.ext).
	 * It can only create a new file or overwrite an existing file if the content does not match.
	 * The file will be chmod standardized, as set in SMART_FRAMEWORK_CHMOD_FILES.
	 *
	 * @param 	STRING 		$file_name 				:: The relative path of file to be written (can be an existing symlink to a file)
	 * @param 	STRING 		$file_content 			:: DEFAULT is '' ; The content string to be written to the file (binary safe)
	 * @param 	YES/NO 		$y_chkcompare 			:: DEFAULT is 'no' ; If 'yes' will check the existing fiile contents and will overwrite if different than the passed contents in $file_content
	 *
	 * @return 	INTEGER								:: 1 if SUCCESS ; 0 on FAIL (this is integer instead of boolean for future extending with status codes)
	 */
	public static function write_if_not_exists($file_name, $file_content, $y_chkcompare='no') {
		//--
		$file_name = (string) $file_name;
		//--
		if((string)$file_name == '') {
			self::log_warning(__METHOD__.'() // WriteIfNotExists: Empty File Name');
			return 0;
		} //end if
		//--
		self::raise_error_if_unsafe_path($file_name);
		//--
		$x_ok = 0;
		//--
		if((string)$y_chkcompare == 'yes') {
			//--
			if((string)self::read($file_name) != (string)$file_content) { // compare content
				$x_ok = self::write($file_name, (string)$file_content);
			} else {
				$x_ok = 1;
			} //end if
			//--
		} else {
			//--
			if(!self::is_type_file($file_name)) {
				$x_ok = self::write($file_name, (string)$file_content);
			} else {
				$x_ok = 1;
			} //end if else
			//--
		} //end if
		//--
		return (int) $x_ok;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ COPY A FILE TO A DESTINATION
	/**
	 * Safe COPY A FILE TO A DIFFERENT LOCATION. WORKS ONLY WITH RELATIVE PATHS (Ex: path/to/a/file.ext).
	 * It will copy the file from source location to a destination location (includding across partitions).
	 * The destination file will be chmod standardized, as set in SMART_FRAMEWORK_CHMOD_FILES.
	 *
	 * @param 	STRING 		$file_name 				:: The relative path of file to be copied (can be a symlink to a file)
	 * @param 	STRING 		$newlocation 			:: The relative path of the destination file (where to copy)
	 * @param 	BOOLEAN 	$overwrite_destination 	:: DEFAULT is FALSE ; If set to FALSE will FAIL if destination file exists ; If set to TRUE will overwrite the file destination if exists
	 * @param 	BOOLEAN 	$check_copy_contents 	:: DEFAULT is TRUE ; If set to TRUE (safe mode) will compare the copied content from the destination file with the original file content using sha1-file checksums ; If set to FALSE (non-safe mode) will not do this comparison check (but may save a big amount of time when working with very large files)
	 *
	 * @return 	INTEGER								:: 1 if SUCCESS ; 0 on FAIL (this is integer instead of boolean for future extending with status codes)
	 */
	public static function copy($file_name, $newlocation, $overwrite_destination=false, $check_copy_contents=true) {
		//--
		$file_name = (string) $file_name;
		$newlocation = (string) $newlocation;
		//--
		if((string)$file_name == '') {
			self::log_warning(__METHOD__.'() // Copy: Empty Source File Name');
			return 0;
		} //end if
		if((string)$newlocation == '') {
			self::log_warning(__METHOD__.'() // Copy: Empty Destination File Name');
			return 0;
		} //end if
		if((string)$file_name == (string)$newlocation) {
			self::log_warning(__METHOD__.'() // Copy: The Source and the Destination Files are the same: '.$file_name);
			return 0;
		} //end if
		//--
		if((!self::is_type_file($file_name)) OR ((self::is_type_link($file_name)) AND (!self::is_type_file(self::link_get_origin($file_name))))) {
			self::log_warning(__METHOD__.'() // Copy // Source is not a FILE: S='.$file_name.' ; D='.$newlocation);
			return 0;
		} //end if
		if($overwrite_destination !== true) {
			if(self::path_exists($newlocation)) {
				self::log_warning(__METHOD__.'() // Copy // The destination file exists (1): S='.$file_name.' ; D='.$newlocation);
				return 0;
			} //end if
		} //end if
		//--
		self::raise_error_if_unsafe_path($file_name);
		self::raise_error_if_unsafe_path($newlocation);
		//--
		clearstatcache(true, $file_name);
		clearstatcache(true, $newlocation);
		//--
		$result = false;
		//--
		if(self::is_type_file($file_name)) {
			//--
			if(($overwrite_destination === true) OR (!self::path_exists($newlocation))) {
				//--
				$result = @copy($file_name, $newlocation); // if destination exists will overwrite it
				//--
				if(self::is_type_file($newlocation)) {
					//--
					self::fix_file_chmod($newlocation); // apply chmod
					//--
					if(!self::have_access_read($newlocation)) {
						self::log_warning(__METHOD__.'() // CopyFile // Destination file is not readable: '.$newlocation);
					} //end if
					//--
					if((int)self::get_file_size($file_name) !== (int)self::get_file_size($newlocation)) {
						$result = false; // clear
						self::delete($newlocation); // remove incomplete copied file
						self::log_warning(__METHOD__.'() // CopyFile // Destination file is not same size as original: '.$newlocation);
					} //end if
					//--
					if($check_copy_contents === true) {
						if((string)sha1_file($file_name) !== (string)sha1_file($newlocation)) {
							$result = false; // clear
							self::delete($newlocation); // remove broken copied file
							self::log_warning(__METHOD__.'() // CopyFile // Destination file checksum failed: '.$newlocation);
						} //end if
					} //end if
					//--
				} else {
					//--
					self::log_warning(__METHOD__.'() // CopyFile // Failed to copy a file: '.$file_name.' // to destination: '.$newlocation);
					//--
				} //end if
				//--
			} else {
				self::log_warning(__METHOD__.'() // CopyFile // Destination file exists (2): '.$newlocation);
			} //end if
			//--
		} //end if
		//--
		if($result) {
			$out = 1;
		} else {
			$out = 0;
		} //end if else
		//--
		return (int) $out;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ RENAME / MOVE FILES
	/**
	 * Safe RENAME OR MOVE A FILE TO A DIFFERENT LOCATION. WORKS ONLY WITH RELATIVE PATHS (Ex: path/to/a/file.ext).
	 * It will rename or move the file from source location to a destination location (includding across partitions).
	 * The destination file will NOT be rewritten if exists and the $overwrite_destination is set to FALSE, so in this case
	 * be sure to check and remove the destination if you intend to overwrite it.
	 * If the $overwrite_destination is set to TRUE the $newlocation will be overwritten.
	 * After rename or move the destination will be chmod standardized, as set in SMART_FRAMEWORK_CHMOD_FILES.
	 *
	 * @param 	STRING 		$file_name 				:: The relative path of file to be renamed or moved (can be a symlink to a file)
	 * @param 	STRING 		$newlocation 			:: The relative path of the destination file (new file name to rename or a new path where to move)
	 *
	 * @return 	INTEGER								:: 1 if SUCCESS ; 0 on FAIL (this is integer instead of boolean for future extending with status codes)
	 */
	public static function rename($file_name, $newlocation, $overwrite_destination=false) {
		//--
		$file_name = (string) $file_name;
		$newlocation = (string) $newlocation;
		//--
		if((string)$file_name == '') {
			self::log_warning(__METHOD__.'() // Rename/Move: Empty Source File Name');
			return 0;
		} //end if
		if((string)$newlocation == '') {
			self::log_warning(__METHOD__.'() // Rename/Move: Empty Destination File Name');
			return 0;
		} //end if
		if((string)$file_name == (string)$newlocation) {
			self::log_warning(__METHOD__.'() // Rename/Move: The Source and the Destination Files are the same: '.$file_name);
			return 0;
		} //end if
		//--
		if((!self::is_type_file($file_name)) OR ((self::is_type_link($file_name)) AND (!self::is_type_file(self::link_get_origin($file_name))))) {
			self::log_warning(__METHOD__.'() // Rename/Move // Source is not a FILE: S='.$file_name.' ; D='.$newlocation);
			return 0;
		} //end if
		if($overwrite_destination !== true) {
			if(self::path_exists($newlocation)) {
				self::log_warning(__METHOD__.'() // Rename/Move // The destination already exists: S='.$file_name.' ; D='.$newlocation);
				return 0;
			} //end if
		} //end if
		//--
		self::raise_error_if_unsafe_path($file_name);
		self::raise_error_if_unsafe_path($newlocation);
		//--
		clearstatcache(true, $file_name);
		clearstatcache(true, $newlocation);
		//--
		$f_cx = false;
		//--
		if(((string)$file_name != (string)$newlocation) AND (self::check_if_safe_path($file_name)) AND (self::check_if_safe_path($newlocation))) {
			//--
			if((self::is_type_file($file_name)) OR ((self::is_type_link($file_name)) AND (self::is_type_file(self::link_get_origin($file_name))))) { // don't move broken links
				//--
				if(!self::is_type_dir($newlocation)) {
					//--
					self::delete($newlocation); // just to be sure
					//--
					if(($overwrite_destination !== true) AND (self::path_exists($newlocation))) {
						//--
						self::log_warning(__METHOD__.'() // RenameFile // Destination file points to an existing file or link: '.$newlocation);
						//--
					} else {
						//--
						$f_cx = @rename($file_name, $newlocation); // If renaming a file and newname exists, it will be overwritten. If renaming a directory and newname exists, this function will emit a warning.
						//--
						if((self::is_type_file($newlocation)) OR ((self::is_type_link($newlocation)) AND (self::is_type_file(self::link_get_origin($newlocation))))) {
							if(self::is_type_file($newlocation)) {
								self::fix_file_chmod($newlocation); // apply chmod just if file and not a linked dir
							} //end if
						} else {
							$f_cx = false; // clear
							self::log_warning(__METHOD__.'() // RenameFile // Failed to rename a file: '.$file_name.' // to destination: '.$newlocation);
						} //end if
						//--
						if(!self::have_access_read($newlocation)) {
							self::log_warning(__METHOD__.'() // RenameFile // Destination file is not readable: '.$newlocation);
						} //end if
					} //end if
					//--
				} //end if else
				//--
			} //end if else
			//--
		} //end if
		//--
		if($f_cx == true) {
			$x_ok = 1;
		} else {
			$x_ok = 0;
		} //end if
		//--
		return (int) $x_ok;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ READ UPLOADED FILES
	/**
	 * Safe READ AN UPLOADED FILE contents. WORKS WITH ABSOLUTE PATHS (Ex: /tmp/path/to/uploaded-file.ext).
	 * INFO: This function is 100% SAFE ON LINUX and UNIX file systems.
	 * WARNING: This function is NOT VERY SAFE TO USE ON WINDOWS file systems (use it on your own risk) because extra checks over the absolute path are not available on windows paths, thus in theory it may lead to insecure path access if crafted paths may result ...
	 * It will read the full file content of the uploaded file.
	 * IT SHOULD BE USED TO ACCESS ONLY TEMPORARY UPLOAD FILES. To read other files use the method self::read()
	 *
	 * @param 	STRING 		$file_name 				:: The absolute path of the uploaded file to be read
	 *
	 * @return 	STRING								:: The file contents ; if the file does not exists or it is not an uploaded file will return an empty string
	 */
	public static function read_uploaded($file_name) {
		//--
		$file_name = (string) $file_name;
		//--
		if((string)$file_name == '') {
			self::log_warning(__METHOD__.'() // Read-Uploaded: Empty Uploaded File Name');
			return '';
		} //end if
		//-- {{{SYNC-FILESYS-UPLD-FILE-CHECKS}}}
		if((string)DIRECTORY_SEPARATOR != '\\') { // if not on Windows (this test will FAIL on Windows ...)
			if(!self::check_if_safe_path($file_name, 'no')) { // here we do not test against absolute path access because uploaded files always return the absolute path
				self::log_warning(__METHOD__.'() // Read-Uploaded: The Uploaded File Path is Not Safe: '.$file_name);
				return '';
			} //end if
			self::raise_error_if_unsafe_path($file_name, 'no'); // here we do not test against absolute path access because uploaded files always return the absolute path
		} //end if
		//--
		clearstatcache(true, $file_name);
		//--
		$f_cx = '';
		//--
		if(is_uploaded_file($file_name)) {
			//--
			if(!self::is_type_dir($file_name)) {
				//--
				if((self::is_type_file($file_name)) AND (self::have_access_read($file_name))) {
					//--
					$f_cx = (string) @file_get_contents($file_name);
					//--
				} else {
					//--
					self::log_warning(__METHOD__.'() // ReadUploadedFile // The file is not readable: '.$file_name);
					//--
				} //end if
				//--
			} //end if else
			//--
		} else {
			//--
			self::log_warning(__METHOD__.'() // ReadUploadedFile // Cannot Find the Uploaded File or it is NOT an Uploaded File: '.$file_name);
			//--
		} //end if
		//--
		return (string) $f_cx;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ DELETE FILES
	/**
	 * Safe DELETE A FILE. WORKS ONLY WITH RELATIVE PATHS (Ex: path/to/a/file.ext).
	 * It will delete a file (or a symlink) if exists
	 *
	 * @param 	STRING 		$file_name 				:: The relative path of file to be deleted (can be a symlink to a file)
	 *
	 * @return 	INTEGER								:: 1 if SUCCESS ; 0 on FAIL (this is integer instead of boolean for future extending with status codes)
	 */
	public static function delete($file_name) {
		//--
		$file_name = (string) $file_name;
		//--
		if((string)$file_name == '') {
			self::log_warning(__METHOD__.'() // FileDelete // The File Name is Empty !');
			return 0; // empty file name
		} //end if
		//--
		self::raise_error_if_unsafe_path($file_name);
		//--
		clearstatcache(true, $file_name);
		//--
		if(!self::path_exists($file_name)) {
			//--
			return 1;
			//--
		} //end if
		//--
		if(self::is_type_link($file_name)) { // {{{SYNC-BROKEN-LINK-DELETE}}}
			//--
			$f_cx = @unlink($file_name);
			//--
			if(($f_cx) AND (!self::is_type_link($file_name))) {
				return 1;
			} else {
				return 0;
			} //end if else
			//--
		} //end if
		//--
		$f_cx = false;
		//--
		if(self::check_if_safe_path($file_name)) {
			//--
			if((self::is_type_file($file_name)) OR (self::is_type_link($file_name))) {
				//--
				if(self::is_type_file($file_name)) {
					//--
					self::fix_file_chmod($file_name); // apply chmod
					//--
					$f_cx = @unlink($file_name);
					//--
					if(self::path_exists($file_name)) {
						$f_cx = false;
						self::log_warning(__METHOD__.'() // DeleteFile // FAILED to delete this file: '.$file_name);
					} //end if
					//--
				} //end if
				//--
			} elseif(self::is_type_dir($file_name)) {
				//--
				self::log_warning(__METHOD__.'() // DeleteFile // A file was marked for deletion but that is a directory: '.$file_name);
				//--
			} //end if
			//--
		} //end if
		//--
		if($f_cx == true) {
			$x_ok = 1;
		} else {
			$x_ok = 0;
		} //end if
		//--
		return (int) $x_ok;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Safe GET THE ORIGIN OF A SYMLINK. WORKS ONLY WITH RELATIVE PATHS (Ex: path/to/a/symlink).
	 * It will get the origin path of a symlink if exists (not broken).
	 * WARNING: Use this function carefuly as it may return an absolute path or a non-safe path of the link origin which may result in unpredictable security issues ...
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param 	STRING 		$y_link 				:: The relative path of symlink to be analyzed
	 *
	 * @return 	STRING								:: The relative or absolute (or non-safe) path to the symlink origin or empty string if broken link (no path safety checks are implemented over)
	 */
	public static function link_get_origin($y_link) {
		//--
		$y_link = (string) $y_link;
		//--
		if((string)$y_link == '') {
			self::log_warning(__METHOD__.'() // Get Link: The Link Name is Empty !');
			return '';
		} //end if
		//--
		if(!self::check_if_safe_path($y_link)) { // pre-check
			self::log_warning(__METHOD__.'() // Get Link: Invalid Path Link : '.$y_link);
			return '';
		} //end if
		if(substr($y_link, -1, 1) == '/') { // test if end with one or more trailing slash(es) and rtrim
			self::log_warning(__METHOD__.'() // Get Link: Link ends with one or many trailing slash(es) / : '.$y_link);
			$y_link = (string) rtrim($y_link, '/');
		} //end if
		if(!self::check_if_safe_path($y_link)) { // post-check
			self::log_warning(__METHOD__.'() // Get Link: Invalid Link Path : '.$y_link);
			return '';
		} //end if
		//--
		self::raise_error_if_unsafe_path($y_link);
		//--
		if(!self::is_type_link($y_link)) {
			self::log_warning(__METHOD__.'() // Get Link: Link does not exists : '.$y_link);
			return '';
		} //end if
		//--
		return (string) @readlink($y_link);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Safe CREATE A SYMLINK. WORKS ONLY WITH RELATIVE PATHS (Ex: path/to/a/something).
	 * It will create a symlink of the origin into destination.
	 * WARNING: Use this function carefuly as the origin path may be an absolute path or a non-safe path of the link origin which may result in unpredictable security issues ...
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param 	STRING 		$origin 				:: The origin of the symlink, relative or absolute path or even may be a non-safe path (no path safety checks are implemented over)
	 * @param 	STRING 		$destination 			:: The destination of the symlink, relative path
	 *
	 * @return 	INTEGER								:: 1 if SUCCESS ; 0 on FAIL (this is integer instead of boolean for future extending with status codes)
	 */
	public static function link_create($origin, $destination) {
		//--
		$origin = (string) $origin;
		$destination = (string) $destination;
		//--
		if((string)$origin == '') {
			self::log_warning(__METHOD__.'() // Create Link: The Origin Name is Empty !');
			return 0;
		} //end if
		if((string)$destination == '') {
			self::log_warning(__METHOD__.'() // Create Link: The Destination Name is Empty !');
			return 0;
		} //end if
		//--
		/* DO NOT CHECK, IT MAY BE AN ABSOLUTE + NON-SAFE PATH RETURNED BY self::link_get_origin() ...
		if(!self::check_if_safe_path($origin, 'no')) { // here we do not test against absolute path access because readlink may return an absolute path
			self::log_warning(__METHOD__.'() // Create Link: Invalid Path for Origin : '.$origin);
			return 0;
		} //end if
		*/
		if(!self::check_if_safe_path($destination)) {
			self::log_warning(__METHOD__.'() // Create Link: Invalid Path for Destination : '.$destination);
			return 0;
		} //end if
		//--
		if(!self::path_exists($origin)) {
			self::log_warning(__METHOD__.'() // Create Link: Origin does not exists : '.$origin);
			return 0;
		} //end if
		if(self::path_exists($destination)) {
			self::log_warning(__METHOD__.'() // Create Link: Destination exists : '.$destination);
			return 0;
		} //end if
		//--
		// DO NOT CHECK, IT MAY BE AN ABSOLUTE + NON-SAFE PATH RETURNED BY self::link_get_origin() ...
		//self::raise_error_if_unsafe_path($origin, 'no'); // here we do not test against absolute path access because readlink may return an absolute path
		//--
		self::raise_error_if_unsafe_path($destination);
		//--
		$result = @symlink($origin, $destination);
		//--
		if($result) {
			$out = 1;
		} else {
			$out = 0;
		} //end if else
		//--
		return (int) $out;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ CREATE DIRS
	/**
	 * Safe CREATE A DIRECTORY (FOLDER) RECURSIVE OR NON-RECURSIVE. WORKS ONLY WITH RELATIVE PATHS (Ex: path/to/a/new-dir).
	 * It will create a new directory (folder) if not exists. If non-recursive will try to create just the last directory (folder) segment.
	 * The directory (folder) will be chmod standardized, as set in SMART_FRAMEWORK_CHMOD_DIRS.
	 *
	 * WARNING: The $allow_protected_paths parameter MUST BE SET TO TRUE ONLY FOR VERY SPECIAL USAGE ONLY, TO ALLOW relative paths like : #path/to/a/new-dir that may not be used with standard SmartFileSystem functions as they should be PROTECTED.
	 * Protected Paths (Directories / Folders) are intended for separing the accesible part of filesystem (for regular operations provided via this class) by the protected part of filesystem that can be by example accessed only from special designed libraries.
	 * Example: create a folder #db/sqlite/ and it's content (files, sub-dirs) will not be accessed by this class but only from outside libraries like SQLite).
	 * This feature implements a separation between regular file system folders that this class can access and other application level protected folders in order to avoid filesystem direct access to the protected folders.
	 * As long as all file system operations will be provided only by this class and not using the PHP internal file system functions this separation is safe and secure.
	 *
	 * @param 	STRING 		$dir_name 				:: The relative path of directory to be created (can be an existing symlink to a directory)
	 * @param 	BOOLEAN 	$recursive 				:: DEFAULT is FALSE ; If TRUE will attempt to create the full directory (folder) structure if not exists and apply over each segment the standardized chmod, as set in SMART_FRAMEWORK_CHMOD_DIRS
	 * @param 	BOOLEAN 	$allow_protected_paths 	:: DEFAULT is FALSE ; If TRUE it may be used to create special protected folders (set to TRUE only if you know what you are really doing and you need to create a folder starting with a `#`, otherwise may lead to security issues ...)
	 *
	 * @return 	INTEGER								:: 1 if SUCCESS ; 0 on FAIL (this is integer instead of boolean for future extending with status codes)
	 */
	public static function dir_create($dir_name, $recursive=false, $allow_protected_paths=false) {
		//--
		$dir_name = (string) $dir_name;
		//--
		if(!defined('SMART_FRAMEWORK_CHMOD_DIRS')) {
			self::log_warning(__METHOD__.'() // Skip: A required constant (SMART_FRAMEWORK_CHMOD_DIRS) has not been defined ...');
			return 0;
		} //end if
		//--
		if((string)$dir_name == '') {
			self::log_warning(__METHOD__.'() // Create Dir [R='.(int)$recursive.'/S='.(int)$allow_protected_paths.'] // The Dir Name is Empty !');
			return 0;
		} //end if
		//--
		if($allow_protected_paths === true) {
			self::raise_error_if_unsafe_path($dir_name, 'yes', 'yes'); // deny absolute paths ; allow protected paths (starting with a `#`)
			$is_path_chk_safe = self::check_if_safe_path($dir_name, 'yes', 'yes'); // deny absolute paths ; allow protected paths (starting with a `#`)
		} else {
			self::raise_error_if_unsafe_path($dir_name);
			$is_path_chk_safe = self::check_if_safe_path($dir_name);
		} //end if else
		//--
		clearstatcache(true, $dir_name);
		//--
		$result = false;
		//--
		if($is_path_chk_safe) {
			//--
			if(!self::path_exists($dir_name)) {
				//--
				if($recursive === true) {
					$result = @mkdir($dir_name, SMART_FRAMEWORK_CHMOD_DIRS, true);
					$dir_elements = (array) explode('/', $dir_name);
					$tmp_crr_dir = '';
					for($i=0; $i<count($dir_elements); $i++) { // fix: to chmod all dir segments (in PHP the mkdir chmod is applied only to the last dir segment if recursive mkdir ...)
						$dir_elements[$i] = (string) trim((string)$dir_elements[$i]);
						if((string)$dir_elements[$i] != '') {
							$tmp_crr_dir .= (string) self::add_dir_last_slash((string)$dir_elements[$i]);
							if((string)$tmp_crr_dir != '') {
								if(self::is_type_dir((string)$tmp_crr_dir)) {
									self::fix_dir_chmod((string)$tmp_crr_dir); // apply separate chmod to each segment
								} //end if
							} //end if
						} //end if
					} //end for
				} else {
					$result = @mkdir($dir_name, SMART_FRAMEWORK_CHMOD_DIRS);
					if(self::is_type_dir($dir_name)) {
						self::fix_dir_chmod($dir_name); // apply chmod
					} //end if
				} //end if else
				//--
			} elseif(self::is_type_dir($dir_name)) {
				//--
				$result = true; // dir exists
				//--
			} else {
				//--
				self::log_warning(__METHOD__.'() // CreateDir [R='.(int)$recursive.'/S='.(int)$allow_protected_paths.'] // FAILED to create a directory because it appear to be a File: '.$dir_name);
				//--
			} //end if else
			//--
			if(!self::is_type_dir($dir_name)) {
				self::log_warning(__METHOD__.'() // CreateDir [R='.(int)$recursive.'/S='.(int)$allow_protected_paths.'] // FAILED to create a directory: '.$dir_name);
				$out = 0;
			} //end if
			//--
			if(!self::have_access_write($dir_name)) {
				self::log_warning(__METHOD__.'() // CreateDir [R='.(int)$recursive.'/S='.(int)$allow_protected_paths.'] // The directory is not writable: '.$dir_name);
				$out = 0;
			} //end if
			//--
		} else {
			//--
			self::log_warning(__METHOD__.'() // CreateDir [R='.(int)$recursive.'/S='.(int)$allow_protected_paths.'] // The directory path is not Safe: '.$dir_name);
			//--
		} //end if
		//--
		if($result == true) {
			$out = 1;
		} else {
			$out = 0;
		} //end if
		//--
		return (int) $out;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ RENAME DIR / MOVE DIR
	/**
	 * Safe RENAME OR MOVE A DIRECTORY (FOLDER) TO A DIFFERENT LOCATION. WORKS ONLY WITH RELATIVE PATHS (Ex: path/to/a/some-dir).
	 * It will rename or move the source directory (folder) to a new location (destination), includding across partitions.
	 * It will FAIL if the destination directory exists, so be sure to check and remove the destination if you intend to overwrite it.
	 * After rename or move the destination will be chmod standardized, as set in SMART_FRAMEWORK_CHMOD_DIRS.
	 *
	 * @param 	STRING 		$dir_name 				:: The relative path of directory (folder) to be renamed or moved (can be a symlink to a directory)
	 * @param 	STRING 		$new_dir_name 			:: The relative path of the destination directory (folder) or a new directory (folder) name to rename or a new path where to move
	 *
	 * @return 	INTEGER								:: 1 if SUCCESS ; 0 on FAIL (this is integer instead of boolean for future extending with status codes)
	 */
	public static function dir_rename($dir_name, $new_dir_name) {
		//--
		$dir_name = (string) $dir_name;
		$new_dir_name = (string) $new_dir_name;
		//--
		if((string)$dir_name == '') {
			self::log_warning(__METHOD__.'() // Rename/Move Dir: Source Dir Name is Empty !');
			return 0;
		} //end if
		if((string)$new_dir_name == '') {
			self::log_warning(__METHOD__.'() // Rename/Move Dir: Destination Dir Name is Empty !');
			return 0;
		} //end if
		if((string)$dir_name == (string)$new_dir_name) {
			self::log_warning(__METHOD__.'() // Rename/Move Dir: The Source and the Destination Files are the same: '.$dir_name);
			return 0;
		} //end if
		//--
		if((!self::is_type_dir($dir_name)) OR ((self::is_type_link($dir_name)) AND (!self::is_type_dir(self::link_get_origin($dir_name))))) {
			self::log_warning(__METHOD__.'() // RenameDir // Source is not a DIR: S='.$dir_name.' ; D='.$new_dir_name);
			return 0;
		} //end if
		if(self::path_exists($new_dir_name)) {
			self::log_warning(__METHOD__.'() // RenameDir // The destination already exists: S='.$dir_name.' ; D='.$new_dir_name);
			return 0;
		} //end if
		//--
		$dir_name = self::add_dir_last_slash($dir_name); // trailing slash
		$new_dir_name = self::add_dir_last_slash($new_dir_name); // trailing slash
		//--
		self::raise_error_if_unsafe_path($dir_name);
		self::raise_error_if_unsafe_path($new_dir_name);
		//--
		if((string)$dir_name == (string)$new_dir_name) {
			self::log_warning(__METHOD__.'() // Rename/Move Dir: Source and Destination are the same: S&D='.$dir_name);
			return 0;
		} //end if
		if((string)$new_dir_name == (string)self::add_dir_last_slash(self::dir_name($dir_name))) {
			self::log_warning(__METHOD__.'() // Copy Dir: The Destination Dir is the same as Source Parent Dir: S='.$dir_name.' ; D='.$new_dir_name);
			return 0;
		} //end if
		if((string)substr($new_dir_name, 0, strlen($dir_name)) == (string)$dir_name) {
			self::log_warning(__METHOD__.'() // Rename/Move Dir: The Destination Dir is inside the Source Dir: S='.$dir_name.' ; D='.$new_dir_name);
			return 0;
		} //end if
		if(!self::is_type_dir(self::add_dir_last_slash(self::dir_name($new_dir_name)))) {
			self::log_warning(__METHOD__.'() // Rename/Move Dir: The Destination Parent Dir is missing: P='.self::add_dir_last_slash(self::dir_name($new_dir_name)).' of D='.$new_dir_name);
			return 0;
		} //end if
		//--
		clearstatcache(true, $dir_name);
		clearstatcache(true, $new_dir_name);
		//--
		$result = false;
		//--
		$dir_name = (string) rtrim((string)$dir_name, '/'); // FIX: remove trailing slash, it may be a link
		$new_dir_name = (string) rtrim((string)$new_dir_name, '/'); // FIX: remove trailing slash, it may be a link
		//--
		if(((string)$dir_name != (string)$new_dir_name) AND (self::check_if_safe_path($dir_name)) AND (self::check_if_safe_path($new_dir_name))) {
			if((self::is_type_dir($dir_name)) OR ((self::is_type_link($dir_name)) AND (self::is_type_dir(self::link_get_origin($dir_name))))) {
				if(!self::path_exists($new_dir_name)) {
					$result = @rename($dir_name, $new_dir_name);
				} //end if
			} //end if
		} //end if else
		//--
		if((!self::is_type_dir($new_dir_name)) OR ((self::is_type_link($new_dir_name)) AND (!self::is_type_dir(self::link_get_origin($new_dir_name))))) {
			self::log_warning(__METHOD__.'() // RenameDir // FAILED to rename a directory: S='.$dir_name.' ; D='.$new_dir_name);
			return 0;
		} //end if
		if(self::path_exists($dir_name)) {
			self::log_warning(__METHOD__.'() // RenameDir // Source DIR still exists: S='.$dir_name.' ; D='.$new_dir_name);
			return 0;
		} //end if
		//--
		if($result == true) {
			$out = 1;
		} else {
			$out = 0;
		} //end if
		//--
		return (int) $out;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ DELETE DIRS
	/**
	 * Safe DELETE (REMOVE) A DIRECTORY (FOLDER). IF RECURSIVE WILL REMOVE ALL THE SUB-DIR CONTENTS (FILES AND SUB-DIRS). WORKS ONLY WITH RELATIVE PATHS (Ex: path/to/a/some-dir).
	 * It will try to remove the directory (folder) if empty in non-recursive mode or will try to delete all directory content (files and sub-folders), if recursive mode enabled.
	 * It will FAIL in non-recursive mode if the directory (folder) is not empty.
	 *
	 * @param 	STRING 		$dir_name 				:: The relative path of directory (folder) to be deleted (removed) ; it can be a symlink to another directory
	 * @param 	BOOLEAN 	$recursive 				:: DEFAULT is TRUE ; If set to TRUE will remove directory and all it's content ; If FALSE will try just to remove the directory if empty, otherwise will FAIL
	 *
	 * @return 	INTEGER								:: 1 if SUCCESS ; 0 on FAIL (this is integer instead of boolean for future extending with status codes)
	 */
	public static function dir_delete($dir_name, $recursive=true) {
		//--
		$dir_name = (string) $dir_name;
		//--
		if((string)$dir_name == '') {
			self::log_warning(__METHOD__.'() // DeleteDir [R='.(int)$recursive.'] // Dir Name is Empty !');
			return 0;
		} //end if
		//--
		clearstatcache(true, $dir_name);
		//--
		if(self::is_type_link($dir_name)) { // {{{SYNC-BROKEN-LINK-DELETE}}}
			//--
			$f_cx = @unlink($dir_name); // avoid deleting content from a linked dir, just remove the link :: THIS MUST BE DONE BEFORE ADDING THE TRAILING SLASH
			//--
			if(($f_cx) AND (!self::is_type_link($dir_name))) {
				return 1;
			} else {
				return 0;
			} //end if else
			//--
		} //end if
		//--
		$dir_name = self::add_dir_last_slash($dir_name); // fix invalid path (must end with /)
		//--
		self::raise_error_if_unsafe_path($dir_name);
		//--
		if(!self::path_exists($dir_name)) {
			//--
			return 1;
			//--
		} //end if
		//-- avoid deleting content from a linked dir, just remove the link (2nd check, after adding the trailing slash)
		if(self::is_type_link($dir_name)) { // {{{SYNC-BROKEN-LINK-DELETE}}}
			//--
			$f_cx = @unlink($dir_name);
			//--
			if(($f_cx) AND (!self::is_type_link($dir_name))) {
				return 1;
			} else {
				return 0;
			} //end if else
			//--
		} //end if
		//--
		$result = false;
		//-- remove all subdirs and files within
		if(self::check_if_safe_path($dir_name)) {
			//--
			if((self::is_type_dir($dir_name)) AND (!self::is_type_link($dir_name))) { // double check if type link
				//--
				self::fix_dir_chmod($dir_name); // apply chmod
				//--
				if($handle = opendir($dir_name)) {
					//--
					while(false !== ($file = readdir($handle))) {
						//--
						if(((string)$file != '') AND ((string)$file != '.') AND ((string)$file != '..')) { // fix empty
							//--
							if(self::check_if_safe_file_or_dir_name((string)$file) != 1) { // skip non-safe filenames to avoid raise error if a directory contains accidentally a nn-safe filename or dirname (at least delete as much as can) ...
								//--
								self::log_warning(__METHOD__.'() // DeleteDir [R='.(int)$recursive.'] // SKIP Unsafe FileName or DirName `'.$file.'` detected in path: '.$dir_name);
								//--
							} else {
								//--
								if((self::is_type_dir($dir_name.$file)) AND (!self::is_type_link($dir_name.$file))) {
									//--
									if($recursive == true) {
										//--
										self::dir_delete($dir_name.$file, $recursive);
										//--
									} else {
										//--
										return 0; // not recursive and in this case sub-folders are not deleted
										//--
									} //end if else
									//--
								} else { // file or link
									//--
									self::delete($dir_name.$file);
									//--
								} //end if else
								//--
							} //end if else
							//--
						} //end if
						//--
					} //end while
					//--
					@closedir($handle);
					//--
				} else {
					//--
					$result = false;
					self::log_warning(__METHOD__.'() // DeleteDir [R='.(int)$recursive.'] // FAILED to open the directory: '.$dir_name);
					//--
				} //end if
				//-- finally, remove itself
				$result = @rmdir($dir_name);
				//--
			} else { // the rest of cases: is a file or a link
				//--
				$result = false;
				self::log_warning(__METHOD__.'() // DeleteDir [R='.(int)$recursive.'] // This is not a directory: '.$dir_name);
				//--
			} //end if
			//--
		} //end if
		//--
		if(self::path_exists($dir_name)) { // last final check
			$result = false;
			self::log_warning(__METHOD__.'() // DeleteDir [R='.(int)$recursive.'] // FAILED to delete a directory: '.$dir_name);
		} //end if
		//--
		if($result == true) {
			$out = 1;
		} else {
			$out = 0;
		} //end if
		//--
		return (int) $out;
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
