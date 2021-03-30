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
// Smart-Framework v.7.2
//======================================================
// Requires PHP 7.3 or later
//======================================================
// this library should be loaded from app web root only
//======================================================

// [REGEX-SAFE-OK] ; [PHP8]

//--------------------------------------------------
define('SMART_FRAMEWORK_VERSION', 'smart.framework.v.7.2'); // required for framework to function
//--------------------------------------------------


//=====================================================================================
// LOAD FRAMEWORK LIBS 						!!! DO NOT CHANGE THE ORDER OF THE LIBS !!!
//=====================================================================================
//----------------------------------------------------
require('lib/framework/lib_unicode.php'); 		// smart unicode (support)
require('lib/framework/lib_smart.php'); 		// smart (base) core
require('lib/framework/lib_crypto.php');		// smart crypto utils (asymmetric) :: encrypt as hash only
require('lib/framework/lib_cryptos.php');		// smart crypto utils (symmetric) :: encrypt/decrypt
require('lib/framework/lib_filesys.php');		// smart file system
require('lib/framework/lib_http_cli.php');		// smart http client
require('lib/framework/lib_auth.php');			// smart authentication
require('lib/framework/lib_valid_parse.php');	// smart validators and parsers
require('lib/framework/lib_utils.php');			// smart utils
require('lib/framework/lib_caching.php');		// smart cache (non-persistent + abstract persistent)
require('lib/framework/lib_templating.php');	// smart templating
//----------------------------------------------------
//=====================================================================================


// end of php code
