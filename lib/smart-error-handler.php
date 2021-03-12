<?php
// [Smart.Framework / ERRORS Management]
// (c) 2006-2021 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

// # r.20210312 # this should be loaded from app web root only

// ===== IMPORTANT =====
//	* NO VARIABLES SHOULD BE DEFINED IN THIS FILE BECAUSE IS LOADED BEFORE REGISTERING ANY OF GET/POST VARIABLES (CAN CAUSE SECURITY ISSUES)
//	* ONLY CONSTANTS CAN BE DEFINED HERE
//	* FOR ERRORS WILL USE htmlspecialchars($string, ENT_HTML401 | ENT_COMPAT | ENT_SUBSTITUTE, SMART_FRAMEWORK_CHARSET, true); // as default, with double encoding
//===================

// ALL ERRORS WILL BE LOGGED TO A LOG FILE: SMART_ERROR_LOGDIR/SMART_ERROR_LOGFILE defined below

//===== WARNING: =====
// Changing the code below is on your own risk and may lead to severe disrupts in the execution of this software !
//====================

//--
if(!defined('SMART_FRAMEWORK_CHARSET')) {
	define('SMART_FRAMEWORK_CHARSET', 'UTF-8');
} //end if
if(!defined('SMART_FRAMEWORK_DEBUG_MODE')) {
	define('SMART_FRAMEWORK_DEBUG_MODE', 'no'); // if not explicit defined, this must be set here to avoid PHP 7.3+ warnings
} //end if
//--
if(defined('SMART_ERROR_LOG_MANAGEMENT')) {
	@http_response_code(500);
	die('Smart.Framework / Errors Management already loaded ...'); // avoid load more than once
} //end if
//--
define('SMART_ERROR_LOG_MANAGEMENT', 'SET');
//--
if(!defined('SMART_ERROR_HANDLER')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_ERROR_HANDLER');
} //end if
//--
if(defined('SMART_ERROR_LOGDIR')) {
	@http_response_code(500);
	die('SMART_ERROR_LOGDIR cannot be defined outside ERROR HANDLER');
} //end if
define('SMART_ERROR_LOGDIR', (string)smart__framework__err__handler__get__absolute_logpath()); // the function will check if path is safe and correct ; if not will raise a fatal error !
//--
if(defined('SMART_ERROR_AREA')) { // display this error area
	@http_response_code(500);
	die('SMART_ERROR_AREA cannot be defined outside ERROR HANDLER');
} //end if
if(defined('SMART_ERROR_LOGFILE')) { // if set as 'log' or 'off' the errors will be registered into this local error log file
	@http_response_code(500);
	die('SMART_ERROR_LOGFILE cannot be defined outside ERROR HANDLER');
} //end if
if(SMART_FRAMEWORK_ADMIN_AREA === true) {
	define('SMART_ERROR_AREA', 'ADM');
	define('SMART_ERROR_LOGFILE', 'phperrors-adm-'.date('Y-m-d@H').'.log');
} else {
	define('SMART_ERROR_AREA', 'IDX');
	define('SMART_ERROR_LOGFILE', 'phperrors-idx-'.date('Y-m-d@H').'.log');
} //end if else
//--
$smart_____framework_____last__error = ''; // initialize
//--

//==
if(((string)SMART_ERROR_HANDLER == 'log') AND ((string)SMART_FRAMEWORK_DEBUG_MODE != 'yes')) { // log :: hide errors and just log them
	ini_set('display_startup_errors', '0');
	ini_set('display_errors', '0');
} else { // dev :: display errors and log them
	ini_set('display_startup_errors', '1');
	ini_set('display_errors', '1');
} //end if else
ini_set('track_errors', '0');
//==
if((string)SMART_ERROR_HANDLER == 'log') {
	error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED); // error reporting for display only, production
} else {
	error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT); // error reporting for display only, development (show deprecated)
} //end if else
set_error_handler(function($errno, $errstr, $errfile, $errline) {
	//--
	global $smart_____framework_____last__error; // presume it is already html special chars safe
	//--
	if(((string)SMART_ERROR_HANDLER == 'log') AND ((string)SMART_FRAMEWORK_DEBUG_MODE != 'yes')) { // log :: hide errors and just log them
		$smart_____framework_____last__error = ''; // hide errors if explicit set so (make sense in production environments)
	} //end if
	//-- The following error types cannot be handled with a user defined function: E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING, and most of E_STRICT raised in the file where set_error_handler() is called : http://php.net/manual/en/function.set-error-handler.php
	$app_halted = '';
	$is_supressed = false;
	$is_fatal = false;
	switch($errno) { // friendly err names
		case E_NOTICE:
			$ferr = 'NOTICE';
		//	if(0 == error_reporting()) { // fix: don't log E_NOTICE from @functions
			if(!(error_reporting() & $errno)) { // fix: don't log E_NOTICE from @functions, fix for PHP 8
				$is_supressed = true;
			} //end if
			break;
		case E_USER_NOTICE:
			$ferr = 'APP-NOTICE';
			if((string)SMART_ERROR_HANDLER == 'log') {
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
	if(((string)SMART_ERROR_HANDLER != 'log') OR ((string)SMART_FRAMEWORK_DEBUG_MODE == 'yes')) {
		$is_supressed = false;
	} //end if
	if(defined('SMART_ERROR_SILENCE_WARNS_NOTICE')) { // to silence warnings and notices from logs this must be set explicit in init.php as: define('SMART_ERROR_SILENCE_WARNS_NOTICE', true); // Error Handler silence warnings and notices log (available just for SMART_ERROR_HANDLER=log mode)
		$is_supressed = true;
	} //end if
	//--
	if(($is_supressed !== true) OR ($is_fatal === true)) {
		if((is_dir((string)SMART_ERROR_LOGDIR)) && (is_writable((string)SMART_ERROR_LOGDIR))) { // here must be is_dir(), is_writable() and file_put_contents() as the smart framework libs are not yet initialized in this phase ...
			@file_put_contents(
				(string) SMART_ERROR_LOGDIR.SMART_ERROR_LOGFILE,
				(string) "\n".'===== '.date('Y-m-d H:i:s O')."\n".'PHP '.PHP_VERSION.' [SMART-ERR-HANDLER] #'.$errno.' ['.$ferr.']'.$app_halted."\n".'HTTP-METHOD: '.(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '').' # '.'CLIENT: '.trim((string)(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '').' ; '.(isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : '').' ; '.(isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR']: ''), '; ').' @ '.(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '')."\n".'URI: ['.SMART_ERROR_AREA.'] @ '.(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '')."\n".'Script: '.$errfile."\n".'Line number: '.$errline."\n".$errstr."\n".'==================================='."\n\n",
				FILE_APPEND | LOCK_EX
			);
		} //end if
	} //end if
	//--
	if(($errno === E_RECOVERABLE_ERROR) OR ($errno === E_USER_ERROR)) { // this is necessary for: E_RECOVERABLE_ERROR and E_USER_ERROR (which is used just for Exceptions) and all other PHP errors which are FATAL and will stop the execution ; For WARNING / NOTICE type errors we just want to log them, not to stop the execution !
		//--
		$message = 'Server Script Execution Halted.'."\n".'See the App Error Log for details';
		if((string)SMART_FRAMEWORK_DEBUG_MODE == 'yes') {
			$message .= ':'."\n".SMART_ERROR_LOGDIR.SMART_ERROR_LOGFILE;
		} else {
			$message .= '.';
		} //end if
		//--
		if(!headers_sent()) {
			@http_response_code(500); // try, if not headers send
		} //end if
		die('<!-- Smart Error Reporting / Smart Error Handler --><div><center><div style="width:548px; border: 1px solid #CCCCCC; margin-top:10px; margin-bottom:10px;"><table cellpadding="4" style="max-width:540px;"><tr valign="top"><td width="32"><img src="'.smart__framework__err__handler__get__basepath().'lib/framework/img/sign-crit-error.svg" alt="[!]" title="[!]"></td><td>&nbsp;</td><td><b>'.'Application Runtime Error @ '.SMART_ERROR_AREA.' [#'.$errno.']:<br>'.'</b><i>'.nl2br((string)htmlspecialchars((string)$message, ENT_HTML401 | ENT_COMPAT | ENT_SUBSTITUTE, (string)SMART_FRAMEWORK_CHARSET, true), false).'</i></td></tr></table></div><br>'.$smart_____framework_____last__error.'</center></div>');
		//--
	} //end if else
	//--
}, E_ALL & ~E_NOTICE); // error reporting for logging
//==
set_exception_handler(function($exception) { // no type for EXCEPTION to be PHP 7 compatible
	//--
	global $smart_____framework_____last__error; // presume it is already html special chars safe
	//--
	//print_r($exception);
	//print_r($exception->getTrace());
	//--
	$message = $exception->getMessage();
	$details = ' Script: '.$exception->getFile()."\n".' Line: '.$exception->getLine();
	$exid = sha1('ExceptionID:'.$message.'/'.$details);
	//--
	if(is_array($exception->getTrace())) {
		//--
		$arr = (array) $exception->getTrace();
		//--
		if(((string)SMART_ERROR_HANDLER != 'log') OR ((string)SMART_FRAMEWORK_DEBUG_MODE == 'yes')) {
			$smart_____framework_____last__error .= ini_get('error_prepend_string').'<b><i>Exception [#'.$exid.']</i><br>Error-Message: '.htmlspecialchars((string)$message, ENT_HTML401 | ENT_COMPAT | ENT_SUBSTITUTE, (string)SMART_FRAMEWORK_CHARSET, true).'</b><div style="color:#555555; padding:5px; margin:5px;">'.htmlspecialchars((string)$details, ENT_HTML401 | ENT_COMPAT | ENT_SUBSTITUTE, (string)SMART_FRAMEWORK_CHARSET, true).'</div>'.trim((string)ini_get('error_append_string')); // fix for PHP 7+
		} //end if
		//--
		if((string)SMART_FRAMEWORK_DEBUG_MODE == 'yes') {
			//--
			$details .= "\n".print_r($arr,1);
			//--
		} else {
			//--
			for($i=0; $i<2; $i++) { // trace just 2 levels
				$details .= "\n".'  ----- Line #'.(isset($arr[$i]['line']) ? $arr[$i]['line'] : '').' @ Class: ['.(isset($arr[$i]['class']) ? $arr[$i]['class'] : '').'] '.(isset($arr[$i]['type']) ? $arr[$i]['type'] : '').' Function: ['.(isset($arr[$i]['function']) ? $arr[$i]['function'] : '').'] | File: '.(isset($arr[$i]['file']) ? $arr[$i]['file'] : '');
				$details .= "\n".'    ----- Args * '.(isset($arr[$i]['args']) ? print_r($arr[$i]['args'],1) : '');
			} //end for
			//--
		} //end if else
		//--
	} //end if
	//--
	@trigger_error('***** EXCEPTION ***** [#'.$exid.']:'."\n".'Error-Message: '.$message."\n".$details, E_USER_ERROR); // log the exception as ERROR
	//-- below code would be executed only if E_USER_ERROR fails to stop the execution
	if(!headers_sent()) {
		@http_response_code(500); // try, if not headers send
	} //end if
	die('Execution Halted. Application Level Exception. See the App Error Log for more details.');
	//--
});
//==
ini_set('ignore_repeated_source', '0'); // do not ignore repeated errors if in different files
if(((string)SMART_ERROR_HANDLER == 'log') AND ((string)SMART_FRAMEWORK_DEBUG_MODE != 'yes')) { // log :: hide errors and just log them
	ini_set('ignore_repeated_errors', '1'); // ignore repeated errors in the same file on the same line
	ini_set('log_errors_max_len', 2048); // max size of one error to log 2k (in production environments this is costly)
	register_shutdown_function('smart__framework__err__handler__catch_fatal_errs');
} else { // dev
	ini_set('ignore_repeated_errors', '0'); // do not ignore repeated errors
	ini_set('error_prepend_string', '<div align="left"><style type="text/css">* { font-family: arial,sans-serif; font-smooth: always; }</style> &nbsp; <font size="7" color="#4E5A92"><b>Code Execution ERROR <img src="'.smart__framework__err__handler__get__basepath().'lib/framework/img/sign-crit-error.svg"> PHP '.PHP_VERSION.'</b></font> <span title="PHP Version: '.PHP_VERSION.'" style="cursor:help;"><img width="48" align="right" src="'.smart__framework__err__handler__get__basepath().'lib/framework/img/php-logo.svg"></span><div><hr size="1"><pre>');
	ini_set('error_append_string', '</pre></div><br><div>'.'<small>'.date('Y-m-d H:i:s O').'</small>'.'<hr size="1"></div><span title="Powered by Smart.Framework" style="cursor:help;"><img src="'.smart__framework__err__handler__get__basepath().'lib/framework/img/sf-logo.svg"></span><span title="Error" style="cursor:help;"><img src="'.smart__framework__err__handler__get__basepath().'lib/framework/img/sign-crit-warn.svg" align="right"></span></div>');
	ini_set('log_errors_max_len', 16384); // max size of one error to log 16k
} //end if else
ini_set('html_errors', '0'); // display errors in TEXT format
ini_set('log_errors', '1'); // log always the errors
ini_set('error_log', (string)SMART_ERROR_LOGDIR.SMART_ERROR_LOGFILE); // error log file
//==
/**
 * Function Error Handler Catch Fatal Errors
 * Info: when display_errors is set to false, will display a blank page
 * This is a fix for that situation ...
 * @access 		private
 * @internal
 */
function smart__framework__err__handler__catch_fatal_errs() {
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
//==
/**
 * Function Error Handler Get BasePath
 * @access 		private
 * @internal
 */
function smart__framework__err__handler__get__basepath() {
	//--
	$imgprefix = (string) dirname((string)(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : ''));
	//--
	if(((string)$imgprefix == '') || ((string)$imgprefix == '/') || ((string)$imgprefix == '\\') || ((string)$imgprefix == '.')) {
		$imgprefix = ''; // no prefix
	} else {
		$imgprefix .= '/'; // fix: add a trailing slash
	} //end if
	//--
	return (string) $imgprefix;
	//--
} //END FUNCTION
//--
/**
 * Function: Get the Error Handler log folder ; it must be .htaccess protected
 * @hints 		The phperrors-idx-yyyy-mm-dd@hh.log and phperrors-adm-yyyy-mm-dd@hh.log error log files will be written into this folder
 * @access 		private
 * @internal
 */
function smart__framework__err__handler__get__absolute_logpath() {
	//--
	// the PHP Bug #31570 (not fixed since a very long time) : cannot access relative paths after destruct of main executors started, ex: handlers registered with register_shutdown_function()
	//--
	// INFO: this must be a full / absolute path (not a relative path) because register_shutdown_function() handlers may not always work with relative paths
	// EXAMPLE: need to log to tmp/logs after majority of objects have been destroyed and no more detection of relative path
	// NOTICE: this converts windows path from using backslash to using slash
	//--
	$err_paths = 'broken in the current PHP installation. Please fix this by install a PHP version without this bug ...';
	//--
	$current_file = (string) @basename(__FILE__);
	if((string)$current_file !== 'smart-error-handler.php') {
		@http_response_code(500);
		die('Smart.Framework # ERROR HANDLER: the Path BaseName detection is '.$err_paths);
		return '';
	} //end if
	//--
	$path = (string) realpath('./'); // get the current absolute path of current running folder (this will be run from index.php or admin.php which is outside lib/ ... so this scenario is considered)
	if((string)$path == '') { // if realpath fails and return an empty path
		@http_response_code(500);
		die('Smart.Framework # ERROR HANDLER: the RealPath detection is '.$err_paths);
		return '';
	} //end if
	if((string)DIRECTORY_SEPARATOR == '\\') { // if on Windows, Fix Path Separator !!!
		if(strpos((string)$path, '\\') !== false) {
			$path = (string) str_replace((string)DIRECTORY_SEPARATOR, '/', (string)$path); // convert windows path from using backslash to using slash
		} //end if
		$regex = '/^[_a-zA-Z0-9\-\.@#\/\:]+$/'; // regex for windows, after converting backslashes to normal slashes ; {{{SYNC-CHK-SAFE-FILENAME}}} with extra `/` and `:`
	} else {
		$regex = '/^[_a-zA-Z0-9\-\.@#\/]+$/'; // regex for linux/unix ; ; {{{SYNC-CHK-SAFE-FILENAME}}} with extra `/`
	} //end if else
	//--
	$max_path_len = (int) ceil(PHP_MAXPATHLEN * 0.33); // the path to the Smart.Framework installation should not be longer than 33% of max path length supported by OS
	//--
	if(
		((string)trim((string)$path) == '') OR // empty path, cannot detect real path !
		((int)strlen((string)$path) > (int)$max_path_len) OR // path too long
		(strpos((string)$path, '.') === 0) OR // path must not start with a single dot (this covers all dangerous scenarios: path is a single dot (.) ; path is a double dot (..) ; path start with a dot (.hidden)
		(strpos((string)$path, '\\') !== false) OR // single backslash \ has been converted already, must not exists
		(strpos((string)$path, '..') !== false) OR // check for double dots .. which are unsafe
		(strpos((string)$path, '//') !== false) OR // check for double slashes // which are unsafe
		(!preg_match((string)$regex, (string)$path))
	) {
		@http_response_code(500);
		die('Smart.Framework # ERROR HANDLER: the current installation of Smart.Framework runs under a non-safe Path (`'.$path.'`) ; Path should be no longer than '.(int)$max_path_len.' characters long (~ 33% of max path length allowed by the OS which is '.PHP_MAXPATHLEN.') ; Only the following restricted character set is allowed in the Path by Smart.Framework: `_`, `a-z`, `A-Z`, `0-9`, `-`, `.`, `@`, `#` and `/` on Linux/Unix/Windows ; on Windows an extra `:` character is allowed. Using non-safe paths in web environments may lead to severe security issues.');
		return '';
	} //end if
	//--
	$path = (string) rtrim((string)$path, '/').'/'; // add last slash to the path after above checks if not already have
	//--
	if (
		(!is_dir($path.'lib/')) OR // try to detect Smart.Framework lib directory to see if the path is correct
		(!is_file($path.'lib/'.$current_file)) // try to detect Smart.Framework lib/smart-error-handler.php (this file) to see if the path is correct
	) {
		@http_response_code(500);
		die('Smart.Framework # ERROR HANDLER: cannot detect the Path to the current Smart.Framework installation. Detected path cannot find the current error handler script at: `'.$path.'lib/'.$current_file.'`');
		return '';
	} //end if
	//--
	$path .= 'tmp/logs/'; // append the `tmp/logs/` to the path as suffix (the path last slash was added above)
	//--
	if(
		(strlen((string)$path) < 11) OR // because 10 characters is only the `/tmp/logs/` suffix
		((string)substr((string)$path, -11, 1) == '.') OR // the `/tmp/logs/` must not be preceded by a `.` that may result in unsafe `./tmp/logs/`
		((string)substr((string)$path, -10, 10) != '/tmp/logs/') // and must end with `/tmp/logs/` to avoid mistakes
	) { // safety checks after adding the `/tmp/logs/` suffix
		@http_response_code(500);
		die('Smart.Framework # ERROR HANDLER: cannot detect the Path to the current Smart.Framework temporary folder: `tmp/logs/` ...');
	} //end if
	//--
	return (string) $path;
	//--
} //END FUNCTION
//==

// end of php code
