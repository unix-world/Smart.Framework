<?php
// Controller: \SmartModExtLib\AuthUsers\AbstractAppApiController
// (c) 2025-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

namespace SmartModExtLib\AuthUsers;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


abstract class AbstractAppApiController extends \SmartAbstractAppController {

	// r.20251202

	// this controller can operate ONLY on current user's workspace (server)

	protected bool $allowOnlyXHR = true;

	final public function Initialize() {

		//--
		if(!\defined('\\SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS') OR (\SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS !== true)) {
			$this->PageViewSetErrorStatus(503, 'Mod Auth Users is NOT Enabled ...');
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
		if(\SmartAuth::is_cluster_current_workspace() !== true) {
			$this->PageViewSetErrorStatus(502, 'Not an Auth Cluster Current Workspace Server');
			return false;
		} //end if
		//--

		//--
		if(\SmartAuth::is_authenticated() !== true) {
			$this->PageViewSetErrorStatus(403, 'Authentication is Required');
			return false;
		} //end if
		//--

		//--
		if($this->allowOnlyXHR !== false) {
			if(\SmartUtils::is_ajax_request() !== true) {
				$this->PageViewSetErrorStatus(400, 'Invalid Api Request');
				return false;
			} //end if
		} //end if
		//--

		//--
		$this->PageViewSetCfg('rawpage', true);
		$this->PageViewSetCfg('rawmime', 'application/json');
		//--

	} //END FUNCTION


	public function Run() { // (OUTPUTS: JSON)
		//--
		// pre-define it to return an error like JSON message ; this method have to be re-defined
		//--
		\Smart::log_warning(__METHOD__.' # No Output. This method must be redefined in the running controller ...');
		//--
		$status   = 'INVALID';
		$title    = 'Abstract AppApi Controller';
		$message  = 'No Output';
		$redirect = '';
		$jsevcode = '';
		//--
		$this->PageViewSetVar(
			'main',
			(string) $this->jsonApiResponse(
				(string) $status,
				(string) $title,
				(string) $message,
				(string) $redirect,
				(string) $jsevcode
			)
		);
		//--
		return 208; // 200
		//--
	} //END FUNCTION


	final protected function jsonApiResponse(string $status, string $title, string $message, string $redirect='', string $jsevcode='') : string {
		//--
		return (string) \SmartViewHtmlHelpers::js_ajax_replyto_html_form(
			(string) $status,
			(string) $title,
			(string) \Smart::nl_2_br((string)\Smart::escape_html((string)$message)),
			(string) $redirect,
			'',
			'',
			(string) $jsevcode
		);
		//--
	} //END FUNCTION


} //END CLASS


// end of php code

