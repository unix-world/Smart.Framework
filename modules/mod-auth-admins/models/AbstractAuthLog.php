<?php
// Class: \SmartModDataModel\AuthAdmins\AbstractAuthLog
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

namespace SmartModDataModel\AuthAdmins;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
//=====================================================================================


/**
 * Abstract Model for ModAuthAdmins Logging
 * @ignore
 */
abstract class AbstractAuthLog {

	// ->
	// v.20250314


	abstract public function __construct(); // THIS SHOULD BE THE ONLY METHOD IN THIS CLASS THAT THROW EXCEPTIONS !!!
	abstract public function __destruct();

	abstract public function logAuthSuccess(?string $auth_id, ?string $ip, ?string $msg) : bool;
	abstract public function logAuthFail(?string $auth_id, ?string $ip, ?string $msg) : bool;
	abstract public function checkFailLoginsByIp(string $ip) : int;
	abstract public function resetFailedLogins(string $ip) : bool;


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code

