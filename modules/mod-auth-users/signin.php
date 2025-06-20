<?php
// Controller: AuthUsers/SignIn
// Route: ?page=auth-users.signin
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

	// r.20250620

	// SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS 	is verified by Initialize() in AbstractSignController
	// Custom request URI Restriction 			is verified by Initialize() in AbstractAccountController

	public function Run() {

		// this controller can operate ONLY on master server

		//--
		if(SmartAuth::is_cluster_master_auth() !== true) {
			$this->PageViewSetErrorStatus(502, 'Not an Auth Cluster Master Server');
			return;
		} //end if
		//--

		//--
		$captchaHtmlCode = '';
		if(SmartAuth::is_authenticated() !== true) {
			if(\SmartModExtLib\AuthUsers\Utils::isAuthLoginCaptchaEnabled() === true) { // if login captcha was explicit enabled
				$captchaHtmlCode = (string) \SmartModExtLib\AuthUsers\Utils::drawAuthUsersCaptchaHtml();
			} //end if
		} //end if
		//--

		//--
		$is2FAEnabled  = (bool) \SmartModExtLib\AuthUsers\Utils::isAuth2FAEnabled();
		$is2FARequired = (bool) \SmartModExtLib\AuthUsers\Utils::isAuth2FARequired();
		//--

		//--
		$authPlugins = (array) \SmartModExtLib\AuthUsers\AuthPlugins::getPluginsForDisplay((string)$this->authGetCsrfPublicKey());
		//--

		//--
		$this->PageViewSetVars([
			'title' 	=> (string) $this->translator->text('sign-in'),
			'main' 		=> (string) SmartMarkersTemplating::render_file_template(
				(string) $this->ControllerGetParam('module-view-path').'signin.mtpl.htm',
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
					'TXT-SIGNIN-TITLE' 			=> (string) $this->translator->text('sign-in-acc'),
					'TXT-USERNAME' 				=> (string) $this->translator->text('auth-email-or-user'),
					'TXT-PASSWORD' 				=> (string) $this->translator->text('auth-password'),
					'TXT-BTN-SIGNIN' 			=> (string) $this->translator->text('hint-btn-signin'),
					'TXT-BTN-SIGNUP' 			=> (string) $this->translator->text('hint-signup'),
					'TXT-BTN-RECOVERY' 			=> (string) $this->translator->text('hint-recovery'),
					'TXT-2FA-CODE' 				=> (string) $this->translator->text('sign-in-2fa'), // must be very short, it appears nead the pin entry
					'TXT-2FA-TITLE' 			=> (string) $this->translator->text('sign-in-2fa-ttl'),
					'TXT-2FA-HINT' 				=> (string) $this->translator->text('sign-in-2fa-hint'),
					//--
					'IS-FA2-AVAILABLE' 			=> (bool)   $is2FAEnabled,
					'IS-FA2-REQUIRED' 			=> (bool)   $is2FARequired,
					//--
					'TXT-BTN-SIGNIN-DEFAULT' 	=> (string) $this->translator->text('sign-in'),
					'TXT-BTN-SIGNUP-DEFAULT' 	=> (string) $this->translator->text('sign-up'),
					'TXT-BTN-PASSWORD-RESET' 	=> (string) $this->translator->text('sign-recovery'),
					'TXT-LNK-HOME-PAGE' 		=> (string) $this->translator->text('homepage'),
					//--
					'TXT-PLUGINS-SIGNIN' 		=> (string) $this->translator->text('sign-in-sso'),
					'TXT-HINT-PLUGINS-SIGNIN' 	=> (string) $this->translator->text('sign-up-with-hint'),
					'TXT-PLUGIN-SIGNIN' 		=> (string) $this->translator->text('sign-in-with'),
					'AUTH-PLUGINS' 				=> (array)  $authPlugins,
					//--
					'TXT-SIGNED-TITLE' 			=> (string) $this->translator->text('signed-in-info'),
					'TXT-SIGNOUT' 				=> (string) $this->translator->text('btn-signout'),
					'TXT-BTN-ACCOUNT' 			=> (string) $this->translator->text('btn-account-display'),
					'TXT-BTN-SETTINGS' 			=> (string) $this->translator->text('btn-account-settings'),
					'CURRENT-ACTION' 			=> (string) $this->ControllerGetParam('action'),
					'TXT-APPS' 					=> (string) $this->translator->text('apps-and-dashboard'),
					//--
				]
			),
			'aside' 	=> (string) SmartMarkersTemplating::render_file_template(
				(string)$this->ControllerGetParam('module-view-path').'signin-aside.mtpl.htm',
				[
					//--
					'WEBSITE-NAME' 				=> (string) Smart::get_from_config('app.info-url', 'string'),
					'IS-AUTHENTICATED' 			=> (int)    (SmartAuth::is_authenticated() === true) ? 1 : 0,
					'AUTH-USERNAME' 			=> (string) SmartAuth::get_auth_username(),
					//--
					'CAPTCHA-HTML' 				=> (string) $captchaHtmlCode,
					'IS-FA2-AVAILABLE' 			=> (bool)   $is2FAEnabled,
					'IS-FA2-REQUIRED' 			=> (bool)   $is2FARequired,
					//--
				]
			),
		]);
		//--

	} //END FUNCTION


} //END CLASS


// end of php code

