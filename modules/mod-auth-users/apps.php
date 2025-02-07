<?php
// Controller: AuthUsers/Apps
// Route: ?page=auth-users.apps
// (c) 2025-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT S EXECUTION
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'INDEX');
define('SMART_APP_MODULE_AUTH', true);


final class SmartAppIndexController extends \SmartModExtLib\AuthUsers\AbstractAppsController {

	// r.20250203

	protected string $title = 'Apps and Dashboard';
	protected string $logo = ''; // optional


	protected function setAppMenuHtml() : string { // optional, can be used to extend the app menu
		//--
		return '<!-- no app menu -->';
		//--
	} //END FUNCTION


	public function Run() {

		//--
		$this->PageViewSetVars([
			'main' 		=> (string) SmartMarkersTemplating::render_placeholder_tpl(
				(string) $this->PageViewGetVar('main'),
				[
					'MOD-AUTH-USERS-APP-HTML' => 'abc',
				]
			)
		]);
		//--

	} //END FUNCTION


} //END CLASS

// end of php code
