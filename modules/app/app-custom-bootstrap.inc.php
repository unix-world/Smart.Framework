<?php
// [Smart.Framework / App - Custom Bootstrap]
// (c) 2006-2021 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

//======================================================
// NOTICE: This file can be customized as you need.
//======================================================
// App Custom Bootstrap Middleware / Shared (for both: index.php / admin.php)
// This code will be loaded into the App Boostrap automatically.
// By default this code does not contain any classes or functions.
// If you include classes or functions here they must be called to run here as the app boostrap just include this file at runtime
//======================================================
// This file must NOT USE Namespaces.
// The functionality of this Middleware is to:
// 	* validate minimal framework requirements
//	* define extra auto-loaders for namespaces / classes
//	* run custom code at app bootstrap like:
// 		- include here some custom code to be executed at the App.Boostrap level before any other code is executed, even before session starts
// 		- define here a Custom Session using: class SmartCustomSession extends SmartAbstractCustomSession {}
// 		- overall start of session (by default session starts just when needed)
// 		- pre-connect to a DB server at boot(strap) time (by default the connections start just when needed)
// 		- ... other purposes ...
//======================================================

//-- defines the modules version (required for AppReleaseHash and modules identification)
define('SMART_APP_MODULES_RELEASE', 'm.sf.2021-03-28'); // this can be used for tracking changes to custom app modules
//--

//-- checks the minimum version of the Smart.Framework to run on
define('SMART_APP_MODULES_MIN_FRAMEWORK_VER', 'v.7.2.1.r.2021.03.28'); // this must be used to validate the required minimum framework version
if(version_compare((string)SMART_FRAMEWORK_RELEASE_TAGVERSION.(string)SMART_FRAMEWORK_RELEASE_VERSION, (string)SMART_APP_MODULES_MIN_FRAMEWORK_VER) < 0) {
	@http_response_code(500);
	die('The Custom App Modules require the Smart.Framework '.SMART_APP_MODULES_MIN_FRAMEWORK_VER.' or later !');
} //end if
//--

// # Load the Smart.Framework extra or vendor libs (if exists) # available in: https://github.com/unix-world/Smart.Framework.Modules
if(is_dir('modules/smart-extra-libs')) {
	require_once('modules/smart-extra-libs/autoload.php'); // autoload for Smart.Framework.Modules / (Smart) Extra Libs
} //end if
if(is_dir('modules/vendor')) {
	require_once('modules/vendor/autoload.php'); // autoload for Smart.Framework.Modules / Vendor Libs
} //end if

// # Sample: Load extra vendor libs with or without autoloaders
//require_once(__DIR__.'/../../vendor/autoload.php'); // PSR standard namespace/class loader(s), from vendor/ directory, in app root ; if using so, add the following security rule in .htaccess: RewriteRule ^vendor/ - [F,L]
//require_once(__DIR__.'/../../../vendor/autoload.php'); // PSR standard namespace/class loader(s), from vendor/ directory, outside of app root

//--
// # Below is a sample code that can handle set languages by subdomains (www.dom.ext | ro.dom.ext | de.dom.ext ...): www => en ; ro => ro ; de => de ...
//--
// Note that the default language (en) will be mapped by default to www ; all the rest of available languages like ro, de, ... will be mapped to each subdomain as above
// The language codes must be enabled as needed in etc/config.php prior to be used
//--
/*
if(SmartFrameworkRuntime::isAdminArea() !== true) { // Handles the Language Detection by SubDomain (just for index, not for admin)
	SmartFrameworkRuntime::AppSetLanguageBySubdomain('www', []); // 'www' will be used for the default language: 'en'
} //end if
*/
//--


// end of php code
