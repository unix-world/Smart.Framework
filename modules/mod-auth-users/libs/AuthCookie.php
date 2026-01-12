<?php
// PHP Auth Users Cookie for Smart.Framework
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
 * Class: \SmartModExtLib\AuthUsers\AuthCookie
 * Auth Users Auth Cookie
 *
 * @depends \SmartModExtLib\AuthUsers\Utils
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20251229
 * @package 	modules:AuthUsers
 *
 */
final class AuthCookie {

	// ::


	public const AUTH_USERS_SF_COOKIE_NAME  = 'Sf_UserAuth'; // {{{SYNC_USER-AUTH-COOKIE-NAME}}}


	public static function getJwtCookie() : string {
		//--
		if(\SmartUtils::isset_cookie((string)self::AUTH_USERS_SF_COOKIE_NAME) !== true) {
			return '';
		} //end if
		//--
		return (string) \trim((string)\SmartUtils::get_cookie((string)self::AUTH_USERS_SF_COOKIE_NAME));
		//--
	} //END FUNCTION


	public static function setJwtCookie(string $token) : bool {
		//--
		$token = (string) \trim((string)$token);
		if((string)$token == '') {
			return false;
		} //end if
		//-- http only cookie, expires session, for safety, when browser is closed
		if(\SmartUtils::set_cookie((string)self::AUTH_USERS_SF_COOKIE_NAME, (string)$token, 0, '/', '@', '@', false, true) === true) {
			return true;
		} //end if
		//--
		\SmartModExtLib\AuthUsers\Utils::logFailedExtAuth('Auth:Cookie', 500, 'Failed to Set Cookie');
		return false;
		//--
	} //END FUNCTION


	public static function unsetJwtCookie() : bool {
		//-- http only
		if(\SmartUtils::unset_cookie((string)self::AUTH_USERS_SF_COOKIE_NAME, '/', '@', '@', false, true) === true) {
			return true;
		} //end if
		//--
		\SmartModExtLib\AuthUsers\Utils::logFailedExtAuth('Auth:Cookie', 500, 'Failed to Unset Cookie');
		return false;
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
