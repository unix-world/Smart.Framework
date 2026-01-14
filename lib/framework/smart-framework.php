<?php
// [LIB - Smart.Framework :: Loader]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework v.8.7 # r.20260114
//======================================================
// Preferred:     PHP 8.2.x / PHP 8.3.x
// Stable with:   PHP 8.1.0 (min) up to PHP 8.4.x (max)
// Unstable with: PHP 8.5 and later versions
// Broken with:   PHP 8.0 and earlier versions
//======================================================
// this library should be loaded from app web root only
//======================================================

// [REGEX-SAFE-OK] ; [PHP8]

//=====================================================================================
// LOAD FRAMEWORK LIBS !!! DO NOT CHANGE THE ORDER OF THE LIBS !!! LIBS DEPEND IN THIS DEFINED ORDER ON THE LIBS LOADED ABOVE !!!
//=====================================================================================
// {{{SYNC-SMART-FRAMEWORK-LIBS-ORDER}}}
//---------------------------------------------------- all these libs depend on lib runtime that need to be loaded via smart runtime before executing any function from these libs ...
require('lib/framework/lib_unicode.php'); 			// smart unicode (support)
require('lib/framework/lib_security.php'); 			// smart security (compliance)
require('lib/framework/lib_smart.php'); 			// smart (base) core + filesysutils
require('lib/framework/lib_cryptohs.php');			// smart crypto (utils) hash
require('lib/framework/lib_cryptoss.php');			// smart crypto (utils) symmetric
require('lib/framework/lib_cryptoas.php');			// smart crypto (utils) asymmetric
require('lib/framework/lib_archive.php');			// smart archive compress/uncompress support
require('lib/framework/lib_caching.php');			// smart cache (non-persistent + abstract persistent)
require('lib/framework/lib_templating.php');		// smart markers templating engine
require('lib/framework/lib_valid_parse.php');		// smart validators and parsers
require('lib/framework/lib_http_cli.php');			// smart http client
require('lib/framework/lib_auth.php');				// smart authentication
//----------------------------------------------------
require('lib/framework/plugins/autoload.php'); 		// auto load  framework plugins # DEPENDS-OPTIONAL: SmartComponents::app_error_message()
//=====================================================================================

// end of php code
