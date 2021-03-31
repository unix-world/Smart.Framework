<?php
// [LIB - Smart.Framework]
// (c) 2006-2020 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework v.7.2 # r.20210331
//======================================================
// Requires PHP 7.3 or later
//======================================================
// this library should be loaded from app web root only
//======================================================

// [REGEX-SAFE-OK] ; [PHP8]

//-- PHP version, 64-bit support and various checks
if(version_compare((string)phpversion(), '7.3.0') < 0) { // check for PHP 7.3 or later
	@http_response_code(500);
	die('PHP Runtime not supported: '.phpversion().' !'.'<br>PHP versions to run this software are: 7.3 / 7.4 / 8.0 or later');
} //end if
//--
if(((int)PHP_INT_SIZE < 8) OR ((string)(int)PHP_INT_MAX < '9223372036854775807')) {
	@http_response_code(500);
	die('PHP Runtime not supported: this version of PHP does not support 64-bit Integers (PHP_INT_SIZE should be 8 and is: '.PHP_INT_SIZE.' ; PHP_INT_MAX should be at least 9223372036854775807 and is: '.PHP_INT_MAX.') ...');
} //end if
//--
if((string)(int)strtotime('2038-03-16 07:55:08 UTC') != '2152338908') { // test year2038 bug with an integer value longer than 32-bit max int which is: 2147483647
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

//-- version
if(defined('SMART_FRAMEWORK_VERSION')) {
	@http_response_code(500);
	die('A Reserved Constant have been already defined: SMART_FRAMEWORK_VERSION');
} //end if
define('SMART_FRAMEWORK_VERSION', 'smart.framework.v.7.2'); // required for framework to function
//--

//=====================================================================================
// LOAD FRAMEWORK LIBS !!! DO NOT CHANGE THE ORDER OF THE LIBS !!! LIBS DEPEND EACH ON THE LIBS LOADED ABOVE !!!
//=====================================================================================
//---------------------------------------------------- all these libs depend on lib runtime that need to be loaded via smart runtime before executing any function from these libs ...
require('lib/framework/lib_unicode.php'); 		// smart unicode (support)
require('lib/framework/lib_smart.php'); 		// smart (base) core
require('lib/framework/lib_crypto.php');		// smart crypto utils (asymmetric) :: encrypt as hash only
require('lib/framework/lib_cryptos.php');		// smart crypto utils (symmetric) :: encrypt/decrypt
require('lib/framework/lib_filesys.php');		// smart file system (utils, fs, get)
require('lib/framework/lib_http_cli.php');		// smart http client
require('lib/framework/lib_auth.php');			// smart authentication
require('lib/framework/lib_valid_parse.php');	// smart validators and parsers
require('lib/framework/lib_utils.php');			// smart utils
require('lib/framework/lib_caching.php');		// smart cache (non-persistent + abstract persistent)
require('lib/framework/lib_templating.php');	// smart templating (req. a persistent cache adapter, derived from abstract persistent ; ex: x-blackhole)
//----------------------------------------------------
//=====================================================================================

// end of php code
