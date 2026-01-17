<?php
// [Smart.Framework / App - Custom Bootstrap]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

//======================================================
// NOTICE: This file can be customized as needed.
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
define('SMART_APP_MODULES_RELEASE', 'm.sf.2026-01-16.2358'); // this can be used for tracking changes to custom app modules
//--

//-- checks the minimum version of the Smart.Framework to run on # v.20260116
define('SMART_APP_MODULES_MIN_FRAMEWORK_VER', 'v.8.7.r.2026.01.16'); // this must be used to validate the required minimum framework version
if(version_compare((string)SMART_FRAMEWORK_RELEASE_TAGVERSION.(string)SMART_FRAMEWORK_RELEASE_VERSION, (string)SMART_APP_MODULES_MIN_FRAMEWORK_VER) < 0) {
	@http_response_code(500);
	die('The Custom App Modules require the Smart.Framework '.SMART_APP_MODULES_MIN_FRAMEWORK_VER.' or later !');
} //end if
//--

// # Uncomment the following to Load the Smart.Framework extra or vendor libs # available in: https://github.com/unix-world/Smart.Framework.Modules
//require_once('modules/smart-extra-libs/autoload.php'); // autoload for Smart.Framework.Modules / (Smart) Extra Libs
//require_once('modules/vendor/autoload.php'); // autoload for Smart.Framework.Modules / Vendor Libs

// # Sample: Load extra vendor libs with autoloaders
//require_once(__DIR__.'/../../vendor/autoload.php'); // PSR standard namespace/class loader(s), from vendor/ directory, in app root ; if using so, add the following security rule in .htaccess: RewriteRule ^vendor/ - [F,L]
//require_once(__DIR__.'/../../../vendor/autoload.php'); // PSR standard namespace/class loader(s), from vendor/ directory, outside of app root

//--
// # Below is a sample code that can handle set languages:
// 	* by cookie: 	 can handle any: master or slave servers ; slave servers are on different subdomains so subdomain as language code rule cannot be used
// 	* by subdomains: can handle just the master server if it can use a subdomain as a language code ; ex: [ www.dom.ext | ro.dom.ext | de.dom.ext ... ]: www => en ; ro => ro ; de => de ...
// !!! IMPORTANT !!! DO NOT ENABLE IT IF ONLY ONE LANGUAGE IS SET IN CONFIGS BECAUSE WILL RAISE AN ERROR ... can work with only 2+ languages defined in configs !
// For the below example:
// 	* 1st param: 'www' will be used for the default language ; must not contain dots
// 	* 2nd param: if TRUE will redirect the 'en' subdomain (because matches the default language as set in SMART_FRAMEWORK_DEFAULT_LANG) to the subdomain to 'www' (1st parameter)
// 	* 3rd param: if TRUE will redirect all other subdomains (except 'www' and the 'en' subdomains), to 'www' (1st parameter)
//--
/*
if(SmartEnvironment::isAdminArea() !== true) { // just for index, not for admin
	if(SMART_FRAMEWORK_AUTH_CLUSTER_ID !== '') { // on slave servers only handle by cookie
		//-- Handles the Language Detection by Cookie ; the Sf_Lang cookie will hold values of language code: '' | 'en' for default ; 'de' will use german language, 'ro' will use romanian language and so on ...
		SmartAppBootstrap::AppSetLanguageByCookie();
		//--
	} else { // on master server which handles also website set language by subdomain
		//-- Handles the Language Detection by SubDomain ; ex: 'www' will use default laguage ; 'de' will use german language, 'ro' will use romanian language and so on ...
		SmartAppBootstrap::AppSetLanguageBySubdomain( // 'en' language for www.mydom.ext ; 'de' language for de.mydom.ext, 'ro' for ro.mydom.ext and so on ...
			'www', 	// default subdomain: www (mapped to default language, en)
			false, 	// redirect default language to default subdomain: FALSE ; if set to TRUE will redirect en.mydom.ext to www.mydom.ext
			false, 	// redirect other subdomains: FALSE ; if set to TRUE any other subdomain except www or a valid language code will be redirected to default, www
			false, 	// notfound other subdomains: FALSE ; if set to TRUE any other subdomain except www or a valid language code will be as 404 not found instead of redirect as above
			[], 	// except subdomains: [] (none) ; if any of the above 2 values are set to TRUE to override that must add subdomain to this array ; ex: [ 'server1' ] will except 'server1' subdomain being redirected or 404 if any of the above two settings are set to TRUE
			null, 	// default lang: use as it is from cfg ; if this is NULL will use the default language for default domain as set in config ; cannot be empty string ! ; otherwise a valid language code must be ; if set this to 'de' for www will use 'de' language instead of 'en'
		);
		//--
		SmartAppBootstrap::AppSetLanguageCookie(); // keep language cookie in sync to be used on slave sub-domains where handling is by cookie, only master can handle by subdomain
		//--
	} //end if else
} //end if
*/
//--

// end of php code
