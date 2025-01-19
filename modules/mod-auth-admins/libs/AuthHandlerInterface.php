<?php
// Class: \SmartModExtLib\AuthAdmins\AuthHandlerInterface
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
 * Auth Handler Interface
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20250107
 * @package 	development:modules:AuthAdmins
 *
 */
interface AuthHandlerInterface {

	// :: INTERFACE

	//=====
	/**
	 * Auth Handler Authenticate
	 * THIS MUST BE EXTENDED TO HANDLE AN AUTHENTICATION METHOD
	 * RETURN: VOID ; On FAILED Logins this method should STOP EXECUTION and provide the proper HTTP Status Message: ex: 401, 403, 429, ...
	 */
	public static function Authenticate(bool $enforce_https=false) : void;
	//=====


} //END INTERFACE


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================

// end of php code
