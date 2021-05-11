<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// Class: \SmartModExtLib\AppRelease\AbstractTaskController
// (c) 2006-2021 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

namespace SmartModExtLib\AppRelease;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
//=====================================================================================

/**
 * Abstract Task Controller
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20210511
 *
 */
abstract class AbstractTaskController extends \SmartAbstractAppController {

	protected $title = 'App Release Task (Abstract)';
	protected $err = '';
	protected $msg = '';

	protected $details = false;

	protected $modal = false;
	protected $working = false;
	protected $workstop = false;
	protected $selfclose = 0;
	protected $endscroll = false;

	private $appid = '';


	final public function getAppId() {
		//--
		return (string) $this->appid;
		//--
	} //END FUNCTION


	final public function Initialize() {
		//--
		$name_prefix = 'AppRelease';
		$name_suffix = 'CodePack';
		$name_all = (string) $name_prefix.'.'.$name_suffix;
		//--
		if((!\defined('\\SMART_APP_MODULE_DIRECT_OUTPUT')) OR (\SMART_APP_MODULE_DIRECT_OUTPUT !== true)) {
			\SmartFrameworkRuntime::Raise500Error(__METHOD__.' # Invalid Controller Mode. Must be with: SMART_APP_MODULE_DIRECT_OUTPUT=true !');
			return;
		} //end if
		//--
		$this->appid = (string) \trim((string)$this->RequestVarGet('appid', '', 'string'));
		//--
		if((string)$this->appid == '') {
			\SmartFrameworkRuntime::Raise400Error('App ID is Empty');
			return;
		} //end if
		//--
		$yaml_settings = \AppCodeUtils::parseYamlSettings((string)$this->appid); // mixed
		if(!\is_int($yaml_settings) OR ($yaml_settings !== 8)) { // there are 7 mandatory settings + 1 (app id) ... see the YAML file
			\SmartFrameworkRuntime::Raise503Error('YAML SETTINGS PARSE ERROR: '.$yaml_settings);
			return;
		} //end if
		//--
		$ini_settings = \AppCodeUtils::parseIniSettings(); // mixed
		if(!\is_int($ini_settings) OR ($ini_settings !== 3)) { // there are 3 mandatory settings: MAX RUN TIMEOUT, PHP and NODE executables {{{SYNC-CHECK-APP-INI-SETTINGS}}}
			\SmartFrameworkRuntime::Raise503Error('INI SETTINGS PARSE ERROR: '.$ini_settings);
			return;
		} //end if
		//--
		$applist = \AppCodeUtils::getAppsFromYamlSettings(); // mixed
		if(!\is_array($applist)) {
			\SmartFrameworkRuntime::Raise503Error('APP LIST PARSE ERROR: #'.$applist);
			return;
		} //end if
		if(!\in_array((string)$this->appid, (array)\array_keys((array)$applist))) {
			\SmartFrameworkRuntime::Raise400Error('Invalid App ID Selected: '.$this->appid);
			return;
		} //end if
		//--
		\SmartFrameworkRuntime::outputHttpHeadersNoCache();
		echo (string) \SmartMarkersTemplating::render_file_template(
			$this->ControllerGetParam('module-tpl-path').'tpl-task-start.mtpl.htm',
			\SmartComponents::set_app_template_conform_metavars([ // {{{SYNC-APP-RELEASE-TPL-VARS}}}
				'WORKING' 			=> (string) (($this->working === true) ? 'yes' : 'no'),
				'WORKSTOP' 			=> (string) (($this->workstop === true) ? 'yes' : 'no'),
				'MODAL' 			=> (string) (($this->modal === true) ? 'yes' : 'no'),
				'TITLE' 			=> (string) $this->title,
				'VERSION' 			=> (string) \AppCodeUtils::getVersion(),
				'NAME' 				=> (string) $name_all,
				'NAME-PREFIX' 		=> (string) $name_prefix,
				'NAME-SUFFIX' 		=> (string) $name_suffix,
				'MOD-VIEW-PATH' 	=> (string) $this->ControllerGetParam('module-view-path'),
				'PHP-BIN-VER' 		=> (string) (defined('\\TASK_APP_RELEASE_CODEPACK_PHP_BIN') ? ' @ '.\TASK_APP_RELEASE_CODEPACK_PHP_BIN : '').(defined('\\TASK_APP_RELEASE_CODEPACK_PHP_VERSION') ? ' :: '.\TASK_APP_RELEASE_CODEPACK_PHP_VERSION : ''),
				'NODE-BIN-VER' 		=> (string) (defined('\\TASK_APP_RELEASE_CODEPACK_NODEJS_BIN') ? ' @ '.\TASK_APP_RELEASE_CODEPACK_NODEJS_BIN : '').(defined('\\TASK_APP_RELEASE_CODEPACK_NODEJS_VERSION') ? ' :: '.\TASK_APP_RELEASE_CODEPACK_NODEJS_VERSION : ''),
				'JS-MIN-VER' 		=> (string) (defined('\\TASK_APP_RELEASE_CODEPACK_NODE_MODULE_MINIFY_JS') ? ' :: '.\TASK_APP_RELEASE_CODEPACK_NODE_MODULE_MINIFY_JS : ''),
				'CSS-MIN-VER' 		=> (string) (defined('\\TASK_APP_RELEASE_CODEPACK_NODE_MODULE_MINIFY_CSS') ? ' :: '.\TASK_APP_RELEASE_CODEPACK_NODE_MODULE_MINIFY_CSS : ''),
				'SF-VER' 			=> (string) ' :: '.\AppCodeUtils::getSfVersion(),
				'APP-ID' 			=> (string) (\defined('\\APPCODEPACK_APP_ID') ? \APPCODEPACK_APP_ID : ''),
				'APP-DEPLOY-HASH' 	=> (string) (\defined('\\APP_DEPLOY_HASH') ? \APP_DEPLOY_HASH : ''),
				'APP-DEPLOY-USER' 	=> (string) (\defined('\\APP_DEPLOY_AUTH_USERNAME') ? \APP_DEPLOY_AUTH_USERNAME : ''),
				'APP_DEPLOY_URLS' 	=> (string) (\defined('\\APP_DEPLOY_URLS') ? \APP_DEPLOY_URLS : ''),
				'APP-FOLDERS' 		=> (string) 'Folders: '.(\defined('\\APP_DEPLOY_FOLDERS') ? \SmartUtils::pretty_print_var(\Smart::json_decode((string)\APP_DEPLOY_FOLDERS)) : ''),
				'APP-FILES' 		=> (string) 'Files: '.(\defined('\\APP_DEPLOY_FILES') ? \SmartUtils::pretty_print_var(\Smart::json_decode((string)\APP_DEPLOY_FILES)) : ''),
				'APP-DETAILS' 		=> (string) (($this->details !== false) ? 'yes' : 'no'),
			])
		);
		$this->InstantFlush();
		//--
	} //END FUNCTION


	final public function ShutDown() {
		//--
		$err = (string) \trim((string)$this->err);
		//--
		if((string)$err != '') {
			echo (string) \SmartComponents::operation_error((string)\Smart::escape_html((string)$err));
		} else {
			echo (string) \SmartComponents::operation_success('OK: Completed ... '.\Smart::escape_html((string)$this->msg));
		} //end if
		$this->InstantFlush();
		//--
		echo (string) \SmartMarkersTemplating::render_file_template(
			$this->ControllerGetParam('module-tpl-path').'tpl-task-end.mtpl.htm',
			(array) \SmartComponents::set_app_template_conform_metavars([
				'TITLE' 			=> (string) $this->title,
				'YEAR' 				=> (string) \date('Y'),
				'HAVE-ERRORS' 		=> (string) (\strlen((string)$err) ? 'yes' : 'no'),
				'MODAL' 			=> (string) (($this->modal === true) ? 'yes' : 'no'),
				'SELFCLOSE' 		=> (string) (((int)$this->selfclose > 0) ? (int)$this->selfclose : 0),
				'ENDSCROLL' 		=> (string) (($this->endscroll === true) ? 'yes' : 'no'),
			])
		);
		$this->InstantFlush();
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
