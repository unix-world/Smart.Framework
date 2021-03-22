<?php
// AppCodePack - Release Manager: a PHP, JS and CSS Optimizer + NetArchive Packer
// (c) 2006-2021 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

//===== CODE OPTIMIZATIONS INFO:
// The AppCodePack optimize will process the following type of source code (php/js/css):
// 		PHP-Scripts: .php
// 		JS-Scripts: .js (excluding: .inc.js)
// 		CSS-StyleSheets: .css
// It will Not Optimize, but Copy + (*Lint) the php/js/css files that contain in the header (first 255 bytes) this comment: [@[#[!NO-STRIP!]#]@]
// Will Skip to Copy the php/js/css files that contain in the header (first 255 bytes) this comment: [@[#[!SF.DEV-ONLY!]#]@]
// For the rest of files and dirs includded in src/ folder it will:
// 		Copy all dirs, including empty ones
// 		Copy all files, including empty ones
// 		Skip all dirs contaning the file: sf-dev-only.nopack
//=====

//##############################################################################
//############### RUNTIME: DO NOT MODIFY CODE BELOW !!!
//##############################################################################

//== customize per app
//--
define('APP_ERR_LOGFILE', '---AppCodePack-PHP-Errors---.log'); 		// path to error log
define('APP_MAX_RUN_TIMEOUT', '2105');								// max execution time in seconds :: differs, ~ 35 min (for pack)
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
define('APPCODEPACK_UNPACK_TESTONLY', true); 												// default is TRUE ; set to FALSE for archive full test + uncompress + replace ; required just for AppCodePack (not for AppCodeUnpack)
define('APPCODE_REGEX_STRIP_MULTILINE_CSS_COMMENTS', "`\/\*(.+?)\*\/`ism"); 				// regex for remove multi-line comments (by now used just for CSS ...) ; required just for AppCodePack (not for AppCodeUnpack)
//==
define('APPCODEPACK_VERSION', 'v.20210312.1255'); 											// current version of this script
define('APPCODEUNPACK_VERSION', (string)APPCODEPACK_VERSION); 								// current version of unpack script (req. for unpack class)
//==
header('Cache-Control: no-cache'); 															// HTTP 1.1
header('Pragma: no-cache'); 																// HTTP 1.0
header('Expires: '.gmdate('D, d M Y', @strtotime('-1 year')).' '.date('H:i:s').' GMT'); 	// HTTP 1.0
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
//==
AppCodePack::Run(); // outputs directly
//==

//###############
//############### END EXECUTION / INITS
//###############


final class AppCodePack {


	public static function Run() {

		//--	1. *Optional* create the 'appcodepack.ini' in the same folder as this script ; if 'appcodepack.ini' does not exists this script will use the INTERNAL methods (no minify but only strip comments in PHP / JS / CSS ...)
		//--	2. Create a src Folder under your RELEASE folder, copy this script there and put all your source-code under a sub-folder named src/ : all your project files like PHP, JS, CSS and the other files
		//--	3. Run this script as: http(s)://127.0.0.1/RELEASE/appcodepack.php
					// * all the files optimizations for PHP / JS / CSS or just copy (for the rest of files) are checked against errors
					// * thus you will see a RED / HIGHLIGTED error message printed in your browser if some errors occur when doing the optimisations ...
					// * depending upon your choosen optimization strategy you will find a new sub-Folder into RELEASE as: src.APPCODEPACK.{{STRATEGY-DETAILS}}, where all the PHP, JS and CSS will be optimized for online distribution and the rest of files will be just copied

		//===== SETTINGS
		$appcode_ini_file_options = [
			'APPCODEPACK_APP_ID' => false,
			'APPCODEPACK_APP_SECRET' => false,
			'APPCODEPACK_APP_UNPACK_URL' => false,
			'APPCODEPACK_APP_UNPACK_USER' => false,
			'APPCODEPACK_APP_UNPACK_PASSWORD' => false,
			'APPCODEPACK_COMPRESS_UTILITY_TYPE' => false,
			'APPCODEPACK_COMPRESS_UTILITY_BIN' => false,
			'APPCODEPACK_COMPRESS_UTILITY_MODULE_JS' => false,
			'APPCODEPACK_COMPRESS_UTILITY_OPTIONS_JS' => false,
			'APPCODEPACK_COMPRESS_UTILITY_MODULE_CSS' => false,
			'APPCODEPACK_COMPRESS_UTILITY_OPTIONS_CSS' => false,
			'APPCODEPACK_LINT_PHP_UTILITY_BIN' => false,
			'APPCODEPACK_LINT_NODEJS_UTILITY_BIN' => false
		];
		$appcode_ini_file_parse = array();
		if((AppPackUtils::is_type_file('appcodepack.ini')) AND (AppPackUtils::have_access_read('appcodepack.ini'))) {
			//--
			$appcode_ini_file_parse = (array) @parse_ini_file('appcodepack.ini', false, INI_SCANNER_RAW);
			//--
			foreach($appcode_ini_file_parse as $appcode_ini_file_pkey => $appcode_ini_file_pval) {
				if(array_key_exists((string)$appcode_ini_file_pkey, (array)$appcode_ini_file_options)) {
					if((string)$appcode_ini_file_pkey === 'APPCODEPACK_COMPRESS_UTILITY_TYPE') {
						define('APPCODEPACK_STRATEGY', strtoupper((string)$appcode_ini_file_pval));
						switch((string)APPCODEPACK_STRATEGY) {
							case 'INTERNAL': // made by this app
								$appcode_ini_file_pval = 'N';
								break;
							case 'NODEJS+UGLIFY': // nodejs+uglify(js|css)
								$appcode_ini_file_pval = 'U';
								break;
							case 'JAVA+GC': // java+google.closure.compiler+spreadsheet
								$appcode_ini_file_pval = 'G';
								break;
							case 'JAVA+YUI': // java+yahoo.yui.compressor
								$appcode_ini_file_pval = 'Y';
								break;
							case 'JAVA+GCYUI': // java+google.closure.compiler(js)+yahoo.yui.compressor(css)
								$appcode_ini_file_pval = 'X';
								break;
							default:
								AppPackUtils::raise_error('A required INI Key contains an invalid value from appcodepack.ini : APPCODEPACK_COMPRESS_UTILITY_TYPE: '.$appcode_ini_file_pval);
								return;
						} //end switch
					} //end if
					if(!defined((string)$appcode_ini_file_pkey)) {
						define((string)$appcode_ini_file_pkey, (string)$appcode_ini_file_pval);
						if(defined((string)$appcode_ini_file_pkey)) {
							$appcode_ini_file_options[(string)$appcode_ini_file_pkey] = true;
						} else {
							AppPackUtils::raise_error('Failed to define an INI Key from appcodepack.ini : '.(string)$appcode_ini_file_pkey);
							return;
						} //end if
					} else {
						AppPackUtils::raise_error('INI Key already defined from appcodepack.ini : '.(string)$appcode_ini_file_pkey);
						return;
					} //end if else
				} else {
					AppPackUtils::raise_error('Invalid INI Key detected in appcodepack.ini : '.(string)$appcode_ini_file_pkey);
					return;
				} //end if else
			} //end foreach
			//--
			switch((string)APPCODEPACK_COMPRESS_UTILITY_TYPE) {
				case 'N':
					foreach(['APPCODEPACK_COMPRESS_UTILITY_TYPE', 'APPCODEPACK_APP_ID', 'APPCODEPACK_APP_SECRET', 'APPCODEPACK_LINT_PHP_UTILITY_BIN', 'APPCODEPACK_LINT_NODEJS_UTILITY_BIN'] as $appcode_ini_file_pkey => $appcode_ini_file_pval) { // {{{SYNC-PACK-INTERNAL-DEFS}}}
						if($appcode_ini_file_options[(string)$appcode_ini_file_pval] !== true) {
							AppPackUtils::raise_error('A required INI Key was not defined from appcodepack.ini : '.(string)$appcode_ini_file_pval);
							return;
						} //end if
					} //end foreach
					break;
				case 'U':
				case 'G':
				case 'Y':
				case 'X':
					foreach($appcode_ini_file_options as $appcode_ini_file_pkey => $appcode_ini_file_pval) {
						if($appcode_ini_file_pval !== true) {
							AppPackUtils::raise_error('A required INI Key was not defined from appcodepack.ini : '.(string)$appcode_ini_file_pkey);
							return;
						} //end if
					} //end foreach
					if((string)APPCODEPACK_COMPRESS_UTILITY_BIN == '') {
						AppPackUtils::raise_error('A required INI Key is empty from appcodepack.ini : APPCODEPACK_COMPRESS_UTILITY_BIN');
						return;
					} //end if
					if((string)APPCODEPACK_COMPRESS_UTILITY_MODULE_JS == '') {
						AppPackUtils::raise_error('A required INI Key is empty from appcodepack.ini : APPCODEPACK_COMPRESS_UTILITY_MODULE_JS');
						return;
					} //end if
					if((string)APPCODEPACK_COMPRESS_UTILITY_MODULE_CSS == '') {
						AppPackUtils::raise_error('A required INI Key is empty from appcodepack.ini : APPCODEPACK_COMPRESS_UTILITY_MODULE_CSS');
						return;
					} //end if
					break;
				default:
					AppPackUtils::raise_error('A required INI Key was parsed to an invalid value from appcodepack.ini : APPCODEPACK_COMPRESS_UTILITY_TYPE: '.APPCODEPACK_COMPRESS_UTILITY_TYPE);
					return;
			} //end switch
			//--
			//echo 'Utility-Type: '.APPCODEPACK_COMPRESS_UTILITY_TYPE.'<hr>'; echo 'Minify-Strategy: '.APPCODEPACK_STRATEGY.'<hr>'; echo '<pre>'; print_r($appcode_ini_file_parse); print_r($appcode_ini_file_options); echo '</pre>'; die();
			//--
			unset($appcode_ini_file_parse);
			unset($appcode_ini_file_pkey);
			unset($appcode_ini_file_pval);
			//--
		} else { // {{{SYNC-PACK-INTERNAL-DEFS}}}
			//--
			define('APPCODEPACK_STRATEGY', 'INTERNAL');
			//--
			define('APPCODEPACK_COMPRESS_UTILITY_TYPE', 'N');
			define('APPCODEPACK_APP_ID', '-----UNDEF-----');
			define('APPCODEPACK_APP_SECRET', '');
			// APPCODEPACK_LINT_PHP_UTILITY_BIN 		:: must be undefined
			// APPCODEPACK_LINT_NODEJS_UTILITY_BIN 		:: must be undefined
			//--
		} //end if
		//--
		unset($appcode_ini_file_options);
		//--
		if(!defined('APPCODEPACK_APP_ID')) {
			AppPackUtils::raise_error('App Internal Error : APPCODEPACK_APP_ID must be defined and was not !');
			return;
		} //end if
		if((string)trim((string)APPCODEPACK_APP_ID) == '') { // {{{SYNC-VALID-APPCODEPACK-APPID}}}
			AppPackUtils::raise_error('App Internal Error : APPCODEPACK_APP_ID is Empty !');
			return;
		} //end if
		if(strlen((string)APPCODEPACK_APP_ID) < 5) { // {{{SYNC-VALID-APPCODEPACK-APPID}}}
			AppPackUtils::raise_error('App Internal Error : APPCODEPACK_APP_ID is Too Short (<5) characters: '.APPCODEPACK_APP_ID);
			return;
		} //end if
		if(strlen((string)APPCODEPACK_APP_ID) > 63) { // {{{SYNC-VALID-APPCODEPACK-APPID}}}
			AppPackUtils::raise_error('App Internal Error : APPCODEPACK_APP_ID is Too Long (>63) characters: '.APPCODEPACK_APP_ID);
			return;
		} //end if
		if(!preg_match('/^[_a-zA-Z0-9\-\.@]+$/', (string)APPCODEPACK_APP_ID)) { // {{{SYNC-VALID-APPCODEPACK-APPID}}} allow safe path characters except: # / which are reserved
			AppPackUtils::raise_error('App Internal Error : APPCODEPACK_APP_ID Contains Invalid Characters: '.APPCODEPACK_APP_ID);
			return;
		} //end if
		//--
		if(!defined('APPCODEPACK_APP_SECRET')) {
			AppPackUtils::raise_error('App Internal Error : APPCODEPACK_APP_SECRET must be defined and was not !');
			return;
		} //end if
		// no check if APPCODEPACK_APP_SECRET is empty (only unpack enforces this check) ; but anyway, if non-empty do check on lengths
		if((string)trim((string)APPCODEPACK_APP_SECRET) != '') { // {{{SYNC-VALID-APPCODEPACK-APPSECRET}}}
			if(strlen((string)APPCODEPACK_APP_SECRET) < 5) { // {{{SYNC-VALID-APPCODEPACK-APPSECRET}}}
				AppPackUtils::raise_error('App Internal Error : APPCODEPACK_APP_SECRET is Too Short (<40) characters: '.APPCODEPACK_APP_SECRET);
				return;
			} //end if
			if(strlen((string)APPCODEPACK_APP_SECRET) > 128) { // {{{SYNC-VALID-APPCODEPACK-APPSECRET}}}
				AppPackUtils::raise_error('App Internal Error : APPCODEPACK_APP_SECRET is Too Long (>128) characters: '.APPCODEPACK_APP_SECRET);
				return;
			} //end if
		} //end if
		//--
		if(defined('APPCODEPACK_APP_HASH_ID')) {
			AppPackUtils::raise_error('App Internal Error : APPCODEPACK_APP_HASH_ID was defined and must not !');
			return;
		} //end if
		if((string)trim((string)APPCODEPACK_APP_SECRET) != '') { // {{{SYNC-VALID-APPCODEPACK-APPSECRET}}}
			define('APPCODEPACK_APP_HASH_ID', sha1((string)APPCODEPACK_APP_ID.'*AppCode(Un)Pack*'.(string)APPCODEPACK_APP_SECRET)); // {{{SYNC-VALID-APPCODEPACK-APPHASH}}}
		} else {
			define('APPCODEPACK_APP_HASH_ID', '');
		} //end if else
		//--
		if(!defined('APPCODEPACK_STRATEGY')) {
			AppPackUtils::raise_error('App Internal Error : APPCODEPACK_STRATEGY must be defined and was not !');
			return;
		} //end if
		//--
		if(!defined('APPCODEPACK_COMPRESS_UTILITY_TYPE')) {
			AppPackUtils::raise_error('App Internal Error : APPCODEPACK_COMPRESS_UTILITY_TYPE must be defined and was not !');
			return;
		} //end if
		//--
		//=====

		//--
		define('APPCODEPACK_PROCESS_SOURCE_DIR', 'src');
		//--
		if((string)APPCODEPACK_STRATEGY !== 'INTERNAL') {
			define('APPCODEPACK_PROCESS_OPTIMIZATIONS_MODE', 'minify');
			define('APPCODEPACK_PROCESS_OPTIMIZATIONS_DIR', (string)APPCODEPACK_PROCESS_SOURCE_DIR.'.APPCODEPACK.'.'MINIFIED-'.APPCODEPACK_COMPRESS_UTILITY_TYPE);
		} else {
			define('APPCODEPACK_PROCESS_OPTIMIZATIONS_MODE', 'comments');
			define('APPCODEPACK_PROCESS_OPTIMIZATIONS_DIR', (string)APPCODEPACK_PROCESS_SOURCE_DIR.'.APPCODEPACK.'.'STRIP-NOCOMMENTS');
		} //end if else
		//--
		define('APPCODEPACK_MARKER_OPTIMIZATIONS', '(PHP'.(defined('APPCODEPACK_LINT_PHP_UTILITY_BIN') ? '+Lint' : '').' / JS'.(defined('APPCODEPACK_LINT_NODEJS_UTILITY_BIN') ? '+Lint' : '').' / CSS)');
		//--

		//===== Main code execution
		//--
		$img_logo_app = 'data:image/svg+xml;base64,PHN2ZyB4bWxuczpkYz0iaHR0cDovL3B1cmwub3JnL2RjL2VsZW1lbnRzLzEuMS8iIHhtbG5zOmNjPSJodHRwOi8vY3JlYXRpdmVjb21tb25zLm9yZy9ucyMiIHhtbG5zOnN2Zz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgdmVyc2lvbj0iMS4xIiB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiB2aWV3Qm94PSIwIDAgMTI4IDEyOCIgaWQ9ImFwcGNvZGVwYWNrLWxvZ28iPgogIDxkZWZzIGlkPSJkZWZzMTQiIC8+CiAgPGcgdHJhbnNmb3JtPSJtYXRyaXgoMCwxLC0xLDAsMTI4LC0zLjg4ODVlLTYpIiBpZD0iZzM3NTUiPgogICAgPGcgdHJhbnNmb3JtPSJtYXRyaXgoMC4xMjkwMTgzOSwwLDAsMC4xMTYzMjQxNiwtMTMuMzAxMzY4LDExOC45MDUpIiBpZD0iZzI5OTMiPgogICAgICA8cGF0aCBkPSJNIDEwMzUuNiwtMTkyLjcgNjE3LjUsNDMuOCB2IC0xODQuMiBsIDI2MC41LC0xNDMuMyAxNTcuNiw5MSB6IG0gMjguNiwtMjUuOSB2IC00OTQuNiBsIC0xNTMsODguMyBWIC0zMDcgbCAxNTMsODguNCB6IG0gLTkwMS41LDI1LjkgNDE4LjEsMjM2LjUgdiAtMTg0LjIgbCAtMjYwLjUsLTE0My4zIC0xNTcuNiw5MSB6IG0gLTI4LjYsLTI1LjkgdiAtNDk0LjYgbCAxNTMsODguMyBWIC0zMDcgbCAtMTUzLDg4LjQgeiBNIDE1MiwtNzQ1LjIgNTgwLjgsLTk4Ny44IHYgMTc4LjEgbCAtMjc0LjcsMTUxLjEgLTIuMSwxLjIgLTE1MiwtODcuOCB6IG0gODk0LjMsMCAtNDI4LjgsLTI0Mi42IHYgMTc4LjEgbCAyNzQuNywxNTEuMSAyLjEsMS4yIDE1MiwtODcuOCB6IiBpZD0icGF0aDgiIHN0eWxlPSJmaWxsOiNkY2RjZGM7ZmlsbC1vcGFjaXR5OjEiIC8+CiAgICAgIDxwYXRoIGQ9Im0gNTgwLjgsLTE4Mi4zIC0yNTcsLTE0MS4zIDAsLTI4MCAyNTcsMTQ4LjQgeiBtIDM2LjcsMCAyNTcsLTE0MS4zIDAsLTI4MCAtMjU3LDE0OC40IHogTSAzNDEuMiwtNjM2IDU5OS4yLC03NzcuOSA4NTcuMiwtNjM2IDY2MS4yNzQ4NCwtNTIyLjg0OTQzIDU5OS4yLC00ODcgeiIgaWQ9InBhdGgxMCIgc3R5bGU9ImZpbGw6Izc3ODg5OTtmaWxsLW9wYWNpdHk6MSIgLz4KICAgIDwvZz4KICAgIDxwYXRoIGQ9Im0gMzIuMjc1LDI5Ljk2NjQ3NCBxIDAsLTEuMjA1NzA3IDEuNTA2ODQsLTIuMTI3NzE4IGwgMy4wNTQzOSwtMS43NzMwOTggcSAxLjU0NzU2LC0wLjg5ODM2OSAzLjcwNiwtMC44OTgzNjkgMi4xOTkxNjksMCAzLjY2NTI4LDAuODk4MzY5IGwgMTEuOTczMjM1LDYuOTI2OTAzIDAsLTE2LjY0MzQ3OCBxIDAsLTEuMjI5MzQ5IDEuNTI3MTk4LC0xLjk5NzY5MSAxLjUyNzE5OCwtMC43NjgzNDIgMy42ODU2MzksLTAuNzY4MzQyIGwgNS4yMTI4MzYsMCBxIDIuMTU4NDQxLDAgMy42ODU2MzksMC43NjgzNDIgMS41MjcxOTgsMC43NjgzNDIgMS41MjcxOTgsMS45OTc2OTEgbCAwLDE2LjY0MzQ3OCAxMS45NzMyMzUsLTYuOTI2OTAzIHEgMS40NjYxMTEsLTAuODk4MzY5IDMuNjY1Mjc2LC0wLjg5ODM2OSAyLjE5OTE2NiwwIDMuNjY1Mjc2LDAuODk4MzY5IGwgMy4wNTQzOTcsMS43NzMwOTggcSAxLjU0NzU2MSwwLjg5ODM3IDEuNTQ3NTYxLDIuMTI3NzE4IDAsMS4yNTI5ODkgLTEuNTQ3NTYxLDIuMTUxMzU5IEwgNjcuNjY1Mjc2LDQ3LjUwODMyMiBRIDY2LjIzOTg5MSw0OC4zODMwNSA2NCw0OC4zODMwNSBxIC0yLjE5OTE2NiwwIC0zLjcwNjAwMSwtMC44NzQ3MjggTCAzMy43ODE4NCwzMi4xMTc4MzMgUSAzMi4yNzUsMzEuMTk1ODIyIDMyLjI3NSwyOS45NjY0NzQgeiIgaWQ9InBhdGgzMDI5IiBzdHlsZT0iZmlsbDojZmY5OTAwO2ZpbGwtb3BhY2l0eToxIiAvPgogIDwvZz4KPC9zdmc+';
		//--
		$img_logo_php = 'data:image/svg+xml;base64,PHN2ZyB4bWxuczpkYz0iaHR0cDovL3B1cmwub3JnL2RjL2VsZW1lbnRzLzEuMS8iIHhtbG5zOmNjPSJodHRwOi8vY3JlYXRpdmVjb21tb25zLm9yZy9ucyMiIHhtbG5zOnN2Zz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgdmVyc2lvbj0iMS4xIiB3aWR0aD0iMTI4IiBoZWlnaHQ9IjEyOCIgdmlld0JveD0iMCAwIDEyMi44OCAxMjIuODgiIGlkPSJwaHAtbG9nby1zdmciPgogIDxkZWZzIGlkPSJkZWZzMTIiIC8+CiAgPGcgdHJhbnNmb3JtPSJtYXRyaXgoMC40NTMzMzMzMywwLDAsMC41OTAxNzQ1OCwzLjk5MzYsMTkuNTU4NCkiIGlkPSJnNCIgc3R5bGU9ImZpbGwtcnVsZTpldmVub2RkIj4KICAgIDxlbGxpcHNlIGN4PSIxMjgiIGN5PSI2Ni42Mjk5OTciIHJ4PSIxMjgiIHJ5PSI2Ni42Mjk5OTciIGlkPSJlbGxpcHNlNiIgc3R5bGU9ImZpbGw6IzczNzQ5NSIgLz4KICAgIDxwYXRoIGQ9Ik0gMzUuOTQ1LDEwNi4wODIgNDkuOTczLDM1LjA2OCBIIDgyLjQxIGMgMTQuMDI3LDAuODc3IDIxLjA0MSw3Ljg5IDIxLjA0MSwyMC4xNjUgMCwyMS4wNDEgLTE2LjY1NywzMy4zMTUgLTMxLjU2MiwzMi40MzggSCA1Ni4xMSBsIC0zLjUwNywxOC40MTEgSCAzNS45NDUgeiBNIDU5LjYxNiw3NC41MjEgNjQsNDguMjE5IGggMTEuMzk3IGMgNi4xMzcsMCAxMC41MiwyLjYzIDEwLjUyLDcuODkgLTAuODc2LDE0LjkwNSAtNy44OSwxNy41MzUgLTE1Ljc4LDE4LjQxMiBoIC0xMC41MiB6IG0gNDAuNTc2LDEzLjE1IDE0LjAyNywtNzEuMDEzIGggMTYuNjU4IGwgLTMuNTA3LDE4LjQxIGggMTUuNzggYyAxNC4wMjgsMC44NzcgMTkuMjg4LDcuODkgMTcuNTM1LDE2LjY1OCBsIC02LjEzNywzNS45NDUgaCAtMTcuNTM0IGwgNi4xMzcsLTMyLjQzOCBjIDAuODc2LC00LjM4NCAwLjg3NiwtNy4wMTQgLTUuMjYsLTcuMDE0IEggMTI0Ljc0IGwgLTcuODksMzkuNDUyIGggLTE2LjY1OCB6IG0gNTMuMjMzLDE4LjQxMSAxNC4wMjcsLTcxLjAxNCBoIDMyLjQzOCBjIDE0LjAyOCwwLjg3NyAyMS4wNDIsNy44OSAyMS4wNDIsMjAuMTY1IDAsMjEuMDQxIC0xNi42NTgsMzMuMzE1IC0zMS41NjIsMzIuNDM4IGggLTE1Ljc4MSBsIC0zLjUwNywxOC40MTEgaCAtMTYuNjU3IHogbSAyMy42NywtMzEuNTYxIDQuMzg0LC0yNi4zMDIgaCAxMS4zOTggYyA2LjEzNywwIDEwLjUyLDIuNjMgMTAuNTIsNy44OSAtMC44NzYsMTQuOTA1IC03Ljg5LDE3LjUzNSAtMTUuNzgsMTguNDEyIGggLTEwLjUyMSB6IiBpZD0icGF0aDgiIHN0eWxlPSJmaWxsOiNGRkZGRkYiIC8+CiAgPC9nPgo8L3N2Zz4=';
		$img_logo_js  = 'data:image/svg+xml;base64,PHN2ZyB4bWxuczpkYz0iaHR0cDovL3B1cmwub3JnL2RjL2VsZW1lbnRzLzEuMS8iIHhtbG5zOmNjPSJodHRwOi8vY3JlYXRpdmVjb21tb25zLm9yZy9ucyMiIHhtbG5zOnN2Zz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgdmVyc2lvbj0iMS4xIiB3aWR0aD0iMTI4IiBoZWlnaHQ9IjEyOCIgdmlld0JveD0iMCAwIDYwIDYwIiBpZD0iamF2YXNjcmlwdC1sb2dvLXN2ZyI+CiAgPGRlZnMgaWQ9ImRlZnMxMCIgLz4KICA8ZyBpZD0idGV4dDM3NTciIHN0eWxlPSJmaWxsOiMwMDAwMDA7ZmlsbC1vcGFjaXR5OjE7c3Ryb2tlOm5vbmUiPgogICAgPGcgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMCwwLjM4MTM1NTcpIiBpZD0iZzM3NjciPgogICAgICA8cGF0aCBkPSJNIDQ5LjA4ODEsNDQuMzAzODczIEMgNTQuMzMyNCw0MC4yMzQxMDUgNTcsMzUuMzUwOCA1NywyOS42MTg2NDQgNTcsMjMuODg2NDg5IDU0LjMzMjQsMTkuMDAzMTgzIDQ5LjA4ODEsMTQuOTMzNDE1IDQzLjc5ODgsMTAuODk5NjUxIDM3LjQ1MTEsOC44NDc0NTc2IDMwLDguODQ3NDU3NiBjIC03LjQ1MTEsMCAtMTMuNzk4OCwyLjA1MjE5MzQgLTE5LjA4OSw2LjA4NTk1NzQgQyA1LjY2NzYsMTkuMDAzMTgzIDMsMjMuODg2NDg5IDMsMjkuNjE4NjQ0IGMgMCw1LjczMjE1NiAyLjY2NzYsMTAuNjE1NDYxIDcuOTExLDE0LjY4NTIyOSA1LjI5MDIsNC4wNjkwNzYgMTEuNjM3OSw2LjA4NTk1OCAxOS4wODksNi4wODU5NTggNy40NTExLDAgMTMuNzk4OCwtMi4wMTY4ODIgMTkuMDg4MSwtNi4wODU5NTggeiIgaWQ9InBhdGg0IiBzdHlsZT0iZmlsbDojZmZjYzAwO2ZpbGwtb3BhY2l0eToxIiAvPgogICAgICA8ZyB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMC40Njg3NSwwKSIgaWQ9ImcyOTg4Ij4KICAgICAgICA8cGF0aCBkPSJtIDIyLjQzMTY0MSwxNS4zMjE5MjYgMy45NDUzMTIsMCAwLDIwLjg3MDM1NCBjIC04ZS02LDIuNzA0NTggLTAuNjcwNTgsNC42Njc5MDQgLTIuMDExNzE5LDUuODg5OTc4IC0xLjMyODEyOSwxLjIyMjA2NCAtMy40NzAwNTQsMS44MzMwOTkgLTYuNDI1NzgxLDEuODMzMTA1IGwgLTEuNTAzOTA2LDAgMCwtMi41NTQzMjcgMS4yMzA0NjksMCBjIDEuNzQ0NzksLTRlLTYgMi45NzUyNTgsLTAuMzc1NjQgMy42OTE0MDYsLTEuMTI2OTA5IDAuNzE2MTQyLC0wLjc1MTI3NSAxLjA3NDIxNSwtMi4wOTg1NTUgMS4wNzQyMTksLTQuMDQxODQ3IGwgMCwtMjAuODcwMzU0IiBpZD0icGF0aDM3NjIiIC8+CiAgICAgICAgPHBhdGggZD0ibSA0NS4yMDUwNzgsMjEuNjg1MjA2IDAsMi42MTQ0MjkgYyAtMS4wMTU2NDIsLTAuNDAwNjY1IC0yLjA3MDMyOCwtMC43MDExNzQgLTMuMTY0MDYyLC0wLjkwMTUyNyAtMS4wOTM3NjQsLTAuMjAwMzI1IC0yLjIyNjU3NSwtMC4zMDA0OTUgLTMuMzk4NDM4LC0wLjMwMDUwOSAtMS43ODM4NjMsMS40ZS01IC0zLjEyNTAwOCwwLjIxMDM3IC00LjAyMzQzNywwLjYzMTA2OCAtMC44ODU0MjMsMC40MjA3MjcgLTEuMzI4MTMxLDEuMDUxNzk1IC0xLjMyODEyNSwxLjg5MzIwOCAtNmUtNiwwLjY0MTA5NyAwLjMxOTAwNCwxLjE0Njk1NCAwLjk1NzAzMSwxLjUxNzU3IDAuNjM4MDEzLDAuMzYwNjIyIDEuOTIwNTY0LDAuNzA2MjA3IDMuODQ3NjU2LDEuMDM2NzU2IGwgMS4yMzA0NjksMC4yMTAzNTcgYyAyLjU1MjA2OSwwLjQyMDcyMiA0LjM2MTk2MywxLjAxNjczIDUuNDI5Njg3LDEuNzg4MDI4IDEuMDgwNzExLDAuNzYxMjk4IDEuNjIxMDc1LDEuODI4MTAzIDEuNjIxMDk0LDMuMjAwNDIzIC0xLjllLTUsMS41NjI2NSAtMC44MDczMSwyLjc5OTc0NCAtMi40MjE4NzUsMy43MTEyODYgLTEuNjAxNTc3LDAuOTExNTQ0IC0zLjgwODYwNiwxLjM2NzMxNiAtNi42MjEwOTQsMS4zNjczMTYgLTEuMTcxODgzLDAgLTIuMzk1ODQsLTAuMDkwMTUgLTMuNjcxODc1LC0wLjI3MDQ1NyAtMS4yNjMwMjUsLTAuMTcwMjkgLTIuNTk3NjU5LC0wLjQzMDczIC00LjAwMzkwNiwtMC43ODEzMjQgbCAwLC0yLjg1NDgzNiBjIDEuMzI4MTIyLDAuNTMwOTAyIDIuNjM2NzE0LDAuOTMxNTggMy45MjU3ODEsMS4yMDIwMzYgMS4yODkwNTUsMC4yNjA0NDMgMi41NjUwOTYsMC4zOTA2NjQgMy44MjgxMjUsMC4zOTA2NjIgMS42OTI2OTcsMmUtNiAyLjk5NDc3OSwtMC4yMjAzNzIgMy45MDYyNSwtMC42NjExMiAwLjkxMTQ0NCwtMC40NTA3NjEgMS4zNjcxNzMsLTEuMDgxODMgMS4zNjcxODgsLTEuODkzMjA3IC0xLjVlLTUsLTAuNzUxMjY4IC0wLjMzMjA0NiwtMS4zMjcyNDMgLTAuOTk2MDk0LC0xLjcyNzkyOCAtMC42NTEwNTUsLTAuNDAwNjcxIC0yLjA4OTg1NiwtMC43ODYzMjUgLTQuMzE2NDA2LC0xLjE1Njk1OSBsIC0xLjI1LC0wLjIyNTM4MiBjIC0yLjIyNjU2OSwtMC4zNjA2MDMgLTMuODM0NjQsLTAuOTExNTM2IC00LjgyNDIxOSwtMS42NTI4IC0wLjk4OTU4NiwtMC43NTEyNjMgLTEuNDg0Mzc3LC0xLjc3ODAwMSAtMS40ODQzNzUsLTMuMDgwMjE4IC0yZS02LC0xLjU4MjY2NyAwLjcyOTE2NCwtMi44MDQ3MzYgMi4xODc1LC0zLjY2NjIxIDEuNDU4MzI3LC0wLjg2MTQ0MyAzLjUyODYzOCwtMS4yOTIxNzIgNi4yMTA5MzgsLTEuMjkyMTkgMS4zMjgxMTMsMS44ZS01IDIuNTc4MTExLDAuMDc1MTUgMy43NSwwLjIyNTM4MiAxLjE3MTg1OSwwLjE1MDI3MiAyLjI1MjU4NywwLjM3NTY1NCAzLjI0MjE4NywwLjY3NjE0NiIgaWQ9InBhdGgzNzY0IiAvPgogICAgICA8L2c+CiAgICA8L2c+CiAgPC9nPgo8L3N2Zz4=';
		$img_logo_css = 'data:image/svg+xml;base64,PHN2ZyB4bWxuczpkYz0iaHR0cDovL3B1cmwub3JnL2RjL2VsZW1lbnRzLzEuMS8iIHhtbG5zOmNjPSJodHRwOi8vY3JlYXRpdmVjb21tb25zLm9yZy9ucyMiIHhtbG5zOnN2Zz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgdmVyc2lvbj0iMS4xIiB3aWR0aD0iMTI4IiBoZWlnaHQ9IjEyOCIgdmlld0JveD0iMCAwIDYwIDYwIiBpZD0iY3NzLWxvZ28tc3ZnIj4KICA8ZGVmcyBpZD0iZGVmczEwIiAvPgogIDxnIGlkPSJ0ZXh0Mzc1NyIgc3R5bGU9ImZpbGw6IzAwMDAwMDtmaWxsLW9wYWNpdHk6MTtzdHJva2U6bm9uZSI+CiAgICA8ZyB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwLDAuMzgxMzU1NykiIGlkPSJnMzc2NyI+CiAgICAgIDxwYXRoIGQ9Ik0gNDkuMDg4MSw0NC4zMDM4NzMgQyA1NC4zMzI0LDQwLjIzNDEwNSA1NywzNS4zNTA4IDU3LDI5LjYxODY0NCA1NywyMy44ODY0ODkgNTQuMzMyNCwxOS4wMDMxODMgNDkuMDg4MSwxNC45MzM0MTUgNDMuNzk4OCwxMC44OTk2NTEgMzcuNDUxMSw4Ljg0NzQ1NzYgMzAsOC44NDc0NTc2IGMgLTcuNDUxMSwwIC0xMy43OTg4LDIuMDUyMTkzNCAtMTkuMDg5LDYuMDg1OTU3NCBDIDUuNjY3NiwxOS4wMDMxODMgMywyMy44ODY0ODkgMywyOS42MTg2NDQgYyAwLDUuNzMyMTU2IDIuNjY3NiwxMC42MTU0NjEgNy45MTEsMTQuNjg1MjI5IDUuMjkwMiw0LjA2OTA3NiAxMS42Mzc5LDYuMDg1OTU4IDE5LjA4OSw2LjA4NTk1OCA3LjQ1MTEsMCAxMy43OTg4LC0yLjAxNjg4MiAxOS4wODgxLC02LjA4NTk1OCB6IiBpZD0icGF0aDQiIHN0eWxlPSJmaWxsOiNCMjhFNzA7ZmlsbC1vcGFjaXR5OjEiIC8+CiAgICA8L2c+CiAgPC9nPgogIDxnIGlkPSJ0ZXh0Mzc3NSI+CiAgICA8ZyBpZD0iZzI5OTMiPgogICAgICA8cGF0aCBkPSJtIDI0LjM4MjMyNCwyMS41MzQ1NzYgMCwyLjUzNTA5NiBjIC0wLjgwOTM0MSwtMC43NTM3NyAtMS42NzQyMDgsLTEuMzE3MTI0IC0yLjU5NDYwNCwtMS42OTAwNjQgLTAuOTEyNDg4LC0wLjM3MjkwOSAtMS44ODQ0NzIsLTAuNTU5MzcxIC0yLjkxNTk1NSwtMC41NTkzODcgLTIuMDMxMjU4LDEuNmUtNSAtMy41ODY0MzIsMC42MjI4NzkgLTQuNjY1NTI3LDEuODY4NTkxIC0xLjA3OTEwNiwxLjIzNzgwNiAtMS42MTg2NTYsMy4wMzEwMTcgLTEuNjE4NjUzLDUuMzc5NjM5IC0zZS02LDIuMzQwNzA1IDAuNTM5NTQ3LDQuMTMzOTE2IDEuNjE4NjUzLDUuMzc5NjM5IDEuMDc5MDk1LDEuMjM3Nzk1IDIuNjM0MjY5LDEuODU2NjkxIDQuNjY1NTI3LDEuODU2Njg5IDEuMDMxNDgzLDJlLTYgMi4wMDM0NjcsLTAuMTg2NDYxIDIuOTE1OTU1LC0wLjU1OTM4NyAwLjkyMDM5NiwtMC4zNzI5MjIgMS43ODUyNjMsLTAuOTM2Mjc2IDIuNTk0NjA0LC0xLjY5MDA2NCBsIDAsMi41MTEyOTIgYyAtMC44NDEwNzksMC41NzEyOSAtMS43MzM3MTcsMC45OTk3NTYgLTIuNjc3OTE3LDEuMjg1NCAtMC45MzYyOTIsMC4yODU2NDUgLTEuOTI4MTEyLDAuNDI4NDY3IC0yLjk3NTQ2NCwwLjQyODQ2NyAtMi42ODk4MjcsMCAtNC44MDgzNTUsLTAuODIxMjI3IC02LjM1NTU5MSwtMi40NjM2ODQgLTEuNTQ3MjQzLC0xLjY1MDM4NyAtMi4zMjA4NjMsLTMuODk5ODM1IC0yLjMyMDg2MiwtNi43NDgzNTIgLTFlLTYsLTIuODU2NDM0IDAuNzczNjE5LC01LjEwNTg4MiAyLjMyMDg2MiwtNi43NDgzNTIgMS41NDcyMzYsLTEuNjUwMzczIDMuNjY1NzY0LC0yLjQ3NTU2OCA2LjM1NTU5MSwtMi40NzU1ODYgMS4wNjMyMjEsMS44ZS01IDIuMDYyOTc2LDAuMTQyODQgMi45OTkyNjcsMC40Mjg0NjcgMC45NDQyLDAuMjc3NzI3IDEuODI4OTA0LDAuNjk4MjU5IDIuNjU0MTE0LDEuMjYxNTk2IiBpZD0icGF0aDI5ODciIHN0eWxlPSJmaWxsOiNmZmZmZmYiIC8+CiAgICAgIDxwYXRoIGQ9Im0gMzYuNTIyMjE3LDI0Ljk5ODAxNiAwLDIuMDcwOTIzIGMgLTAuNjE4OTA3LC0wLjMxNzM3MSAtMS4yNjE2MDYsLTAuNTU1NDA4IC0xLjkyODEwMSwtMC43MTQxMTEgLTAuNjY2NTEyLC0wLjE1ODY4IC0xLjM1NjgxOSwtMC4yMzgwMjUgLTIuMDcwOTIzLC0wLjIzODAzNyAtMS4wODcwNDEsMS4yZS01IC0xLjkwNDMwMSwwLjE2NjYzNyAtMi40NTE3ODIsMC40OTk4NzggLTAuNTM5NTU0LDAuMzMzMjYzIC0wLjgwOTMzLDAuODMzMTQgLTAuODA5MzI2LDEuNDk5NjMzIC00ZS02LDAuNTA3ODIyIDAuMTk0MzkzLDAuOTA4NTE4IDAuNTgzMTkxLDEuMjAyMDg4IDAuMzg4Nzg5LDAuMjg1NjUzIDEuMTcwMzQ0LDAuNTU5Mzk1IDIuMzQ0NjY1LDAuODIxMjI4IGwgMC43NDk4MTcsMC4xNjY2MjYgYyAxLjU1NTE2NywwLjMzMzI1OSAyLjY1ODA3MSwwLjgwNTM2NiAzLjMwODcxNiwxLjQxNjMyMSAwLjY1ODU1OCwwLjYwMzAzMyAwLjk4Nzg0MywxLjQ0ODA2NCAwLjk4Nzg1NCwyLjUzNTA5NSAtMS4xZS01LDEuMjM3Nzk1IC0wLjQ5MTk1NCwyLjIxNzcxNCAtMS40NzU4MywyLjkzOTc1OCAtMC45NzU5NjEsMC43MjIwNDYgLTIuMzIwODY5LDEuMDgzMDY5IC00LjAzNDcyOSwxLjA4MzA2OSAtMC43MTQxMTcsMCAtMS40NTk5NjUsLTAuMDcxNDEgLTIuMjM3NTQ5LC0wLjIxNDIzMyAtMC43Njk2NTYsLTAuMTM0ODg4IC0xLjU4Mjk0OSwtMC4zNDExODcgLTIuNDM5ODgsLTAuNjE4ODk3IGwgMCwtMi4yNjEzNTIgYyAwLjgwOTMyNCwwLjQyMDUzNCAxLjYwNjc0NywwLjczNzkxNyAyLjM5MjI3MywwLjk1MjE0OCAwLjc4NTUxOCwwLjIwNjMgMS41NjMxMDUsMC4zMDk0NSAyLjMzMjc2MywwLjMwOTQ0OCAxLjAzMTQ4OCwyZS02IDEuODI0OTQ0LC0wLjE3NDU1OSAyLjM4MDM3MiwtMC41MjM2ODEgMC41NTU0MSwtMC4zNTcwNTQgMC44MzMxMiwtMC44NTY5MzEgMC44MzMxMjksLTEuNDk5NjM0IC05ZS02LC0wLjU5NTA4OSAtMC4yMDIzNCwtMS4wNTEzMjYgLTAuNjA2OTk0LC0xLjM2ODcxMyAtMC4zOTY3MzcsLTAuMzE3Mzc4IC0xLjI3MzUwNiwtMC42MjI4NTkgLTIuNjMwMzEsLTAuOTE2NDQzIGwgLTAuNzYxNzE5LC0wLjE3ODUyOCBjIC0xLjM1NjgxNSwtMC4yODU2MzggLTIuMzM2NzM0LC0wLjcyMjAzOSAtMi45Mzk3NTgsLTEuMzA5MjA0IC0wLjYwMzAyOSwtMC41OTUwODUgLTAuOTA0NTQzLC0xLjQwODM3OCAtMC45MDQ1NDEsLTIuNDM5ODgxIC0yZS02LC0xLjI1MzY1MSAwLjQ0NDMzNCwtMi4yMjE2NjcgMS4zMzMwMDcsLTIuOTA0MDUyIDAuODg4NjY5LC0wLjY4MjM2IDIuMTUwMjY0LC0xLjAyMzU0NiAzLjc4NDc5MSwtMS4wMjM1NiAwLjgwOTMxOCwxLjRlLTUgMS41NzEwMzYsMC4wNTk1MiAyLjI4NTE1NiwwLjE3ODUyOCAwLjcxNDEwMiwwLjExOTAzMiAxLjM3MjY3LDAuMjk3NTU5IDEuOTc1NzA4LDAuNTM1NTgzIiBpZD0icGF0aDI5ODkiIHN0eWxlPSJmaWxsOiNmZmZmZmYiIC8+CiAgICAgIDxwYXRoIGQ9Im0gNDkuMjMzMzk4LDI0Ljk5ODAxNiAwLDIuMDcwOTIzIGMgLTAuNjE4OTA2LC0wLjMxNzM3MSAtMS4yNjE2MDYsLTAuNTU1NDA4IC0xLjkyODEsLTAuNzE0MTExIC0wLjY2NjUxMiwtMC4xNTg2OCAtMS4zNTY4MTksLTAuMjM4MDI1IC0yLjA3MDkyMywtMC4yMzgwMzcgLTEuMDg3MDQyLDEuMmUtNSAtMS45MDQzMDIsMC4xNjY2MzcgLTIuNDUxNzgyLDAuNDk5ODc4IC0wLjUzOTU1NSwwLjMzMzI2MyAtMC44MDkzMywwLjgzMzE0IC0wLjgwOTMyNiwxLjQ5OTYzMyAtNGUtNiwwLjUwNzgyMiAwLjE5NDM5MywwLjkwODUxOCAwLjU4MzE5MSwxLjIwMjA4OCAwLjM4ODc4OSwwLjI4NTY1MyAxLjE3MDM0MywwLjU1OTM5NSAyLjM0NDY2NSwwLjgyMTIyOCBsIDAuNzQ5ODE3LDAuMTY2NjI2IGMgMS41NTUxNjcsMC4zMzMyNTkgMi42NTgwNzEsMC44MDUzNjYgMy4zMDg3MTYsMS40MTYzMjEgMC42NTg1NTgsMC42MDMwMzMgMC45ODc4NDIsMS40NDgwNjQgMC45ODc4NTQsMi41MzUwOTUgLTEuMmUtNSwxLjIzNzc5NSAtMC40OTE5NTUsMi4yMTc3MTQgLTEuNDc1ODMsMi45Mzk3NTggLTAuOTc1OTYyLDAuNzIyMDQ2IC0yLjMyMDg3LDEuMDgzMDY5IC00LjAzNDcyOSwxLjA4MzA2OSAtMC43MTQxMTcsMCAtMS40NTk5NjYsLTAuMDcxNDEgLTIuMjM3NTQ5LC0wLjIxNDIzMyAtMC43Njk2NTYsLTAuMTM0ODg4IC0xLjU4Mjk0OSwtMC4zNDExODcgLTIuNDM5ODgxLC0wLjYxODg5NyBsIDAsLTIuMjYxMzUyIGMgMC44MDkzMjUsMC40MjA1MzQgMS42MDY3NDgsMC43Mzc5MTcgMi4zOTIyNzMsMC45NTIxNDggMC43ODU1MTgsMC4yMDYzIDEuNTYzMTA2LDAuMzA5NDUgMi4zMzI3NjQsMC4zMDk0NDggMS4wMzE0ODcsMmUtNiAxLjgyNDk0MywtMC4xNzQ1NTkgMi4zODAzNzEsLTAuNTIzNjgxIDAuNTU1NDExLC0wLjM1NzA1NCAwLjgzMzEyMSwtMC44NTY5MzEgMC44MzMxMywtMS40OTk2MzQgLTllLTYsLTAuNTk1MDg5IC0wLjIwMjM0MSwtMS4wNTEzMjYgLTAuNjA2OTk1LC0xLjM2ODcxMyAtMC4zOTY3MzYsLTAuMzE3Mzc4IC0xLjI3MzUwNSwtMC42MjI4NTkgLTIuNjMwMzEsLTAuOTE2NDQzIGwgLTAuNzYxNzE4LC0wLjE3ODUyOCBjIC0xLjM1NjgxNiwtMC4yODU2MzggLTIuMzM2NzM0LC0wLjcyMjAzOSAtMi45Mzk3NTksLTEuMzA5MjA0IC0wLjYwMzAyOSwtMC41OTUwODUgLTAuOTA0NTQyLC0xLjQwODM3OCAtMC45MDQ1NDEsLTIuNDM5ODgxIC0xZS02LC0xLjI1MzY1MSAwLjQ0NDMzNCwtMi4yMjE2NjcgMS4zMzMwMDgsLTIuOTA0MDUyIDAuODg4NjY4LC0wLjY4MjM2IDIuMTUwMjY0LC0xLjAyMzU0NiAzLjc4NDc5LC0xLjAyMzU2IDAuODA5MzE5LDEuNGUtNSAxLjU3MTAzNywwLjA1OTUyIDIuMjg1MTU2LDAuMTc4NTI4IDAuNzE0MTAyLDAuMTE5MDMyIDEuMzcyNjcxLDAuMjk3NTU5IDEuOTc1NzA4LDAuNTM1NTgzIiBpZD0icGF0aDI5OTEiIHN0eWxlPSJmaWxsOiNmZmZmZmYiIC8+CiAgICA8L2c+CiAgPC9nPgo8L3N2Zz4=';
		//--
		$img_loading  = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAzMiAzMiIgd2lkdGg9IjMyIiBoZWlnaHQ9IjMyIiBmaWxsPSJncmV5IiBpZD0ibG9hZGluZy1jeWNsb24tc3ZnIj4KICA8cGF0aCB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwIDApIiBkPSJNMCAxMiBWMjAgSDQgVjEyeiI+CiAgICA8YW5pbWF0ZVRyYW5zZm9ybSBhdHRyaWJ1dGVOYW1lPSJ0cmFuc2Zvcm0iIHR5cGU9InRyYW5zbGF0ZSIgdmFsdWVzPSIwIDA7IDI4IDA7IDAgMDsgMCAwIiBkdXI9IjEuNXMiIGJlZ2luPSIwIiByZXBlYXRDb3VudD0iaW5kZWZpbml0ZSIga2V5dGltZXM9IjA7MC4zOzAuNjsxIiBrZXlTcGxpbmVzPSIwLjIgMC4yIDAuNCAwLjg7MC4yIDAuMiAwLjQgMC44OzAuMiAwLjIgMC40IDAuOCIgY2FsY01vZGU9InNwbGluZSIgLz4KICA8L3BhdGg+CiAgPHBhdGggb3BhY2l0eT0iMC41IiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwIDApIiBkPSJNMCAxMiBWMjAgSDQgVjEyeiI+CiAgICA8YW5pbWF0ZVRyYW5zZm9ybSBhdHRyaWJ1dGVOYW1lPSJ0cmFuc2Zvcm0iIHR5cGU9InRyYW5zbGF0ZSIgdmFsdWVzPSIwIDA7IDI4IDA7IDAgMDsgMCAwIiBkdXI9IjEuNXMiIGJlZ2luPSIwLjFzIiByZXBlYXRDb3VudD0iaW5kZWZpbml0ZSIga2V5dGltZXM9IjA7MC4zOzAuNjsxIiBrZXlTcGxpbmVzPSIwLjIgMC4yIDAuNCAwLjg7MC4yIDAuMiAwLjQgMC44OzAuMiAwLjIgMC40IDAuOCIgY2FsY01vZGU9InNwbGluZSIgLz4KICA8L3BhdGg+CiAgPHBhdGggb3BhY2l0eT0iMC4yNSIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMCAwKSIgZD0iTTAgMTIgVjIwIEg0IFYxMnoiPgogICAgPGFuaW1hdGVUcmFuc2Zvcm0gYXR0cmlidXRlTmFtZT0idHJhbnNmb3JtIiB0eXBlPSJ0cmFuc2xhdGUiIHZhbHVlcz0iMCAwOyAyOCAwOyAwIDA7IDAgMCIgZHVyPSIxLjVzIiBiZWdpbj0iMC4ycyIgcmVwZWF0Q291bnQ9ImluZGVmaW5pdGUiIGtleXRpbWVzPSIwOzAuMzswLjY7MSIga2V5U3BsaW5lcz0iMC4yIDAuMiAwLjQgMC44OzAuMiAwLjIgMC40IDAuODswLjIgMC4yIDAuNCAwLjgiIGNhbGNNb2RlPSJzcGxpbmUiIC8+CiAgPC9wYXRoPgo8L3N2Zz4KPCEtLSBodHRwczovL2dpdGh1Yi5jb20vanhuYmxrL2xvYWRpbmcvbG9hZGluZy1jeWNsb24uc3ZnIDsgTGljZW5zZTogTUlUIC0tPg==';
		$code_loading_start = '<div id="img-loader" style="position:fixed; top:30px; right:10px;"><img width="48" height="48" alt="Loading ..." title="Loading ..." src="'.AppPackUtils::escape_html($img_loading).'"></div>';
		$code_loading_stop  = '<script>try{ document.getElementById(\'img-loader\').innerHTML = \'\'; }catch(err){ console.error(err); }</script>';
		//--
		$img_result_ok  = '<div id="img-result" style="position:fixed; top:5px; right:225px; cursor:help;"><img width="64" height="64" alt="Result Status: OK" title="Result Status: OK" src="data:image/svg+xml;base64,PHN2ZyB4bWxuczpkYz0iaHR0cDovL3B1cmwub3JnL2RjL2VsZW1lbnRzLzEuMS8iIHhtbG5zOmNjPSJodHRwOi8vY3JlYXRpdmVjb21tb25zLm9yZy9ucyMiIHhtbG5zOnN2Zz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgc3R5bGU9ImZpbGwtcnVsZTpldmVub2RkIiB4bWw6c3BhY2U9InByZXNlcnZlIiBpZD0ic2lnbi1vay1zdmciIHZpZXdCb3g9IjAgMCA3Mi4yNDg4OTMgNzIuMjQ4ODkzIiBoZWlnaHQ9IjMyIiB3aWR0aD0iMzIiIHZlcnNpb249IjEuMSI+PGRlZnMgaWQ9ImRlZnM0Ij4gIDxzdHlsZSBpZD0ic3R5bGU2IiB0eXBlPSJ0ZXh0L2NzcyIgLz48L2RlZnM+PGcgc3R5bGU9ImZpbGw6I2ZmZmZmZjtmaWxsLW9wYWNpdHk6MTtzdHJva2U6bm9uZTsiIGlkPSJ0ZXh0Mzc4MSI+ICA8ZyBpZD0iZzMwMDkiPiAgICA8ZyBzdHlsZT0iZmlsbDojZmZmZmZmO2ZpbGwtb3BhY2l0eToxO3N0cm9rZTpub25lOyIgaWQ9InRleHQzNzU2Ij4gICAgICA8ZyBpZD0iZzM4MDIiPiAgICAgICAgPGcgaWQ9ImczMzY4Ij4gICAgICAgICAgPGcgaWQ9InRleHQzMzY0IiBzdHlsZT0iZmlsbDojZmZmZmZmO2ZpbGwtb3BhY2l0eToxO3N0cm9rZTpub25lO3N0cm9rZS13aWR0aDoxcHg7c3Ryb2tlLWxpbmVjYXA6YnV0dDtzdHJva2UtbGluZWpvaW46bWl0ZXI7c3Ryb2tlLW9wYWNpdHk6MSI+ICAgICAgICAgICAgPGcgaWQ9ImczNDU1Ij4gICAgICAgICAgICAgIDxwYXRoIHN0eWxlPSJmaWxsLXJ1bGU6ZXZlbm9kZDtmaWxsOiM5OGM3MjY7ZmlsbC1vcGFjaXR5OjE7c3Ryb2tlOiM5OGM3MjY7c3Ryb2tlLXdpZHRoOjE0LjEyMTA4NTE3O3N0cm9rZS1vcGFjaXR5OjEiIGlkPSJwYXRoMzAxMSIgZD0ibSA2Mi45MzA1NzQsMzYuMTI0NDQ3IGEgMjYuODA2MTI4LDI2LjgwNjEyOCAwIDEgMSAtNTMuNjEyMjUzOCwwIDI2LjgwNjEyOCwyNi44MDYxMjggMCAxIDEgNTMuNjEyMjUzOCwwIHoiIC8+ICAgICAgICAgICAgICA8cGF0aCBkPSJtIDIyLjc2Mjk4OCwzNC4xNzMxNDUgcSAxLjI4OTg0NCwwIDEuOTUxMzAyLDIuMTE2NjY3IDEuMzIyOTE3LDMuOTY4NzUgMS44ODUxNTcsMy45Njg3NSAwLjQyOTk0NywwIDAuODkyOTY4LC0wLjY2MTQ1OCA5LjI5MzQ5LC0xMy42NTkxMTYgMTcuMTk3OTE4LC0xOS40NDY4NzYgMy4zNDAzNjUsLTIuNDQ3Mzk2IDYuNTE1MzY1LC0yLjQ0NzM5NiA0LjIwMDI2MSwwIDUuMDYwMTU3LDAuMjY0NTgzIDAuMzYzODAyLDAuMDk5MjIgMC4zNjM4MDIsMC44MjY4MjMgMCwwLjU5NTMxMyAtMC43NjA2NzcsMS40ODgyODEgLTIxLjI2NTg4NywyNC40MDc4MTQgLTI1LjMzMzg1NiwzMS43NTAwMDIgLTEuMzg5MDYzLDIuNTEzNTQyIC02LjQxNjE0NiwyLjUxMzU0MiAtMS42NTM2NDYsMCAtMy40NzI2NTcsLTAuODU5ODk2IC0wLjc2MDY3NywtMC4zOTY4NzUgLTIuNjQ1ODMzLC01LjA2MDE1NiAtMi4zODEyNSwtNS44ODY5OCAtMi4zODEyNSwtMTAuMzE4NzUxIDAsLTEuNjIwNTczIDIuMzE1MTA0LC0yLjY3ODkwNiAzLjE3NSwtMS40NTUyMDkgNC43NjI1LC0xLjQ1NTIwOSBsIDAuMDY2MTUsMCB6IiBzdHlsZT0iZmlsbDojZmZmZmZmIiBpZD0icGF0aDM0NTIiIC8+ICAgICAgICAgICAgPC9nPiAgICAgICAgICA8L2c+ICAgICAgICA8L2c+ICAgICAgPC9nPiAgICA8L2c+ICA8L2c+PC9nPjwvc3ZnPg=="></div>';
		$img_result_err = '<div id="img-result" style="position:fixed; top:5px; right:225px; cursor:help;"><img width="64" height="64" alt="Result Status: ERROR" title="Result Status: ERROR" src="data:image/svg+xml;base64,PHN2ZyB4bWxuczpkYz0iaHR0cDovL3B1cmwub3JnL2RjL2VsZW1lbnRzLzEuMS8iIHhtbG5zOmNjPSJodHRwOi8vY3JlYXRpdmVjb21tb25zLm9yZy9ucyMiIHhtbG5zOnN2Zz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgdmVyc2lvbj0iMS4xIiB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCA3Mi4yNDg4OTMgNzIuMjQ4ODkzIiBpZD0ic2lnbi1jcml0LWVycm9yLXN2ZyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSIgc3R5bGU9ImZpbGwtcnVsZTpldmVub2RkIj4gIDxkZWZzIGlkPSJkZWZzNCI+ICAgIDxzdHlsZSB0eXBlPSJ0ZXh0L2NzcyIgaWQ9InN0eWxlNiIgLz4gIDwvZGVmcz4gIDxnIHRyYW5zZm9ybT0idHJhbnNsYXRlKDAsLTUyNy43NTExMSkiIGlkPSJMYXllcl94MDAyMF8xIj4gICAgPGcgaWQ9Il8xNjMwODkzNzYiPiAgICAgIDxnIGlkPSJnMzg3OCI+ICAgICAgICA8cG9seWdvbiBwb2ludHM9IjcsMTc4LjYzNSA5Mi44MTc4LDkyLjgxOCAxNzguNjM1LDcuMDAwMiAzMDAsNy4wMDAzIDQyMS4zNjUsNyA1MDcuMTgyLDkyLjgxNzkgNTkzLDE3OC42MzYgNTkzLDMwMCA1OTMsNDIxLjM2NSA1MDcuMTgyLDUwNy4xODIgNDIxLjM2NCw1OTMgMzAwLDU5MyAxNzguNjM1LDU5MyA5Mi44MTc2LDUwNy4xODIgNyw0MjEuMzY1IDcuMDAwMSwzMDAgIiB0cmFuc2Zvcm09Im1hdHJpeCgwLjEyMDQwMTk4LDAsMCwwLjEyMDQwMTk4LDAuMjA0NzU5NjMsNTI3Ljc5MzcxKSIgaWQ9Il8xNjMwODg0MTYiIHN0eWxlPSJmaWxsOiNlNzM0MTE7ZmlsbC1vcGFjaXR5OjE7ZmlsbC1ydWxlOmV2ZW5vZGQiIC8+ICAgICAgICA8cmVjdCB3aWR0aD0iMTIuMDU2ODI3IiBoZWlnaHQ9IjMyLjg0NDQ1NiIgeD0iMzAuMjk2OTQiIHk9IjU0MC42NjcyNCIgaWQ9InJlY3QzODU4IiBzdHlsZT0iZmlsbDojZmZmZmZmO2ZpbGwtb3BhY2l0eToxIiAvPiAgICAgICAgPHJlY3Qgd2lkdGg9IjEyLjA1NjgyNyIgaGVpZ2h0PSIxMS44NDg5NDgiIHg9IjMwLjI5Njk0IiB5PSI1NzcuNzU3MiIgaWQ9InJlY3QzODU4LTMiIHN0eWxlPSJmaWxsOiNmZmZmZmY7ZmlsbC1vcGFjaXR5OjE7ZmlsbC1ydWxlOmV2ZW5vZGQiIC8+ICAgICAgPC9nPiAgICA8L2c+ICA8L2c+PC9zdmc+"></div>';
		//--
		AppPackUtils::InstantFlush();
		//--
		echo '<!DOCTYPE html>'."\n".'<html>'."\n".'<head><meta charset="'.AppPackUtils::escape_html((string)SMART_FRAMEWORK_CHARSET).'"><title>Smart'.'::'.'AppCode.PACK</title><style type="text/css">*{font-family:arial,sans-serif;font-smooth:always;} hr{border:none 0; border-top:1px solid #CCCCCC; height:1px;} a{color:#000000 !important;}</style></head>'."\n".'<body>'."\n";
		echo '<table><tr>';
		echo '<td width="64" style="cursor:help;"><img width="48" height="48" alt="AppCodePack '.AppPackUtils::escape_html((string)APPCODEPACK_VERSION).'" title="AppCodePack '.AppPackUtils::escape_html((string)APPCODEPACK_VERSION).'" src="'.AppPackUtils::escape_html($img_logo_app).'"></td>';
		echo '<td><h1 style="display:inline; cursor:pointer;" onclick="self.location=\'?\';"><span style="color:#778899;">Smart</span><span style="color:#DCDCDC;">::</span><span style="color:#555555;">AppCode</span><span style="color:#999999;">.</span><span style="color:#3C5A98;">Pack</span></h1></td>';
		echo '<td> &nbsp; &nbsp; &nbsp; </td>';
		echo '<td>';
		echo '<img width="32" height="32" alt="PHP-Logo" title="PHP-Logo" src="'.AppPackUtils::escape_html($img_logo_php).'">&nbsp; ';
		echo '<img width="32" height="32" alt="JS-Logo" title="JS-Logo" src="'.AppPackUtils::escape_html($img_logo_js).'">&nbsp; ';
		echo '<img width="32" height="32" alt="CSS-Logo" title="CSS-Logo" src="'.AppPackUtils::escape_html($img_logo_css).'">&nbsp; ';
		echo '</td>';
		echo '</tr></table>';
		AppPackUtils::InstantFlush();
		//--
		if((string)$_GET['run'] == 'optimize') {
			//--
			AppPackUtils::delete('---AppCodePack-Optimizations-Done---.log');
			//--
			AppPackUtils::delete('---AppCodePack-Package---.log');
			AppPackUtils::write(
				'---AppCodePack-Result---.log',
				'START Processing ['.date('Y-m-d H:i:s O').'] ...'."\n"
			);
			//--
			echo (string) $code_loading_start;
			//--
			echo '<hr><div style="padding:4px; background:#DDEEFF; font-weight:bold;">'.'TASK / optimize: '.AppPackUtils::escape_html((string)APPCODEPACK_PROCESS_OPTIMIZATIONS_MODE).' <span style="cursor:help;" title="'.AppPackUtils::escape_html((string)APPCODEPACK_STRATEGY).' :: '.AppPackUtils::escape_html((string)APPCODEPACK_MARKER_OPTIMIZATIONS).'">['.AppPackUtils::escape_html((string)APPCODEPACK_COMPRESS_UTILITY_TYPE).'] - '.AppPackUtils::escape_html((string)APPCODEPACK_MARKER_OPTIMIZATIONS).'</span>'.' &nbsp;&nbsp;::&nbsp;&nbsp; '.'#START: '.date('Y-m-d H:i:s O').' # App-ID: '.AppPackUtils::escape_html((string)APPCODEPACK_APP_ID).'</div><hr>';
			AppPackUtils::InstantFlush();
			$appcodeoptimizer = new AppCodeOptimizer();
			$appcodeoptimizer->optimize_code((string)APPCODEPACK_PROCESS_SOURCE_DIR);
			echo '<span title="AppCodePack :: Processing DONE !" style="color:#000000;text-weight:bold;cursor:default;"> &laquo;<b>&bull;</b>&raquo; </span>'."\n";
			if($appcodeoptimizer->have_errors()) {
				echo $img_result_err;
				echo "\n".'<br><br><div title="Status / Errors" style="background:#FF3300; color:#FFFFFF; font-weight:bold; padding:5px; border-radius:5px;">'.@nl2br(AppPackUtils::escape_html($appcodeoptimizer->get_errors()), false).'</div>'."\n";
				AppPackUtils::write(
					'---AppCodePack-Result---.log',
					'Processing ERRORS !!! ['.date('Y-m-d H:i:s O').'] ...'."\n\n".$appcodeoptimizer->get_errors()
				);
			} else {
				echo $img_result_ok;
				echo "\n".'<br><br><div title="Status / OK" style="background:#98C726; color:#000000; font-weight:bold; padding:5px; border-radius:5px;"><h3>[ &nbsp; STATUS: OK &nbsp; &radic; &nbsp; ]</h3></div>'."\n";
				echo "\n".'<div title="Status / Log" style="background:#EFEFEF; color:#000000; padding:5px; border-radius:5px;"><b>[ Log ]</b><hr><pre>'.AppPackUtils::escape_html($appcodeoptimizer->get_log()).'</pre></div>'."\n";
				echo '<!-- {APPCODEPACK:[@SUCCESS(Task:Optimize)@]} -->';
				AppPackUtils::write(
					'---AppCodePack-Result---.log',
					'##### Processing DONE / '.APPCODEPACK_STRATEGY.' - '.APPCODEPACK_MARKER_OPTIMIZATIONS.' : ['.date('Y-m-d H:i:s O').']'."\n\n".'##### LOG: '."\n".trim($appcodeoptimizer->get_log())."\n".'##### END'
				);
				AppPackUtils::write(
					'---AppCodePack-Optimizations-Done---.log',
					(string) date('Y-m-d H:i:s O')
				);
			} //end if
			unset($appcodeoptimizer);
			echo '<br><div style="padding:4px; background:#DDEEFF; font-weight:bold;">'.'TASK / optimize :'.AppPackUtils::escape_html((string)APPCODEPACK_PROCESS_OPTIMIZATIONS_MODE.' ['.(string)APPCODEPACK_COMPRESS_UTILITY_TYPE.'] - '.APPCODEPACK_MARKER_OPTIMIZATIONS).' &nbsp;&nbsp;::&nbsp;&nbsp; '.'#END: '.date('Y-m-d H:i:s O').'</div>';
			//--
			echo (string) $code_loading_stop;
			//--
		} elseif((string)$_GET['run'] == 'deploy') {
			//--
			echo (string) $code_loading_start;
			//--
			echo '<hr><div style="padding:4px; background:#DDEEFF; font-weight:bold;">'.'TASK / deploy &nbsp;&nbsp;::&nbsp;&nbsp; '.'#START: '.date('Y-m-d H:i:s O').'<i><br> # App-ID: '.AppPackUtils::escape_html((string)APPCODEPACK_APP_ID).'<br> # AppID-Hash: '.APPCODEPACK_APP_HASH_ID.'<br> # Release Server Deploy URL(s): '.AppPackUtils::escape_html((string)APPCODEPACK_APP_UNPACK_URL.' ['.(string)APPCODEPACK_APP_UNPACK_USER.']').'</i></div><hr>';
			AppPackUtils::InstantFlush();
			//--
			if(((string)APPCODEPACK_APP_UNPACK_URL != '') AND ((string)APPCODEPACK_APP_UNPACK_USER != '') AND ((string)APPCODEPACK_APP_UNPACK_PASSWORD != '')) {
				//--
				if(
					((string)trim((string)$_POST['netarch_package']) != '') AND
					(((string)trim((string)$_POST['netarch_deploy_url']) != '') AND (strpos((string)APPCODEPACK_APP_UNPACK_URL, (string)$_POST['netarch_deploy_url']) !== false)) AND
					((string)substr((string)$_POST['netarch_package'], -10, 10) == '.z-netarch') AND
					(((string)substr((string)$_POST['netarch_deploy_url'], 0, 7) == 'http://') OR ((string)substr((string)$_POST['netarch_deploy_url'], 0, 8) == 'https://')) AND
					(AppPackUtils::check_if_safe_path((string)$_POST['netarch_package'])) AND
					(AppPackUtils::is_type_file((string)$_POST['netarch_package']))
				) {
					$browser = AppPackUtils::browse_url(
						(string) $_POST['netarch_deploy_url'], // url
						'POST', // method
						(string) APPCODEPACK_APP_UNPACK_USER, // user
						(string) base64_decode((string)APPCODEPACK_APP_UNPACK_PASSWORD), // pass
						[ // cookies
							'AppCodeRemoteVersion' => (string) APPCODEPACK_VERSION
						],
						[ // post vars
							'remote' 		=> 'appcodepack', // hide buttons
							'run' 			=> 'deploy',
							'appid' 		=> (string) APPCODEPACK_APP_ID,
							'apphashid' 	=> (string) APPCODEPACK_APP_HASH_ID,
							'remoteinfo' 	=> [
								'url' => (string) $_POST['netarch_deploy_url'],
								'user' => (string) APPCODEPACK_APP_UNPACK_USER
							]
						],
						[ // post files
							'znetarch' 		=> [
								'filename' 	=> (string) AppPackUtils::base_name((string)$_POST['netarch_package']),
								'content' 	=> (string) AppPackUtils::read((string)$_POST['netarch_package'])
							]
						],
						[ // raw headers
							'Z-Application-Name: Smart.Framework:AppCodePack'
						]
					);
					if(!is_array($browser)) {
						echo "\n".'<div title="Status / Errors" style="background:#FF3300; color:#FFFFFF; font-weight:bold; padding:5px; border-radius:5px;">'.'Package Deploy Failed with Code: '.(int)$browser.'.'.'</div>'."\n";
					} else {
						if(($browser['status'] != 1) OR ($browser['errno']) OR ($browser['ermsg']) OR ($browser['http-status'] != 200)) {
							echo "\n".'<div title="Status / Errors" style="background:#FF3300; color:#FFFFFF; font-weight:bold; padding:5px; border-radius:5px;">'.'Package Deploy Failed with ERRORS: '.AppPackUtils::escape_html($browser['errno']).' # '.AppPackUtils::escape_html($browser['ermsg']).' / HTTP Status Code '.(int)$browser['http-status'].'</div>'."\n";
						} else {
							echo "\n".'<div style="padding:7px; line-height:1.125em; font-weight:bold; color: #FFFFFF; background:#009ACE; border:1px solid #0089BD; border-radius:6px; box-shadow: 2px 2px 3px #D2D2D2;">'.'Deploy Result'.' # HTTP Status Code: '.(int)$browser['http-status'].'</div>';
						} //end if else
					} //end if else
					echo '<div style="margin-top:5px; padding:7px; line-height:1.125em; font-size:1.25rem; font-weight:bold; text-align:center; background:#778899; color:#FFFFFF; border-radius:5px;">Selected Release Server URL: `'.AppPackUtils::escape_html((string)$_POST['netarch_deploy_url']).'`</div><br><div><center><iframe name="UnpackDeployOnServerResponseSandBox" id="UnpackDeployOnServerResponseSandBox" scrolling="auto" marginwidth="5" marginheight="5" hspace="0" vspace="0" frameborder="0" style="width:80vw; min-width:920px; min-height:50vh; height:max-content; border:1px solid #CCCCCC;" sandbox="allow-same-origin" srcdoc="'.AppPackUtils::escape_html($browser['http-body']).'"></iframe></center></div>';
					unset($browser);
				} else {
					echo "\n".'<div title="Status / Errors" style="background:#FF3300; color:#FFFFFF; font-weight:bold; padding:5px; border-radius:5px;">'.'Invalid/Empty Release Package Selected or Invalid/Empty Release Server URL Selected.'.'</div>'."\n";
				} //end if else
			} else {
				echo "\n".'<div title="Status / Errors" style="background:#FF3300; color:#FFFFFF; font-weight:bold; padding:5px; border-radius:5px;">'.'The Release Server Deploy URL and Authentication Credentials must be non-empty (APPCODEPACK_APP_UNPACK_URL / APPCODEPACK_APP_UNPACK_USER / APPCODEPACK_APP_UNPACK_PASSWORD).'.'</div>'."\n";
			} //end if else
			//--
			echo '<br><div style="padding:4px; background:#DDEEFF; font-weight:bold;">'.'TASK / deploy &nbsp;&nbsp;::&nbsp;&nbsp; # App-ID: '.AppPackUtils::escape_html((string)APPCODEPACK_APP_ID).' &nbsp;&nbsp;::&nbsp;&nbsp; '.'#END: '.date('Y-m-d H:i:s O').'</div>';
			//--
			echo (string) $code_loading_stop;
			//--
		} elseif((string)$_GET['run'] == 'pack') {
			//--
			echo (string) $code_loading_start;
			//--
			echo '<hr><div style="padding:4px; background:#DDEEFF; font-weight:bold;">'.'TASK / pack &nbsp;&nbsp;::&nbsp;&nbsp; '.'#START: '.date('Y-m-d H:i:s O').' # App-ID: '.AppPackUtils::escape_html((string)APPCODEPACK_APP_ID).'</div><hr>';
			AppPackUtils::InstantFlush();
			$date_iso_arch = (string) date('Y-m-d H:i:s');
			$date_arch = (string) date('Ymd-His', strtotime($date_iso_arch));
			$name_arch = AppPackUtils::safe_filename('appcode-package_'.$date_arch.'.z-netarch');
			if((string)APPCODEPACK_APP_ID != '-----UNDEF-----') {
				$name_arch = (string) AppPackUtils::safe_filename((string)APPCODEPACK_APP_ID.'__'.$name_arch);
			} //end if
			$the_archdir = AppPackUtils::safe_pathname('#---APPCODE-PACKAGES---#');
			$the_archname = '';
			$the_archpath = '';
			//--
			if((AppPackUtils::is_type_file('---AppCodePack-Optimizations-Done---.log')) AND (!AppPackUtils::is_type_file('---AppCodePack-Package---.log')) AND (AppPackUtils::is_type_dir((string)APPCODEPACK_PROCESS_OPTIMIZATIONS_DIR))) {
				AppPackUtils::write('---AppCodePack-Package---.log', ''); // must be initialized
				$arch = new AppNetPackager();
				$arch->start((string)$the_archdir, (string)$name_arch, (string)$date_iso_arch, (string)$_GET['comment']);
				$the_archname = (string) $arch->get_archive_file_name();
				$the_archpath = (string) $arch->get_archive_file_path();
				echo '<br><div><h3 style="display:inline;">Creating the Package Arch. File: '.AppPackUtils::escape_html($the_archname).'&nbsp;&nbsp;&nbsp;&raquo;&nbsp;&nbsp;&nbsp;./'.AppPackUtils::escape_html($the_archdir).'</h3></div>';
				AppPackUtils::InstantFlush();
				$arch->pack_dir((string)APPCODEPACK_PROCESS_OPTIMIZATIONS_DIR);
				$err_arch = (string) $arch->save();
				if(!$err_arch) {
					echo '<div><h3 style="display:inline; color:#666699!important;">Checking the Package Integrity: '.AppPackUtils::escape_html($the_archname).'&nbsp;&nbsp;&nbsp;&raquo;&nbsp;&nbsp;&nbsp;'.'@MEMORY'.'</h3></div>';
					if((bool)APPCODEPACK_UNPACK_TESTONLY !== true) {
						echo '<div><h3 style="display:inline; color:#FF9900!important;">Testing the Package Unpacking: '.AppPackUtils::escape_html($the_archname).'&nbsp;&nbsp;&nbsp;&raquo;&nbsp;&nbsp;&nbsp;./'.AppPackUtils::escape_html(APPCODEPACK_APP_ID).'</h3></div>';
					} //end if
					AppPackUtils::InstantFlush();
					$err_arch = (string) AppPackUtils::unpack_netarchive(
						(string) AppPackUtils::read((string)$the_archpath),
						(bool) APPCODEPACK_UNPACK_TESTONLY
					);
				} //end if
				unset($arch);
			} else {
				$err_arch = 'Either the Package has already been created or the Optimizations Folder ['.(string)APPCODEPACK_PROCESS_OPTIMIZATIONS_DIR.'] does not exists. Clear and run the Optimizations first ...';
			} //end if else
			echo '<br>';
			AppPackUtils::InstantFlush();
			sleep(1);
			if((string)$err_arch != '') {
				echo "\n".'<div title="Status / Errors" style="background:#FF3300; color:#FFFFFF; font-weight:bold; padding:5px; border-radius:5px;">'.@nl2br(AppPackUtils::escape_html($err_arch), false).'</div><br>'."\n";
			} else {
				AppPackUtils::write('---AppCodePack-Package---.log', (string)$the_archname);
				echo "\n".'<div title="Status / OK" style="background:#98C726; color:#000000; font-weight:bold; padding:10px; border-radius:5px; line-height:25px;"><h3>[ &nbsp; STATUS: OK &nbsp; &radic; ]</h3>';
				if((string)APPCODEPACK_APP_ID != '-----UNDEF-----') {
					echo 'AppID: '.AppPackUtils::escape_html(APPCODEPACK_APP_ID).'<br>'."\n";
					if((string)APPCODEPACK_APP_HASH_ID != '') {
						echo 'AppID-Hash: '.AppPackUtils::escape_html(APPCODEPACK_APP_HASH_ID).'<br>'."\n";
					} //end if
					$the_archsize = 0;
					if(AppPackUtils::is_type_file($the_archpath)) {
						$the_archsize = AppPackUtils::get_file_size($the_archpath);
					} //end if
					echo 'Package: <a download="'.AppPackUtils::escape_html($the_archname).'" href="'.AppPackUtils::escape_html(rawurlencode($the_archdir).'/'.rawurlencode($the_archname)).'">'.AppPackUtils::escape_html($the_archname).'</a>'.'&nbsp;&nbsp;&nbsp;['.AppPackUtils::pretty_print_bytes($the_archsize, 2, '&nbsp;').']'.'<br>'."\n";
					echo 'Comment: '.AppPackUtils::escape_html((string)$_GET['comment']).'<br>'."\n";
					echo '<br>'."\n";
				} //end if
				echo '</div>'."\n";
				echo "\n".'<div title="Status / Log" style="background:#EFEFEF; color:#000000; padding:5px; border-radius:5px;"><b>[ Log ]</b><hr>Release Package File ['.number_format((int)$the_archsize, 0, '.', ',').' bytes]'.': '.AppPackUtils::escape_html($the_archpath).'</div>'."\n";
				echo '<!-- {APPCODEPACK:[@SUCCESS(Task:Pack)@]} -->';
			} //end if
			echo '<br><div style="padding:4px; background:#DDEEFF; font-weight:bold;">'.'TASK / pack &nbsp;&nbsp;::&nbsp;&nbsp; '.'#END: '.date('Y-m-d H:i:s O').'</div>';
			//--
			echo (string) $code_loading_stop;
			//--
		} elseif((string)$_GET['run'] == 'cleanup') {
			//--
			echo (string) $code_loading_start;
			//--
			echo '<hr><div style="padding:4px; background:#DDEEFF; font-weight:bold;">'.'TASK / cleanup &nbsp;&nbsp;::&nbsp;&nbsp; '.'#START: '.date('Y-m-d H:i:s O').' # App-ID: '.AppPackUtils::escape_html((string)APPCODEPACK_APP_ID).'</div><hr>';
			AppPackUtils::InstantFlush();
			//--
			$appcode_err_cleanup = '';
			//--
			$the_archdir = AppPackUtils::safe_pathname('#---APPCODE-PACKAGES---#');
			if(AppPackUtils::is_type_dir((string)$the_archdir)) {
				$appcode_cleanup = AppPackUtils::dir_delete((string)$the_archdir, true);
				if((string)$appcode_cleanup != '1') {
					$appcode_err_cleanup = 'ERROR: Failed to remove the Packages Folder: '.(string)$the_archdir;
				} elseif(AppPackUtils::is_type_dir((string)$the_archdir)) {
					$appcode_err_cleanup = 'ERROR: The Packages Folder was not deleted: '.(string)$the_archdir;
				} //end if
			} //end if
			//--
			if(AppPackUtils::is_type_dir((string)APPCODEPACK_PROCESS_OPTIMIZATIONS_DIR)) {
				$appcode_cleanup = AppPackUtils::dir_delete((string)APPCODEPACK_PROCESS_OPTIMIZATIONS_DIR, true);
				if((string)$appcode_cleanup != '1') {
					$appcode_err_cleanup = 'ERROR: Failed to remove the Optimizations Folder: '.(string)APPCODEPACK_PROCESS_OPTIMIZATIONS_DIR;
				} elseif(AppPackUtils::is_type_dir((string)APPCODEPACK_PROCESS_OPTIMIZATIONS_DIR)) {
					$appcode_err_cleanup = 'ERROR: The Optimizations Folder was not deleted: '.(string)APPCODEPACK_PROCESS_OPTIMIZATIONS_DIR;
				} //end if
			} //end if
			//--
			if(AppPackUtils::is_type_file('---AppCodePack-Package---.log')) {
				AppPackUtils::delete('---AppCodePack-Package---.log');
				if(AppPackUtils::path_exists('---AppCodePack-Package---.log')) {
					$appcode_err_cleanup = 'ERROR: The Optimizations Package Log File could not be removed ...';
				} //end if
			} //end if
			if(AppPackUtils::is_type_file('---AppCodePack-Optimizations-Done---.log')) {
				AppPackUtils::delete('---AppCodePack-Optimizations-Done---.log');
				if(AppPackUtils::path_exists('---AppCodePack-Optimizations-Done---.log')) {
					$appcode_err_cleanup = 'ERROR: The Optimizations Done Log File could not be removed ...';
				} //end if
			} //end if
			if(AppPackUtils::is_type_file('---AppCodePack-Result---.log')) {
				AppPackUtils::delete('---AppCodePack-Result---.log');
				if(AppPackUtils::path_exists('---AppCodePack-Result---.log')) {
					$appcode_err_cleanup = 'ERROR: The Optimizations Results Log File could not be removed ...';
				} //end if
			} //end if
			//--
			sleep(3);
			//--
			if($appcode_err_cleanup) {
				echo "\n".'<br><br><div title="Status / Errors" style="background:#FF3300; color:#FFFFFF; font-weight:bold; padding:5px; border-radius:5px;">'.@nl2br(AppPackUtils::escape_html($appcode_err_cleanup), false).'</div>'."\n";
			} else {
				echo "\n".'<div title="Status / OK" style="background:#98C726; color:#000000; font-weight:bold; padding:5px; border-radius:5px;"><h3>[ &nbsp; STATUS: OK &nbsp; &radic; &nbsp; ]</h3></div>'."\n";
				echo '<!-- {APPCODEPACK:[@SUCCESS(Task:CleanUp)@]} -->';
			} //end if else
			echo '<br><div style="padding:4px; background:#DDEEFF; font-weight:bold;">'.'TASK / cleanup &nbsp;&nbsp;::&nbsp;&nbsp; '.'#END: '.date('Y-m-d H:i:s O').'</div>';
			//--
			echo (string) $code_loading_stop;
			//--
		} else {
			//--
			echo '<hr>';
			//--
			if(($_GET['run']) AND (AppPackUtils::is_type_file('appcodepack-extra-run.php')) AND (AppPackUtils::is_type_file('appcodepack-extra-run.inc.htm'))) {
				//--
				echo '<div style="padding:4px; background:#DDEEFF; font-weight:bold;">'.'Extra TASK / '.AppPackUtils::escape_html((string)$_GET['run']).' &nbsp;&nbsp;::&nbsp;&nbsp; '.'#START: '.date('Y-m-d H:i:s O').' # App-ID: '.AppPackUtils::escape_html((string)APPCODEPACK_APP_ID).'</div><hr>';
				echo (string) $code_loading_start;
				AppPackUtils::InstantFlush();
				sleep(1);
				echo (string) self::pack_run_extra_script((string)$_GET['run'], 'appcodepack-extra-run.php');
				echo (string) $code_loading_stop;
				//--
			} else {
				//--
				if(AppPackUtils::path_exists('---AppCodePack-RunExtra---.log')) {
					AppPackUtils::delete('---AppCodePack-RunExtra---.log');
				} //end if
				//--
				if((string)APPCODEPACK_APP_ID != '-----UNDEF-----') {
					echo '<div style="margin-bottom:10px; padding:7px; line-height:1.125em; font-weight:bold; color: #FFFFFF; background:#009ACE; border:1px solid #0089BD; border-radius:6px; box-shadow: 2px 2px 3px #D2D2D2;"><table>';
					echo '<tr valign="top"><td>Managed AppID: </td><td> &nbsp; &nbsp; </td><td>'.AppPackUtils::escape_html(APPCODEPACK_APP_ID).'</td></tr>';
					if((string)APPCODEPACK_APP_HASH_ID != '') {
						echo '<tr valign="top"><td>Secret AppID-Hash: </td><td> &nbsp; &nbsp; </td><td>'.AppPackUtils::escape_html(APPCODEPACK_APP_HASH_ID).'</td></tr>';
					} //end if
					if(((string)APPCODEPACK_APP_UNPACK_URL != '') AND ((string)APPCODEPACK_APP_UNPACK_USER != '') AND ((string)APPCODEPACK_APP_UNPACK_PASSWORD != '')) {
						echo '<tr valign="top"><td>Release Server Deploy URL(s): </td><td> &nbsp; &nbsp; </td><td>'.'['.AppPackUtils::escape_html(APPCODEPACK_APP_UNPACK_USER).':*****'.'] @ '.AppPackUtils::escape_html(APPCODEPACK_APP_UNPACK_URL).'</td></tr>';
					} //end if
					echo '</table></div>';
				} //end if
				echo '<div style="padding:4px; background:#D3E397; font-weight:bold;">'.'<h2>Select a TASK to RUN from the list below</h2></div><hr>';
				AppPackUtils::InstantFlush();
				echo '<div style="font-size:1.25em!important;">'."\n";
				echo '<select id="task-run-sel" style="font-size:1em!important; max-width:750px;">'."\n";
				echo '<option value="">--- NO TASK Selected ---</option>'."\n";
				echo '<optgroup label="AppCode.Pack TASKS: RELEASE">'."\n";
				if(!AppPackUtils::path_exists((string)APPCODEPACK_PROCESS_OPTIMIZATIONS_DIR)) {
					echo '<option value="optimize" title="'.AppPackUtils::escape_html((string)APPCODEPACK_STRATEGY).'">RELEASE: ['.AppPackUtils::escape_html((string)APPCODEPACK_COMPRESS_UTILITY_TYPE).'] OPTIMIZE Source Code '.APPCODEPACK_MARKER_OPTIMIZATIONS.' @ Folders: ['.AppPackUtils::escape_html((string)APPCODEPACK_PROCESS_SOURCE_DIR).' -&gt; '.AppPackUtils::escape_html((string)APPCODEPACK_PROCESS_OPTIMIZATIONS_DIR).']'.'</option>'."\n";
				} else {
					if(((string)APPCODEPACK_APP_ID != '-----UNDEF-----') AND (!AppPackUtils::path_exists('---AppCodePack-Package---.log')) AND (strpos((string)AppPackUtils::read('---AppCodePack-Result---.log'), '##### Processing DONE / '.APPCODEPACK_STRATEGY.' - '.APPCODEPACK_MARKER_OPTIMIZATIONS.' :') === 0)) {
						echo '<option value="pack">RELEASE: PACKAGE :: Optimizations Folder: ['.AppPackUtils::escape_html((string)APPCODEPACK_PROCESS_OPTIMIZATIONS_DIR).']</option>'."\n";
					} //end if
					echo '<option value="cleanup">RELEASE: CLEANUP :: Optimizations Folder: ['.AppPackUtils::escape_html((string)APPCODEPACK_PROCESS_OPTIMIZATIONS_DIR).']</option>'."\n";
				} //end if
				echo '</optgroup>'."\n";
				if((AppPackUtils::is_type_file('appcodepack-extra-run.php')) AND (AppPackUtils::is_type_file('appcodepack-extra-run.inc.htm'))) {
					echo AppPackUtils::read('appcodepack-extra-run.inc.htm');
				} //end if
				echo '</select> &nbsp; ';
				echo '<button style="padding: 3px 12px 3px 12px !important; font-size:1em !important; font-weight:bold !important; color:#FFFFFF !important; background-color:#4B73A4 !important; border:1px solid #3C5A98 !important; border-radius:3px !important; cursor: pointer !important;" onClick="var selTask = \'\'; try { selTask = document.getElementById(\'task-run-sel\').value; } catch(err){} if(selTask){ if(selTask == \'pack\') { var comment = prompt(\'Enter a Package Release Info\', \'\'); self.location = \'?run=\' + selTask + \'&comment=\' + encodeURIComponent(comment ? String(comment) : \'\'); } else { self.location = \'?run=\' + selTask; } } else { alert(\'No Task Selected ...\'); }" title="Click this button to run the selected task from the near list">Run the Selected TASK</button>'."\n";
				echo '</div>';
				//--
				if(AppPackUtils::path_exists('---AppCodePack-Package---.log')) {
					$the_archdir = AppPackUtils::safe_pathname('#---APPCODE-PACKAGES---#');
					if(AppPackUtils::is_type_dir($the_archdir)) {
						clearstatcache(true, (string)$the_archdir);
						$arr_dir_packs = scandir((string)$the_archdir); // don't make it array, can be false
						if(($arr_dir_packs !== false) AND (AppPackUtils::array_size($arr_dir_packs) > 0)) {
							echo '<form method="post" action="?run=deploy">'."\n";
							echo '<br><hr><div style="background:#ECECEC; color:#333333; border-radius:5px; padding:8px; margin-bottom:5px; font-weight:bold;"><h2>List of available Release Packages:</h2>';
							$pkcnt = 0;
							for($i=0; $i<AppPackUtils::array_size($arr_dir_packs); $i++) {
								if(((string)trim((string)$arr_dir_packs[$i]) != '') AND ((string)$arr_dir_packs[$i] != '.') AND ((string)$arr_dir_packs[$i] != '..')) { // fix ok
									if(AppPackUtils::check_if_safe_file_or_dir_name((string)$arr_dir_packs[$i])) {
										if(AppPackUtils::check_if_safe_path((string)$the_archdir.'/'.$arr_dir_packs[$i])) {
											if(AppPackUtils::is_type_file($the_archdir.'/'.$arr_dir_packs[$i])) {
												$pkcnt++;
												echo '<input type="radio" name="netarch_package" value="'.AppPackUtils::escape_html($the_archdir.'/'.$arr_dir_packs[$i]).'">&nbsp;Package #'.(int)$pkcnt.': <a download="'.AppPackUtils::escape_html($arr_dir_packs[$i]).'" href="'.AppPackUtils::escape_html(rawurlencode($the_archdir).'/'.rawurlencode((string)$arr_dir_packs[$i])).'">'.AppPackUtils::escape_html($arr_dir_packs[$i]).'</a>'.'&nbsp;&nbsp;&nbsp;['.AppPackUtils::pretty_print_bytes(AppPackUtils::get_file_size($the_archdir.'/'.$arr_dir_packs[$i]), 2, '&nbsp;').'] @ '.AppPackUtils::escape_html(date('Y-m-d H:i:s O', (int)AppPackUtils::get_file_mtime($the_archdir.'/'.$arr_dir_packs[$i]))).'<br>'."\n";
											} //end if
										} //end if
									} //end if
								} //end if
							} //end for
							$arr_pack_urls = (string) trim((string)APPCODEPACK_APP_UNPACK_URL);
							if(((string)$arr_pack_urls != '') AND ((string)APPCODEPACK_APP_UNPACK_USER != '') AND ((string)APPCODEPACK_APP_UNPACK_PASSWORD != '')) {
								$arr_pack_urls = (array) explode('|', (string)$arr_pack_urls);
								echo '<br><div><select name="netarch_deploy_url" style="min-width:300px;"><option value="">----- Select a Release Server URL for this Package (from this list) -----</option>'."\n";
								for($z=0; $z<count($arr_pack_urls); $z++) {
									$arr_pack_urls[$z] = (string) trim((string)$arr_pack_urls[$z]);
									if((string)$arr_pack_urls[$z] != '') {
										echo ' <option value="'.AppPackUtils::escape_html((string)$arr_pack_urls[$z]).'">'.'Release Server @'.($z+1).'. '.AppPackUtils::escape_html((string)$arr_pack_urls[$z]).'</option>'."\n";
									} //end if
								} //end for
								echo '</select></div>'."\n";
								echo '<br><button type="submit" style="padding: 3px 12px 3px 12px !important; font-size:1em !important; font-weight:bold !important; color:#FFFFFF !important; background-color:#FF9900 !important; border:1px solid #FFAA00 !important; border-radius:3px !important; cursor: pointer !important;" title="Click this button to proceed">Deploy the selected Package and Unpack the Code on the selected Release Server</button><br><br>';
							} //end if
							echo '<br></div><br>'."\n";
							echo '</form>'."\n";
						} //end if
					} //end if
				} //end if
				//--
			} //end if
			//--
		} //end if else
		//--
		echo '<hr><div align="right"><small style="color:#CCCCCC;">&copy; 2013-'.date('Y').' unix-world.org</small></div><br>'."\n";
		if($_GET['run']) {
			echo '<button style="position:fixed; top:10px; right:10px; padding: 3px 12px 3px 12px !important; font-size:1em !important; font-weight:bold !important; color:#FFFFFF !important; background-color:#4B73A4 !important; border:1px solid #3C5A98 !important; border-radius:3px !important; cursor: pointer !important;" onclick="self.location=\'?\';" title="Click this button to return to the application main index">Return to Index</button>';
		} //end if
		echo '</body>'."\n".'</html>';
		AppPackUtils::InstantFlush();
		//--
		//=====

	} //END FUNCTION


	private static function pack_run_extra_script($task, $path_to_extra_script) {
		//--
		if(AppPackUtils::path_exists('---AppCodePack-RunExtra---.log')) {
			return "\n".'<div title="Status / Warning" style="background:#FFCC00; color:#000000; font-weight:bold; padding:5px; border-radius:5px;">'.'TASK: '.AppPackUtils::escape_html((string)strtoupper((string)$task)).' / STATUS: WARNING !'.'<br>'.'<br>'.'This task has already run ...'.'</div>'."\n";
		} //end if
		//--
		define('APPCODEPACK_PROCESS_EXTRA_RUN', (string)$task); // required by appcodepack-extra-run.php
		//--
		AppPackUtils::write(
			'---AppCodePack-RunExtra---.log',
			'START Processing ['.date('Y-m-d H:i:s O').'] ...'."\n".'TASK: `'.$task.'`'."\n"
		);
		//--
		$task = (string) strtoupper((string)$task);
		//--
		$the_run_err = '';
		$the_run_output = '';
		ob_start();
		try {
			include_once((string)$path_to_extra_script); // don't suppress output errors !!
		} catch (Exception $e) {
			$the_run_err = (string) $e->getMessage();
		} //end try catch
		$the_run_output = ob_get_contents();
		ob_end_clean();
		//--
		AppPackUtils::write(
			'---AppCodePack-RunExtra---.log',
			'END Processing ['.date('Y-m-d H:i:s O').'] ...'."\n".'ERRORS: `'.$the_run_err.'`',
			'a'
		);
		//--
		if(defined('APPCODEPACK_PROCESS_EXTRA_RUN_EXTERNAL')) {
			//--
			echo "\n".'<div title="Status / External" style="background:#778899; color:#FFFFFF; font-weight:bold; padding:5px; border-radius:5px;">'.'TASK: '.AppPackUtils::escape_html((string)strtoupper((string)$task)).' / STATUS: EXTERNAL ...'.'</div><br>'."\n";
			return '<div><center><iframe name="PackRunExternalResponseSandBox" id="PackRunExternalResponseSandBox" scrolling="auto" marginwidth="5" marginheight="5" hspace="0" vspace="0" frameborder="0" style="width:96vw; min-width:920px; min-height:70vh; height:max-content; border:1px solid #CCCCCC;" src="'.AppPackUtils::escape_html((string)APPCODEPACK_PROCESS_EXTRA_RUN_EXTERNAL).'"></iframe></center></div><br>';
			//--
		} else {
			//--
			if($the_run_err) {
				return "\n".'<div title="Status / Errors" style="background:#FF3300; color:#FFFFFF; font-weight:bold; padding:5px; border-radius:5px;">'.'TASK: '.AppPackUtils::escape_html((string)strtoupper((string)$task)).' / STATUS: ERROR !'.'<br><pre>'.AppPackUtils::escape_html($the_run_err).'</pre></div>'.($the_run_output ? '<pre style="font-size:13px!important; font-weight:bold;">'.AppPackUtils::escape_html($the_run_output).'</pre>' : '')."\n";
			} else {
				return "\n".'<div title="Status / OK" style="background:#98C726; color:#000000; font-weight:bold; padding:5px; border-radius:5px;"><h3>[ &nbsp; '.'TASK: '.AppPackUtils::escape_html((string)strtoupper((string)$task)).' / STATUS: OK'.' &nbsp; &radic; &nbsp; ]</h3></div>'.($the_run_output ? '<pre style="font-size:13px!important; font-weight:bold;">'.AppPackUtils::escape_html($the_run_output).'</pre>' : '')."\n".'<!-- {APPCODEPACK:[@SUCCESS(Task:'.AppPackUtils::escape_html((string)strtoupper((string)$task)).')@]} -->';
			} //end if else
			//--
		} //end if else
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


final class AppCodeOptimizer {

	// ->
	// v.20200121

	private $log;
	private $err;
	private $debug;
	private $counters = array();
	private $sfnostrip = array();
	private $sfnopack = array();
	private $sfdevonly = array();


	//====================================================
	public function __construct($y_debug='') {
		//--
		$this->debug = (bool) $y_debug;
		//--
		clearstatcache(true); // do a full clear stat cache at the begining
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	/*
	This is the main optimizations function
	*/
	public function optimize_code($dirsource, $subdir='') {

	//--
	$mode = (string) APPCODEPACK_PROCESS_OPTIMIZATIONS_MODE;
	//--

	//--
	$dirsource = (string) AppPackUtils::safe_pathname(trim((string)$dirsource));
	$subdir = (string) AppPackUtils::safe_pathname(trim((string)$subdir));
	//--

	//--
	if((string)$subdir == '') {
		//-- reset
		$this->log = '';
		$this->err = '';
		$this->counters = array();
		//--
		$this->counters['dirs']++;
		//--
		echo '<span style="color:#DCDCDC;"><b>Sources Root Folder: ['.AppPackUtils::escape_html($dirsource).']</b></span> : ';
		echo '<span title="AppCodePack :: START Processing Folder: '.AppPackUtils::escape_html($dirsource).'" style="color:#000000;text-weight:bold;cursor:default;"> &laquo;<b>&starf;</b>&raquo; </span>'."\n";
		//--
	} else {
		//--
		echo '<span title="Sources Sub-Folder: '.AppPackUtils::escape_html($dirsource.'/'.$subdir).'" style="color:#778899;text-weight:bold;cursor:default;"> &laquo;.&raquo; </span>'."\n";
		//--
	} //end if
	//--
	AppPackUtils::InstantFlush();
	//--

	//--
	if((string)$dirsource == '') {
		$this->err = 'ERROR: Empty Source Folder ...';
		return;
	} //end if
	//--

	//--
	$dirsource = (string) $dirsource;
	$dirrealpathsource = (string) realpath((string)$dirsource);
	//--
	if(substr($dirsource, -1, 1) == '/') {
		$this->err = 'ERROR: Source Folder: '.$dirsource.' Trailing Slash MUST NOT BE USED !';
		return;
	} //end if
	//--
	if((!AppPackUtils::is_type_dir($dirsource)) OR (!AppPackUtils::is_type_dir($dirrealpathsource))) {
		$this->err = 'ERROR: Source Folder: '.$dirsource.'=('.$dirrealpathsource.') does NOT EXISTS !';
		return;
	} //end if
	//--

	//--
	$dirdest = (string) APPCODEPACK_PROCESS_OPTIMIZATIONS_DIR;
	AppPackUtils::raise_error_if_unsafe_path($dirdest); // must be relative path
	//--

	//--
	$originaldirsource = (string) $dirsource; // preserve this for recurring
	$originalsubdir = '';
	if((string)$subdir != '') {
		$originalsubdir = (string) AppPackUtils::add_dir_last_slash($subdir); // relative path
		$dirdest = (string) AppPackUtils::add_dir_last_slash($dirdest).$subdir; // relative path
		AppPackUtils::raise_error_if_unsafe_path($dirdest); // must be relative path
		$dirsource = (string) $dirsource.'/'.$subdir; // absolute path
	} //end if
	//--

	//--
	if((string)$subdir == '') {
		if(AppPackUtils::path_exists($dirdest)) {
			$this->err = 'ERROR: Destination Folder ALREADY EXISTS: ['.basename($dirdest).']';
			return;
		} //end if
	} //end if
	//--

	//--
	if($this->debug) {
		$this->add_to_log('Source Folder: '.$dirsource);
		$this->add_to_log('Destination Folder: '.$dirdest);
	} //end if
	//--

	//--
	if(!AppPackUtils::have_access_read($dirsource)) {
		$this->err = 'ERROR: The Source Folder: ['.$dirsource.'] is not readable !';
		return;
	} //end if
	//--
	if($handle = opendir($dirsource)) {
		//--
		if((string)$subdir == '') {
			if(AppPackUtils::path_exists($dirdest)) {
				$this->err = 'ERROR: The Destination Folder: ['.$dirdest.'] already exists !';
				return;
			} //end if
		} //end if
		//--
		AppPackUtils::dir_create($dirdest);
		//--
		if(!AppPackUtils::is_type_dir($dirdest)) {
			$this->err = 'ERROR: The Destination Folder: ['.$dirdest.'] could not be created !';
			return;
		} //end if
		if(!AppPackUtils::have_access_write($dirdest)) {
			$this->err = 'ERROR: The Destination Folder: ['.$dirdest.'] is not writable !';
			return;
		} //end if
		//--
		while(false !== ($file = readdir($handle))) {
			//--
			if(((string)$file != '') AND ((string)$file != '.') AND ((string)$file != '..') AND ((substr($file, 0, 1) != '.') OR ((string)$file == '.htaccess'))) { // fix empty
				//--
				$tmp_path = (string) $dirsource.'/'.$file; // absolute path
				$tmp_dest = (string) AppPackUtils::add_dir_last_slash($dirdest).$file; // relative path
				//--
				AppPackUtils::raise_error_if_unsafe_path($tmp_path, 'no');
				AppPackUtils::raise_error_if_unsafe_path($tmp_dest);
				//--
				if(AppPackUtils::path_exists($tmp_path)) {
					//--
					if(AppPackUtils::path_exists($tmp_path.'/'.'sf-dev-only.nopack')) { // absolute path
						//--
						$this->counters['dir-nopack']++; // SKIP !!!
						$this->sfnopack[] = (string) $tmp_path;
						//--
						echo '<span title="NO-PACK*DIR: '.AppPackUtils::escape_html($tmp_path).'" style="color:#FF3300;cursor:default;">&laquo;&cross;&raquo;</span>'."\n";
						AppPackUtils::InstantFlush();
						//--
						if($this->debug) {
							$this->add_to_log('No-Pack Folder: '.$tmp_path);
						} //end if
						//--
					} else {
						//--
						if(!AppPackUtils::is_type_dir($tmp_path)) { // FILE
							//-- file or link
							if(AppPackUtils::path_exists($tmp_dest)) {
								$this->err = 'ERROR: The destination FILE already exists: '.$tmp_dest;
								break;
							} //end if
							//--
							$tmp_read_file_head = '';
							$tmp_is_a_file_and_is_devonly = false;
							$tmp_is_a_file_and_needs_strip = false;
							if((substr($tmp_path, -4, 4) == '.php') OR ((substr($tmp_path, -3, 3) == '.js') AND (substr($tmp_path, -7, 7) != '.inc.js')) OR (substr($tmp_path, -4, 4) == '.css')) {
								$tmp_read_file_head = (string) AppPackUtils::read($tmp_path, 255, 'no', 'no'); // read the first 255 bytes of file to search for skip strip comment ; don't deny absolute path
								if(stripos((string)$tmp_read_file_head, '[@[#[!SF.DEV-ONLY!]#]@]') !== false) {
									$tmp_is_a_file_and_is_devonly = true;
								} elseif(stripos((string)$tmp_read_file_head, '[@[#[!NO-STRIP!]#]@]') === false) {
									$tmp_is_a_file_and_needs_strip = true;
								} //end if
								$tmp_read_file_head = '';
							} //end if
							//--
							if($tmp_is_a_file_and_is_devonly === true) {
								//--
								$this->counters['dev-only-files']++;
								$this->sfdevonly[] = (string) $tmp_path;
								//--
								if(substr($tmp_path, -4, 4) == '.php') {
									echo '<span title="PHP @ DEV-ONLY: '.AppPackUtils::escape_html($tmp_path).'" style="color:#FF3300;cursor:default;">&clubs;</span>'."\n";
								} elseif(substr($tmp_path, -3, 3) == '.js') {
									echo '<span title="JS @ DEV-ONLY: '.AppPackUtils::escape_html($tmp_path).'" style="color:#FF3300;cursor:default;">&spades;</span>'."\n";
								} elseif(substr($tmp_path, -4, 4) == '.css') {
									echo '<span title="CSS @ DEV-ONLY: '.AppPackUtils::escape_html($tmp_path).'" style="color:#FF3300;cursor:default;">&hearts;</span>'."\n";
								} else {
									echo '<span title="File @ DEV-ONLY: '.AppPackUtils::escape_html($tmp_path).'" style="color:#FF3300;cursor:default;">&diams;</span>'."\n";
								} //end if else
								//--
								AppPackUtils::InstantFlush();
								//--
							} else {
								//--
								if((substr($tmp_path, -4, 4) == '.php') AND ($tmp_is_a_file_and_needs_strip === true)) {
									//--
									$this->counters['php']++;
									//--
									echo '<span title="PHP: '.AppPackUtils::escape_html($tmp_path).'" style="color:#4F5B93;cursor:default;">&clubs;</span>'."\n";
									AppPackUtils::InstantFlush();
									//--
									if($this->debug) {
										$this->add_to_log('PHP Script: '.$tmp_path);
									} //end if
									//--
									if((string)$mode == 'comments') { // remove comments
										//--
										$the_php_proc_mode = 'CS'; // {{{SYNC-SIGNATURE-STRIP-COMMENTS}}}
										$tmp_content = PhpOptimizer::strip_code($tmp_path);
										//--
									} else { // minify code
										//--
										$the_php_proc_mode = 'ZM'; // zend minify
										$tmp_content = PhpOptimizer::minify_code($tmp_path);
										//--
									} //end if else
									//--
									if((string)trim((string)$tmp_content) == '') {
										$this->err = 'ERROR: EMPTY PHP FILE: '.$tmp_path.' @ '.$tmp_dest;
										break;
									} //end if
									//--
									$tmp_content = '<?php'."\n".'// PHP-Script ('.$the_php_proc_mode.'): '.basename($tmp_path).' @ '.date('Y-m-d H:i:s O')."\n".trim(substr($tmp_content, 5));
									//--
									$out = AppPackUtils::write(
										(string) $tmp_dest,
										(string) $tmp_content
									);
									$chk = AppPackUtils::is_type_file($tmp_dest);
									//--
									if(($out != 1) OR (!$chk)) {
										$this->err = 'ERROR: A PHP FILE failed to be created: '.$tmp_path.' @ '.$tmp_dest;
										break;
									} else {
										if((string)$mode == 'comments') { // if remove comments, recheck syntax
											$tmp_chksyntax = PhpOptimizer::minify_code($tmp_dest);
											if((string)$tmp_chksyntax == '') {
												$this->err = 'ERROR: A PHP FILE check syntax FAILED: '.$tmp_path.' @ '.$tmp_dest;
												break;
											} //end if
											$tmp_chksyntax = '';
										} //end if
									} //end if else
									//--
								} elseif((substr($tmp_path, -3, 3) == '.js') AND (substr($tmp_path, -7, 7) != '.inc.js') AND ($tmp_is_a_file_and_needs_strip === true)) {
									//--
									$this->counters['js']++;
									//--
									echo '<span title="JS: '.AppPackUtils::escape_html($tmp_path).' " style="color:#FFCC00;cursor:default;">&spades;</span>'."\n";
									AppPackUtils::InstantFlush();
									//--
									if($this->debug) {
										$this->add_to_log('JS-Script: '.$tmp_path);
									} //end if
									//--
									if((string)$mode == 'comments') { // remove comments
										//--
										$the_compressor_signature = 'CS'; // {{{SYNC-SIGNATURE-STRIP-COMMENTS}}}
										$tmp_content = (string) JsOptimizer::strip_code($tmp_path);
										$tmp_error = '';
										if((string)$tmp_content == '') {
											$tmp_error = 'ERROR: Empty Output from Js: '.$tmp_path;
										} //end if
										//--
									} else { // minify code
										//--
										$the_compressor_signature = (string) APPCODEPACK_COMPRESS_UTILITY_TYPE.'M';
										$tmp_arr = (array) JsOptimizer::minify_code($tmp_path);
										$tmp_content = (string) $tmp_arr['content'];
										$tmp_error = (string) $tmp_arr['error'];
										$tmp_arr = array();
										unset($tmp_arr);
										//--
									} //end if else
									//--
									if((strlen($tmp_error) > 0) OR ((string)$tmp_content == '')) {
										$this->err = 'ERROR: Failed to process a JS-Script: '.$tmp_path.' @ ['.$tmp_error.']';
										break;
									} //end if
									//--
									$out = AppPackUtils::write(
										(string) $tmp_dest,
										'// JS-Script ('.$the_compressor_signature.'): '.basename($tmp_path).' @ '.date('Y-m-d H:i:s O')."\n".$tmp_content."\n".'//END'
									);
									$chk = AppPackUtils::is_type_file($tmp_dest);
									//--
									if(($out != 1) OR (!$chk)) {
										$this->err = 'ERROR: A JS-Script failed to be created: '.$tmp_path.' @ '.$tmp_dest;
										break;
									} //end if else
									//--
								} elseif((substr($tmp_path, -4, 4) == '.css') AND ($tmp_is_a_file_and_needs_strip === true)) {
									//--
									$this->counters['css']++;
									//--
									echo '<span title="CSS: '.AppPackUtils::escape_html($tmp_path).'" style="color:#98BF21;cursor:default;">&hearts;</span>'."\n";
									AppPackUtils::InstantFlush();
									//--
									if($this->debug) {
										$this->add_to_log('Css-Stylesheet: '.$tmp_path);
									} //end if
									//--
									if((string)$mode == 'comments') { // remove comments
										//--
										$the_compressor_signature = 'CS'; // {{{SYNC-SIGNATURE-STRIP-COMMENTS}}}
										$tmp_content = (string) CssOptimizer::strip_code($tmp_path);
										$tmp_error = '';
										if((string)$tmp_content == '') {
											$tmp_error = 'ERROR: Empty Output from Css: '.$tmp_path;
										} //end if
										//--
									} else { // minify code
										//--
										$the_compressor_signature = (string) APPCODEPACK_COMPRESS_UTILITY_TYPE.'M';
										$tmp_arr = CssOptimizer::minify_code($tmp_path);
										$tmp_content = (string) $tmp_arr['content'];
										$tmp_error = (string) $tmp_arr['error'];
										$tmp_arr = array();
										unset($tmp_arr);
										//--
									} //end if else
									//--
									if((strlen($tmp_error) > 0) OR ((string)$tmp_content == '')) {
										$this->err = 'ERROR: Failed to process a CSS-Stylesheet: '.$tmp_path.' @ ['.$tmp_error.']';
										break;
									} //end if
									//--
									$out = AppPackUtils::write(
										(string) $tmp_dest,
										'/* CSS-Stylesheet ('.$the_compressor_signature.'): '.basename($tmp_path).' @ '.date('Y-m-d H:i:s O').' */'."\n".$tmp_content."\n".'/* END */'
									);
									$chk = AppPackUtils::is_type_file($tmp_dest);
									//--
									if(($out != 1) OR (!$chk)) {
										$this->err = 'ERROR: A CSS-Stylesheet failed to be created: '.$tmp_path.' @ '.$tmp_dest;
										break;
									} //end if else
									//--
								} else {
									//--
									if(substr($tmp_path, -4, 4) == '.php') { // php skipped scripts
										$this->counters['files-nostrip']++;
										$this->sfnostrip[] = (string) $tmp_path;
										echo '<span title="PHP *No-Strip* : '.AppPackUtils::escape_html($tmp_path).'" style="color:#E13DFC;cursor:default;">&clubs;</span>'."\n";
									} elseif((substr($tmp_path, -3, 3) == '.js') AND (substr($tmp_path, -7, 7) != '.inc.js')) { // js skipped scripts
										$this->counters['files-nostrip']++;
										$this->sfnostrip[] = (string) $tmp_path;
										echo '<span title="JS *No-Strip* : '.AppPackUtils::escape_html($tmp_path).'" style="color:#E13DFC;cursor:default;">&spades;</span>'."\n";
									} elseif(substr($tmp_path, -4, 4) == '.css') { // css skipped scripts
										$this->counters['files-nostrip']++;
										$this->sfnostrip[] = (string) $tmp_path;
										echo '<span title="CSS *No-Strip* : '.AppPackUtils::escape_html($tmp_path).'" style="color:#E13DFC;cursor:default;">&hearts;</span>'."\n";
									} elseif(substr($file, 0, 1) == '.') { // dot files
										$this->counters['dot-files']++;
										echo '<span title="Dot File: '.AppPackUtils::escape_html($tmp_path).'" style="color:#E13DFC;cursor:default;">&diams;</span>'."\n";
									} else { // the rest of files
										$this->counters['other-files']++;
										echo '<span title="File: '.AppPackUtils::escape_html($tmp_path).'" style="color:#CCCCCC;cursor:default;">&diams;</span>'."\n";
									} //end if else
									//--
									AppPackUtils::InstantFlush();
									//--
									if($this->debug) {
										$this->add_to_log('Other File: '.$tmp_path);
									} //end if
									//--
									$out = AppPackUtils::copy($tmp_path, $tmp_dest, false, true, 'no'); // don't overwrite destination, check copied content, allow absolute path
									$chk = AppPackUtils::is_type_file($tmp_dest);
									//--
									if(($out != 1) OR (!$chk)) {
										$this->err = 'ERROR: A Misc. FILE failed to be copied: '.$tmp_path.' @ '.$tmp_dest;
										break;
									} //end if else
									//--
								} //end if else
								//--
								if(substr($tmp_path, -4, 4) == '.php') { // php skipped scripts
									$lint_chk = (string) PhpOptimizer::lint_code(realpath($tmp_dest));
									if($lint_chk) {
										$this->err = 'ERROR: A PHP FILE syntax check failed: '.realpath($tmp_dest).' @ ['.$lint_chk.']';
										break;
									} //end if
								} elseif((substr($tmp_path, -3, 3) == '.js') AND (substr($tmp_path, -7, 7) != '.inc.js')) { // js skipped scripts
									$lint_chk = (string) JsOptimizer::lint_code(realpath($tmp_dest));
									if($lint_chk) {
										$this->err = 'ERROR: A JS-Script syntax check failed: '.realpath($tmp_dest).' @ ['.$lint_chk.']';
										break;
									} //end if
								} //end if else
								//--
							} //end if else
							//--
						} else { // DIR
							//--
							$this->counters['dirs']++;
							//-- dir
							if($this->debug) {
								$this->add_to_log('Sub-Folder: '.$tmp_path);
							} //end if
							//--
							if(AppPackUtils::is_type_file($tmp_dest)) {
								$this->err = 'ERROR: The destination Sub-Folder is a File: '.$tmp_dest;
								break;
							} //end if
							if(AppPackUtils::is_type_dir($tmp_dest)) {
								$this->err = 'ERROR: The destination Sub-Folder already Exists: '.$tmp_dest;
								break;
							} //end if
							//--
							$out = AppPackUtils::dir_create($tmp_dest, true); // recursive
							$chk = (bool) (AppPackUtils::is_type_dir($tmp_dest) AND AppPackUtils::have_access_write($tmp_dest));
							//--
							if(($out != 1) OR (!$chk)) {
								$this->err = 'ERROR: A Sub-Folder Failed to be Created: '.$tmp_dest;
								break;
							} //end if
							//--
							$this->optimize_code((string)$originaldirsource, (string)$originalsubdir.$file);
							if((string)$this->err != '') {
								break; // bug fix !!!
							} //end if
							//--
						} //end if else
						//--
					} //end if else
					//--
				} //end if
				//--
			} //end if
			//--
		} //end while
		//--
		@closedir($handle);
		//--
	} else {
		//--
		$this->err = 'ERROR: The Folder: ['.$dirsource.'] is not accessible !';
		//--
	} //end if
	//--

	} //END FUNCTION
	//====================================================


	//====================================================
	public function have_errors() {
		//--
		return (bool) strlen(trim((string)$this->err));
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	public function get_errors() {
		//--
		$out = '';
		if($this->have_errors()) {
			$out .= 'LAST FAILURE :: '.$this->err."\n";
		} //end if
		//--
		return (string) $out;
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	public function get_log() {
		//--
		$out = '';
		//--
		$tot_f = (int) ($this->counters['php'] + $this->counters['js'] + $this->counters['css'] + $this->counters['files-nostrip'] + $this->counters['dev-only-files'] + $this->counters['other-files'] + $this->counters['dot-files']);
		$total = (int) ($this->counters['dirs'] + $this->counters['dir-nopack'] + $tot_f);
		//--
		$out .= 'PHP Scripts (processed): '.$this->counters['php']."\n";
		$out .= 'JS Scripts (processed): '.$this->counters['js']."\n";
		$out .= 'CSS Stylesheets (processed): '.$this->counters['css']."\n";
		if(count($this->sfnostrip) > 0) {
			$out .= '* No-Strip * PHP / JS / CSS Files (not processed): '.$this->counters['files-nostrip']."\n";
			for($i=0; $i<count($this->sfnostrip); $i++) {
				$out .= "\t".'- '.$this->sfnostrip[$i]."\n";
			} //end for
		} //end if
		if(($this->counters['dev-only-files'] > 0) OR (count($this->sfdevonly) > 0)) {
			$out .= '* Skipped (Development-Only) * PHP / JS / CSS Files (not copied): '.$this->counters['dev-only-files']."\n";
			for($i=0; $i<count($this->sfdevonly); $i++) {
				$out .= "\t".'- '.$this->sfdevonly[$i]."\n";
			} //end for
		} //end if
		$out .= 'Other Files (copied): '.$this->counters['other-files']."\n";
		if($this->counters['dot-files'] > 0) {
			$out .= '* Dot * Files: '.$this->counters['dot-files']."\n";
		} //end if
		$out .= '** Total Files (includded) **: '.$tot_f."\n";
		$out .= '** Total Dirs (includded) **: '.$this->counters['dirs']."\n";
		if(($this->counters['dir-nopack'] > 0) OR (count($this->sfnopack) > 0)) {
			$out .= '* Skipped (No-Pack) * Dirs (not includded): '.$this->counters['dir-nopack']."\n";
			for($i=0; $i<count($this->sfnopack); $i++) {
				$out .= "\t".'- '.$this->sfnopack[$i]."\n";
			} //end for
		} //end if
		$out .= '*** TOTAL Files and Dirs (includded) ***: '.$total."\n";
		//--
		if((string)$this->log != '') {
			$out .= "\n\n".'##### DEBUG LOG #####'."\n".$this->log;
		} //end if
		//--
		return (string) $out;
		//--
	} //END FUNCTION
	//====================================================


	//##############################################################


	//===============================================================
	private function add_to_log($message) {
		//--
		if(!$this->debug) {
			return;
		} //end if
		//--
		$message = str_replace(array("\n", "\r", "\t"), array(' ', ' ', ' '), (string)$message);
		//--
		$this->log .= $message."\n";
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


final class PhpOptimizer {

	// ::
	// v.20200121

	private static $strip_autoloaded = false;

//====================================================
// php strip
public static function strip_code($y_file) {
	//--
	$y_file = (string) $y_file;
	//--
	AppPackUtils::raise_error_if_unsafe_path($y_file, 'no');
	//--
	if(!AppPackUtils::is_type_file($y_file)) {
		return '';
	} //end if
	//--
	if(!self::$strip_autoloaded) {
		if(!is_file('StripCode.php')) {
			AppPackUtils::raise_error(
				'Not Found: StripCode.php',
				'A required PHP File was not Found: StripCode.php'
			);
		} //end if
		require_once('StripCode.php');
		self::$strip_autoloaded = true;
	} //end if
	//--
	if(!method_exists('StripCode', 'strip_php_code')) {
		AppPackUtils::raise_error(
			'Method Not Found: StripCode::strip_php_code()',
			'A required PHP Method Not Found: StripCode::strip_php_code() in StripCode.php'
		);
	} //end if
	//--
	return (string) StripCode::strip_php_code((string)$y_file);
	//--
} //END FUNCTION
//====================================================


//====================================================
// php minify
public static function minify_code($file) {
	//--
	if((string)$file == '') {
		return '';
	} //end if
	//--
	if(!AppPackUtils::is_type_file($file)) {
		return '';
	} //end if
	//--
	$file_contents = php_strip_whitespace($file);
	//--
	return (string) $file_contents;
	//--
} //END FUNCTION
//====================================================


//====================================================
// php lint
public static function lint_code($y_script_path) {
	//--
	$y_script_path = (string) trim((string)$y_script_path);
	//--
	$err = '';
	//--
	if((string)$y_script_path == '') {
		$err = 'ERROR: PHP-Lint / Empty Script Path';
	} elseif(defined('APPCODEPACK_LINT_PHP_UTILITY_BIN')) {
		if(AppPackUtils::have_access_executable((string)APPCODEPACK_LINT_PHP_UTILITY_BIN)) {
			$parr = (array) AppPackUtils::run_proc_cmd(
				(string) escapeshellcmd((string)APPCODEPACK_LINT_PHP_UTILITY_BIN).' -l '.escapeshellarg($y_script_path),
				null,
				null,
				null
			);
			$exitcode = $parr['exitcode']; // don't make it INT !!!
			$lint_content = (string) $parr['stdout'];
			$lint_errors = (string) $parr['stderr'];
			if(($exitcode !== 0) OR ((string)$lint_errors != '')) { // exitcode is zero (0) on success and no stderror
				$err = 'ERROR: PHP-Lint Failed with ExitCode['.$exitcode.'] on this File: '.$y_script_path."\n".$lint_errors;
			} //end if
		} else {
			$err = 'ERROR: PHP-Lint / BINARY NOT Found: '.APPCODEPACK_LINT_PHP_UTILITY_BIN;
		} //end if
	} //end if
	//--
	return (string) $err;
	//--
} //END FUNCTION
//====================================================


} //END FUNCTION


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


final class JsOptimizer {

	// ::
	// v.20200121

	private static $strip_autoloaded = false;


//====================================================
// js strip
public static function strip_code($y_file) {
	//--
	$y_file = (string) trim((string)$y_file);
	//--
	AppPackUtils::raise_error_if_unsafe_path($y_file, 'no');
	//--
	if(!AppPackUtils::is_type_file($y_file)) {
		return '';
	} //end if
	//--
	if(!self::$strip_autoloaded) {
		if(!is_file('StripCode.php')) {
			AppPackUtils::raise_error(
				'Not Found: StripCode.php',
				'A required PHP File was not Found: StripCode.php'
			);
		} //end if
		require_once('StripCode.php');
		self::$strip_autoloaded = true;
	} //end if
	//--
	if(!method_exists('StripCode', 'strip_js_code')) {
		AppPackUtils::raise_error(
			'Method Not Found: StripCode::strip_js_code()',
			'A required PHP Method Not Found: StripCode::strip_js_code() in StripCode.php'
		);
	} //end if
	//--
	return (string) StripCode::strip_js_code((string)$y_file);
	//--
} //END FUNCTION
//====================================================


//====================================================
// js minify
public static function minify_code($y_script_path) {
	//--
	$y_script_path = (string) trim((string)$y_script_path);
	$y_script_path = (string) realpath((string)$y_script_path);
	//--
	$enc_content = '';
	$err = '';
	//--
	if((string)$y_script_path == '') {
		$err = 'ERROR: JsOptimizer/Minify / Empty Path ...';
	} elseif((defined('APPCODEPACK_COMPRESS_UTILITY_BIN')) && (defined('APPCODEPACK_COMPRESS_UTILITY_MODULE_JS')) && (defined('APPCODEPACK_COMPRESS_UTILITY_OPTIONS_JS'))) {
		if(AppPackUtils::have_access_executable((string)APPCODEPACK_COMPRESS_UTILITY_BIN)) {
			if(AppPackUtils::is_type_file((string)APPCODEPACK_COMPRESS_UTILITY_MODULE_JS)) {
				if(AppPackUtils::have_access_read((string)APPCODEPACK_COMPRESS_UTILITY_MODULE_JS)) {
					//--
					$exitcode = -1;
					$enc_errors = '';
					//--
					switch((string)APPCODEPACK_COMPRESS_UTILITY_TYPE) {
						case 'U': // node uglify
							$parr = (array) AppPackUtils::run_proc_cmd(
								(string) escapeshellcmd((string)APPCODEPACK_COMPRESS_UTILITY_BIN).' '.APPCODEPACK_COMPRESS_UTILITY_MODULE_JS.' '.trim((string)APPCODEPACK_COMPRESS_UTILITY_OPTIONS_JS).' -- '.escapeshellarg($y_script_path),
								null,
								null,
								null
							); // [--beautify beautify=false,ascii-only=true] required to preserve safe unicode sequences ; [--screw-ie8] required to dissalow IE8 hacks to support IE<9 which can break other code
							$exitcode = $parr['exitcode']; // don't make it INT !!!
							$enc_content = (string) $parr['stdout'];
							$enc_errors = (string) $parr['stderr'];
							break;
						case 'G': // google closures compiler
						case 'X': // google closures compiler (js only)
							$parr = (array) AppPackUtils::run_proc_cmd(
								(string) escapeshellcmd((string)APPCODEPACK_COMPRESS_UTILITY_BIN).' -Xmx256m -jar '.APPCODEPACK_COMPRESS_UTILITY_MODULE_JS.' --js '.escapeshellarg($y_script_path).' --jscomp_off \'*\' --warning_level QUIET --env BROWSER --charset UTF-8 '.trim((string)APPCODEPACK_COMPRESS_UTILITY_OPTIONS_JS),
								null,
								null,
								null
							);
							$exitcode = $parr['exitcode']; // don't make it INT !!!
							$enc_content = (string) $parr['stdout'];
							$enc_errors = (string) $parr['stderr'];
							break;
						case 'Y': // yui compressor
							$parr = (array) AppPackUtils::run_proc_cmd(
								(string) escapeshellcmd((string)APPCODEPACK_COMPRESS_UTILITY_BIN).' -Xmx256m -jar '.APPCODEPACK_COMPRESS_UTILITY_MODULE_JS.' '.escapeshellarg($y_script_path).' '.trim((string)APPCODEPACK_COMPRESS_UTILITY_OPTIONS_JS).' --charset UTF-8 --type js',
								null,
								null,
								null
							);
							$exitcode = $parr['exitcode']; // don't make it INT !!!
							$enc_content = (string) $parr['stdout'];
							$enc_errors = (string) $parr['stderr'];
							break;
						default:
							// no other yet supported !
					} //end switch
					//--
					if(($exitcode === 0) AND ((string)$enc_errors == '')) { // exitcode is zero (0) on success and no stderror
						//-- this is risky for a language like javascript or php and fails if comments are inside strings !!!
						if(((string)APPCODEPACK_COMPRESS_UTILITY_TYPE == 'G') OR ((string)APPCODEPACK_COMPRESS_UTILITY_TYPE == 'X') OR ((string)APPCODEPACK_COMPRESS_UTILITY_TYPE == 'Y')) { // just for YUI and GoogleClosures
							// TODO: remove the multiline special comments with ! after * on $enc_content
						} //end if
						//--
					} else {
						//--
						$err = 'ERROR: JsOptimizer/Minify Failed with ExitCode['.$exitcode.'] on this File: '.$y_script_path."\n".$enc_errors;
					} //end if
					//--
					$exitcode = -1;
					$enc_errors = '';
					//--
				} else {
					$err = 'ERROR: JsOptimizer/Minify MODULE is NOT Readable: '.APPCODEPACK_COMPRESS_UTILITY_MODULE_JS;
				} //end if else
			} else {
				$err = 'ERROR: JsOptimizer/Minify MODULE NOT Found: '.APPCODEPACK_COMPRESS_UTILITY_MODULE_JS;
			} //end if
		} else {
			$err = 'ERROR: JsOptimizer/Minify / BINARY NOT Found: '.APPCODEPACK_COMPRESS_UTILITY_BIN;
		} //end if
	} else {
		$err = 'ERROR: JsOptimizer/Minify / Incomplete Configuration ...';
	} //end if else
	//--
	return array('content' => (string)$enc_content, 'error' => (string)$err);
	//--
} //END FUNCTION
//====================================================


//====================================================
// js lint
public static function lint_code($y_script_path) {
	//--
	$y_script_path = (string) trim((string)$y_script_path);
	//--
	$err = '';
	//--
	if((string)$y_script_path == '') {
		$err = 'ERROR: Js-Lint / Empty Script Path';
	} elseif(defined('APPCODEPACK_LINT_NODEJS_UTILITY_BIN')) {
		if(AppPackUtils::have_access_executable((string)APPCODEPACK_LINT_NODEJS_UTILITY_BIN)) {
			$parr = (array) AppPackUtils::run_proc_cmd(
				(string) escapeshellcmd((string)APPCODEPACK_LINT_NODEJS_UTILITY_BIN).' -c '.escapeshellarg($y_script_path), // nodejs check option ( -c ) is available on newser versions
				null,
				null,
				null
			);
			$exitcode = $parr['exitcode']; // don't make it INT !!!
			$lint_content = (string) $parr['stdout'];
			$lint_errors = (string) $parr['stderr'];
			if(($exitcode !== 0) OR ((string)$lint_errors != '')) { // exitcode is zero (0) on success and no stderror
				$err = 'ERROR: Js-Lint Failed with ExitCode['.$exitcode.'] on this File: '.$y_script_path."\n".$lint_errors;
			} //end if
		} else {
			$err = 'ERROR: Js-Lint / BINARY NOT Found: '.APPCODEPACK_LINT_NODEJS_UTILITY_BIN;
		} //end if
	} //end if
	//--
	return (string) $err;
	//--
} //END FUNCTION
//====================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


final class CssOptimizer {

	// ::
	// v.20200121

	private static $strip_autoloaded = false;


//====================================================
// css strip
public static function strip_code($y_file) {
	//--
	$y_file = (string) trim((string)$y_file);
	//--
	AppPackUtils::raise_error_if_unsafe_path($y_file, 'no');
	//--
	if(!AppPackUtils::is_type_file($y_file)) {
		return '';
	} //end if
	//--
	if(!self::$strip_autoloaded) {
		if(!is_file('StripCode.php')) {
			AppPackUtils::raise_error(
				'Not Found: StripCode.php',
				'A required PHP File was not Found: StripCode.php'
			);
		} //end if
		require_once('StripCode.php');
		self::$strip_autoloaded = true;
	} //end if
	//--
	if(!method_exists('StripCode', 'strip_css_code')) {
		AppPackUtils::raise_error(
			'Method Not Found: StripCode::strip_css_code()',
			'A required PHP Method Not Found: StripCode::strip_css_code() in StripCode.php'
		);
	} //end if
	//--
	return (string) StripCode::strip_css_code((string)$y_file);
	//--
} //END FUNCTION
//====================================================


//====================================================
// css minify
public static function minify_code($y_stylesheet_path) {
	//--
	$y_script_path = (string) trim((string)$y_script_path);
	$y_stylesheet_path = (string) realpath((string)$y_stylesheet_path);
	//--
	$enc_content = '';
	$err = '';
	//--
	if((string)$y_stylesheet_path == '') {
		$err = 'ERROR: CssOptimizer/Minify / Empty Path ...';
	} elseif((defined('APPCODEPACK_COMPRESS_UTILITY_BIN')) && (defined('APPCODEPACK_COMPRESS_UTILITY_MODULE_JS')) && (defined('APPCODEPACK_COMPRESS_UTILITY_OPTIONS_CSS'))) {
		if(AppPackUtils::have_access_executable((string)APPCODEPACK_COMPRESS_UTILITY_BIN)) {
			if(AppPackUtils::is_type_file((string)APPCODEPACK_COMPRESS_UTILITY_MODULE_CSS)) {
				if(AppPackUtils::have_access_read((string)APPCODEPACK_COMPRESS_UTILITY_MODULE_CSS)) {
					//--
					$exitcode = -1;
					$enc_errors = '';
					//--
					switch((string)APPCODEPACK_COMPRESS_UTILITY_TYPE) {
						case 'U': // node uglify
							$parr = (array) AppPackUtils::run_proc_cmd(
								(string) escapeshellcmd((string)APPCODEPACK_COMPRESS_UTILITY_BIN).' '.APPCODEPACK_COMPRESS_UTILITY_MODULE_CSS.' '.trim((string)APPCODEPACK_COMPRESS_UTILITY_OPTIONS_CSS).' '.escapeshellarg($y_stylesheet_path),
								null,
								null,
								null
							);
							$exitcode = $parr['exitcode']; // don't make it INT !!!
							$enc_errors = (string) $parr['stderr'];
							$enc_content = (string) $parr['stdout'];
							break;
						case 'G': // google closures stylesheets
							$parr = (array) AppPackUtils::run_proc_cmd(
								(string) escapeshellcmd((string)APPCODEPACK_COMPRESS_UTILITY_BIN).' -Xmx256m -jar '.APPCODEPACK_COMPRESS_UTILITY_MODULE_CSS.' '.escapeshellarg($y_stylesheet_path).' --allow-unrecognized-properties --allow-unrecognized-functions --rename NONE '.trim((string)APPCODEPACK_COMPRESS_UTILITY_OPTIONS_CSS),
								null,
								null,
								null
							);
							$exitcode = $parr['exitcode']; // don't make it INT !!!
							$enc_content = (string) $parr['stdout'];
							$enc_errors = (string) $parr['stderr'];
							break;
						case 'Y': // yui compressor
						case 'X': // yui compressor (css only)
							$parr = (array) AppPackUtils::run_proc_cmd(
								(string) escapeshellcmd((string)APPCODEPACK_COMPRESS_UTILITY_BIN).' -Xmx256m -jar '.APPCODEPACK_COMPRESS_UTILITY_MODULE_CSS.' '.escapeshellarg($y_stylesheet_path).' '.trim((string)APPCODEPACK_COMPRESS_UTILITY_OPTIONS_CSS).' --charset UTF-8 --type css',
								null,
								null,
								null
							);
							$exitcode = $parr['exitcode']; // don't make it INT !!!
							$enc_errors = (string) $parr['stderr'];
							$enc_content = (string) $parr['stdout'];
							break;
						default:
							// no other yet supported ...
					} //end switch
					//--
					if(($exitcode === 0) AND ((string)$enc_errors == '')) { // exitcode is zero (0) on success and no stderror
						//-- this is OK for CSS
						if(((string)APPCODEPACK_COMPRESS_UTILITY_TYPE == 'G') OR ((string)APPCODEPACK_COMPRESS_UTILITY_TYPE == 'X') OR ((string)APPCODEPACK_COMPRESS_UTILITY_TYPE == 'Y')) { // just for YUI and GoogleClosures
							$enc_content = (string) preg_replace((string)APPCODE_REGEX_STRIP_MULTILINE_CSS_COMMENTS, ' ', (string)$enc_content); // remove multi-line comments (the YUI compressor misses some of them)
						} //end if
						//--
					} else {
						//--
						$err = 'ERROR: CssOptimizer/Minify Failed with ExitCode['.$exitcode.'] on this File: '.$y_stylesheet_path."\n".$enc_errors;
					} //end if
					//--
					$exitcode = -1;
					$enc_errors = '';
					//--
				} else {
					$err = 'ERROR: CssOptimizer/Minify MODULE is NOT Readable: '.APPCODEPACK_COMPRESS_UTILITY_MODULE_CSS;
				} //end if else
			} else {
				$err = 'ERROR: CssOptimizer/Minify MODULE NOT Found: '.APPCODEPACK_COMPRESS_UTILITY_MODULE_CSS;
			} //end if
		} else {
			$err = 'ERROR: CssOptimizer/Minify / BINARY NOT Found: '.APPCODEPACK_COMPRESS_UTILITY_BIN;
		} //end if
	} else {
		$err = 'ERROR: CssOptimizer/Minify / Incomplete Configuration ...';
	} //end if else
	//--
	return array('content' => (string)$enc_content, 'error' => (string)$err);
	//--
} //END FUNCTION
//====================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Packager for Software Releases
 *
 * DEPENDS: AppPackUtils
 * DEPENDS-EXT: PHP ZLIB Extension
 * DEPENDS-EXT-OPTIONAL: YUICompressor (external)
 *
 * @access 		private
 * @internal
 *
 */
final class AppNetPackager {

	// ->
	// v.20200121

//=====================================================================================
//--
private $error_log = '';
//--
private $comment = '';
private $optimizations_dir = '';
private $arch_dir = '';
private $arch_content = '';
private $archive_file = '';
private $archive_name = '';
private $num_dirs = 0;
private $num_files = 0;
private $date_time = '';
private $arr_folders = array();
private $arr_files = array();
//--
//=====================================================================================


//=====================================================================================
public function __construct() {
	//--
	// gzencode / gzdecode (rfc1952) have minimal CRC32 checksum for data integrity by default
	// but ... this type of archive goes better and implements SHA1/SHA512 checksums
	//--
	if(!function_exists('gzencode')) {
		AppPackUtils::raise_error('ERROR: The PHP ZLIB Extension (gzencode) is required for AppNetPackager');
		return;
	} //end if
	//--
	$this->init_clear();
	//--
} //END FUNCTION
//=====================================================================================


//=====================================================================================
public function start($y_dir, $y_archive_name, $y_date_time_markup, $comment='') {
	//--
	$this->init_clear();
	//--
	$this->comment = (string) trim((string)$comment);
	if((string)$this->comment == '') {
		$this->comment = '-';
	} else {
		$this->comment = (string) AppPackUtils::deaccent_fix_str_to_iso((string)$this->comment);
		if(strlen($this->comment) > 255) {
			$this->comment = (string) substr($this->comment, 0, 255);
		} //end if
	} //end if
	$this->date_time = (string) $y_date_time_markup;
	$this->arch_dir = (string) AppPackUtils::add_dir_last_slash((string)$y_dir);
	$this->archive_name = (string) AppPackUtils::safe_filename((string)$y_archive_name);
	$this->archive_file = (string) AppPackUtils::safe_pathname((string)$this->arch_dir.$this->archive_name);
	//--
	AppPackUtils::raise_error_if_unsafe_path($this->arch_dir);
	AppPackUtils::raise_error_if_unsafe_path($this->archive_file);
	//--
	if(!AppPackUtils::is_type_dir($this->arch_dir)) {
		AppPackUtils::dir_create($this->arch_dir);
		if(!AppPackUtils::is_type_dir($this->arch_dir)) {
			$this->error_log = 'ERROR: Could not create destination dir !';
			return '';
		} //end if
	} //end if
	//--
	if(AppPackUtils::is_type_file($this->archive_file)) {
		AppPackUtils::delete($this->archive_file);
	} //end if
	//--
	if(AppPackUtils::path_exists($this->archive_file)) {
		$this->error_log = 'ERROR: OLD Archive is still present / Could not remove it !';
		return '';
	} //end if
	//--
	if(strlen($this->arch_dir) <= 0) {
		$this->error_log = 'Packager // Empty Folder Name !';
		return '';
	} //end if
	//--
	if(!AppPackUtils::is_type_dir($this->arch_dir)) {
		$this->error_log = 'Packager // Inexistent Folder !';
		return '';
	} //end if
	//--
	$test = AppPackUtils::write(
		(string) $this->archive_file,
		'' // empty init content
	);
	if($test != 1) {
		$this->error_log = 'Packager // Failed to initialize the new archive file !';
		return '';
	} //end if
	//--
	$this->arch_content .= '#[AppCodePack-Package//START]'."\n";
	$this->arch_content .= '#AppCodePack-Version: '.$this->conform_column(APPCODEPACK_VERSION)."\n";
	$this->arch_content .= '#Package-Date: '.$this->conform_column($this->date_time)."\n";
	//--
} //END FUNCTION
//=====================================================================================


//=====================================================================================
public function get_archive_file_name() {
	//--
	return (string) $this->archive_name;
	//--
} //END FUNCTION
//=====================================================================================


//=====================================================================================
public function get_archive_file_path() {
	//--
	return (string) $this->archive_file;
	//--
} //END FUNCTION
//=====================================================================================


//=====================================================================================
public function pack_dir($y_folder) {
	//--
	if(strlen($this->error_log) <= 0) {
		$this->optimizations_dir = (string) $y_folder;
		$this->dir_recursive_pack((string)$y_folder);
	} //end if
	//--
} //END FUNCTION
//=====================================================================================


//=====================================================================================
public function save() {
	//--
	if((string)$this->error_log != '') {
		return 'AppCodePack.Packager / Save :: InitCheck # '.$this->error_log;
	} //end if
	//--
	if(!defined('APPCODEPACK_APP_ID')) {
		return 'A required constant (APPCODEPACK_APP_ID) has not been defined';
	} //end if
	//--
	$this->arch_content .= '#Folders: '.$this->conform_column($this->num_dirs)."\n";
	$this->arch_content .= '#Files: '.$this->conform_column($this->num_files)."\n";
	$this->arch_content .= '#[AppCodePack-Package//END]'."\n";
	//--
	$packet = @gzencode((string)$this->arch_content, 9, FORCE_GZIP); // don't make it string, may return false
	if(($packet === false) OR ((string)$packet == '')) { // if error
		return 'AppCodePack.Packager / Save :: ZLib Deflate ERROR ! ... # '.$this->error_log;
	} //end if
	$len_data = strlen((string)$this->arch_content);
	$len_arch = strlen((string)$packet);
	if(($len_data > 0) AND ($len_arch > 0)) {
		$ratio = $len_data / $len_arch;
	} else {
		$ratio = 0;
	} //end if
	if($ratio <= 0) { // check for empty input / output !
		return 'AppCodePack.Packager / Save :: ZLib Data Ratio is zero ! ... # '.$this->error_log;
	} //end if
	if($ratio > 32768) { // check for this bug in ZLib {{{SYNC-GZ-ARCHIVE-ERR-CHECK}}}
		return 'AppCodePack.Packager / Save :: ZLib Data Ratio is higher than 32768 ! ... # '.$this->error_log;
	} //end if
	//--
	$ver_zlib = (string) phpversion('zlib');
	//--
	$packet = (string) base64_encode((string)$packet);
	$data  = '';
	//--
	$data .= '#AppCodePack-NetArchive'."\n";
	$data .= '#AppCodePack-Version: '.$this->conform_column(APPCODEPACK_VERSION.' @ gzenc.9.gzip // (c) 2013-'.date('Y').' unix-world.org :: BSD Licensed @ Smart.Framework')."\n";
	$data .= '#Comment: '.$this->conform_column((string)$this->comment)."\n";
	$data .= '#File: '.$this->conform_column((string)$this->archive_name)."\n";
	$data .= '#App-ID: '.$this->conform_column((string)APPCODEPACK_APP_ID)."\n";
	$data .= '#Package-Date: '.$this->conform_column((string)$this->date_time)."\n";
	$data .= '#Package-Info-Dir: '.$this->conform_column((string)$this->optimizations_dir)."\n";
	$data .= '#Package-Info-Items: '.$this->conform_column((string)($this->num_dirs + $this->num_files))."\n";
	$data .= '#Package-Signature:'.$this->conform_column((string)AppPackUtils::sha512($packet))."\n";
	$data .= '#Checksum-Signature:'.$this->conform_column((string)AppPackUtils::sha512($this->arch_content))."\n";
	$data .= (string) $this->conform_column((string)$packet)."\n";
	$data .= '#PHP-Version: '.$this->conform_column((string)PHP_VERSION.' / ZLib: '.(string)$ver_zlib).' / ClientIP: '.(string)$_SERVER['REMOTE_ADDR']."\n";
	$data .= '#END-NetArchive';
	//--
	$packet = ''; // free mem
	//--
	$test = AppPackUtils::write(
		(string) $this->archive_file,
		(string) $data
	);
	if($test != 1) {
		$this->error_log = 'ERROR: Failed to save data to the Archive File !';
	} elseif(!AppPackUtils::is_type_file($this->archive_file)) {
		$this->error_log = 'ERROR: Archive File could not be found !';
	} //end if
	//--
	if((string)$this->error_log != '') {
		return 'AppCodePack.Packager / Save :: Check # '.$this->error_log;
	} //end if
	//--
	$data = '';
	//--
	$out = (string) $this->error_log;
	//--
	$this->init_clear(); // free mem
	//--
	return (string) $out;
	//--
} //END FUNCTION
//=====================================================================================


//=====================================================================================
// [PRIVATE]
private function init_clear() {
	//--
	clearstatcache(true); // do a full clear stat cache at the begining
	//--
	$this->error_log = '';
	//--
	$this->comment = '';
	$this->optimizations_dir = '';
	$this->arch_dir = '';
	$this->arch_content = '';
	$this->archive_file = '';
	$this->archive_name = '';
	$this->num_dirs = 0;
	$this->num_files = 0;
	$this->date_time = '';
	$this->arr_folders = array();
	$this->arr_files = array();
	//--
} //END FUNCTION
//=====================================================================================


//=====================================================================================
// [PRIVATE]
private function dir_pack($tmp_path) {
	//--
	$out = '';
	//--
	$tmp_path = (string) $tmp_path;
	//--
	if((string)trim((string)$tmp_path) == '') {
		$this->error_log = 'ERROR: Empty Dir Name to Pack !';
	} //end if
	if(!AppPackUtils::check_if_safe_path((string)$tmp_path)) {
		$this->error_log = 'ERROR: Invalid Dir Name to Pack: '.$tmp_path;
	} //end if
	//--
	$this->arr_folders[] = $tmp_path;
	//--
	if(AppPackUtils::is_type_dir($tmp_path)) { // dir
		//--
		$this->num_dirs += 1;
		//--
		$cksum_name 	= (string) sha1($tmp_path);
		$tmp_size 		= '0';
		$file_content 	= '';
		$cksum_file 	= '';
		$cksum_arch 	= '';
		//-- dirname[\t]DIR[\t]0[\t]sha1checksumname[\t][\t][\t][\n]
		$out .= $this->conform_column($tmp_path)."\t";
		$out .= $this->conform_column('DIR')."\t";
		$out .= $this->conform_column('0')."\t";
		$out .= $this->conform_column($cksum_name)."\t";
		$out .= "\t";
		$out .= "\t";
		$out .= "\n";
		//--
	} else {
		//--
		$this->error_log = 'ERROR: Invalid Dir to Pack: '.$tmp_path;
		//--
	} //end if
	//--
	return (string) $out;
	//--
} //END FUNCTION
//=====================================================================================


//=====================================================================================
// [PRIVATE]
private function file_pack($tmp_path) {
	//--
	$out = '';
	//--
	$tmp_path = (string) $tmp_path;
	//--
	if((string)trim((string)$tmp_path) == '') {
		$this->error_log = 'ERROR: Empty File Name to Pack !';
	} //end if
	if(!AppPackUtils::check_if_safe_path((string)$tmp_path)) {
		$this->error_log = 'ERROR: Invalid File Name to Pack: '.$tmp_path;
	} //end if
	//--
	$this->arr_files[] = $tmp_path;
	//--
	if(AppPackUtils::is_type_file($tmp_path)) { // file
		//--
		$this->num_files += 1;
		//--
		$the_fsize = (int) filesize($tmp_path);
		//--
		$cksum_name 	= (string) sha1($tmp_path);
		$tmp_type 		= 'FILE';
		$tmp_size 		= (string) $the_fsize;
		$file_content 	= (string) AppPackUtils::read($tmp_path); // this reads and return the file as it is
		if((int)strlen((string)$file_content) !== $the_fsize) {
			$this->error_log = 'ERROR: Invalid FileSize ['.$the_fsize.'] to Pack !'.'<br>'.AppPackUtils::escape_html((string)$tmp_path);
			return '';
		} //end if
		$cksum_file 	= (string) sha1($file_content);
		$file_content 	= (string) bin2hex($file_content);
		$cksum_arch 	= (string) sha1($file_content);
		//-- filename[\t]filetype[\t]filesize[\t]sha1checksumname[\t]sha1checksumfile[\t]sha1checksumarch[\t]filecontent_gzencode-FORCE_GZIP_bin2hex[\n]
		$out .= $this->conform_column($tmp_path)."\t";
		$out .= $this->conform_column($tmp_type)."\t";
		$out .= $this->conform_column($tmp_size)."\t";
		$out .= $this->conform_column($cksum_name)."\t";
		$out .= $this->conform_column($cksum_file)."\t";
		$out .= $this->conform_column($cksum_arch)."\t";
		$out .= $this->conform_column($file_content)."\n";
		//--
	} else {
		//--
		$this->error_log = 'ERROR: Invalid File to Pack: '.$tmp_path;
		//--
	} //end if else
	//--
	return (string) $out;
	//--
} //END FUNCTION
//=====================================================================================


//=====================================================================================
// [PRIVATE]
// recursive function to copy a folder with all sub folders and files
private function dir_recursive_pack($dirsource) {

//===================================
// WARNING: Should Not Copy Destination inside Source to avoid Infinite Loop (anyway there is a loop protection but it is not safe as we don't know if all files were copied) !!!
// WARNING: Last two params SHOULD NOT be used (they are private to remember the initial dirs...)
//=================================== Must not end in Slash !!!
// $dirsource = 'some/folder/one';
//===================================

//--
if(strlen($this->error_log) > 0) {
	return;
} //end if
//--

//-- protection
AppPackUtils::raise_error_if_unsafe_path($dirsource);
//--

//--
if(strlen($dirsource) <= 0) {
	$this->error_log = 'Packager // ERROR: The Archive FileName and Source DirName must not be empty !';
	return '';
} //end if
//--

//--
if(!AppPackUtils::is_type_dir($dirsource)) {
	$this->error_log = 'Packager // ERROR: Source is not a Dir ! \''.$dirsource.'\'';
	return '';
} //end if else
//--

//--
if($handle = opendir($dirsource)) {
	//--
	$this->arch_content .= $this->dir_pack($dirsource);
	//--
	while(false !== ($file = readdir($handle))) {
		//--
		if(((string)$file != '') AND ((string)$file != '.') AND ((string)$file != '..') AND ((string)$file != '.svn') AND ((string)$file != '.git') AND ((string)$file != '.gitignore') AND ((string)$file != '.gitattributes') AND (substr((string)$file, 0, 4) != '.DS_')) { // fix empty
			//--
			$tmp_path = AppPackUtils::add_dir_last_slash($dirsource).$file;
			AppPackUtils::raise_error_if_unsafe_path($tmp_path);
			//--
			if(AppPackUtils::path_exists($tmp_path)) {
				//--
				if(AppPackUtils::is_type_dir($tmp_path)) {
					//--
					$this->arch_content .= $this->dir_pack($tmp_path);
					//--
					if(strlen($this->error_log) > 0) { // if an error is detected
						break;
					} else {
						$this->dir_recursive_pack($tmp_path);
					} //end if
					//--
				} elseif(AppPackUtils::is_type_file($tmp_path)) {
					//--
					$this->arch_content .= $this->file_pack($tmp_path);
					//--
				} else {
					//--
					$this->error_log = 'Packager // ERROR: A broken Link detected: '.$tmp_path;
					//--
				} //end if
				//--
				if(strlen($this->error_log) > 0) { // if an error is detected
					break;
				} //end if
				//--
			} else {
				//--
				$this->error_log = 'Packager // ERROR: Invalid File or Dir ! \''.$tmp_path.'\'';
				break;
				//--
			} //end if
			//--
		} //end if
		//--
	} //end while
	//--
	@closedir($handle);
	//--
} else {
	//--
	$this->error_log = 'Packager // ERROR: Cannot open the Dir ! \''.$dirsource.'\'';
	//--
} //end if else
//--

} //END FUNCTION
//=====================================================================================


//=====================================================================================
// [PRIVATE]
private function conform_column($y_text) {
	//--
	$y_text = trim((string)$y_text);
	//--
	$originals = array("\t", "\n", "\r");
	$replacems = array('', '', '');
	//--
	return (string) str_replace($originals, $replacems, $y_text);
	//--
} //END FUNCTION
//=====================================================================================


} //END CLASS

//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


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
