<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// Controller: AppRelease/Manage
// Route: ?/page/app-release.manage (?page=app-release.manage)
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
 * Task Controller
 *
 * @ignore
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
			if(!is_int($ini_settings) OR ($ini_settings !== 3)) { // there are 3 mandatory settings: MAX RUN TIMEOUT, PHP and NODE executables {{{SYNC-CHECK-APP-INI-SETTINGS}}}
				$this->PageViewSetErrorStatus(503, 'INI SETTINGS PARSE ERROR: #'.$ini_settings);
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
			$html_select = (string) SmartViewHtmlHelpers::html_select_list_single(
				'task-run-sel',
				'',
				'form',
				[
					'#OPTGROUP#Default' => (string) $name_all.' @ TASKS.RELEASE :: DEFAULT',
					'code-optimize' 	=> 'RELEASE.OPTIMIZE :: Minify Source Code: PHP / Javascript / CSS',
					'code-netpack' 		=> 'RELEASE.PACKAGE :: Create the Release Package',
					'code-deploy' 		=> 'RELEASE.DEPLOY :: Deploy the Release Package',
					'code-cleanup' 		=> 'RELEASE.CLEANUP :: Cleanup Previous Optimizations',
				],
				'',
				'0/0',
				'onChange="try { manageButtonSelTask(); } catch(err) { console.log(\'manageButtonSelTask ERR:\', err) }" autocomplete="off"',
				'no',
				'yes',
				'--- NO TASK Selected ---',
				'class:ux-field-xl customList'
			);
			//--
			$last_package = '';
			$last_pack_dwn_url = '';
			if(SmartFileSystem::is_type_file((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'package-errors.log')) {
				$archives = (array) (new \SmartGetFileSystem(true))->get_storage((string)TASK_APP_RELEASE_CODEPACK_APP_DIR, false, false, '.z-netarch');
				if(Smart::array_size($archives['list-files']) > 0) {
					$last_package = (string) $archives['list-files'][0];
				} //end if
				$archives = null;
			} //end if
			//--
			if((string)$last_package != '') {
				$last_pack_dwn_url = (string) SmartFrameworkRuntime::Create_Download_Link((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.$last_package, (string)$download_key); // generate an encrypted internal download link to serve that file once
			} //end if
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
		$this->PageViewSetVars([
			'semaphore' => (string) Smart::array_to_list($semaphores),
			'title' 	=> (string) $name_all,
			'main' 		=> (string) SmartMarkersTemplating::render_file_template(
				$this->ControllerGetParam('module-view-path').'app-manage.mtpl.htm', // {{{SYNC-APP-RELEASE-TPL-VARS}}}
				[
					'MODAL' 			=> (string) 'no',
					'YEAR' 				=> (string) date('Y'),
					'VERSION' 			=> (string) AppCodeUtils::getVersion(),
					'NAME' 				=> (string) $name_all,
					'NAME-PREFIX' 		=> (string) $name_prefix,
					'NAME-SUFFIX' 		=> (string) $name_suffix,
					'MOD-VIEW-PATH' 	=> (string) $this->ControllerGetParam('module-view-path'),
					'PHP-BIN-VER' 		=> (string) (defined('TASK_APP_RELEASE_CODEPACK_PHP_BIN') ? ' @ '.TASK_APP_RELEASE_CODEPACK_PHP_BIN : '').(defined('TASK_APP_RELEASE_CODEPACK_PHP_VERSION') ? ' :: '.TASK_APP_RELEASE_CODEPACK_PHP_VERSION : ''),
					'NODE-BIN-VER' 		=> (string) (defined('TASK_APP_RELEASE_CODEPACK_NODEJS_BIN') ? ' @ '.TASK_APP_RELEASE_CODEPACK_NODEJS_BIN : '').(defined('TASK_APP_RELEASE_CODEPACK_NODEJS_VERSION') ? ' :: '.TASK_APP_RELEASE_CODEPACK_NODEJS_VERSION : ''),
					'JS-MIN-VER' 		=> (string) (defined('TASK_APP_RELEASE_CODEPACK_NODE_MODULE_MINIFY_JS') ? ' :: '.TASK_APP_RELEASE_CODEPACK_NODE_MODULE_MINIFY_JS : ''),
					'CSS-MIN-VER' 		=> (string) (defined('TASK_APP_RELEASE_CODEPACK_NODE_MODULE_MINIFY_CSS') ? ' :: '.TASK_APP_RELEASE_CODEPACK_NODE_MODULE_MINIFY_CSS : ''),
					'SF-VER' 			=> (string) ' :: '.AppCodeUtils::getSfVersion(),
					'MOD-SELF-URL' 		=> (string) 'task.php?page='.Smart::escape_url($this->ControllerGetParam('module')).'.',
					'CTRL-SELF-NAME' 	=> (string) $this->ControllerGetParam('action'),
					'HTML-POWERED-INFO' => (string) SmartComponents::app_powered_info(
						'yes',
						[
							[],
							[
								'type' => 'sside',
								'name' => (string) 'NodeJS'.((defined('TASK_APP_RELEASE_CODEPACK_NODEJS_VERSION') && ((string)TASK_APP_RELEASE_CODEPACK_NODEJS_VERSION != '')) ? ' :: '.TASK_APP_RELEASE_CODEPACK_NODEJS_VERSION : ''),
								'logo' => (string) SmartUtils::get_server_current_url().'lib/framework/img/nodejs-logo.svg',
								'url' => 'https://nodejs.org'
							],
							[
								'type' => 'cside',
								'name' => (string) $arr_bw['desc'],
								'logo' => (string) SmartUtils::get_server_current_url().$arr_bw['img'],
								'url' => ''
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
