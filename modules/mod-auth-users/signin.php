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

	// r.20250207

	public function Run() {

		//--
		$isLoginCaptchaEnabled = (bool) \SmartModExtLib\AuthUsers\Utils::isAuthLoginCaptchaEnabled();
		$captchaHtmlCode = '';
		if(SmartAuth::is_authenticated() !== true) {
			if($isLoginCaptchaEnabled === true) { // if login captcha was explicit enabled
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
			'title' 	=> 'Sign-In',
			'main' 		=> (string) SmartMarkersTemplating::render_file_template(
				(string) $this->ControllerGetParam('module-view-path').'signin.mtpl.htm',
				[
					//--
					'WEBSITE-NAME' 				=> (string) Smart::get_from_config('app.info-url', 'string'),
					'IS-AUTHENTICATED' 			=> (int)    (SmartAuth::is_authenticated() === true) ? 1 : 0,
					'AUTH-USERNAME' 			=> (string) SmartAuth::get_auth_username(),
					//--
					'TXT-SIGNIN-TITLE' 			=> 'Sign-In to Your Account',
					'TXT-USERNAME' 				=> 'Email Address or UserName',
					'TXT-PASSWORD' 				=> 'Password',
					'TXT-BTN-SIGNIN' 			=> 'Enter your Email/UserName and Password and click this button, for Sign-In',
					'TXT-BTN-SIGNUP' 			=> 'If you do not already have an account click this button, for Sign-Up',
					'TXT-BTN-RECOVERY' 			=> 'If you forgot your password click this button, for Password Recovery',
					'TXT-2FA-CODE' 				=> '2FA Code', // must be very short, it appears nead the pin entry
					'TXT-2FA-TITLE' 			=> 'Enter the Two-Factor Authentication Code here (TOTP 2FA Token)',
					'TXT-2FA-HINT' 				=> 'The (TOTP) 2FA Code is required just if you enabled the Two-Factor Authentication on your account. Enter the TOTP Code generated by your Authenticaton App (ex: Google Authenticator or Free OTP).',
					//--
					'IS-FA2-AVAILABLE' 			=> (bool)   $is2FAEnabled,
					'IS-FA2-REQUIRED' 			=> (bool)   $is2FARequired,
					//--
					'TXT-BTN-SIGNIN-DEFAULT' 	=> 'Sign-In',
					'TXT-BTN-SIGNUP-DEFAULT' 	=> 'Register a New Account',
					'TXT-BTN-PASSWORD-RESET' 	=> 'Forgot Your Password ?',
					'TXT-LNK-TROUBLE-SIGNIN' 	=> 'Sign-In Troubles ? Read the instructions.',
					//--
					'TXT-PLUGINS-SIGNIN' 		=> 'or use SSO, easy Sign-Up with',
					'TXT-HINT-PLUGINS-SIGNIN' 	=> 'Simple and secure Authentication, without passwords. Select by click a SSO identity provider from below to Authenticate using Single-Sign-On. If you have multiple identities, using different email addresses on different providers, be sure you pick-up the same provider each time, otherwise you will be landing in a different account namespace.',
					'TXT-PLUGIN-SIGNIN' 		=> 'Sign-In with',
					'AUTH-PLUGINS' 				=> (array)  $authPlugins,
					//--
					'TXT-SIGNED-TITLE' 			=> 'Your Sign-In Info',
					'TXT-SIGNOUT' 				=> 'Sign-Out',
					'TXT-BTN-ACCOUNT' 			=> 'Display Your Account',
					'TXT-BTN-SETTINGS' 			=> 'Your Account Settings',
					'CURRENT-ACTION' 			=> (string) $this->ControllerGetParam('action'),
					'TXT-APPS' 					=> 'Apps and Dashboard',
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
					'IS-CAPTCHA-ENABLED' 		=> (string) (($isLoginCaptchaEnabled !== false) ? 'yes' : 'no'),
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

