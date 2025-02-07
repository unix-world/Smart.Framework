<?php
// PHP Auth 2FA for Smart.Framework
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
 * Class: \SmartModExtLib\AuthUsers\Auth2FA
 * Manages the Auth 2FA Methods
 *
 * @depends
 * @depends
 *
 * @version 	v.20250205
 * @package 	modules:AuthUsers
 *
 */
final class Auth2FA {


	public static function is2FASecretValid(string $secret) : bool {
		//--
		$secret = (string) \trim((string)$secret);
		if((string)$secret == '') {
			return false;
		} //end if
		//--
		if(\SmartModExtLib\AuthAdmins\Auth2FTotp::IsSecretValid((string)$secret) !== true) {
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


	public static function get2FAPinToken(?string $secret) : string {
		//--
		$secret = (string) \trim((string)$secret);
		if((string)$secret == '') {
			return '';
		} //end if
		//--
		if(\SmartModExtLib\AuthAdmins\Auth2FTotp::IsSecretValid((string)$secret) !== true) {
			return '';
		} //end if
		//--
		return (string) \SmartModExtLib\AuthAdmins\Auth2FTotp::GenerateToken((string)$secret);
		//--
	} //END FUNCTION


	public static function get2FAUrl(?string $secret, ?string $id) : string {
		//--
		$secret = (string) \trim((string)$secret);
		if((string)$secret == '') {
			return '';
		} //end if
		//--
		$id = (string) \trim((string)$id);
		if((string)$id == '') {
			return '';
		} //end if
		//--
		$issuer = (string) \SmartUtils::get_server_current_basedomain_name();
		//--
		return (string) \SmartModExtLib\AuthAdmins\Auth2FTotp::GenerateBarcodeUrl((string)$secret, (string)$id, (string)$issuer);
		//--
	} //END FUNCTION


	public static function get2FASvgBarCode(?string $secret, ?string $id) : string {
		//--
		$url = (string) self::get2FAUrl((string)$secret, (string)$id);
		if((string)\trim((string)$url) == '') {
			return '';
		} //end if
		//--
		return (string) \SmartModExtLib\AuthAdmins\Auth2FTotp::GenerateBarcodeQrCodeSVGFromUrl((string)$url, '#ED2559');
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
