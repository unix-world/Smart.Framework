<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// Controller: AuthUsers/TestTplEmail
// Route: ?page=auth-users.test-tpl-email
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'INDEX'); // INDEX


final class SmartAppIndexController extends SmartAbstractAppController {

	public function Initialize() {

		//-- dissalow run this sample if not test mode enabled
		if(!defined('SMART_FRAMEWORK_TEST_MODE') OR (SMART_FRAMEWORK_TEST_MODE !== true)) {
			$this->PageViewSetErrorStatus(503, 'ERROR: Test mode is disabled ...');
			return false;
		} //end if
		//--

		//--
		if(!defined('SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS') OR (SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS !== true)) {
			$this->PageViewSetErrorStatus(503, 'Mod Auth Users is NOT Enabled ...');
			return false;
		} //end if
		//--

		return true;

	} //END FUNCTION


	public function Run() {

		//--
		$action = (string) $this->RequestVarGet('action', '', 'string');
		//--

		//--
		$message = '';
		//--
		switch((string)$action) {
			case 'register':
				//--
				$token = 'sample-token';
				$hash  = 'sample-hash';
				//--
				$message = (string) \SmartModExtLib\AuthUsers\AuthEmail::renderTplRegistration(
					(string) $token,
					(string) $hash,
					true
				);
				//--
				break;
			case 'recovery':
				//--
				$code = '(0123456789)#[AB3DEF78WZ]';
				//--
				$message = (string) \SmartModExtLib\AuthUsers\AuthEmail::renderTplRecovery(
					(string) $code
				);
				//--
				break;
			default:
				$message = 'Select the Action: `register` | `recovery`';
		} //end switch
		//--
		if((string)trim((string)$message) == '') {
			$this->PageViewSetErrorStatus(500, 'Email Message is Empty, see the error log ...');
			return;
		} //end if
		//--

		//--
		$this->PageViewSetCfg('rawpage', true);
		$this->PageViewSetCfg('rawmime', 'text/html');
		//--

		//--
		$this->PageViewSetVar('main', (string)$message);
		//--

	} //END FUNCTION

} //END CLASS

// end of php code
