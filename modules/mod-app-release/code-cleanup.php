<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// Controller: AppRelease/CodeCleanup
// Route: ?/page/app-release.code-cleanup (?page=app-release.code-cleanup)
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
 * @version 	v.20260120
 *
 */
final class SmartAppTaskController extends \SmartModExtLib\AppRelease\AbstractTaskController {

	protected $title = 'Cleanup: Delete Optimizations Folder, Package and Release files';

	protected $sficon = '';
	protected $msg = '';
	protected $err = '';

	protected $working = true;
	protected $modal = true;
	protected $selfclose = 0;

	public function Run() {

		//--
		sleep(5); // wait if the optimization was canceled and if there are still background processes running ...
		//--

		//--
		$ok = false;
		$appid = (string) $this->getAppId();
		if((string)$appid != '') {
			if(defined('TASK_APP_RELEASE_CODEPACK_APP_DIR')) {
				$str_ofs = (int) strlen('/'.$appid.'/'); // mixed
				if((int)$str_ofs >= 6) { // min app id len is 4 + 2 slashes ; {{{SYNC-APPCODEPACK-ID-SIZE}}}
					if((string)substr((string)TASK_APP_RELEASE_CODEPACK_APP_DIR, -1 * (int)$str_ofs, (int)$str_ofs) == (string)'/'.$appid.'/') { // must end in it
						if(SmartFileSysUtils::checkIfSafePath((string)TASK_APP_RELEASE_CODEPACK_APP_DIR)) {
							$ok = true;
						} //end if
					} //end if
				} //end if
			} //end if
		} //end if
		if($ok !== true) {
			$this->err = (string) 'INVALID Cleanup Folder !';
			return;
		} //end if
		//--
		if(SmartFileSystem::is_type_dir((string)TASK_APP_RELEASE_CODEPACK_APP_DIR)) {
			SmartFileSystem::dir_delete((string)TASK_APP_RELEASE_CODEPACK_APP_DIR);
		} //end if
		if(SmartFileSystem::is_type_dir((string)TASK_APP_RELEASE_CODEPACK_APP_DIR)) {
			$this->err = (string) 'Cleanup Folder FAILED !';
			return;
		} //end if
		//--

		//--
		$this->sficon = 'bin';
		$this->msg = 'Cleanup DONE';
		$this->selfclose = 3500;
		//--

	} //END FUNCTION


} //END CLASS

// end of php code
