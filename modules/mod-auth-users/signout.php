<?php
// Controller: AuthUsers/SignOut
// Route: ?page=auth-users.signout
// (c) 2025-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'INDEX'); // INDEX, ADMIN, TASK, SHARED


final class SmartAppIndexController extends SmartAbstractAppController {

	// r.20250205

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
		// This must not return 403 if authenticated or not, to be able to safe logout in any weird situation that may happen
		//--

		//--
		$this->PageViewSetCfg('template-path', '@');
		$this->PageViewSetCfg('template-file', 'template.htm');
		//--

		//--
		$semaphores = [];
		//--
		$semaphores[] = 'styles:dark';
		$semaphores[] = 'skip:js-ui';
		$semaphores[] = 'skip:js-media';
		$semaphores[] = 'skip:unveil-js';
		//--
		$this->PageViewSetVar('semaphore', (string)$this->PageViewCreateSemaphores((array)$semaphores));
		//--

		//--
		return true;
		//--

	} //END FUNCTION


	public function Run() {

		//--
		$title = 'Sign-Out of Your Account';
		//--

		//--
		$waitmsg = 'You do Not Appear to be Signed-In ...';
		$message = 'All Authentication Data have been Cleared ...';
		//--
		if(SmartAuth::is_authenticated() === true) {
			$waitmsg = 'You will be signed out of your account ...';
			$message = 'You have been Signed-Out your account ...';
		} //end if

		//--
		$isOk = \SmartModExtLib\AuthUsers\AuthCookie::usetJwtCookie();
		if($isOk !== true) {
			$message = 'Sign out Failed ! Please contact the website administrator.';
		} //end if
		//--

		//-- {{{SYNC-AUTH-USERS-CLEAR-COOKIES}}}
		\SmartModExtLib\AuthUsers\Utils::unsetRedirUrlCookie(); // clear redir cookie
		\SmartModExtLib\AuthUsers\Utils::unsetCsrfCookie(); // clear csrf cookie on signout
		\SmartModExtLib\AuthUsers\Utils::clearAuthUsersCaptchaHtml(); // clear captcha cookies on signout
		//--

		//--
		$this->PageViewSetVars([
			'title' 	=> (string) $title,
			'main' 		=> (string) SmartMarkersTemplating::render_file_template(
				(string) $this->ControllerGetParam('module-view-path').'signout.mtpl.htm',
				[
					'IS-ERROR' 		=> (string) ($isOk ? '0' : '1'),
					'TXT-TITLE' 	=> (string) $title,
					'TXT-WAIT' 		=> (string) $waitmsg,
					'TXT-SIGNOUT' 	=> (string) $message,
					'TXT-BTN-REDIR' => 'Return to the Sign-In Page',
					'URL-REDIR' 	=> (string) \SmartModExtLib\AuthUsers\Utils::AUTH_USERS_URL_SIGNIN,
				]
			)
		]);
		//--

	} //END FUNCTION


} //END CLASS


// end of php code
