<?php
// Class: \SmartModExtLib\AuthAdmins\AuthProviderInterface
// (c) 2006-2023 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

namespace SmartModExtLib\AuthAdmins;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

// [PHP8]

//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
//=====================================================================================


/**
 * Auth Provider Interface
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20231020
 * @package 	development:modules:AuthAdmins
 *
 */
interface AuthProviderInterface {

	// :: INTERFACE

	public const AUTH_MODE_PREFIX_AUTHEN 		= 'AUTH:';
	public const AUTH_MODE_PREFIX_HTTP_BASIC 	= 'HTTP-BASIC:';
	public const AUTH_MODE_PREFIX_HTTP_BEARER 	= 'HTTP-BEARER:';

	public const AUTH_USER_NAME_TOKEN 			= '.TOKEN.'; // because it starts/ends with a dot and also have UPPER LETTERS it cannot be validated as a username

	public const AUTH_RESULT = [
		'auth-safe'  => -1, // auth safety grade ; https basic auth: 100..102 ; http basic auth: 0..2
		'auth-mode'  => null,
		'auth-user'  => null,
		'auth-pass'  => null, // plain pass, for STANDARD BASIC AUTH
		'auth-hash'  => null, // encrypted pass hash (irreversible hash of pass), to use with Tokens
		'auth-error' => '?',
	];

	//=====
	/**
	 * Auth Provider Get Credentials
	 * THIS MUST BE EXTENDED TO HANDLE AN AUTHENTICATION METHOD
	 * RETURN: ARRAY (instance of AUTH_RESULT)
	 */
	public static function GetCredentials(bool $is_https_required, bool $enable_tokens) : array;
	//=====


} //END INTERFACE


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================

// end of php code
