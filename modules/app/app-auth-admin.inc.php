<?php
// [Smart.Framework / App - Authenticate / Admin]
// (c) 2006-2021 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

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
// SIMPLE AUTHENTICATION FOR ADMIN AREA, SINGLE ACCOUNT (admin.php)
// modules/mod-auth-admins/libs/SimpleAuthAdminsHandler.php
//-------------------------------------------
// NOTICE: As this is just a simple (very basic) authentication for admin area, it uses a basic authentication with just a single account, with username / password as set in: config-admin.php
// The default credentials as set in confir are (you can change them):
// 		username = admin 	(APP_AUTH_ADMIN_USERNAME 	as constant, set in config-admin.php)
// 		password = pass 	(APP_AUTH_ADMIN_PASSWORD 	as constant, set in config-admin.php)
// This is the best way to integrate with framework's authentication system by using SmartAuth:: object.
//-------------------------------------------
if((!SmartAppInfo::TestIfModuleExists('mod-auth-admins')) OR (!class_exists('\\SmartModExtLib\\AuthAdmins\\SimpleAuthAdminsHandler'))) {
	SmartFrameworkRuntime::Raise500Error('A required module is missing: `mod-auth-admins` ...');
	die('AppAuthAdmin:ModuleMissing:AuthAdmins');
} //end if
\SmartModExtLib\AuthAdmins\SimpleAuthAdminsHandler::Authenticate(
	false // enforce SSL: TRUE/FALSE
);
//-------------------------------------------

//-------------------------------------------
// ADVANCED AUTHENTICATION FOR ADMIN AREA, MULTI-ACCOUNT (admin.php)
// modules/mod-auth-admins/libs/AuthAdminsHandler.php
//-------------------------------------------
// NOTICE: instead of using the above Simple Authentication you can use a multi-user account Advanced Authentication system with multiple login areas by uncomment the following code and comment out the simple authentication from above:
//-------------------------------------------
/*
if((!SmartAppInfo::TestIfModuleExists('mod-auth-admins')) OR (!class_exists('\\SmartModExtLib\\AuthAdmins\\AuthAdminsHandler'))) {
	SmartFrameworkRuntime::Raise500Error('A required module is missing: `mod-auth-admins` ...');
	die('AppAuthAdmin:ModuleMissing:AuthAdmins');
} //end if
\SmartModExtLib\AuthAdmins\AuthAdminsHandler::Authenticate(
	false // enforce SSL: TRUE/FALSE
);
*/
//-------------------------------------------

// end of php code
