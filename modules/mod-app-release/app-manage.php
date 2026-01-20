<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// Controller: AppRelease/AppManage
// Route: ?/page/app-release.app-manage (?page=app-release.app-manage)
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'TASK');
define('SMART_APP_MODULE_AUTH', true);
define('SMART_APP_MODULE_AUTOLOAD', true);


/**
 * Task Controller: Task Manager
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20260120
 *
 */
final class SmartAppTaskController extends SmartAbstractAppController {


	public function Initialize() {
		//--
		if(!SmartAppInfo::TestIfModuleExists('mod-auth-admins')) {
			$this->PageViewSetErrorStatus(500, 'Mod AuthAdmins is missing !');
			return false;
		} //end if
		//--
		$this->PageViewSetCfg('template-path', 'modules/mod-auth-admins/templates/');
		$this->PageViewSetCfg('template-file', 'template-simple.htm');
		//--
		return true;
		//--
	} //END FUNCTION


	public function Run() {

		//--
		$name_prefix = 'AppRelease';
		$name_suffix = 'CodePack';
		$name_all = (string) $name_prefix.'.'.$name_suffix;
		//--

		//--
		$semaphores = [];
		$semaphores[] = 'load:ui-toolkit';
		$semaphores[] = 'load:base-js';
	//	$semaphores[] = 'load:utils-js';
		$semaphores[] = 'skip:unveil-js';
		//--

		//--
		$arr_bw = (array) SmartComponents::get_imgdesc_by_bw_id((string)SmartUtils::get_os_browser_ip('bw'));
		//--

		//--
		$appid = (string) trim((string)$this->RequestVarGet('appid', '', 'string'));
		//--
		$ini_settings = AppCodeUtils::parseIniSettings(); // mixed
		if(!is_int($ini_settings) OR ((int)$ini_settings !== 3)) { // there are 3 mandatory settings: MAX RUN TIMEOUT and PHP executable {{{SYNC-CHECK-APP-INI-SETTINGS}}}
			$this->PageViewSetErrorStatus(503, 'INI SETTINGS PARSE ERROR: Num Req. is: #'.$ini_settings);
			return;
		} //end if
		//--
		$arr_utils_metainfo = (array) AppCodeUtils::getArrIniMetaInfo(); // requires AppCodeUtils::parseIniSettings() !!
		if((string)$appid != '') {
			if(defined('TASK_APP_RELEASE_CODEPACK_MODE') AND ((string)TASK_APP_RELEASE_CODEPACK_MODE == 'minify')) {
				foreach($arr_utils_metainfo as $key => $val) {
					if($val !== null) {
						if((string)$val == '') {
							$this->PageViewSetErrorStatus(503, 'Optimizer Minify Strategy requires: '.$key);
							return;
						} //end if
					} //end if
				} //end foreach
			} //end if
		} //end if
		$arr_markers = [
			'MODAL' 			=> (string) 'no',
			'YEAR' 				=> (string) date('Y'),
			'VERSION' 			=> (string) AppCodeUtils::getVersion(),
			'NAME' 				=> (string) $name_all,
			'NAME-PREFIX' 		=> (string) $name_prefix,
			'NAME-SUFFIX' 		=> (string) $name_suffix,
			'MOD-VIEW-PATH' 	=> (string) $this->ControllerGetParam('module-view-path'),
			'PHP-SELF-VER' 		=> (string) $arr_utils_metainfo['PHP-SELF-VER'],
			'PHP-BIN-VER' 		=> (string) $arr_utils_metainfo['PHP-BIN-VER'],
			'NODE-BIN-VER' 		=> (string) $arr_utils_metainfo['NODE-BIN-VER'],
			'JS-MIN-VER' 		=> (string) $arr_utils_metainfo['JS-MIN-VER'],
			'JS-LINT-MODE' 		=> (string) $arr_utils_metainfo['JS-LINT-MODE'],
			'CSS-MIN-VER' 		=> (string) $arr_utils_metainfo['CSS-MIN-VER'],
			'CSS-LINT-MODE' 	=> (string) $arr_utils_metainfo['CSS-LINT-MODE'],
			'SF-VER' 			=> (string) ' :: '.AppCodeUtils::getSfVersion(),
			'MOD-SELF-URL' 		=> (string) 'task.php?page='.Smart::escape_url($this->ControllerGetParam('module')).'.',
			'CTRL-SELF-NAME' 	=> (string) $this->ControllerGetParam('action'),
			'APP-ID' 			=> '',
		];
		$arr_utils_metainfo = null;
		//--
		$action = (string) $this->RequestVarGet('action', '', 'string');
		if((string)$action == 'pass-utility') {
			$this->PageViewSetVars([
				'semaphore' => (string) Smart::array_to_list($semaphores),
				'title' 	=> (string) $name_all,
				'main' 		=> (string) SmartMarkersTemplating::render_file_template(
					(string) $this->ControllerGetParam('module-view-path').'app-manage-pass-utility.mtpl.htm', // {{{SYNC-APP-RELEASE-TPL-VARS}}}
					(array)$arr_markers
				)
			]);
			return;
		} //end if
		//--
		$last_package = '';
		$last_pack_dwn_url = '';
		$appcodeunpack_script = '';
		$appcodeunpack_dwn_url = '';
		//--
		if((string)$appid != '') {
			//--
			$yaml_settings = AppCodeUtils::parseYamlSettings((string)$appid); // mixed
			if(!is_int($yaml_settings) OR ($yaml_settings !== 8)) { // there are 7 mandatory settings + 1 (app id) ... see the YAML file
				$this->PageViewSetErrorStatus(503, 'YAML SETTINGS PARSE ERROR: #'.$yaml_settings);
				return;
			} //end if
			//--
			$applist = AppCodeUtils::getAppsFromYamlSettings(); // mixed
			if(!is_array($applist)) {
				$this->PageViewSetErrorStatus(503, 'APP LIST PARSE ERROR: #'.$applist);
				return;
			} //end if
			if(!in_array((string)$appid, (array)array_keys((array)$applist))) {
				$this->PageViewSetErrorStatus(400, 'Invalid App ID Selected: '.$appid);
				return;
			} //end if
			//--
			if(!defined('TASK_APP_RELEASE_CODEPACK_MODE')) {
				$this->PageViewSetErrorStatus(500, 'A required constant is missing: TASK_APP_RELEASE_CODEPACK_MODE');
				return;
			} //end if
			if(!defined('TASK_APP_RELEASE_CODEPACK_APP_DIR')) {
				$this->PageViewSetErrorStatus(500, 'A required constant is missing: TASK_APP_RELEASE_CODEPACK_APP_DIR');
				return;
			} //end if
			if(!defined('TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR')) {
				$this->PageViewSetErrorStatus(500, 'A required constant is missing: TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR');
				return;
			} //end if
			if(!defined('APP_DEPLOY_SECRET')) {
				$this->PageViewSetErrorStatus(500, 'A required constant is missing: APP_DEPLOY_SECRET');
				return;
			} //end if
			//--
			$download_key = (string) SmartHashCrypto::checksum((string)$this->ControllerGetParam('controller').'#'.$appid); // generate a unique download key that will expire shortly
			$dwn = (string) trim((string)$this->RequestVarGet('dwn', '', 'string'));
			if((string)$dwn != '') {
				$this->PageViewSetCfgs([
					'download-key' 		=> (string) $download_key,
					'download-packet' 	=> (string) $dwn
				]);
				return;
			} //end if
			//--
			if(SmartFileSystem::is_type_file((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'package-errors.log')) {
				$archives = (array) (new SmartGetFileSystem(true))->get_storage((string)TASK_APP_RELEASE_CODEPACK_APP_DIR, false, false, '.z-netarch');
				if(Smart::array_size($archives['list-files']) > 0) {
					$last_package = (string) $archives['list-files'][0];
				} //end if
				$archives = null;
			} //end if
			if((string)$last_package != '') {
				$last_pack_dwn_url = (string) SmartFrameworkRuntime::Create_Download_Link((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.$last_package, (string)$download_key); // generate an encrypted internal download link to serve that file once
			} //end if
			//--
			$arr_actions = [
				'#OPTGROUP#Default#Tasks#' 	=> (string) $name_all.' :: RELEASE @ TASKS',
				'release-info' 				=> (string) 'RELEASE.INFO :: Display Release Info and Settings'
			];
			if(!SmartFileSystem::path_exists((string)TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR)) { // if no optimizations dir
				$arr_actions['code-optimize'] = 'RELEASE.OPTIMIZE :: Optimize ['.TASK_APP_RELEASE_CODEPACK_MODE.'] Source Code: PHP / Javascript / CSS';
			} else {
				if(SmartFileSystem::is_type_file((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'optimization-errors.log')) { // if optimize completed
					if(SmartFileSystem::is_type_file((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'appcodeunpack.php')) {
						$appcodeunpack_script = (string) 'appcodeunpack.php';
						$appcodeunpack_dwn_url = (string) SmartFrameworkRuntime::Create_Download_Link((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'appcodeunpack.php', (string)$download_key); // generate an encrypted internal download link to serve that file once
						$arr_actions['code-netxunpack'] = 'RELEASE.MANAGER :: Remove the AppCodeUnPack Standalone Script';
					} else {
						$arr_actions['code-netunpack'] = 'RELEASE.MANAGER :: Generate the AppCodeUnPack Standalone Script';
					} //end if
					if((string)$last_package == '') {
						$arr_actions['code-netpack'] = 'RELEASE.PACKAGE :: Create the Release Package';
					} else {
						$arr_actions['code-deploy'] = 'RELEASE.DEPLOY :: Deploy the Release Package';
					} //end if else
				} //end if else
				$arr_actions['code-cleanup'] = 'RELEASE.CLEANUP :: Delete Optimizations Folder, Package and Release files';
			} //end if else
			//--
			if(defined('TASK_APP_RELEASE_EXTRA_ARR_TASKS')) {
				if(Smart::array_size(TASK_APP_RELEASE_EXTRA_ARR_TASKS) > 0) {
					if(Smart::array_type_test((array)TASK_APP_RELEASE_EXTRA_ARR_TASKS) == 2) { // associative
						foreach((array)TASK_APP_RELEASE_EXTRA_ARR_TASKS as $key => $val) {
							$key = (string) trim((string)$key);
							if((string)$key != '') {
								if(!array_key_exists((string)$key, (array)$arr_actions)) {
									if(Smart::is_nscalar($val)) {
										$val = (string) trim((string)$val);
										if((string)$val != '') {
											$arr_actions[(string)$key] = (string) $val;
										} //end if
									} //end if
								} //end if
							} //end if
						} //end foreach
					} //end if
				} //end if
			} //end if
			//--
			$html_select = (string) \SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::html_select_list_single(
				'task-run-sel',
				'',
				'form',
				(array) $arr_actions,
				'',
				'0/0',
				'onChange="try { manageButtonSelTask(); } catch(err) { console.log(\'manageButtonSelTask ERR:\', err) }" autocomplete="off"',
				'no',
				'yes',
				'--- NO TASK Selected ---',
				'class:ux-field-xl customList'
			);
			//--
		} else {
			//--
			$applist = AppCodeUtils::getAppsFromYamlSettings(); // mixed
			if(!is_array($applist)) {
				$this->PageViewSetErrorStatus(503, 'APP LIST PARSE ERROR: #'.$applist);
				return;
			} //end if
			//--
			$html_select = (string) \SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::html_select_list_single(
				'sel-app',
				'',
				'form',
				(array) $applist,
				'',
				'0/0',
				'onChange="try { manageButtonSelApp(); } catch(err) { console.log(\'manageButtonSelApp ERR:\', err) }" autocomplete="off"',
				'no',
				'yes',
				'--- NO APPLICATION Selected ---',
				'class:ux-field-xl customList'
			);
			//--
		} //end if else
		//--
		$this->PageViewSetVars([
			'semaphore' => (string) Smart::array_to_list($semaphores),
			'title' 	=> (string) $name_all,
			'main' 		=> (string) SmartMarkersTemplating::render_file_template(
				(string) $this->ControllerGetParam('module-view-path').'app-manage.mtpl.htm', // {{{SYNC-APP-RELEASE-TPL-VARS}}}
				(array) array_merge((array)$arr_markers, [
					'DATE-TIME' 		=> (string) gmdate('Y-m-d H:i:s +0000'),
					'APP-ID' 			=> (string) (defined('APPCODEPACK_APP_ID') ? APPCODEPACK_APP_ID : ''),
					'APP-DEPLOY-HASH' 	=> (string) (defined('APP_DEPLOY_HASH') ? APP_DEPLOY_HASH : ''),
					'APP-DEPLOY-USER' 	=> (string) (defined('APP_DEPLOY_AUTH_USERNAME') ? APP_DEPLOY_AUTH_USERNAME : ''),
					'APP_DEPLOY_URLS' 	=> (string) (defined('APP_DEPLOY_URLS') ? APP_DEPLOY_URLS : ''),
					'HTML-POWERED-INFO' => (string) SmartComponents::app_powered_info(
						'yes',
						[
							[],
							[
								'type' => 'sside',
								'name' => (string) ((defined('TASK_APP_RELEASE_CODEPACK_MODE') && ((string)TASK_APP_RELEASE_CODEPACK_MODE == 'minify')) ? 'NodeJS'.((defined('TASK_APP_RELEASE_CODEPACK_NODEJS_VERSION') && ((string)TASK_APP_RELEASE_CODEPACK_NODEJS_VERSION != '')) ? ' :: '.TASK_APP_RELEASE_CODEPACK_NODEJS_VERSION : '') : 'AppCodePack'),
								'logo' => (string) SmartUtils::get_server_current_url().((defined('TASK_APP_RELEASE_CODEPACK_MODE') && ((string)TASK_APP_RELEASE_CODEPACK_MODE == 'minify')) ? 'lib/framework/img/nodejs-logo.svg' : 'lib/framework/img/netarch-logo.svg'),
								'url' => ((defined('TASK_APP_RELEASE_CODEPACK_MODE') && ((string)TASK_APP_RELEASE_CODEPACK_MODE == 'minify')) ? 'https://nodejs.org' : ''),
							],
							[
								'type' => 'cside',
								'name' => (string) $arr_bw['desc'],
								'logo' => (string) SmartUtils::get_server_current_url().$arr_bw['img'],
								'url' => '',
							]
						],
						true, // no dbs
						true, // watch
						false // hide logo
					),
					'HTML-LIST-SEL' 		=> (string) $html_select,
					'LAST-PACKAGE' 			=> (string) $last_package,
					'LAST-PKG-DWN-URL' 		=> (string) ($last_pack_dwn_url ? 'task.php?page='.Smart::escape_url($this->ControllerGetParam('controller')).'&appid='.Smart::escape_url((string)$appid).'&dwn='.Smart::escape_url((string)$last_pack_dwn_url) : ''),
					'APP-UNPACK-STD' 		=> (string) $appcodeunpack_script,
					'APP-UNPACK-DWN-URL' 	=> (string) ($appcodeunpack_dwn_url ? 'task.php?page='.Smart::escape_url($this->ControllerGetParam('controller')).'&appid='.Smart::escape_url((string)$appid).'&dwn='.Smart::escape_url((string)$appcodeunpack_dwn_url) : ''),
				])
			)
		]);
		//--

	} //END FUNCTION


} //END CLASS

// end of php code
