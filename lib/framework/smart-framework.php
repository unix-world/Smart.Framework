<?php
// [LIB - Smart.Framework :: Loader]
// (c) 2006-2022 unix-world.org - all rights reserved
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
// Smart-Framework v.8.7 # r.20221002
//======================================================
// Requires PHP 7.3 / 7.4 / 8.0 / 8.1 / 8.2 or later
//======================================================
// this library should be loaded from app web root only
//======================================================

// [REGEX-SAFE-OK] ; [PHP8]

//=====================================================================================
// LOAD FRAMEWORK LIBS !!! DO NOT CHANGE THE ORDER OF THE LIBS !!! LIBS DEPEND IN THIS DEFINED ORDER ON THE LIBS LOADED ABOVE !!!
//=====================================================================================
// {{{SYNC-SMART-FRAMEWORK-LIBS-ORDER}}}
//---------------------------------------------------- all these libs depend on lib runtime that need to be loaded via smart runtime before executing any function from these libs ...
require('lib/framework/lib_unicode.php'); 		// smart unicode (support)
require('lib/framework/lib_security.php'); 		// smart security (compliance)
require('lib/framework/lib_registry.php'); 		// smart registry (data records)
require('lib/framework/lib_smart.php'); 		// smart (base) core
require('lib/framework/lib_filesys.php');		// smart file system (utils, fs, get)
require('lib/framework/lib_caching.php');		// smart cache (non-persistent + abstract persistent)
//-------
require('lib/framework/lib_cryptohs.php');		// smart crypto (utils) hash
require('lib/framework/lib_cryptoas.php');		// smart crypto (utils) symmetric and asymmetric
require('lib/framework/lib_http_cli.php');		// smart http client
require('lib/framework/lib_auth.php');			// smart authentication
require('lib/framework/lib_valid_parse.php');	// smart validators and parsers
require('lib/framework/lib_utils.php');			// smart utils
require('lib/framework/lib_templating.php');	// smart templating (req. a persistent cache adapter, derived from abstract persistent ; ex: x-blackhole)
//----------------------------------------------------
//=====================================================================================

// end of php code
