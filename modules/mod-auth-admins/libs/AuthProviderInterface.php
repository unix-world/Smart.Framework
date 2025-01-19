<?php
// Class: \SmartModExtLib\AuthAdmins\AuthProviderInterface
// (c) 2008-present unix-world.org - all rights reserved
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
 * @version 	v.20250107
 * @package 	development:modules:AuthAdmins
 *
 */
interface AuthProviderInterface {

	// :: INTERFACE

	public const AUTH_MODE_PREFIX_AUTHEN 		= 'AUTH:';
	public const AUTH_MODE_PREFIX_HTTP_BASIC 	= 'HTTP-BASIC:';
	public const AUTH_MODE_PREFIX_HTTP_BEARER 	= 'HTTP-BEARER:';

	public const REGEX_VALID_USERNAME_OR_BEARER = '/[[:ascii:]]+/'; // valid username can't be outside ASCII characters ; normally some of these characters are not quite supported, but be very permissive here, it is just a simple provider, thus validate later more specific ...
	// username it must be very permissive here, must allow also `#token` suffix ...
	// passwords can contain anything, they are base64 encoded in the URL

	public const AUTH_RESULT = [
		'auth-error' 	=>   '?', // if non-empty, it means error ...
		'auth-safe'  	=> -1000, // auth safety grade ; https basic auth: 100..102 ; http basic auth: 0..2
		'auth-user'  	=>    '', // auth user name, for STANDARD BASIC AUTH
		'auth-pass'  	=>    '', // auth (plain) pass, for STANDARD BASIC AUTH
		'auth-bearer' 	=>    '', // the AUTH Bearer Token, if provided
		'auth-mode'  	=>   '!', // auth mode signature
	];

	//=====
	/**
	 * Auth Provider Get Credentials
	 * THIS MUST BE EXTENDED TO HANDLE AN AUTHENTICATION METHOD
	 * RETURN: ARRAY (instance of AUTH_RESULT)
	 */
	public static function GetCredentials(bool $enable_bearer) : array;
	//=====


} //END INTERFACE


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================

// end of php code
