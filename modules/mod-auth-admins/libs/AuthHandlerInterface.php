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
//===================================================================================== INTERFACE START [OK: NAMESPACE]
//=====================================================================================


/**
 * Auth Handler Interface
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20260114
 * @package 	development:modules:AuthAdmins
 *
 */
interface AuthHandlerInterface {

	// :: INTERFACE

	//=====
	/**
	 * Auth Handler Authenticate
	 * THIS MUST BE EXTENDED TO HANDLE AN AUTHENTICATION METHOD
	 * RETURN: VOID
	 */
	public static function Authenticate() : void;
	//=====


	//=====
	/**
	 * Auth Handler AuthLock
	 * THIS MUST BE EXTENDED TO LOCK AUTHENTICATION AFTER THE Authenticate has been called
	 * RETURN: VOID
	 */
	public static function AuthLock() : void;
	//=====


} //END INTERFACE


//=====================================================================================
//===================================================================================== INTERFACE END
//=====================================================================================

// end of php code
