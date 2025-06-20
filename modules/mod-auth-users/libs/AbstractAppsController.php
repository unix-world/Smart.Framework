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

	// r.20250314

	// SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS 	is verified by Initialize() in AbstractAccountController
	// Custom request URI Restriction 			is verified by Initialize() in AbstractAccountController

	protected string $logo = ''; // optional

	protected ?array $semaphores = null;
	protected string $templatePath = 'modules/mod-auth-users/templates/'; 	// this must be hardcoded because this class can be extended in other modules
	protected string $templateFile = 'template-apps.htm'; 					// this must be hardcoded because this class can be extended in other modules


	protected function setAppTitle() : string {
		//--
		return (string) $this->translator->text('apps-n-dashboard');
		//--
	} //END FUNCTION


	protected function setAppMenuHtml() : string { // optional
		//--
		return '<!-- app menu html -->';
		//--
	} //END FUNCTION


	final protected function preRun() : bool {
		//--
		if(\SmartAuth::is_cluster_current_workspace() !== true) {
			$this->PageViewSetErrorStatus(502, 'This App is unavailable outside of your User Account Clustered WorkSpace');
			return false;
		} //end if
		//--
		$title = (string) \trim((string)$this->setAppTitle());
		if((string)$title == '') {
			$title = (string) $this->translator->text('apps-n-dashboard');
		} //end if
		//--
		$this->PageViewSetVars([
			'title' => (string) $title,
			'main' 	=> (string) \SmartMarkersTemplating::render_file_template(
				(string) 'modules/mod-auth-users/views/apps.mtpl.htm', // this must be hardcoded because this class can be extended in other modules
				[
					//--
					'WEBSITE-NAME' 				=> (string) \Smart::get_from_config('app.info-url', 'string'),
					//--
					'AUTH-USERNAME' 			=> (string) \SmartAuth::get_auth_username(),
					'CURRENT-ACTION' 			=> (string) $this->ControllerGetParam('action'),
					//--
					'APP-TITLE' 				=> (string) (string) $title,
					'APP-LOGO' 					=> (string) (string) $this->logo,
					'APP-MENU-HTML' 			=> (string) (string) $this->setAppMenuHtml(),
					//--
				]
			),
			'aside' => (string) \SmartMarkersTemplating::render_file_template(
				(string) 'modules/mod-auth-users/views/apps-aside.mtpl.htm', // this must be hardcoded because this class can be extended in other modules
				[
					//--
					'URL-PREFIX-MASTER' 		=> (string) \SmartModExtLib\AuthUsers\AuthClusterUser::getAuthClusterUrlPrefixMaster(),
					'URL-PREFIX-LOCAL' 			=> (string) \SmartModExtLib\AuthUsers\AuthClusterUser::getAuthClusterUrlPrefixLocal(),
					//--
					'WEBSITE-NAME' 				=> (string) \Smart::get_from_config('app.info-url', 'string'),
					//--
					'AUTH-USERNAME' 			=> (string) \SmartAuth::get_auth_username(),
					'CURRENT-ACTION' 			=> (string) $this->ControllerGetParam('action'),
					//--
					'SIGNED-TITLE' 				=> (string) $this->translator->text('signed-in'),
					'SIGN-OUT' 					=> (string) $this->translator->text('btn-signout'),
					'MY-ACCOUNT' 				=> (string) $this->translator->text('my-account'),
					'MY-DASHBOARD' 				=> (string) $this->translator->text('my-dashboard'),
					'MY-APPS' 					=> (string) $this->translator->text('my-apps'),
					'CLICK-EXPAND-ON-OFF' 		=> (string) $this->translator->text('click-expand'),
					//--
					'APPS-JSON' 				=> (string) \Smart::json_encode((array)\SmartModExtLib\AuthUsers\Apps::getApps()),
					//--
				]
			),
		]);
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


} //END CLASS


// end of php code

