<?php
// Controller: AuthUsers/Settings
// Route: ?page=auth-users.settings
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


final class SmartAppIndexController extends \SmartModExtLib\AuthUsers\AbstractAccountController {

	// r.20250203

	public function Run() {

		//--
		$title = 'Modify Your Account Settings';
		//--
		$this->PageViewSetVars([
			'title' 	=> (string) $title,
			'main' 		=> (string) SmartMarkersTemplating::render_file_template(
				(string) $this->ControllerGetParam('module-view-path').'settings.mtpl.htm',
				[
					//--
					'AUTH-USERNAME' 			=> (string) SmartAuth::get_auth_username(),
					'TXT-SIGNED-TITLE' 			=> 'You are Signed-In',
					'TXT-SIGNOUT' 				=> 'Sign-Out',
					'TXT-BTN-ACCOUNT' 			=> 'Display Your Account',
					'TXT-BTN-SETTINGS' 			=> 'Your Account Settings',
					'CURRENT-ACTION' 			=> (string) $this->ControllerGetParam('action'),
					'TXT-APPS' 					=> 'Apps and Dashboard',
					//--
				]
			),
			'aside' => (string) SmartMarkersTemplating::render_file_template(
				(string) $this->ControllerGetParam('module-view-path').'settings-aside.mtpl.htm',
				[
					//--
					'AUTH-USERNAME' 			=> (string) SmartAuth::get_auth_username(),
					//--
					'TXT-ACC-TITLE' 			=> (string) $title,
					'AUTH-ID' 					=> (string) SmartAuth::get_auth_id(),
					'TXT-USER-ID' 				=> 'UserID',
					//--
					'TXT-FULL-NAME' 			=> 'Full Name',
					'AUTH-FULL-NAME' 			=> (string) SmartAuth::get_user_fullname(),
					'TXT-DETAILS' 				=> 'Details',
					'AUTH-DETAILS' 				=> (string) Smart::json_encode((array)SmartAuth::get_user_metadata(), true, true, false),
				]
			),
		]);
		//--

	} //END FUNCTION

} //END CLASS

// end of php code
