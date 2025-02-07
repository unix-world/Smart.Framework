<?php
// Controller: AuthUsers/Account
// Route: ?page=auth-users.account
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
		$title = 'Welcome to Your Account';
		//--
		$this->PageViewSetVars([
			'title' 	=> (string) $title,
			'main' 		=> (string) SmartMarkersTemplating::render_file_template(
				(string) $this->ControllerGetParam('module-view-path').'account.mtpl.htm',
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
				(string) $this->ControllerGetParam('module-view-path').'account-aside.mtpl.htm',
				[
					//--
					'AUTH-USERNAME' 			=> (string) SmartAuth::get_auth_username(),
					//--
					'TXT-ACC-TITLE' 			=> (string) $title,
					'AUTH-ID' 					=> (string) SmartAuth::get_auth_id(),
					'TXT-USER-ID' 				=> 'UserID',
					//--
				]
			),
		]);
		//--

	} //END FUNCTION

} //END CLASS

// end of php code
