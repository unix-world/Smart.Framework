<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// Controller: AppRelease/AppManage
// Route: ?/page/app-release.app-manage (?page=app-release.app-manage)
// (c) 2013-2021 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

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
 * @version 	v.20210522
 *
 */
final class SmartAppTaskController extends SmartAbstractAppController {


	public function Run() {

		//--
		$name_prefix = 'AppRelease';
		$name_suffix = 'CodePack';
		$name_all = (string) $name_prefix.'.'.$name_suffix;
		//--

		//--
		$semaphores = [];
		$semaphores[] = 'load:ui-toolkit';
		$semaphores[] = 'load:jquery';
		$semaphores[] = 'load:growl';
		$semaphores[] = 'load:alertable';
		$semaphores[] = 'load:sf-js';
		$semaphores[] = 'load:utils-js';
		//--

		//--
		$arr_bw = (array) SmartComponents::get_imgdesc_by_bw_id((string)SmartUtils::get_os_browser_ip('bw'));
		//--

		//--
		$appid = (string) trim((string)$this->RequestVarGet('appid', '', 'string'));
		//--
		if((string)$appid != '') {
			//--
			$download_key = (string) sha1((string)$this->ControllerGetParam('controller').'#'.$appid.'#'.SMART_FRAMEWORK_SECURITY_KEY); // generate a unique download key that will expire shortly
			//--
			$pkg = (string) trim((string)$this->RequestVarGet('pkg', '', 'string'));
			if((string)$pkg != '') {
				$this->PageViewSetCfgs([
					'download-key' 		=> (string) $download_key,
					'download-packet' 	=> (string) $pkg
				]);
				return;
			} //end if
			//--
			$yaml_settings = AppCodeUtils::parseYamlSettings((string)$appid); // mixed
			if(!is_int($yaml_settings) OR ($yaml_settings !== 8)) { // there are 7 mandatory settings + 1 (app id) ... see the YAML file
				$this->PageViewSetErrorStatus(503, 'YAML SETTINGS PARSE ERROR: #'.$yaml_settings);
				return;
			} //end if
			//--
			$ini_settings = AppCodeUtils::parseIniSettings(); // mixed
			if(!is_int($ini_settings) OR ($ini_settings !== 2)) { // there are 2 mandatory settings: MAX RUN TIMEOUT and PHP executable {{{SYNC-CHECK-APP-INI-SETTINGS}}}
				$this->PageViewSetErrorStatus(503, 'INI SETTINGS PARSE ERROR: Num Req. is: #'.$ini_settings);
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
			//--
			$last_package = '';
			$last_pack_dwn_url = '';
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
				'#OPTGROUP#Default' => (string) $name_all.' @ TASKS.RELEASE :: DEFAULT',
				'release-info' 		=> (string) 'RELEASE.INFO :: Display Release Info and Settings'
			];
			if(!SmartFileSystem::path_exists((string)TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR)) { // if no optimizations dir
				$arr_actions['code-optimize'] = 'RELEASE.OPTIMIZE :: Optimize ['.TASK_APP_RELEASE_CODEPACK_MODE.'] Source Code: PHP / Javascript / CSS';
			} else {
				if(SmartFileSystem::is_type_file((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'optimization-errors.log')) { // if optimize completed
					if(SmartFileSystem::is_type_file((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'appcodeunpack.php')) {
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
			$html_select = (string) SmartViewHtmlHelpers::html_select_list_single(
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
			$html_select = (string) SmartViewHtmlHelpers::html_select_list_single(
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
			$last_package = '';
			$last_pack_dwn_url = '';
			//--
		} //end if else
		//--

		//--
		$arr_utils_metainfo = (array) AppCodeUtils::getArrIniMetaInfo();
		if((string)$appid != '') {
			if((string)TASK_APP_RELEASE_CODEPACK_MODE == 'minify') {
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
		//--
		$this->PageViewSetVars([
			'semaphore' => (string) Smart::array_to_list($semaphores),
			'title' 	=> (string) $name_all,
			'main' 		=> (string) SmartMarkersTemplating::render_file_template(
				(string) $this->ControllerGetParam('module-view-path').'app-manage.mtpl.htm', // {{{SYNC-APP-RELEASE-TPL-VARS}}}
				[
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
					'HTML-LIST-SEL' 	=> (string) $html_select,
					'APP-ID' 			=> (string) (defined('APPCODEPACK_APP_ID') ? APPCODEPACK_APP_ID : ''),
					'APP-DEPLOY-HASH' 	=> (string) (defined('APP_DEPLOY_HASH') ? APP_DEPLOY_HASH : ''),
					'APP-DEPLOY-USER' 	=> (string) (defined('APP_DEPLOY_AUTH_USERNAME') ? APP_DEPLOY_AUTH_USERNAME : ''),
					'APP_DEPLOY_URLS' 	=> (string) (defined('APP_DEPLOY_URLS') ? APP_DEPLOY_URLS : ''),
					'LAST-PACKAGE' 		=> (string) $last_package,
					'LAST-PKG-DWN-URL' 	=> (string) ($last_pack_dwn_url ? 'task.php?page='.Smart::escape_url($this->ControllerGetParam('controller')).'&appid='.Smart::escape_url((string)$appid).'&pkg='.Smart::escape_url((string)$last_pack_dwn_url) : ''),
				]
			)
		]);
		//--

	} //END FUNCTION

} //END CLASS

// end of php code
