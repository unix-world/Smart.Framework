<?php
// Controller: AuthUsers/Authorize
// Route: ?page=auth-users.authorize
// (c) 2025-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT S EXECUTION
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'INDEX'); // INDEX


final class SmartAppIndexController extends SmartAbstractAppController {

	// r.20250207
	// this is the auth users public authorize (api) used for: signin, register, recovery

	public function Initialize() {

		//--
		if(!SmartAppInfo::TestIfModuleExists('mod-auth-admins')) {
			$this->PageViewSetErrorStatus(500, 'Mod AuthAdmins is missing !');
			return false;
		} //end if
		//--

		//--
		if(SmartEnvironment::isAdminArea() !== false) {
			Smart::log_warning(__METHOD__.' # ERR: Controller cannot run under Admin area');
			$this->PageViewSetErrorStatus(500, 'ERROR: This Abstract Controller must run inside Index Area');
			return false;
		} //end if
		//--

		//--
		if(SmartUtils::is_ajax_request() !== true) {
			$this->PageViewSetErrorStatus(400, 'Invalid Request');
			return false;
		} //end if
		//--

	} //END FUNCTION


	public function Run() { // (OUTPUTS: HTML/JSON)

		//--
		$action = (string) $this->RequestVarGet('action', '', 'string');
		//--

		//--
		$this->PageViewSetCfg('rawpage', true);
		$this->PageViewSetCfg('rawmime', 'application/json');
		//--

		//--
		$status   = 'INVALID';
		$title    = 'Invalid Request';
		$message  = 'Invalid Action';
		$redirect = '';
		$jsevcode = '';
		//--

		//--
		if(SmartAuth::is_authenticated() === true) {
			//--
			$status   = 'FAILED';
			$title    = 'Request Failed';
			$message  = 'You are already authenticated';
			//--
		} else if(\SmartModExtLib\AuthUsers\Utils::isValidCsrfCookie() !== true) {
			//--
			$status   = 'FAILED';
			$title    = 'Your Session Key has Expired';
			$message  = 'If this error persist check your browser cookie settings and allow cookies.';
			//--
			if((string)\SmartModExtLib\AuthUsers\Utils::setCsrfCookie() != '') {
				$message = 'A new Session Key was issued. Try Again.';
			} //end if
			//--
		} else {
			//--
			switch((string)$action) {
				//-------
				case 'auth':
					//--
					$isCaptchaValid = false;
					$isUserExists = false;
					$isPasswordMatch = false;
					$is2FACodeOK = false;
					$is2FASkipped = false;
					//--
					$frm 			= (array)  $this->RequestVarGet('frm', array(), 'array');
					$frm['user']	= (string) trim((string)($frm['user'] ?? null));
					$frm['pass'] 	= (string) trim((string)($frm['pass'] ?? null));
					$frm['2fa'] 	= (string) trim((string)($frm['2fa'] ?? null));
					//--
					$originalUserName = (string) $frm['user'];
					//--
					$status = 'ERROR';
					$title = 'Authorization Failed';
					$redirect = '';
					//--
					$message = '';
					if((string)trim((string)$frm['user']) == '') {
						$message = 'UserName is Empty';
					} else if((string)trim((string)$frm['pass']) == '') {
						$message = 'Password is Empty';
					} else if((SmartAuth::validate_auth_ext_username((string)$frm['user']) !== true) AND (SmartAuth::validate_auth_username((string)$frm['user']) !== true)) { // can be email or user id
						$message = 'UserName format is Invalid';
					} else if(SmartAuth::validate_auth_password((string)$frm['pass']) !== true) {
						$message = 'Password format is Invalid';
					} //end if else
					//--
					if(\SmartModExtLib\AuthUsers\Utils::isAuthLoginCaptchaEnabled() === true) { // if login captcha was explicit enabled
						if(\SmartModExtLib\AuthUsers\Utils::verifyAuthUsersCaptchaHtml() === true) { // captcha verification
							$isCaptchaValid = true;
						} else {
							$message = 'Solve the Captcha before Sign-In';
						} //end if
					} else {
						$isCaptchaValid = true; // captcha is not enabled, consider it true
					} //end if else
					//--
					if((string)$message == '') {
						$userData = [];
						if((strpos((string)$frm['user'], '@') === false) && ((int)strlen((string)$frm['user']) == 21)) {
							$userData = (array) \SmartModDataModel\AuthUsers\AuthUsersFrontend::getAccountById((string)$frm['user']);
							if((int)Smart::array_size($userData) > 0) {
								if(
									((string)trim((string)($userData['id'] ?? null)) != '') // acount id is not empty
									AND
									((int)strlen((string)($userData['id'] ?? null)) == 21) // account id is 21 chars, valid
									AND
									((string)($userData['id'] ?? null) === (string)\SmartModExtLib\AuthUsers\Utils::userNameToUserAccountId((string)$frm['user'])) // account id match the login user id
									AND
									((string)trim((string)($userData['email'] ?? null)) != '') // account have an email address
									AND
									(strpos((string)($userData['email'] ?? null), '@') !== false) // which is valid
									AND
									(SmartAuth::validate_auth_ext_username((string)($userData['email'] ?? null)) === true) // and the email address is valid as username
								) { // IMPORTANT SECURITY CHECK !
									$frm['user'] = (string) ($userData['email'] ?? null);
								} else {
									$userData = []; // reset, wrong account selected, internal error
									Smart::log_warning('AuthUsers :: getAccount :: UserName mismatch for `'.$frm['user'].'` get wrong account `'.($userData['id'] ?? null).'`');
								} //end if
							} //end if
						} else {
							$userData = (array) \SmartModDataModel\AuthUsers\AuthUsersFrontend::getAccountByEmail((string)$frm['user']);
						} //end if else
						//Smart::log_notice(print_r($userData,1));
						if((int)Smart::array_size($userData) > 0) {
							if(
								((string)trim((string)($userData['id'] ?? null)) == '') // if ID is empty
								OR
								((int)strlen((string)($userData['id'] ?? null)) != 21) // or have an invalid length
								OR
								((string)trim((string)($userData['email'] ?? null)) == '') // if email is empty
								OR
								(strpos((string)($userData['email'] ?? null), '@') === false) // or email not valid, redundant safety check
								OR
								((string)($userData['email'] ?? null) !== (string)$frm['user']) // account email match the login user email
								OR
								((string)trim((string)($userData['password'] ?? null)) == '') // if pass hash is empty
								OR
								( // {{{SYNC-ALLOWED-PASS-ALGOS}}}
									((int)($userData['passalgo'] ?? null) != (int)SmartAuth::ALGO_PASS_SMART_SAFE_SF_PASS)
									AND
									((int)($userData['passalgo'] ?? null) != (int)SmartAuth::ALGO_PASS_SMART_SAFE_BCRYPT)
								)
							) { // IMPORTANT SECURITY CHECK !
								$userData = []; // reset, wrong account selected, internal error
								Smart::log_warning('AuthUsers :: getAccount :: UserName mismatch for `'.$frm['user'].'` get wrong account `'.($userData['email'] ?? null).'`');
							} //end if
						} //end if
						if((int)Smart::array_size($userData) > 0) {
							$isUserExists = true;
							if(\SmartModExtLib\AuthUsers\Utils::verifyPassword((string)$frm['user'], (string)$frm['pass'], (int)($userData['passalgo'] ?? null), (string)($userData['password'] ?? null)) === true) {
								$isPasswordMatch = true;
							} else {
								$message = 'UserName or Password are Invalid';
								if(SmartEnvironment::ifDevMode() === true) {
									$message = 'Password do not match'; // DEBUG only message, do not use in production
								} //end if
							} //end if else
						} else {
							$message = 'UserName or Password are Invalid';
							if(SmartEnvironment::ifDevMode() === true) {
								$message = 'UserName does not exists'; // DEBUG only message, do not use in production
							} //end if
						} //end if else
					} //end if
					//--
					if((string)$message == '') {
						if(\SmartModExtLib\AuthUsers\Utils::isAuth2FAEnabled() !== false) { // 2FA is active
							$title = '2FA Code is Required';
							if((string)trim((string)$frm['2fa']) == '') { // 2FA code is empty
								$status = 'WARN';
								$message = '2FA Code is Empty';
							} else if(\SmartModExtLib\AuthUsers\Utils::validateAuth2FACodeFormat((string)$frm['2fa']) !== true) { // 2FA code have an invalid format
								$status = 'WARN';
								$message = '2FA Code format is Invalid';
							} //end if else
							if(\SmartModExtLib\AuthUsers\Utils::isAuth2FARequired() === false) { // 2FA is active but not mandatory
								if((string)trim((string)$frm['2fa']) == '') { // 2FA code is empty
									if((string)trim((string)($userData['fa2'] ?? null)) != '') { // user has 2FA enabled
										$status = 'INFO';
										$message = 'Enter your 2FA Code';
										$jsevcode = 'display2FAZone()';
									} else { // user has not enabled 2FA, clear the 2FA err msg
										$is2FASkipped = true;
										$message = ''; // clear err message, user does not have 2FA enabled, 2FA is not mandatory
									} //end if
								} //end if
							} //end if
						} else { // 2FA is inactive
							$is2FASkipped = true;
						} //end if else
					} //end if
					//--
					if((string)$message == '') {
						if($is2FASkipped === true) {
							$is2FACodeOK = true; // 2FA verification is skipped because is inactive or user has not 2FA enabled, consider it OK
						} else {
							if((string)$frm['2fa'] != '') {
								if(\SmartModExtLib\AuthUsers\Utils::verify2FACode((string)$frm['user'], (string)$frm['2fa'], (string)($userData['fa2'] ?? null), true) === true) { // is encrypted
									$is2FACodeOK = true; // 2FA verification is OK
								} else {
									$status = 'WARN';
									$message = '2FA Code is Invalid';
								} //end if
							} //end if else
						} //end if
					} //end if
					//--
					if((string)$message == '') {
						if(($isCaptchaValid === true) AND ($isUserExists === true) AND ($isPasswordMatch === true) AND ($is2FACodeOK === true)) {
							//-- all OK, auth success, can issue the JWT Token
							$jwt = (array) \SmartModExtLib\AuthUsers\AuthJwt::newAuthCookieJwtToken((string)$frm['user'], '@', 'cookie'); // {{{SYNC-AUTH-USERS-PROVIDER-SELF}}}
							if($jwt['err'] !== false) {
								$status = 'FAIL';
								$message = 'JWT Authorization Failed: '.(int)$jwt['err'];
							} else if((string)trim((string)$jwt['token']) == '') {
								$status = 'FAIL';
								$message = 'JWT Authorization Failed: 501';
							} if((string)trim((string)$jwt['serial']) == '') {
								$status = 'FAIL';
								$message = 'JWT Authorization Failed: 502';
							} if((string)trim((string)$jwt['sign']) == '') {
								$status = 'FAIL';
								$message = 'JWT Authorization Failed: 503';
							} //end if else
							//--
							if((string)$message == '') {
								$arrLoginInfo = [
									'auth:date-time' 		=> (string) date('Y-m-d H:i:s O'),
									'auth:ip-address' 		=> (string) SmartUtils::get_ip_client(),
									'captcha:enabled' 		=> (bool)   \SmartModExtLib\AuthUsers\Utils::isAuthLoginCaptchaEnabled(),
									'captcha:validated' 	=> (bool)   $isCaptchaValid,
									'username:frm' 			=> (string) $originalUserName,
									'password:frm' 			=> (bool)   strlen((string)$frm['pass']),
									'password:valid:match' 	=> (bool)   $isPasswordMatch,
									'2fa:frm' 				=> (int)    strlen((string)$frm['2fa']),
									'2fa:enabled' 			=> (bool)   \SmartModExtLib\AuthUsers\Utils::isAuth2FAEnabled(),
									'2fa:required' 			=> (bool)   \SmartModExtLib\AuthUsers\Utils::isAuth2FARequired(),
									'2fa:validated' 		=> (bool)   $is2FACodeOK,
									'exists:account' 		=> (bool)   $isUserExists,
									'account:id' 			=> (string) ($userData['id'] ?? null),
									'account:email' 		=> (string) ($userData['email'] ?? null),
									'account:password' 		=> (string) ($userData['password'] ?? null),
									'account:passalgo' 		=> (int)    ($userData['passalgo'] ?? null),
								];
								$success = (int) \SmartModDataModel\AuthUsers\AuthUsersFrontend::setAccountLogin( // this is mandatory to set the JWT serial in DB to allow the login with this JWT
									[
										'email' 		=> (string) $frm['user'],
										'jwtserial' 	=> (string) $jwt['serial'],
										'jwtsignature' 	=> (string) $jwt['sign'],
										'authlog' 		=> (string) Smart::json_encode((array)$arrLoginInfo, true, true, false), // prettyprint, unescaped unicode,html
									]
								);
								if((int)$success != 1) {
									$status = 'FAIL';
									$message = 'Account Authorization Failed: 505';
								} //end if
							} //end if
							//--
							if((string)$message == '') {
								if(\SmartModExtLib\AuthUsers\AuthCookie::setJwtCookie((string)$jwt['token']) !== true) {
									$status = 'NOTICE';
									$message = 'Authorization Cookie Failed: 506';
									$redirect = (string) \SmartModExtLib\AuthUsers\Utils::AUTH_USERS_URL_ACCOUNT;
								} //end if
							} //end if
							//-- #
						} else { // should never reach this point ... except if the above code is broken !
							$status = 'FAIL';
							$message = 'Internal Error, Authentication Failed';
						} //end if else
					} //end if
					//--
					if((string)$message == '') {
						//--
						$status = 'OK';
						$title = 'Authorization Successful';
						$message = 'You are now Signed-In ...';
						//-- {{{SYNC-AUTH-USERS-LOGIN-REDIRECT}}}
						$redirect = (string) trim((string)\SmartModExtLib\AuthUsers\Utils::getRedirUrlCookie());
						if((string)$redirect == '') {
							$redirect = (string) \SmartModExtLib\AuthUsers\Utils::AUTH_USERS_URL_ACCOUNT;
						} //end if
						//-- {{{SYNC-AUTH-USERS-CLEAR-COOKIES}}}
						\SmartModExtLib\AuthUsers\Utils::unsetRedirUrlCookie(); // clear redir cookie
						\SmartModExtLib\AuthUsers\Utils::unsetCsrfCookie(); // clear csrf cookie on signin
						\SmartModExtLib\AuthUsers\Utils::clearAuthUsersCaptchaHtml(); // clear captcha cookies on signin
						//--
					} //end if
					//--
					break;
				//-------
				case 'register':
					//--
					$isCaptchaValid = false;
					//--
					$frm 			= (array)  $this->RequestVarGet('frm', array(), 'array');
					$frm['email']	= (string) trim((string)($frm['email'] ?? null));
					$frm['pass'] 	= (string) trim((string)($frm['pass'] ?? null));
					$frm['rpass'] 	= (string) trim((string)($frm['rpass'] ?? null));
					//--
					$status = 'ERROR';
					$title = 'Registration Failed';
					$redirect = '';
					//--
					$message = '';
					if((string)trim((string)$frm['email']) == '') {
						$message = 'UserName is Empty';
					} else if((string)trim((string)$frm['pass']) == '') {
						$message = 'Password is Empty';
					} else if((string)trim((string)$frm['rpass']) == '') {
						$message = 'Password Re-Type is Empty';
					} else if((string)$frm['pass'] !== (string)$frm['rpass']) {
						$message = 'Password Re-Type does Not Match';
					} else if(SmartAuth::validate_auth_ext_username((string)$frm['email']) !== true) {
						$message = 'UserName format is Invalid';
					} else if(SmartAuth::validate_auth_password((string)$frm['pass']) !== true) {
						$message = 'Password format is Invalid';
					} //end if else
					//--
					if(\SmartModExtLib\AuthUsers\Utils::isAuthRegisterCaptchaEnabled() !== false) { // if register captcha was not explicit disabled
						if(\SmartModExtLib\AuthUsers\Utils::verifyAuthUsersCaptchaHtml() === true) { // captcha verification
							$isCaptchaValid = true;
						} else {
							$message = 'Solve the Captcha before Sign-Up';
						} //end if
					} else {
						$isCaptchaValid = true; // captcha is not enabled, consider it true
					} //end if else
					//--
					if((string)$message == '') {
						//--
						$status = 'OK';
						$title = 'Registration Successful';
						$message = 'You are now Signed-Up ...';
						$redirect = (string) \SmartModExtLib\AuthUsers\Utils::AUTH_USERS_URL_SIGNIN;
	$redirect = ''; // testing ...
						//-- {{{SYNC-AUTH-USERS-CLEAR-COOKIES}}}
						// do not use or clear redir cookie here, only the sign-in operations will do
						\SmartModExtLib\AuthUsers\Utils::unsetCsrfCookie(); // clear csrf cookie on register
						\SmartModExtLib\AuthUsers\Utils::clearAuthUsersCaptchaHtml(); // clear captcha cookies on register
						//--
					} //end if
					//--
					break;
				//------- DEFAULT
				default:
					//--
					// other invalid actions
					//--
			} // end switch
			//--
		} //end if else
		//--

		//--
		$this->PageViewSetVar(
			'main',
			(string) SmartViewHtmlHelpers::js_ajax_replyto_html_form(
				(string) $status,
				(string) $title,
				(string) $message,
				(string) $redirect,
				'',
				'',
				(string) $jsevcode,
			)
		);
		//--
		return 200;
		//--

	} //END FUNCTION


} //END CLASS


//end of php code
