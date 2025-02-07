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

	// r.20250205

	public function Run() {

		//--
		$isRegisterCaptchaEnabled = (bool) \SmartModExtLib\AuthUsers\Utils::isAuthRegisterCaptchaEnabled();
		$captchaHtmlCode = '';
		if(SmartAuth::is_authenticated() !== true) {
			if($isRegisterCaptchaEnabled !== false) { // if register captcha was not explicit disabled
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
			'title' 	=> 'Sign-Up',
			'main' 		=> (string) SmartMarkersTemplating::render_file_template(
				(string) $this->ControllerGetParam('module-view-path').'signup.mtpl.htm',
				[
					//--
					'WEBSITE-NAME' 				=> (string) Smart::get_from_config('app.info-url', 'string'),
					'IS-AUTHENTICATED' 			=> (int)    (SmartAuth::is_authenticated() === true) ? 1 : 0,
					'AUTH-USERNAME' 			=> (string) SmartAuth::get_auth_username(),
					//--
					'TXT-SIGNUP-TITLE' 			=> 'Register a New Account',
					'TXT-EMAIL-ADDRESS' 		=> 'Your Email Address',
					'TXT-PASSWORD' 				=> 'Your Password',
					'TXT-RE-PASSWORD' 			=> 'Re-Type Your Password',
					'TXT-BTN-SIGNIN' 			=> 'If you already have an account, click here to Sign-In',
					'TXT-BTN-SIGNUP' 			=> 'Enter your Email and Password, Re-Type your Password and click this button, for Sign-Up (will create a New Account)',
					'TXT-BTN-RECOVERY' 			=> 'If you forgot your password click this button, for Password Recovery',
					//--
					'TXT-BTN-SIGNUP-DEFAULT' 	=> 'Sign-Up',
					'TXT-BTN-SIGNIN-DEFAULT' 	=> 'Already have an Account ?',
					'TXT-BTN-PASSWORD-RESET' 	=> 'Forgot Your Password ?',
					'TXT-LNK-TROUBLE-SIGNUP' 	=> 'Sign-Up Troubles ? Read the instructions.',
					//--
					'TXT-PLUGINS-SIGNIN' 		=> 'or use SSO, easy Sign-Up with',
					'TXT-HINT-PLUGINS-SIGNIN' 	=> 'Simple and secure Authentication, without passwords. Select by click a SSO identity provider from below to Authenticate using Single-Sign-On. If you have multiple identities, using different email addresses on different providers, be sure you pick-up the same provider each time, otherwise you will be landing in a different account namespace.',
					'TXT-PLUGIN-SIGNIN' 		=> 'Sign-Up with',
					'AUTH-PLUGINS' 				=> (array)  $authPlugins,
					//--
					'TXT-SIGNED-TITLE' 			=> 'Your Sign-Up Info',
					'TXT-SIGNOUT' 				=> 'Sign-Out',
					'TXT-BTN-ACCOUNT' 			=> 'Display Your Account',
					'TXT-BTN-SETTINGS' 			=> 'Your Account Settings',
					'CURRENT-ACTION' 			=> (string) $this->ControllerGetParam('action'),
					'TXT-APPS' 					=> 'Apps and Dashboard',
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
					'IS-CAPTCHA-ENABLED' 		=> (string) (($isRegisterCaptchaEnabled !== false) ? 'yes' : 'no'),
					'CAPTCHA-HTML' 				=> (string) $captchaHtmlCode,
					'IS-FA2-AVAILABLE' 			=> (bool)   $is2FAEnabled,
					'IS-FA2-REQUIRED' 			=> (bool)   $is2FARequired,
					//--
					'TXT-REG-TITLE' 			=> 'New Account Registration',
					'AUTH-PLUGINS' 				=> (array)  $authPlugins,
					//--
				]
			),
		]);
		//--

	} //END FUNCTION


} //END CLASS


// end of php code

