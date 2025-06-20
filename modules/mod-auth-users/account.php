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

	// r.20250620

	// SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS 	is verified by Initialize() in AbstractAccountController
	// Custom request URI Restriction 			is verified by Initialize() in AbstractAccountController

	public function Run() {

		//--
		$title = (string) $this->translator->text('welcome');
		//--
		$this->PageViewSetVars([
			'title' 	=> (string) $title,
			'main' 		=> (string) SmartMarkersTemplating::render_file_template(
				(string) $this->ControllerGetParam('module-view-path').'account.mtpl.htm',
				[
					//--
					'URL-PREFIX-MASTER' 		=> (string) \SmartModExtLib\AuthUsers\AuthClusterUser::getAuthClusterUrlPrefixMaster(),
					'URL-PREFIX-LOCAL' 			=> (string) \SmartModExtLib\AuthUsers\AuthClusterUser::getAuthClusterUrlPrefixLocal(),
					//--
					'AUTH-USERNAME' 			=> (string) SmartAuth::get_auth_username(),
					'TXT-SIGNED-TITLE' 			=> (string) $this->translator->text('signed-in'),
					'TXT-SIGNOUT' 				=> (string) $this->translator->text('btn-signout'),
					'TXT-BTN-ACCOUNT' 			=> (string) $this->translator->text('btn-account-display'),
					'TXT-BTN-SETTINGS' 			=> (string) $this->translator->text('btn-account-settings'),
					'CURRENT-ACTION' 			=> (string) $this->ControllerGetParam('action'),
					'TXT-APPS' 					=> (string) $this->translator->text('apps-and-dashboard'),
					//--
				]
			),
			'aside' => (string) SmartMarkersTemplating::render_file_template(
				(string) $this->ControllerGetParam('module-view-path').'account-aside.mtpl.htm',
				[
					//--
					'TXT-NAV-ACCOUNT' 			=> (string) $this->translator->text('nav-account'),
					'AUTH-USERNAME' 			=> (string) SmartAuth::get_auth_username(),
					'TXT-ACC-TITLE' 			=> (string) $title,
					'AUTH-ID' 					=> (string) SmartAuth::get_auth_id(),
					'TXT-USER-ID' 				=> (string) $this->translator->text('id-user'),
					'CLUSTER-ID' 				=> (string) SmartAuth::get_auth_cluster_id(),
					'TXT-CLUSTER-ID' 			=> (string) $this->translator->text('id-cluster'),
					//--
				]
			),
		]);
		//--

	} //END FUNCTION

} //END CLASS

// end of php code
