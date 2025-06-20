<?php
// Controller: AuthUsers/Recovery
// Route: ?page=auth-users.recovery
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
			if(\SmartModExtLib\AuthUsers\Utils::isAuthRecoveryCaptchaEnabled() !== false) { // if recovery captcha was not explicit disabled
				$captchaHtmlCode = (string) \SmartModExtLib\AuthUsers\Utils::drawAuthUsersCaptchaHtml();
			} //end if
		} //end if
		//--

		//--
		$this->PageViewSetVars([
			'title' 	=> (string) $this->translator->text('recovery-title'),
			'main' 		=> (string) SmartMarkersTemplating::render_file_template(
				(string) $this->ControllerGetParam('module-view-path').'recovery.mtpl.htm',
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
					'TXT-RECOVERY-TITLE' 		=> (string) $this->translator->text('sign-recovery'),
					'TXT-USERNAME' 				=> (string) $this->translator->text('auth-email-or-user'),
					'TXT-BTN-SIGNIN' 			=> (string) $this->translator->text('hint-signin'),
					'TXT-BTN-SIGNUP' 			=> (string) $this->translator->text('hint-signup'),
					'TXT-BTN-RECOVERY' 			=> (string) $this->translator->text('hint-btn-recovery'),
					//--
					'TXT-BTN-SIGNIN-DEFAULT' 	=> (string) $this->translator->text('sign-in-acc'),
					'TXT-BTN-SIGNUP-DEFAULT' 	=> (string) $this->translator->text('sign-up'),
					'TXT-BTN-PASSWORD-RESET' 	=> (string) $this->translator->text('btn-recovery'),
					'TXT-LNK-HOME-PAGE' 		=> (string) $this->translator->text('homepage'),
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
				(string)$this->ControllerGetParam('module-view-path').'recovery-aside.mtpl.htm',
				[
					//--
					'WEBSITE-NAME' 				=> (string) Smart::get_from_config('app.info-url', 'string'),
					'IS-AUTHENTICATED' 			=> (int)    (SmartAuth::is_authenticated() === true) ? 1 : 0,
					'AUTH-USERNAME' 			=> (string) SmartAuth::get_auth_username(),
					//--
					'CAPTCHA-HTML' 				=> (string) $captchaHtmlCode,
					'TXT-CAPTCHA-HINT' 			=> (string) $this->translator->text('recovery-captcha-hint'),
					//--
					'TXT-RECOVERY-TITLE' 		=> (string) $this->translator->text('recovery-title-alt'),
					//--
					'TXT-RECOVERY-INFO' 		=> (string) $this->translator->text('recovery-head'),
					'TXT-RECOVERY-HEADLINE' 	=> (string) $this->translator->text('recovery-head-desc'),
					//--
					'TXT-RECOVERY-STEPS' 		=> (string) $this->translator->text('recovery-step1'),
					'TXT-RECOVERY-HOWTO' 		=> (string) $this->translator->text('recovery-step1-desc'),
					//--
					'TXT-RECOVERY-EMAIL' 		=> (string) $this->translator->text('recovery-step2'),
					'TXT-RECOVERY-SPAM' 		=> (string) $this->translator->text('recovery-step2-desc'),
					//--
				]
			),
		]);
		//--

	} //END FUNCTION


} //END CLASS


// end of php code

