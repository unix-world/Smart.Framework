<?php
// PHP Auth JWT for Smart.Framework
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
 * Class: \SmartModExtLib\AuthUsers\AuthJwt
 * Manages the Auth JWT Methods
 *
 * @depends 	\SmartModExtLib\AuthUsers\Utils
 * @depends 	\SmartModExtLib\AuthUsers\AuthPlugins
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20250207
 * @package 	AuthUsers
 *
 */
final class AuthJwt {


	public const AUTH_USERS_JWT_EXPIRATION 			= 60 * 24; 			// 1440 m = 1d
	public const AUTH_USERS_COOKIE_JWT_ALGO 		= 'H3S512'; 		// Ed25519, H3S512, H3S384, ...


	public static function validateAuthCookieJwtToken(?string $token) : array {
		//--
		$issuer = (string) \SmartUtils::get_server_current_basedomain_name().':'.\SmartUtils::get_server_current_port();
		$ipaddr = (string) \SmartUtils::get_ip_client(); // this must be the current client IP address to validate with
		//--
		return (array) \SmartAuth::jwt_token_validate((string)self::AUTH_USERS_COOKIE_JWT_ALGO, (string)$issuer, (string)$token, (string)$ipaddr);
		//--
	} //END FUNCTION


	public static function newAuthCookieJwtToken(?string $email, ?string $provider, ?string $mode) : array {
		//--
		// valid $mode: '' | 'cookie' ; used for xtras mode
		//--
		$jwt = [
			'err' 		=> 900,
			'token' 	=> '',
			'serial' 	=> '',
			'sign' 		=> '',
		];
		//--
		$mode = (string) \strtolower((string)\trim((string)$mode));
		switch((string)$mode) {
			case 'cookie':
			case '':
				break;
			default:
				$jwt['err'] = 901;
				return (array) $jwt;
		} //end switch
		//--
		$email = (string) \strtolower((string)\trim((string)$email));
		if((string)$email == '') {
			$jwt['err'] = 902;
			return (array) $jwt;
		} //end if
		if( // {{{SYNC-AUTH-USERS-EMAIL-AS-USERNAME-SAFE-VALIDATION}}}
			((int)\strlen((string)$email) < 5)
			OR
			((int)\strlen((string)$email) > 72)
			OR
			(\strpos((string)$email, '@') == false)
			OR
			(\SmartAuth::validate_auth_ext_username((string)$email) !== true)
		) {
			$jwt['err'] = 903;
			return (array) $jwt;
		} //end if
		//--
		$provider = (string) \trim((string)$provider);
		if((string)$provider == '') {
			$jwt['err'] = 904;
			return (array) $jwt;
		} //end if
		if((string)$provider != '@') { // {{{SYNC-AUTH-USERS-PROVIDER-SELF}}}
			if(!\preg_match((string)\SmartModExtLib\AuthUsers\AuthPlugins::AUTH_USERS_PLUGINS_VALID_ID_REGEX, (string)$provider)) {
				$jwt['err'] = 905;
				return (array) $jwt;
			} //end if
			if(\SmartModExtLib\AuthUsers\AuthPlugins::pluginExists((string)$provider) !== true) {
				$jwt['err'] = 906;
				return (array) $jwt;
			} //end if
		} //end if
		//--
		$expire = (int) self::AUTH_USERS_JWT_EXPIRATION; // minutes
		$issuer = (string) \SmartUtils::get_server_current_basedomain_name().':'.\SmartUtils::get_server_current_port();
		$ipaddr = (string) \SmartUtils::get_ip_client(); // this must be the current client IP address to bind the token by
		//--
		$privs = [];
		$restr = [];
		//--
		$xtras = '-';
		if((string)$mode == 'cookie') {
			$xtras = (string) self::xtrasModeCookie((string)$email).'|['.$provider.']';
		} //end if
		//--
		$token = (array) \SmartAuth::jwt_token_create((string)self::AUTH_USERS_COOKIE_JWT_ALGO, (string)$email, (string)$ipaddr, (string)\SmartModExtLib\AuthUsers\Utils::AUTH_USERS_AREA, (string)$issuer, (int)$expire, (array)$privs, (array)$restr, (string)$xtras, 2); // TF:enc
		if((string)($token['error'] ?? null) != '') {
			\Smart::log_warning(__METHOD__.' # JWT ERR: '.($token['error'] ?? null));
			$jwt['err'] = 951;
			return (array) $jwt;
		} //end if
		//--
		$jwt['err'] 	= false; // set explicit to false
		$jwt['token'] 	= (string) ($token['token'] ?? null);
		$jwt['serial'] 	= (string) ($token['serial'] ?? null);
		$jwt['sign'] 	= (string) ($token['sign'] ?? null);
		//--
		return (array) $jwt;
		//--
	} //END FUNCTION


	public static function xtrasModeCookie(string $email) : string {
		//--
		$issuer = (string) \SmartUtils::get_server_current_basedomain_name().':'.\SmartUtils::get_server_current_port();
		//--
		$safeXtrasSign = (string) \Smart::b64_to_b64s((string)\SmartHashCrypto::sh3a512('AuthUsers:JWT'."\r".$issuer."\r".$email."\r".\SmartUtils::get_visitor_tracking_uid()."\r".\SMART_FRAMEWORK_SECURITY_KEY, true)); // b64s
		//--
		return 'Cookie['.\SmartHashCrypto::crc32b((string)$safeXtrasSign).'.'.\SmartHashCrypto::crc32b((string)\strrev((string)$safeXtrasSign)).']';
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
