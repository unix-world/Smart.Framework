<?php
// [Smart.Framework / App - Authenticate / Admin]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//----------------------------------------------------- v.20250214

//======================================================
// App Authenticate Middleware :: Admin / Task Areas Overall Authentication (admin.php and task.php)
// This file must NOT USE Namespaces.
// The functionality of this Middleware is to:
//	* ask for overall authentication for Admin Area and set if successful
//	* if not authenticated, display the login form
//======================================================
// This code will be loaded into the App Boostrap automatically, to provide the Authentication for the admin.php / task.php ...
// By default this code does not contain any classes or functions.
// If you include classes or functions here they must be called to run here as the app boostrap just include this file at runtime
//======================================================

//-------------------------------------------
// This file can be customized as you need.
// It will set an overall authentication for the Admin Area and Task Area.
// Use the default, below, or implement your own ...
//-------------------------------------------

//-------------------------------------------
// SMART.UNICORN AUTHENTICATION SYSTEM FOR ADMIN AND TASK AREAS, MULTI-ACCOUNT (admin.php / task.php)
//-------------------------------------------
// NOTICE: This authentication system implements a Secure Authentication System with the following features:
// 	* multi-user accounts
// 	* secure passwords hashing based on: SHA3-512 and PBKDF2
// 	* fail login timeouts, IP based (DDOS protection after 10 fail logins ...) ; extended DDOS protected by captcha
// INFO: see the modules/mod-auth-admins/doc/README.md on how to setup this in configs ...
//-------------------------------------------
if(!SmartAppInfo::TestIfModuleExists('mod-auth-admins')) {
	SmartFrameworkRuntime::Raise500Error('A required module is missing: `mod-auth-admins` # Smart.Unicorn Authentication ...');
	die('AppAuthAdmin:ModuleMissing:AuthAdmins');
} //end if
final class SmartModelAuthAdmins    extends \SmartModDataModel\AuthAdmins\SqAuthAdmins{}
final class SmartModelAuthLogAdmins extends \SmartModDataModel\AuthAdmins\SqAuthLog{}
\SmartModExtLib\AuthAdmins\SmartAuthAdminsHandler::Authenticate(); // define('APP_AUTH_ADMIN_ENFORCE_HTTPS', true);
\SmartModExtLib\AuthAdmins\SmartAuthAdminsHandler::AuthLock();
//-------------------------------------------

// end of php code
