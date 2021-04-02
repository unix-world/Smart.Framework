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

// # r.20210402 # this should be loaded from app web root only

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
if((string)trim((string)ini_get('default_mimetype')) != 'text/html') {
	@http_response_code(500);
	die('PHP Default MimeType must be set as: `text/html` but it set to: `'.ini_get('default_mimetype').'`');
} //end if
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
if(!defined('SMART_FRAMEWORK_CHARSET')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_CHARSET');
} //end if
//--
if(defined('SMART_ERROR_LOGDIR')) {
	@http_response_code(500);
	die('SMART_ERROR_LOGDIR cannot be defined outside ERROR HANDLER');
} //end if
if(defined('SMART_ERROR_LOGSUFFIXDIR')) {
	if(!defined('SMART_SOFTWARE_APP_NAME')) { // on Smart Framework this cannot be defined prior to load this file as it is checked in smart runtime ; only extra apps like app code pack/unpack can do this ...
		@http_response_code(500);
		die('A Reserved Constant have been already defined: SMART_ERROR_LOGSUFFIXDIR without defining the SMART_SOFTWARE_APP_NAME');
	} //end if
} //end if
if(!defined('SMART_ERROR_LOGSUFFIXDIR')) { // this can be customized for other instances like app code pack/unpack
	define('SMART_ERROR_LOGSUFFIXDIR', 'tmp/logs/'); // must have the trailing slash and must not have a prefix slash ; for smart framework default is 'tmp/logs/'
} //end if
if(!define('SMART_ERROR_LOGDIR', (string)smart__framework__err__handler__get__absolute_logpath((string)SMART_ERROR_LOGSUFFIXDIR))) { // the function will check if path is safe and correct ; if not will raise a fatal error !
	@http_response_code(500);
	die('Failed to define the SMART_ERROR_LOGDIR ...');
} //end if
//--
if(defined('SMART_ERROR_AREA')) { // display this error area
	@http_response_code(500);
	die('SMART_ERROR_AREA cannot be defined outside ERROR HANDLER');
} //end if
if(defined('SMART_ERROR_LOGFILE')) { // if set as 'log' or 'off' the errors will be registered into this local error log file
	@http_response_code(500);
	die('SMART_ERROR_LOGFILE cannot be defined outside ERROR HANDLER');
} //end if
if(!defined('SMART_FRAMEWORK_ADMIN_AREA')) {
	@http_response_code(500);
	die('A required RUNTIME constant has not been defined: SMART_FRAMEWORK_ADMIN_AREA');
} //end if
if(SMART_FRAMEWORK_ADMIN_AREA === true) {
	define('SMART_ERROR_AREA', 'ADM');
	define('SMART_ERROR_LOGFILE', 'phperrors-adm-'.date('Y-m-d@H').'.log');
} else {
	define('SMART_ERROR_AREA', 'IDX');
	define('SMART_ERROR_LOGFILE', 'phperrors-idx-'.date('Y-m-d@H').'.log');
} //end if else
if(!defined('SMART_ERROR_AREA')) { // display this error area
	@http_response_code(500);
	die('Failed to define the SMART_ERROR_AREA ...');
} //end if
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
//==
if((string)SMART_ERROR_HANDLER == 'log') {
	error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED); // error reporting for display only, production
} else {
	error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT); // error reporting for display only, development (show deprecated)
} //end if else
//==
$smart_____framework_____last__error = ''; // initialize, empty
$smart_____framework_____is_html_last__error = false; // initialize, false
//==
// IMPORTANT: SINCE PHP7 there is no need to reserve memory for logging ... it does itself and will log via the err log registered shutdown handler below
//==
set_error_handler(function($errno, $errstr, $errfile, $errline) {
	//--
	global $smart_____framework_____last__error;
	global $smart_____framework_____is_html_last__error;
	//--
	if(((string)SMART_ERROR_HANDLER == 'log') AND (SMART_FRAMEWORK_DEBUG_MODE !== true)) { // if long and debug not enabled :: hide errors and just log them
		$smart_____framework_____last__error = ''; // hide errors if explicit set so (make sense in production environments)
		$smart_____framework_____is_html_last__error = false;
	} else {
		if($smart_____framework_____is_html_last__error !== true) {
			$smart_____framework_____last__error = (string) htmlspecialchars((string)$smart_____framework_____last__error, ENT_HTML401 | ENT_COMPAT | ENT_SUBSTITUTE, (string)SMART_FRAMEWORK_CHARSET, true);
			$smart_____framework_____is_html_last__error = true;
		} //end if
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
			$app_halted = ' :: Execution HALTED !';
			$ferr = 'APP-ERROR';
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
	//--
	if(($is_supressed !== true) OR ($is_fatal === true)) {
		$log_message = (string) "\n".
			'==================================='."\n".
			'PHP '.PHP_VERSION.' [SMART-ERR-HANDLER:'.strtoupper((string)SMART_FRAMEWORK_ENV).'] #'.$errno.' ['.$ferr.']'.$app_halted.' @ '.date('Y-m-d H:i:s O')."\n".
			'-----------------'."\n".
			'HTTP-METHOD: '.(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '').' # '.'CLIENT: '.trim((string)(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '').' ; '.(isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : '').' ; '.(isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR']: ''), '; ').' @ '.(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '')."\n".
			'URI: ['.SMART_ERROR_AREA.'] @ '.(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '')."\n".
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
	$script = '';
	$appenv = null;
	if(($errno === E_RECOVERABLE_ERROR) OR ($errno === E_USER_ERROR)) { // this is necessary for: E_RECOVERABLE_ERROR and E_USER_ERROR (which is used just for Exceptions) and all other PHP errors which are FATAL and will stop the execution ; For WARNING / NOTICE type errors we just want to log them, not to stop the execution !
		//--
		$message = 'Server Script Execution Halted.'."\n".'See the App Error Log for details';
		if((string)SMART_ERROR_HANDLER == 'dev') { // if dev mode
			$message .= ':';
			$script = (string) SMART_ERROR_LOGDIR.SMART_ERROR_LOGFILE;
			$appenv = (string) SMART_FRAMEWORK_ENV;
		} else {
			$message .= '.';
		} //end if
		//--
		$errlogo = '<img src="'.smart__framework__err__handler__get__basepath().'lib/framework/img/sign-crit-error.svg" alt="[!]" title="[!]">';
		if(defined('SMART_FRAMEWORK_RUNTIME_MODE')) {
			if((string)SMART_FRAMEWORK_RUNTIME_MODE == 'task') { // {{{SYNC-RUNTIME-MODE-OVERRIDE-TASK}}}
				$errlogo = '[!]'; // add support for appcode pack/unpack, there are no images there
				$smart_____framework_____last__error = ''; // hide errors if explicit set so (make sense in production environments)
				$smart_____framework_____is_html_last__error = false;
			} //end if
		} //end if
		//--
		if(!headers_sent()) {
			@http_response_code(500); // try, if not headers send
		} //end if
		die('<!DOCTYPE html>'."\n".'<!-- Smart.Framework @ Smart Error Reporting / Smart Error Handler :: '.date('Y-m-d H:i:s O').' -->'."\n".'<html>'."\n".'<head><meta charset="'.SMART_FRAMEWORK_CHARSET.'"><title>!</title><link rel="icon" href="data:,"><style>hr {border: none 0; border-top: 1px solid #ECECEC; height: 1px; }</style></head>'."\n".'<body>'."\n".'<br><div><center><div style="min-width:300px; max-width:75vw; border: 1px solid #ECECEC; margin-top:10px; margin-bottom:10px;"><table cellpadding="4" style="max-width:70vw;"><tr valign="top"><td width="32">'.$errlogo.'</td><td>&nbsp;</td><td><b><span style="font-size:1.25rem">'.'Application Runtime Error'.($appenv ? ' ('.$appenv.')' : '').' @ '.SMART_ERROR_AREA.' [#'.$errno.']:</span><br>'.'</b><i>'.nl2br((string)htmlspecialchars((string)$message, ENT_HTML401 | ENT_COMPAT | ENT_SUBSTITUTE, (string)SMART_FRAMEWORK_CHARSET, true), false).($script ? '<br><span style="color:#778899;">'.htmlspecialchars((string)$script, ENT_HTML401 | ENT_COMPAT | ENT_SUBSTITUTE, (string)SMART_FRAMEWORK_CHARSET, true).'</span>' : '').'</i></td></tr></table></div><br>'.($smart_____framework_____last__error ? "\n".'<!-- START: Last ERR Message --><div style="max-width:70vw; padding:5px; border: 1px solid #F0F0F0; border-radius:3px;"><span style="color:#222222; font-style:italic; font-weight:bold; font-size:3rem;"><span style="color:#4e5a92;">PHP '.PHP_VERSION.'</span> Last ERROR:</span><br><br>'.$smart_____framework_____last__error.'<br></div><br><br><hr size="1">'."\n".'<!-- #END: Last ERR Message -->'."\n" : '').'</center></div>'."\n".'</body>'."\n".'</html>');
		//--
	} //end if else
	//--
}, E_ALL & ~E_NOTICE); // error reporting for logging
//==
set_exception_handler(function($exception) { // no type for EXCEPTION to be PHP 7+ compatible
	//--
	global $smart_____framework_____last__error;
	global $smart_____framework_____is_html_last__error;
	//--
	//print_r($exception);
	//print_r($exception->getTrace());
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
		if(defined('SMART_FRAMEWORK_RUNTIME_MODE')) {
			if((string)SMART_FRAMEWORK_RUNTIME_MODE == 'task') { // {{{SYNC-RUNTIME-MODE-OVERRIDE-TASK}}}
				$hide_last_err = true;
			} //end if
		} //end if else
		if(((string)SMART_ERROR_HANDLER != 'log') OR (SMART_FRAMEWORK_DEBUG_MODE === true)) { // if not log or debug is on
			$hide_last_err = true;
		} //end if
		if($hide_last_err !== false) { // add support for appcode pack/unpack, there are no images there so hiding the last error message will hide all sub-images
			if($smart_____framework_____is_html_last__error !== true) {
				$smart_____framework_____last__error = (string) htmlspecialchars((string)$smart_____framework_____last__error, ENT_HTML401 | ENT_COMPAT | ENT_SUBSTITUTE, (string)SMART_FRAMEWORK_CHARSET, true);
				$smart_____framework_____is_html_last__error = true;
			} //end if
			$smart_____framework_____last__error .= (string) ini_get('error_prepend_string')."\n".'<b><span style="color:#C2203F;"><i>Exception [#'.$code.'] / '.$exid.'</i></span><br><br><div style="font-size:1.5rem;">Error-Message: '.htmlspecialchars((string)$message, ENT_HTML401 | ENT_COMPAT | ENT_SUBSTITUTE, (string)SMART_FRAMEWORK_CHARSET, true).'</div></b><div style="color:#555555; padding:5px; margin:5px;">'.htmlspecialchars((string)$details, ENT_HTML401 | ENT_COMPAT | ENT_SUBSTITUTE, (string)SMART_FRAMEWORK_CHARSET, true).'</div>'.trim((string)ini_get('error_append_string'))."\n"; // fix for PHP 7+
		} //end if
		//--
		if(SMART_FRAMEWORK_DEBUG_MODE === true) { // if debug
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
if(((string)SMART_ERROR_HANDLER == 'log') AND (SMART_FRAMEWORK_DEBUG_MODE !== true)) { // if log and not debug :: hide errors and just log them
	ini_set('ignore_repeated_errors', '1'); // ignore repeated errors in the same file on the same line
} else { // dev or log+debug
	ini_set('ignore_repeated_errors', '0'); // do not ignore repeated errors
	ini_set('error_prepend_string', '<div style="text-align:left!important;"><style type="text/css">* { font-family: arial,sans-serif; font-smooth: always; }</style> &nbsp; <span style="font-size:3rem; color:#ED2839;"><img width="64" height="64" src="'.smart__framework__err__handler__get__basepath().'lib/framework/img/php-logo.svg"> <b>Code Execution FAILED</b></span> <img align="right" width="64" height="64" src="'.smart__framework__err__handler__get__basepath().'lib/framework/img/sign-error.svg"><div><hr size="1"><pre style="white-space:pre-wrap;overflow-x:auto;">');
	ini_set('error_append_string', '</pre></div><br><div style="color:#888888; text-align:right;"><small>'.date('Y-m-d H:i:s O').'</small><hr size="1"></div><div title="Powered by Smart.Framework" style="cursor:help;"><center><img width="64" height="64" src="'.smart__framework__err__handler__get__basepath().'lib/framework/img/sf-logo.svg"></center></div></div>');
} //end if else
ini_set('html_errors', '0'); // display errors in TEXT format
ini_set('log_errors', '1'); // log always the errors
ini_set('log_errors_max_len', 65535); // max size of one error to log 16k
ini_set('error_log', (string)SMART_ERROR_LOGDIR.SMART_ERROR_LOGFILE); // error log file
//==
register_shutdown_function(function(){
	//--
	$error = error_get_last();
	if(is_array($error) && isset($error['type'])) {
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
				$log_message = (string) "\n".
					'==================================='."\n".
					'PHP '.PHP_VERSION.' [SMART-ERR-HANDLER:'.strtoupper((string)SMART_FRAMEWORK_ENV).'] #0 [APP-SHUTDOWN-ERROR] :: Execution COMPLETED ! @ '.date('Y-m-d H:i:s O')."\n".
					'-----------------'."\n".
					'HTTP-METHOD: '.(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '').' # '.'CLIENT: '.trim((string)(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '').' ; '.(isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : '').' ; '.(isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR']: ''), '; ').' @ '.(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '')."\n".
					'URI: ['.SMART_ERROR_AREA.'] @ '.(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '')."\n".
					'-----------------'."\n".
					'Script: '.$error['file']."\n".
					'Line number: '.$error['line']."\n".
					'-----------------'."\n".
					'Error-Message: '.$error['message']."\n".
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
				} //end if else
				break;
			default:
				// don't handle
		} //end switch
	} //end if
	//--
});
//==
/**
 * Function Error Handler Get BasePath
 * @access 		private
 * @internal
 */
function smart__framework__err__handler__get__basepath() {
	//--
	$prefix = (string) trim((string)dirname((string)(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '')));
	//--
	if(((string)$prefix == '') || ((string)$prefix == '/') || ((string)$prefix == '\\') || ((string)$prefix == '.') || ((string)$prefix == '..')) {
		$prefix = ''; // no prefix
	} else {
		$prefix .= '/'; // fix: add a trailing slash
	} //end if
	//--
	return (string) $prefix;
	//--
} //END FUNCTION
//==
/**
 * Function: Get the Error Handler log folder ; it must be .htaccess protected
 * @hints 		The phperrors-idx-yyyy-mm-dd@hh.log and phperrors-adm-yyyy-mm-dd@hh.log error log files will be written into this folder
 * @access 		private
 * @internal
 */
function smart__framework__err__handler__get__absolute_logpath(string $suffix_path) {
	//--
	// the PHP Bug #31570 (not fixed since a very long time) : cannot access relative paths after destruct of main executors started, ex: handlers registered with register_shutdown_function()
	//--
	// INFO: this must be a full / absolute path (not a relative path) because register_shutdown_function() handlers may not always work with relative paths
	// EXAMPLE: need to log to tmp/logs after majority of objects have been destroyed and no more detection of relative path
	// NOTICE: this converts windows path from using backslash to using slash
	//--
	$suffix_path = (string) trim((string)$suffix_path);
	//--
	$unix_regex = '/^[_a-zA-Z0-9\-\.@#\/]+$/'; // regex for linux/unix ; ; {{{SYNC-CHK-SAFE-FILENAME}}} with extra `/`
	$windows_regex = '/^[_a-zA-Z0-9\-\.@#\/\:]+$/'; // regex for windows, after converting backslashes to normal slashes ; {{{SYNC-CHK-SAFE-FILENAME}}} with extra `/` and `:`
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
	$path = (string) trim((string)realpath('./')); // get the current absolute path of current running folder (this will be run from index.php or admin.php which is outside lib/ ... so this scenario is considered)
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
	//--
	$max_path_len = (int) ceil(PHP_MAXPATHLEN * 0.33); // the path to the Smart.Framework installation should not be longer than 33% of max path length supported by OS
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
