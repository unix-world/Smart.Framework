<?php
// Controller: \SmartModExtLib\AuthUsers\AbstractAppsController
// (c) 2025-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

namespace SmartModExtLib\AuthUsers;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


abstract class AbstractAppsController extends \SmartModExtLib\AuthUsers\AbstractAccountController {

	// r.20250205

	protected string $title = 'Apps`n`Dashboard';
	protected string $logo = ''; // optional

	protected ?array $semaphores = null;
	protected string $templatePath = 'modules/mod-auth-users/templates/'; 	// this must be hardcoded because this class can be extended in other modules
	protected string $templateFile = 'template-apps.htm'; 					// this must be hardcoded because this class can be extended in other modules


	protected function setAppMenuHtml() : string { // optional
		//--
		return '';
		//--
	} //END FUNCTION


	final protected function preRun() : void {
		//--
		$this->PageViewSetVars([
			'title' => (string) $this->title,
			'main' 		=> (string) \SmartMarkersTemplating::render_file_template(
				(string) 'modules/mod-auth-users/views/apps.mtpl.htm', // this must be hardcoded because this class can be extended in other modules
				[
					//--
					'AUTH-USERNAME' 			=> (string) \SmartAuth::get_auth_username(),
					'CURRENT-ACTION' 			=> (string) $this->ControllerGetParam('action'),
					//--
					'APP-TITLE' 				=> (string) (string) $this->title,
					'APP-LOGO' 					=> (string) (string) $this->logo,
					'APP-MENU-HTML' 			=> (string) (string) $this->setAppMenuHtml(),
					//--
				]
			),
			'aside' => (string) \SmartMarkersTemplating::render_file_template(
				(string) 'modules/mod-auth-users/views/apps-aside.mtpl.htm', // this must be hardcoded because this class can be extended in other modules
				[
					//--
					'AUTH-USERNAME' 			=> (string) \SmartAuth::get_auth_username(),
					'CURRENT-ACTION' 			=> (string) $this->ControllerGetParam('action'),
					//--
				]
			),
		]);
		//--
	} //END FUNCTION


	public function Run() {
		//--
		\Smart::log_warning(__METHOD__.' # No Output. This method must be redefined in the running controller ...');
		//--
		return true; // pre-define it to return an empty blank page
		//--
	} //END FUNCTION


} //END CLASS


// end of php code

