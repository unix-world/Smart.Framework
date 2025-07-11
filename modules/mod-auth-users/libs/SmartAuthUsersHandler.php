<?php
// PHP Auth Users Auth Handler for Smart.Framework
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
 * Class: \SmartModExtLib\AuthUsers\SmartAuthUsersHandler
 * Auth Users Smart Auth Handler
 *
 * @depends 	\SmartModExtLib\AuthUsers\AuthJwt
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20250620
 * @package 	modules:AuthUsers
 *
 */
final class SmartAuthUsersHandler
	implements \SmartModExtLib\AuthAdmins\AuthHandlerInterface {

	// ::


	public static function Authenticate() : void {
		//--
		$mode = 'cookie';
		//--
		$token = (string) \trim((string)\SmartModExtLib\AuthUsers\AuthCookie::getJwtCookie());
		if((string)$token == '') {
			return;
		} //end if
		//--
		$jwtValidArr = (array) \SmartModExtLib\AuthUsers\AuthJwt::validateAuthJwtToken((string)$mode, (string)$token);
		if((string)($jwtValidArr['error'] ?? null) != '') {
			\SmartModExtLib\AuthUsers\AuthCookie::usetJwtCookie();
			\SmartModExtLib\AuthUsers\Utils::logFailedExtAuth('JWT:Validation', 401, (string)($jwtValidArr['error'] ?? null));
			return;
		} //end if
		//--
		$email = (string) \trim((string)($jwtValidArr['user-name'] ?? null));
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
			\SmartModExtLib\AuthUsers\AuthCookie::usetJwtCookie();
			\SmartModExtLib\AuthUsers\Utils::logFailedExtAuth('JWT:Validation', 403, 'UserName is Invalid: `'.$email.'`');
			return;
		} //end if
		//--
		$xtras = (string) \trim((string)($jwtValidArr['xtras'] ?? null));
		if(\strpos((string)$xtras, ']|') === false) {
			\SmartModExtLib\AuthUsers\AuthCookie::usetJwtCookie();
			\SmartModExtLib\AuthUsers\Utils::logFailedExtAuth('JWT:Validation', 403, 'Xtras are Invalid: `'.$xtras.'` for `'.$email.'`');
			return;
		} //end if
		$arrXtras = (array) explode('|', (string)$xtras, 2); // explode only by 1st occurence ; json may contain also |
		if((int)\Smart::array_size($arrXtras) != 2) {
			\SmartModExtLib\AuthUsers\AuthCookie::usetJwtCookie();
			\SmartModExtLib\AuthUsers\Utils::logFailedExtAuth('JWT:Validation', 403, 'Xtras are Invalid, split length by separator: `'.$xtras.'` for `'.$email.'`');
			return;
		} //end if
		//--
		$reqXtras = (string) \trim((string)\SmartModExtLib\AuthUsers\AuthJwt::xtrasMode((string)$mode, (string)$email));
		if(
			((string)$reqXtras == '')
			OR
			(\strpos((string)$xtras, (string)\ucfirst((string)$mode).'[') !== 0)
			OR
			(\strpos((string)$xtras, (string)$reqXtras.'|') !== 0)
			OR
			((string)($arrXtras[0] ?? null) !== (string)$reqXtras)
		) {
			\SmartModExtLib\AuthUsers\AuthCookie::usetJwtCookie();
			\SmartModExtLib\AuthUsers\Utils::logFailedExtAuth('JWT:Validation', 403, 'Xtras are Wrong: `'.$xtras.'` for `'.$email.'`');
			return;
		} //end if
		//--
		$jsonXtras = \Smart::json_decode((string)\trim((string)($arrXtras[1] ?? null)), true, 2); // max 2 sub-levels ; {{{SYNC-JWT-XTRARR-JSON-LEVELS}}}
		if(!\is_array($jsonXtras)) {
			$jsonXtras = [];
		} //end if
		if((int)\Smart::array_size($jsonXtras) <= 0) {
			\SmartModExtLib\AuthUsers\AuthCookie::usetJwtCookie();
			\SmartModExtLib\AuthUsers\Utils::logFailedExtAuth('JWT:Validation', 403, 'Xtras Data is Invalid: `'.$xtras.'` for `'.$email.'`');
			return;
		} //end if
		// \Smart::log_notice(print_r($jsonXtras,1));
		//--
		$clusterID = (string) \trim((string)($jsonXtras['cluster'] ?? null));
		if(\SmartAuth::validate_cluster_id((string)$clusterID) !== true) { // {{{SYNC-AUTH-USERS-SAFE-VALIDATE-CLUSTER}}}
			\SmartModExtLib\AuthUsers\AuthCookie::usetJwtCookie();
			\SmartModExtLib\AuthUsers\Utils::logFailedExtAuth('JWT:Auth', 403, 'User ClusterID is Invalid: `'.$email.'` / `'.$clusterID.'`');
			return;
		} //end if
		//--
		$userID = (string) \trim((string)($jsonXtras['id'] ?? null));
		if( // {{{SYNC-AUTH-USERS-SAFE-VALIDATE-ID}}}
			((string)$userID == '')
			OR
			((int)\strlen((string)$userID) != 21)
			OR
			(\strpos((string)$userID, '.') === false)
			OR
			(\SmartAuth::validate_auth_username((string)$userID, false) !== true)
		) {
			\SmartModExtLib\AuthUsers\AuthCookie::usetJwtCookie();
			\SmartModExtLib\AuthUsers\Utils::logFailedExtAuth('JWT:Auth', 403, 'User UserID is Invalid: `'.$email.'` / `'.$userID.'`');
			return;
		} //end if
		//--
		$provider = (string) \trim((string)($jsonXtras['provider'] ?? null));
		if((string)$provider == '') {
			\SmartModExtLib\AuthUsers\AuthCookie::usetJwtCookie();
			\SmartModExtLib\AuthUsers\Utils::logFailedExtAuth('JWT:Validation', 403, 'Xtras Provider is Empty: `'.$xtras.'` for `'.$email.'`');
			return;
		} else if((string)$provider != '@') { // {{{SYNC-AUTH-USERS-PROVIDER-SELF}}}
			if(!\preg_match((string)\SmartModExtLib\AuthUsers\AuthPlugins::AUTH_USERS_PLUGINS_VALID_ID_REGEX, (string)$provider)) {
				\SmartModExtLib\AuthUsers\AuthCookie::usetJwtCookie();
				\SmartModExtLib\AuthUsers\Utils::logFailedExtAuth('JWT:Validation', 403, 'Xtras Provider is Wrong: `'.$xtras.'` for `'.$email.'`');
				return;
			} //end if
			if(\SmartModExtLib\AuthUsers\AuthPlugins::pluginExists((string)$provider) !== true) {
				\SmartModExtLib\AuthUsers\AuthCookie::usetJwtCookie();
				\SmartModExtLib\AuthUsers\Utils::logFailedExtAuth('JWT:Validation', 403, 'Xtras Provider does Not Exists: `'.$xtras.'` for `'.$email.'`');
				return;
			} //end if
		} //end if
		//--
		$area = (string) \trim((string)($jwtValidArr['area'] ?? null));
		if((string)$area !== (string)\SmartModExtLib\AuthUsers\Utils::AUTH_USERS_AREA) {
			\SmartModExtLib\AuthUsers\AuthCookie::usetJwtCookie();
			\SmartModExtLib\AuthUsers\Utils::logFailedExtAuth('JWT:Validation', 403, 'Area is Invalid: `'.$area.'` for `'.$email.'`');
			return;
		} //end if
		//--
		$iplist = (string) \trim((string)($jwtValidArr['ip-list'] ?? null));
		if(
			((string)$iplist == '')
			OR
			((string)$iplist == '*')
			OR
			(\strpos((string)$iplist, '<') === false)
			OR
			(\strpos((string)$iplist, '>') === false)
		) {
			\SmartModExtLib\AuthUsers\AuthCookie::usetJwtCookie();
			\SmartModExtLib\AuthUsers\Utils::logFailedExtAuth('JWT:Validation', 403, 'IpList is Invalid: `'.$iplist.'` for `'.$email.'`');
			return;
		} //end if
		//--
		$serial = (string) \trim((string)($jwtValidArr['serial'] ?? null));
		if(((string)$serial == '') || ((int)\strlen((string)$serial) != 21)) {
			\SmartModExtLib\AuthUsers\AuthCookie::usetJwtCookie();
			\SmartModExtLib\AuthUsers\Utils::logFailedExtAuth('JWT:Validation', 403, 'Serial is Invalid: `'.$serial.'` for `'.$email.'`');
			return;
		} //end if
		//--
		$signature = (string) \trim((string)($jwtValidArr['sign'] ?? null));
		if((string)$signature == '') {
			\SmartModExtLib\AuthUsers\AuthCookie::usetJwtCookie();
			\SmartModExtLib\AuthUsers\Utils::logFailedExtAuth('JWT:Validation', 403, 'Signature is Empty for `'.$email.'`');
			return;
		} //end if
		//--
		$userData = (array) \SmartModExtLib\AuthUsers\AuthClusterUser::getAccountWorkspace((string)$clusterID, (string)$userID, (string)$email);
		if((int)\Smart::array_size($userData) <= 0) {
			\SmartModExtLib\AuthUsers\AuthCookie::usetJwtCookie();
			\SmartModExtLib\AuthUsers\Utils::logFailedExtAuth('JWT:Auth', 403, 'User Account does not Exists: `'.$email.'`');
			return;
		} //end if
		if((string)($userData['cluster'] ?? null) !== (string)$clusterID) {
			\SmartModExtLib\AuthUsers\AuthCookie::usetJwtCookie();
			\SmartModExtLib\AuthUsers\Utils::logFailedExtAuth('JWT:Auth', 403, 'User ClusterID mismatch: `'.$email.'` / `'.($userData['cluster'] ?? null).'` / `'.$clusterID.'`');
			return;
		} //end if
		if((string)\SmartModExtLib\AuthUsers\Utils::userAccountIdToUserName((string)($userData['id'] ?? null)) !== (string)$userID) {
			\SmartModExtLib\AuthUsers\AuthCookie::usetJwtCookie();
			\SmartModExtLib\AuthUsers\Utils::logFailedExtAuth('JWT:Auth', 403, 'User UserID mismatch: `'.$email.'` / `'.($userData['id'] ?? null).'` / `'.$userID.'`');
			return;
		} //end if
		if((string)($userData['id'] ?? null) !== (string)\SmartModExtLib\AuthUsers\Utils::userNameToUserAccountId((string)$userID)) {
			\SmartModExtLib\AuthUsers\AuthCookie::usetJwtCookie();
			\SmartModExtLib\AuthUsers\Utils::logFailedExtAuth('JWT:Auth', 403, 'User UserName mismatch: `'.$email.'` / `'.($userData['id'] ?? null).'` / `'.$userID.'`');
			return;
		} //end if
		if((string)($userData['email'] ?? null) !== (string)$email) {
			\SmartModExtLib\AuthUsers\AuthCookie::usetJwtCookie();
			\SmartModExtLib\AuthUsers\Utils::logFailedExtAuth('JWT:Auth', 403, 'User Email mismatch: `'.$email.'` / `'.($userData['email'] ?? null).'`');
			return;
		} //end if
		//-- status must be 1 or 2 ; 1 = allow multi-sessions ; 2 = disallow multi-sessions
		if((intval($userData['status'] ?? null) < 1) || (intval($userData['status'] ?? null) > 2)) { // {{{SYNC-ACCOUNT-MULTISESSIONS}}}
			\SmartModExtLib\AuthUsers\AuthCookie::usetJwtCookie();
			\SmartModExtLib\AuthUsers\Utils::logFailedExtAuth('JWT:Auth', 403, 'User Account is Disabled: `'.$email.'` / `'.($userData['status'] ?? null).'`');
			return;
		} else if(intval($userData['status'] ?? null) == 2) { // {{{SYNC-ACCOUNT-MULTISESSIONS-DISABLED}}}
			if((string)($userData['jwtserial'] ?? null) !== (string)$serial) {
				\SmartModExtLib\AuthUsers\AuthCookie::usetJwtCookie();
				\SmartModExtLib\AuthUsers\Utils::logFailedExtAuth('JWT:Auth', 403, 'Token serial is wrong: `'.$serial.'` / `'.($userData['jwtserial'] ?? null).'` for auth `'.$email.'`');
				return;
			} //end if
			if((string)($userData['jwtsignature'] ?? null) !== (string)$signature) {
				\SmartModExtLib\AuthUsers\AuthCookie::usetJwtCookie();
				\SmartModExtLib\AuthUsers\Utils::logFailedExtAuth('JWT:Auth', 403, 'Token signature is wrong: `'.$signature.'` / `'.($userData['jwtsignature'] ?? null).'` for auth `'.$email.'`');
				return;
			} //end if
		} //end if
		//--
		$userEncKey = (string) \SmartModExtLib\AuthUsers\Utils::userEncryptionKey((string)$email);
		if((string)\trim((string)$userEncKey) == '') {
			\SmartModExtLib\AuthUsers\AuthCookie::usetJwtCookie();
			\SmartModExtLib\AuthUsers\Utils::logFailedExtAuth('Auth', 403, 'Invalid User Encryption Key for auth `'.$email.'`');
			return;
		} //end if
		//--
		$passalgo = (int) ($userData['passalgo'] ?? null);
		$passhash = (string) \trim((string)($userData['password'] ?? null));
		if((string)$provider != '@') { // {{{SYNC-AUTH-USERS-PROVIDER-SELF}}}
			$passalgo = (int) \SmartAuth::ALGO_PASS_SMART_SAFE_WEB_TOKEN;
			$passhash = (string) ($jwtValidArr['token'] ?? null);
		} //end if
		//--
		$quota = (int) ($userData['quota'] ?? null);
		if((int)$quota < 0) {
			$quota = -1; // means unlimited
		} //end if
		//--
		$fa2secret = (string) \trim((string)($userData['fa2'] ?? null));
		if((string)$fa2secret != '') {
			$fa2secret = (string) \trim((string)\SmartCipherCrypto::tf_decrypt((string)$fa2secret, (string)$userEncKey, true)); // TF+BF
			if(\SmartModExtLib\AuthUsers\Auth2FA::is2FASecretValid((string)$fa2secret) !== true) {
				$fa2secret = '';
			} //end if
		} //end if
		//--
		$secKey = (string) \trim((string)($userData['seckey'] ?? null));
		if((string)$secKey != '') {
			$secKey = (string) \trim((string)\SmartCipherCrypto::tf_decrypt((string)$secKey, (string)$userEncKey, true)); // TF+BF
		} //end if
		//--
		$signKeys = (string) \trim((string)($userData['signkeys'] ?? null));
		if((string)$signKeys != '') {
			$signKeys = (string) \trim((string)\SmartCipherCrypto::tf_decrypt((string)$signKeys, (string)$userEncKey, true)); // TF+BF
			if((string)$signKeys != '') {
				$signKeys = \Smart::json_decode((string)$signKeys);
			} //end if
		} //end if
		if(!\is_array($signKeys)) {
			$signKeys = [];
		} //end if
		//--
		$arrWorkspaces = [
			'is:local' => (bool) ($userData['#workspace:is:local'] ?? null),
		];
		if(($userData['#workspace:is:local'] ?? null) === true) {
			$arrWorkspaces['db'] = (string) (string) \SmartModExtLib\AuthUsers\AuthClusterUser::getAccountWorkspacePath((string)$userID);
		} //end if
		//--
		\SmartAuth::set_auth_data( // v.20250218
			(string) \SmartModExtLib\AuthUsers\Utils::AUTH_USERS_AREA, // auth realm
			(string) 'COOKIE.JWT:'.$provider, // auth method
			(string) $clusterID, // cluster ID
			(int)    $passalgo, // pass algo
			(string) $passhash, // auth password hash (will be stored as encrypted, in-memory)
			(string) $email, // auth user name
			(string) $userID, // auth ID (on backend must be set exact as the auth username)
			(string) $email, // user email * Optional *
			(string) \trim((string)($userData['name'] ?? null)), // user full name (First Name + ' ' + Last name) * Optional *
			(string) \trim((string)($userData['priv'] ?? null)), // user privileges * Optional *
			(string) \trim((string)($userData['restr'] ?? null)), // user restrictions * Optional *
			(array)  [ // {{{SYNC-AUTH-KEYS}}}
				'fa2sec'  => (string) $fa2secret,
				'seckey'  => (string) $secKey,
				'privkey' => (string) ($signKeys['privkey'] ?? null),
				'pubkey'  => (string) ($signKeys['pubkey'] ?? null),
			], // keys
			(int)    $quota, // user quota in MB * Optional ; -1 unlimited 0..n MB
			[ // user metadata (array) ; may vary
				'registered' 	=> (string) ($userData['registered'] ?? null),
				'status' 		=> (int)    ($userData['status'] ?? null),
				'allowfed' 		=> (string) ($userData['allowfed'] ?? null),
				'data' 			=> (array)  \Smart::json_decode((string)($userData['data'] ?? null), true, 2), // max 2 levels
				'settings' 		=> (array)  \Smart::json_decode((string)($userData['settings'] ?? null), true, 7), // max 7 levels ; {{{SYNC-AUTH-METADATA-MAX-LEVELS}}}
				'iprestr' 		=> (string) ($userData['iprestr'] ?? null),
			],
			(array)  $arrWorkspaces, // workspaces
		);
		//--
		//die('<pre>'.\Smart::escape_html(\SmartUtils::pretty_print_var(\SmartAuth::get_auth_data(true))).'</pre>');
		//--
		//-- {{{SYNC-AUTH-USERS-CLEAR-COOKIES}}}
		\SmartModExtLib\AuthUsers\Utils::unsetRedirUrlCookie(); // clear redir cookie
	//	\SmartModExtLib\AuthUsers\Utils::unsetCsrfCookie(); // clear csrf cookie on signin ; do not unset, is required for setting forms
		\SmartModExtLib\AuthUsers\Utils::clearAuthUsersCaptchaHtml(); // clear captcha cookies on signin
		//--
		return;
		//--
	} //END FUNCTION


	public static function AuthLock() : void {
		//--
		\SmartAuth::lock_auth_data();
		//--
		return;
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
