<?php
// Controller: AuthUsers/AuthorizeApi
// Route: ?page=auth-users.authorize-api.json
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

	// r.20260107

	// it runs just on auth master server
	// this is the auth users public authorize (api) used for: signin, register, recovery

	private ?object $translator = null;

	public function Initialize() {

		//--
		if(!defined('SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS') OR (SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS !== true)) {
			$this->PageViewSetErrorStatus(503, 'Mod Auth Users is NOT Enabled ...');
			return false;
		} //end if
		//--

		//--
		if(!SmartAppInfo::TestIfModuleExists('mod-auth-admins')) {
			$this->PageViewSetErrorStatus(500, 'Mod AuthAdmins is missing !');
			return false;
		} //end if
		//--

		//--
		if(SmartEnvironment::isAdminArea() !== false) {
			Smart::log_warning(__METHOD__.' # ERR: Controller cannot run under Admin area');
			$this->PageViewSetErrorStatus(502, 'ERROR: This Controller must run inside Index Area');
			return false;
		} //end if
		//--

		//--
		if(SmartAuth::is_cluster_master_auth() !== true) {
			$this->PageViewSetErrorStatus(502, 'Not an Auth Cluster Master Server');
			return false;
		} //end if
		//--

		//--
		if(SmartUtils::is_ajax_request() !== true) {
			$this->PageViewSetErrorStatus(400, 'Invalid Api Request');
			return false;
		} //end if
		//--

		//--
		if($this->translator === null) {
			$this->translator = SmartTextTranslations::getTranslator('mod-auth-users', 'auth-users');
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
		$title    = (string) $this->translator->text('api-invalid-request');
		$message  = (string) $this->translator->text('api-invalid-action');
		$redirect = '';
		$jsevcode = '';
		//--

		//--
		if(SmartAuth::is_authenticated() === true) {
			//--
			$status   = 'FAILED';
			$title    = (string) $this->translator->text('api-failed-request');
			$message  = (string) $this->translator->text('api-already-auth');
			//--
		} else if(\SmartModExtLib\AuthUsers\Utils::isValidCsrfCookie() !== true) {
			//--
			$status   = 'FAILED';
			$title    = (string) $this->translator->text('api-session-expired');
			$message  = (string) $this->translator->text('api-session-error-persist');
			//--
			if((string)\SmartModExtLib\AuthUsers\Utils::setCsrfCookie() != '') {
				$message = (string) $this->translator->text('api-session-new');
			} //end if
			//--
		} else {
			//--
			switch((string)$action) {
				//-------
				case 'auth':
					//--
					$userData = [];
					$isCaptchaValid = false;
					$isUserExists = false;
					$isPasswordMatch = false;
					$isPassCodeMatch = false;
					$is2FACodeOK = false;
					$is2FASkipped = false;
					//--
					$frm 			= (array)  $this->RequestVarGet('frm', array(), 'array');
					$frm['user']	= (string) strtolower((string)trim((string)($frm['user'] ?? null))); // {{{SYNC-AUTH-USERS-INPUT-USERNAME-EMAIL-TRIM-LOWERCASE}}}
					$frm['pass'] 	= (string) trim((string)($frm['pass'] ?? null));
					$frm['2fa'] 	= (string) trim((string)($frm['2fa'] ?? null));
					//--
					$originalUserName = (string) $frm['user'];
					//--
					$status = 'ERROR';
					$title = (string) $this->translator->text('api-auth-failed');
					$redirect = '';
					//--
					$message = '';
					if((string)trim((string)$frm['user']) == '') {
						$message = (string) $this->translator->text('api-auth-user-empty');
					} else if((string)trim((string)$frm['pass']) == '') {
						$message = (string) $this->translator->text('api-auth-pass-empty');
					} else if((SmartAuth::validate_auth_ext_username((string)$frm['user']) !== true) AND (SmartAuth::validate_auth_username((string)$frm['user']) !== true)) { // can be email or user id
						$message = (string) $this->translator->text('api-auth-user-invalid');
					} else if(SmartAuth::validate_auth_password((string)$frm['pass']) !== true) {
						$message = (string) $this->translator->text('api-auth-pass-invalid');
					} //end if else
					//--
					if(\SmartModExtLib\AuthUsers\Utils::isAuthLoginCaptchaEnabled() === true) { // if login captcha was explicit enabled
						if(\SmartModExtLib\AuthUsers\Utils::verifyAuthUsersCaptchaHtml() === true) { // captcha verification
							$isCaptchaValid = true;
						} else {
							$message = (string) $this->translator->text('api-auth-captcha-solve');
						} //end if
					} else {
						$isCaptchaValid = true; // captcha is not enabled, consider it true
					} //end if else
					//--
					if((string)$message == '') {
						//--
						if((strpos((string)$frm['user'], '@') === false) && ((int)strlen((string)$frm['user']) == 21)) {
							$userData = (array) \SmartModDataModel\AuthUsers\AuthUsersFrontend::getAccountById((string)$frm['user']);
							if((int)Smart::array_size($userData) > 0) { // {{{SYNC-VALIDATE-AUTH-BY-USERNAME}}}
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
							) { // IMPORTANT SECURITY CHECK !
								$userData = []; // reset, wrong account selected, internal error
								Smart::log_warning('AuthUsers :: getAccount :: UserName mismatch for `'.$frm['user'].'` get wrong account `'.($userData['email'] ?? null).'`');
							} //end if
						} //end if
						//--
						if((int)Smart::array_size($userData) > 0) {
							$isUserExists = true;
							if(
								((string)trim((string)($userData['password'] ?? null)) != '') // if pass hash is not empty
								AND
								( // {{{SYNC-ALLOWED-PASS-ALGOS}}}
									((int)($userData['passalgo'] ?? null) == (int)SmartAuth::ALGO_PASS_SMART_SAFE_SF_PASS)
									OR
									((int)($userData['passalgo'] ?? null) == (int)SmartAuth::ALGO_PASS_SMART_SAFE_BCRYPT)
								)
								AND
								(\SmartModExtLib\AuthUsers\Utils::verifyPassword((string)$frm['user'], (string)$frm['pass'], (int)($userData['passalgo'] ?? null), (string)($userData['password'] ?? null)) === true)
							) { // login with user/pass
								$isPasswordMatch = true;
							} elseif(
								((int)intval($userData['passresetcnt'] ?? null) > 0)
								AND
								((string)trim((string)($userData['passresetotc'] ?? null)) != '') // if passcode hash is not empty
								AND
								(\SmartModExtLib\AuthUsers\Utils::isValidOneTimePassCodePlain((string)$frm['pass']) === true)
								AND
								(\SmartModExtLib\AuthUsers\Utils::verifyOneTimePassCode((string)$frm['pass'], (string)($userData['passresetotc'] ?? null)) === true)
							) { // login with user/passcode (recovery)
								$isPassCodeMatch = true;
								$isPasswordMatch = true;
							} else {
								$message = (string) $this->translator->text('api-auth-user-or-pass-wrong'); // fake message, for production
								if(SmartEnvironment::ifDevMode() === true) {
									$message = (string) $this->translator->text('api-auth-pass-wrong'); // DEBUG only message, do not use in production
								} //end if
							} //end if else
						} else {
							$message = (string) $this->translator->text('api-auth-user-or-pass-wrong'); // fake message, for production
							if(SmartEnvironment::ifDevMode() === true) {
								$message = (string) $this->translator->text('api-auth-user-wrong'); // DEBUG only message, do not use in production
							} //end if
						} //end if else
						//--
					} //end if
					//--
					if((string)$message == '') {
						if((int)$userData['status'] <= 0) { // {{{SYNC-ACCOUNT-STATUS-DISABLED}}}
							$status = 'NOTICE';
							$message = (string) $this->translator->text('api-auth-account-disabled');
						} //end if
					} //end if
					//--
					if((string)$message == '') {
						if(\SmartModExtLib\AuthUsers\Utils::isAuth2FAEnabled() !== false) { // 2FA is active
							if((string)trim((string)$frm['2fa']) == '') { // 2FA code is empty
								$status = 'WARN';
								$title = (string) $this->translator->text('api-auth-2fa-required');
								$message = (string) $this->translator->text('api-auth-2fa-empty');
							} else if(\SmartModExtLib\AuthUsers\Utils::validateAuth2FACodeFormat((string)$frm['2fa']) !== true) { // 2FA code have an invalid format
								$status = 'WARN';
								$title = (string) $this->translator->text('api-auth-2fa-required');
								$message = (string) $this->translator->text('api-auth-2fa-invalid');
							} //end if else
							if(\SmartModExtLib\AuthUsers\Utils::isAuth2FARequired() === false) { // 2FA is active but not mandatory
								if((string)trim((string)$frm['2fa']) == '') { // 2FA code is empty
									if((string)trim((string)($userData['fa2'] ?? null)) != '') { // user has 2FA enabled
										$status = 'INFO';
										$title = (string) $this->translator->text('api-auth-2fa-required');
										$message = (string) $this->translator->text('api-auth-2fa-hint');
										$jsevcode = 'display2FAZone()'; // {{{SYNC-JS-ACTION-2FA-DISPLAY}}}
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
								if(\SmartModExtLib\AuthUsers\Utils::verify2FACode((string)\SmartModExtLib\AuthUsers\Utils::userAccountIdToUserName((string)($userData['id'] ?? null)), (string)$frm['2fa'], (string)($userData['fa2'] ?? null), true) === true) { // ok ; is encrypted
									$is2FACodeOK = true; // 2FA verification is OK
								} else {
									$status = 'WARN';
									$message = (string) $this->translator->text('api-auth-2fa-wrong');
								} //end if
							} //end if else
						} //end if
					} //end if
					//--
					if((string)$message == '') {
						if(($isCaptchaValid === true) AND ($isUserExists === true) AND ($isPasswordMatch === true) AND ($is2FACodeOK === true)) {
							//-- all OK, auth success, can issue the JWT Token
							$provider = '@'; // {{{SYNC-AUTH-USERS-PROVIDER-SELF}}}
							$jwt = (array) \SmartModExtLib\AuthUsers\AuthJwt::newAuthJwtToken('cookie', (string)$provider, (string)($userData['cluster'] ?? null), (string)($userData['id'] ?? null), (string)($userData['email'] ?? null)); // {{{SYNC-AUTH-USERS-JWT-ASSIGN}}}
							if($jwt['err'] !== false) {
								$status = 'FAIL';
								$message = (string) $this->translator->text('api-auth-jwt-failed').': '.(int)$jwt['err'];
							} else if((string)trim((string)$jwt['token']) == '') {
								$status = 'FAIL';
								$message = (string) $this->translator->text('api-auth-jwt-failed').': 501';
							} if((string)trim((string)$jwt['serial']) == '') {
								$status = 'FAIL';
								$message = (string) $this->translator->text('api-auth-jwt-failed').': 502';
							} if((string)trim((string)$jwt['sign']) == '') {
								$status = 'FAIL';
								$message = (string) $this->translator->text('api-auth-jwt-failed').': 503';
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
									'passcode:valid:match' 	=> (bool)   $isPassCodeMatch,
									'2fa:frm' 				=> (int)    strlen((string)$frm['2fa']),
									'2fa:enabled' 			=> (bool)   \SmartModExtLib\AuthUsers\Utils::isAuth2FAEnabled(),
									'2fa:required' 			=> (bool)   \SmartModExtLib\AuthUsers\Utils::isAuth2FARequired(),
									'2fa:validated' 		=> (bool)   $is2FACodeOK,
									'exists:account' 		=> (bool)   $isUserExists,
									'account:cluster' 		=> (string) ($userData['cluster'] ?? null),
									'account:id' 			=> (string) ($userData['id'] ?? null),
									'account:email' 		=> (string) ($userData['email'] ?? null),
									'account:password' 		=> (bool)   strlen($userData['password'] ?? null),
									'account:passalgo' 		=> (int)    ($userData['passalgo'] ?? null),
								];
								$success = \SmartModExtLib\AuthUsers\AuthClusterUser::setAccountWorkspace( // {{{SYNC-CREATE-ACCOUNT-LOGIN-WORKSPACE}}}
									(string) ($userData['cluster'] ?? null),
									(string) ($userData['email'] ?? null),
									(string) $jwt['serial'],
									(string) $jwt['sign'],
									(array)  $arrLoginInfo
								);
								if($success !== true) {
									$status = 'FAIL';
									$message = (string) $this->translator->text('api-auth-acc-failed').': 505';
								} //end if
							} //end if
							//--
							if((string)$message == '') {
								if(\SmartModExtLib\AuthUsers\AuthCookie::setJwtCookie((string)$jwt['token']) !== true) {
									$status = 'NOTICE';
									$message = (string) $this->translator->text('api-auth-cookie-failed').': 506';
									$redirect = (string) \SmartModExtLib\AuthUsers\Utils::AUTH_USERS_URL_ACCOUNT;
								} //end if
							} //end if
							//-- #
						} else { // should never reach this point ... except if the above code is broken !
							$status = 'FAIL';
							$message = (string) $this->translator->text('api-auth-internal-err');
						} //end if else
					} //end if
					//--
					if((string)$message == '') {
						//--
						$status = 'OK';
						$title = (string) $this->translator->text('api-auth-success');
						$message = (string) $this->translator->text('api-auth-signed-in');
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
					$regToken = [];
					//--
					$frm 			= (array)  $this->RequestVarGet('frm', array(), 'array');
					$frm['email']	= (string) strtolower((string)trim((string)($frm['email'] ?? null))); // {{{SYNC-AUTH-USERS-INPUT-USERNAME-EMAIL-TRIM-LOWERCASE}}}
					$frm['pass'] 	= (string) trim((string)($frm['pass'] ?? null));
					$frm['rpass'] 	= (string) trim((string)($frm['rpass'] ?? null));
					//--
					$status = 'ERROR';
					$title = (string) $this->translator->text('api-register-failed');
					$redirect = '';
					//--
					$message = '';
					if((string)trim((string)$frm['email']) == '') {
						$message = (string) $this->translator->text('api-auth-user-empty');
					} else if((string)trim((string)$frm['pass']) == '') {
						$message = (string) $this->translator->text('api-auth-pass-empty');
					} else if((string)trim((string)$frm['rpass']) == '') {
						$message = (string) $this->translator->text('api-auth-repass-empty');
					} else if((string)$frm['pass'] !== (string)$frm['rpass']) {
						$message = (string) $this->translator->text('api-auth-repass-invalid');
					} else if(SmartAuth::validate_auth_ext_username((string)$frm['email']) !== true) {
						$message = (string) $this->translator->text('api-auth-user-invalid');
					} else if(SmartAuth::validate_auth_password((string)$frm['pass']) !== true) {
						$message = (string) $this->translator->text('api-auth-pass-invalid');
					} //end if else
					//--
					if(\SmartModExtLib\AuthUsers\Utils::isAuthRegisterCaptchaEnabled() !== false) { // if register captcha was not explicit disabled
						if(\SmartModExtLib\AuthUsers\Utils::verifyAuthUsersCaptchaHtml() === true) { // captcha verification
							$isCaptchaValid = true;
						} else {
							$message = (string) $this->translator->text('api-auth-captcha-solve');
						} //end if
					} else {
						$isCaptchaValid = true; // captcha is not enabled, consider it true
					} //end if else
					//--
					if((string)$message == '') {
						if(\SmartModExtLib\AuthUsers\AuthRegister::isRegistrationAllowedFromClientIp() !== true) {
							$message = (string) $this->translator->text('api-register-ip-failed');
						} //end if
					} //end if
					//--
					if((string)$message == '') {
						if(\SmartModDataModel\AuthUsers\AuthUsersFrontend::canRegisterAccount((string)$frm['email']) !== true) {
							$message = (string) $this->translator->text('api-register-acc-fail');
						} //end if
					} //end if
					//--
					if((string)$message == '') {
						//--
						$regToken = (array) \SmartModExtLib\AuthUsers\AuthRegister::tokenCreate((string)$frm['email'], (string)$frm['pass']);
						//print_r($regToken);
						if($regToken['err'] !== 0) {
							$message = (string) $this->translator->text('api-register-failed').': '.(int)$regToken['err'];
						} //end if
						//--
						if((string)$message == '') {
							$vfyRegToken = (array) \SmartModExtLib\AuthUsers\AuthRegister::tokenValidate((string)$regToken['token'], (string)$regToken['hash']);
							//print_r($vfyRegToken);
							if($vfyRegToken['err'] !== 0) {
								$message = (string) $this->translator->text('api-register-integrity-fail').': '.(int)$vfyRegToken['err'];
							} //end if
						} //end if
						//--
					} //end if
					//--
					if((string)$message == '') {
						if((string)trim((string)($regToken['hash'] ?? null)) == '') {
							$message = (string) $this->translator->text('api-register-internal-err').': (C)'; // Code is N/A
						} else if((string)trim((string)($regToken['token'] ?? null)) == '') {
							$message = (string) $this->translator->text('api-register-internal-err').': (T)'; // Token is N/A
						} //end if else
					} //end if
					//--
					//if((string)$message == '') { $message = 'OK:test'; }
					//--
					if((string)$message == '') {
						//--
						if((defined('SMART_AUTHUSERS_REGISTER_NOEMAIL')) AND (SMART_AUTHUSERS_REGISTER_NOEMAIL === true)) {
							$redirect = (string) \SmartModExtLib\AuthUsers\AuthRegister::buildUrlActivate((string)($regToken['token'] ?? null), (string)($regToken['hash'] ?? null));
						} else {
							$urlContainsHash = false;
							if((defined('SMART_AUTHUSERS_REGISTER_URLWITHCODE')) AND (SMART_AUTHUSERS_REGISTER_URLWITHCODE === true)) {
								$urlContainsHash = true;
							} //end if
							$success = (int) \SmartModExtLib\AuthUsers\AuthEmail::mailSendRegistration((string)$frm['email'], (string)($regToken['token'] ?? null), (string)($regToken['hash'] ?? null), (bool)$urlContainsHash);
							if((int)$success == 1) {
								$redirect = (string) \SmartModExtLib\AuthUsers\AuthRegister::buildUrlActivate((string)($regToken['token'] ?? null)); // don't send hash in URL in this case, will be sent by email
							} else {
								$message = (string) $this->translator->text('api-register-email-failed').': '.(int)$success;
							} //end if else
						} //end if else
						//--
					} //end if
					//--
					if((string)$message == '') {
						//--
						$status = 'OK';
						$title = (string) $this->translator->text('api-register-success');
						$message = (string) $this->translator->text('api-register-activation-hint');
						//-- {{{SYNC-AUTH-USERS-CLEAR-COOKIES}}}
						// do not use or clear redir cookie here, only the sign-in operations will do
						\SmartModExtLib\AuthUsers\Utils::unsetCsrfCookie(); // clear csrf cookie on register
						\SmartModExtLib\AuthUsers\Utils::clearAuthUsersCaptchaHtml(); // clear captcha cookies on register
						//--
					} //end if
					//--
					break;
				//-------
				case 'register.activate':
					//--
					$frm 			= (array)  $this->RequestVarGet('frm', array(), 'array');
					$frm['token'] 	= (string) trim((string)($frm['token'] ?? null));
					$frm['hash'] 	= (string) trim((string)($frm['hash'] ?? null));
					//--
					$status = 'ERROR';
					$title = (string) $this->translator->text('api-activate-acc-failed');
					$redirect = '';
					//--
					$message = '';
					if((string)trim((string)$frm['token']) == '') {
						$message = (string) $this->translator->text('api-activate-acc-empty-token');
					} else if((string)trim((string)$frm['hash']) == '') {
						$message = (string) $this->translator->text('api-activate-acc-empty-code');
					} //end if else
					//--
					if((string)$message == '') {
						if(\SmartModExtLib\AuthUsers\AuthRegister::isRegistrationAllowedFromClientIp() !== true) {
							$message = (string) $this->translator->text('api-register-ip-failed');
						} //end if
					} //end if
					//--
					if((string)$message == '') {
						//--
						$vfyRegToken = (array) \SmartModExtLib\AuthUsers\AuthRegister::tokenValidate((string)$frm['token'], (string)$frm['hash']);
						//print_r($vfyRegToken);
						if($vfyRegToken['err'] !== 0) {
							if((int)$vfyRegToken['err'] == 178) { // {{{SYNC-ACTIVATION-CODE-HASH-CHECK-CODE}}}
								$message = (string) $this->translator->text('api-activate-acc-invalid-code');
							} else {
								$message = (string) $this->translator->text('api-activate-acc-failed').': '.(int)$vfyRegToken['err'];
							} //end if else
						} //end if
						//--
						if((string)$message == '') {
							if(\SmartModDataModel\AuthUsers\AuthUsersFrontend::canRegisterAccount((string)trim((string)$vfyRegToken['token']['e'])) !== true) {
								$message = (string) $this->translator->text('api-activate-acc-error');
							} //end if
						} //end if
						//--
						if((string)$message == '') {
							if((int)Smart::array_size($vfyRegToken['token']) <= 0) {
								$message = (string) $this->translator->text('api-activate-acc-empty-data');
							} //end if
							if((string)$message == '') {
								$success = (int) \SmartModDataModel\AuthUsers\AuthUsersFrontend::createAccount([
									'email' 	=> (string) strtolower((string)trim((string)$vfyRegToken['token']['e'])),
									'password' 	=> (string) trim((string)$vfyRegToken['token']['p']),
								]);
								if((int)$success !== 1) {
									if((int)$success === 0) {
										$message = (string) $this->translator->text('api-activate-acc-exists');
									} else {
										$message = (string) $this->translator->text('api-activate-acc-failed').': '.(int)$success;
									} //end if else
								} //end if
							} //end if
						} //end if
						//--
					} //end if
					//--
					if((string)$message == '') {
						//--
						$status = 'OK';
						$title = (string) $this->translator->text('api-activate-acc-success');
						$message = (string) $this->translator->text('api-activate-acc-ok-hint');
						$redirect = \SmartModExtLib\AuthUsers\Utils::AUTH_USERS_URL_SIGNIN;
						//$redirect = '';
						//-- {{{SYNC-AUTH-USERS-CLEAR-COOKIES}}}
						// do not use or clear redir cookie here, only the sign-in operations will do
						\SmartModExtLib\AuthUsers\Utils::unsetCsrfCookie(); // clear csrf cookie on register
						// no need to clear captcha cookies on register activation
						//--
					} //end if
					//--
					break;
				//------- RECOVERY
				case 'recovery':
					//--
					$userData = [];
					$isCaptchaValid = false;
					$isUserExists = false;
					$oneTimePass = null;
					//--
					$frm 			= (array)  $this->RequestVarGet('frm', array(), 'array');
					$frm['user'] 	= (string) trim((string)($frm['user'] ?? null));
					//--
					$status = 'ERROR';
					$title = (string) $this->translator->text('api-pass-recovery-failed');
					$redirect = '';
					//--
					$message = '';
					if((string)trim((string)$frm['user']) == '') {
						$message = (string) $this->translator->text('api-pass-recovery-empty-user-email');
					} //end if else
					//--
					if(\SmartModExtLib\AuthUsers\Utils::isAuthRecoveryCaptchaEnabled() !== false) { // if recovery captcha was not explicit disabled
						if(\SmartModExtLib\AuthUsers\Utils::verifyAuthUsersCaptchaHtml() === true) { // captcha verification
							$isCaptchaValid = true;
						} else {
							$message = (string) $this->translator->text('api-auth-captcha-solve');
						} //end if
					} else {
						$isCaptchaValid = true; // captcha is not enabled, consider it true
					} //end if else
					//--
					if((string)$message == '') {
						//--
						if((strpos((string)$frm['user'], '@') === false) && ((int)strlen((string)$frm['user']) == 21)) {
							$userData = (array) \SmartModDataModel\AuthUsers\AuthUsersFrontend::getAccountById((string)$frm['user']);
							if((int)Smart::array_size($userData) > 0) { // {{{SYNC-VALIDATE-AUTH-BY-USERNAME}}}
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
									Smart::log_warning('AuthUsers/Recovery :: getAccount :: UserName mismatch for `'.$frm['user'].'` get wrong account `'.($userData['id'] ?? null).'`');
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
							) { // IMPORTANT SECURITY CHECK !
								$userData = []; // reset, wrong account selected, internal error
								Smart::log_warning('AuthUsers/Recovery :: getAccount :: UserName mismatch for `'.$frm['user'].'` get wrong account `'.($userData['email'] ?? null).'`');
							} //end if
						} //end if
						//--
						if((int)Smart::array_size($userData) > 0) {
							//--
							$isUserExists = true;
							//--
							$numResets = (int) intval((string)trim((string)($userData['passresetcnt'] ?? null)));
							if((int)$numResets < 0) {
								$numResets = 0;
							} //end if
							//--
							$lastDtReset = (string) trim((string)($userData['passresetldt'] ?? null));
							$lastReset = 0;
							if((string)$lastReset != '') {
								$lastReset = (int) strtotime((string)$lastDtReset);
							} //end if
							$diffReset = 0;
							if((int)$lastReset > 0) {
								$diffReset = (int) ((int)time() - (int)$lastReset);
							} //end if
							if((int)$diffReset < 0) {
								$diffReset = 0;
							} //end if
							//--
							$isAcceptedReset = true;
							if($numResets >= 3) { // 3 or more resets
								if($numResets <= 5) { // 3..5 resets ; timing: 15 minutes
									if((int)$diffReset < 60 * 15) {
										$isAcceptedReset = false;
									} //end if
								} else if($numResets <= 8) { // 5..8 resets ; timing: 1 hour
									if((int)$diffReset < 60 * 60) {
										$isAcceptedReset = false;
									} //end if
								} else if($numResets <= 12) { // 8..12 resets ; timing: 4 hours
									if((int)$diffReset < 60 * 60 * 4) {
										$isAcceptedReset = false;
									} //end if
								} else { // more than 12 resets ; timing 12 hours
									if((int)$diffReset < 60 * 60 * 12) {
										$isAcceptedReset = false;
									} //end if
								} //end if else
							} //end if
							//--
							if($isAcceptedReset === true) {
								$oneTimePass = (string) \SmartModExtLib\AuthUsers\Utils::createOneTimePassCodePlain();
							} //end if
							$recoveryResult = (int) \SmartModDataModel\AuthUsers\AuthUsersFrontend::setAccountRecoveryData(
								(string)($userData['id'] ?? null),
								$oneTimePass // do not cast to string, can be null
							);
							if($recoveryResult !== 1) {
								$message = (string) $this->translator->text('api-pass-recovery-failed').' ('.(int)$recoveryResult.')';
							} //end if
							//--
							//Smart::log_notice('#DEBUG# :: AuthUsers/Recovery :: isAcceptedReset='.(int)$isAcceptedReset.' ; numResets='.(int)$numResets.' ; lastDtReset='.$lastDtReset.' ; lastReset='.(int)$lastReset.' ; diffReset='.(int)$diffReset.' ; oneTimePass=`'.$oneTimePass.'`');
							//--
						} else {
							$message = (string) $this->translator->text('api-pass-recovery-user-wrong'); // fake message, for production
							if(SmartEnvironment::ifDevMode() === true) {
								$message = (string) $this->translator->text('api-auth-user-wrong'); // DEBUG only message, do not use in production
							} //end if
						} //end if else
						//--
					} //end if
					//--
					if((string)$message == '') {
						if(($isCaptchaValid === true) AND ($isUserExists === true)) {
							if((string)$oneTimePass != '') { // send email
								$success = (int) \SmartModExtLib\AuthUsers\AuthEmail::mailSendRecovery(($userData['email'] ?? null), (string)$oneTimePass);
								if((int)$success != 1) {
									$message = (string) $this->translator->text('api-pass-recovery-email-failed').' ('.(int)$success.')';
								} //end if
							} else {
								$message = (string) $this->translator->text('api-pass-recovery-disallowed');
							} //end if else
						} else { // should never reach this point ... except if the above code is broken !
							$status = 'FAIL';
							$message = (string) $this->translator->text('api-pass-recovery-error');
						} //end if else
					} //end if
					//--
					if((string)$message == '') {
						//--
						$status = 'OK';
						$title = (string) $this->translator->text('recovery-title');
						$message = (string) $this->translator->text('api-pass-recovery-success');
						$redirect = \SmartModExtLib\AuthUsers\Utils::AUTH_USERS_URL_SIGNIN;
						//$redirect = '';
						//-- {{{SYNC-AUTH-USERS-CLEAR-COOKIES}}}
						// do not use or clear redir cookie here, only the sign-in operations will do
						\SmartModExtLib\AuthUsers\Utils::unsetCsrfCookie(); // clear csrf cookie on register
						//--
					} //end if
					//--
					if(((string)$frm['user'] != '') AND ($isCaptchaValid === true)) {
						\SmartModExtLib\AuthUsers\Utils::clearAuthUsersCaptchaHtml(); // clear captcha cookies on every try
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
				(string) Smart::nl_2_br((string)Smart::escape_html((string)$message)),
				(string) $redirect,
				'',
				'',
				(string) $jsevcode
			)
		);
		//--
		return 200;
		//--

	} //END FUNCTION


} //END CLASS


//end of php code
