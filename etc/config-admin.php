<?php
// [@[#[!NO-STRIP!]#]@]
// [Smart.Framework / CFG - SETTINGS / ADMIN]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//----------------------------------------------------- r.20240118

//--------------------------------------- Templates and Home Page (admin)
$configs['app']['admin-domain'] 					= 'localhost.local'; 					// admin domain as yourdomain.ext
$configs['app']['admin-home'] 						= 'samples.welcome';					// admin home page action
$configs['app']['admin-default-module'] 			= 'samples';							// admin default module
$configs['app']['admin-template-path'] 				= 'default';							// default admin template folder from etc/templates/ will auto-set the path as etc/templates/default/
$configs['app']['admin-template-file'] 				= 'template.htm';						// default admin template file
//--------------------------------------- Templates and Home Page (task)
$configs['app']['task-domain'] 						= 'localhost.local'; 					// task domain as yourdomain.ext
$configs['app']['task-home'] 						= 'auth-admins.tasks';					// task home page action
$configs['app']['task-default-module'] 				= 'auth-admins';						// task default module
$configs['app']['task-template-path'] 				= 'modules/mod-auth-admins/templates/'; // default task template path set to modules/mod-auth-admins/templates/
$configs['app']['task-template-file'] 				= 'template-simple.htm';				// default task template file
//---------------------------------------

//--
// HINTS:
// * to initialize the Smart Auth (multi account for the backend: admin/task areas) see the: modules/mod-auth-admins/doc/README.md
// * to enable the task area (works with IP bind only), also set your IP address(es) (etc/init.php) ; ex: SMART_FRAMEWORK_RUNTIME_TASK_ALLOWED_IPS = '<127.0.0.1>' ; if need multiple, use: '<127.0.0.1>,<127.0.0.2>,<127.0.0.3>' ...
//--

//-- *optional* flag to enable passwords safety check for complexity ; default is enabled
define('APP_AUTH_ADMIN_COMPLEX_PASSWORDS', true); // when enabled the passwords require min 8 characters instead of min 7 and must meet certain complexity as having at least 1 character A-Z, 1 character a-z, one digit 0-9, one special character such as: ! @ # $ % ^ & * ( ) _ - + = [ { } ] / | . , ; ? .
//--

/*
//-- initialize
define('APP_AUTH_ADMIN_INIT_IP_ADDRESS', '127.0.0.1'); // just for initialization
//-- auth initialization credentials for the admin area (admin.php / task.php) ; CHANGE THEM for production environments, they are just sample ... !!!
// IMPORTANT:
//	- these login credentials (user and password) have to be set just for the first time (initialization) and thereafter have to be completely removed from this config ; after Smart Auth initialization, login using these credentials, go to the Smart Auth Admins Manager and change the initialization password for the admin account ...
define('APP_AUTH_ADMIN_USERNAME', 'superadmin'); // default admins auth account name is: `admin` ; if changed the encrypted passwords below need to be regenerated as they are hashed with the username
define('APP_AUTH_ADMIN_PASSWORD', 'The1pas!'); // default admins auth complex pass is: `The1pas!` ; complex passwords req. min 8 characters, max 72 ; see the comment above about complexity characters in passwords
//-- security: enforce HTTPS
//define('APP_AUTH_ADMIN_ENFORCE_HTTPS', true); // if this line is uncommented will enforce HTTPS authentication on both: admin/task areas
//--
*/

//-- admins privileges
define('APP_AUTH_PRIVILEGES', '<super-admin>,<admin>,<oauth2>,<db-admin:mongo>,<cloud>,<svn>'); // sample: '<oauth2>,<db-admin:mongo>,<cloud>,<svn>' // a privilege key can have 2..22 characters and can contain only: a-z 0-9 - ; must start with a-z only
//-- Smart Auth (only) admin area namespaces
$configs['app-auth']['adm-namespaces'] = [
	'Admins Manager' 	=> 'admin.php?page=auth-admins.manager.stml',
	'OAuth2 Manager' 	=> 'admin.php?page=oauth2.manager.stml',
//	'MongoDB Manager' 	=> 'admin.php?page=db-admin.mongodb.stml',
	// ...
//	'[ LOGOUT ]' 		=> 'admin.php?logout=true',
];
//-- task namespaces
$configs['app-auth']['tsk-namespaces'] = [
	'AppRelease.CodePack' => 'task.php?page=app-release.app-manage',
	// ...
];
//--

// end of php code
