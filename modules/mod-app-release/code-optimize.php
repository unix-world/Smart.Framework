<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// Controller: AppRelease/CodeOptimize
// Route: ?/page/app-release.code-optimize (?page=app-release.code-optimize)
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

	protected $title = 'Optimize the source Code';

	protected $sficon = '';
	protected $msg = '';
	protected $err = '';

	protected $goback = '';

	protected $details = true;

	protected $working = true;
	protected $workstop = true;
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
		if(!defined('OPTIMIZATIONS_MAX_RUN_TIMEOUT')) {
			$this->err = 'A required constant is missing: OPTIMIZATIONS_MAX_RUN_TIMEOUT';
			return;
		} //end if
		if((int)OPTIMIZATIONS_MAX_RUN_TIMEOUT < (int)SMART_FRAMEWORK_EXECUTION_TIMEOUT) {
			$this->err = 'Value is set too low for: OPTIMIZATIONS_MAX_RUN_TIMEOUT='.(int)OPTIMIZATIONS_MAX_RUN_TIMEOUT;
			return;
		} elseif((int)OPTIMIZATIONS_MAX_RUN_TIMEOUT > 86400) {
			$this->err = 'Value is set too low high: OPTIMIZATIONS_MAX_RUN_TIMEOUT='.(int)OPTIMIZATIONS_MAX_RUN_TIMEOUT;
			return;
		} //end if
		//--
		ini_set('max_execution_time', (int)OPTIMIZATIONS_MAX_RUN_TIMEOUT);
		if((int)ini_get('max_execution_time') !== (int)OPTIMIZATIONS_MAX_RUN_TIMEOUT) {
			$this->err = 'Failed to set PHP.INI max_execution_time as: '.(int)OPTIMIZATIONS_MAX_RUN_TIMEOUT;
			return;
		} //end if
		//--
		ini_set('ignore_user_abort', '0');
		if((string)ini_get('ignore_user_abort') !== (string)'0') {
			$this->err = 'Failed to set PHP.INI ignore_user_abort to: 0';
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
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR);
		if(SmartFileSystem::path_exists((string)TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR)) {
			$this->err = 'The optimizations folder already exists, run Cleanup before running Optimize !';
			return;
		} //end if
		SmartFileSystem::dir_create((string)TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR, true);
		if(!SmartFileSystem::is_type_dir((string)TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR)) {
			$this->err = 'The optimizations folder could not be created. Check the FileSystem permissions on tmp/ folder !';
			return;
		} //end if
		//--

		//--
		$this->EchoHtmlMessage('<div class="operation_info">Optimizing the source code: `'.Smart::escape_html((string)TASK_APP_RELEASE_CODEPACK_MODE).'`<br>'.'Optimizations Max Run Timeout: '.Smart::escape_html((string)(defined('OPTIMIZATIONS_MAX_RUN_TIMEOUT') ? ((int)OPTIMIZATIONS_MAX_RUN_TIMEOUT).' seconds' : 'N/A')).'</div>');
		//--

		//--
		$arr_folders = Smart::json_decode((string)APP_DEPLOY_FOLDERS); // mixed
		if(!is_array($arr_folders)) {
			$arr_folders = array();
		} //end if
		$arr_folders = (array) $this->getFilesOrFoldersProcessArr((array)$arr_folders);
		if($this->err) {
			return;
		} //end if
		//--
		$arr_files = Smart::json_decode((string)APP_DEPLOY_FILES); // mixed
		if(!is_array($arr_files)) {
			$arr_files = array();
		} //end if
		$arr_files = (array) $this->getFilesOrFoldersProcessArr((array)$arr_files);
		if($this->err) {
			return;
		} //end if
		//--

		//--
		$processed = 0;
		//--
		if($this->err) {
			return;
		} //end if
		if(Smart::array_size($arr_folders) > 0) {
			foreach($arr_folders as $key => $val) {
				if(!$this->err) {
					if(
						(stripos($val, '!skip') === false) AND
						(
							(stripos($val, '=') === false) OR
							(
								(stripos($val, '=') !== false) AND // the rename dir can be a file in an already optimized dir so skip if exists, will be renamed later
								(!SmartFileSystem::is_type_dir((string)TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR.$key))
							)
						)
					) {
						$processed++;
						$this->optimizeDir((string)$key);
					} //end if
				} else {
					break;
				} //end if
			} //end foreach
		} //end if
		//--
		if($this->err) {
			return;
		} //end if
		if(Smart::array_size($arr_files) > 0) {
			foreach($arr_files as $key => $val) {
				if(!$this->err) {
					if(
						(stripos($val, '!skip') === false) AND
						(
							(stripos($val, '=') === false) OR
							(
								(stripos($val, '=') !== false) AND // the rename file can be a file in an already optimized dir so skip if exists, will be renamed later
								(!SmartFileSystem::is_type_file((string)TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR.$key))
							)
						)
					) {
						$processed++;
						$this->optimizeFile((string)$key);
					} //end if
				} else {
					break;
				} //end if
			} //end foreach
		} //end if
		//--
		if($this->err) {
			return;
		} //end if
		if((int)$processed <= 0) {
			$this->err = 'No Files or Folders Optimized';
			return;
		} //end if
		//--

		//--
		$post_optimizations_rename = [];
		//--
		if($this->err) {
			return;
		} //end if
		if(Smart::array_size($arr_files) > 0) {
			foreach($arr_files as $key => $val) {
				if(!$this->err) {
					if(stripos($val, '=') !== false) {
						$tmp_arr = (array) explode('=', (string)$val);
						if(Smart::array_size($tmp_arr) != 2) {
							$this->err = 'Invalid File Rename (1): `'.$key.'` ; `'.$val.'`';
							break;
						} else {
							$tmp_nkey = (string) trim((string)$tmp_arr[1]);
							$tmp_arr = null;
							if(((string)$tmp_nkey == '') OR (!SmartFileSysUtils::checkIfSafePath((string)$tmp_nkey))) {
								$this->err = 'Invalid File Rename (2): `'.$key.'` ; `'.$val.'`';
								break;
							} else {
								$tmp_fpath = (string) TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR.$key;
								SmartFileSysUtils::raiseErrorIfUnsafePath((string)$tmp_fpath);
								if(!SmartFileSystem::is_type_file((string)$tmp_fpath)) {
									$this->err = 'Cannot Rename Inexistent File: `'.$key.'` ; `'.$val.'`';
									break;
								} //end if
								$tmp_nfpath = (string) TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR.$tmp_nkey;
								SmartFileSysUtils::raiseErrorIfUnsafePath((string)$tmp_nfpath);
								if(SmartFileSystem::path_exists((string)$tmp_nfpath)) {
									$this->err = 'Overwrite is not allowed, Cannot Rename File: `'.$key.'` ; `'.$val.'`';
									break;
								} //end if
								if(!SmartFileSystem::rename((string)$tmp_fpath, (string)$tmp_nfpath)) {
									$this->err = 'Cannot Rename File: `'.$key.'` ; `'.$val.'`';
									break;
								} //end if
								if(SmartFileSystem::path_exists((string)$tmp_fpath)) {
									$this->err = 'Failed to Rename File (1): `'.$key.'` ; `'.$val.'`';
									break;
								} //end if
								if(!SmartFileSystem::is_type_file((string)$tmp_nfpath)) {
									$this->err = 'Failed to Rename File (2): `'.$key.'` ; `'.$val.'`';
									break;
								} //end if
								$post_optimizations_rename[] = 'Renaming File: `'.$key.'` as `'.$tmp_nkey.'`';
							} //end if
						} //end if else
						$tmp_arr = null;
					} //end if
				} else {
					break;
				} //end if
			} //end foreach
		} //end if
		//--
		if($this->err) {
			return;
		} //end if
		if(Smart::array_size($arr_folders) > 0) {
			foreach($arr_folders as $key => $val) {
				if(!$this->err) {
					if(stripos($val, '=') !== false) {
						$tmp_arr = (array) explode('=', (string)$val);
						if(Smart::array_size($tmp_arr) != 2) {
							$this->err = 'Invalid Folder Rename (1): `'.$key.'` ; `'.$val.'`';
							break;
						} else {
							$tmp_nkey = (string) trim((string)$tmp_arr[1]);
							$tmp_arr = null;
							if(((string)$tmp_nkey == '') OR (!SmartFileSysUtils::checkIfSafePath((string)$tmp_nkey))) {
								$this->err = 'Invalid Folder Rename (2): `'.$key.'` ; `'.$val.'`';
								break;
							} else {
								$tmp_fpath = (string) TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR.$key;
								SmartFileSysUtils::raiseErrorIfUnsafePath((string)$tmp_fpath);
								if(!SmartFileSystem::is_type_dir((string)$tmp_fpath)) {
									$this->err = 'Cannot Rename Inexistent Folder: `'.$key.'` ; `'.$val.'`';
									break;
								} //end if
								$tmp_nfpath = (string) TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR.$tmp_nkey;
								SmartFileSysUtils::raiseErrorIfUnsafePath((string)$tmp_nfpath);
								if(SmartFileSystem::path_exists((string)$tmp_nfpath)) {
									$this->err = 'Overwrite is not allowed, Cannot Rename Folder: `'.$key.'` ; `'.$val.'`';
									break;
								} //end if
								if(!SmartFileSystem::dir_rename((string)$tmp_fpath, (string)$tmp_nfpath)) {
									$this->err = 'Cannot Rename Folder: `'.$key.'` ; `'.$val.'`';
									break;
								} //end if
								if(SmartFileSystem::path_exists((string)$tmp_fpath)) {
									$this->err = 'Failed to Rename Folder (1): `'.$key.'` ; `'.$val.'`';
									break;
								} //end if
								if(!SmartFileSystem::is_type_dir((string)$tmp_nfpath)) {
									$this->err = 'Failed to Rename Folder (2): `'.$key.'` ; `'.$val.'`';
									break;
								} //end if
								$post_optimizations_rename[] = 'Renaming Folder: `'.$key.'` as `'.$tmp_nkey.'`';
							} //end if
						} //end if else
						$tmp_arr = null;
					} //end if
				} else {
					break;
				} //end if
			} //end foreach
		} //end if
		//--
		$this->EchoHtmlMessage((string)SmartMarkersTemplating::render_file_template(
			$this->ControllerGetParam('module-view-path').'partials/app-optimize-result.inc.htm',
			[
				'TYPE' 		=> 'POST-OPTIMIZATIONS',
				'NAME' 		=> (string) 'RENAME',
				'DETAILS' 	=> (string) implode("\n", $post_optimizations_rename),
			],
			'yes' // cache
		));
		//--

		//--
		$post_optimizations_skip = [];
		//--
		if($this->err) {
			return;
		} //end if
		if(Smart::array_size($arr_files) > 0) {
			foreach($arr_files as $key => $val) {
				if(!$this->err) {
					if(stripos($val, '!skip') !== false) {
						$tmp_fpath = (string) TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR.$key;
						SmartFileSysUtils::raiseErrorIfUnsafePath((string)$tmp_fpath);
						if(SmartFileSystem::is_type_file((string)$tmp_fpath)) {
							if(!SmartFileSystem::delete((string)$tmp_fpath)) {
								$this->err = 'Cannot Delete File: `'.$key.'`';
								break;
							} //end if
							if(SmartFileSystem::path_exists((string)$tmp_fpath)) {
								$this->err = 'Failed to Delete File: `'.$key.'` :: Path Still Exists';
								break;
							} //end if
						} //end if
						$post_optimizations_skip[] = 'Removing Skip File: `'.$key.'`';
					} //end if
				} else {
					break;
				} //end if
			} //end foreach
		} //end if
		//--
		if($this->err) {
			return;
		} //end if
		if(Smart::array_size($arr_folders) > 0) {
			foreach($arr_folders as $key => $val) {
				if(!$this->err) {
					if(stripos($val, '!skip') !== false) {
						$tmp_fpath = (string) TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR.$key;
						SmartFileSysUtils::raiseErrorIfUnsafePath((string)$tmp_fpath);
						if(SmartFileSystem::is_type_dir((string)$tmp_fpath)) {
							if(!SmartFileSystem::dir_delete((string)$tmp_fpath)) {
								$this->err = 'Cannot Delete Folder: `'.$key.'`';
								break;
							} //end if
							if(SmartFileSystem::path_exists((string)$tmp_fpath)) {
								$this->err = 'Failed to Delete Folder: `'.$key.'` :: Path Still Exists';
								break;
							} //end if
						} //end if
						$post_optimizations_skip[] = 'Removing Skip Folder: `'.$key.'`';
					} //end if
				} else {
					break;
				} //end if
			} //end foreach
		} //end if
		//--
		$this->EchoHtmlMessage((string)SmartMarkersTemplating::render_file_template(
			$this->ControllerGetParam('module-view-path').'partials/app-optimize-result.inc.htm',
			[
				'TYPE' 		=> 'POST-OPTIMIZATIONS',
				'NAME' 		=> (string) 'SKIP',
				'DETAILS' 	=> (string) implode("\n", $post_optimizations_skip),
			],
			'yes' // cache
		));
		//--

		//--
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)TASK_APP_RELEASE_CODEPACK_APP_DIR);
		SmartFileSystem::write((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'optimization-errors.log', (string)($this->err ? $this->err : '#NULL'));
		//--

		//--
		$this->sficon = 'shrink';
		$this->msg = 'Optimizations DONE: ['.TASK_APP_RELEASE_CODEPACK_MODE.']';
		//--

	} //END FUNCTION


	private function getFilesOrFoldersProcessArr(array $arr) {
		//--
		if(!is_array($arr)) {
			return [];
		} //end if
		if(Smart::array_size($arr) <= 0) {
			return [];
		} //end if
		if(Smart::array_type_test($arr) != 1) { // must be non-associative
			return [];
		} //end if
		//--
		$out_arr = [];
		for($i=0; $i<Smart::array_size($arr); $i++) {
			if(Smart::is_nscalar($arr[$i])) {
				$key = (string) trim((string)$arr[$i]);
				if((string)$key != '') {
					$tmp_arr = (array) explode(';', (string)$key);
					if(Smart::array_size($tmp_arr) > 2) { // can be 1 or 2 only !
						$this->err = 'Invalid Entry Name: `'.$key.'`';
						return [];
						break;
					} //end if
					$val = null;
					if(isset($tmp_arr[1])) {
						$val = (string) trim((string)$tmp_arr[1]);
					} //end if
					$key = (string) trim((string)$tmp_arr[0]);
					$tmp_arr = null;
					if(SmartFileSysUtils::checkIfSafePath((string)$key)) {
						$out_arr[(string)$key] = (string) $val;
					} //end if
				} //end if
			} //end if
		} //end for
		//--
		return (array) $out_arr;
		//--
	} //END FUNCTION


	private function optimizeDir(string $dir) {
		//--
		if($this->err) {
			return;
		} //end if
		//--
		if((string)trim((string)$dir) == '') {
			$this->err = __METHOD__.' # Empty folder name';
			return;
		} //end if
		//--
		if(!SmartFileSysUtils::checkIfSafePath((string)$dir)) {
			$this->err = __METHOD__.' # Invalid folder name: `'.$dir.'`';
			return;
		} //end if
		//--
		$appcodeoptimizer = new AppCodeOptimizer();
		$appcodeoptimizer->optimize_code_dir((string)$dir);
		//--
		$this->err = (string) $appcodeoptimizer->get_errors();
		if($this->err) {
			return;
		} //end if
		//--
		$this->EchoHtmlMessage((string)SmartMarkersTemplating::render_file_template(
			$this->ControllerGetParam('module-view-path').'partials/app-optimize-result.inc.htm',
			[
				'TYPE' 		=> 'DIR',
				'NAME' 		=> (string) $dir,
				'DETAILS' 	=> (string) $appcodeoptimizer->get_log(),
			],
			'yes' // cache
		));
		//--
	} //END FUNCTION


	private function optimizeFile(string $file) {
		//--
		if($this->err) {
			return;
		} //end if
		//--
		if((string)trim((string)$file) == '') {
			$this->err = __METHOD__.' # Empty file name';
			return;
		} //end if
		//--
		if(!SmartFileSysUtils::checkIfSafePath((string)$file)) {
			$this->err = __METHOD__.' # Invalid file name: `'.$file.'`';
			return;
		} //end if
		//--
		$appcodeoptimizer = new AppCodeOptimizer();
		$appcodeoptimizer->optimize_code_file((string)$file);
		//--
		$this->err = (string) $appcodeoptimizer->get_errors();
		if($this->err) {
			return;
		} //end if
		//--
		$this->EchoHtmlMessage((string)SmartMarkersTemplating::render_file_template(
			$this->ControllerGetParam('module-view-path').'partials/app-optimize-result.inc.htm',
			[
				'TYPE' 		=> 'FILE',
				'NAME' 		=> (string) $file,
				'DETAILS' 	=> (string) $appcodeoptimizer->get_log(),
			],
			'yes' // cache
		));
		//--
	} //END FUNCTION


} //END CLASS

// end of php code
