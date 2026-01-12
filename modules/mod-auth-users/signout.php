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

	// r.20251229

	private ?object $translator = null;

	public function Initialize() {

		//--
		if(!defined('SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS') OR (SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS !== true)) {
			$this->PageViewSetErrorStatus(503, 'Mod Auth Users is NOT Enabled ...');
			return false;
		} //end if
		//--
		if(\SmartModExtLib\AuthUsers\Utils::isValidRequestUri() !== true) {
			$this->PageViewSetErrorStatus(404, 'Auth Users cannot handle a Custom Request URI');
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
		// This must not return 403 if authenticated or not, to be able to safe logout in any weird situation that may happen
		//--

		//--
		if($this->translator === null) {
			$this->translator = SmartTextTranslations::getTranslator('mod-auth-users', 'auth-users');
		} //end if
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
		$title = (string) $this->translator->text('sign-out');
		//--

		//--
		$waitmsg = (string) $this->translator->text('sign-out-ttl-x');
		$message = (string) $this->translator->text('sign-out-msg-x');
		//--
		if(SmartAuth::is_authenticated() === true) {
			$waitmsg = (string) $this->translator->text('sign-out-ttl');
			$message = (string) $this->translator->text('sign-out-msg');
		} //end if

		//--
		$isOk = \SmartModExtLib\AuthUsers\AuthCookie::unsetJwtCookie();
		if($isOk !== true) {
			$message = (string) $this->translator->text('sign-out-fail');
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
					'TXT-BTN-REDIR' => (string) $this->translator->text('sign-out-return'),
					'URL-REDIR' 	=> (string) \SmartModExtLib\AuthUsers\Utils::AUTH_USERS_URL_SIGNIN,
				]
			)
		]);
		//--

	} //END FUNCTION


} //END CLASS


// end of php code
