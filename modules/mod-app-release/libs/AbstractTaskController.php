<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// Class: \SmartModExtLib\AppRelease\AbstractTaskController
// (c) 2006-2021 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

namespace SmartModExtLib\AppRelease;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_DIRECT_OUTPUT', true);

//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
//=====================================================================================

/**
 * Task Controller: Abstract Custom Task
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20210526
 *
 */
abstract class AbstractTaskController extends \SmartAbstractAppController {

	protected $title = 'App Release Task (Abstract)';

	protected $sficon = '';
	protected $msg = '';
	protected $err = '';
	protected $notice = '';
	protected $notehtml = '';

	protected $goback = '';

	protected $details = false;

	protected $modal = false;
	protected $workvar = '';
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
		if(!\is_int($ini_settings) OR ((int)$ini_settings !== 3)) { // there are 3 mandatory settings: MAX RUN TIMEOUT and PHP executable {{{SYNC-CHECK-APP-INI-SETTINGS}}}
			\SmartFrameworkRuntime::Raise503Error('INI SETTINGS PARSE ERROR: Num Req. is: #'.$ini_settings);
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
		if((string)$this->workvar != '') { // conditional by workvar
			if(\SmartFrameworkSecurity::ValidateUrlVariableName((string)$this->workvar)) {
				if(!$this->RequestVarGet((string)$this->workvar)) {
					$this->working = false;
				} //end if
			} //end if
		} //end if
		//--
		\SmartFrameworkRuntime::outputHttpHeadersNoCache();
		$arr_utils_metainfo = (array) \AppCodeUtils::getArrIniMetaInfo();
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
			])
		);
		$this->InstantFlush();
		//--
	} //END FUNCTION


	final public function ShutDown() {
		//--
		$err = (string) \trim((string)$this->err);
		$notice = (string) \trim((string)$this->notice);
		//--
		$icon = '';
		if(\is_array($this->sficon) AND (\Smart::array_size($this->sficon) > 0)) {
			foreach($this->sficon as $key => $val) {
				if(\Smart::is_nscalar($val)) {
					if((string)\trim((string)$val) != '') {
						$icon .= ' &nbsp;&nbsp; <i class="sfi sfi-2x sfi-'.\Smart::escape_html((string)$val).'"></i>';
					} //end if
				} //end if
			} //end foreach
		} elseif((string)trim((string)$this->sficon) != '') {
			$icon = ' &nbsp;&nbsp; <i class="sfi sfi-2x sfi-'.\Smart::escape_html((string)$this->sficon).'"></i>';
		} //end if
		//--
		if((string)$err != '') {
			echo (string) \SmartComponents::operation_error((string)\Smart::nl_2_br((string)\Smart::escape_html((string)$err)).$icon);
		} elseif((string)$notice != '') {
			echo (string) \SmartComponents::operation_notice((string)\Smart::nl_2_br((string)\Smart::escape_html((string)$notice)).$icon);
			echo "\n";
			echo (string) $this->notehtml;
		} else {
			echo (string) \SmartComponents::operation_success('OK: Completed ... '.\Smart::nl_2_br((string)\Smart::escape_html((string)$this->msg)).$icon);
		} //end if
		$this->InstantFlush();
		//--
		echo (string) \SmartMarkersTemplating::render_file_template(
			$this->ControllerGetParam('module-tpl-path').'tpl-task-end.mtpl.htm',
			(array) \SmartComponents::set_app_template_conform_metavars([
				'TITLE' 			=> (string) $this->title,
				'YEAR' 				=> (string) \date('Y'),
				'WORKING' 			=> (string) (($this->working === true) ? 'yes' : 'no'),
				'HAVE-ERRORS' 		=> (string) (\strlen((string)$err) ? 'yes' : 'no'),
				'HAVE_NOTICE' 		=> (string) (\strlen((string)$notice) ? 'yes' : 'no'),
				'MODAL' 			=> (string) (($this->modal === true) ? 'yes' : 'no'),
				'SELFCLOSE' 		=> (string) (((int)$this->selfclose > 0) ? (int)$this->selfclose : 0),
				'ENDSCROLL' 		=> (string) (($this->endscroll === true) ? 'yes' : 'no'),
				'GO-BACK-URL' 		=> (string) $this->goback,
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
