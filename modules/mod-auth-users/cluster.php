<?php
// Controller: AuthUsers/Cluster
// Route: ?page=auth-users.cluster.json
// (c) 2025-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT S EXECUTION
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'TASK'); // TASK
define('SMART_APP_MODULE_AUTH', true);


final class SmartAppTaskController extends SmartAbstractAppController {

	// r.20250620

	// this is the auth users cluster account sync

	public function Initialize() {

		//--
		if(!defined('SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS') OR (SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS !== true)) {
			$this->PageViewSetErrorStatus(503, 'Mod Auth Users is NOT Enabled in Cluster ADM/TSK Area ...');
			return false;
		} //end if
		//--

		//--
		if(!SmartAppInfo::TestIfModuleExists('mod-auth-admins')) {
			$this->PageViewSetErrorStatus(500, 'Mod AuthAdmins is missing !');
			return false;
		} //end if
		//--

		//--
		if(SmartEnvironment::isTaskArea() !== true) {
			Smart::log_warning(__METHOD__.' # ERR: Controller must run under Admin Task area');
			$this->PageViewSetErrorStatus(502, 'ERROR: This Controller must run inside Admin Task Area');
			return false;
		} //end if
		//--

		//--
		if(SmartAuth::is_cluster_master_auth() !== false) {
			Smart::log_warning(__METHOD__.' # ERR: This is the Auth Cluster Master Server, cannot handle this controller');
			$this->PageViewSetErrorStatus(502, 'ERROR: This Controller can run just on a Non-Master Auth Server from the Auth Cluster');
			return false;
		} //end if
		//--

	} //END FUNCTION


	public function Run() { // (OUTPUTS: HTML/JSON)

		//--
		$action 	= (string) trim((string)$this->RequestVarGet('action', '', 'string'));
		$data 		= (string) trim((string)$this->RequestVarGet('data', '', 'string'));
		$checksum 	= (string) trim((string)$this->RequestVarGet('checksum', '', 'string'));
		//--

		//--
		$status  = 400;
		$message = 'Bad Request';
		//--

		//--
		switch((string)strtolower((string)$action)) {
			//--
			case 'auth-users:cluster:set:account.json':
				//--
				$status = (int) $this->authUsersClusterSetAccountJson((string)$action, (string)$data, (string)$checksum);
				if((int)$status == 200) {
					$message = 'OK';
				} else {
					$message = 'Operation Failed';
				} //end if else
				//--
				break;
			//--
			default:
				// N/A
			//--
		} //end switch
		//--

		//--
		$this->PageViewSetCfg('rawpage', true);
		$this->PageViewSetCfg('rawmime', 'application/json');
		//--
		$data = [
			'status' 	=> (int)    $status,
			'message' 	=> (string) $message,
		];
		//--
		$this->PageViewSetVar('main', (string)Smart::json_encode((array)$data, false, true, false));
		//--
		return 201;
		//--

	} //END FUNCTION


	private function authUsersClusterSetAccountJson(string $action, string $data, string $checksum) : int {
		//--
		if((!defined('SMART_FRAMEWORK_SECURITY_KEY')) OR ((string)trim((string)SMART_FRAMEWORK_SECURITY_KEY) == '')) {
			Smart::log_warning(__METHOD__.' # SMART_FRAMEWORK_SECURITY_KEY is not defined or empty !');
			return 750;
		} //end if
		//--
		$cluster = (string) trim((string)SmartAuth::get_cluster_id());
		if((string)$cluster == '') {
			Smart::log_warning(__METHOD__.' # Invalid Request, this Auth Cluster Server (not master) have an Empty Cluster ID');
			return 752;
		} //end if
		if(SmartAuth::validate_cluster_id((string)$cluster) !== true) { // {{{SYNC-AUTH-USERS-SAFE-VALIDATE-CLUSTER}}}
			Smart::log_warning(__METHOD__.' # Invalid Request, this Auth Cluster Server (not master) have an Invalid Cluster ID: `'.$cluster.'`');
			return 753;
		} //end if
		//--
		if((string)strtolower((string)$action) != 'auth-users:cluster:set:account.json') {
			Smart::log_warning(__METHOD__.' # Action is Invalid: `'.$action.'`');
			return 754;
		} //end if
		//--
		if((string)trim((string)$data) == '') {
			Smart::log_warning(__METHOD__.' # Data is Empty');
			return 755;
		} //end if
		//--
		if((string)trim((string)$checksum) == '') {
			Smart::log_warning(__METHOD__.' # Data Checksum is Empty');
			return 756;
		} //end if
		//--
		if((string)$checksum != (string)SmartHashCrypto::checksum((string)$action."\r".$data)) {
			Smart::log_warning(__METHOD__.' # Data Checksum Failed');
			return 757;
		} //end if
		//--
		$data = (string) trim((string)SmartCipherCrypto::t3f_decrypt((string)$data, 'AuthUsers:Cluster:'."\r".SMART_FRAMEWORK_SECURITY_KEY, true)); // {{{SYNC-USER-AUTH-CLUSTER-CRYPTO-EXCHANGE-KEY}}}
		if((string)$data == '') {
			Smart::log_warning(__METHOD__.' # Data Decryption Failed');
			return 760;
		} //end if
		//--
		$data = Smart::json_decode((string)$data, true, 2);
		if((int)Smart::array_size($data) <= 0) {
			Smart::log_warning(__METHOD__.' # Data Decoding Failed');
			return 761;
		} //end if
		//--
		$data['cluster'] = (string) trim((string)($data['cluster'] ?? null));
		if((string)$data['cluster'] == '') {
			Smart::log_warning(__METHOD__.' # Account Data Cluster is Empty');
			return 770;
		} //end if
		if(SmartAuth::validate_cluster_id((string)$data['cluster']) !== true) { // {{{SYNC-AUTH-USERS-SAFE-VALIDATE-CLUSTER}}}
			Smart::log_warning(__METHOD__.' # Account Data Cluster is Invalid');
			return 771;
		} //end if
		if((string)$data['cluster'] != (string)$cluster) {
			Smart::log_warning(__METHOD__.' # Account Data Cluster is Wrong');
			return 772;
		} //end if
		//--
		$email = (string) trim((string)($data['email'] ?? null));
		if( // {{{SYNC-AUTH-USERS-EMAIL-AS-USERNAME-SAFE-VALIDATION}}}
			((string)$email == '')
			OR
			((int)strlen((string)$email) < 5)
			OR
			((int)strlen((string)$email) > 72)
			OR
			(strpos((string)$email, '@') == false)
			OR
			(SmartAuth::validate_auth_ext_username((string)$email) !== true)
		) {
			Smart::log_warning(__METHOD__.' # Email is Empty or Invalid: `'.$email.'`');
			return 773;
		} //end if
		//--
		$data['id'] = (string) trim((string)($data['id'] ?? null));
		if((string)$data['id'] == '') {
			Smart::log_warning(__METHOD__.' # Account User ID is Empty for `'.$email.'`');
			return 774;
		} //end if
		$id = (string) trim((string)\SmartModExtLib\AuthUsers\Utils::userAccountIdToUserName((string)$data['id']));
		if( // {{{SYNC-AUTH-USERS-SAFE-VALIDATE-ID}}}
			((string)$id == '')
			OR
			((int)strlen((string)$id) != 21)
			OR
			(strpos((string)$id, '.') === false)
			OR
			(SmartAuth::validate_auth_username((string)$id, false) !== true)
		) {
			Smart::log_warning(__METHOD__.' # Account ID is Empty or Invalid: `'.$id.'` for `'.$email.'`');
			return 775;
		} //end if
		//--
		if(\SmartModExtLib\AuthUsers\AuthClusterUser::updateAccountWorkspaceAuthData((array)$data) !== true) {
			Smart::log_warning(__METHOD__.' # Account Workspace Setup Failed for: `'.$id.'` / `'.$email.'`');
			return 776;
		} //end if
		//--
		return 200; // OK
		//--
	} //END FUNCTION


} //END CLASS


//end of php code
