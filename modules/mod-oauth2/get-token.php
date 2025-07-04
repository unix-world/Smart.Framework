<?php
// Controller: OAuth2 / Get Token
// Route: ?/page/oauth2.get-token (?page=oauth2.get-token)
// (c) 2008-present unix-world.org - all rights reserved

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'SHARED'); // SHARED
define('SMART_APP_MODULE_AUTH', true);


final class SmartAppIndexController extends AbstractController {}

final class SmartAppTaskController  extends AbstractController {}

final class SmartAppAdminController extends AbstractController {}


abstract class AbstractController extends SmartAbstractAppController {

	// v.20250218

	public function Initialize() {
		//--
		if(!SmartAppInfo::TestIfModuleExists('mod-auth-admins')) {
			$this->PageViewSetErrorStatus(500, 'Mod AuthAdmins is missing !');
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


	public function Run() {

		//--
		if(SmartAuth::is_authenticated() !== true) {
			$this->PageViewSetCfg('error', 'OAuth2 Get.Token Requires Authentication !');
			return 403;
		} //end if
		//--

		//--
		if(SmartAuth::test_login_privilege('oauth2') !== true) { // PRIVILEGES
			$this->PageViewSetCfg('error', 'OAuth2 Get.Token requires the following privilege: `oauth2`');
			return 403;
		} //end if
		//--
		if(SmartAuth::test_login_restriction('readonly') === true) { // RESTRICTIONS
			$this->PageViewSetCfg('error', 'OAuth2 Get.Token is unavailable for the following restriction: `readonly` ...');
			return 403;
		} //end if
		// the `virtual` restriction is allowed to operate in this area
		//--

		//--
		if(SmartAuth::is_cluster_current_workspace() !== true) {
			$this->PageViewSetCfg('error', 'OAuth2 Get.Token is unavailable outside of your User Account Clustered WorkSpace ...');
			return 502;
		} //end if
		//--

		//--
		if(SmartEnvironment::isAdminArea() === true) {
			if(SmartEnvironment::isTaskArea() !== false) {
				$this->PageViewSetCfg('error', 'OAuth2 Get.Token cannot run under Admin Task Area !');
				return 502;
			} //end if
		} //end if
		//--

		//--
		$id = (string) trim((string)$this->RequestVarGet('id', '', 'string'));
		if($id == '') {
			$this->PageViewSetCfg('error', 'API ID is Empty');
			return 400;
		} elseif(strlen($id) > 127) {
			$this->PageViewSetCfg('error', 'API ID is Too Long');
			return 400;
		} //end if
		//--

		//--
		$format = (string) strtolower((string)trim((string)$this->RequestVarGet('format', 'json', 'string')));
		//--
		$answerJSON = true;
		$mimeType = 'application/json';
		if((string)$format == 'text') {
			$answerJSON = false;
			$mimeType = 'text/plain';
		} elseif((string)$format != 'json') {
			$this->PageViewSetCfg('error', 'Invalid Format');
			return 400;
		} //end if
		//--

		//--
		$data = \SmartModExtLib\Oauth2\Oauth2Api::getApiAccessToken((string)$id, (bool)$answerJSON); // do not cast to string, returns NULL if API does not exists
		if($data === null) {
			$this->PageViewSetCfg('error', 'API ID is Invalid');
			return 400;
		} elseif(!is_string($data)) {
			$this->PageViewSetCfg('error', 'API returned an Invalid Answer');
			return 500;
		} //end if
		//--

		//--
		$this->PageViewSetCfg('rawpage', true);
		$this->PageViewSetCfg('rawmime', (string)$mimeType);
		$this->PageViewSetCfg('rawdisp', 'inline');
		//--
		$this->PageViewSetVar('main', (string)$data);
		//--

	} //END FUNCTION

} //END CLASS


// end of php code
