<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// Controller: AppRelease/ReleaseInfo
// Route: ?/page/app-release.release-info (?page=app-release.release-info)
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

// SMART_APP_MODULE_DIRECT_OUTPUT :: TRUE :: # by parent class

define('SMART_APP_MODULE_AREA', 'TASK');
define('SMART_APP_MODULE_AUTH', true);
define('SMART_APP_MODULE_AUTOLOAD', true);


/**
 * Task Controller: Custom Task
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20250207
 *
 */
final class SmartAppTaskController extends \SmartModExtLib\AppRelease\AbstractTaskController {

	protected $title = 'Release Info';

	protected $err = '';
	protected $notice = '';

	protected $goback = '';

	protected $details = true;

	protected $working = false;


	public function Run() {

		//--
		$appid = (string) $this->getAppId();
		if((string)$appid == '') {
			$this->err = 'App ID is Empty';
			return;
		} //end if
		//--

		//--
		$this->goback = (string) $this->ControllerGetParam('url-script').'?page='.$this->ControllerGetParam('module').'.app-manage&appid='.Smart::escape_url((string)$appid);
		//--

		//--
		if(!defined('APP_DEPLOY_HASH')) {
			$this->err = 'A required constant is missing: APP_DEPLOY_HASH';
			return;
		} //end if
		if((string)trim((string)APP_DEPLOY_HASH) == '') {
			$this->err = 'A required constant: APP_DEPLOY_HASH is empty';
			return;
		} //end if
		//--
		if(!defined('APP_DEPLOY_URLS')) {
			$this->err = 'A required constant is missing: APP_DEPLOY_URLS';
			return;
		} //end if
		if((string)trim((string)APP_DEPLOY_URLS) == '') {
			$this->err = 'A required constant: APP_DEPLOY_URLS is empty';
			return;
		} //end if
		//--
		if(!defined('APP_DEPLOY_AUTH_USERNAME')) {
			$this->err = 'A required constant is missing: APP_DEPLOY_AUTH_USERNAME';
			return;
		} //end if
		if((string)trim((string)APP_DEPLOY_AUTH_USERNAME) == '') {
			$this->err = 'A required constant: APP_DEPLOY_AUTH_USERNAME is empty';
			return;
		} //end if
		//--
		if(!defined('APP_DEPLOY_AUTH_PASSWORD')) {
			$this->err = 'A required constant is missing: APP_DEPLOY_AUTH_PASSWORD';
			return;
		} //end if
		if((string)trim((string)APP_DEPLOY_AUTH_PASSWORD) == '') {
			$this->err = 'A required constant: APP_DEPLOY_AUTH_PASSWORD is empty';
			return;
		} //end if
		//--

		//--
		if(!defined('TASK_APP_RELEASE_CODEPACK_APP_DIR')) {
			$this->err = 'A required constant is missing: TASK_APP_RELEASE_CODEPACK_APP_DIR';
			return;
		} //end if
		if(!SmartFileSysUtils::checkIfSafePath((string)TASK_APP_RELEASE_CODEPACK_APP_DIR)) {
			$this->err = 'The release app folder have an invalid path ...';
			return;
		} //end if
		//--
		if(!defined('TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR')) {
			$this->err = 'A required constant is missing: TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR';
			return;
		} //end if
		if(!SmartFileSysUtils::checkIfSafePath((string)TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR)) {
			$this->err = 'The optimizations folder have an invalid path ...';
			return;
		} //end if
		//--

		//--
		if(SmartFileSystem::is_type_file((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'package-errors.log')) {
			if((string)SmartFileSystem::read((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'package-errors.log') !== '#NULL') {
				$this->err = 'The release package error log is not clean: `package-errors.log`';
				return;
			} //end if
		} //end if
		//--

		//--
		if(!defined('APP_DEPLOY_FOLDERS')) {
			$this->err = 'A required constant is missing: APP_DEPLOY_FOLDERS';
			return;
		} //end if
		if(!defined('APP_DEPLOY_FILES')) {
			$this->err = 'A required constant is missing: APP_DEPLOY_FILES';
			return;
		} //end if
		//--
		if(!defined('TASK_APP_RELEASE_CODEPACK_MODE')) {
			$this->err = 'A required constant is missing: TASK_APP_RELEASE_CODEPACK_MODE';
			return;
		} //end if
		//--

		//--
		$optimizations_exists = (bool) SmartFileSystem::path_exists((string)TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR);
		$optimizations_completed = (bool) ($optimizations_exists && SmartFileSystem::is_type_file((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'optimization-errors.log'));
		//--

		//--
		$last_package = '';
		$packsize = 0;
		$packsh3a512b64  = '';
		if(SmartFileSystem::is_type_file((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'package-errors.log')) {
			$archives = (array) (new SmartGetFileSystem(true))->get_storage((string)TASK_APP_RELEASE_CODEPACK_APP_DIR, false, false, '.z-netarch');
			if(Smart::array_size($archives['list-files']) > 0) {
				$last_package = (string) $archives['list-files'][0];
				if((string)$last_package != '') {
					$tmp_content = (string) SmartFileSystem::read((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.$last_package);
					$packsize = (int) strlen((string)$tmp_content);
					$packsh3a512b64 = (string) SmartHashCrypto::sh3a512((string)$tmp_content, true);
					$tmp_content = '';
				} //end if
			} //end if
			$archives = null;
		} //end if
		//--
		$deploys = [];
		if((string)$last_package != '') {
			$jsons = (array) (new SmartGetFileSystem(true))->get_storage((string)TASK_APP_RELEASE_CODEPACK_APP_DIR, false, false, '.json');
			if(Smart::array_size($jsons['list-files']) > 0) {
				for($i=0; $i<Smart::array_size($jsons['list-files']); $i++) {
					if(((string)substr((string)$jsons['list-files'][$i], 0, 7) == 'deploy-') AND ((string)substr((string)$jsons['list-files'][$i], -5, 5) == '.json')) {
						$tmp_deploy = (string) SmartFileSystem::read((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.(string)$jsons['list-files'][$i]);
						$tmp_deploy = Smart::json_decode((string)$tmp_deploy);
						if(Smart::array_size($tmp_deploy) > 0) {
							if(
								isset($tmp_deploy['deploy-url']) AND Smart::is_nscalar($tmp_deploy['deploy-url']) AND
								isset($tmp_deploy['date-time']) AND Smart::is_nscalar($tmp_deploy['date-time']) AND
								isset($tmp_deploy['app-id']) AND Smart::is_nscalar($tmp_deploy['app-id']) AND
								isset($tmp_deploy['package']) AND Smart::is_nscalar($tmp_deploy['package']) AND
								isset($tmp_deploy['fsize']) AND Smart::is_nscalar($tmp_deploy['fsize']) AND
								isset($tmp_deploy['checksum']) AND Smart::is_nscalar($tmp_deploy['checksum']) AND
								isset($tmp_deploy['signature']) AND Smart::is_nscalar($tmp_deploy['signature'])
							) {
								if(
									((string)$last_package == (string)$tmp_deploy['package']) AND
									((string)$appid == (string)$tmp_deploy['app-id']) AND
									((int)$tmp_deploy['fsize'] > 0) AND
									((int)$packsize == (int)$tmp_deploy['fsize']) AND
									((int)strlen((string)$tmp_deploy['checksum']) >= (int)88) AND // sh3a512 hex/b64
									((string)$packsh3a512b64 == (string)$tmp_deploy['checksum']) AND
									((int)strlen((string)$tmp_deploy['signature']) >= (int)40) AND // sh3a224 hex/b64
									((string)$tmp_deploy['signature'] == (string)sha1((string)'#'.$appid.'#'.APP_DEPLOY_HASH.'#'.$last_package.'#'.$tmp_deploy['deploy-url'].'#')) // {{{SYNC-APP-DEPLOY-SIGNATURE}}}
								) {
									$deploys[(string)$tmp_deploy['deploy-url']] = (string) $tmp_deploy['date-time'];
								} //end if
							} //end if
						} //end if
					} //end if
				} //end for
			} //end if
			$jsons = null;
		} //end if
		//--

		//--
		$notice = [];
		$notice[] = 'AppID: '.$appid;
		$notice[] = 'Optimizations Strategy: '.TASK_APP_RELEASE_CODEPACK_MODE;
		$notice[] = 'Optimizations Exist: '.$this->convertBoolToYesNo((bool)$optimizations_exists);
		$notice[] = 'Optimizations Completed: '.$this->convertBoolToYesNo((bool)$optimizations_completed);
		$notice[] = 'Optimizations Max Run Timeout: '.(defined('OPTIMIZATIONS_MAX_RUN_TIMEOUT') ? ((int)OPTIMIZATIONS_MAX_RUN_TIMEOUT).' seconds' : 'N/A');
		$notice[] = 'NetArchive Max Memory Size: '.(defined('NETPACK_MAX_MEMORY_SIZE') ? NETPACK_MAX_MEMORY_SIZE : 'N/A');
		$notice[] = 'Release Package: '.($last_package ? $last_package : '-');
		if((string)$last_package != '') {
			$notice[] = 'Release Package Size (bytes): '.$packsize;
			$notice[] = 'Release Package Checksum (sha3-512-b64): '.$packsh3a512b64;
			if(SmartFileSystem::is_type_file((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'appcodeunpack.php')) {
				$notice[] = 'Update Release Manager: Yes';
			} //end if
			$notice[] = 'Successful Deployments:';
			if(Smart::array_size($deploys) > 0) {
				foreach((array)$deploys as $key => $val) {
					$notice[] = ' * '.$key.' :: '.$val;
				} //end foreach
			} else {
				$notice[] = 'NONE';
			} //end if
		} //end if
		//--
		$this->notice = implode("\n", (array)$notice);
		$notice = null;
		//--

	} //END FUNCTION


	private function convertBoolToYesNo(bool $val) {
		//--
		return (string) (($val === true) ? 'Yes' : 'No');
		//--
	} //END FUNCTION


} //END CLASS

// end of php code
