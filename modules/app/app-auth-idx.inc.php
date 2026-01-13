<?php
// [Smart.Framework / App - Authenticate / Index]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//----------------------------------------------------- v.20260112

//======================================================
// App Authenticate Middleware :: Index Area Overall Authentication (index.php)
// This file must NOT USE Namespaces.
// The functionality of this Middleware is to:
//	* ask for overall authentication for Index Area and set if successful
//	* if not authenticated, display the login form
//======================================================
// This code will be loaded into the App Boostrap automatically, to provide the Authentication for the index.php ...
// By default this code does not contain any classes or functions.
// If you include classes or functions here they must be called to run here as the app boostrap just include this file at runtime
//======================================================

//-------------------------------------------
// This file can be customized as you need.
// Generally the Index Area is PUBLIC thus will not need an overall authentication.
// Use the default, below, or implement your own ...
//-------------------------------------------

//-- security: click hijacking protection (choose only one from below)
//@header('X-Frame-Options: SAMEORIGIN'); // basic protection (not working with wildcard domains) ; accepted values: `DENY`, `SAMEORIGIN`, `ALLOWALL`
//@header('Content-Security-Policy: frame-ancestors \'self\' '.$configs['app']['index-domain'].' *.'.$configs['app']['index-domain']); // advanced protection (allow specific domains or wildcard domains)
//--

// # Here is a sample code to handle the privileged IP of localhost as client ; the constant APP_INDEX_IPRANGE_PRIVILEGED can be later used for special privileges when running the app on DEV mode
/*
if(defined('APP_INDEX_IPRANGE_PRIVILEGED')) {
	die('The constant `APP_INDEX_IPRANGE_PRIVILEGED` MUST NOT BE Defined outside AppAuthIndex');
} //end if
if(((string)SmartUtils::get_ip_client() === '127.0.0.1') OR ((string)SmartUtils::get_ip_client() === '::1')) { // IPv4 or IPv6 localhost IP's
	define('APP_INDEX_IPRANGE_PRIVILEGED', false);
} else {
	define('APP_INDEX_IPRANGE_PRIVILEGED', true);
} //end if else
*/

//-------------------------------------------
// SMART.UNICORN AUTHENTICATION SYSTEM FOR INDEX AREA, MULTI-ACCOUNT (index.php)
//-------------------------------------------
// NOTICE: This authentication system implements a Secure Authentication System with the following features:
// 	* multi-user accounts
// 	* secure passwords hashing based on: SHA3-512 and PBKDF2 or BCrypt
// 	* fail login timeouts, IP based (DDOS protection after 10 fail logins ...) ; extended DDOS protected by captcha
// INFO: see the modules/mod-auth-users/doc/README.md on how to setup this in configs ...
//-------------------------------------------
if(defined('SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS') AND (SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS === true)) {
	//--
	if(!SmartAppInfo::TestIfModuleExists('mod-auth-admins')) {
		SmartFrameworkRuntime::Raise500Error('A required module is missing: `mod-auth-admins` # Smart.Unicorn Authentication ...');
		die('AppAuthAdmin:ModuleMissing:AuthAdmins');
	} //end if
	if(!SmartAppInfo::TestIfModuleExists('mod-auth-users')) {
		SmartFrameworkRuntime::Raise500Error('A required module is missing: `mod-auth-users` # Smart.Unicorn Authentication ...');
		die('AppAuthUser:ModuleMissing:AuthUsers');
	} //end if
	\SmartModExtLib\AuthUsers\SmartAuthUsersHandler::Authenticate(); // req. mod-auth-admins, abstract class: \SmartModExtLib\AuthAdmins\AuthHandlerInterface
	\SmartModExtLib\AuthUsers\SmartAuthUsersHandler::AuthLock();
	//--
} //end if
//-------------------------------------------

// end of php code
