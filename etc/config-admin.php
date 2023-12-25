<?php
// [@[#[!NO-STRIP!]#]@]
// [Smart.Framework / CFG - SETTINGS / ADMIN]
// (c) 2006-2023 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//----------------------------------------------------- r.20231026

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
// HINT: to switch from Simple Auth (single account) to Smart Auth (multi account) see the: modules/app/app-auth-admin.inc.php
//--

//-- *optional* flag to enable passwords safety check for complexity ; default is enabled
define('APP_AUTH_ADMIN_COMPLEX_PASSWORDS', true); // when enabled the passwords require min 8 characters instead of min 7 and must meet certain complexity as having at least 1 character A-Z, 1 character a-z, one digit 0-9, one special character such as: ! @ # $ % ^ & * ( ) _ - + = [ { } ] / | . , ; ? .
//--

/*
//-- initialize
//define('APP_AUTH_ADMIN_INIT_IP_ADDRESS', '127.0.0.1'); // just for Smart Auth ; default is disabled
//-- auth credentials for the admin area (admin.php / task.php) ; CHANGE THEM for production environments, they are just sample ... !!!
// IMPORTANT:
//	- for Simple Auth these have to stay always in this config
//	- for Smart Auth these login credentials (user and password) have to be set just for the first time (initialization) and thereafter have to be completely removed from this config ; after Smart Auth initialization, login using these credentials, go to the Smart Auth Admins Manager and change the initialization password for the admin account ...
define('APP_AUTH_ADMIN_USERNAME', 'superadmin'); // default admins auth account name is: `admin` ; if changed the encrypted passwords below need to be regenerated as they are hashed with the username
define('APP_AUTH_ADMIN_PASSWORD', '$2y$10$oSeHgSWF2ZVdn.JkOmaG4O6dsLEAMHHuC3Xg6ogxhmzV2N/Dsig.y'); // default admins auth complex pass is: `The1pas!` ; complex passwords req. min 8 characters, max 72 ; see the comment above about complexity characters in passwords
// To generate an encrypted password for this config, use: \SmartAuth::password_hash_create($plainTextPassword)
//-- *optional* sample default user private key ; default is disabled ; just for Simple Auth (supports just one hardcoded admin account as set above) ; not used for Smart Auth ; Smart Auth uses and stores per user private keys, in a different place ; default is: 32d2884601694310e81dcece5c71eb04cf999a44f7b7ead03e9ca997aee467c6a18a0901ee8ce2f716acf4b404381b59
//define('APP_AUTH_ADMIN_ENCRYPTED_PRIVKEY', 'bf448.v2!18CVf7_vOgmQjFeOTgNV67eGYS1Ux5GcK1neXwvUgO0m3HMcmjwdsYeIL9b2tYdVFiK47OnXy7VbwKNSSQL1b-kyPogLcjYaUaxGC7N82-HJkFvsLowpvMy95-1jpL_uhCvaYPko5jCRFmjBJQ01rrieOBnw4n2oPVtGOi1ik0bOoXntFaX2YsC8D-pPrynCttBw6yk0WJmW2NlHtYmY2MekwgO5DTqPNblVgHCVctPPKZbFeG3Npw..'); // this is *optional* and used just by the Simple Admin Auth (hardcoded admin account as set above) and may be required just for some extra modules if they are use ...
define('APP_AUTH_ADMIN_ENCRYPTED_PRIVKEY', '');
// To generate an encrypted private key for this config, use \SmartAuth::encrypt_privkey($plainTextKey, \SmartHashCrypto::password($plainTextPassword, $userName))
//-- security: enforce HTTPS
//define('APP_AUTH_ADMIN_ENFORCE_HTTPS', true); // Smart Auth and Simple Auth
//--
*/

//-- admins privileges (Smart Auth and Simple Auth)
define('APP_AUTH_PRIVILEGES', '<super-admin>,<admin>,<oauth2>,<db-admin:mongo>,<cloud>,<svn>'); // sample: '<oauth2>,<db-admin:mongo>,<cloud>,<svn>' // a privilege key can have 2..22 characters and can contain only: a-z 0-9 - ; must start with a-z only
//-- Smart Auth (only) admin area namespaces
$configs['app-auth']['adm-namespaces'] = [
	'Admins Manager' 	=> 'admin.php?page=auth-admins.manager.stml',
	'OAuth2 Manager' 	=> 'admin.php?page=oauth2.manager.stml',
//	'MongoDB Manager' 	=> 'admin.php?page=db-admin.mongodb.stml',
	// ...
//	'[ LOGOUT ]' 		=> 'admin.php?logout=true',
];
//-- task namespaces (Smart Auth and Simple Auth)
$configs['app-auth']['tsk-namespaces'] = [
	'AppRelease.CodePack' => 'task.php?page=app-release.app-manage',
	// ...
];
//--

// end of php code
