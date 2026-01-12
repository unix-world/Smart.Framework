<?php
// PHP Auth Users JWT for Smart.Framework
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
 * Auth Users JWT
 *
 * @depends 	\SmartModExtLib\AuthUsers\Utils
 * @depends 	\SmartModExtLib\AuthUsers\AuthPlugins
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20251230
 * @package 	modules:AuthUsers
 *
 */
final class AuthJwt {

	// ::

	public const AUTH_USERS_JWT_ALGO 				= 'H3S512'; 		// Ed25519, H3S512, H3S384, ...
	public const AUTH_USERS_JWT_EXPIRATION 			= 60 * 24; 			// 1440 m = 1d
	public const AUTH_USERS_JWT_MAX_EXPIRATION 		= 60 * 24 * 365; 	// 1440 m * 365 = 365d


	public static function newAuthJwtToken(?string $mode, ?string $provider, ?string $cluster, ?string $id, ?string $email, array $privs=[], array $restr=[], array $data=[], int $expire=0) : array {
		//--
		// valid $mode: 'cookie' | 'api' ; used for xtras mode
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
			case 'api':
				break;
			default:
				$jwt['err'] = 901;
				return (array) $jwt;
		} //end switch
		//--
		$cluster = (string) \trim((string)$cluster);
		if(\SmartAuth::validate_cluster_id((string)$cluster) !== true) {
			$jwt['err'] = 902;
			return (array) $jwt;
		} //end if
		//--
		$email = (string) \strtolower((string)\trim((string)$email));
		if( // {{{SYNC-AUTH-USERS-EMAIL-AS-USERNAME-SAFE-VALIDATION}}}
			((string)$email == '')
			OR
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
		$id = (string) \trim((string)$id);
		if(((string)$id == '') OR ((int)\strlen((string)$id) != 21)) {
			$jwt['err'] = 904;
			return (array) $jwt;
		} //end if
		$id = (string) \trim((string)\SmartModExtLib\AuthUsers\Utils::userAccountIdToUserName((string)$id));
		if(((string)$id == '') OR ((int)\strlen((string)$id) != 21)) {
			$jwt['err'] = 905;
			return (array) $jwt;
		} //end if
		//--
		$provider = (string) \trim((string)$provider);
		if((string)$provider == '') {
			$jwt['err'] = 906;
			return (array) $jwt;
		} //end if
		if((string)$provider != '@') { // {{{SYNC-AUTH-USERS-PROVIDER-SELF}}}
			if(!\preg_match((string)\SmartModExtLib\AuthUsers\AuthPlugins::AUTH_USERS_PLUGINS_VALID_ID_REGEX, (string)$provider)) {
				$jwt['err'] = 907;
				return (array) $jwt;
			} //end if
			if(\SmartModExtLib\AuthUsers\AuthPlugins::pluginExists((string)$provider) !== true) {
				$jwt['err'] = 908;
				return (array) $jwt;
			} //end if
		} //end if
		//--
		if((int)\Smart::array_size($data) > 0) {
			if(\Smart::array_type_test($data) !== 2) { // if non-empty array. must be associative
				$jwt['err'] = 902;
				return (array) $jwt;
			} //end if
		} //end if
		//--
		if((int)$expire <= 0) { // use default
			$expire = (int) self::AUTH_USERS_JWT_EXPIRATION; // minutes
		} //end if
		if((int)$expire <= 0) { // security check: just in case, disallow non-expiring JWTs
			$jwt['err'] = 909;
			return (array) $jwt;
		} //end if
		if((int)$expire > (int)self::AUTH_USERS_JWT_MAX_EXPIRATION) { // security check: just in case, disallow JWT with larger expiration than max expire
			$jwt['err'] = 910;
			return (array) $jwt;
		} //end if
		//--
		$issuer = (string) \SmartUtils::get_server_current_basedomain_name().':'.\SmartUtils::get_server_current_port();
		//--
		$xtrarr = [
			'provider' 	=> (string) $provider,
			'cluster' 	=> (string) $cluster,
			'id' 		=> (string) $id,
			'data' 		=> (array)  $data,
		];
		//--
		$xtras = (string) \trim((string)self::xtrasMode((string)$mode, (string)$email));
		if((string)$xtras == '') {
			\Smart::log_warning(__METHOD__.' # JWT Xtras are Empty');
			$jwt['err'] = 921;
			return (array) $jwt;
		} //end if
		//--
		$xtras .= '|'.\Smart::json_encode((array)$xtrarr, false, true, false, 2); // max 2 sub-levels ; {{{SYNC-JWT-XTRARR-JSON-LEVELS}}}
		//--
		$ipaddr = '*'; // api
		if((string)$mode == 'cookie') {
			$ipaddr = (string) \SmartUtils::get_ip_client(); // this must be the current client IP address to bind the token by
		} //end if
		if((string)\trim((string)$ipaddr) == '') {
			\Smart::log_warning(__METHOD__.' # JWT IP Address is Empty');
			$jwt['err'] = 922;
			return (array) $jwt;
		} //end if
		//--
		$token = (array) \SmartAuth::jwt_token_create((string)self::AUTH_USERS_JWT_ALGO, (string)$email, (string)$ipaddr, (string)\SmartModExtLib\AuthUsers\Utils::AUTH_USERS_AREA, (string)$issuer, (int)$expire, (array)$privs, (array)$restr, (string)$xtras, 2); // TF:enc
		// \Smart::log_notice(__METHOD__.' # '.\print_r($token,1));
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


	public static function xtrasMode(?string $mode, ?string $email) : string {
		//--
		$uid = '';
		//--
		$mode = (string) \strtolower((string)\trim((string)$mode));
		switch((string)$mode) {
			case 'cookie':
				$uid = (string) \SmartUtils::get_visitor_tracking_uid();
				break;
			case 'api':
				$uid = (string) '{API}'; // TODO: use the user's seckey ?
				break;
			default:
				return '';
		} //end switch
		//--
		if((string)\trim((string)$uid) == '') {
			return '';
		} //end if
		//--
		$issuer = (string) \SmartUtils::get_server_current_basedomain_name().':'.\SmartUtils::get_server_current_port();
		//--
		$safeXtrasSign = (string) \Smart::b64_to_b64s((string)\SmartHashCrypto::sh3a512('AuthUsers:JWT'."\r".$issuer."\r".$mode."\r".$email."\r".\Smart::normalize_spaces((string)$uid)."\r".\SMART_FRAMEWORK_SECURITY_KEY, true)); // b64s
		//--
		return (string) \ucfirst((string)$mode).'['.\SmartHashCrypto::crc32b((string)$safeXtrasSign).'.'.\SmartHashCrypto::crc32b((string)\strrev((string)$safeXtrasSign)).']';
		//--
	} //END FUNCTION


	public static function validateAuthJwtToken(?string $mode, ?string $token) : array {
		//--
		$mode = (string) \strtolower((string)\trim((string)$mode));
		switch((string)$mode) {
			case 'cookie':
			case 'api':
				break;
			default:
				return [
					'error' => 'Invalid Mode: '.$mode,
				];
		} //end switch
		//--
		$ipaddr = '*';
		if((string)$mode == 'cookie') {
			$ipaddr = (string) \SmartUtils::get_ip_client(); // this must be the current client IP address to validate with
		} //end if
		//--
		$issuer = (string) \SmartUtils::get_server_current_basedomain_name().':'.\SmartUtils::get_server_current_port();
		//--
		return (array) \SmartAuth::jwt_token_validate((string)self::AUTH_USERS_JWT_ALGO, (string)$issuer, (string)$token, (string)$ipaddr);
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
