<?php
// Controller: AuthUsers/SignUp
// Route: ?page=auth-users.signup
// (c) 2025-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'INDEX'); // INDEX


final class SmartAppIndexController extends \SmartModExtLib\AuthUsers\AbstractSignController {

	// r.20260108

	// SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS 	is verified by Initialize() in AbstractSignController
	// Custom request URI Restriction 			is verified by Initialize() in AbstractSignController

	public function Run() {

		// this controller can operate ONLY on master server

		//--
		if(SmartAuth::is_cluster_master_auth() !== true) {
			$this->PageViewSetErrorStatus(502, 'Not an Auth Cluster Master Server');
			return;
		} //end if
		//--

		//--
		$token = (string) trim((string)$this->RequestVarGet('token', '', 'string'));
		$hash  = (string) trim((string)$this->RequestVarGet('hash',  '', 'string')); // this is optional
		//--
		if((string)$token != '') {
			//--
			$vfyRegToken = (array) \SmartModExtLib\AuthUsers\AuthRegister::tokenValidate((string)$token); // validate without hash, just for display, hash is optional here
			if($vfyRegToken['err'] !== 0) {
				$errCodeReg = '#['.$vfyRegToken['err'].']';
				if($vfyRegToken['err'] === 120) {
					$errCodeReg = 'Token is either expired or has been already used #[120]';
				} //end if
				$vfyRegToken = []; // reset
				$this->PageViewSetErrorStatus(403, 'User Account Registration Error, Invalid Token: '.$errCodeReg);
				return;
			} //end if
			//--
			if((int)Smart::array_size($vfyRegToken) > 0) {
				return (int) $this->displayRegistration((string)$token, (array)$vfyRegToken, (string)$hash);
			} //end if
			//--
		} //end if
		//--

		//--
		return (int) $this->displaySignUp();
		//--

	} //END FUNCTION


	private function displayRegistration(string $token, array $vfyRegToken, string $hash) : int {

		//--
		$token = (string) trim((string)$token);
		if((string)$token == '') {
			Smart::log_warning(__METHOD__.' # User Account Registration: Token is Empty');
			return 500;
		} //end if
		//--
		if((int)Smart::array_size($vfyRegToken) <= 0) {
			Smart::log_warning(__METHOD__.' # User Account Registration: Data is Empty');
			return 500;
		} //end if
		if($vfyRegToken['err'] !== 0) {
			Smart::log_warning(__METHOD__.' # User Account Registration, Token is Invalid: '.$vfyRegToken['err']);
			return 500;
		} //end if
		//--
		if((int)Smart::array_size($vfyRegToken['token']) <= 0) {
			Smart::log_warning(__METHOD__.' # User Account Registration: Data Token is Empty');
			return 500;
		} //end if
		//--
		$email = (string) trim((string)$vfyRegToken['token']['e']);
		//--

		//--
		$this->PageViewSetVars([
			'title' 	=> (string) $this->translator->text('sign-up-reg'),
			'main' 		=> (string) SmartMarkersTemplating::render_file_template(
				(string) $this->ControllerGetParam('module-view-path').'register.mtpl.htm',
				[
					//--
					'URL-PREFIX-MASTER' 		=> (string) \SmartModExtLib\AuthUsers\AuthClusterUser::getAuthClusterUrlPrefixMaster(),
					'URL-PREFIX-LOCAL' 			=> (string) \SmartModExtLib\AuthUsers\AuthClusterUser::getAuthClusterUrlPrefixLocal(),
					//--
					'URL-HOME' 					=> (string) SmartUtils::get_server_current_url(),
					'WEBSITE-NAME' 				=> (string) Smart::get_from_config('app.info-url', 'string'),
					'IS-AUTHENTICATED' 			=> (int)    (SmartAuth::is_authenticated() === true) ? 1 : 0,
					'AUTH-USERNAME' 			=> (string) SmartAuth::get_auth_username() ?: $email,
					//--
					'TXT-REGISTER-IP-RESTRICT' 	=> (string) $this->translator->text('sign-up-ip-restr'),
					'IS-REGISTER-IP-RESTRICTED' => (string) ((\SmartModExtLib\AuthUsers\AuthRegister::isRegistrationRestrictedByIp() === true) ? 'yes' : 'no'),
					//--
					'TXT-REGISTER-TITLE' 		=> (string) $this->translator->text('sign-up-reg-activate'),
					'TXT-LNK-HOME-PAGE' 		=> (string) $this->translator->text('homepage'),
					'TXT-BTN-SIGNUP-DEFAULT' 	=> (string) $this->translator->text('sign-up'),
					//--
					'TXT-REGISTER-CODE' 		=> (string) $this->translator->text('sign-up-reg-code'),
					'TXT-CODE-HASH' 			=> (string) $this->translator->text('sign-up-reg-code-hint'),
					'TXT-BTN-ACTIVATE-DEFAULT' 	=> (string) $this->translator->text('sign-up-reg-a-btn'),
					'TXT-BTN-ACTIVATE' 			=> (string) $this->translator->text('sign-up-reg-a-btn-hint'),
					//--
					'REGISTRATION-CODE' 		=> (string) $hash,
					'REGISTRATION-TOKEN' 		=> (string) $token,
					//--
					'TXT-SIGNED-TITLE' 			=> (string) $this->translator->text('signed-up-info'),
					'TXT-SIGNOUT' 				=> (string) $this->translator->text('btn-signout'),
					'TXT-BTN-ACCOUNT' 			=> (string) $this->translator->text('btn-account-display'),
					'TXT-BTN-SETTINGS' 			=> (string) $this->translator->text('btn-account-settings'),
					'CURRENT-ACTION' 			=> (string) $this->ControllerGetParam('action'),
					'TXT-APPS' 					=> (string) $this->translator->text('apps-and-dashboard'),
					//--
				]
			),
			'aside' 	=> (string) SmartMarkersTemplating::render_file_template(
				(string)$this->ControllerGetParam('module-view-path').'register-aside.mtpl.htm',
				[
					//--
					'WEBSITE-NAME' 				=> (string) Smart::get_from_config('app.info-url', 'string'),
					'IS-AUTHENTICATED' 			=> (int)    (SmartAuth::is_authenticated() === true) ? 1 : 0,
					'AUTH-USERNAME' 			=> (string) SmartAuth::get_auth_username() ?: $email,
					//--
					'TXT-REG-TITLE' 			=> (string) $this->translator->text('sign-up-reg-a-ttl'),
					//--
					'TXT-REG-ACTIVATE-TTL'		=> (string) $this->translator->text('sign-up-reg-a-head'),
					'TXT-REG-ACTIVATE-DESC' 	=> (string) $this->translator->text('sign-up-reg-a-head-desc'),
					//--
					'TXT-REG-ACTIV-CODE-TTL' 	=> (string) $this->translator->text('sign-up-reg-a-step1'),
					'TXT-REG-ACTIV-CODE-DESC' 	=> (string) $this->translator->text('sign-up-reg-a-step1-desc'),
					//--
					'TXT-REG-ACTIV-EMAIL' 		=> (string) $this->translator->text('sign-up-reg-a-step2'),
					'TXT-REG-ACTIV-HINT' 		=> (string) $this->translator->text('sign-up-reg-a-step2-desc'),
					'TXT-REG-ACTIV-SECURITY' 	=> (string) $this->translator->text('sign-up-reg-safety'),
					//--
				]
			),
		]);
		//--

		//--
		return 200;
		//--

	} //END FUNCTION


	private function displaySignUp() : int {

		//--
		$captchaHtmlCode = '';
		if(SmartAuth::is_authenticated() !== true) {
			if(\SmartModExtLib\AuthUsers\Utils::isAuthRegisterCaptchaEnabled() !== false) { // if register captcha was not explicit disabled
				$captchaHtmlCode = (string) \SmartModExtLib\AuthUsers\Utils::drawAuthUsersCaptchaHtml();
			} //end if
		} //end if
		//--

		//-- required just for info purposes in the aside area
		$is2FAEnabled  = (bool) \SmartModExtLib\AuthUsers\Utils::isAuth2FAEnabled();
		$is2FARequired = (bool) \SmartModExtLib\AuthUsers\Utils::isAuth2FARequired();
		//--

		//--
		$authPlugins = (array) \SmartModExtLib\AuthUsers\AuthPlugins::getPluginsForDisplay((string)$this->authGetCsrfPublicKey());
		//--

		//--
		$this->PageViewSetVars([
			'title' 	=> (string) $this->translator->text('sign-up'),
			'main' 		=> (string) SmartMarkersTemplating::render_file_template(
				(string) $this->ControllerGetParam('module-view-path').'signup.mtpl.htm',
				[
					//--
					'URL-PREFIX-MASTER' 		=> (string) \SmartModExtLib\AuthUsers\AuthClusterUser::getAuthClusterUrlPrefixMaster(),
					'URL-PREFIX-LOCAL' 			=> (string) \SmartModExtLib\AuthUsers\AuthClusterUser::getAuthClusterUrlPrefixLocal(),
					//--
					'URL-HOME' 					=> (string) SmartUtils::get_server_current_url(),
					'WEBSITE-NAME' 				=> (string) Smart::get_from_config('app.info-url', 'string'),
					'IS-AUTHENTICATED' 			=> (int)    (SmartAuth::is_authenticated() === true) ? 1 : 0,
					'AUTH-USERNAME' 			=> (string) SmartAuth::get_auth_username(),
					//--
					'TXT-REGISTER-IP-RESTRICT' 	=> (string) $this->translator->text('sign-up-ip-restr'),
					'IS-REGISTER-IP-RESTRICTED' => (string) ((\SmartModExtLib\AuthUsers\AuthRegister::isRegistrationRestrictedByIp() === true) ? 'yes' : 'no'),
					//--
					'TXT-SIGNUP-TITLE' 			=> (string) $this->translator->text('sign-up'),
					'TXT-EMAIL-ADDRESS' 		=> (string) $this->translator->text('auth-email'),
					'TXT-PASSWORD' 				=> (string) $this->translator->text('auth-password'),
					'TXT-RE-PASSWORD' 			=> (string) $this->translator->text('auth-repassword'),
					'TXT-BTN-SIGNIN' 			=> (string) $this->translator->text('hint-signin'),
					'TXT-BTN-SIGNUP' 			=> (string) $this->translator->text('hint-btn-signup'),
					'TXT-BTN-RECOVERY' 			=> (string) $this->translator->text('hint-recovery'),
					//--
					'TXT-BTN-SIGNUP-DEFAULT' 	=> (string) $this->translator->text('btn-signup'),
					'TXT-BTN-SIGNIN-DEFAULT' 	=> (string) $this->translator->text('btn-signup-already'),
					'TXT-BTN-PASSWORD-RESET' 	=> (string) $this->translator->text('sign-recovery'),
					'TXT-LNK-HOME-PAGE' 		=> (string) $this->translator->text('homepage'),
					//--
					'TXT-PLUGINS-SIGNIN' 		=> (string) $this->translator->text('sign-in-sso'),
					'TXT-HINT-PLUGINS-SIGNIN' 	=> (string) $this->translator->text('sign-up-with-hint'),
					'TXT-PLUGIN-SIGNIN' 		=> (string) $this->translator->text('sign-up-with'),
					'AUTH-PLUGINS' 				=> (array)  $authPlugins,
					//--
					'TXT-SIGNED-TITLE' 			=> (string) $this->translator->text('signed-up-info'),
					'TXT-SIGNOUT' 				=> (string) $this->translator->text('btn-signout'),
					'TXT-BTN-ACCOUNT' 			=> (string) $this->translator->text('btn-account-display'),
					'TXT-BTN-SETTINGS' 			=> (string) $this->translator->text('btn-account-settings'),
					'CURRENT-ACTION' 			=> (string) $this->ControllerGetParam('action'),
					'TXT-APPS' 					=> (string) $this->translator->text('apps-and-dashboard'),
					//--
				]
			),
			'aside' 	=> (string) SmartMarkersTemplating::render_file_template(
				(string)$this->ControllerGetParam('module-view-path').'signup-aside.mtpl.htm',
				[
					//--
					'WEBSITE-NAME' 				=> (string) Smart::get_from_config('app.info-url', 'string'),
					'IS-AUTHENTICATED' 			=> (int)    (SmartAuth::is_authenticated() === true) ? 1 : 0,
					'AUTH-USERNAME' 			=> (string) SmartAuth::get_auth_username(),
					//--
					'URL-ACCOUNT' 				=> (string) self::URL_ACCOUNT_HREF,
					'URL-REDIR-ACCOUNT-MSEC' 	=> (int)    self::URL_ACCOUNT_TIME,
					//--
					'CAPTCHA-HTML' 				=> (string) $captchaHtmlCode,
					'TXT-CAPTCHA-HINT' 			=> (string) $this->translator->text('sign-up-reg-captcha-hint'),
					//--
					'IS-FA2-AVAILABLE' 			=> (bool)   $is2FAEnabled,
					'IS-FA2-REQUIRED' 			=> (bool)   $is2FARequired,
					//--
					'AUTH-PLUGINS' 				=> (array)  $authPlugins,
					//--
					'TXT-REG-TITLE' 			=> (string) $this->translator->text('sign-up-title'),
					//--
					'TXT-REG-ACKNOWLEDGE' 		=> (string) $this->translator->text('sign-up-reg-head'),
					'TXT-REG-HEADLINE' 			=> (string) $this->translator->text('sign-up-reg-head-desc'),
					//--
					'TXT-REG-STD-TTL' 			=> (string) $this->translator->text('sign-up-reg-step1'),
					'TXT-REG-STD-VALID-EMAIL' 	=> (string) $this->translator->text('sign-up-reg-step1-desc'),
					'TXT-REG-STD-EMAIL-MSG' 	=> (string) $this->translator->text('sign-up-reg-step1-1'),
					'TXT-REG-STD-ACTIVATE' 		=> (string) $this->translator->text('sign-up-reg-step1-1-desc'),
					'TXT-REG-STD-SECURITY' 		=> (string) $this->translator->text('sign-up-reg-safety'),
					//--
					'TXT-REG-SSO-TTL' 			=> (string) $this->translator->text('sign-up-reg-step2'),
					'TXT-REG-SSO-NO-ACTIVATE' 	=> (string) $this->translator->text('sign-up-reg-step2-1'),
					'TXT-REG-SSO-PROVIDERS' 	=> (string) $this->translator->text('sign-up-reg-step2-1-desc'),
					'TXT-REG-SSO-IDENTITIES' 	=> (string) $this->translator->text('sign-up-reg-step2-1-hint'),
					//--
					'TXT-REG-2FA-TTL' 			=> (string) $this->translator->text('sign-up-reg-step3'),
					'TXT-REG-2FA-DESC' 			=> (string) $this->translator->text('sign-up-reg-step3-desc'),
					//--
					'TXT-REG-SEC-TTL' 			=> (string) $this->translator->text('sign-up-reg-step4'),
					'TXT-REG-SEC-DESC' 			=> (string) $this->translator->text('sign-up-reg-step4-desc'),
					//--
				]
			),
		]);
		//--

		//--
		return 200;
		//--

	} //END FUNCTION


} //END CLASS


// end of php code

