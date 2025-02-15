<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// Class: \SmartModExtLib\AppRelease\AbstractTaskController
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

namespace SmartModExtLib\AppRelease;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

if(!\SmartAppInfo::TestIfModuleExists('mod-auth-admins')) {
	\SmartFrameworkRuntime::Raise500Error('Mod AuthAdmins is missing !');
	die('Mod AuthAdmins is missing !');
} //end if

// SMART_APP_MODULE_DIRECT_OUTPUT :: TRUE :: # by parent class

//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
//=====================================================================================

/**
 * Task Controller: Abstract Custom Task
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20250207
 *
 */
abstract class AbstractTaskController extends \SmartModExtLib\AuthAdmins\AbstractTaskController {

	protected $title = 'App Release Task (Abstract)';

	protected $name_prefix = 'App';
	protected $name_suffix = 'Task';
	protected $app_tpl = ''; // path/to/some.mtpl.htm
	protected $app_main_url = '';

	private $appid = '';
	protected $details = false;


	final public function getAppId() {
		//--
		return (string) $this->appid;
		//--
	} //END FUNCTION


	protected function InitTask() {
		//--
		if(!$this->TestDirectOutput()) {
			return 'ERROR: Direct Output is not enabled ...';
		} //end if
		//--
		$this->appid = (string) \trim((string)$this->RequestVarGet('appid', '', 'string'));
		if((string)$this->appid == '') {
			\SmartFrameworkRuntime::Raise400Error('App ID is Empty');
			return false;
		} //end if
		//--
		$yaml_settings = \AppCodeUtils::parseYamlSettings((string)$this->appid); // mixed
		if(!\is_int($yaml_settings) OR ($yaml_settings !== 8)) { // there are 7 mandatory settings + 1 (app id) ... see the YAML file
			\SmartFrameworkRuntime::Raise503Error('YAML SETTINGS PARSE ERROR: '.$yaml_settings);
			return false;
		} //end if
		//--
		$ini_settings = \AppCodeUtils::parseIniSettings(); // mixed
		if(!\is_int($ini_settings) OR ((int)$ini_settings !== 3)) { // there are 3 mandatory settings: MAX RUN TIMEOUT and PHP executable {{{SYNC-CHECK-APP-INI-SETTINGS}}}
			\SmartFrameworkRuntime::Raise503Error('INI SETTINGS PARSE ERROR: Num Req. is: #'.$ini_settings);
			return false;
		} //end if
		//--
		$applist = \AppCodeUtils::getAppsFromYamlSettings(); // mixed
		if(!\is_array($applist)) {
			\SmartFrameworkRuntime::Raise503Error('APP LIST PARSE ERROR: #'.$applist);
			return false;
		} //end if
		if(!\in_array((string)$this->appid, (array)\array_keys((array)$applist))) {
			\SmartFrameworkRuntime::Raise400Error('Invalid App ID Selected: '.$this->appid);
			return false;
		} //end if
		//--
		if((string)$this->workvar != '') { // conditional by workvar
			if(\SmartFrameworkSecurity::ValidateUrlVariableName((string)$this->workvar)) {
				if(!$this->RequestVarGet((string)$this->workvar)) {
					$this->working = false;
				} //end if
			} //end if
		} //end if
		//--
		$this->name_prefix = 'AppRelease';
		$this->name_suffix = 'CodePack';
		//--
		$this->app_tpl = 'modules/mod-app-release/views/app-task.mtpl.htm';
		$this->app_main_url = (string) $this->ControllerGetParam('url-script').'?page='.\Smart::escape_url((string)$this->ControllerGetParam('module').'.app-manage').'&appid='.\Smart::escape_url((string)$this->appid);
		//--
		$arr_utils_metainfo = (array) \AppCodeUtils::getArrIniMetaInfo(); // requires \AppCodeUtils::parseIniSettings() !!
		//--
		return array(
			'VERSION' 			=> (string) \AppCodeUtils::getVersion(),
			'PHP-SELF-VER' 		=> (string) $arr_utils_metainfo['PHP-SELF-VER'],
			'PHP-BIN-VER' 		=> (string) $arr_utils_metainfo['PHP-BIN-VER'],
			'NODE-BIN-VER' 		=> (string) $arr_utils_metainfo['NODE-BIN-VER'],
			'JS-MIN-VER' 		=> (string) $arr_utils_metainfo['JS-MIN-VER'],
			'JS-LINT-MODE' 		=> (string) $arr_utils_metainfo['JS-LINT-MODE'],
			'CSS-MIN-VER' 		=> (string) $arr_utils_metainfo['CSS-MIN-VER'],
			'CSS-LINT-MODE' 	=> (string) $arr_utils_metainfo['CSS-LINT-MODE'],
			'SF-VER' 			=> (string) ' :: '.\AppCodeUtils::getSfVersion(),
			'APP-ID' 			=> (string) (\defined('\\APPCODEPACK_APP_ID') ? \APPCODEPACK_APP_ID : ''),
			'APP-DEPLOY-HASH' 	=> (string) (\defined('\\APP_DEPLOY_HASH') ? \APP_DEPLOY_HASH : ''),
			'APP-DEPLOY-USER' 	=> (string) (\defined('\\APP_DEPLOY_AUTH_USERNAME') ? \APP_DEPLOY_AUTH_USERNAME : ''),
			'APP_DEPLOY_URLS' 	=> (string) (\defined('\\APP_DEPLOY_URLS') ? \APP_DEPLOY_URLS : ''),
			'APP-STRATEGY' 		=> (string) (\defined('\\TASK_APP_RELEASE_CODEPACK_MODE') ? \TASK_APP_RELEASE_CODEPACK_MODE : ''),
			'APP-FOLDERS' 		=> (string) 'Folders: '.(\defined('\\APP_DEPLOY_FOLDERS') ? \SmartUtils::pretty_print_var(\Smart::json_decode((string)\APP_DEPLOY_FOLDERS)) : ''),
			'APP-FILES' 		=> (string) 'Files: '.(\defined('\\APP_DEPLOY_FILES') ? \SmartUtils::pretty_print_var(\Smart::json_decode((string)\APP_DEPLOY_FILES)) : ''),
			'APP-DETAILS' 		=> (string) (($this->details !== false) ? 'yes' : 'no'),
		);
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
