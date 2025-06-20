<?php
// PHP Auth Users Namespaces for Smart.Framework
// Module Library
// (c) 2008-present unix-world.org - all rights reserved

// this class integrates with the default Smart.Framework modules autoloader so does not need anything else to be setup

namespace SmartModExtLib\AuthUsers;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: \SmartModExtLib\AuthUsers\AuthNameSpaces
 * Auth Users JWT
 *
 * @depends 	\SmartModExtLib\AuthUsers\Utils
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20250620
 * @package 	modules:AuthUsers
 *
 */
final class AuthNameSpaces {

	// ::


	public static function GetNameSpaces() {
		//--
		$areas = [];
		//--
		if(\SmartEnvironment::isAdminArea() === false) {
			//--
			$translator = \SmartTextTranslations::getTranslator('mod-auth-users', 'auth-users');
			//--
			if(\SmartAuth::is_authenticated() === true) {
				$areas = [
					(string) $translator->text('apps-and-dashboard') 	=> (string) \SmartModExtLib\AuthUsers\AuthClusterUser::getAuthClusterUrlPrefixLocal().\SmartModExtLib\AuthUsers\Utils::AUTH_USERS_URL_APPS,
					(string) $translator->text('my-account') 			=> (string) \SmartModExtLib\AuthUsers\AuthClusterUser::getAuthClusterUrlPrefixMaster().\SmartModExtLib\AuthUsers\Utils::AUTH_USERS_URL_ACCOUNT,
				];
			} else {
				$areas = [
					(string) $translator->text('homepage') 				=> (string) \SmartModExtLib\AuthUsers\AuthClusterUser::getAuthClusterUrlPrefixMaster().\SmartModExtLib\AuthUsers\Utils::AUTH_USERS_URL_SIGNIN,
				];
			} //end if
			//--
		} //end if
		//--
		return (array) $areas;
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================

// end of php code
