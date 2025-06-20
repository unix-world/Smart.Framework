<?php
// Controller: \SmartModExtLib\AuthUsers\AbstractSignController
// (c) 2025-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

namespace SmartModExtLib\AuthUsers;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


abstract class AbstractSignController extends \SmartAbstractAppController {

	// r.20250529

	protected ?object $translator = null;

	protected ?array $semaphores = null;
	protected string $templatePath = '';
	protected string $templateFile = '';

	private string $csrfPublicKey = '';


	final protected function authGetCsrfPublicKey() : string {
		//--
		return (string) $this->csrfPublicKey;
		//--
	} //END FUNCTION


	final public function Initialize() {

		//--
		if(!\defined('\\SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS') OR (\SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS !== true)) {
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
		if(!\SmartAppInfo::TestIfModuleExists('mod-auth-admins')) {
			$this->PageViewSetErrorStatus(500, 'Mod AuthAdmins is missing !');
			return false;
		} //end if
		//--
		if(!\SmartAppInfo::TestIfModuleExists('mod-oauth2')) {
			$this->PageViewSetErrorStatus(500, 'Mod Oauth2 is missing !');
			return false;
		} //end if
		//--

		//--
		if(\SmartEnvironment::isAdminArea() !== false) {
			\Smart::log_warning(__METHOD__.' # ERR: Controller cannot run under Admin area');
			$this->PageViewSetErrorStatus(502, 'ERROR: This Abstract Controller must run inside Index Area');
			return false;
		} //end if
		//--

		//--
		if(\SmartAuth::is_authenticated() !== true) { // if user is authenticated prevent issuing a CSRF, to save cookie space, it is not allowed to have auth actions
			$this->csrfPublicKey = (string) \SmartModExtLib\AuthUsers\Utils::setCsrfCookie();
		} //end if
		//--

		//--
		$redir = (string) \trim((string)$this->RequestVarGet('redir', '', 'string'));
		if((string)$redir != '') {
			\SmartModExtLib\AuthUsers\Utils::setRedirUrlCookie((string)$redir);
		} //end if
		//--

		//--
		if($this->translator === null) {
			$this->translator = \SmartTextTranslations::getTranslator('mod-auth-users', 'auth-users');
			if($this->translator === null) {
				$this->PageViewSetErrorStatus(500, 'Mod Auth Users Translator is Missing !');
				return false;
			} //end if
		} //end if
		//--

		//--
		$this->templatePath = (string) \trim((string)$this->templatePath);
		if((string)$this->templatePath == '') {
			$this->templatePath = '@';
		} //end if
		//--
		$this->templateFile = (string) \trim((string)$this->templateFile);
		if((string)$this->templateFile == '') {
			if(\SmartAuth::is_authenticated() === true) {
				$this->templateFile = 'template-account.htm';
			} else {
				$this->templateFile = 'template-sign.htm';
			} //end if
		} //end if
		//--
		$this->PageViewSetCfg('template-path', (string)$this->templatePath);
		$this->PageViewSetCfg('template-file', (string)$this->templateFile);
		//--

		//--
		if($this->semaphores === null) {
			//--
			$this->semaphores = [];
			//--
			$this->semaphores[] = 'styles:dark';
			$this->semaphores[] = 'skip:js-ui';
			$this->semaphores[] = 'skip:js-media';
			$this->semaphores[] = 'skip:unveil-js';
			//--
		} //end if
		//--
		if(!\is_array($this->semaphores)) {
			$this->semaphores = [];
		} //end if
		if((int)\Smart::array_type_test($this->semaphores) != 1) {
			$this->semaphores = [];
		} //end if
		//--
		$this->PageViewSetVar('semaphore', (string)$this->PageViewCreateSemaphores((array)$this->semaphores));
		//--

		//--
		return true;
		//--

	} //END FUNCTION


	public function Run() {
		//--
		// pre-define it to return an empty blank page ; this method have to be re-defined
		//--
		\Smart::log_warning(__METHOD__.' # No Output. This method must be redefined in the running controller ...');
		//--
		$this->PageViewSetErrorStatus(500, 'No Output');
		return;
		//--
	} //END FUNCTION


	final public function ShutDown() {
		//--
		// N/A
		//--
	} //END FUNCTION


} //END CLASS


// end of php code

