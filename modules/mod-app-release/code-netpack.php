<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// Controller: AppRelease/CodeNetpack
// Route: ?/page/app-release.code-netpack (?page=app-release.code-netpack)
// (c) 2013-2021 unix-world.org - all rights reserved
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
 * @version 	v.20210527
 *
 */
final class SmartAppTaskController extends \SmartModExtLib\AppRelease\AbstractTaskController {

	protected $title = 'Package the Optimized Code';

	protected $sficon = '';
	protected $msg = '';
	protected $err = '';

	protected $goback = '';

	protected $working = true;
	protected $endscroll = true;

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
		if(!defined('NETPACK_MAX_MEMORY_SIZE')) {
			$this->err = 'A required constant is missing: NETPACK_MAX_MEMORY_SIZE';
			return;
		} //end if
		if((string)trim((string)NETPACK_MAX_MEMORY_SIZE) == '') {
			$this->err = 'Value is empty for: NETPACK_MAX_MEMORY_SIZE';
			return;
		} elseif(!preg_match('/^[a-zA-Z0-9]+$/', (string)NETPACK_MAX_MEMORY_SIZE)) {
			$this->err = 'Value set is invalid: NETPACK_MAX_MEMORY_SIZE='.(string)NETPACK_MAX_MEMORY_SIZE;
			return;
		} //end if
		//--
		ini_set('memory_limit', (string)NETPACK_MAX_MEMORY_SIZE);
		if((string)ini_get('memory_limit') !== (string)NETPACK_MAX_MEMORY_SIZE) {
			$this->err = 'Failed to set PHP.INI memory_limit as: '.(string)NETPACK_MAX_MEMORY_SIZE;
			return;
		} //end if
		//--

		//--
		if(!defined('TASK_APP_RELEASE_CODEPACK_APP_DIR')) {
			$this->err = 'A required constant is missing: TASK_APP_RELEASE_CODEPACK_APP_DIR';
			return;
		} //end if
		if(!SmartFileSysUtils::check_if_safe_path((string)TASK_APP_RELEASE_CODEPACK_APP_DIR)) {
			$this->err = 'The release app folder have an invalid path ...';
			return;
		} //end if
		//--
		if(!defined('TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR')) {
			$this->err = 'A required constant is missing: TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR';
			return;
		} //end if
		if(!SmartFileSysUtils::check_if_safe_path((string)TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR)) {
			$this->err = 'The optimizations folder have an invalid path ...';
			return;
		} //end if
		//--

		//--
		if(SmartFileSystem::is_type_file((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'package-errors.log')) {
			$archive = (array) (new SmartGetFileSystem(true))->get_storage((string)TASK_APP_RELEASE_CODEPACK_APP_DIR, false, false, '.z-netarch');
			if(Smart::array_size($archive['list-files']) > 0) {
				$archive = (string) $archive['list-files'][0];
			} else {
				$archive = 'UNKNOWN';
			} //end if
			$this->err = 'The release package appears that have been already done: `'.$archive.'` ... perhaps it was deleted manually and the package errors log was not !';
			return;
		} //end if
		//--
		if(!SmartFileSystem::is_type_file((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'optimization-errors.log')) {
			$this->err = 'The optimizations folder exists but optimizations may have not been completed ...';
			return;
		} //end if
		if((string)SmartFileSystem::read((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'optimization-errors.log') !== '#NULL') {
			$this->err = 'The optimizations error log is not clean: `optimization-errors.log`';
			return;
		} //end if
		//--

		//--
		$ok = false;
		$str_ofs = (int) strlen('/'.$appid.'/'); // mixed
		if((int)$str_ofs > 12) { // min app id len is 10 + 2 slashes
			if((string)substr((string)TASK_APP_RELEASE_CODEPACK_APP_DIR, -1 * (int)$str_ofs, (int)$str_ofs) == (string)'/'.$appid.'/') { // must end in it
				if(SmartFileSysUtils::check_if_safe_path((string)TASK_APP_RELEASE_CODEPACK_APP_DIR)) {
					$str_ofs = (int) strlen('/'.$appid.'/'.AppCodeUtils::APPCODEPACK_SUFFIX_OPTIMIZATIONS); // mixed
					if((int)$str_ofs > 12) { // min app id len is 10 + 2 slashes
						if((string)substr((string)TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR, -1 * (int)$str_ofs, (int)$str_ofs) == (string)'/'.$appid.'/'.AppCodeUtils::APPCODEPACK_SUFFIX_OPTIMIZATIONS) { // must end in it
							if(SmartFileSysUtils::check_if_safe_path((string)TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR)) {
								$ok = true;
							} //end if
						} //end if
					} //end if
				} //end if
			} //end if
		} //end if
		if($ok !== true) {
			$this->err = (string) 'INVALID Optimizations Folder !';
			return;
		} //end if
		if(!SmartFileSystem::is_type_dir((string)TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR)) {
			$this->err = (string) 'Optimizations Folder does not exists !';
			return;
		} //end if
		//--
		$comment = (string) trim((string)$this->RequestVarGet('comment', '', 'string'));
		//--
		$date_iso_arch = (string) date('Y-m-d H:i:s');
		$date_arch = (string) date('Ymd-His', strtotime($date_iso_arch));
		$name_arch = Smart::safe_filename('appcode-package_'.$date_arch.'.z-netarch');
		//--
		$arch = new AppNetPackager();
		$arch->start((string)$appid, (string)TASK_APP_RELEASE_CODEPACK_APP_DIR, (string)$name_arch, (string)$date_iso_arch, (string)$comment);
		$the_archname = (string) $arch->get_archive_file_name();
		$the_archpath = (string) $arch->get_archive_file_path();
		//--
		echo '<div class="operation_info">Creating the Release Package: `'.Smart::escape_html((string)$the_archname).'`<br>'.'NetArchive Max Memory Size: '.Smart::escape_html((string)(defined('NETPACK_MAX_MEMORY_SIZE') ? NETPACK_MAX_MEMORY_SIZE : 'N/A')).'</div>';
		echo '<div style="font-size:0.75rem!important">';
		$arch->pack_dir((string)TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR);
		echo '</div>';
		$this->err = (string) $arch->save();
		$this->err = (string) trim((string)$this->err);
		if((string)$this->err != '') {
			$this->err = 'NetArch ERRORS: '.$this->err;
			return; // pack errors
		} //end if
		//--

		//--
		SmartFileSysUtils::raise_error_if_unsafe_path((string)TASK_APP_RELEASE_CODEPACK_APP_DIR);
		SmartFileSystem::write((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'package-errors.log', (string)($this->err ? $this->err : '#NULL'));
		//--

		//--
		$this->sficon = 'box-add';
		$this->msg = 'Package archiving and check is SUCCESSFUL: `'.$the_archname.'`';
		//--

	} //END FUNCTION


} //END CLASS

// end of php code
