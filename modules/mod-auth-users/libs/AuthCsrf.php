<?php
// PHP Auth Users Csrf for Smart.Framework
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
 * Class: \SmartModExtLib\AuthUsers\AuthCsrf
 * Auth Users Csrf
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20250314
 * @package 	modules:AuthUsers
 *
 */
final class AuthCsrf {

	// ::

	public const AUTH_USERS_COOKIE_NAME_CSRF = 'Sf_UserAuth_Csrf'; // {{{SYNC-AUTH-USERS-COOKIE-NAME-CSRF}}} ; CSRF key


	public static function csrfNewPrivateKey() : string {
		//--
		return (string) \SmartCsrf::newPrivateKey(); // this is the private key, unencrypted
		//--
	} //END FUNCTION


	public static function csrfPrivateKeyEncrypt(string $privKey) : string {
		//--
		$privKey = (string) \trim((string)$privKey);
		if((string)$privKey == '') {
			return '';
		} //end if
		//--
		return (string) \SmartUtils::url_obfs_encrypt((string)$privKey); // def key, TF
		//--
	} //END FUNCTION


	public static function csrfPrivateKeyDecrypt(string $encPrivKey) : string {
		//--
		$encPrivKey = (string) \trim((string)$encPrivKey);
		if((string)$encPrivKey == '') {
			return '';
		} //end if
		//--
		return (string) \SmartUtils::url_obfs_decrypt((string)$encPrivKey); // def key, TF
		//--
	} //END FUNCTION


	// this generate the private key, with a secret based on: ClientIP, UA-Signature and SMART_FRAMEWORK_SECURITY_KEY
	public static function csrfPublicKey(string $privKey) : string {
		//--
		return (string) \SmartCsrf::getPublicKey(
			(string) $privKey,
			(string) self::csrfSecret()
		);
		//--
	} //END FUNCTION


	public static function csrfCheckState(string $pubKey, string $privKey) : bool {
		//--
		$pubKey = (string) \trim((string)$pubKey);
		$privKey = (string) \trim((string)$privKey);
		//--
		$out = false;
		//--
		if(
			((string)$pubKey != '')
			AND
			((string)$privKey != '')
		) {
			$out = (bool) \SmartCsrf::verifyKeys(
				(string) $pubKey,
				(string) $privKey,
				(string) self::csrfSecret()
			);
		} //end if
		//--
		return (bool) $out;
		//--
	} //END FUNCTION


	//======== [ PRIVATES ]


	private static function csrfSecret() : string {
		//-- bind to user Ident + today date
		return (string) 'AuthUsersCsrf'.'!'.\SmartUtils::unique_client_private_key(); // the secret ; do not use \SmartUtils::unique_auth_client_private_key() here, the client is more likely not yet authenticated
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
