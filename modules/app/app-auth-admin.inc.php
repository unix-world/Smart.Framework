<?php
// [Smart.Framework / App - Authenticate / Admin]
// (c) 2006-2021 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//----------------------------------------------------- v.20210526

//======================================================
// App Authenticate Middleware / Admin Area Overall Authentication (admin.php)
// This file must NOT USE Namespaces.
// The functionality of this Middleware is to:
//	* ask for overall authentication for Admin Area and set if successful
//	* if not authenticated, display the login form
//======================================================
// This code will be loaded into the App Boostrap automatically, to provide the Authentication for the admin.php ...
// By default this code does not contain any classes or functions.
// If you include classes or functions here they must be called to run here as the app boostrap just include this file at runtime
//======================================================

//-------------------------------------------
// This file can be customized as you need.
// It will set an overall authentication for the Admin Area.
// Choose from below: Simple or Advanced Authentication, or implement your own ...
//-------------------------------------------

//-------------------------------------------
// SIMPLE AUTHENTICATION SYSTEM FOR ADMIN AND TASK AREAS, SINGLE ACCOUNT (admin.php / task.php)
// modules/mod-auth-admins/libs/SimpleAuthAdminsHandler.php
//-------------------------------------------
// NOTICE: As this is just a simple (very basic) authentication for admin area, it uses a basic authentication with just a single account, with username / password as set in: config-admin.php
// The default credentials as set in config are (you can change them):
// 		username = admin 	(APP_AUTH_ADMIN_USERNAME 	as constant, set in config-admin.php)
// 		password = the-pass (APP_AUTH_ADMIN_PASSWORD 	as constant, set in config-admin.php)
// This is the best way to integrate with framework's authentication system by using SmartAuth:: object.
//-------------------------------------------
if((!SmartAppInfo::TestIfModuleExists('mod-auth-admins')) OR (!class_exists('\\SmartModExtLib\\AuthAdmins\\SimpleAuthAdminsHandler'))) {
	SmartFrameworkRuntime::Raise500Error('A required module is missing: `mod-auth-admins` # SimpleAuth ...');
	die('AppAuthAdmin:ModuleMissing:AuthAdmins');
} //end if
\SmartModExtLib\AuthAdmins\SimpleAuthAdminsHandler::Authenticate(
	false // enforce HTTPS: TRUE/FALSE
);
//-------------------------------------------

//-------------------------------------------
// UNICORN AUTHENTICATION SYSTEM FOR ADMIN AND TASK AREAS, MULTI-ACCOUNT (admin.php / task.php)
// modules/mod-auth-admins/libs/AuthAdminsHandler.php
//-------------------------------------------
// NOTICE: This authentication system is more secure than the Simple Authentication system from above ; Features:
// 	* multi-user accounts
// 	* secure passwords hashing, SHA512
// 	* fail login timeouts, IP based (DDOS protection after 10 fail logins ...)
// INFO: see the modules/mod-auth-admins/doc/README.md on how to setup this in configs ...
//-------------------------------------------
/*
if((!SmartAppInfo::TestIfModuleExists('mod-auth-admins')) OR (!class_exists('\\SmartModExtLib\\AuthAdmins\\AuthAdminsHandler'))) {
	SmartFrameworkRuntime::Raise500Error('A required module is missing: `mod-auth-admins` # Unicorn Auth ...');
	die('AppAuthAdmin:ModuleMissing:AuthAdmins');
} //end if
\SmartModExtLib\AuthAdmins\AuthAdminsHandler::Authenticate(
	false // enforce HTTPS: TRUE/FALSE
);
*/
//-------------------------------------------

// end of php code
