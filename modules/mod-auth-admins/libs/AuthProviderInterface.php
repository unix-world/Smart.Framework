<?php
// Class: \SmartModExtLib\AuthAdmins\AuthProviderInterface
// (c) 2006-2022 unix-world.org - all rights reserved
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
 * @version 	v.20220730
 * @package 	development:modules:AuthAdmins
 *
 */
interface AuthProviderInterface {

	// :: INTERFACE

	//=====
	/**
	 * Auth Provider Get Credentials
	 * THIS MUST BE EXTENDED TO HANDLE AN AUTHENTICATION METHOD
	 * RETURN: ARRAY: [ 'auth-user' => 'username', 'auth-pass' => 'pass', 'auth-mode' => 'AUTH:DESCRIPTION', 'auth-safe' => 2 ]
	 */
	public static function GetCredentials(bool $is_https_required);
	//=====


} //END INTERFACE


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================

// end of php code
